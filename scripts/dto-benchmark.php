#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * DTO Benchmark Script
 *
 * Runs benchmarks comparing Traditional Mutable DTOs vs SimpleDTO Immutable DTOs
 * and displays results in a formatted table.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use Tests\utils\DTOs\CompanyDto;
use Tests\utils\DTOs\DepartmentDto;
use Tests\utils\SimpleDTOs\CompanySimpleDto;
use Tests\utils\SimpleDTOs\DepartmentSimpleDto;
use Tests\utils\SimpleDTOs\ProjectSimpleDto;

class DtoBenchmarkRunner
{
    private static function formatTime(float $seconds): string
    {
        if (0.001 > $seconds) {
            return number_format($seconds * 1_000_000, 2) . ' μs';
        }
        if (1 > $seconds) {
            return number_format($seconds * 1_000, 2) . ' ms';
        }

        return number_format($seconds, 3) . ' s';
    }

    private static function formatPercent(float $percent): string
    {
        $sign = 0 < $percent ? '+' : '';

        return $sign . number_format($percent, 1) . '%';
    }

    /**
     * @param callable(): void $callback
     * @return array{name: string, iterations: int, total_time: float, avg_time: float, ops_per_sec: int}
     */
    public static function run(string $name, callable $callback, int $iterations = 1000): array
    {
        // Warmup
        for ($i = 0; 10 > $i; $i++) {
            $callback();
        }

        // Measure
        gc_collect_cycles();
        $startTime = hrtime(true);

        for ($i = 0; $iterations > $i; $i++) {
            $callback();
        }

        $endTime = hrtime(true);

        $totalTime = ($endTime - $startTime) / 1e9;
        $avgTime = $totalTime / $iterations;

        return [
            'name' => $name,
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time' => $avgTime,
            'ops_per_sec' => (int)(1 / $avgTime),
        ];
    }

