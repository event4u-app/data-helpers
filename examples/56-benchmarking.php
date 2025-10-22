<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

echo "=== SimpleDTO Benchmarking ===\n\n";

// Example 1: Basic Benchmarking
echo "1. Basic Benchmarking\n";
echo "--------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

$data = ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'];

// Benchmark instantiation
$results = UserDTO::benchmarkInstantiation($data, 1000);
echo "Instantiation (1000 iterations):\n";
echo "  Duration: " . number_format($results['duration'] * 1000, 2) . " ms\n";
echo "  Memory: " . number_format($results['memory'] / 1024, 2) . " KB\n";
echo "  Throughput: " . number_format($results['throughput']) . " ops/sec\n";
echo "  Avg Duration: " . number_format($results['avgDuration'] * 1000000, 2) . " μs\n";
echo "  Avg Memory: " . number_format($results['avgMemory']) . " bytes\n\n";

// Example 2: toArray Benchmarking
echo "2. toArray Benchmarking\n";
echo "----------------------\n";

$results = UserDTO::benchmarkToArray($data, 1000);
echo "toArray (1000 iterations):\n";
echo "  Duration: " . number_format($results['duration'] * 1000, 2) . " ms\n";
echo "  Memory: " . number_format($results['memory'] / 1024, 2) . " KB\n";
echo "  Throughput: " . number_format($results['throughput']) . " ops/sec\n";
echo "  Avg Duration: " . number_format($results['avgDuration'] * 1000000, 2) . " μs\n\n";

// Example 3: JSON Serialization Benchmarking
echo "3. JSON Serialization Benchmarking\n";
echo "---------------------------------\n";

$results = UserDTO::benchmarkJsonSerialize($data, 1000);
echo "JSON Serialization (1000 iterations):\n";
echo "  Duration: " . number_format($results['duration'] * 1000, 2) . " ms\n";
echo "  Memory: " . number_format($results['memory'] / 1024, 2) . " KB\n";
echo "  Throughput: " . number_format($results['throughput']) . " ops/sec\n";
echo "  Avg Duration: " . number_format($results['avgDuration'] * 1000000, 2) . " μs\n\n";

// Example 4: Comprehensive Benchmark Suite
echo "4. Comprehensive Benchmark Suite\n";
echo "--------------------------------\n";

$results = UserDTO::runBenchmarkSuite($data, 1000);
echo "Benchmark Suite Results:\n";
foreach ($results as $operation => $metrics) {
    echo "  " . ucfirst($operation) . ":\n";
    echo "    Duration: " . number_format($metrics['duration'] * 1000, 2) . " ms\n";
    echo "    Throughput: " . number_format($metrics['throughput']) . " ops/sec\n";
}
echo "\n";

// Example 5: Cache Performance Comparison
echo "5. Cache Performance Comparison\n";
echo "------------------------------\n";

$comparison = UserDTO::compareCachePerformance($data, 1000);
echo "With Cache:\n";
echo "  Duration: " . number_format($comparison['withCache']['duration'] * 1000, 2) . " ms\n";
echo "  Throughput: " . number_format($comparison['withCache']['throughput']) . " ops/sec\n";
echo "\nWithout Cache:\n";
echo "  Duration: " . number_format($comparison['withoutCache']['duration'] * 1000, 2) . " ms\n";
echo "  Throughput: " . number_format($comparison['withoutCache']['throughput']) . " ops/sec\n";
echo "\nSpeedup:\n";
echo "  Duration: " . number_format($comparison['speedup']['duration'], 2) . "x\n";
echo "  Memory: " . number_format($comparison['speedup']['memory'], 2) . "x\n\n";

// Example 6: Complex DTO Benchmarking
echo "6. Complex DTO Benchmarking\n";
echo "--------------------------\n";

