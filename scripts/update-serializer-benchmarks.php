#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Update Symfony Serializer benchmark results in documentation
 *
 * This script runs comprehensive benchmarks comparing:
 * - Symfony Serializer
 * - SimpleDTO
 * - LiteDTO
 *
 * Results are automatically updated in the documentation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\SimpleDto;

$rootDir = dirname(__DIR__);
$docPath = $rootDir . '/starlight/src/content/docs/performance/serializer-benchmarks.md';

echo "🚀  Running Serializer Benchmarks...\n\n";

// Check if Symfony Serializer is available
$hasSymfony = class_exists('Symfony\Component\Serializer\Serializer');
if (!$hasSymfony) {
    echo "⚠️  Symfony Serializer not found. Install with: composer require symfony/serializer symfony/property-info\n";
}

/**
 * Run a benchmark
 *
 * @param callable(): void $callback
 * @return array{avg_time_us: float, ops_per_sec: int, memory_bytes: int}
 */
function runBenchmark(string $name, callable $callback, int $iterations = 10000): array
{
    echo "  Running: {$name}...\n";

    // Warmup
    for ($i = 0; 100 > $i; $i++) {
        $callback();
    }

    // Measure
    gc_collect_cycles();
    $memBefore = memory_get_usage();
    $startTime = hrtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    $endTime = hrtime(true);
    $memAfter = memory_get_usage();

    $totalTimeNs = $endTime - $startTime;
    $avgTimeUs = ($totalTimeNs / $iterations) / 1000; // Convert to microseconds
    $opsPerSec = (int)(1_000_000_000 / ($totalTimeNs / $iterations));
    $memoryBytes = max(0, $memAfter - $memBefore);

    return [
        'avg_time_us' => $avgTimeUs,
        'ops_per_sec' => $opsPerSec,
        'memory_bytes' => $memoryBytes,
    ];
}

/**
 * Format time
 */
function formatTime(float $microseconds): string
{
    if (1 > $microseconds) {
        return number_format($microseconds * 1000, 2) . 'ns';
    }
    if (1000 > $microseconds) {
        return number_format($microseconds, 2) . 'μs';
    }
    return number_format($microseconds / 1000, 2) . 'ms';
}

/**
 * Format memory
 */
function formatMemory(int $bytes): string
{
    if (1024 > $bytes) {
        return $bytes . 'B';
    }
    if (1024 * 1024 > $bytes) {
        return number_format($bytes / 1024, 2) . 'KB';
    }
    return number_format($bytes / (1024 * 1024), 2) . 'MB';
}

/**
 * Calculate speedup factor
 */
function calculateSpeedup(float $baseline, float $current): string
{
    if (0 >= $baseline || 0 >= $current) {
        return '-';
    }
    $factor = $baseline / $current;
    if (1 < $factor) {
        return sprintf('**%.1fx faster**', $factor);
    }
    if (1 > $factor) {
        return sprintf('%.1fx slower', 1 / $factor);
    }
    return 'same';
}

// Test data
$simpleData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
];

$nestedData = [
    'user' => [
        'profile' => [
            'firstName' => 'Alice',
            'lastName' => 'Smith',
            'age' => 30,
        ],
        'contact' => [
            'email' => 'alice@example.com',
            'phone' => '+1234567890',
        ],
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
            'zipCode' => '10001',
            'country' => 'USA',
        ],
    ],
];

$deeplyNestedData = [
    'level1' => [
        'level2' => [
            'level3' => [
                'level4' => [
                    'level5' => [
                        'name' => 'Deep Value',
                        'value' => 42,
                    ],
                ],
            ],
        ],
    ],
];

$collectionData = array_fill(0, 100, $simpleData);

// Define DTOs
class SimpleUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

class LiteUserDto extends LiteDto
{
    public string $name;
    public string $email;
    public int $age;
}

class NestedProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly int $age,
    ) {}
}

class NestedContactDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $phone,
    ) {}
}

class NestedAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class NestedUserDto extends SimpleDto
{
    public function __construct(
        public readonly NestedProfileDto $profile,
        public readonly NestedContactDto $contact,
        public readonly NestedAddressDto $address,
    ) {}
}

class NestedRootDto extends SimpleDto
{
    public function __construct(
        public readonly NestedUserDto $user,
    ) {}
}

class DeepLevel5Dto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $value,
    ) {}
}

class DeepLevel4Dto extends SimpleDto
{
    public function __construct(
        public readonly DeepLevel5Dto $level5,
    ) {}
}

class DeepLevel3Dto extends SimpleDto
{
    public function __construct(
        public readonly DeepLevel4Dto $level4,
    ) {}
}

class DeepLevel2Dto extends SimpleDto
{
    public function __construct(
        public readonly DeepLevel3Dto $level3,
    ) {}
}

