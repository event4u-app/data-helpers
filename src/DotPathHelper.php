<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use InvalidArgumentException;

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
     * Split a dot-notation string into segments.
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
        if ('' === $path) {
            return [];
        }

        // Fast checks for empty segments with specific messages
        if ('.' === $path[0]) {
            throw new InvalidArgumentException(sprintf("Invalid dot-path syntax: leading dot in '%s'", $path));
        }
        if (str_ends_with($path, '.')) {
            throw new InvalidArgumentException(sprintf("Invalid dot-path syntax: trailing dot in '%s'", $path));
        }
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException(sprintf("Invalid dot-path syntax: double dot in '%s'", $path));
        }

        $segments = explode('.', $path);

        // Defensive check (covers rare cases like "a." or ".a" even if above changes)
        foreach ($segments as $seg) {
            if ('' === $seg) {
                throw new InvalidArgumentException(sprintf("Invalid dot-path syntax: empty segment in '%s'", $path));
            }
        }

        return $segments;
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

    /** Detect if a path contains at least one wildcard. Validates syntax and throws on invalid paths. */
    public static function containsWildcard(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        // Reuse validation logic
        self::segments($path); // will throw on invalid syntax

        return str_contains($path, '*');
    }
}
