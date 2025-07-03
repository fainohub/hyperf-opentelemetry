<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry;

use Hyperf\Contract\ConfigInterface;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;

class Switcher
{
    public function __construct(
        protected readonly CachedInstrumentation $instrumentation,
        protected readonly ConfigInterface $config,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->get('open-telemetry.instrumentation.enabled', true);
    }

    public function isTracingEnabled(?string $key = null): bool
    {
        if (null === $key) {
            return $this->instrumentation->tracer()->isEnabled()
                    && $this->isEnabled()
                    && $this->config->get('open-telemetry.instrumentation.tracing', true);
        }

        return $this->isTracingEnabled()
                && $this->config->get("open-telemetry.instrumentation.features.{$key}.traces", true);
    }

    public function isMetricsEnabled(?string $key = null): bool
    {
        if ($key === null) {
            return $this->isEnabled()
                && $this->config->get('open-telemetry.instrumentation.metrics', true);
        }

        return $this->isMetricsEnabled()
            && $this->config->get("open-telemetry.instrumentation.features.{$key}.metrics", true);
    }
}
