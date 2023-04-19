<?php

declare(strict_types=1);

namespace FainoHub\HyperfOpentelemetry;

/**
 * Class ConfigProvider
 * @package FainoHub\HyperfOpentelemetry
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
                //
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
