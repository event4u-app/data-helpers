<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache\Drivers;

use event4u\DataHelpers\Cache\CacheInterface;
use event4u\DataHelpers\Cache\LruCache;

/**
 * In-memory cache driver using LRU cache.
 */
final readonly class MemoryDriver implements CacheInterface
{
    private LruCache $cache;

    public function __construct(int $maxSize = 1000)
    {
        $this->cache = new LruCache($maxSize);
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // TTL is ignored for in-memory cache (no expiration)
        $this->cache->set($key, $value);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function clear(): void
    {
        $this->cache->clear();
    }

    public function getStats(): array
    {
        return $this->cache->getStats();
    }
}