    /** @param array<int, array{name: string, iterations: int, total_time: float, avg_time: float, ops_per_sec: int}> $benchmarks */
    public static function printTable(array $benchmarks): void
    {
        if ([] === $benchmarks) {
            return;
        }

        $maxNameLen = max(array_map(fn(array $b): int => strlen($b['name']), $benchmarks));
        $maxNameLen = max($maxNameLen, 30);

        echo "\n";
        printf(
            "  %-{$maxNameLen}s  %12s  %12s  %15s  %10s\n",
            'Benchmark',
            'Total',
            'Avg',
            'Ops/sec',
            'vs Baseline'
        );
        echo '  ' . str_repeat('─', $maxNameLen + 65) . "\n";

        $baseline = null;
        foreach ($benchmarks as $result) {
            if (null === $baseline) {
                $baseline = $result['avg_time'];
            }

            $diff = (($result['avg_time'] - $baseline) / $baseline) * 100;
            $diffStr = 0.0 === $diff ? '-' : self::formatPercent($diff);

            printf(
                "  %-{$maxNameLen}s  %12s  %12s  %15s  %10s\n",
                $result['name'],
                self::formatTime($result['total_time']),
                self::formatTime($result['avg_time']),
                number_format($result['ops_per_sec']),
                $diffStr
            );
        }

        echo "\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DTO Benchmark: Traditional Mutable vs SimpleDTO Immutable    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Prepare test data
$jsonFile = __DIR__ . '/../tests/utils/json/data_mapper_from_file_test.json';
$mapping = [
    'name' => '{{ company.name }}',
    'registration_number' => '{{ company.registration_number }}',
    'email' => '{{ company.email }}',
    'phone' => '{{ company.phone }}',
    'address' => '{{ company.address }}',
    'city' => '{{ company.city }}',
    'country' => '{{ company.country }}',
    'founded_year' => '{{ company.founded_year }}',
    'employee_count' => '{{ company.employee_count }}',
    'annual_revenue' => '{{ company.annual_revenue }}',
    'is_active' => '{{ company.is_active }}',
    'departments' => [
        '*' => [
            'name' => '{{ company.departments.*.name }}',
            'code' => '{{ company.departments.*.code }}',
            'budget' => '{{ company.departments.*.budget }}',
            'employee_count' => '{{ company.departments.*.employee_count }}',
            'manager_name' => '{{ company.departments.*.manager_name }}',
        ],
    ],
    'projects' => [
        '*' => [
            'name' => '{{ company.projects.*.name }}',
            'code' => '{{ company.projects.*.code }}',
            'budget' => '{{ company.projects.*.budget }}',
            'start_date' => '{{ company.projects.*.start_date }}',
            'end_date' => '{{ company.projects.*.end_date }}',
            'status' => '{{ company.projects.*.status }}',
        ],
    ],
];

$testData = [
    'name' => 'Engineering',
    'code' => 'ENG',
    'budget' => 5000000.00,
    'employee_count' => 120,
    'manager_name' => 'Alice Johnson',
];

// ============================================================================
// Benchmark 1: DTO Creation with DataMapper
// ============================================================================
echo "\n🔥 Benchmark 1: DTO Creation with DataMapper (1,000 iterations)\n";
echo str_repeat('─', 64) . "\n";

$benchmarks = [];

$benchmarks[] = DtoBenchmarkRunner::run('Traditional Mutable DTO', function() use ($jsonFile, $mapping): void {
    $company = new CompanyDto();
    /** @phpstan-ignore-next-line unknown */
    DataMapper::sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();
}, 1000);

$benchmarks[] = DtoBenchmarkRunner::run('SimpleDTO Immutable', function() use ($jsonFile, $mapping): void {
    $mappedArray = DataMapper::sourceFile($jsonFile)->target([])->template($mapping)->map()->toArray();

    /** @var array<int, array<string, mixed>> $departmentsData */
    $departmentsData = $mappedArray['departments'];
    $departments = array_map(
        fn(array $dept): DepartmentSimpleDto => DepartmentSimpleDto::fromArray($dept),
        $departmentsData
    );

    /** @var array<int, array<string, mixed>> $projectsData */
    $projectsData = $mappedArray['projects'];
    $projects = array_map(
        fn(array $proj): ProjectSimpleDto => ProjectSimpleDto::fromArray($proj),
        $projectsData
    );

    /** @var array<string, mixed> $companyData */
    $companyData = [
        ...$mappedArray,
        'departments' => $departments,
        'projects' => $projects,
    ];
    CompanySimpleDto::fromArray($companyData);
}, 1000);

DtoBenchmarkRunner::printTable($benchmarks);

// ============================================================================
// Benchmark 2: Simple DTO Creation (no DataMapper)
// ============================================================================
echo "🔥 Benchmark 2: Simple DTO Creation (100,000 iterations)\n";
echo str_repeat('─', 64) . "\n";

$benchmarks = [];

$benchmarks[] = DtoBenchmarkRunner::run('Traditional: new + assign', function() use ($testData): void {
    $dto = new DepartmentDto();
    $dto->name = $testData['name'];
    $dto->code = $testData['code'];
    $dto->budget = $testData['budget'];
    $dto->employee_count = $testData['employee_count'];
    $dto->manager_name = $testData['manager_name'];
}, 100000);

$benchmarks[] = DtoBenchmarkRunner::run('SimpleDTO: fromArray()', function() use ($testData): void {
    DepartmentSimpleDto::fromArray($testData);
}, 100000);

DtoBenchmarkRunner::printTable($benchmarks);

// ============================================================================
// Benchmark 3: toArray() Conversion
// ============================================================================
echo "🔥 Benchmark 3: toArray() Conversion (100,000 iterations)\n";
echo str_repeat('─', 64) . "\n";

$dtoMutable = new DepartmentDto();
$dtoMutable->name = 'Engineering';
$dtoMutable->code = 'ENG';
$dtoMutable->budget = 5000000.00;
$dtoMutable->employee_count = 120;
$dtoMutable->manager_name = 'Alice Johnson';

$dtoImmutable = DepartmentSimpleDto::fromArray($testData);

$benchmarks = [];

$benchmarks[] = DtoBenchmarkRunner::run('Traditional: toArray()', function() use ($dtoMutable): void {
    $dtoMutable->toArray();
}, 100000);

$benchmarks[] = DtoBenchmarkRunner::run('SimpleDTO: toArray()', function() use ($dtoImmutable): void {
    $dtoImmutable->toArray();
}, 100000);

DtoBenchmarkRunner::printTable($benchmarks);

// ============================================================================
// Summary
// ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Summary                                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  ✅ SimpleDTO is FASTER for DataMapper integration\n";
echo "  ⚠️  Traditional DTO is FASTER for simple creation without DataMapper\n";
echo "  💡 Use SimpleDTO for APIs, immutability, and type safety\n";
echo "  💡 Use Traditional DTO for performance-critical hot paths\n";
echo "\n";
echo "  Run 'composer bench:dto:readme' to update docs/simple-dto.md\n";
echo "\n";
