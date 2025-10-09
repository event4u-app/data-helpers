<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache\Drivers;

use event4u\DataHelpers\Cache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Symfony cache driver - uses Symfony's cache system.
 */
final class SymfonyCacheDriver implements CacheInterface
{
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ?int $defaultTtl = null,
    ) {
    }

    public function get(string $key): mixed
    {
        $item = $this->cache->getItem($this->sanitizeKey($key));

        if (!$item->isHit()) {
            $this->misses++;
            return null;
        }

        $this->hits++;
        return $item->get();
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $item = $this->cache->getItem($this->sanitizeKey($key));
        $item->set($value);

        // Use provided TTL, fallback to default TTL
        $effectiveTtl = $ttl ?? $this->defaultTtl;

        if (null !== $effectiveTtl) {
            $item->expiresAfter($effectiveTtl);
        }

        $this->cache->save($item);
    }

    public function has(string $key): bool
    {
        return $this->cache->hasItem($this->sanitizeKey($key));
    }

    public function delete(string $key): void
    {
        $this->cache->deleteItem($this->sanitizeKey($key));
    }

    public function clear(): void
    {
        $this->cache->clear();
        $this->hits = 0;
        $this->misses = 0;
    }

    /** @return array{hits: int, misses: int, size: int, max_size: int|null} */
    public function getStats(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'size' => 0, // Symfony doesn't expose cache size
            'max_size' => null,
        ];
    }

    /**
     * Sanitize cache key to be PSR-6 compliant.
     * PSR-6 keys must not contain: {}()/\@:
     */
    private function sanitizeKey(string $key): string
    {
        return str_replace([':', '{', '}', '(', ')', '/', '\\', '@'], '_', $key);
    }
}
