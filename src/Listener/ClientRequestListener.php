<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Event\RequestReceived;
use Hyperf\HttpServer\Event\RequestTerminated;
use Hyperf\Stringable\Str;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Psr\Http\Message\ServerRequestInterface;

class ClientRequestListener extends InstrumentationListener implements ListenerInterface
{
    private float $startTime;

    public function listen(): array
    {
        return [
            RequestReceived::class,
            RequestTerminated::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $this->switcher->isEnabled()) {
            return;
        }

        match ($event::class) {
            RequestReceived::class   => $this->onRequestReceived($event),
            RequestTerminated::class => $this->onRequestTerminated($event),
            default                  => null,
        };
    }

    protected function onRequestReceived(RequestReceived $event): void
    {
        $this->startTime = microtime(true);

        $context = Globals::propagator()->extract($event->request->getHeaders());

        $span = $this->instrumentation->tracer()
            ->spanBuilder('[HTTP] ' . $event->request->getMethod())
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setParent($context)
            ->startSpan();

        $span->setAttributes([
            TraceAttributes::HTTP_REQUEST_METHOD => $event->request->getMethod(),
            TraceAttributes::URL_FULL            => (string) $event->request->getUri(),
            TraceAttributes::URL_PATH            => $event->request->getUri()->getPath(),
            TraceAttributes::URL_SCHEME          => $event->request->getUri()->getScheme(),
            TraceAttributes::SERVER_ADDRESS      => $event->request->getUri()->getHost(),
            TraceAttributes::SERVER_PORT         => $event->request->getUri()->getPort(),
            TraceAttributes::USER_AGENT_ORIGINAL => $event->request->getHeaderLine('User-Agent'),
            TraceAttributes::URL_QUERY           => $event->request->getUri()->getQuery(),
            TraceAttributes::CLIENT_ADDRESS      => $this->getRequestIP($event->request),
            ...$this->transformHeaders('request', $event->request->getHeaders()),
        ]);

        Context::storage()->attach($span->storeInContext($context));
    }

    protected function onRequestTerminated(RequestTerminated $event): void
    {
        if (!$scope = Context::storage()->scope()) {
            return;
        }

        $span = Span::fromContext($scope->context());
        if (!$span->isRecording()) {
            $scope->detach();
            return;
        }

        $statusCode = $event->response->getStatusCode();

        $span->setAttributes([
            TraceAttributes::HTTP_RESPONSE_STATUS_CODE => $statusCode,
            TraceAttributes::HTTP_RESPONSE_BODY_SIZE   => $event->response->getBody()->getSize(),
            ...$this->transformHeaders('response', $event->response->getHeaders()),
        ]);

        $this->spanRecordException($span, $event->getThrowable());

        $span->end();
        $scope->detach();

        if ($this->switcher->isMetricsEnabled('client_request')) {
            $duration = (microtime(true) - $this->startTime) * 1000;

            $this->instrumentation->meter()
                ->createHistogram('http.server.duration', 'ms')
                ->record($duration, [
                    'http.method' => $event->request->getMethod(),
                    'http.status_code' => $statusCode,
                ]);
        }

        // Exporta todas as métricas do ciclo
        // TODO: validar
        $this->exporter->meterCollect();
    }

    private function transformHeaders(string $type, array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = Str::lower($key);
            if ($this->canTransformHeaders($type, $key)) {
                $result["http.{$type}.header.$key"] = implode(', ', $value);
            }
        }

        return $result;
    }

    private function canTransformHeaders(string $type, string $key): bool
    {
        $headers = (array) $this->config->get("open-telemetry.instrumentation.features.client_request.options.headers.$type", ['*']);

        foreach ($headers as $header) {
            if (Str::is(Str::lower($header), $key)) {
                return true;
            }
        }

        return false;
    }

    private function getRequestIP(ServerRequestInterface $request): string
    {
        $ips = $request->getHeaderLine('x-forwarded-for')
            ?: $request->getHeaderLine('remote-host')
                ?: $request->getHeaderLine('x-real-ip')
                    ?: $request->getServerParams()['remote_addr'] ?? '';

        return explode(',', $ips)[0] ?? '';
    }
}
