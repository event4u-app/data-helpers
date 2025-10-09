<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache\Drivers;

use event4u\DataHelpers\Cache\CacheInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache driver - uses Laravel's cache system.
 */
final class LaravelCacheDriver implements CacheInterface
{
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly string $prefix = 'data_helpers:',
        private readonly ?int $defaultTtl = null,
    ) {
    }

    public function get(string $key): mixed
    {
        $value = Cache::get($this->prefix . $key);

        if (null === $value) {
            $this->misses++;
            return null;
        }

        $this->hits++;
        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // Use provided TTL, fallback to default TTL, or cache forever
        $effectiveTtl = $ttl ?? $this->defaultTtl;

        if (null !== $effectiveTtl) {
            Cache::put($this->prefix . $key, $value, $effectiveTtl);
        } else {
            Cache::forever($this->prefix . $key, $value);
        }
    }

    public function has(string $key): bool
    {
        return Cache::has($this->prefix . $key);
    }

    public function delete(string $key): void
    {
        Cache::forget($this->prefix . $key);
    }

    public function clear(): void
    {
        // Laravel doesn't have a prefix-based clear, so we track keys manually
        // For now, we just flush the entire cache store
        Cache::flush();
        $this->hits = 0;
        $this->misses = 0;
    }

    /** @return array{hits: int, misses: int, size: int, max_size: int|null} */
    public function getStats(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'size' => 0, // Laravel doesn't expose cache size
            'max_size' => null,
        ];
    }
}
