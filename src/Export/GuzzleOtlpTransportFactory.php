<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Export;

use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransport;

class GuzzleOtlpTransportFactory
{
    private const DEFAULT_COMPRESSION = 'none';

    public function create(
        string $endpoint,
        string $contentType,
        array $headers = [],
        $compression = null,
        float $timeout = 10,
        int $retryDelay = 100,
        int $maxRetries = 3,
        ?string $cacert = null,
        ?string $cert = null,
        ?string $key = null
    ): PsrTransport {
        if ($compression === self::DEFAULT_COMPRESSION) {
            $compression = null;
        }

        $client = new Client([
            'client' => [
                'base_uri' => $endpoint,
                'timeout' => $timeout,
                'connect_timeout' => 1,
            ],
            'pool' => [
                'max_connections' => 50,
            ],
        ]);

        return new PsrTransport(
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            $endpoint,
            $contentType,
            $headers,
            (array) $compression,
            $retryDelay,
            $maxRetries,
        );
    }
}
