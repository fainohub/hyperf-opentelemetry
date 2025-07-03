<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Stringable\Str;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

class DbQueryExecutedListener extends InstrumentationListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    public function process(object $event): void
    {
        match ($event::class) {
            QueryExecuted::class => $this->onQueryExecuted($event),
            default              => null,
        };
    }

    protected function onQueryExecuted(QueryExecuted $event): void
    {
        $tracing = $this->switcher->isTracingEnabled('db_query');
        $metrics = $this->switcher->isMetricsEnabled('db_query');

        if (! $tracing && ! $metrics) {
            return;
        }

        // db query span end time
        $nowInNs = (int) (microtime(true) * 1E9);

        // combine sql and bindings
        $sql = $this->config->get('open-telemetry.instrumentation.features.db_query.options.combine_sql_and_bindings', false)
            ? $this->combineSqlAndBindings($event)
            : $event->sql;

        $operation = Str::upper(Str::before($event->sql, ' '));
        $driver = $event->connection->getDriverName();

        if ($tracing) {
            $span = $this->instrumentation->tracer()->spanBuilder('sql ' . $event->sql)
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->setStartTimestamp($this->calculateQueryStartTime($nowInNs, $event->time))
                ->startSpan();

            $span->setAttributes([
                TraceAttributes::DB_SYSTEM_NAME    => $driver,
                TraceAttributes::DB_NAMESPACE      => $event->connection->getDatabaseName(),
                TraceAttributes::DB_OPERATION_NAME => Str::upper(Str::before($event->sql, ' ')),
                TraceAttributes::DB_QUERY_TEXT     => $sql,
                TraceAttributes::SERVER_ADDRESS    => $event->connection->getConfig('host'),
                TraceAttributes::SERVER_PORT       => $event->connection->getConfig('port'),
            ]);

            if ($event->result instanceof Throwable) {
                $this->spanRecordException($span, $event->result);
            }

            $span->end();
        }

        if ($metrics) {
            $this->instrumentation->meter()
                ->createHistogram('db.query.duration', 'ms')
                ->record($event->time, [
                    'operation' => $operation,
                    'driver' => $driver,
                ]);
        }
    }

    protected function combineSqlAndBindings(QueryExecuted $event): string
    {
        $sql = $event->sql;
        if (!Arr::isAssoc($event->bindings)) {
            foreach ($event->bindings as $value) {
                $sql = Str::replaceFirst('?', "'{$value}'", $sql);
            }
        }

        return $sql;
    }

    private function calculateQueryStartTime(int $nowInNs, float $queryTimeMs): int
    {
        return (int) ($nowInNs - ($queryTimeMs * 1E6));
    }
}
