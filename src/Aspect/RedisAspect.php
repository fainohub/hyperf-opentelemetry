<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Aspect;

use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Redis\Redis;
use Hyperf\Stringable\Str;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

class RedisAspect extends AbstractAspect
{
    public array $classes = [
        Redis::class . '::__call',
    ];

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $tracing = $this->switcher->isTracingEnabled('redis');
        $metrics = $this->switcher->isMetricsEnabled('redis');

        if (! $tracing && ! $metrics) {
            return $proceedingJoinPoint->process();
        }

        $args = $proceedingJoinPoint->arguments['keys'];
        $command = Str::lower($args['name']);
        $commandFull = $command . ' ' . $this->buildCommandArguments($args['arguments']);
        $poolName = (fn () => $this->poolName ?? 'default')->call($proceedingJoinPoint->getInstance());

        $start = microtime(true);
        $span = null;

        if ($tracing) {
            $span = $this->instrumentation->tracer()
                ->spanBuilder('[REDIS] ' . Str::upper($command))
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->startSpan();

            $span->setAttributes([
                TraceAttributes::DB_SYSTEM_NAME    => 'redis',
                TraceAttributes::DB_OPERATION_NAME => Str::upper($command),
                TraceAttributes::DB_QUERY_TEXT     => Str::limit($commandFull, 512),
                'redis.pool'                       => $poolName,
            ]);
        }

        try {
            $result = $proceedingJoinPoint->process();

            if ($span) {
                $span->setStatus(StatusCode::STATUS_OK);
            }
        } catch (Throwable $e) {
            if ($span) {
                $this->spanRecordException($span, $e);
            }

            throw $e;
        } finally {
            if ($span) {
                $span->end();
            }

            if ($metrics) {
                $duration = (microtime(true) - $start) * 1000;

                $this->instrumentation->meter()
                    ->createHistogram('redis.command.duration', 'ms')
                    ->record($duration, [
                        'command' => $command,
                        'pool'    => $poolName,
                    ]);

                $this->instrumentation->meter()
                    ->createCounter('redis.command.total')
                    ->add(1, [
                        'command' => $command,
                        'pool'    => $poolName,
                    ]);
            }
        }

        return $result;
    }

    private function buildCommandArguments(array $args): string
    {
        $callback = static function (array $args) use (&$callback) {
            $result = '';
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    $result .= $callback($arg);
                } elseif (! is_object($arg)) {
                    $result .= $arg . ' ';
                }
            }

            return $result;
        };

        return $callback($args);
    }
}
