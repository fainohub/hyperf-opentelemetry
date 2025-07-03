<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Listener;

use Hyperf\Crontab\Event\AfterExecute;
use Hyperf\Event\Contract\ListenerInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
class CrontabListener extends InstrumentationListener implements ListenerInterface
{
    private float $startTime = 0;

    public function listen(): array
    {
        return [
            AfterExecute::class,
        ];
    }

    public function process(object $event): void
    {
        match ($event::class) {
            AfterExecute::class => $this->onAfterExecute($event),
            default             => null,
        };
    }

    protected function onAfterExecute(AfterExecute $event): void
    {
        $this->startTime = microtime(true);

        $span = $this->instrumentation->tracer()
            ->spanBuilder('[CRON] ' . $event->crontab->getName())
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        $span->setAttributes([
            'crontab.name' => $event->crontab->getName(),
            'crontab.rule' => $event->crontab->getRule(),
            'crontab.type' => $event->crontab->getType(),
        ]);

        // TODO: OpenTelemetry não fornece diretamente o erro em AfterExecute — se necessário, tratar via fallback

        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();

        if ($this->switcher->isMetricsEnabled('crontab')) {
            $duration = (microtime(true) - $this->startTime) * 1000;

            $this->instrumentation->meter()
                ->createHistogram('cron.task.duration', 'ms')
                ->record($duration, [
                    'crontab' => $event->crontab->getName(),
                    'type' => $event->crontab->getType(),
                ]);
        }

        $this->exporter->meterCollect();
    }
}
