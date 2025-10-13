<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Loggers;

use event4u\DataHelpers\Logging\DataHelpersLogger;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use Throwable;

/**
 * Prometheus metrics logger decorator.
 *
 * Writes metrics in Prometheus format for Grafana dashboards.
 */
final readonly class PrometheusLogger implements DataHelpersLogger
{
    /**
     * @param DataHelpersLogger $baseLogger Base logger to decorate
     * @param string $metricsFile Path to metrics file
     */
    public function __construct(
        private DataHelpersLogger $baseLogger,
        private string $metricsFile = './storage/metrics/data-helpers.prom',
    ) {
        $this->ensureMetricsDirectoryExists();
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        $this->baseLogger->log($level, $message, $context);
    }

    public function exception(Throwable $exception, array $context = []): void
    {
        $this->baseLogger->exception($exception, $context);

        // Increment error counter
        $this->incrementCounter('data_helpers_errors_total', [
            'type' => $exception::class,
        ]);
    }

    public function metric(string $name, float $value, array $tags = []): void
    {
        $this->baseLogger->metric($name, $value, $tags);

        // Write metric to Prometheus file
        $this->writeMetric($name, $value, $tags);
    }

    public function event(LogEvent $event, array $context = []): void
    {
        $this->baseLogger->event($event, $context);

        // Increment event counter
        $this->incrementCounter('data_helpers_events_total', [
            'event' => $event->value,
        ]);
    }

    public function performance(string $operation, float $durationMs, array $context = []): void
    {
        $this->baseLogger->performance($operation, $durationMs, $context);

        // Write performance metric
        $this->writeMetric(
            'data_helpers_operation_duration_seconds',
            $durationMs / 1000,
            ['operation' => $operation],
        );

        // Write record count if available
        if (isset($context['record_count'])) {
            $this->writeMetric(
                'data_helpers_records_processed_total',
                (float)$context['record_count'],
                ['operation' => $operation],
            );
        }
    }

    public function isEventEnabled(LogEvent $event): bool
    {
        return $this->baseLogger->isEventEnabled($event);
    }

    public function isLevelEnabled(LogLevel $level): bool
    {
        return $this->baseLogger->isLevelEnabled($level);
    }

    /**
     * Write a metric to the Prometheus file.
     *
     * @param string $name Metric name
     * @param float $value Metric value
     * @param array<string, string> $labels Metric labels
     */
    private function writeMetric(string $name, float $value, array $labels = []): void
    {
        $labelStr = $this->formatLabels($labels);
        $metric = sprintf('%s%s %s%s', $name, $labelStr, $value, PHP_EOL);

        file_put_contents($this->metricsFile, $metric, FILE_APPEND | LOCK_EX);
    }

    /**
     * Increment a counter metric.
     *
     * @param string $name Counter name
     * @param array<string, string> $labels Counter labels
     */
    private function incrementCounter(string $name, array $labels = []): void
    {
        // Read current value
        $current = $this->readCounter($name, $labels);

        // Increment and write
        $this->writeMetric($name, $current + 1, $labels);
    }

    /**
     * Read current counter value.
     *
     * @param string $name Counter name
     * @param array<string, string> $labels Counter labels
     */
    private function readCounter(string $name, array $labels = []): float
    {
        if (!file_exists($this->metricsFile)) {
            return 0.0;
        }

        $labelStr = $this->formatLabels($labels);
        $pattern = '/^' . $name . preg_quote($labelStr, '/') . ' ([0-9.]+)$/m';

        $content = file_get_contents($this->metricsFile);
        if (false === $content) {
            return 0.0;
        }

        if (preg_match($pattern, $content, $matches)) {
            return (float)$matches[1];
        }

        return 0.0;
    }

    /**
     * Format labels for Prometheus.
     *
     * @param array<string, string> $labels Labels
     */
    private function formatLabels(array $labels): string
    {
        if ([] === $labels) {
            return '';
        }

        $parts = [];
        foreach ($labels as $key => $value) {
            $parts[] = sprintf('%s="%s"', $key, $value);
        }

        return '{' . implode(',', $parts) . '}';
    }

    /** Ensure metrics directory exists. */
    private function ensureMetricsDirectoryExists(): void
    {
        $dir = dirname($this->metricsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

