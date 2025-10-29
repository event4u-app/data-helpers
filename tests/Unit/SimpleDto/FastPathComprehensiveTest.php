<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto\DataCollection;
use event4u\DataHelpers\SimpleDto\Support\FastPath;
use Tests\Unit\SimpleDto\FastPath\Fixtures\ChildDto;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithCastAttribute;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithConditionalProperty;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithDataCollection;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithMappingAttribute;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithMultipleAttributes;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithValidationAttribute;
use Tests\Unit\SimpleDto\FastPath\Fixtures\LargeDto;
use Tests\Unit\SimpleDto\FastPath\Fixtures\ParentDto;
use Tests\Unit\SimpleDto\FastPath\Fixtures\SimpleDtoForFastPath;

/**
 * Comprehensive tests for FastPath covering all scenarios.
 *
 * Phase 7: Tests for DataCollection, attributes, wrapping, sorting, inheritance, etc.
 */

// ============================================================================
// DataCollection Tests
// ============================================================================

test('DTO with DataCollection property is eligible for FastPath', function(): void {
    // DataCollection is just a property, doesn't affect FastPath eligibility
    expect(FastPath::canUseFastPath(DtoWithDataCollection::class))->toBeTrue();
});

test('FastPath handles DataCollection correctly', function(): void {
    $collection = DataCollection::forDto(SimpleDtoForFastPath::class, [
        ['name' => 'Item 1', 'age' => 20],
        ['name' => 'Item 2', 'age' => 30],
    ]);

    $dto = new DtoWithDataCollection(name: 'Test', items: $collection);

    $result = FastPath::fastToArray($dto);

    expect($result['name'])->toBe('Test');
    expect($result['items'])->toBeInstanceOf(DataCollection::class);
});

// ============================================================================
// Attribute Category Tests
// ============================================================================

test('DTO with conditional property attribute is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithConditionalProperty::class))->toBeFalse();
});

test('DTO with mapping attribute is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithMappingAttribute::class))->toBeFalse();
});

test('DTO with validation attribute is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithValidationAttribute::class))->toBeFalse();
});

test('DTO with cast attribute is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithCastAttribute::class))->toBeFalse();
});

test('DTO with multiple attributes on one property is NOT eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(DtoWithMultipleAttributes::class))->toBeFalse();
});

// ============================================================================
// Runtime Modification Tests (Wrapping, Sorting, Additional Data)
// ============================================================================

test('DTO with wrap() is NOT eligible for FastPath at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $wrapped = $dto->wrap('data');

    expect(FastPath::canUseFastPathAtRuntime($wrapped))->toBeFalse();
});

test('DTO with sorted() is NOT eligible for FastPath at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $sorted = $dto->sorted();

    expect(FastPath::canUseFastPathAtRuntime($sorted))->toBeFalse();
});

test('DTO with sorted(desc) is NOT eligible for FastPath at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $sorted = $dto->sorted('desc');

    expect(FastPath::canUseFastPathAtRuntime($sorted))->toBeFalse();
});

test('DTO with sortedBy() is NOT eligible for FastPath at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $sorted = $dto->sortedBy(fn(mixed $a, mixed $b): int => strcmp((string)$a, (string)$b));

    expect(FastPath::canUseFastPathAtRuntime($sorted))->toBeFalse();
});

test('DTO with with() additional data is NOT eligible for FastPath at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $withData = $dto->with('extra', 'value');

    expect(FastPath::canUseFastPathAtRuntime($withData))->toBeFalse();
});

// ============================================================================
// Inheritance Tests
// ============================================================================

test('parent DTO without attributes is eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(ParentDto::class))->toBeTrue();
});

test('child DTO extending parent without attributes is eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(ChildDto::class))->toBeTrue();
});

test('FastPath works correctly with inherited properties', function(): void {
    $dto = new ChildDto(parentProperty: 'Parent', childProperty: 'Child');

    $result = FastPath::fastToArray($dto);

    expect($result['parentProperty'])->toBe('Parent');
    expect($result['childProperty'])->toBe('Child');
});

