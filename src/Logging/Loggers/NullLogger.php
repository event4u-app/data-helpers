<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Loggers;

use event4u\DataHelpers\Logging\DataHelpersLogger;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use Throwable;

/**
 * Null logger implementation.
 *
 * Does nothing - used when logging is disabled.
 */
final class NullLogger implements DataHelpersLogger
{
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        // Do nothing
    }

    public function exception(Throwable $exception, array $context = []): void
    {
        // Do nothing
    }

    public function metric(string $name, float $value, array $tags = []): void
    {
        // Do nothing
    }

    public function event(LogEvent $event, array $context = []): void
    {
        // Do nothing
    }

    public function performance(string $operation, float $durationMs, array $context = []): void
    {
        // Do nothing
    }

    public function isEventEnabled(LogEvent $event): bool
    {
        return false;
    }

    public function isLevelEnabled(LogLevel $level): bool
    {
        return false;
    }
}
