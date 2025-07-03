<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Contract;

interface ExporterInterface
{
    public function configure(): void;

    public function meterCollect(): void;
}
