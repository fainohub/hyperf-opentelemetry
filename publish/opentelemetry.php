<?php

declare(strict_types=1);

return [
    'metrics' => [
        'exporter' => env('OTEL_METRICS_EXPORTER', 'stdout'), /* otlp, stdout */
        'protocol' => env('OTEL_EXPORTER_OTLP_METRICS_PROTOCOL', 'http'), /* http, grpc */
        'endpoint' => env('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT', ''),
    ]
];