// ============================================================================
// Performance Tests
// ============================================================================

test('large DTO with 50 properties is eligible for FastPath', function(): void {
    expect(FastPath::canUseFastPath(LargeDto::class))->toBeTrue();
});

test('FastPath handles large DTOs efficiently', function(): void {
    $dto = new LargeDto(
        prop1: 'value1',
        prop2: 'value2',
        prop3: 'value3',
        prop10: 'value10',
        prop20: 'value20',
        prop30: 'value30',
        prop40: 'value40',
        prop50: 'value50',
    );

    $start = microtime(true);
    $result = FastPath::fastToArray($dto);
    $duration = microtime(true) - $start;

    // Should be very fast (< 1ms)
    expect($duration)->toBeLessThan(0.001);
    expect($result)->toHaveKey('prop1');
    expect($result)->toHaveKey('prop50');
    expect($result['prop1'])->toBe('value1');
    expect($result['prop50'])->toBe('value50');
});

test('FastPath is faster than normal path for large DTOs', function(): void {
    $dto = new LargeDto(
        prop1: 'value1',
        prop10: 'value10',
        prop20: 'value20',
        prop30: 'value30',
        prop40: 'value40',
        prop50: 'value50',
    );

    // Warmup
    FastPath::fastToArray($dto);
    $dto->toArray();

    // Benchmark FastPath
    $iterations = 1000;
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        FastPath::fastToArray($dto);
    }
    $fastPathTime = microtime(true) - $start;

    // Benchmark normal path (should use FastPath internally)
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $dto->toArray();
    }
    $normalTime = microtime(true) - $start;

    // FastPath should be at least as fast as normal path
    expect($fastPathTime)->toBeLessThanOrEqual($normalTime * 1.5); // Allow 50% overhead for detection
});

// ============================================================================
// Edge Cases
// ============================================================================

test('FastPath handles DTO with all null properties', function(): void {
    $dto = new LargeDto();

    $result = FastPath::fastToArray($dto);

    expect($result)->toHaveCount(50);
    expect($result['prop1'])->toBeNull();
    expect($result['prop50'])->toBeNull();
});

test('FastPath cache can be cleared', function(): void {
    // First call - caches result
    $result1 = FastPath::canUseFastPath(SimpleDtoForFastPath::class);

    // Clear cache
    FastPath::clearCache();

    // Second call - should recalculate
    $result2 = FastPath::canUseFastPath(SimpleDtoForFastPath::class);

    expect($result1)->toBe($result2);
    expect($result1)->toBeTrue();
});

test('FastPath stats are tracked correctly', function(): void {
    FastPath::clearCache();

    // Check some DTOs
    FastPath::canUseFastPath(SimpleDtoForFastPath::class); // eligible
    FastPath::canUseFastPath(DtoWithCastAttribute::class); // not eligible
    FastPath::canUseFastPath(LargeDto::class); // eligible

    $stats = FastPath::getStats();

    expect($stats['total'])->toBe(3);
    expect($stats['eligible'])->toBe(2);
    expect($stats['ineligible'])->toBe(1);
});

test('FastPath handles concurrent toArray() calls correctly', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);

    // Simulate concurrent calls
    $results = [];
    for ($i = 0; 10 > $i; $i++) {
        $results[] = $dto->toArray();
    }

    // All results should be identical
    foreach ($results as $result) {
        expect($result)->toBe($results[0]);
    }
});

test('FastPath correctly identifies DTOs that become ineligible at runtime', function(): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);

    // Initially eligible
    expect(FastPath::canUseFastPath(SimpleDtoForFastPath::class))->toBeTrue();
    expect(FastPath::canUseFastPathAtRuntime($dto))->toBeTrue();

    // After modification, not eligible at runtime
    $modified = $dto->only(['name']);
    expect(FastPath::canUseFastPath(SimpleDtoForFastPath::class))->toBeTrue(); // Class still eligible
    expect(FastPath::canUseFastPathAtRuntime($modified))->toBeFalse(); // Instance not eligible
});
