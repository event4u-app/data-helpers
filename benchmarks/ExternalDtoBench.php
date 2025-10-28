<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Tests\Utils\SimpleDtos\DepartmentSimpleDto;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;

// External DTO libraries (installed temporarily)
use ALameLlama\Carapace\Data as CarapaceData;

#[BeforeMethods('setUp')]
class ExternalDtoBench
{
    /** @var array<string, mixed> */
    private array $testData;

    /** @var array<string, mixed> */
    private array $complexData;

    public function setUp(): void
    {
        $this->testData = [
            'name' => 'Engineering',
            'code' => 'ENG',
            'budget' => 5000000.00,
            'employee_count' => 120,
            'manager_name' => 'Alice Johnson',
        ];

        $this->complexData = [
            'name' => 'Engineering Department',
            'code' => 'ENG-001',
            'budget' => 5000000.00,
            'employee_count' => 120,
            'manager_name' => 'Alice Johnson',
            'location' => 'Building A',
            'floor' => 3,
            'phone' => '+1234567890',
            'email' => 'engineering@example.com',
            'established_date' => '2020-01-15',
        ];
    }

    /** Benchmark: Our SimpleDto - fromArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoFromArray(): void
    {
        DepartmentSimpleDto::fromArray($this->testData);
    }

    /** Benchmark: Our SimpleDto with #[UltraFast] - fromArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoUltraFastFromArray(): void
    {
        UltraFastDepartmentDto::fromArray($this->testData);
    }

    /** Benchmark: Other Dtos - from() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoFrom(): void
    {
        if (!class_exists(CarapaceData::class)) {
            return; // Skip if not installed
        }
        CarapaceDepartmentData::from($this->testData);
    }

    /** Benchmark: Plain PHP - manual construction */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpConstructor(): void
    {
        new PlainDepartmentDtoWithConstructor(
            $this->testData['name'],
            $this->testData['code'],
            $this->testData['budget'],
            $this->testData['employee_count'],
            $this->testData['manager_name']
        );
    }

    /** Benchmark: Plain PHP - new + assign */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpNewAssign(): void
    {
        $dto = new PlainDepartmentDto();
        $dto->name = $this->testData['name'];
        $dto->code = $this->testData['code'];
        $dto->budget = $this->testData['budget'];
        $dto->employee_count = $this->testData['employee_count'];
        $dto->manager_name = $this->testData['manager_name'];
    }

    /** Benchmark: Our SimpleDto - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoToArray(): void
    {
        $dto = DepartmentSimpleDto::fromArray($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Our SimpleDto with #[UltraFast] - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoUltraFastToArray(): void
    {
        $dto = UltraFastDepartmentDto::fromArray($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Other Dtos - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoToArray(): void
    {
        if (!class_exists(CarapaceData::class)) {
            return; // Skip if not installed
        }
        $dto = CarapaceDepartmentData::from($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Our SimpleDto - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoComplexData(): void
    {
        DepartmentSimpleDto::fromArray($this->complexData);
    }

    /** Benchmark: Our SimpleDto with #[UltraFast] - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoUltraFastComplexData(): void
    {
        UltraFastDepartmentDto::fromArray($this->complexData);
    }

    /** Benchmark: Other Dtos - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoComplexData(): void
    {
        if (!class_exists(CarapaceData::class)) {
            return; // Skip if not installed
        }
        CarapaceDepartmentData::from($this->complexData);
    }
}

/**
 * Carapace DTO
 * Real implementation using Carapace's Data class
 */
if (class_exists(CarapaceData::class)) {
    class CarapaceDepartmentData extends CarapaceData
    {
        public function __construct(
            public string $name,
            public string $code,
            public float $budget,
            public int $employee_count,
            public string $manager_name,
            public ?string $location = null,
            public ?int $floor = null,
            public ?string $phone = null,
            public ?string $email = null,
            public ?string $established_date = null,
        ) {}
    }
}

/**
 * Plain PHP DTO with public properties
 */
class PlainDepartmentDto
{
    public string $name;
    public string $code;
    public float $budget;
    public int $employee_count;
    public string $manager_name;
}

/**
 * Plain PHP DTO with constructor
 */
class PlainDepartmentDtoWithConstructor
{
    public function __construct(
        public string $name,
        public string $code,
        public float $budget,
        public int $employee_count,
        public string $manager_name,
    ) {}
}

/**
 * SimpleDto with #[UltraFast] attribute
 * For maximum performance benchmarking
 */
#[UltraFast]
class UltraFastDepartmentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count,
        public readonly string $manager_name,
    ) {}
}