class DeepLevel1Dto extends SimpleDto
{
    public function __construct(
        public readonly DeepLevel2Dto $level2,
    ) {}
}

class DeepRootDto extends SimpleDto
{
    public function __construct(
        public readonly DeepLevel1Dto $level1,
    ) {}
}

// Setup Symfony Serializer if available
$symfonySerializer = null;
if ($hasSymfony) {
    $reflectionExtractor = new \Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor();
    $phpDocExtractor = new \Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor();
    $propertyInfoExtractor = new \Symfony\Component\PropertyInfo\PropertyInfoExtractor(
        [$reflectionExtractor],
        [$phpDocExtractor, $reflectionExtractor],
        [$phpDocExtractor],
        [$reflectionExtractor],
        [$reflectionExtractor]
    );

    $symfonySerializer = new \Symfony\Component\Serializer\Serializer(
        [
            new \Symfony\Component\Serializer\Normalizer\ObjectNormalizer(null, null, null, $propertyInfoExtractor),
            new \Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(),
        ],
        [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]
    );
}

echo "\n📊 Benchmark 1: Simple Object Deserialization\n";
echo str_repeat('─', 60) . "\n";

$results = [];

// SimpleDTO
$results['simple_simpledto'] = runBenchmark('SimpleDTO', function() use ($simpleData): void {
    SimpleUserDto::fromArray($simpleData);
});

// LiteDTO
$results['simple_litedto'] = runBenchmark('LiteDTO', function() use ($simpleData): void {
    $dto = new LiteUserDto();
    $dto->name = $simpleData['name'];
    $dto->email = $simpleData['email'];
    $dto->age = $simpleData['age'];
});

// Symfony Serializer
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['simple_symfony'] = runBenchmark('Symfony Serializer', function() use (
        $symfonySerializer,
        $simpleData
    ): void {
        $symfonySerializer->denormalize($simpleData, SimpleUserDto::class);
    });
}

echo "\n📊 Benchmark 2: Nested Object Deserialization (3 levels)\n";
echo str_repeat('─', 60) . "\n";

// SimpleDTO - Nested
$results['nested_simpledto'] = runBenchmark('SimpleDTO (nested)', function() use ($nestedData): void {
    NestedRootDto::fromArray($nestedData);
});

// Symfony Serializer - Nested
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['nested_symfony'] = runBenchmark('Symfony Serializer (nested)', function() use (
        $symfonySerializer,
        $nestedData
    ): void {
        $symfonySerializer->denormalize($nestedData, NestedRootDto::class);
    });
}

echo "\n📊 Benchmark 3: Deeply Nested Object (5 levels)\n";
echo str_repeat('─', 60) . "\n";

// SimpleDTO - Deep
$results['deep_simpledto'] = runBenchmark('SimpleDTO (5 levels)', function() use ($deeplyNestedData): void {
    DeepRootDto::fromArray($deeplyNestedData);
});

// Symfony Serializer - Deep
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['deep_symfony'] = runBenchmark('Symfony Serializer (5 levels)', function() use (
        $symfonySerializer,
        $deeplyNestedData
    ): void {
        $symfonySerializer->denormalize($deeplyNestedData, DeepRootDto::class);
    });
}

echo "\n📊 Benchmark 4: Collection (100 items)\n";
echo str_repeat('─', 60) . "\n";

// SimpleDTO - Collection
$results['collection_simpledto'] = runBenchmark('SimpleDTO (100 items)', function() use ($collectionData): void {
    array_map(fn(array $item): \SimpleUserDto => SimpleUserDto::fromArray($item), $collectionData);
}, 1000);

// LiteDTO - Collection
$results['collection_litedto'] = runBenchmark('LiteDTO (100 items)', function() use ($collectionData): void {
    array_map(function(array $item): \LiteUserDto {
        $dto = new LiteUserDto();
        $dto->name = $item['name'];
        $dto->email = $item['email'];
        $dto->age = $item['age'];
        return $dto;
    }, $collectionData);
}, 1000);

// Symfony Serializer - Collection
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['collection_symfony'] = runBenchmark('Symfony Serializer (100 items)', function() use (
        $symfonySerializer,
        $collectionData
    ): void {
        $symfonySerializer->denormalize($collectionData, SimpleUserDto::class . '[]');
    }, 1000);
}

echo "\n📊 Benchmark 5: Serialization (toArray/normalize)\n";
echo str_repeat('─', 60) . "\n";

$simpleDto = SimpleUserDto::fromArray($simpleData);
$liteDto = new LiteUserDto();
$liteDto->name = $simpleData['name'];
$liteDto->email = $simpleData['email'];
$liteDto->age = $simpleData['age'];

// SimpleDTO - toArray
$results['serialize_simpledto'] = runBenchmark('SimpleDTO::toArray()', function() use ($simpleDto): void {
    $simpleDto->toArray();
});

