<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\API\Common\Signal\Signals;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpUtil;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
class MetricReaderFactory
{
    public function __invoke(ContainerInterface $container): MetricReaderInterface
    {
        $config = $container->get(ConfigInterface::class);
        $exporter = $this->getExporter($config->get('opentelemetry.metrics'));

        return new ExportingReader($exporter, ClockFactory::getDefault());
    }

    private function getExporter(array $config): MetricExporterInterface
    {
        return match ($config['exporter']) {
            'otlp' => new MetricExporter($this->getOtlpTransport($config['protocol'], $config['endpoint'])),
            default => new MetricExporter(
                (new StreamTransportFactory())->create(STDOUT, 'application/x-ndjson')
            ),
        };
    }

    private function getOtlpTransport(string $protocol, string $endpoint): TransportInterface
    {
        return match ($protocol) {
            'grcp' => (new GrpcTransportFactory())->create($endpoint . OtlpUtil::method(Signals::METRICS)),
            default => PsrTransportFactory::discover()->create($endpoint, ContentTypes::JSON),
        };
    }
}
