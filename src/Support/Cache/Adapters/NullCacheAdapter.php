<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache\Adapters;

use event4u\DataHelpers\Support\Cache\CacheInterface;

/**
 * Null cache adapter that doesn't cache anything.
 *
 * This adapter is used when caching is disabled (CacheDriver::NONE).
 * All operations are no-ops and always return false/null.
 */
final class NullCacheAdapter implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return false;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        return false;
    }

    public function deleteMultiple(array $keys): bool
    {
        return false;
    }
}

