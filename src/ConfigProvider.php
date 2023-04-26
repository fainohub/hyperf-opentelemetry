<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry;

use Hyperf\OpenTelemetry\Metric\MeterProviderFactory;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;

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
                MeterProviderInterface::class => MeterProviderFactory::class
            ],
            'commands' => [
                //
            ],
            'listeners' => [

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
        ];
    }
}
