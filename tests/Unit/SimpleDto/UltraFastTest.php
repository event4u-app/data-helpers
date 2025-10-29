<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Support\UltraFastEngine;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for #[UltraFast] performance attribute.
 */
final class UltraFastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UltraFastEngine::clearCache();
    }

    #[Test]
    public function it_creates_ultra_fast_dto_from_array(): void
    {
        $dto = UltraFastDepartmentDto::fromArray([
            'name' => 'Engineering',
            'code' => 'ENG',
            'budget' => 1000000.0,
            'employee_count' => 50,
        ]);

        $this->assertSame('Engineering', $dto->name);
        $this->assertSame('ENG', $dto->code);
        $this->assertSame(1000000.0, $dto->budget);
        $this->assertSame(50, $dto->employee_count);
    }

    #[Test]
    public function it_converts_ultra_fast_dto_to_array(): void
    {
        $dto = UltraFastDepartmentDto::fromArray([
            'name' => 'Engineering',
            'code' => 'ENG',
            'budget' => 1000000.0,
            'employee_count' => 50,
        ]);

        $array = $dto->toArray();

        $this->assertSame([
            'name' => 'Engineering',
            'code' => 'ENG',
            'budget' => 1000000.0,
            'employee_count' => 50,
        ], $array);
    }

    #[Test]
    public function it_handles_map_from_attribute(): void
    {
        $dto = UltraFastWithMapFromDto::fromArray([
            'department_name' => 'Engineering',
            'department_code' => 'ENG',
        ]);

        $this->assertSame('Engineering', $dto->name);
        $this->assertSame('ENG', $dto->code);
    }

    #[Test]
    public function it_handles_map_to_attribute(): void
    {
        $dto = UltraFastWithMapToDto::fromArray([
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        $array = $dto->toArray();

        $this->assertSame([
            'department_name' => 'Engineering',
            'department_code' => 'ENG',
        ], $array);
    }

    #[Test]
    public function it_handles_nested_dtos(): void
    {
        $dto = UltraFastCompanyDto::fromArray([
            'name' => 'Acme Corp',
            'department' => [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ],
        ]);

        $this->assertSame('Acme Corp', $dto->name);
        $this->assertSame('Engineering', $dto->department->name);
        $this->assertSame('ENG', $dto->department->code);
    }

    #[Test]
    public function it_handles_nested_dtos_in_to_array(): void
    {
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

        $this->assertSame([
            'name' => 'Acme Corp',
            'department' => [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 1000000.0,
                'employee_count' => 50,
            ],
        ], $array);
    }

    #[Test]
    public function it_handles_default_values(): void
    {
        $dto = UltraFastDepartmentDto::fromArray([
            'name' => 'Engineering',
            'code' => 'ENG',
            'budget' => 1000000.0,
            // employee_count is missing, should use default
        ]);

        $this->assertSame(0, $dto->employee_count);
    }

    #[Test]
    public function it_handles_nullable_values(): void
    {
        $dto = UltraFastWithNullableDto::fromArray([
            'name' => 'Engineering',
            // code is missing, should be null
        ]);

        $this->assertSame('Engineering', $dto->name);
        $this->assertNull($dto->code);
    }

    #[Test]
    public function it_throws_exception_for_missing_required_parameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: name');

        UltraFastDepartmentDto::fromArray([
            'code' => 'ENG',
            'budget' => 1000000.0,
            'employee_count' => 50,
        ]);
    }

    #[Test]
    public function it_is_much_faster_than_normal_mode(): void
    {
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

        // UltraFast should be faster than Normal or at leas as fast as Normal
        // Note: With cache, the difference is smaller. Real gains come with cache optimization.
        $this->assertLessThan($normalTime, $ultraFastTime,
            sprintf('UltraFast (%.4fms) should be faster than Normal (%.4fms)',
                $ultraFastTime * 1000, $normalTime * 1000)
        );
    }
}

// Test DTOs

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
