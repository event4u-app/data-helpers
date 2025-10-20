<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use Tests\utils\DTOs\CompanyDto;
use Tests\utils\SimpleDTOs\CompanySimpleDto;
use Tests\utils\SimpleDTOs\DepartmentSimpleDto;
use Tests\utils\SimpleDTOs\ProjectSimpleDto;

/**
 * Benchmark helper class
 */
class Benchmark
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

    private static function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return number_format($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }

    public static function run(string $name, callable $callback, int $iterations = 1000): array
    {
        // Warmup
        for ($i = 0; 10 > $i; $i++) {
            $callback();
        }

        // Measure
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);
        $startTime = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }

        $endTime = hrtime(true);
        $memoryAfter = memory_get_usage(true);

        $totalTime = ($endTime - $startTime) / 1e9; // Convert to seconds
        $avgTime = $totalTime / $iterations;
        $memoryUsed = $memoryAfter - $memoryBefore;

        return [
            'name' => $name,
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time' => $avgTime,
            'memory_used' => $memoryUsed,
            'formatted_total' => self::formatTime($totalTime),
            'formatted_avg' => self::formatTime($avgTime),
            'formatted_memory' => self::formatMemory($memoryUsed),
        ];
    }

    public static function printResult(array $result): void
    {
        echo sprintf('  %s%s', $result['name'], PHP_EOL);
        echo sprintf('    Iterations:  %s%s', $result['iterations'], PHP_EOL);
        echo sprintf('    Total time:  %s%s', $result['formatted_total'], PHP_EOL);
        echo sprintf('    Avg time:    %s%s', $result['formatted_avg'], PHP_EOL);
        echo sprintf('    Memory:      %s%s', $result['formatted_memory'], PHP_EOL);
    }

    public static function compare(array $result1, array $result2): void
    {
        $timeDiff = (($result2['avg_time'] - $result1['avg_time']) / $result1['avg_time']) * 100;
        $memDiff = (($result2['memory_used'] - $result1['memory_used']) / max($result1['memory_used'], 1)) * 100;

        echo "\n  📊 Comparison:\n";
        if (0 < $timeDiff) {
            echo sprintf('    ⏱️  %s is ', $result2['name']) . number_format(abs($timeDiff), 1) . "% SLOWER\n";
        } else {
            echo sprintf('    ⏱️  %s is ', $result2['name']) . number_format(abs($timeDiff), 1) . "% FASTER\n";
        }

        if (0 < $memDiff) {
            echo sprintf('    💾 %s uses ', $result2['name']) . number_format(abs($memDiff), 1) . "% MORE memory\n";
        } else {
            echo sprintf('    💾 %s uses ', $result2['name']) . number_format(abs($memDiff), 1) . "% LESS memory\n";
        }
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DTO Benchmark: Traditional Mutable vs SimpleDTO Immutable    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

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

// ============================================================================
// Benchmark 1: DTO Creation + DataMapper
// ============================================================================
echo "🔥 Benchmark 1: DTO Creation with DataMapper\n";
echo str_repeat('─', 64) . "\n";

$iterations = 1000;

$result1 = Benchmark::run('Traditional Mutable DTO', function() use ($jsonFile, $mapping): void {
    $company = new CompanyDto();
    DataMapper::sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();
}, $iterations);

$result2 = Benchmark::run('SimpleDTO Immutable', function() use ($jsonFile, $mapping): void {
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
}, $iterations);

Benchmark::printResult($result1);
echo "\n";
Benchmark::printResult($result2);
Benchmark::compare($result1, $result2);

// ============================================================================
// Benchmark 2: JSON Serialization
// ============================================================================
echo "\n\n";
echo "🔥 Benchmark 2: JSON Serialization\n";
echo str_repeat('─', 64) . "\n";

// Prepare DTOs
$company1 = new CompanyDto();
$companyMutable = DataMapper::sourceFile($jsonFile)->target($company1)->template($mapping)->map()->getTarget();

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
$companyImmutable = CompanySimpleDto::fromArray($companyData);

$iterations = 10000;

$result3 = Benchmark::run('Traditional DTO (manual)', function() use ($companyMutable): void {
    // Traditional DTOs need manual serialization
    json_encode([
        'name' => $companyMutable->name,
        'email' => $companyMutable->email,
        'founded_year' => $companyMutable->founded_year,
        'departments' => array_map(fn($d): array => [
            'name' => $d->name,
            'code' => $d->code,
        ], $companyMutable->departments),
    ]);
}, $iterations);

$result4 = Benchmark::run('SimpleDTO (automatic)', function() use ($companyImmutable): void {
    // SimpleDTO has automatic JSON serialization
    json_encode($companyImmutable);
}, $iterations);

Benchmark::printResult($result3);
echo "\n";
Benchmark::printResult($result4);
Benchmark::compare($result3, $result4);

// ============================================================================
// Benchmark 3: Array Conversion
// ============================================================================
echo "\n\n";
echo "🔥 Benchmark 3: Array Conversion (toArray)\n";
echo str_repeat('─', 64) . "\n";

$iterations = 10000;

$result5 = Benchmark::run('Traditional DTO (manual)', function() use ($companyMutable): void {
    // Traditional DTOs need manual array conversion
    [
        'name' => $companyMutable->name,
        'email' => $companyMutable->email,
        'founded_year' => $companyMutable->founded_year,
        'departments' => array_map(fn($d): array => [
            'name' => $d->name,
            'code' => $d->code,
        ], $companyMutable->departments),
    ];
}, $iterations);

$result6 = Benchmark::run('SimpleDTO (automatic)', function() use ($companyImmutable): void {
    // SimpleDTO has automatic toArray()
    $companyImmutable->toArray();
}, $iterations);

Benchmark::printResult($result5);
echo "\n";
Benchmark::printResult($result6);
Benchmark::compare($result5, $result6);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Benchmark Complete                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

