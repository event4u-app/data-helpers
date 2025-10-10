<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache\Drivers;

use event4u\DataHelpers\Cache\CacheInterface;

/**
 * Null cache driver - no caching at all.
 */
final class NoneDriver implements CacheInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // Do nothing
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): void
    {
        // Do nothing
    }

    public function clear(): void
    {
        // Do nothing
    }

    public function getStats(): array
    {
        return [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
            'max_size' => null,
        ];
    }
}
