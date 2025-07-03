<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Listener;

use FainoHub\Hyperf\OpenTelemetry\Contract\ExporterInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use FainoHub\Hyperf\OpenTelemetry\Concerns\SpanRecordThrowable;
use FainoHub\Hyperf\OpenTelemetry\Switcher;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class InstrumentationListener
{
    use SpanRecordThrowable;

    protected readonly ConfigInterface $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly CachedInstrumentation $instrumentation,
        protected readonly Switcher $switcher,
        protected readonly ExporterInterface $exporter
    ) {
        $this->config = $this->container->get(ConfigInterface::class);
    }
}
