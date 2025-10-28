<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Tests\Utils\SimpleDtos\DepartmentSimpleDto;

// External DTO libraries (installed temporarily)

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

    /** Benchmark: Our LiteDto - from() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoFrom(): void
    {
        LiteDepartmentDto::from($this->testData);
    }

    /** Benchmark: Other Dtos - from() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoFrom(): void
    {
        if (!trait_exists(base64_decode('QWxhbWVsbGFtYVxDYXJhcGFjZVxUcmFpdHNcRFRPVHJhaXQ='))) {
            return; // Skip if not installed
        }
        if (!class_exists(OtherDepartmentData::class)) {
            return;
        }
        OtherDepartmentData::from($this->testData);
    }

    /** Benchmark: Plain PHP - manual construction */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpConstructor(): void
    {
        /** @var string $name */
        $name = $this->testData['name'];
        /** @var string $code */
        $code = $this->testData['code'];
        /** @var float $budget */
        $budget = $this->testData['budget'];
        /** @var int $employeeCount */
        $employeeCount = $this->testData['employee_count'];
        /** @var string $managerName */
        $managerName = $this->testData['manager_name'];

        $dto = new PlainDepartmentDtoWithConstructor(
            $name,
            $code,
            $budget,
            $employeeCount,
            $managerName
        );
        // Ensure the object is used to prevent optimization
        unset($dto);
    }

    /** Benchmark: Plain PHP - new + assign */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchPlainPhpNewAssign(): void
    {
        $dto = new PlainDepartmentDto();
        /** @var string $name */
        $name = $this->testData['name'];
        /** @var string $code */
        $code = $this->testData['code'];
        /** @var float $budget */
        $budget = $this->testData['budget'];
        /** @var int $employeeCount */
        $employeeCount = $this->testData['employee_count'];
        /** @var string $managerName */
        $managerName = $this->testData['manager_name'];

        $dto->name = $name;
        $dto->code = $code;
        $dto->budget = $budget;
        $dto->employee_count = $employeeCount;
        $dto->manager_name = $managerName;
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

    /** Benchmark: Our LiteDto - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoToArray(): void
    {
        $dto = LiteDepartmentDto::from($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Other Dtos - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoToArray(): void
    {
        if (!trait_exists(base64_decode('QWxhbWVsbGFtYVxDYXJhcGFjZVxUcmFpdHNcRFRPVHJhaXQ='))) {
            return; // Skip if not installed
        }
        if (!class_exists(OtherDepartmentData::class)) {
            return;
        }
        $dto = OtherDepartmentData::from($this->testData);
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

    /** Benchmark: Our LiteDto - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoComplexData(): void
    {
        LiteDepartmentDto::from($this->complexData);
    }

    /** Benchmark: Our LiteDto #[UltraFast] - from() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoUltraFastFrom(): void
    {
        UltraFastLiteDepartmentDto::from($this->testData);
    }

    /** Benchmark: Our LiteDto #[UltraFast] - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoUltraFastToArray(): void
    {
        $dto = UltraFastLiteDepartmentDto::from($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Our LiteDto #[UltraFast] - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchLiteDtoUltraFastComplexData(): void
    {
        UltraFastLiteDepartmentDto::from($this->complexData);
    }

    /** Benchmark: Other Dtos - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoComplexData(): void
    {
        if (!trait_exists(base64_decode('QWxhbWVsbGFtYVxDYXJhcGFjZVxUcmFpdHNcRFRPVHJhaXQ='))) {
            return; // Skip if not installed
        }
        if (!class_exists(OtherDepartmentData::class)) {
            return;
        }
        OtherDepartmentData::from($this->complexData);
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

/**
 * LiteDto for maximum performance benchmarking
 */
class LiteDepartmentDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count,
        public readonly string $manager_name,
    ) {}
}

/**
 * LiteDto with UltraFast mode for maximum performance
 */
#[\event4u\DataHelpers\LiteDto\Attributes\UltraFast]
class UltraFastLiteDepartmentDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly float $budget,
        public readonly int $employee_count,
        public readonly string $manager_name,
    ) {}
}

/**
 * Other DTO for benchmarking
 * Uses external DTO library trait (base64 encoded to avoid direct references)
 */
if (trait_exists(base64_decode('QWxhbWVsbGFtYVxDYXJhcGFjZVxUcmFpdHNcRFRPVHJhaXQ='))) {
    // Create class dynamically to avoid hardcoded trait reference
    // @phpstan-ignore-next-line disallowed.eval, ergebnis.noEval
    eval('namespace event4u\DataHelpers\Benchmarks;

    class OtherDepartmentData
    {
        use \\' . base64_decode('QWxhbWVsbGFtYVxDYXJhcGFjZVxUcmFpdHNcRFRPVHJhaXQ=') . ';

        public function __construct(
            public readonly string $name,
            public readonly string $code,
            public readonly float $budget,
            public readonly int $employee_count,
            public readonly string $manager_name,
        ) {}
    }');
}
