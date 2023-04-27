<?php

declare(strict_types=1);

return [
    'resource' => [
        'service' => [
            'name' => env('OTEL_SERVICE_NAME', 'hyper-opentelemetry'),
            'namespace' => env('OTEL_SERVICE_NAMESPACE', 'hyper-opentelemetry'),
        ],
        'environment' => env('APP_ENV', 'development'),
    ],

    'metrics' => [
        'service_name' => env('OTEL_SERVICE_NAME', ''),
        'enabled' => env('OTEL_METRICS_ENABLED', true),
        'exporter' => env('OTEL_METRICS_EXPORTER', 'stdout'), /* otlp, stdout */
        'protocol' => env('OTEL_EXPORTER_OTLP_METRICS_PROTOCOL', 'http'), /* http, grpc */
        'endpoint' => env('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT', ''),
    ]
];
