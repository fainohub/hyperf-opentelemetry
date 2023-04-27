<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\OpenTelemetry\Resource\ResourceFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeFactory;
use OpenTelemetry\SDK\Common\Log\LoggerHolder;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Metrics\Exemplar\ExemplarFilter\WithSampledTraceExemplarFilter;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use OpenTelemetry\SDK\Metrics\StalenessHandler\ImmediateStalenessHandlerFactory;
use OpenTelemetry\SDK\Metrics\View\CriteriaViewRegistry;
use Psr\Log\LoggerInterface;

class MeterProviderFactory
{
    public function __construct(
        private MetricReaderInterface $reader
    ) {
    }

    public function __invoke(ContainerInterface $container): MeterProviderInterface
    {
        $this->setLogger($container);

        $clock = ClockFactory::getDefault();

        $resource = ResourceFactory::create($container->make(ConfigInterface::class));

        return new MeterProvider(
            null,
            $resource,
            $clock,
            Attributes::factory(),
            new InstrumentationScopeFactory(Attributes::factory()),
            [$this->reader],
            new CriteriaViewRegistry(),
            new WithSampledTraceExemplarFilter(),
            new ImmediateStalenessHandlerFactory()
        );
    }

    private function setLogger(ContainerInterface $container): void
    {
        try {
            LoggerHolder::set($container->make(LoggerInterface::class));
        } catch (\Throwable $exception) {
            var_dump('set logger error: ' . $exception->getMessage());
        }
    }
}
