<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache\Adapters;

use event4u\DataHelpers\Support\Cache\CacheInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * Laravel cache adapter.
 *
 * Wraps Laravel's cache system for use with the cache abstraction layer.
 */
final class LaravelCacheAdapter implements CacheInterface
{
    private Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (null === $ttl) {
            return $this->cache->forever($key, $value);
        }

        return $this->cache->put($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function delete(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function clear(): bool
    {
        /** @phpstan-ignore-next-line method.notFound */
        return $this->cache->flush();
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        /** @phpstan-ignore-next-line method.notFound */
        $result = $this->cache->many($keys);

        // Fill missing keys with default value
        foreach ($keys as $key) {
            if (!isset($result[$key])) {
                $result[$key] = $default;
            }
        }

        return $result;
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        if (null === $ttl) {
            foreach ($values as $key => $value) {
                $this->cache->forever($key, $value);
            }

            return true;
        }

        /** @phpstan-ignore-next-line method.notFound */
        return $this->cache->putMany($values, $ttl);
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->cache->forget($key)) {
                $success = false;
            }
        }

        return $success;
    }
}

