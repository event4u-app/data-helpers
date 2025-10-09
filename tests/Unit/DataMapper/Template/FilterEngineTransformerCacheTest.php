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
});

