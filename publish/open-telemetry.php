<?php

declare(strict_types=1);

use function Hyperf\Support\env;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SemConv\ResourceAttributes;

return [
    'driver' => env('OTEL_EXPORTER_DRIVER', 'otlp'),

    'resource' => [
        ResourceAttributes::SERVICE_NAMESPACE           => env('APP_NAMESPACE', 'hyperf-opentelemetry'),
        ResourceAttributes::SERVICE_NAME                => env('APP_NAME', 'hyperf-app'),
        ResourceAttributes::SERVICE_INSTANCE_ID         => gethostname() ?: uniqid(),
        ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => env('APP_ENV', 'local'),
    ],

    'exporter' => [
        'otlp' => [
            'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        ],
    ],

    'sampler' => new AlwaysOnSampler(),

    'instrumentation' => [
        'enabled' => env('OTEL_INSTRUMENTATION_ENABLED', true),

        'tracing' => env('OTEL_INSTRUMENTATION_TRACING_ENABLED', true),
        'metrics' => env('OTEL_INSTRUMENTATION_METRICS_ENABLED', true),
        'logger'  => env('OTEL_INSTRUMENTATION_LOGGER_ENABLED', true),

        'features' => [
            'client_request' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_CLIENT_REQUEST_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_CLIENT_REQUEST_METRICS', true),
                'options' => [
                    'headers' => [
                        'request'  => ['*'],
                        'response' => ['*'],
                    ],
                ],
            ],
            'db_query' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_DB_QUERY_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_DB_QUERY_METRICS', true),
                'options' => [
                    'combine_sql_and_bindings' => false,
                ],
            ],
            'command' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_COMMAND_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_COMMAND_METRICS', true),
                'options' => [],
            ],
            'crontab' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_CRONTAB_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_CRONTAB_METRICS', true),
                'options' => [],
            ],
            'guzzle' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_GUZZLE_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_GUZZLE_METRICS', true),
                'options' => [
                    'headers' => [
                        'request'  => ['*'],
                        'response' => ['*'],
                    ],
                ],
            ],
            'redis' => [
                'traces' => env('OTEL_INSTRUMENTATION_FEATURES_REDIS_TRACES', true),
                'metrics' => env('OTEL_INSTRUMENTATION_FEATURES_REDIS_METRICS', true),
                'options' => [],
            ],
        ],
    ],
];
