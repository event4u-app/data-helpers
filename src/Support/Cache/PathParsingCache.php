<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use InvalidArgumentException;

/**
 * Cache for parsed dot-notation paths and wildcard resolutions.
 *
 * Dot-notation paths like 'users.*.email' need to be parsed into segments
 * and analyzed for wildcards. This cache stores the parsed results to avoid
 * repeated string operations.
 *
 * Phase 3 Optimization: Centralized path parsing cache
 * - Caches path segments (explode by '.')
 * - Caches wildcard detection
 * - Caches compiled path information
 * - Statistics tracking (hit/miss ratio)
 *
 * Performance Impact:
 * - Eliminates repeated explode() calls
 * - Eliminates repeated str_contains() checks
 * - Expected: 15-25% faster path operations
 */
final class PathParsingCache
{
    /**
     * Cache for path segments.
     *
     * Format: ['users.*.email' => ['users', '*', 'email']]
     *
     * @var array<string, array<int, string>>
     */
    private static array $segmentsCache = [];

    /**
     * Cache for wildcard detection.
     *
     * Format: ['users.*.email' => true, 'user.name' => false]
     *
     * @var array<string, bool>
     */
    private static array $wildcardCache = [];

    /**
     * Cache for compiled path information.
     *
     * Format: ['users.*.email' => ['segments' => [...], 'hasWildcard' => true, 'depth' => 3]]
     *
     * @var array<string, array{segments: array<int, string>, hasWildcard: bool, depth: int}>
     */
    private static array $compiledCache = [];

    /**
     * Statistics for cache performance monitoring.
     *
     * @var array{hits: int, misses: int, size: int}
     */
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
    ];

    /** Maximum cache size before cleanup (prevent memory leaks) */
    private const MAX_CACHE_SIZE = 1000;

    /**
     * Get path segments (cached).
     *
     * Splits a dot-notation path into segments.
     * Empty segments are not allowed and will throw InvalidArgumentException.
     *
     * @param string $path The dot-notation path
     * @return array<int, string> The path segments
     *
     * @throws InvalidArgumentException If path has invalid syntax (leading/trailing dot, double dots)
     */
    public static function getSegments(string $path): array
    {
        // Check cache first
        if (array_key_exists($path, self::$segmentsCache)) {
            self::$stats['hits']++;

            return self::$segmentsCache[$path];
        }

        self::$stats['misses']++;

        // Empty path returns empty array
        if ('' === $path) {
            return self::$segmentsCache[$path] = [];
        }

        // Validate path syntax
        if ('.' === $path[0]) {
            throw new InvalidArgumentException('Invalid dot-path syntax: leading dot in "' . $path . '"');
        }
        if (str_ends_with($path, '.')) {
            throw new InvalidArgumentException('Invalid dot-path syntax: trailing dot in "' . $path . '"');
        }
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('Invalid dot-path syntax: double dot in "' . $path . '"');
        }

        // Split by dot
        $segments = explode('.', $path);

        // Defensive check for empty segments
        foreach ($segments as $seg) {
            if ('' === $seg) {
                throw new InvalidArgumentException('Invalid dot-path syntax: empty segment in "' . $path . '"');
            }
        }

        // Cache and return
        self::$segmentsCache[$path] = $segments;
        self::$stats['size'] = count(self::$segmentsCache) + count(self::$wildcardCache) + count(self::$compiledCache);

        // Cleanup if cache is too large
        if (self::$stats['size'] >= self::MAX_CACHE_SIZE) {
            self::cleanup();
        }

        return $segments;
    }

    /**
     * Check if path contains wildcard (cached).
     *
     * @param string $path The dot-notation path
     * @return bool True if path contains '*'
     *
     * @throws InvalidArgumentException If path has invalid syntax
     */
    public static function hasWildcard(string $path): bool
    {
        // Check cache first
        if (array_key_exists($path, self::$wildcardCache)) {
            self::$stats['hits']++;

            return self::$wildcardCache[$path];
        }

        self::$stats['misses']++;

        // Empty path has no wildcard
        if ('' === $path) {
            return self::$wildcardCache[$path] = false;
        }

        // Validate syntax (will throw on invalid paths)
        self::getSegments($path);

        // Check for wildcard
        $hasWildcard = str_contains($path, '*');
        self::$wildcardCache[$path] = $hasWildcard;
        self::$stats['size'] = count(self::$segmentsCache) + count(self::$wildcardCache) + count(self::$compiledCache);

        return $hasWildcard;
    }

    /**
     * Get compiled path information (cached).
     *
     * Returns all path information in a single call for maximum performance.
     *
     * @param string $path The dot-notation path
     * @return array{segments: array<int, string>, hasWildcard: bool, depth: int}
     *
     * @throws InvalidArgumentException If path has invalid syntax
     */
    public static function compile(string $path): array
    {
        // Check cache first
        if (isset(self::$compiledCache[$path])) {
            self::$stats['hits']++;

            return self::$compiledCache[$path];
        }

        self::$stats['misses']++;

        // Get segments (cached)
        $segments = self::getSegments($path);

        // Get wildcard status (cached)
        $hasWildcard = self::hasWildcard($path);

        // Calculate depth
        $depth = count($segments);

        // Compile and cache
        $compiled = [
            'segments' => $segments,
            'hasWildcard' => $hasWildcard,
            'depth' => $depth,
        ];

        self::$compiledCache[$path] = $compiled;
        self::$stats['size'] = count(self::$segmentsCache) + count(self::$wildcardCache) + count(self::$compiledCache);

        // Cleanup if cache is too large
        if (self::$stats['size'] >= self::MAX_CACHE_SIZE) {
            self::cleanup();
        }

        return $compiled;
    }

    /**
     * Warm the cache with commonly used paths.
     *
     * @param array<int, string> $paths List of paths to pre-compile
     */
    public static function warm(array $paths): void
    {
        foreach ($paths as $path) {
            self::compile($path);
        }
    }

    /**
     * Clear all caches.
     */
    public static function clear(): void
    {
        self::$segmentsCache = [];
        self::$wildcardCache = [];
        self::$compiledCache = [];
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
        ];
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, hitRatio: float}
     */
    public static function getStats(): array
    {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRatio = $total > 0 ? self::$stats['hits'] / $total : 0.0;

        return [
            'hits' => self::$stats['hits'],
            'misses' => self::$stats['misses'],
            'size' => self::$stats['size'],
            'hitRatio' => $hitRatio,
        ];
    }

    /**
     * Reset statistics without clearing the cache.
     */
    public static function resetStats(): void
    {
        self::$stats['hits'] = 0;
        self::$stats['misses'] = 0;
        // Keep size as is
    }

    /**
     * Cleanup old entries when cache is too large.
     *
     * Removes oldest 20% of entries from each cache (simple LRU approximation).
     */
    private static function cleanup(): void
    {
        $removeCount = (int)(self::MAX_CACHE_SIZE * 0.2 / 3); // Divide by 3 caches

        self::$segmentsCache = array_slice(self::$segmentsCache, $removeCount, null, true);
        self::$wildcardCache = array_slice(self::$wildcardCache, $removeCount, null, true);
        self::$compiledCache = array_slice(self::$compiledCache, $removeCount, null, true);

        self::$stats['size'] = count(self::$segmentsCache) + count(self::$wildcardCache) + count(self::$compiledCache);
    }
}

