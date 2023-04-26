<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry;

use Hyperf\OpenTelemetry\Listener\OnBeforeHandle;
use Hyperf\OpenTelemetry\Metric\MeterProviderFactory;
use Hyperf\OpenTelemetry\Metric\MetricReaderFactory;
use Hyperf\OpenTelemetry\Process\MetricProcess;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;

/**
 * Class ConfigProvider
 * @package Hyperf\OpenTelemetry
 */
class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                MetricReaderInterface::class => MetricReaderFactory::class,
                MeterProviderInterface::class => MeterProviderFactory::class
            ],
            'commands' => [
                //
            ],
            'listeners' => [
                OnBeforeHandle::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for opentelemetry.',
                    'source' => __DIR__ . '/../publish/opentelemetry.php',
                    'destination' => BASE_PATH . '/config/autoload/opentelemetry.php',
                ],
            ],
            'processes' => [
                MetricProcess::class,
            ],
        ];
    }
}
