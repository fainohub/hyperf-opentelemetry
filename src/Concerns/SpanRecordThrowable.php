<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Concerns;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

trait SpanRecordThrowable
{
    /**
     * @param SpanInterface $span
     * @param ?Throwable $e
     * @return void
     */
    protected function spanRecordException(SpanInterface $span, ?Throwable $e = null): void
    {
        if ($e === null) {
            return;
        }

        $trace = $e->getTrace()[0] ?? [];

        $span->setAttributes([
            TraceAttributes::EXCEPTION_TYPE       => get_class($e),
            TraceAttributes::EXCEPTION_MESSAGE    => $e->getMessage(),
            TraceAttributes::EXCEPTION_STACKTRACE => $e->getTraceAsString(),
            TraceAttributes::CODE_LINE_NUMBER     => $e->getLine(),
            TraceAttributes::CODE_FUNCTION_NAME   => isset($trace['class'], $trace['function'])
                ? "{$trace['class']}::{$trace['function']}"
                : ($trace['function'] ?? 'unknown'),
        ]);

        $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
        $span->recordException($e);
    }
}
