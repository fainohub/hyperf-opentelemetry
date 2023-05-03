<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use Throwable;

class MetricCollector
{
    public function __construct(
        protected MetricReaderInterface $reader
    ) {
    }

    public function handle(): void
    {
        Coroutine::create(function () {
            while (true) {
                try {
                    $this->reader->collect();

                    $workerExited = CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5);
                    if ($workerExited) {
                        break;
                    }
                } catch (Throwable $e) {
                    if (ApplicationContext::hasContainer()
                        && ApplicationContext::getContainer()->has(StdoutLoggerInterface::class)) {
                        ApplicationContext::getContainer()
                            ->get(StdoutLoggerInterface::class)
                            ->error('Metric collector error:' . $e->getMessage());
                    }
                }
            }
        });
    }
}
