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
        $config = $container->make(ConfigInterface::class);

        $this->setLogger($container, $config);

        $clock = ClockFactory::getDefault();

        $resource = ResourceFactory::create($config);

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

    private function setLogger(ContainerInterface $container, ConfigInterface $config): void
    {
        try {
            $loggerClass = $config->get('opentelemetry.logger', LoggerInterface::class);
            LoggerHolder::set($container->make($loggerClass));
        } catch (\Throwable $exception) {
            var_dump('set logger error: ' . $exception->getMessage());
        }
    }
}