class ComplexDTO extends SimpleDTO
{
    /** @param array<mixed> $tags */
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly int $age,
        public readonly DateTimeImmutable $createdAt,
        public readonly array $tags,
        public readonly ?string $description = null,
    ) {}

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
        ];
    }

    #[Computed]
    public function displayName(): string
    {
        return strtoupper($this->name);
    }
}

$complexData = [
    'user_name' => 'Jane Doe',
    'age' => 25,
    'createdAt' => '2024-01-01 12:00:00',
    'tags' => ['php', 'laravel', 'symfony'],
    'description' => 'A complex DTO example',
];

$results = ComplexDTO::runBenchmarkSuite($complexData, 1000);
echo "Complex DTO Results:\n";
foreach ($results as $operation => $metrics) {
    echo "  " . ucfirst($operation) . ":\n";
    echo "    Duration: " . number_format($metrics['duration'] * 1000, 2) . " ms\n";
    echo "    Throughput: " . number_format($metrics['throughput']) . " ops/sec\n";
}
echo "\n";

// Example 7: Benchmark Report Generation
echo "7. Benchmark Report Generation\n";
echo "-----------------------------\n";

$results = UserDTO::runBenchmarkSuite($data, 1000);
/** @phpstan-ignore-next-line unknown */
$report = UserDTO::generateBenchmarkReport($results);
echo $report;

// Example 8: Multiple DTOs Comparison
echo "8. Multiple DTOs Comparison\n";
echo "--------------------------\n";

class SimpleDTO1 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
    ) {}
}

class SimpleDTO2 extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

class SimpleDTO3 extends SimpleDTO
{
    /** @param array<mixed> $tags */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
        public readonly array $tags,
        public readonly ?string $description = null,
    ) {}
}

SimpleDTO1::runBenchmarkSuite(['name' => 'John'], 1000);
SimpleDTO2::runBenchmarkSuite(['name' => 'John', 'age' => 30, 'email' => 'john@example.com'], 1000);
SimpleDTO3::runBenchmarkSuite([
    'name' => 'John',
    'age' => 30,
    'email' => 'john@example.com',
    'tags' => ['php', 'laravel'],
    'description' => 'Test',
], 1000);

$allResults = SimpleDTO1::getBenchmarkResults();
echo "Comparison of DTOs:\n";
foreach ($allResults as $class => $results) {
    $className = basename(str_replace('\\', '/', (string)$class));
    echo "\n{$className}:\n";
    echo "  Instantiation: " . number_format($results['instantiation']['throughput']) . " ops/sec\n";
    echo "  toArray: " . number_format($results['toArray']['throughput']) . " ops/sec\n";
    echo "  JSON: " . number_format($results['jsonSerialize']['throughput']) . " ops/sec\n";
}
echo "\n";

// Example 9: Validation Benchmarking
echo "9. Validation Benchmarking\n";
echo "-------------------------\n";

$results = UserDTO::benchmarkValidation($data, 1000);
echo "Validation (1000 iterations):\n";
echo "  Duration: " . number_format($results['duration'] * 1000, 2) . " ms\n";
echo "  Throughput: " . number_format($results['throughput']) . " ops/sec\n\n";

// Example 10: Real-World Scenario
echo "10. Real-World Scenario\n";
echo "----------------------\n";

// Simulate API response processing
$start = microtime(true);
$users = [];
for ($i = 0; 10000 > $i; $i++) {
    $users[] = UserDTO::fromArray([
        'name' => 'User ' . $i,
        'age' => 20 + ($i % 50),
        'email' => sprintf('user%d@example.com', $i),
    ]);
}
$duration = microtime(true) - $start;

echo "Processed 10,000 API responses:\n";
echo "  Duration: " . number_format($duration * 1000, 2) . " ms\n";
echo "  Throughput: " . number_format(10000 / $duration) . " users/sec\n";
echo "  Avg per user: " . number_format(($duration / 10000) * 1000, 2) . " ms\n\n";

// Clear benchmark results
UserDTO::clearBenchmarkResults();

echo "=== Benchmarking Complete ===\n";
