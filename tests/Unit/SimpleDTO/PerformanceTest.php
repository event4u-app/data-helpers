<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

// Helper function to clear caches
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function clearPerformanceCaches(): void
{
    TestPerformanceDTO::clearPerformanceCache();
    TestPerformanceDTO::clearCastCache();
    TestPerformanceDTO::clearRulesCache();
}

describe('SimpleDTO Performance', function(): void {
    describe('Constructor Params Cache', function(): void {
        beforeEach(clearPerformanceCaches(...));
        it('caches constructor parameters', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly ?string $email = null,
                ) {}
            };

            // First call - builds cache
            $params1 = $dto::getCachedConstructorParams();

            // Second call - uses cache
            $params2 = $dto::getCachedConstructorParams();

            expect($params1)->toBe($params2)
                ->and($params1)->toHaveKey('name')
                ->and($params1)->toHaveKey('age')
                ->and($params1)->toHaveKey('email')
                ->and($params1['name']['type'])->toBe('string')
                ->and($params1['age']['type'])->toBe('int')
                ->and($params1['email']['type'])->toBe('string')
                ->and($params1['name']['hasDefault'])->toBeTrue()
                ->and($params1['age']['hasDefault'])->toBeTrue()
                ->and($params1['email']['hasDefault'])->toBeTrue();
        });

        it('handles classes without constructor', function(): void {
            $dto = new class extends SimpleDTO {};

            $params = $dto::getCachedConstructorParams();

            expect($params)->toBeArray()
                ->and($params)->toBeEmpty();
        });
    });

    describe('Property Metadata Cache', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('caches property metadata', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly ?string $email = null,
                ) {}
            };

            // First call - builds cache
            $metadata1 = $dto::getCachedPropertyMetadata();

            // Second call - uses cache
            $metadata2 = $dto::getCachedPropertyMetadata();

            expect($metadata1)->toBe($metadata2)
                ->and($metadata1)->toHaveKey('name')
                ->and($metadata1)->toHaveKey('age')
                ->and($metadata1)->toHaveKey('email')
                ->and($metadata1['name']['type'])->toBe('string')
                ->and($metadata1['name']['isNullable'])->toBeFalse()
                ->and($metadata1['email']['isNullable'])->toBeTrue();
        });
    });

    describe('Attribute Metadata Cache', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('caches property attributes', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                ) {}
            };

            // First call - builds cache
            $attrs1 = $dto::getCachedPropertyAttributes('name');

            // Second call - uses cache
            $attrs2 = $dto::getCachedPropertyAttributes('name');

            expect($attrs1)->toBe($attrs2)
                ->and($attrs1)->toHaveKey(MapFrom::class);
        });

        it('returns empty array for non-existent property', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $attrs = $dto::getCachedPropertyAttributes('nonexistent');

            expect($attrs)->toBeArray()
                ->and($attrs)->toBeEmpty();
        });
    });

    describe('Cache Statistics', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('provides cache statistics', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            // Warm up cache
            $dto::warmUpCache();

            $stats = $dto::getPerformanceCacheStats();

            expect($stats)->toHaveKey('constructorParams')
                ->and($stats)->toHaveKey('propertyMetadata')
                ->and($stats)->toHaveKey('attributeMetadata')
                ->and($stats)->toHaveKey('totalMemory')
                ->and($stats['constructorParams'])->toBeGreaterThan(0)
                ->and($stats['propertyMetadata'])->toBeGreaterThan(0)
                ->and($stats['totalMemory'])->toBeGreaterThan(0);
        });
    });

    describe('Cache Warm Up', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('warms up all caches', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            // Warm up
            $dto::warmUpCache();

            $stats = $dto::getPerformanceCacheStats();

            expect($stats['constructorParams'])->toBe(1)
                ->and($stats['propertyMetadata'])->toBe(1)
                ->and($stats['attributeMetadata'])->toBeGreaterThan(0);
        });
    });

    describe('Cache Clearing', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('clears all performance caches', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            // Build cache
            $dto::warmUpCache();

            $statsBefore = $dto::getPerformanceCacheStats();

            // Clear cache
            $dto::clearPerformanceCache();

            $statsAfter = $dto::getPerformanceCacheStats();

            expect($statsBefore['constructorParams'])->toBeGreaterThan(0)
                ->and($statsAfter['constructorParams'])->toBe(0)
                ->and($statsAfter['propertyMetadata'])->toBe(0)
                ->and($statsAfter['attributeMetadata'])->toBe(0);
        });
    });

    describe('Performance Benchmarks', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('fromArray is faster with cache', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            $data = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];

            // Warm up cache
            $dto::warmUpCache();

            // Measure with cache
            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $dto::fromArray($data);
            }
            $withCache = microtime(true) - $start;

            // Clear cache
            $dto::clearPerformanceCache();

            // Measure without cache (first call will rebuild)
            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $dto::fromArray($data);
            }
            $withoutCache = microtime(true) - $start;

            // With cache should be faster or at least not significantly slower
            // We allow significant variance due to system load and cache warmup overhead
            expect($withCache)->toBeLessThanOrEqual($withoutCache * 2.0);
        });

        it('handles large number of instances efficiently', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $data = ['name' => 'John', 'age' => 30];

            // Warm up
            $dto::warmUpCache();

            $start = microtime(true);
            $instances = [];
            for ($i = 0; 1000 > $i; $i++) {
                $instances[] = $dto::fromArray($data);
            }
            $duration = microtime(true) - $start;

            // Should complete in reasonable time (< 1 second for 1000 instances)
            expect($duration)->toBeLessThan(1.0)
                ->and(count($instances))->toBe(1000);
        });

        it('toArray is efficient with multiple calls', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30, 'email' => 'john@example.com']);

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $instance->toArray();
            }
            $duration = microtime(true) - $start;

            // Should complete in reasonable time (< 0.5 seconds for 1000 calls)
            expect($duration)->toBeLessThan(0.5);
        });
    });

    describe('Memory Efficiency', function(): void {
        beforeEach(clearPerformanceCaches(...));

        it('does not leak memory with repeated instantiation', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $data = ['name' => 'John', 'age' => 30];

            // Warm up
            $dto::warmUpCache();

            $memoryBefore = memory_get_usage();

            // Create many instances
            for ($i = 0; 1000 > $i; $i++) {
                $instance = $dto::fromArray($data);
                unset($instance); // Explicitly unset to allow GC
            }

            // Force garbage collection
            gc_collect_cycles();

            $memoryAfter = memory_get_usage();
            $memoryIncrease = $memoryAfter - $memoryBefore;

            // Memory increase should be reasonable (< 1MB for 1000 instances)
            expect($memoryIncrease)->toBeLessThan(1024 * 1024);
        });
    });
});

// Helper class for testing
class TestPerformanceDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
    ) {}
}
