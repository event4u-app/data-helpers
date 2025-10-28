<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache\Adapters;

use event4u\DataHelpers\Support\Cache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Symfony cache adapter.
 *
 * Wraps Symfony's PSR-6 cache system for use with the cache abstraction layer.
 */
final class SymfonyCacheAdapter implements CacheInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $item = $this->cache->getItem($key);
        $item->set($value);

        if (null !== $ttl) {
            $item->expiresAfter($ttl);
        }

        return $this->cache->save($item);
    }

    public function has(string $key): bool
    {
        return $this->cache->hasItem($key);
    }

    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $items = $this->cache->getItems($keys);
        $result = [];

        foreach ($items as $key => $item) {
            $result[$key] = $item->isHit() ? $item->get() : $default;
        }

        return $result;
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            $item = $this->cache->getItem($key);
            $item->set($value);

            if (null !== $ttl) {
                $item->expiresAfter($ttl);
            }

            if (!$this->cache->save($item)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(array $keys): bool
    {
        return $this->cache->deleteItems($keys);
    }
}

