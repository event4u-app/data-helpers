<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Support\UltraFastEngine;

// UltraFast DTOs
#[UltraFast]
class UltraFastDepartmentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count = 0,
    ) {}
}

#[UltraFast]
class UltraFastWithMapFromDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('department_name')]
        public readonly string $name,

        #[MapFrom('department_code')]
        public readonly string $code,
    ) {}
}

#[UltraFast]
class UltraFastWithMapToDto extends SimpleDto
{
    public function __construct(
        #[MapTo('department_name')]
        public readonly string $name,

        #[MapTo('department_code')]
        public readonly string $code,
    ) {}
}

#[UltraFast]
class UltraFastCompanyDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly UltraFastDepartmentDto $department,
    ) {}
}

#[UltraFast]
class UltraFastWithNullableDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $code = null,
    ) {}
}

class NormalDepartmentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count = 0,
    ) {}
}

beforeEach(function(): void {
    UltraFastEngine::clearCache();
});

describe('UltraFast Mode', function(): void {
    describe('Basic Functionality', function(): void {
        it('creates DTO from array', function(): void {
            $dto = UltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            expect($dto->name)->toBe('Engineering')
                ->and($dto->code)->toBe('ENG')
                ->and($dto->budget)->toBe(1000000.0)
                ->and($dto->employee_count)->toBe(50);
        });

        it('converts DTO to array', function(): void {
            $dto = UltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);
        });

        it('converts DTO to JSON', function(): void {
            $dto = UltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"Engineering","code":"ENG","budget":1000000,"employee_count":50}');
        });
    });

    describe('Attribute Support', function(): void {
        it('handles MapFrom attribute', function(): void {
            /** @phpstan-ignore-next-line staticMethod.notFound */
            $dto = UltraFastWithMapFromDto::fromArray([
                'department_name' => 'Engineering',
                'department_code' => 'ENG',
            ]);

            expect($dto->name)->toBe('Engineering')
                /** @phpstan-ignore-next-line property.notFound */
                ->and($dto->code)->toBe('ENG');
        });

        it('handles MapTo attribute', function(): void {
            /** @phpstan-ignore-next-line staticMethod.notFound */
            $dto = UltraFastWithMapToDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'department_name' => 'Engineering',
                'department_code' => 'ENG',
            ]);
        });
    });

    describe('Nested DTOs', function(): void {
        it('handles nested DTOs', function(): void {
            $dto = UltraFastCompanyDto::fromArray([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);

            expect($dto->name)->toBe('Acme Corp')
                ->and($dto->department->name)->toBe('Engineering')
                ->and($dto->department->code)->toBe('ENG');
        });

        it('handles nested DTOs in toArray', function(): void {
            $dto = UltraFastCompanyDto::fromArray([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'Acme Corp',
                'department' => [
                    'name' => 'Engineering',
                    'code' => 'ENG',
                    'budget' => 1000000.0,
                    'employee_count' => 50,
                ],
            ]);
        });
    });

    describe('Default and Nullable Values', function(): void {
        it('handles default values', function(): void {
            $dto = UltraFastDepartmentDto::fromArray([
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                // employee_count is missing, should use default
            ]);

            expect($dto->employee_count)->toBe(0);
        });

        it('handles nullable values', function(): void {
            $dto = UltraFastWithNullableDto::fromArray([
                'name' => 'Engineering',
                // code is missing, should be null
            ]);

            expect($dto->name)->toBe('Engineering')
                ->and($dto->code)->toBeNull();
        });
    });

    describe('Error Handling', function(): void {
        it('throws exception for missing required parameter', function(): void {
            UltraFastDepartmentDto::fromArray([
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ]);
        })->throws(InvalidArgumentException::class, 'Missing required parameter: name');
    });

    describe('Performance', function(): void {
        it('is much faster than normal mode', function(): void {
            $data = [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ];

            // Warm up
            for ($i = 0; 100 > $i; $i++) {
                UltraFastDepartmentDto::fromArray($data);
                NormalDepartmentDto::fromArray($data);
            }

            // Benchmark UltraFast
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                UltraFastDepartmentDto::fromArray($data);
            }
            $ultraFastTime = microtime(true) - $start;

            // Benchmark Normal
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                NormalDepartmentDto::fromArray($data);
            }
            $normalTime = microtime(true) - $start;

            // UltraFast should be faster than Normal or at least as fast as Normal
            // Note: With cache, the difference is smaller. Real gains come with cache optimization.
            expect($ultraFastTime)->toBeLessThan($normalTime);
        });
    });
});
