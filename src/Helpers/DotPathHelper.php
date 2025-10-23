<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Helpers;

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
    /** @var array<string, array<int, string>> */
    private static array $segmentsCache = [];

    /** @var array<string, bool> */
    private static array $wildcardCache = [];

    /**
     * Split a dot-notation string into segments (cached).
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
        // Check cache first
        if (array_key_exists($path, self::$segmentsCache)) {
            return self::$segmentsCache[$path];
        }

        if ('' === $path) {
            return self::$segmentsCache[$path] = [];
        }

        // Fast checks for empty segments with specific messages
        if ('.' === $path[0]) {
            throw new InvalidArgumentException('Invalid dot-path syntax: leading dot in "' . $path . '"');
        }
        if (str_ends_with($path, '.')) {
            throw new InvalidArgumentException('Invalid dot-path syntax: trailing dot in "' . $path . '"');
        }
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('Invalid dot-path syntax: double dot in "' . $path . '"');
        }

        $segments = explode('.', $path);

        // Defensive check (covers rare cases like "a." or ".a" even if above changes)
        foreach ($segments as $seg) {
            if ('' === $seg) {
                throw new InvalidArgumentException('Invalid dot-path syntax: empty segment in "' . $path . '"');
            }
        }

        // Cache and return
        return self::$segmentsCache[$path] = $segments;
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

    /** Detect if a path contains at least one wildcard (cached). Validates syntax and throws on invalid paths. */
    public static function containsWildcard(string $path): bool
    {
        // Check cache first
        if (array_key_exists($path, self::$wildcardCache)) {
            return self::$wildcardCache[$path];
        }

        if ('' === $path) {
            return self::$wildcardCache[$path] = false;
        }

        // Reuse validation logic
        self::segments($path); // will throw on invalid syntax

        return self::$wildcardCache[$path] = str_contains($path, '*');
    }
}
