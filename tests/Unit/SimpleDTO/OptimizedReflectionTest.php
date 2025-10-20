<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\Support\ReflectionCache;

// Helper function for test setup
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupOptimizedReflection(): void
{
    ReflectionCache::clear();
}

describe('Optimized Reflection', function(): void {
    beforeEach(function(): void {
        ReflectionCache::clear();
    });

    describe('ReflectionClass Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches ReflectionClass instances', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $ref1 = ReflectionCache::getClass($dto);
            $ref2 = ReflectionCache::getClass($dto);

            expect($ref1)->toBe($ref2);
        });

        it('works with class strings', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $ref1 = ReflectionCache::getClass($dto::class);
            $ref2 = ReflectionCache::getClass($dto);

            expect($ref1->getName())->toBe($ref2->getName());
        });
    });

    describe('ReflectionProperty Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches ReflectionProperty instances', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $prop1 = ReflectionCache::getProperty($dto, 'name');
            $prop2 = ReflectionCache::getProperty($dto, 'name');

            expect($prop1)->toBe($prop2);
        });

        it('caches negative lookups', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $prop1 = ReflectionCache::getProperty($dto, 'nonexistent');
            $prop2 = ReflectionCache::getProperty($dto, 'nonexistent');

            expect($prop1)->toBeNull()
                ->and($prop2)->toBeNull();
        });

        it('gets all properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $properties = ReflectionCache::getProperties($dto);

            expect($properties)->toHaveKey('name')
                ->and($properties)->toHaveKey('age')
                ->and(count($properties))->toBeGreaterThanOrEqual(2);
        });
    });

    describe('ReflectionMethod Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches ReflectionMethod instances', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}

                public function testMethod(): string
                {
                    return 'test';
                }
            };

            $method1 = ReflectionCache::getMethod($dto, 'testMethod');
            $method2 = ReflectionCache::getMethod($dto, 'testMethod');

            expect($method1)->toBe($method2);
        });

        it('gets all methods', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}

                public function method1(): void {}
                public function method2(): void {}
            };

            $methods = ReflectionCache::getMethods($dto);

            expect($methods)->toHaveKey('method1')
                ->and($methods)->toHaveKey('method2');
        });
    });

    describe('Property Attributes Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches property attributes', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                ) {}
            };

            $attrs1 = ReflectionCache::getPropertyAttributes($dto, 'name');
            $attrs2 = ReflectionCache::getPropertyAttributes($dto, 'name');

            expect($attrs1)->toBe($attrs2)
                ->and($attrs1)->toHaveKey(MapFrom::class);
        });

        it('returns empty array for properties without attributes', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $attrs = ReflectionCache::getPropertyAttributes($dto, 'name');

            expect($attrs)->toBeArray()
                ->and($attrs)->toBeEmpty();
        });
    });

    describe('Method Attributes Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches method attributes', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}

                #[Computed]
                public function displayName(): string
                {
                    return $this->name;
                }
            };

            $attrs1 = ReflectionCache::getMethodAttributes($dto, 'displayName');
            $attrs2 = ReflectionCache::getMethodAttributes($dto, 'displayName');

            expect($attrs1)->toBe($attrs2)
                ->and($attrs1)->toHaveKey(Computed::class);
        });
    });

    describe('Class Attributes Caching', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('caches class attributes', function(): void {
            $dto = new #[MapInputName('snake_case')]
            class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $attrs1 = ReflectionCache::getClassAttributes($dto);
            $attrs2 = ReflectionCache::getClassAttributes($dto);

            expect($attrs1)->toBe($attrs2)
                ->and($attrs1)->toHaveKey(MapInputName::class);
        });
    });

    describe('Cache Statistics', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('provides cache statistics', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}

                #[Computed]
                public function displayName(): string
                {
                    return $this->name;
                }
            };

            // Warm up cache
            ReflectionCache::getClass($dto);
            ReflectionCache::getProperties($dto);
            ReflectionCache::getMethods($dto);
            ReflectionCache::getPropertyAttributes($dto, 'name');
            ReflectionCache::getMethodAttributes($dto, 'displayName');
            ReflectionCache::getClassAttributes($dto);

            $stats = ReflectionCache::getStats();

            expect($stats)->toHaveKey('classes')
                ->and($stats)->toHaveKey('properties')
                ->and($stats)->toHaveKey('methods')
                ->and($stats)->toHaveKey('propertyAttributes')
                ->and($stats)->toHaveKey('methodAttributes')
                ->and($stats)->toHaveKey('classAttributes')
                ->and($stats)->toHaveKey('estimatedMemory')
                ->and($stats['classes'])->toBeGreaterThan(0)
                ->and($stats['estimatedMemory'])->toBeGreaterThan(0);
        });
    });

    describe('Cache Clearing', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('clears all caches', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            ReflectionCache::getClass($dto);
            ReflectionCache::getProperties($dto);

            $statsBefore = ReflectionCache::getStats();

            ReflectionCache::clear();

            $statsAfter = ReflectionCache::getStats();

            expect($statsBefore['classes'])->toBeGreaterThan(0)
                ->and($statsAfter['classes'])->toBe(0)
                ->and($statsAfter['properties'])->toBe(0);
        });

        it('clears cache for specific class', function(): void {
            $dto1 = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $dto2 = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $age = 0,
                ) {}
            };

            ReflectionCache::getClass($dto1);
            ReflectionCache::getClass($dto2);

            $statsBefore = ReflectionCache::getStats();

            ReflectionCache::clearClass($dto1::class);

            $statsAfter = ReflectionCache::getStats();

            expect($statsBefore['classes'])->toBe(2)
                ->and($statsAfter['classes'])->toBe(1);
        });
    });

    describe('Performance', function(): void {
        beforeEach(fn() => setupOptimizedReflection());

        it('completes quickly with caching', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            // Warm up cache
            ReflectionCache::getClass($dto);
            ReflectionCache::getProperties($dto);

            // Measure with cache
            $start = microtime(true);
            for ($i = 0; 10000 > $i; $i++) {
                ReflectionCache::getClass($dto);
                ReflectionCache::getProperties($dto);
            }
            $duration = microtime(true) - $start;

            // Should complete quickly (< 10ms for 10000 iterations)
            expect($duration)->toBeLessThan(0.01);
        });
    });
});

