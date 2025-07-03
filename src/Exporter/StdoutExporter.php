<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Exporter;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use FainoHub\Hyperf\OpenTelemetry\Contract\ExporterInterface;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Logs\Exporter\ConsoleExporterFactory;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricExporter\ConsoleMetricExporterFactory;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessorFactory;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StdoutExporter implements ExporterInterface
{
    protected ConfigInterface $config;

    protected ExportingReader $meterReader;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly ResourceInfo $resource,
    ) {
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function configure(): void
    {
        $spanProcessor = (new SpanProcessorFactory())->create(
            (new ConsoleSpanExporterFactory())->create()
        );

        $logExporter = (new ConsoleExporterFactory())->create();

        $this->meterReader = new ExportingReader(
            (new ConsoleMetricExporterFactory())->create(),
        );

        $meterProvider = MeterProvider::builder()
            ->setResource($this->resource)
            ->addReader($this->meterReader)
            ->build();

        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor($spanProcessor)
            ->setResource($this->resource)
            ->build();

        $loggerProvider = LoggerProvider::builder()
            ->setResource($this->resource)
            ->addLogRecordProcessor(
                new BatchLogRecordProcessor($logExporter, Clock::getDefault())
            )
            ->build();

        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setMeterProvider($meterProvider)
            ->setLoggerProvider($loggerProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
    }

    public function meterCollect(): void
    {
        $this->meterReader->collect();
    }
}
