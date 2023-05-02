<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;

class MetricExporterFactory
{
    public function __invoke(ContainerInterface $container): MetricExporterInterface
    {
        $config = $container->get(ConfigInterface::class);

        return match ($config->get('opentelemetry.metrics.exporter')) {
            'otlp' => new MetricExporter($container->get(TransportInterface::class), Temporality::CUMULATIVE),
            default => new MetricExporter(
                (new StreamTransportFactory())->create(STDOUT, 'application/x-ndjson')
            ),
        };
    }
}
