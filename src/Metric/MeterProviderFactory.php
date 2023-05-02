<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\OpenTelemetry\Resource\ResourceFactory;
use OpenTelemetry\SDK\Common\Log\LoggerHolder;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use Psr\Log\LoggerInterface;

class MeterProviderFactory
{
    public function __invoke(ContainerInterface $container): MeterProviderInterface
    {
        $config = $container->make(ConfigInterface::class);
        $reader = $container->make(MetricReaderInterface::class);
        $resource = ResourceFactory::create($config);

        $this->setLogger($container, $config);

        return MeterProvider::builder()
            ->registerMetricReader($reader)
            ->setResource($resource)
            ->build();
    }

    private function setLogger(ContainerInterface $container, ConfigInterface $config): void
    {
        try {
            $loggerClass = $config->get('opentelemetry.logger', LoggerInterface::class);
            LoggerHolder::set($container->make($loggerClass));
        } catch (\Throwable $exception) {
            //
        }
    }
}
