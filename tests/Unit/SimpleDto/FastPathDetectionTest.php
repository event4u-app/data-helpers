<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto\Support\FastPath;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithAutoCast;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithCastsMethod;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithComputedMethod;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithCustomAttribute;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithHiddenProperty;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithNestedDto;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithOptionalType;
use Tests\Unit\SimpleDto\FastPath\Fixtures\SimpleDtoForFastPath;

/**
 * Tests for FastPath detection logic.
 *
 * Phase 7: Comprehensive tests for all FastPath detection scenarios.
 */
test('simple DTO without attributes is eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(SimpleDtoForFastPath::class))->toBeTrue();
});

test('DTO with #[AutoCast] is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithAutoCast::class))->toBeFalse();
});

test('DTO with #[Hidden] property is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithHiddenProperty::class))->toBeFalse();
});

test('DTO with #[Computed] method is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithComputedMethod::class))->toBeFalse();
});

test('DTO with Optional type is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithOptionalType::class))->toBeFalse();
});

test('DTO with casts() method is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithCastsMethod::class))->toBeFalse();
});

test('DTO with custom attribute is NOT eligible for FastPath', function (): void {
    expect(FastPath::canUseFastPath(DtoWithCustomAttribute::class))->toBeFalse();
});

test('DTO with nested DTO is eligible for FastPath', function (): void {
    // Nested DTOs are handled recursively in fastToArray()
    expect(FastPath::canUseFastPath(DtoWithNestedDto::class))->toBeTrue();
});

test('simple DTO at runtime without modifications is eligible for FastPath', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);

    expect(FastPath::canUseFastPathAtRuntime($dto))->toBeTrue();
});

test('simple DTO with only() is NOT eligible for FastPath at runtime', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithOnly = $dto->only(['name']);

    expect(FastPath::canUseFastPathAtRuntime($dtoWithOnly))->toBeFalse();
});

test('simple DTO with except() is NOT eligible for FastPath at runtime', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithExcept = $dto->except(['age']);

    expect(FastPath::canUseFastPathAtRuntime($dtoWithExcept))->toBeFalse();
});

test('simple DTO with with() is NOT eligible for FastPath at runtime', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithWith = $dto->with(['email' => 'test@example.com']);

    expect(FastPath::canUseFastPathAtRuntime($dtoWithWith))->toBeFalse();
});

test('simple DTO with withContext() is NOT eligible for FastPath at runtime', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithContext = $dto->withContext(['admin']);

    expect(FastPath::canUseFastPathAtRuntime($dtoWithContext))->toBeFalse();
});

test('simple DTO with includeComputed() is NOT eligible for FastPath at runtime', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithComputed = $dto->includeComputed(['someComputed']);

    expect(FastPath::canUseFastPathAtRuntime($dtoWithComputed))->toBeFalse();
});

test('FastPath detection is cached', function (): void {
    // First call
    $result1 = FastPath::canUseFastPath(SimpleDtoForFastPath::class);

    // Second call (should use cache)
    $result2 = FastPath::canUseFastPath(SimpleDtoForFastPath::class);

    expect($result1)->toBe($result2);
    expect($result1)->toBeTrue();
});

test('FastPath correctly handles empty only()', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithEmptyOnly = $dto->only([]);

    // only([]) has semantic meaning (show nothing), so NOT eligible for FastPath
    expect(FastPath::canUseFastPathAtRuntime($dtoWithEmptyOnly))->toBeFalse();
});

test('FastPath correctly handles empty except()', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithEmptyExcept = $dto->except([]);

    // except([]) means "exclude nothing", so eligible for FastPath
    expect(FastPath::canUseFastPathAtRuntime($dtoWithEmptyExcept))->toBeTrue();
});

test('FastPath correctly handles empty with()', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);
    $dtoWithEmptyWith = $dto->with([]);

    // with([]) means "add nothing", so eligible for FastPath
    expect(FastPath::canUseFastPathAtRuntime($dtoWithEmptyWith))->toBeTrue();
});

