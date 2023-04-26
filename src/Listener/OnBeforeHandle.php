<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Listener;

use Hyperf\Command\Event\AfterExecute;
use Hyperf\Command\Event\BeforeHandle;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use Psr\Container\ContainerInterface;
use Swoole\Timer;

/**
 * Collect and handle metrics before command start.
 */
class OnBeforeHandle implements ListenerInterface
{
    protected MetricReaderInterface $reader;

    public function __construct(protected ContainerInterface $container)
    {
        $this->reader = $container->get(MetricReaderInterface::class);
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

        while (true) {
            $this->reader->collect();

            $workerExited = CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5);

            if ($workerExited) {
                break;
            }
        }
    }
}
