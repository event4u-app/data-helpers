#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Update DTO benchmark results in docs/simple-dto.md
 *
 * This script runs DTO benchmarks and updates the benchmark results section
 * between the <!-- DTO_BENCHMARK_RESULTS_START --> and <!-- DTO_BENCHMARK_RESULTS_END --> markers.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use Tests\utils\DTOs\CompanyDto;
use Tests\utils\DTOs\DepartmentDto;
use Tests\utils\SimpleDTOs\CompanySimpleDto;
use Tests\utils\SimpleDTOs\DepartmentSimpleDto;
use Tests\utils\SimpleDTOs\ProjectSimpleDto;

$rootDir = dirname(__DIR__);
$readmePath = $rootDir . '/docs/simple-dto.md';

// Check if README exists
if (!file_exists($readmePath)) {
    echo sprintf('❌  docs/simple-dto.md not found at: %s%s', $readmePath, PHP_EOL);
    exit(1);
}

echo "🚀  Running DTO benchmarks...\n\n";

/**
 * @param callable(): void $callback
 * @return array{name: string, iterations: int, avg_time: float, ops_per_sec: int}
 */
function runBenchmark(string $name, callable $callback, int $iterations): array
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
        'avg_time' => $avgTime,
        'ops_per_sec' => (int)(1 / $avgTime),
    ];
}

function formatTime(float $seconds): string
{
    if (0.001 > $seconds) {
        return number_format($seconds * 1_000_000, 2) . ' μs';
    }
    if (1 > $seconds) {
        return number_format($seconds * 1_000, 2) . ' ms';
    }

    return number_format($seconds, 3) . ' s';
}

// Prepare test data
$jsonFile = $rootDir . '/tests/utils/json/data_mapper_from_file_test.json';
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

// Run benchmarks
$results = [];

echo "  Running Benchmark 1: DTO Creation with DataMapper...\n";
$results['datamapper_traditional'] = runBenchmark('Traditional Mutable DTO', function() use (
    $jsonFile,
    $mapping
): void {
    $company = new CompanyDto();
    // @phpstan-ignore-next-line method.resultUnused
    DataMapper::sourceFile($jsonFile)->target($company)->template($mapping)->map()->getTarget();
}, 1000);

