<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;

class MetricReaderFactory
{
    public function __invoke(ContainerInterface $container): MetricReaderInterface
    {
        $exporter = $container->get(MetricExporterInterface::class);

        return new ExportingReader($exporter, ClockFactory::getDefault());
    }
}
