<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
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

        while (true) {
            $this->reader->collect();

            $workerExited = CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5);

            if ($workerExited) {
                break;
            }
        }
    }
}