<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Export;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\API\Common\Signal\Signals;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\OtlpUtil;
use OpenTelemetry\SDK\Common\Export\TransportInterface;

class TransportFactory
{
    public function __invoke(ContainerInterface $container): TransportInterface
    {
        $config = $container->get(ConfigInterface::class);
        $endpoint = $config->get('opentelemetry.metrics.endpoint');

        return match ($config->get('opentelemetry.metrics.protocol', 'http')) {
            'grcp' => (new GrpcTransportFactory())->create($endpoint . OtlpUtil::method(Signals::METRICS)),
            default => $container->get(GuzzleTransportFactory::class)->create($endpoint, ContentTypes::JSON),
        };
    }
}
