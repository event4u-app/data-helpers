<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Helpers;

use event4u\DataHelpers\Support\Cache\PathParsingCache;

/**
 * Helper for dot-notation paths with wildcard support.
 *
 * Example:
 *   DotPathHelper::segments("users.*.name") -> ["users", "*", "name"]
 *   DotPathHelper::buildPrefix("users", "0") -> "users.0"
 */
class DotPathHelper
{
    /**
     * Split a dot-notation string into segments (cached).
     *
     * Phase 3 Enhancement: Uses PathParsingCache for better cache management.
     *
     * Empty segments are not allowed. The following are invalid and will throw InvalidArgumentException:
     * - Leading or trailing dot: ".a", "a."
     * - Double dots producing empty segment: "a..b"
     *
     * An empty path "" is allowed and returns an empty segment list.
     *
     * @return array<int, string>
     */
    public static function segments(string $path): array
    {
        return PathParsingCache::getSegments($path);
    }

    /** Join prefix and next segment into a new dot-path. */
    public static function buildPrefix(string $prefix, int|string $segment): string
    {
        return '' === $prefix ? (string)$segment : $prefix . '.' . $segment;
    }

    /** Check if a segment is a wildcard. */
    public static function isWildcard(?string $segment): bool
    {
        return '*' === $segment;
    }

    /**
     * Detect if a path contains at least one wildcard (cached).
     *
     * Phase 3 Enhancement: Uses PathParsingCache for better cache management.
     */
    public static function containsWildcard(string $path): bool
    {
        return PathParsingCache::hasWildcard($path);
    }
}
