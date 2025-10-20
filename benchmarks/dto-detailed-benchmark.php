<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use Tests\utils\DTOs\DepartmentDto;
use Tests\utils\SimpleDTOs\DepartmentSimpleDto;

/**
 * Detailed benchmark for specific operations
 */
class DetailedBenchmark
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

    public static function run(string $name, callable $callback, int $iterations = 10000): array
    {
        // Warmup
        for ($i = 0; 100 > $i; $i++) {
            $callback();
        }

        // Measure
        gc_collect_cycles();
        $startTime = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
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
            'formatted_total' => self::formatTime($totalTime),
            'formatted_avg' => self::formatTime($avgTime),
            'ops_per_sec' => number_format(1 / $avgTime, 0),
        ];
    }

    public static function printResults(array $results): void
    {
        $maxNameLen = max(array_map(fn(array $r): int => strlen((string)$r['name']), $results));

        echo "\n";
        printf("  %-{$maxNameLen}s  %12s  %12s  %15s\n", 'Operation', 'Total', 'Avg', 'Ops/sec');
        echo "  " . str_repeat('─', $maxNameLen + 45) . "\n";

        foreach ($results as $result) {
            printf(
                "  %-{$maxNameLen}s  %12s  %12s  %15s\n",
                $result['name'],
                $result['formatted_total'],
                $result['formatted_avg'],
                $result['ops_per_sec']
            );
        }
    }

    public static function compareTwo(array $result1, array $result2): void
    {
        $diff = (($result2['avg_time'] - $result1['avg_time']) / $result1['avg_time']) * 100;

        echo "\n  📊 ";
        if (0 < $diff) {
            echo $result2['name'] . ' is ' . number_format(abs($diff), 1) . sprintf(
                '%% SLOWER than %s%s',
                $result1['name'],
                PHP_EOL
            );
        } else {
            echo $result2['name'] . ' is ' . number_format(abs($diff), 1) . sprintf(
                '%% FASTER than %s%s',
                $result1['name'],
                PHP_EOL
            );
        }
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Detailed DTO Benchmark                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// ============================================================================
// Benchmark 1: Simple DTO Creation (no DataMapper)
// ============================================================================
echo "\n🔥 Benchmark 1: Simple DTO Creation (no DataMapper)\n";
echo str_repeat('─', 64) . "\n";

$testData = [
    'name' => 'Engineering',
    'code' => 'ENG',
    'budget' => 5000000.00,
    'employee_count' => 120,
    'manager_name' => 'Alice Johnson',
];

$iterations = 100000;

$results = [];

$results[] = DetailedBenchmark::run('Traditional: new + assign', function() use ($testData): void {
    $dto = new DepartmentDto();
    $dto->name = $testData['name'];
    $dto->code = $testData['code'];
    $dto->budget = $testData['budget'];
    $dto->employee_count = $testData['employee_count'];
    $dto->manager_name = $testData['manager_name'];
}, $iterations);

$results[] = DetailedBenchmark::run('SimpleDTO: fromArray()', function() use ($testData): void {
    DepartmentSimpleDto::fromArray($testData);
}, $iterations);

DetailedBenchmark::printResults($results);
DetailedBenchmark::compareTwo($results[0], $results[1]);

// ============================================================================
// Benchmark 2: Property Access
// ============================================================================
echo "\n\n🔥 Benchmark 2: Property Access (read)\n";
echo str_repeat('─', 64) . "\n";

$dtoMutable = new DepartmentDto();
$dtoMutable->name = 'Engineering';
$dtoMutable->code = 'ENG';
$dtoMutable->budget = 5000000.00;

$dtoImmutable = DepartmentSimpleDto::fromArray($testData);

$iterations = 1000000;

$results = [];

$results[] = DetailedBenchmark::run('Traditional: read property', function() use ($dtoMutable): void {
    $name = $dtoMutable->name;
    $code = $dtoMutable->code;
    $budget = $dtoMutable->budget;
}, $iterations);

$results[] = DetailedBenchmark::run('SimpleDTO: read property', function() use ($dtoImmutable): void {
    $name = $dtoImmutable->name;
    $code = $dtoImmutable->code;
    $budget = $dtoImmutable->budget;
}, $iterations);

DetailedBenchmark::printResults($results);
DetailedBenchmark::compareTwo($results[0], $results[1]);

// ============================================================================
// Benchmark 3: toArray() Performance
// ============================================================================
echo "\n\n🔥 Benchmark 3: toArray() Performance\n";
echo str_repeat('─', 64) . "\n";

$iterations = 100000;

$results = [];

$results[] = DetailedBenchmark::run('Traditional: manual array', function() use ($dtoMutable): void {
    [
        'name' => $dtoMutable->name,
        'code' => $dtoMutable->code,
        'budget' => $dtoMutable->budget,
        'employee_count' => $dtoMutable->employee_count,
        'manager_name' => $dtoMutable->manager_name,
    ];
}, $iterations);

$results[] = DetailedBenchmark::run('SimpleDTO: toArray()', function() use ($dtoImmutable): void {
    $dtoImmutable->toArray();
}, $iterations);

DetailedBenchmark::printResults($results);
DetailedBenchmark::compareTwo($results[0], $results[1]);

// ============================================================================
// Benchmark 4: JSON Serialization (simple)
// ============================================================================
echo "\n\n🔥 Benchmark 4: JSON Serialization (simple DTO)\n";
echo str_repeat('─', 64) . "\n";

$iterations = 100000;

$results = [];

$results[] = DetailedBenchmark::run('Traditional: manual json', function() use ($dtoMutable): void {
    json_encode([
        'name' => $dtoMutable->name,
        'code' => $dtoMutable->code,
        'budget' => $dtoMutable->budget,
        'employee_count' => $dtoMutable->employee_count,
        'manager_name' => $dtoMutable->manager_name,
    ]);
}, $iterations);

$results[] = DetailedBenchmark::run('SimpleDTO: json_encode()', function() use ($dtoImmutable): void {
    json_encode($dtoImmutable);
}, $iterations);

DetailedBenchmark::printResults($results);
DetailedBenchmark::compareTwo($results[0], $results[1]);

// ============================================================================
// Benchmark 5: Batch Creation (100 DTOs)
// ============================================================================
echo "\n\n🔥 Benchmark 5: Batch Creation (100 DTOs)\n";
echo str_repeat('─', 64) . "\n";

$batchData = array_map(fn($i): array => [
    'name' => 'Department ' . $i,
    'code' => 'DEPT' . $i,
    'budget' => 1000000.00 * $i,
    'employee_count' => 10 * $i,
    'manager_name' => 'Manager ' . $i,
], range(1, 100));

$iterations = 1000;

$results = [];

$results[] = DetailedBenchmark::run('Traditional: 100 DTOs', function() use ($batchData): void {
    $dtos = [];
    foreach ($batchData as $data) {
        $dto = new DepartmentDto();
        $dto->name = $data['name'];
        $dto->code = $data['code'];
        $dto->budget = $data['budget'];
        $dto->employee_count = $data['employee_count'];
        $dto->manager_name = $data['manager_name'];
        $dtos[] = $dto;
    }
}, $iterations);

$results[] = DetailedBenchmark::run('SimpleDTO: 100 DTOs', function() use ($batchData): void {
    $dtos = array_map(
        fn(array $data): DepartmentSimpleDto => DepartmentSimpleDto::fromArray($data),
        $batchData
    );
}, $iterations);

DetailedBenchmark::printResults($results);
DetailedBenchmark::compareTwo($results[0], $results[1]);

// ============================================================================
// Summary
// ============================================================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Summary                                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  ✅ SimpleDTO Advantages:\n";
echo "     • Faster DTO creation (fromArray vs manual assignment)\n";
echo "     • Built-in toArray() with minimal overhead\n";
echo "     • Immutability guarantees\n";
echo "     • Type safety with readonly properties\n";
echo "\n";
echo "  ⚠️  SimpleDTO Considerations:\n";
echo "     • JSON serialization has overhead (JsonSerializable interface)\n";
echo "     • Slightly slower for very simple manual JSON encoding\n";
echo "\n";
echo "  💡 Recommendation:\n";
echo "     Use SimpleDTO for:\n";
echo "     • APIs with frequent array/JSON conversions\n";
echo "     • Data that should be immutable\n";
echo "     • Complex nested structures\n";
echo "\n";
echo "     Use Traditional DTO for:\n";
echo "     • Performance-critical hot paths\n";
echo "     • Simple data structures with minimal conversions\n";
echo "     • When mutability is required\n";
echo "\n";

