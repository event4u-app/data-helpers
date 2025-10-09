<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

/**
 * Cache interface for different cache backends.
 */
interface CacheInterface
{
    /**
     * Get a value from cache.
     *
     * @param string $key Cache key
     * @return mixed|null Value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Store a value in cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttl Time to live in seconds (null = forever)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Check if a key exists in cache.
     *
     * @param string $key Cache key
     */
    public function has(string $key): bool;

    /**
     * Remove a value from cache.
     *
     * @param string $key Cache key
     */
    public function delete(string $key): void;

    /** Clear all cache entries. */
    public function clear(): void;

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, max_size: int|null}
     */
    public function getStats(): array;
}