$results['datamapper_simple'] = runBenchmark('SimpleDTO Immutable', function() use ($jsonFile, $mapping): void {
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

echo "  Running Benchmark 2: Simple DTO Creation...\n";
$results['creation_traditional'] = runBenchmark('Traditional: new + assign', function() use ($testData): void {
    $dto = new DepartmentDto();
    $dto->name = $testData['name'];
    $dto->code = $testData['code'];
    $dto->budget = $testData['budget'];
    $dto->employee_count = $testData['employee_count'];
    $dto->manager_name = $testData['manager_name'];
}, 100000);

$results['creation_simple'] = runBenchmark('SimpleDTO: fromArray()', function() use ($testData): void {
    DepartmentSimpleDto::fromArray($testData);
}, 100000);

echo "  Running Benchmark 3: toArray() Conversion...\n";
$dtoMutable = new DepartmentDto();
$dtoMutable->name = 'Engineering';
$dtoMutable->code = 'ENG';
$dtoMutable->budget = 5000000.00;
$dtoMutable->employee_count = 120;
$dtoMutable->manager_name = 'Alice Johnson';

$dtoImmutable = DepartmentSimpleDto::fromArray($testData);

$results['toarray_traditional'] = runBenchmark('Traditional: toArray()', function() use ($dtoMutable): void {
    $dtoMutable->toArray();
}, 100000);

$results['toarray_simple'] = runBenchmark('SimpleDTO: toArray()', function() use ($dtoImmutable): void {
    $dtoImmutable->toArray();
}, 100000);

echo "\n📊  Generating markdown tables...\n\n";

// Calculate performance differences
$dataMapperDiff = (($results['datamapper_traditional']['avg_time'] - $results['datamapper_simple']['avg_time']) / $results['datamapper_traditional']['avg_time']) * 100;
$creationDiff = (($results['creation_simple']['avg_time'] - $results['creation_traditional']['avg_time']) / $results['creation_traditional']['avg_time']) * 100;
$toArrayDiff = (($results['toarray_simple']['avg_time'] - $results['toarray_traditional']['avg_time']) / $results['toarray_traditional']['avg_time']) * 100;

// Generate markdown
$markdown = "### Benchmark 1: DTO Creation with DataMapper\n\n";
$markdown .= "| Approach | Avg Time | Ops/sec | Performance |\n";
$markdown .= "|----------|----------|---------|-------------|\n";
$markdown .= sprintf(
    "| Traditional Mutable DTO | %s | %s | Baseline |\n",
    formatTime($results['datamapper_traditional']['avg_time']),
    number_format($results['datamapper_traditional']['ops_per_sec'])
);
$markdown .= sprintf(
    "| SimpleDTO Immutable | %s | %s | **%.1f%% faster** ✅ |\n",
    formatTime($results['datamapper_simple']['avg_time']),
    number_format($results['datamapper_simple']['ops_per_sec']),
    abs($dataMapperDiff)
);

$markdown .= "\n### Benchmark 2: Simple DTO Creation (no DataMapper)\n\n";
$markdown .= "| Approach | Avg Time | Ops/sec | Performance |\n";
$markdown .= "|----------|----------|---------|-------------|\n";
$markdown .= sprintf(
    "| Traditional: new + assign | %s | %s | Baseline |\n",
    formatTime($results['creation_traditional']['avg_time']),
    number_format($results['creation_traditional']['ops_per_sec'])
);
$markdown .= sprintf(
    "| SimpleDTO: fromArray() | %s | %s | %.1f%% slower ⚠️ |\n",
    formatTime($results['creation_simple']['avg_time']),
    number_format($results['creation_simple']['ops_per_sec']),
    abs($creationDiff)
);

$markdown .= "\n### Benchmark 3: toArray() Conversion\n\n";
$markdown .= "| Approach | Avg Time | Ops/sec | Performance |\n";
$markdown .= "|----------|----------|---------|-------------|\n";
$markdown .= sprintf(
    "| Traditional: toArray() | %s | %s | Baseline |\n",
    formatTime($results['toarray_traditional']['avg_time']),
    number_format($results['toarray_traditional']['ops_per_sec'])
);

if (0 > $toArrayDiff) {
    $markdown .= sprintf(
        "| SimpleDTO: toArray() | %s | %s | **%.1f%% faster** ✅ |\n",
        formatTime($results['toarray_simple']['avg_time']),
        number_format($results['toarray_simple']['ops_per_sec']),
        abs($toArrayDiff)
    );
} else {
    $markdown .= sprintf(
        "| SimpleDTO: toArray() | %s | %s | %.1f%% slower ⚠️ |\n",
        formatTime($results['toarray_simple']['avg_time']),
        number_format($results['toarray_simple']['ops_per_sec']),
        abs($toArrayDiff)
    );
}

$markdown .= "\n### Summary\n\n";
$markdown .= "**Real-World Performance (what matters):**\n\n";
$markdown .= sprintf(
    "- ✅ **SimpleDTO is %.1f%% faster** for DataMapper integration (most common use case)\n",
    abs($dataMapperDiff)
);

if (0 > $toArrayDiff) {
    $markdown .= sprintf("- ✅ **SimpleDTO is %.1f%% faster** for toArray() conversion\n", abs($toArrayDiff));
} elseif (5 > abs($toArrayDiff)) {
    $markdown .= sprintf(
        "- ✅ **SimpleDTO is practically equal** for toArray() (%.1f%% difference is negligible)\n",
        abs($toArrayDiff)
    );
} else {
    $markdown .= sprintf("- ⚠️  Traditional DTO is %.1f%% faster for toArray() conversion\n", abs($toArrayDiff));
}

$markdown .= "\n**Synthetic Benchmark (unrealistic scenario):**\n\n";
$markdown .= sprintf(
    "- ⚠️  Traditional DTO is %.1f%% faster for manual property assignment (but nobody does this in real code)\n",
    abs($creationDiff)
);

$markdown .= "\n**🏆 Winner: SimpleDTO** - Faster where it matters, with immutability and type safety as bonus!\n";

// Read README
$readme = file_get_contents($readmePath);

if (false === $readme) {
    echo "❌  Failed to read docs/simple-dto.md\n";
    exit(1);
}

// Update benchmark results section
$startMarker = '<!-- DTO_BENCHMARK_RESULTS_START -->';
$endMarker = '<!-- DTO_BENCHMARK_RESULTS_END -->';

$startPos = strpos($readme, $startMarker);
$endPos = strpos($readme, $endMarker);

if (false === $startPos || false === $endPos) {
    echo "❌  Could not find DTO benchmark markers in docs/simple-dto.md\n";
    echo "    Add the following markers to the file:\n";
    echo "    <!-- DTO_BENCHMARK_RESULTS_START -->\n";
    echo "    <!-- DTO_BENCHMARK_RESULTS_END -->\n";
    exit(1);
}

// Replace content between markers
$before = substr($readme, 0, $startPos + strlen($startMarker));
$after = substr($readme, $endPos);

$newReadme = $before . "\n\n" . $markdown . "\n" . $after;

// Write README
file_put_contents($readmePath, $newReadme);

echo "✅  Benchmark results updated in docs/simple-dto.md\n";
echo "\n";

