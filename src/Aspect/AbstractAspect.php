<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Aspect;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Aop\AbstractAspect as BaseAbstractAspect;
use FainoHub\Hyperf\OpenTelemetry\Concerns\SpanRecordThrowable;
use FainoHub\Hyperf\OpenTelemetry\Switcher;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class AbstractAspect extends BaseAbstractAspect
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
    ) {
        $this->config = $this->container->get(ConfigInterface::class);
    }
}
