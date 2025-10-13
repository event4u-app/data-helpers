<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging;

use Throwable;

/**
 * Logger interface for data-helpers.
 *
 * Provides logging capabilities for mapping operations, queries, performance metrics,
 * and data quality issues.
 */
interface DataHelpersLogger
{
    /**
     * Log a message with a specific level.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     */
    public function log(LogLevel $level, string $message, array $context = []): void;

    /**
     * Log an exception.
     *
     * @param Throwable $exception The exception to log
     * @param array<string, mixed> $context Additional context
     */
    public function exception(Throwable $exception, array $context = []): void;

    /**
     * Log a metric value.
     *
     * @param string $name Metric name
     * @param float $value Metric value
     * @param array<string, string> $tags Metric tags
     */
    public function metric(string $name, float $value, array $tags = []): void;

    /**
     * Log an event.
     *
     * @param LogEvent $event The event to log
     * @param array<string, mixed> $context Event context
     */
    public function event(LogEvent $event, array $context = []): void;

    /**
     * Log a performance measurement.
     *
     * @param string $operation Operation name
     * @param float $durationMs Duration in milliseconds
     * @param array<string, mixed> $context Additional context
     */
    public function performance(string $operation, float $durationMs, array $context = []): void;

    /**
     * Check if logging is enabled for a specific event.
     *
     * @param LogEvent $event The event to check
     */
    public function isEventEnabled(LogEvent $event): bool;

    /**
     * Check if logging is enabled for a specific level.
     *
     * @param LogLevel $level The level to check
     */
    public function isLevelEnabled(LogLevel $level): bool;
}

