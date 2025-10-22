<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging;

/**
 * PSR-3 compatible log levels.
 */
enum LogLevel: string
{
    case EMERGENCY = 'emergency';
    case ALERT = 'alert';
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case NOTICE = 'notice';
    case INFO = 'info';
    case DEBUG = 'debug';

    /** Get numeric severity (higher = more severe). */
    public function severity(): int
    {
        return match ($this) {
            self::EMERGENCY => 800,
            self::ALERT => 700,
            self::CRITICAL => 600,
            self::ERROR => 500,
            self::WARNING => 400,
            self::NOTICE => 300,
            self::INFO => 200,
            self::DEBUG => 100,
        };
    }

    /** Check if this level is at least as severe as the given level. */
    public function isAtLeast(self $level): bool
    {
        return $this->severity() >= $level->severity();
    }
}
