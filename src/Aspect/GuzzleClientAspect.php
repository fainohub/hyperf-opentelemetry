<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Aspect;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Stringable\Str;
use FainoHub\Hyperf\OpenTelemetry\Propagator\HeadersPropagator;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GuzzleClientAspect extends AbstractAspect
{
    public array $classes = [
        Client::class . '::transfer',
    ];

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $tracing = $this->switcher->isTracingEnabled('guzzle');
        $metrics = $this->switcher->isMetricsEnabled('guzzle');

        if (! $tracing && ! $metrics) {
            return $proceedingJoinPoint->process();
        }

        /** @var RequestInterface $request */
        $request = $proceedingJoinPoint->arguments['keys']['request'];
        $method  = $request->getMethod();
        $start   = microtime(true);
        $span    = null;

        if ($tracing) {
            $context = Context::getCurrent();

            $span = $this->instrumentation->tracer()
                ->spanBuilder($method . ' ' . $request->getUri()->getPath())
                ->setParent($context)
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->startSpan();

            Globals::propagator()->inject($request, HeadersPropagator::instance(), $span->storeInContext($context));

            $span->setAttributes([
                TraceAttributes::HTTP_REQUEST_METHOD => $method,
                TraceAttributes::URL_FULL            => (string) $request->getUri(),
                TraceAttributes::URL_PATH            => $request->getUri()->getPath(),
                TraceAttributes::URL_SCHEME          => $request->getUri()->getScheme(),
                TraceAttributes::SERVER_ADDRESS      => $request->getUri()->getHost(),
                TraceAttributes::SERVER_PORT         => $request->getUri()->getPort(),
                TraceAttributes::USER_AGENT_ORIGINAL => $request->getHeaderLine('User-Agent'),
                TraceAttributes::URL_QUERY           => $request->getUri()->getQuery(),
                ...$this->transformHeaders('request', $request->getHeaders()),
            ]);
        }

        $proceedingJoinPoint->arguments['keys']['request'] = $request;
        $promise = $proceedingJoinPoint->process();

        if ($promise instanceof PromiseInterface) {
            $promise->then(
                function (ResponseInterface $response) use ($span, $method, $start, $metrics) {
                    if ($span) {
                        $span->setAttributes([
                            TraceAttributes::HTTP_RESPONSE_STATUS_CODE => $response->getStatusCode(),
                            TraceAttributes::NETWORK_PROTOCOL_VERSION  => $response->getProtocolVersion(),
                            TraceAttributes::HTTP_RESPONSE_BODY_SIZE   => $response->getHeaderLine('Content-Length'),
                            ...$this->transformHeaders('response', $response->getHeaders()),
                        ]);

                        if ($response->getStatusCode() >= 400) {
                            $span->setStatus(StatusCode::STATUS_ERROR);
                        }

                        $span->end();
                    }

                    if ($metrics) {
                        $duration = (microtime(true) - $start) * 1000;
                        $this->instrumentation->meter()
                            ->createHistogram('http.client.duration', 'ms')
                            ->record($duration, [
                                'http.method' => $method,
                                'http.status_code' => $response->getStatusCode(),
                            ]);
                    }

                    return $response;
                },
                function (Throwable $t) use ($span, $method, $start, $metrics) {
                    if ($span) {
                        $this->spanRecordException($span, $t);
                        $span->end();
                    }

                    if ($metrics) {
                        $duration = (microtime(true) - $start) * 1000;
                        $this->instrumentation->meter()
                            ->createHistogram('http.client.duration', 'ms')
                            ->record($duration, [
                                'http.method' => $method,
                                'http.status_code' => 0,
                            ]);
                    }

                    throw $t;
                }
            );
        }

        return $promise;
    }

    /**
     * Transform headers to OpenTelemetry attributes.
     *
     * @param array<array<string>> $headers
     * @return array<string, string>
     */
    private function transformHeaders(string $type, array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = Str::lower($key);
            if ($this->canTransformHeaders($type, $key)) {
                $result["http.{$type}.header.{$key}"] = implode(', ', $value);
            }
        }

        return $result;
    }

    private function canTransformHeaders(string $type, string $key): bool
    {
        $headers = (array) $this->config->get("open-telemetry.instrumentation.features.guzzle.options.headers.{$type}", ['*']);

        foreach ($headers as $header) {
            if (Str::is(Str::lower($header), $key)) {
                return true;
            }
        }

        return false;
    }
}
