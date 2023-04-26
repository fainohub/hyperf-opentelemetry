<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Listener;

use Hyperf\Command\Event\AfterExecute;
use Hyperf\Command\Event\BeforeHandle;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\OpenTelemetry\Metric\MetricCollector;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;

/**
 * Collect and handle metrics before command start.
 */
class OnBeforeHandle implements ListenerInterface
{
    public function __construct(
        protected MetricCollector $collector
    ) {
    }

    public function listen(): array
    {
        return [
            BeforeHandle::class,
            AfterExecute::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof AfterExecute) {
            CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
            return;
        }

        $this->collector->handle();
    }
}
