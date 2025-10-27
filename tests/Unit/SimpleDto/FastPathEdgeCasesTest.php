<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto\Support\FastPath;
use Tests\Unit\SimpleDto\FastPath\Fixtures\DtoWithNestedDto;
use Tests\Unit\SimpleDto\FastPath\Fixtures\SimpleDtoForFastPath;
use Tests\Utils\SimpleDtos\CompanySimpleDto;
use Tests\Utils\SimpleDtos\DepartmentSimpleDto;

/**
 * Edge case tests for FastPath.
 *
 * Phase 7: Tests for complex scenarios, nesting, collections, etc.
 */
test('FastPath fastToArray() produces same result as normal toArray() for simple DTO', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30, email: 'test@example.com');

    $fastPathResult = FastPath::fastToArray($dto);
    $normalResult = $dto->toArray();

    expect($fastPathResult)->toBe($normalResult);
});

test('FastPath handles null values correctly', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: null, email: null);

    $result = FastPath::fastToArray($dto);

    expect($result)->toBe([
        'name' => 'Test',
        'age' => null,
        'email' => null,
    ]);
});

test('FastPath handles all null values correctly', function (): void {
    $dto = new SimpleDtoForFastPath(name: null, age: null, email: null);

    $result = FastPath::fastToArray($dto);

    expect($result)->toBe([
        'name' => null,
        'age' => null,
        'email' => null,
    ]);
});

test('FastPath handles nested DTOs correctly', function (): void {
    $nested = new SimpleDtoForFastPath(name: 'Nested', age: 25, email: 'nested@example.com');
    $dto = new DtoWithNestedDto(name: 'Parent', nested: $nested);

    // FastPath returns nested DTOs as objects (not converted to arrays)
    // The conversion happens in processDataForSerialization()
    $result = FastPath::fastToArray($dto);

    expect($result['name'])->toBe('Parent');
    expect($result['nested'])->toBeInstanceOf(SimpleDtoForFastPath::class);
    expect($result['nested']->name)->toBe('Nested');
    expect($result['nested']->age)->toBe(25);
});

test('FastPath handles null nested DTO correctly', function (): void {
    $dto = new DtoWithNestedDto(name: 'Parent', nested: null);

    $result = FastPath::fastToArray($dto);

    expect($result)->toBe([
        'name' => 'Parent',
        'nested' => null,
    ]);
});

test('FastPath handles deeply nested DTOs correctly', function (): void {
    $level2 = new SimpleDtoForFastPath(name: 'Level 2', age: 25, email: 'level2@example.com');
    $level1 = new DtoWithNestedDto(name: 'Level 1', nested: $level2);

    // FastPath returns nested DTOs as objects
    $result = FastPath::fastToArray($level1);

    expect($result['name'])->toBe('Level 1');
    expect($result['nested'])->toBeInstanceOf(SimpleDtoForFastPath::class);
    expect($result['nested']->name)->toBe('Level 2');
});

test('FastPath handles arrays of DTOs correctly', function (): void {
    $departments = [
        new DepartmentSimpleDto(name: 'IT', code: 'IT'),
        new DepartmentSimpleDto(name: 'HR', code: 'HR'),
    ];

    $dto = new CompanySimpleDto(
        name: 'Test Company',
        departments: $departments,
    );

    // FastPath returns arrays of DTOs as arrays of objects (not converted)
    $result = FastPath::fastToArray($dto);

    expect($result['departments'])->toBeArray();
    expect($result['departments'])->toHaveCount(2);
    expect($result['departments'][0])->toBeInstanceOf(DepartmentSimpleDto::class);
    expect($result['departments'][0]->name)->toBe('IT');
    expect($result['departments'][1])->toBeInstanceOf(DepartmentSimpleDto::class);
    expect($result['departments'][1]->name)->toBe('HR');
});

test('FastPath handles empty arrays correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        departments: [],
        projects: [],
    );

    $result = FastPath::fastToArray($dto);

    expect($result['departments'])->toBe([]);
    expect($result['projects'])->toBe([]);
});

test('FastPath handles mixed arrays correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        departments: [
            new DepartmentSimpleDto(name: 'IT', code: 'IT'),
        ],
        projects: [], // Empty array
    );

    $result = FastPath::fastToArray($dto);

    expect($result['departments'])->toHaveCount(1);
    expect($result['projects'])->toBe([]);
});

test('FastPath handles boolean values correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        is_active: true,
    );

    $result = FastPath::fastToArray($dto);

    expect($result['is_active'])->toBeTrue();
});

test('FastPath handles false boolean correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        is_active: false,
    );

    $result = FastPath::fastToArray($dto);

    expect($result['is_active'])->toBeFalse();
});

test('FastPath handles numeric values correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        founded_year: 2020,
        employee_count: 100,
        annual_revenue: 1000000.50,
    );

    $result = FastPath::fastToArray($dto);

    expect($result['founded_year'])->toBe(2020);
    expect($result['employee_count'])->toBe(100);
    expect($result['annual_revenue'])->toBe(1000000.50);
});

test('FastPath handles zero values correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        founded_year: 0,
        employee_count: 0,
        annual_revenue: 0.0,
    );

    $result = FastPath::fastToArray($dto);

    expect($result['founded_year'])->toBe(0);
    expect($result['employee_count'])->toBe(0);
    expect($result['annual_revenue'])->toBe(0.0);
});

test('FastPath handles string values correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: 'Test Company',
        email: 'test@example.com',
        phone: '+1234567890',
        address: '123 Main St',
        city: 'New York',
        country: 'USA',
    );

    $result = FastPath::fastToArray($dto);

    expect($result['name'])->toBe('Test Company');
    expect($result['email'])->toBe('test@example.com');
    expect($result['phone'])->toBe('+1234567890');
    expect($result['address'])->toBe('123 Main St');
    expect($result['city'])->toBe('New York');
    expect($result['country'])->toBe('USA');
});

test('FastPath handles empty strings correctly', function (): void {
    $dto = new CompanySimpleDto(
        name: '',
        email: '',
    );

    $result = FastPath::fastToArray($dto);

    expect($result['name'])->toBe('');
    expect($result['email'])->toBe('');
});

test('FastPath toArray() uses FastPath for eligible DTOs', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);

    // Should use FastPath
    expect(FastPath::canUseFastPath(SimpleDtoForFastPath::class))->toBeTrue();
    expect(FastPath::canUseFastPathAtRuntime($dto))->toBeTrue();

    $result = $dto->toArray();

    expect($result)->toBe([
        'name' => 'Test',
        'age' => 30,
        'email' => null,
    ]);
});

test('FastPath jsonSerialize() uses FastPath for eligible DTOs', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30);

    $result = $dto->jsonSerialize();

    expect($result)->toBe([
        'name' => 'Test',
        'age' => 30,
        'email' => null,
    ]);
});

test('FastPath json_encode() works correctly', function (): void {
    $dto = new SimpleDtoForFastPath(name: 'Test', age: 30, email: 'test@example.com');

    $json = json_encode($dto);
    $decoded = json_decode($json, true);

    expect($decoded)->toBe([
        'name' => 'Test',
        'age' => 30,
        'email' => 'test@example.com',
    ]);
});