// LiteDTO - toArray
$results['serialize_litedto'] = runBenchmark('LiteDTO::toArray()', function() use ($liteDto): void {
    $liteDto->toArray();
});

// Symfony Serializer - normalize
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['serialize_symfony'] = runBenchmark('Symfony::normalize()', function() use (
        $symfonySerializer,
        $simpleDto
    ): void {
        $symfonySerializer->normalize($simpleDto);
    });
}

echo "\n📊 Benchmark 6: JSON Serialization\n";
echo str_repeat('─', 60) . "\n";

// SimpleDTO - JSON
$results['json_simpledto'] = runBenchmark('SimpleDTO::jsonSerialize()', function() use ($simpleDto): void {
    json_encode($simpleDto);
});

// LiteDTO - JSON
$results['json_litedto'] = runBenchmark('LiteDTO::jsonSerialize()', function() use ($liteDto): void {
    json_encode($liteDto);
});

// Symfony Serializer - JSON
if ($symfonySerializer instanceof \Symfony\Component\Serializer\Serializer) {
    $results['json_symfony'] = runBenchmark('Symfony::serialize(json)', function() use (
        $symfonySerializer,
        $simpleDto
    ): void {
        $symfonySerializer->serialize($simpleDto, 'json');
    });
}

echo "\n✅ Benchmarks completed!\n\n";

// Generate markdown
$markdown = generateMarkdown($results, $hasSymfony);

// Update documentation
if (!file_exists($docPath)) {
    echo "📝 Creating new documentation file...\n";
    $template = file_get_contents(__DIR__ . '/serializer-benchmarks-template.md');
    if (false === $template) {
        echo "❌ Template file not found\n";
        exit(1);
    }
    file_put_contents($docPath, $template);
}

$doc = file_get_contents($docPath);
if (false === $doc) {
    echo "❌ Failed to read documentation file\n";
    exit(1);
}

$startMarker = '<!-- BENCHMARK_RESULTS_START -->';
$endMarker = '<!-- BENCHMARK_RESULTS_END -->';

$startPos = strpos($doc, $startMarker);
$endPos = strpos($doc, $endMarker);

if (false === $startPos || false === $endPos) {
    echo "❌ Benchmark markers not found in documentation\n";
    exit(1);
}

$before = substr($doc, 0, $startPos + strlen($startMarker));
$after = substr($doc, $endPos);
$updated = $before . "\n\n" . $markdown . "\n" . $after;

file_put_contents($docPath, $updated);

echo sprintf('✅ Documentation updated: %s%s', $docPath, PHP_EOL);
echo "\n🎉 Done!\n";

/**
 * @param array<string, array{avg_time_us: float, ops_per_sec: int, memory_bytes: int}> $results
 */
