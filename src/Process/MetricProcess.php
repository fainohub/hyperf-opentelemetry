<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Retry\Retry;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;

class MetricProcess extends AbstractProcess
{
    public $name = 'opentelemetry-metric';

    public $nums = 1;

    protected MetricReaderInterface $reader;

    public function isEnable($server): bool
    {
        return true;
    }

    public function handle(): void
    {
        $this->reader = make(MetricReaderInterface::class);

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

    private function collect()
    {
        while (true) {
            $this->reader->collect();
            $this->reader->forceFlush();

            $workerExited = CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5);

            if ($workerExited) {
                break;
            }
        }
    }
}