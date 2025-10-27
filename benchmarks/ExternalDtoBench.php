<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Tests\Utils\SimpleDtos\DepartmentSimpleDto;

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

    /** Benchmark: Other DTO library - manual construction */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoLibraryFrom(): void
    {
        // Simulating other DTO library behavior (similar to Spatie Laravel Data)
        new OtherDtoLibraryDepartment(
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

    /** Benchmark: Plain PHP - constructor */
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

    /** Benchmark: Our SimpleDto - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoToArray(): void
    {
        $dto = DepartmentSimpleDto::fromArray($this->testData);
        $dto->toArray();
    }

    /** Benchmark: Other DTO library - toArray() */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoLibraryToArray(): void
    {
        $dto = new OtherDtoLibraryDepartment(
            $this->testData['name'],
            $this->testData['code'],
            $this->testData['budget'],
            $this->testData['employee_count'],
            $this->testData['manager_name']
        );
        // Simulating toArray() behavior
        [
            'name' => $dto->name,
            'code' => $dto->code,
            'budget' => $dto->budget,
            'employee_count' => $dto->employee_count,
            'manager_name' => $dto->manager_name,
        ];
    }

    /** Benchmark: Our SimpleDto - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchSimpleDtoComplexData(): void
    {
        DepartmentSimpleDto::fromArray($this->complexData);
    }

    /** Benchmark: Other DTO library - Complex Data */
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchOtherDtoLibraryComplexData(): void
    {
        new OtherDtoLibraryDepartment(
            $this->complexData['name'],
            $this->complexData['code'],
            $this->complexData['budget'],
            $this->complexData['employee_count'],
            $this->complexData['manager_name'],
            $this->complexData['location'] ?? null,
            $this->complexData['floor'] ?? null,
            $this->complexData['phone'] ?? null,
            $this->complexData['email'] ?? null,
            $this->complexData['established_date'] ?? null
        );
    }
}

/**
 * Simulated other DTO library (similar to Spatie Laravel Data)
 * This represents typical behavior of other DTO libraries
 */
class OtherDtoLibraryDepartment
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