function generateMarkdown(array $results, bool $hasSymfony): string
{
    $md = "**Last updated:** " . date('Y-m-d H:i:s') . " UTC\n\n";

    if (!$hasSymfony) {
        $md .= ":::caution[Symfony Serializer Not Installed]\n";
        $md .= "Install Symfony Serializer to see full comparison:\n";
        $md .= "```bash\n";
        $md .= "composer require symfony/serializer symfony/property-info\n";
        $md .= "```\n";
        $md .= ":::\n\n";
    }

    // Benchmark 1: Simple
    $md .= "## Benchmark 1: Simple Object Deserialization\n\n";
    $md .= "Single object with 4 properties (string, string, int, bool).\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['simple_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO | %s | %s | %s | %s |\n",
        formatTime($results['simple_simpledto']['avg_time_us']),
        number_format($results['simple_simpledto']['ops_per_sec']),
        formatMemory($results['simple_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['simple_simpledto']['avg_time_us']) : '-'
    );

    $md .= sprintf(
        "| LiteDTO | %s | %s | %s | %s |\n",
        formatTime($results['simple_litedto']['avg_time_us']),
        number_format($results['simple_litedto']['ops_per_sec']),
        formatMemory($results['simple_litedto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['simple_litedto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['simple_symfony'])) {
        $md .= sprintf(
            "| Symfony Serializer | %s | %s | %s | baseline |\n",
            formatTime($results['simple_symfony']['avg_time_us']),
            number_format($results['simple_symfony']['ops_per_sec']),
            formatMemory($results['simple_symfony']['memory_bytes'])
        );
    }

    // Benchmark 2: Nested
    $md .= "\n## Benchmark 2: Nested Object Deserialization\n\n";
    $md .= "Nested structure with 3 levels (user → profile/contact/address).\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['nested_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO | %s | %s | %s | %s |\n",
        formatTime($results['nested_simpledto']['avg_time_us']),
        number_format($results['nested_simpledto']['ops_per_sec']),
        formatMemory($results['nested_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['nested_simpledto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['nested_symfony'])) {
        $md .= sprintf(
            "| Symfony Serializer | %s | %s | %s | baseline |\n",
            formatTime($results['nested_symfony']['avg_time_us']),
            number_format($results['nested_symfony']['ops_per_sec']),
            formatMemory($results['nested_symfony']['memory_bytes'])
        );
    }

    // Benchmark 3: Deep
    $md .= "\n## Benchmark 3: Deeply Nested Object\n\n";
    $md .= "Deeply nested structure with 5 levels.\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['deep_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO | %s | %s | %s | %s |\n",
        formatTime($results['deep_simpledto']['avg_time_us']),
        number_format($results['deep_simpledto']['ops_per_sec']),
        formatMemory($results['deep_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['deep_simpledto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['deep_symfony'])) {
        $md .= sprintf(
            "| Symfony Serializer | %s | %s | %s | baseline |\n",
            formatTime($results['deep_symfony']['avg_time_us']),
            number_format($results['deep_symfony']['ops_per_sec']),
            formatMemory($results['deep_symfony']['memory_bytes'])
        );
    }

    // Benchmark 4: Collection
    $md .= "\n## Benchmark 4: Collection Processing\n\n";
    $md .= "Processing 100 simple objects.\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['collection_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO | %s | %s | %s | %s |\n",
        formatTime($results['collection_simpledto']['avg_time_us']),
        number_format($results['collection_simpledto']['ops_per_sec']),
        formatMemory($results['collection_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['collection_simpledto']['avg_time_us']) : '-'
    );

    $md .= sprintf(
        "| LiteDTO | %s | %s | %s | %s |\n",
        formatTime($results['collection_litedto']['avg_time_us']),
        number_format($results['collection_litedto']['ops_per_sec']),
        formatMemory($results['collection_litedto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['collection_litedto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['collection_symfony'])) {
        $md .= sprintf(
            "| Symfony Serializer | %s | %s | %s | baseline |\n",
            formatTime($results['collection_symfony']['avg_time_us']),
            number_format($results['collection_symfony']['ops_per_sec']),
            formatMemory($results['collection_symfony']['memory_bytes'])
        );
    }

    // Benchmark 5: Serialization
    $md .= "\n## Benchmark 5: Serialization (toArray/normalize)\n\n";
    $md .= "Converting DTO back to array.\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['serialize_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO::toArray() | %s | %s | %s | %s |\n",
        formatTime($results['serialize_simpledto']['avg_time_us']),
        number_format($results['serialize_simpledto']['ops_per_sec']),
        formatMemory($results['serialize_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['serialize_simpledto']['avg_time_us']) : '-'
    );

    $md .= sprintf(
        "| LiteDTO::toArray() | %s | %s | %s | %s |\n",
        formatTime($results['serialize_litedto']['avg_time_us']),
        number_format($results['serialize_litedto']['ops_per_sec']),
        formatMemory($results['serialize_litedto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['serialize_litedto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['serialize_symfony'])) {
        $md .= sprintf(
            "| Symfony::normalize() | %s | %s | %s | baseline |\n",
            formatTime($results['serialize_symfony']['avg_time_us']),
            number_format($results['serialize_symfony']['ops_per_sec']),
            formatMemory($results['serialize_symfony']['memory_bytes'])
        );
    }

    // Benchmark 6: JSON
    $md .= "\n## Benchmark 6: JSON Serialization\n\n";
    $md .= "Converting DTO to JSON string.\n\n";
    $md .= "| Method | Time | Ops/sec | Memory | vs Symfony |\n";
    $md .= "|--------|------|---------|--------|------------|\n";

    $symfonyTime = $results['json_symfony']['avg_time_us'] ?? null;

    $md .= sprintf(
        "| SimpleDTO (json_encode) | %s | %s | %s | %s |\n",
        formatTime($results['json_simpledto']['avg_time_us']),
        number_format($results['json_simpledto']['ops_per_sec']),
        formatMemory($results['json_simpledto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['json_simpledto']['avg_time_us']) : '-'
    );

    $md .= sprintf(
        "| LiteDTO (json_encode) | %s | %s | %s | %s |\n",
        formatTime($results['json_litedto']['avg_time_us']),
        number_format($results['json_litedto']['ops_per_sec']),
        formatMemory($results['json_litedto']['memory_bytes']),
        $symfonyTime ? calculateSpeedup($symfonyTime, $results['json_litedto']['avg_time_us']) : '-'
    );

    if ($hasSymfony && isset($results['json_symfony'])) {
        $md .= sprintf(
            "| Symfony::serialize(json) | %s | %s | %s | baseline |\n",
            formatTime($results['json_symfony']['avg_time_us']),
            number_format($results['json_symfony']['ops_per_sec']),
            formatMemory($results['json_symfony']['memory_bytes'])
        );
    }

    return $md;
}
