<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Metric;

use Hyperf\Retry\Retry;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;

class MetricCollector
{
    public function __construct(
        protected MetricReaderInterface $reader
    ) {
    }

    public function handle(): void
    {
        Coroutine::create(function () {
            if (class_exists(Retry::class)) {
                Retry::whenThrows()->backoff(100)->call(function () {
                    $this->collect();
                });
            } else {
                retry(PHP_INT_MAX, function () {
                    $this->collect();
                }, 100);
            }
        });
    }

    private function collect(): void
    {
        while (true) {
            $this->reader->collect();

            $workerExited = CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5);

            if ($workerExited) {
                break;
            }
        }
    }
}
