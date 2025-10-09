<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Template\FilterEngine;

describe('FilterEngine Transformer Caching', function(): void {
    it('caches transformer instances', function(): void {
        // Apply same filter multiple times
        $result1 = FilterEngine::apply('  hello  ', ['trim']);
        $result2 = FilterEngine::apply('  world  ', ['trim']);
        $result3 = FilterEngine::apply('  test  ', ['trim']);

        expect($result1)->toBe('hello');
        expect($result2)->toBe('world');
        expect($result3)->toBe('test');
    });

    it('reuses transformer instances for same filter', function(): void {
        // Use reflection to check if instances are reused
        $reflection = new ReflectionClass(FilterEngine::class);
        $property = $reflection->getProperty('transformerInstances');

        // Clear cache
        $property->setValue(null, []);

        // Apply filter
        FilterEngine::apply('test', ['trim']);
        /** @var array<class-string, mixed> $instances1 */
        $instances1 = $property->getValue(null);
        $count1 = count($instances1);

        // Apply same filter again
        FilterEngine::apply('test2', ['trim']);
        /** @var array<class-string, mixed> $instances2 */
        $instances2 = $property->getValue(null);
        $count2 = count($instances2);

        // Should have same number of instances (reused)
        expect($count1)->toBe($count2);
        expect($count1)->toBe(1);
    });

    it('creates separate instances for different transformers', function(): void {
        $reflection = new ReflectionClass(FilterEngine::class);
        $property = $reflection->getProperty('transformerInstances');

        // Clear cache
        $property->setValue(null, []);

        // Apply different filters
        FilterEngine::apply('test', ['trim']);
        FilterEngine::apply('TEST', ['lower']);
        FilterEngine::apply('test', ['upper']);

        /** @var array<class-string, mixed> $instances */
        $instances = $property->getValue(null);

        // Should have 3 different transformer instances
        expect(count($instances))->toBeGreaterThanOrEqual(3);
    });

    it('returns different results for different filters', function(): void {
        // Apply different filters
        $result1 = FilterEngine::apply('  HELLO  ', ['trim']);
        $result2 = FilterEngine::apply('  HELLO  ', ['lower']);
        $result3 = FilterEngine::apply('  HELLO  ', ['upper']);

        // Should be different
        expect($result1)->toBe('HELLO');
        expect($result2)->toBe('  hello  ');
        expect($result3)->toBe('  HELLO  ');

        // Apply again - should return same results (using cached instances)
        $result1Again = FilterEngine::apply('  HELLO  ', ['trim']);
        $result2Again = FilterEngine::apply('  HELLO  ', ['lower']);
        $result3Again = FilterEngine::apply('  HELLO  ', ['upper']);

        expect($result1Again)->toBe($result1);
        expect($result2Again)->toBe($result2);
        expect($result3Again)->toBe($result3);
    });

    it('does not mix up results for different transformers', function(): void {
        // Apply multiple filters in sequence
        $result1 = FilterEngine::apply('hello world', ['upper']);
        $result2 = FilterEngine::apply('HELLO WORLD', ['lower']);
        $result3 = FilterEngine::apply('  test  ', ['trim']);

        // Verify results
        expect($result1)->toBe('HELLO WORLD');
        expect($result2)->toBe('hello world');
        expect($result3)->toBe('test');

        // Apply again in different order
        $result3Again = FilterEngine::apply('  test  ', ['trim']);
        $result1Again = FilterEngine::apply('hello world', ['upper']);
        $result2Again = FilterEngine::apply('HELLO WORLD', ['lower']);

        // Should still return correct results
        expect($result1Again)->toBe('HELLO WORLD');
        expect($result2Again)->toBe('hello world');
        expect($result3Again)->toBe('test');
    });
});

