<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Enums;

/**
 * Cache invalidation strategies for detecting when cache should be regenerated.
 *
 * - manual: No automatic validation - cache only invalidated by clear-cache command (like Spatie Laravel Data)
 * - mtime: Check file modification time on every cache hit (fast)
 * - hash: Check file content hash on every cache hit (slower, more accurate)
 * - both: Check both mtime and hash on every cache hit (most accurate)
 */
enum CacheInvalidation: string
{
    case MANUAL = 'manual';
    case MTIME = 'mtime';
    case HASH = 'hash';
    case BOTH = 'both';

    /**
     * Check if this strategy requires automatic validation on cache hits.
     */
    public function requiresValidation(): bool
    {
        return $this !== self::MANUAL;
    }
}

