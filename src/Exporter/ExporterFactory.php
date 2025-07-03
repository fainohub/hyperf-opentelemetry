<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Exporter;

use FainoHub\Hyperf\OpenTelemetry\Contract\ExporterInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ExporterFactory
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ExporterInterface
    {
        $config = $container->get(ConfigInterface::class);
        $driver = $config->get('open-telemetry.driver', 'otlp');

        return match ($driver) {
            'stdout' => $container->get(StdoutExporter::class),
            'null' => $container->get(NullExporter::class),
            default  => $container->get(OtlpExporter::class),
        };
    }
}
