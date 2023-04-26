<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\API\Common\Signal\Signals;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpUtil;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Metrics\Exemplar\ExemplarFilter\WithSampledTraceExemplarFilter;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\StalenessHandler\ImmediateStalenessHandlerFactory;
use OpenTelemetry\SDK\Metrics\View\CriteriaViewRegistry;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;

class MeterProviderFactory
{
    public function __invoke(ContainerInterface $container): MeterProviderInterface
    {
        $clock = ClockFactory::getDefault();

        $config = $container->get(ContainerInterface::class);
        $exporter = $this->getExporter($config->get('opentelemetry.metrics'));

        return new MeterProvider(
            null,
            ResourceInfoFactory::defaultResource(),
            $clock,
            Attributes::factory(),
            new InstrumentationScopeFactory(Attributes::factory()),
            [new ExportingReader($exporter, $clock)],
            new CriteriaViewRegistry(),
            new WithSampledTraceExemplarFilter(),
            new ImmediateStalenessHandlerFactory()
        );
    }

    private function getExporter(array $config): MetricExporterInterface
    {
        switch ($config['exporter']) {
            case 'otlp':
                return new MetricExporter($this->getOtlpTransport($config['protocol'], $config['endpoint']));
            case 'stdout':
            default:
                return new MetricExporter(
                    (new StreamTransportFactory())->create(STDOUT, 'application/x-ndjson')
                );
        }
    }

    private function getOtlpTransport(string $protocol, string $endpoint): TransportInterface
    {
        switch ($protocol) {
            case 'grcp':
                return (new GrpcTransportFactory())->create($endpoint . OtlpUtil::method(Signals::METRICS));
            case 'http':
            default:
                return PsrTransportFactory::discover()->create($endpoint, ContentTypes::JSON);
        }
    }
}
