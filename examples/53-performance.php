<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

echo "=== SimpleDTO Performance Optimization ===\n\n";

// Example 1: Cache Statistics
echo "1. Cache Statistics\n";
echo "-------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

// Warm up cache
UserDTO::warmUpCache();

$stats = UserDTO::getPerformanceCacheStats();
echo sprintf('Constructor Params Cached: %d%s', $stats['constructorParams'], PHP_EOL);
echo sprintf('Property Metadata Cached: %d%s', $stats['propertyMetadata'], PHP_EOL);
echo sprintf('Attribute Metadata Cached: %d%s', $stats['attributeMetadata'], PHP_EOL);
echo "Total Memory Used: " . number_format($stats['totalMemory']) . " bytes\n\n";

// Example 2: Performance Comparison
echo "2. Performance Comparison (with vs without cache)\n";
echo "------------------------------------------------\n";

$data = ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'];

// With cache
UserDTO::warmUpCache();
$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    UserDTO::fromArray($data);
}
$withCache = microtime(true) - $start;

// Without cache
UserDTO::clearPerformanceCache();
$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    UserDTO::fromArray($data);
}
$withoutCache = microtime(true) - $start;

echo "1000 instances with cache: " . number_format($withCache * 1000, 2) . " ms\n";
echo "1000 instances without cache: " . number_format($withoutCache * 1000, 2) . " ms\n";
echo "Speedup: " . number_format($withoutCache / $withCache, 2) . "x\n\n";

// Example 3: Memory Efficiency
echo "3. Memory Efficiency\n";
echo "-------------------\n";

UserDTO::warmUpCache();

$memoryBefore = memory_get_usage();

$instances = [];
for ($i = 0; 1000 > $i; $i++) {
    $instances[] = UserDTO::fromArray($data);
}

$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;

echo "Memory for 1000 instances: " . number_format($memoryUsed / 1024, 2) . " KB\n";
echo "Memory per instance: " . number_format($memoryUsed / 1000) . " bytes\n\n";

// Example 4: toArray Performance
echo "4. toArray Performance\n";
echo "---------------------\n";

$user = UserDTO::fromArray($data);

$start = microtime(true);
for ($i = 0; 10000 > $i; $i++) {
    $user->toArray();
}
$duration = microtime(true) - $start;

echo "10000 toArray() calls: " . number_format($duration * 1000, 2) . " ms\n";
echo "Average per call: " . number_format(($duration / 10000) * 1000000, 2) . " μs\n\n";

// Example 5: Complex DTO Performance
echo "5. Complex DTO Performance\n";
echo "-------------------------\n";

class ComplexUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
        public readonly string $email = '',
        public readonly array $tags = [],
        public readonly array $metadata = [],
    ) {}

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'json',
        ];
    }
}

// Warm up
ComplexUserDTO::warmUpCache();

$complexData = [
    'name' => 'Jane Doe',
    'age' => 25,
    'email' => 'jane@example.com',
    'tags' => ['developer', 'php', 'laravel'],
    'metadata' => ['role' => 'admin', 'department' => 'IT'],
];

$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    ComplexUserDTO::fromArray($complexData);
}
$duration = microtime(true) - $start;

echo "1000 complex DTO instances: " . number_format($duration * 1000, 2) . " ms\n";
echo "Average per instance: " . number_format(($duration / 1000) * 1000, 2) . " ms\n\n";

// Example 6: Cache Clearing
echo "6. Cache Management\n";
echo "------------------\n";

$statsBefore = UserDTO::getPerformanceCacheStats();
echo "Before clearing:\n";
echo sprintf('  Constructor Params: %d%s', $statsBefore['constructorParams'], PHP_EOL);
echo sprintf('  Property Metadata: %d%s', $statsBefore['propertyMetadata'], PHP_EOL);

UserDTO::clearPerformanceCache();

$statsAfter = UserDTO::getPerformanceCacheStats();
echo "After clearing:\n";
echo sprintf('  Constructor Params: %d%s', $statsAfter['constructorParams'], PHP_EOL);
echo "  Property Metadata: {$statsAfter['propertyMetadata']}\n\n";

// Example 7: Batch Processing Performance
echo "7. Batch Processing Performance\n";
echo "------------------------------\n";

UserDTO::warmUpCache();

$batchData = [];
for ($i = 0; 100 > $i; $i++) {
    $batchData[] = [
        'name' => 'User ' . $i,
        'age' => 20 + ($i % 50),
        'email' => sprintf('user%d@example.com', $i),
    ];
}

$start = microtime(true);
$users = array_map(fn($data): \UserDTO => UserDTO::fromArray($data), $batchData);
$duration = microtime(true) - $start;

echo "Processed 100 users in: " . number_format($duration * 1000, 2) . " ms\n";
echo "Average per user: " . number_format(($duration / 100) * 1000, 2) . " ms\n";
echo "Throughput: " . number_format(100 / $duration) . " users/second\n\n";

echo "=== Performance Optimization Complete ===\n";

