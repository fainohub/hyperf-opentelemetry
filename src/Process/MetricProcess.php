<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Process;

use Hyperf\Contract\ConfigInterface;
use Hyperf\OpenTelemetry\Metric\MetricCollector;
use Hyperf\Process\AbstractProcess;

class MetricProcess extends AbstractProcess
{
    public $name = 'opentelemetry-metric';

    public $nums = 1;

    public function isEnable($server): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get('opentelemetry.metrics.enabled');
    }

    public function handle(): void
    {
        $collector = $this->container->get(MetricCollector::class);
        $collector->handle();
    }
}