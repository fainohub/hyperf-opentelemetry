<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Listener;

use Carbon\Carbon;
use Hyperf\Command\Event\AfterExecute;
use Hyperf\Command\Event\BeforeHandle;
use Symfony\Component\Console\Input\InputInterface;
use Hyperf\Event\Contract\ListenerInterface;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;

/**
 * @property InputInterface $input
 */
class CommandListener extends InstrumentationListener implements ListenerInterface
{
    private float $startTime = 0;

    public function listen(): array
    {
        return [
            BeforeHandle::class,
            AfterExecute::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $this->switcher->isEnabled()) {
            return;
        }

        match ($event::class) {
            BeforeHandle::class => $this->onBeforeHandle($event),
            AfterExecute::class => $this->onAfterExecute($event),
            default             => null,
        };
    }

    protected function onBeforeHandle(BeforeHandle $event): void
    {
        $this->startTime = microtime(true);

        $args = (fn () => $this->input->getArguments())->call($event->getCommand());

        $span = $this->instrumentation->tracer()->spanBuilder('[CLI] ' . $event->getCommand()->getName())
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setAttributes([
                TraceAttributes::PROCESS_COMMAND         => $event->getCommand()->getName(),
                TraceAttributes::PROCESS_COMMAND_ARGS    => $args,
                TraceAttributes::PROCESS_CREATION_TIME   => Carbon::now()->toIso8601String(),
                TraceAttributes::PROCESS_EXECUTABLE_NAME => $event->getCommand()->getName(),
            ])
            ->startSpan();

        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    }

    protected function onAfterExecute(AfterExecute $event): void
    {
        if (! $scope = Context::storage()->scope()) {
            return;
        }

        $span = Span::fromContext($scope->context());

        if (! $span->isRecording()) {
            $scope->detach();
            return;
        }

        $duration = (microtime(true) - $this->startTime) * 1000;

        if ($this->switcher->isMetricsEnabled('command')) {
            $this->instrumentation->meter()
                ->createHistogram('cli.command.duration', 'ms')
                ->record($duration, ['command' => $event->getCommand()->getName()]);
        }

        $span->setAttributes([
            TraceAttributes::PROCESS_EXIT_TIME => Carbon::now()->toIso8601String(),
        ]);

        if (($e = $event->getThrowable()) !== null) {
            $this->spanRecordException($span, $e);
        } else {
            $span->setStatus(StatusCode::STATUS_OK);
        }

        $span->end();
        $scope->detach();

        $this->exporter->meterCollect();
    }
}
