<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

/**
 * Class-scoped cache with LRU eviction per class.
 *
 * This helper provides a cache that is scoped to a specific class,
 * with a configurable maximum number of entries per class.
 * When the limit is reached, the least recently used entry is evicted.
 */
final class ClassScopedCache
{
    private const TIMESTAMP_SUFFIX = ':timestamp';
    private const INDEX_SUFFIX = ':index';

    private static int $accessCounter = 0;

    /**
     * Get a value from class-scoped cache.
     *
     * @param string $class Class name (usually static::class)
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Value from cache or default
     */
    public static function get(string $class, string $key, mixed $default = null): mixed
    {
        $cacheKey = self::buildCacheKey($class, $key);
        $value = CacheHelper::get($cacheKey, $default);

        // Update timestamp on read (LRU)
        if ($value !== $default) {
            self::updateTimestamp($class, $key);
        }

        return $value;
    }

    /**
     * Store a value in class-scoped cache.
     *
     * @param string $class Class name (usually static::class)
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     * @param int $maxEntries Maximum entries per class (default: 25)
     */
    public static function set(
        string $class,
        string $key,
        mixed $value,
        ?int $ttl = null,
        int $maxEntries = 25
    ): void {
        $cacheKey = self::buildCacheKey($class, $key);
        $isNewEntry = !CacheHelper::has($cacheKey);

        // Check if we need to evict entries (only for new entries)
        if ($isNewEntry) {
            self::evictIfNeeded($class, $maxEntries);
        }

        // Store value
        CacheHelper::set($cacheKey, $value, $ttl);

        // Update timestamp and index
        self::updateTimestamp($class, $key);

        // Add to index only if new entry
        if ($isNewEntry) {
            self::addToIndex($class, $key);
        }
    }

    /**
     * Check if a key exists in class-scoped cache.
     *
     * @param string $class Class name (usually static::class)
     * @param string $key Cache key
     */
    public static function has(string $class, string $key): bool
    {
        return CacheHelper::has(self::buildCacheKey($class, $key));
    }

    /**
     * Remove a value from class-scoped cache.
     *
     * @param string $class Class name (usually static::class)
     * @param string $key Cache key
     */
    public static function delete(string $class, string $key): void
    {
        $cacheKey = self::buildCacheKey($class, $key);
        CacheHelper::delete($cacheKey);
        CacheHelper::delete($cacheKey . self::TIMESTAMP_SUFFIX);
        self::removeFromIndex($class, $key);
    }

    /**
     * Clear all cache entries for a specific class.
     *
     * @param string $class Class name (usually static::class)
     */
    public static function clearClass(string $class): void
    {
        $index = self::getIndex($class);

        foreach ($index as $key) {
            self::delete($class, $key);
        }

        CacheHelper::delete(self::buildIndexKey($class));
    }

    /**
     * Get or store a value in class-scoped cache.
     *
     * @param string $class Class name (usually static::class)
     * @param string $key Cache key
     * @param callable|mixed $value Value to store if key not found (can be a callback)
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     * @param int $maxEntries Maximum entries per class (default: 25)
     * @return mixed Value from cache or newly stored value
     */
    public static function remember(
        string $class,
        string $key,
        mixed $value,
        ?int $ttl = null,
        int $maxEntries = 25
    ): mixed {
        $cached = self::get($class, $key);

        if (null !== $cached) {
            return $cached;
        }

        // If value is a callback, execute it
        $resolvedValue = is_callable($value) ? $value() : $value;

        self::set($class, $key, $resolvedValue, $ttl, $maxEntries);

        return $resolvedValue;
    }

    /**
     * Get statistics for a specific class cache.
     *
     * @param string $class Class name
     * @return array{count: int, keys: array<string>}
     */
    public static function getClassStats(string $class): array
    {
        $index = self::getIndex($class);

        return [
            'count' => count($index),
            'keys' => $index,
        ];
    }

    /** Build cache key with class prefix. */
    private static function buildCacheKey(string $class, string $key): string
    {
        return sprintf('class_cache:%s:%s', $class, $key);
    }

    /** Build index key for class. */
    private static function buildIndexKey(string $class): string
    {
        return sprintf('class_cache:%s%s', $class, self::INDEX_SUFFIX);
    }

    /** Update timestamp for LRU tracking. */
    private static function updateTimestamp(string $class, string $key): void
    {
        $cacheKey = self::buildCacheKey($class, $key);
        CacheHelper::forever($cacheKey . self::TIMESTAMP_SUFFIX, ++self::$accessCounter);
    }

    /**
     * Get index of all keys for a class.
     *
     * @return array<string>
     */
    private static function getIndex(string $class): array
    {
        $index = CacheHelper::get(self::buildIndexKey($class), []);

        // @phpstan-ignore-next-line - CacheHelper returns mixed, but we know it's array<string>
        return is_array($index) ? $index : [];
    }

    /** Add key to class index. */
    private static function addToIndex(string $class, string $key): void
    {
        $index = self::getIndex($class);

        if (!in_array($key, $index, true)) {
            $index[] = $key;
            CacheHelper::forever(self::buildIndexKey($class), $index);
        }
    }

    /** Remove key from class index. */
    private static function removeFromIndex(string $class, string $key): void
    {
        $index = self::getIndex($class);
        $index = array_values(array_filter($index, fn(string $k): bool => $k !== $key));
        CacheHelper::forever(self::buildIndexKey($class), $index);
    }

    /** Evict least recently used entry if max entries reached. */
    private static function evictIfNeeded(string $class, int $maxEntries): void
    {
        $index = self::getIndex($class);

        if (count($index) < $maxEntries) {
            return;
        }

        // Find least recently used entry
        $oldestKey = null;
        $oldestTimestamp = PHP_INT_MAX;

        foreach ($index as $key) {
            $cacheKey = self::buildCacheKey($class, $key);
            $timestamp = CacheHelper::get($cacheKey . self::TIMESTAMP_SUFFIX, 0);

            if ($timestamp < $oldestTimestamp) {
                $oldestTimestamp = $timestamp;
                $oldestKey = $key;
            }
        }

        // Evict oldest entry
        if (null !== $oldestKey) {
            self::delete($class, $oldestKey);
        }
    }
}

