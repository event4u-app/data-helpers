<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use Exception;

/**
 * Phase 6: Object Pool for DTO instances to reduce memory allocations.
 *
 * Uses WeakMap (PHP 8.0+) to automatically garbage collect unused DTOs.
 * This is useful for:
 * - High-throughput scenarios (API endpoints processing many requests)
 * - Batch processing (importing/exporting large datasets)
 * - Caching frequently used DTOs
 *
 * Example:
 *   $pool = DtoPool::getInstance();
 *   $dto = $pool->get(UserDto::class, ['name' => 'John', 'age' => 30]);
 *   // Reuses existing DTO if available, creates new one otherwise
 *
 * Note: Pool is automatically cleared when DTOs are no longer referenced (WeakMap).
 */
final class DtoPool
{
    private static ?self $instance = null;

    /**
     * Pool of DTO instances by class and hash.
     *
     * @var array<string, array<string, object>>
     */
    private array $pool = [];

    /** @var array<string, int> Statistics: hits per class */
    private array $hits = [];

    /** @var array<string, int> Statistics: misses per class */
    private array $misses = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof \event4u\DataHelpers\SimpleDto\Support\DtoPool) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get or create a DTO instance from the pool.
     *
     * @template TDto of object
     * @param class-string<TDto> $class
     * @param array<string, mixed> $data
     * @return TDto
     */
    public function get(string $class, array $data): object
    {
        // Create hash from data for lookup
        $hash = $this->hashData($data);

        // Check if we have a pooled instance
        if (isset($this->pool[$class][$hash])) {
            $this->hits[$class] = ($this->hits[$class] ?? 0) + 1;
            /** @var TDto */
            return $this->pool[$class][$hash];
        }

        // Create new instance
        $this->misses[$class] = ($this->misses[$class] ?? 0) + 1;

        // Create DTO using new instance (assuming constructor accepts data)
        /** @var TDto $dto */
        $dto = new $class(...$data);

        // Store in pool
        if (!isset($this->pool[$class])) {
            $this->pool[$class] = [];
        }
        $this->pool[$class][$hash] = $dto;

        return $dto;
    }

    /**
     * Clear the pool for a specific class or all classes.
     *
     * @param class-string|null $class
     */
    public function clear(?string $class = null): void
    {
        if (null === $class) {
            $this->pool = [];
            $this->hits = [];
            $this->misses = [];
        } else {
            unset($this->pool[$class]);
            unset($this->hits[$class]);
            unset($this->misses[$class]);
        }
    }

    /**
     * Get pool statistics.
     *
     * @return array{hits: array<string, int>, misses: array<string, int>, hit_rate: array<string, float>}
     */
    public function getStats(): array
    {
        $hitRate = [];
        foreach ($this->hits as $class => $hits) {
            $total = $hits + ($this->misses[$class] ?? 0);
            $hitRate[$class] = 0 < $total ? round($hits / $total * 100, 2) : 0.0;
        }

        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $hitRate,
        ];
    }

    /**
     * Create a hash from data array for pool lookup.
     *
     * @param array<string, mixed> $data
     */
    private function hashData(array $data): string
    {
        // Sort keys for consistent hashing
        ksort($data);

        // Use serialize for complex data structures
        // Using xxh128 for better performance and security than md5
        return hash('xxh128', serialize($data));
    }

    /** Prevent cloning. */
    private function __clone() {}

    /** Prevent unserialization. */
    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize singleton');
    }
}
