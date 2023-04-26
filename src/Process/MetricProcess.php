<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Process;

use Hyperf\Contract\ConfigInterface;
use Hyperf\OpenTelemetry\Metric\MetricCollector;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;

class MetricProcess extends AbstractProcess
{
    public $name = 'opentelemetry-metric';

    public $nums = 1;

    public function __construct(
        protected MetricCollector $collector,
        protected ConfigInterface $config,
        ContainerInterface $container
    ) {
        parent::__construct($container);
    }

    public function isEnable($server): bool
    {
        return $this->config->get('opentelemetry.metrics.enabled');
    }

    public function handle(): void
    {
        $this->collector->handle();
    }
}