<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

/**
 * Least Recently Used (LRU) Cache implementation.
 *
 * When the cache is full, the least recently used entry is discarded.
 */
final class LruCache
{
    /** @var array<string, mixed> */
    private array $cache = [];

    /** @var array<string, int> */
    private array $usage = [];

    /** @var array<string, int> Expiration timestamps (unix timestamp) */
    private array $expirations = [];

    private int $accessCounter = 0;

    public function __construct(
        private readonly int $maxEntries
    ) {
    }

    /**
     * Get value from cache.
     *
     * @return mixed|null Returns null if key not found or expired
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->cache)) {
            return null;
        }

        // Check if expired
        if ($this->isExpired($key)) {
            $this->delete($key);
            return null;
        }

        // Update usage counter
        $this->usage[$key] = ++$this->accessCounter;

        return $this->cache[$key];
    }

    /** Check if key exists in cache and is not expired. */
    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->cache)) {
            return false;
        }

        // Check if expired
        if ($this->isExpired($key)) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Set value in cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttl Time to live in seconds (null = no expiration)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // If cache is full and key doesn't exist, remove least recently used
        if (count($this->cache) >= $this->maxEntries && !array_key_exists($key, $this->cache)) {
            $this->removeLeastRecentlyUsed();
        }

        $this->cache[$key] = $value;
        $this->usage[$key] = ++$this->accessCounter;

        // Set expiration if TTL provided
        if (null !== $ttl) {
            $this->expirations[$key] = time() + $ttl;
        } else {
            // Remove expiration if exists
            unset($this->expirations[$key]);
        }
    }

    /** Delete a value from cache. */
    public function delete(string $key): void
    {
        unset($this->cache[$key], $this->usage[$key], $this->expirations[$key]);
    }

    /** Clear all cache entries. */
    public function clear(): void
    {
        $this->cache = [];
        $this->usage = [];
        $this->expirations = [];
        $this->accessCounter = 0;
    }

    /** Get current cache size. */
    public function size(): int
    {
        return count($this->cache);
    }

    /** Get maximum cache size. */
    public function maxSize(): int
    {
        return $this->maxEntries;
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, max_size: int|null}
     */
    public function getStats(): array
    {
        $size = $this->size();
        $maxSize = $this->maxSize();

        return [
            'hits' => 0, // LruCache doesn't track hits/misses
            'misses' => 0,
            'size' => $size,
            'max_size' => $maxSize,
        ];
    }

    /** Check if a key is expired. */
    private function isExpired(string $key): bool
    {
        if (!isset($this->expirations[$key])) {
            return false;
        }

        return time() >= $this->expirations[$key];
    }

    /** Remove least recently used entry. */
    private function removeLeastRecentlyUsed(): void
    {
        if ([] === $this->usage) {
            return;
        }

        // Find key with lowest usage counter
        $lruKey = array_key_first($this->usage);
        $lruValue = $this->usage[$lruKey];

        foreach ($this->usage as $key => $value) {
            if ($value < $lruValue) {
                $lruKey = $key;
                $lruValue = $value;
            }
        }

        unset($this->cache[$lruKey], $this->usage[$lruKey], $this->expirations[$lruKey]);
    }
}
