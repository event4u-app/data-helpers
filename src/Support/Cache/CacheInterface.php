<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

/**
 * Cache interface for metadata and code generation caching.
 *
 * Provides a unified interface for different cache backends
 * (Laravel, Symfony, Filesystem).
 */
interface CacheInterface
{
    /**
     * Get a value from the cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist
     *
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = forever)
     *
     * @return bool True on success, false on failure
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key Cache key
     *
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Delete a value from the cache.
     *
     * @param string $key Cache key
     *
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache entries.
     *
     * @return bool True on success, false on failure
     */
    public function clear(): bool;

    /**
     * Get multiple values from the cache.
     *
     * @param array<string> $keys Cache keys
     * @param mixed $default Default value for missing keys
     *
     * @return array<string, mixed> Array of key => value pairs
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Store multiple values in the cache.
     *
     * @param array<string, mixed> $values Key => value pairs
     * @param int|null $ttl Time to live in seconds (null = forever)
     *
     * @return bool True on success, false on failure
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Delete multiple values from the cache.
     *
     * @param array<string> $keys Cache keys
     *
     * @return bool True on success, false on failure
     */
    public function deleteMultiple(array $keys): bool;
}

