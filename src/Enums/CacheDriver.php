<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Enums;

/**
 * Cache driver enum.
 *
 * Defines available cache drivers for the Data Helpers package.
 */
enum CacheDriver: string
{
    /**
     * In-memory LRU cache (fast, no persistence).
     */
    case MEMORY = 'memory';

    /**
     * Framework cache system (Laravel or Symfony).
     * Automatically detects and uses the appropriate framework cache.
     */
    case FRAMEWORK = 'framework';

    /**
     * No caching (for testing/debugging).
     */
    case NONE = 'none';

    /** Get the default cache driver. */
    public static function default(): self
    {
        return self::FRAMEWORK;
    }

    /** Get the fallback cache driver. */
    public static function fallback(): self
    {
        return self::MEMORY;
    }
}

