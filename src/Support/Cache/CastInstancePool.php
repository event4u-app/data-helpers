<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;
use InvalidArgumentException;

/**
 * Pool of reusable cast instances for performance optimization.
 *
 * Instead of creating new cast instances for every property cast operation,
 * this pool maintains a cache of cast instances that can be reused.
 *
 * Phase 3 Optimization: Centralized cast instance management
 * - Lazy instantiation (only create when needed)
 * - Singleton pattern per cast class + parameters
 * - Statistics tracking (hit/miss ratio)
 * - Memory-efficient (reuses instances)
 *
 * Performance Impact:
 * - Reduces object creation overhead
 * - Improves memory usage
 * - Expected: 10-20% faster cast operations
 */
final class CastInstancePool
{
    /**
     * Pool of cast instances keyed by cast class + parameters.
     *
     * Format: ['CastClass:param1:param2' => CastInstance]
     *
     * @var array<string, CastsAttributes>
     */
    private static array $pool = [];

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

    /** Maximum pool size before cleanup (prevent memory leaks) */
    private const MAX_POOL_SIZE = 200;

    /**
     * Get or create a cast instance from the pool.
     *
     * @param class-string<CastsAttributes> $castClass The cast class name
     * @param array<int|string, mixed> $parameters Optional cast parameters
     * @return CastsAttributes The cast instance
     *
     * @throws InvalidArgumentException If cast class doesn't exist or doesn't implement CastsAttributes
     */
    public static function get(string $castClass, array $parameters = []): CastsAttributes
    {
        // Create cache key from class + parameters
        $cacheKey = self::createCacheKey($castClass, $parameters);

        // Check if instance exists in pool
        if (isset(self::$pool[$cacheKey])) {
            self::$stats['hits']++;

            return self::$pool[$cacheKey];
        }

        // Cache miss - create new instance
        self::$stats['misses']++;

        // Validate cast class
        if (!class_exists($castClass)) {
            throw new InvalidArgumentException(sprintf('Cast class %s does not exist', $castClass));
        }

        if (!is_subclass_of($castClass, CastsAttributes::class)) {
            throw new InvalidArgumentException(
                sprintf('Cast class %s must implement %s', $castClass, CastsAttributes::class)
            );
        }

        // Create new instance with parameters
        $instance = new $castClass(...$parameters);

        // Store in pool
        self::$pool[$cacheKey] = $instance;
        self::$stats['size'] = count(self::$pool);

        // Cleanup if pool is too large
        if (self::$stats['size'] >= self::MAX_POOL_SIZE) {
            self::cleanup();
        }

        return $instance;
    }

    /**
     * Check if a cast instance exists in the pool.
     *
     * @param class-string<CastsAttributes> $castClass The cast class name
     * @param array<int|string, mixed> $parameters Optional cast parameters
     */
    public static function has(string $castClass, array $parameters = []): bool
    {
        $cacheKey = self::createCacheKey($castClass, $parameters);

        return isset(self::$pool[$cacheKey]);
    }

    /**
     * Warm the pool with commonly used cast instances.
     *
     * Pre-creates cast instances for better performance on first use.
     *
     * @param array<class-string<CastsAttributes>, array<int, array<int|string, mixed>>> $casts
     *        Format: ['CastClass' => [[], ['param1'], ['param1', 'param2']]]
     */
    public static function warm(array $casts): void
    {
        foreach ($casts as $castClass => $parameterSets) {
            foreach ($parameterSets as $parameters) {
                self::get($castClass, $parameters);
            }
        }
    }

    /**
     * Clear the entire pool.
     *
     * Useful for testing or when you want to free memory.
     */
    public static function clear(): void
    {
        self::$pool = [];
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
        ];
    }

    /**
     * Get pool statistics.
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
     * Reset statistics without clearing the pool.
     */
    public static function resetStats(): void
    {
        self::$stats['hits'] = 0;
        self::$stats['misses'] = 0;
        // Keep size as is
    }

    /**
     * Create a cache key from cast class and parameters.
     *
     * @param class-string<CastsAttributes> $castClass
     * @param array<int|string, mixed> $parameters
     */
    private static function createCacheKey(string $castClass, array $parameters): string
    {
        if ([] === $parameters) {
            return $castClass;
        }

        // Serialize parameters for cache key
        // Use json_encode for consistent key generation
        $paramKey = json_encode($parameters, JSON_THROW_ON_ERROR);

        return $castClass . ':' . $paramKey;
    }

    /**
     * Cleanup old entries when pool is too large.
     *
     * Removes oldest 20% of entries (simple LRU approximation).
     */
    private static function cleanup(): void
    {
        $removeCount = (int)(self::MAX_POOL_SIZE * 0.2);

        // Remove first N entries (oldest)
        self::$pool = array_slice(self::$pool, $removeCount, null, true);
        self::$stats['size'] = count(self::$pool);
    }
}

