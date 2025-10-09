<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

/**
 * Cache helper - provides convenient methods for caching.
 * 
 * This is a facade for the CacheManager that provides a simple API
 * for working with the cache system.
 */
final class CacheHelper
{
    /**
     * Get a value from cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Value from cache or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = CacheManager::getInstance()->get($key);
        
        return $value ?? $default;
    }

    /**
     * Store a value in cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): void
    {
        CacheManager::getInstance()->set($key, $value, $ttl);
    }

    /**
     * Store a value in cache (alias for set).
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     */
    public static function put(string $key, mixed $value, ?int $ttl = null): void
    {
        self::set($key, $value, $ttl);
    }

    /**
     * Check if a key exists in cache.
     *
     * @param string $key Cache key
     */
    public static function has(string $key): bool
    {
        return CacheManager::getInstance()->has($key);
    }

    /**
     * Check if a key exists in cache (alias for has).
     *
     * @param string $key Cache key
     */
    public static function exists(string $key): bool
    {
        return self::has($key);
    }

    /**
     * Remove a value from cache.
     *
     * @param string $key Cache key
     */
    public static function delete(string $key): void
    {
        CacheManager::getInstance()->delete($key);
    }

    /**
     * Remove a value from cache (alias for delete).
     *
     * @param string $key Cache key
     */
    public static function forget(string $key): void
    {
        self::delete($key);
    }

    /**
     * Remove a value from cache (alias for delete).
     *
     * @param string $key Cache key
     */
    public static function remove(string $key): void
    {
        self::delete($key);
    }

    /** Clear all cache entries. */
    public static function clear(): void
    {
        CacheManager::getInstance()->clear();
    }

    /** Clear all cache entries (alias for clear). */
    public static function flush(): void
    {
        self::clear();
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, max_size: int|null}
     */
    public static function getStats(): array
    {
        return CacheManager::getInstance()->getStats();
    }

    /**
     * Get a value from cache, or store a default value if not found.
     *
     * @param string $key Cache key
     * @param callable|mixed $value Value to store if key not found (can be a callback)
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     * @return mixed Value from cache or newly stored value
     */
    public static function remember(string $key, mixed $value, ?int $ttl = null): mixed
    {
        $cached = self::get($key);
        
        if (null !== $cached) {
            return $cached;
        }
        
        // If value is a callback, execute it
        $resolvedValue = is_callable($value) ? $value() : $value;
        
        self::set($key, $resolvedValue, $ttl);
        
        return $resolvedValue;
    }

    /**
     * Get multiple values from cache.
     *
     * @param array<string> $keys Cache keys
     * @param mixed $default Default value for missing keys
     * @return array<string, mixed> Key-value pairs
     */
    public static function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = self::get($key, $default);
        }
        
        return $result;
    }

    /**
     * Store multiple values in cache.
     *
     * @param array<string, mixed> $values Key-value pairs to store
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     */
    public static function setMultiple(array $values, ?int $ttl = null): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value, $ttl);
        }
    }

    /**
     * Delete multiple values from cache.
     *
     * @param array<string> $keys Cache keys to delete
     */
    public static function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            self::delete($key);
        }
    }

    /**
     * Increment a numeric value in cache.
     *
     * @param string $key Cache key
     * @param int $value Amount to increment by (default: 1)
     * @return int New value after increment
     */
    public static function increment(string $key, int $value = 1): int
    {
        $current = (int)self::get($key, 0);
        $new = $current + $value;
        self::set($key, $new);
        
        return $new;
    }

    /**
     * Decrement a numeric value in cache.
     *
     * @param string $key Cache key
     * @param int $value Amount to decrement by (default: 1)
     * @return int New value after decrement
     */
    public static function decrement(string $key, int $value = 1): int
    {
        return self::increment($key, -$value);
    }

    /**
     * Store a value in cache forever (no expiration).
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     */
    public static function forever(string $key, mixed $value): void
    {
        self::set($key, $value, null);
    }

    /**
     * Get and delete a value from cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed Value from cache or default
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::delete($key);
        
        return $value;
    }
}

