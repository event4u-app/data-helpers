#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Update benchmark results in README.md
 *
 * This script runs PHPBench and updates the benchmark results section in README.md
 * between the <!-- BENCHMARK_RESULTS_START --> and <!-- BENCHMARK_RESULTS_END --> markers.
 */

$rootDir = dirname(__DIR__);
$readmePath = $rootDir . '/README.md';

// Check if README exists
if (!file_exists($readmePath)) {
    echo sprintf('❌  README.md not found at: %s%s', $readmePath, PHP_EOL);
    exit(1);
}

echo "🚀  Running benchmarks 10 times and calculating averages...\n\n";

// Run benchmarks 10 times and collect results
$allRuns = [];
$benchCommand = 'cd ' . escapeshellarg($rootDir) . ' && vendor/bin/phpbench run --report=table 2>&1';

for ($run = 1; 10 >= $run; $run++) {
    echo "  Run {$run}/10...\n";

    exec($benchCommand, $outputLines, $returnCode);

    if (0 !== $returnCode) {
        echo '❌  Failed to run benchmarks (exit code: ' . $returnCode . ")\n";
        exit(1);
    }

    $output = implode("\n", $outputLines);
    $outputLines = []; // Reset for next run

    // Parse table output
    $runResults = [
        'DataAccessor' => [],
        'DataMutator' => [],
        'DataMapper' => [],
        'DtoSerialization' => [],
    ];

    $lines = explode("\n", $output);
    $currentClass = null;

    foreach ($lines as $line) {
        // Detect class headers
        if (str_contains($line, 'DataAccessorBench')) {
            $currentClass = 'DataAccessor';
            continue;
        }
        if (str_contains($line, 'DataMutatorBench')) {
            $currentClass = 'DataMutator';
            continue;
        }
        if (str_contains($line, 'DataMapperBench')) {
            $currentClass = 'DataMapper';
            continue;
        }
        if (str_contains($line, 'DtoSerializationBench')) {
            $currentClass = 'DtoSerialization';
            continue;
        }

        // Parse data rows (format: | subject | set | revs | its | mem_peak | mode | rstdev |)
        if ($currentClass && preg_match('/\|\s*(\w+)\s*\|.*\|\s*([\d.]+)μs\s*\|/', $line, $matches)) {
            $subjectName = $matches[1];
            $time = (float)$matches[2];

            $runResults[$currentClass][$subjectName][] = $time;
        }
    }

    $allRuns[] = $runResults;
}

echo "\n📊  Calculating averages...\n\n";

// Calculate averages
$results = [
    'DataAccessor' => [],
    'DataMutator' => [],
    'DataMapper' => [],
    'DtoSerialization' => [],
];

foreach ($allRuns as $runResults) {
    foreach ($runResults as $className => $subjects) {
        foreach ($subjects as $subjectName => $times) {
            if (!isset($results[$className][$subjectName])) {
                $results[$className][$subjectName] = [];
            }
            $results[$className][$subjectName] = array_merge($results[$className][$subjectName], $times);
        }
    }
}

// Convert to final format with averages
foreach ($results as $className => $subjects) {
    $averaged = [];
    foreach ($subjects as $subjectName => $times) {
        $averaged[] = [
            'name' => $subjectName,
            'time' => array_sum($times) / count($times),
        ];
    }
    $results[$className] = $averaged;
}

// Generate markdown tables
$markdown = "### DataAccessor\n\n";
$markdown .= "| Operation | Time | Description |\n";
$markdown .= "|-----------|------|-------------|\n";

$descriptions = [
    'benchSimpleGet' => 'Get value from flat array',
    'benchNestedGet' => 'Get value from nested path',
    'benchWildcardGet' => 'Get values using single wildcard',
    'benchDeepWildcardGet' => 'Get values using multiple wildcards (10 depts × 20 employees)',
    'benchTypedGetString' => 'Get typed string value',
    'benchTypedGetInt' => 'Get typed int value',
    'benchCreateAccessor' => 'Instantiate DataAccessor',
];

foreach ($results['DataAccessor'] as $result) {
    $resultName = (string)$result['name'];
    $name = str_replace('bench', '', $resultName);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime((float)$result['time']);
    $desc = $descriptions[$resultName] ?? '';
    $markdown .= '| ' . $name . ' | ' . $time . ' | ' . $desc . " |\n";
}

$markdown .= "\n### DataMutator\n\n";
$markdown .= "| Operation | Time | Description |\n";
$markdown .= "|-----------|------|-------------|\n";

$descriptions = [
    'benchSimpleSet' => 'Set value in flat array',
    'benchNestedSet' => 'Set value in nested path',
    'benchDeepSet' => 'Set value creating new nested structure',
    'benchMultipleSet' => 'Set multiple values at once',
    'benchMerge' => 'Deep merge arrays',
    'benchUnset' => 'Remove single value',
    'benchMultipleUnset' => 'Remove multiple values',
];

foreach ($results['DataMutator'] as $result) {
    $resultName = (string)$result['name'];
    $name = str_replace('bench', '', $resultName);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime((float)$result['time']);
    $desc = $descriptions[$resultName] ?? '';
    $markdown .= '| ' . $name . ' | ' . $time . ' | ' . $desc . " |\n";
}

$markdown .= "\n### DataMapper\n\n";
$markdown .= "| Operation | Time | Description |\n";
$markdown .= "|-----------|------|-------------|\n";

$descriptions = [
    'benchSimpleMapping' => 'Map flat structure',
    'benchNestedMapping' => 'Map nested structure',
    'benchAutoMap' => 'Automatic field mapping',
    'benchMapFromTemplate' => 'Map using template expressions',
];

foreach ($results['DataMapper'] as $result) {
    $resultName = (string)$result['name'];
    $name = str_replace('bench', '', $resultName);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime((float)$result['time']);
    $desc = $descriptions[$resultName] ?? '';
    $markdown .= '| ' . $name . ' | ' . $time . ' | ' . $desc . " |\n";
}

$markdown .= "\n### DTO Serialization Comparison\n\n";
$markdown .= "Comparison of DataMapper vs Symfony Serializer for mapping nested JSON to DTOs:\n\n";
$markdown .= "| Method | Time | vs Symfony | Description |\n";
$markdown .= "|--------|------|------------|-------------|\n";

$descriptions = [
    'benchManualMapping' => 'Direct DTO constructor (baseline)',
    'benchDataMapperTemplate' => 'DataMapper with template syntax ({{ ... }})',
    'benchDataMapperSimplePaths' => 'DataMapper with simple path mapping',
    'benchSymfonySerializerArray' => 'Symfony Serializer from array',
    'benchSymfonySerializerJson' => 'Symfony Serializer from JSON',
];

// Find Symfony time for comparison
$symfonyTime = null;
foreach ($results['DtoSerialization'] as $result) {
    if ('benchSymfonySerializerArray' === $result['name']) {
        $symfonyTime = $result['time'];
    }
}

// Sort DtoSerialization results in desired order
$sortOrder = [
    'benchManualMapping' => 1,
    'benchDataMapperSimplePaths' => 2,
    'benchDataMapperTemplate' => 3,
    'benchSymfonySerializerArray' => 4,
    'benchSymfonySerializerJson' => 5,
];
usort(
    $results['DtoSerialization'],
    fn(array $a, array $b): int => ($sortOrder[(string)$a['name']] ?? 999) <=> ($sortOrder[(string)$b['name']] ?? 999)
);

foreach ($results['DtoSerialization'] as $result) {
    $resultName = (string)$result['name'];
    $name = str_replace('bench', '', $resultName);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime((float)$result['time']);
    $desc = $descriptions[$resultName] ?? '';

    // Calculate comparison vs Symfony
    $vsSymfony = '';
    if ($symfonyTime && 0 < $symfonyTime && 'benchSymfonySerializerArray' !== $resultName && 'benchSymfonySerializerJson' !== $resultName) {
        $factor = (float)$symfonyTime / (float)$result['time'];
        $vsSymfony = sprintf('**%.1fx faster**', $factor);
    }

    $markdown .= '| ' . $name . ' | ' . $time . ' | ' . $vsSymfony . ' | ' . $desc . " |\n";
}

// Read README
$readme = file_get_contents($readmePath);

if (false === $readme) {
    echo "❌  Failed to read README.md\n";
    exit(1);
}

// Update benchmark results section
$startMarker = '<!-- BENCHMARK_RESULTS_START -->';
$endMarker = '<!-- BENCHMARK_RESULTS_END -->';

$startPos = strpos($readme, $startMarker);
$endPos = strpos($readme, $endMarker);

if (false === $startPos || false === $endPos) {
    echo "❌  Could not find benchmark markers in README.md\n";
    exit(1);
}

// Replace content between markers
$before = substr($readme, 0, $startPos + strlen($startMarker));
$after = substr($readme, $endPos);

$newReadme = $before . "\n" . $markdown . $after;

// Update performance comparison section
$perfStartMarker = '<!-- PERFORMANCE_COMPARISON_START -->';
$perfEndMarker = '<!-- PERFORMANCE_COMPARISON_END -->';

$perfStartPos = strpos($newReadme, $perfStartMarker);
$perfEndPos = strpos($newReadme, $perfEndMarker);

if (false !== $perfStartPos && false !== $perfEndPos) {
    // Extract DataMapper vs Symfony comparison
    $dataMapperTemplateTime = null;
    $symfonyArrayTime = null;
    foreach ($results['DtoSerialization'] as $result) {
        if ('benchDataMapperTemplate' === $result['name']) {
            $dataMapperTemplateTime = $result['time'];
        }
        if ('benchSymfonySerializerArray' === $result['name']) {
            $symfonyArrayTime = $result['time'];
        }
    }

    $speedupFactor = 0.0;
    if ($dataMapperTemplateTime && $symfonyArrayTime && 0 < $dataMapperTemplateTime) {
        $speedupFactor = (float)$symfonyArrayTime / (float)$dataMapperTemplateTime;
    }

    $performanceSection = "### 🚀 **Blazing fast performance**\n\n";
    $performanceSection .= "DataMapper is significantly faster than traditional serializers for DTO mapping:\n\n";
    if (0 < $speedupFactor) {
        $performanceSection .= sprintf("- Up to **%.1fx faster** than Symfony Serializer\n", $speedupFactor);
    }
    $performanceSection .= "- Optimized for nested data structures\n";
    $performanceSection .= "- Zero reflection overhead for template-based mapping\n";
    $performanceSection .= "- See [benchmarks](#-performance) for detailed performance comparison";

    // Replace content between markers
    $beforePerf = substr($newReadme, 0, $perfStartPos + strlen($perfStartMarker));
    $afterPerf = substr($newReadme, $perfEndPos);
    $newReadme = $beforePerf . "\n" . $performanceSection . "\n" . $afterPerf;
}

// Write README
file_put_contents($readmePath, $newReadme);

echo "✅  Benchmark results updated in README.md\n";
echo "✅  Performance comparison updated in 'Why use this?' section\n";

function formatTime(float $microseconds): string
{
    if (1 > $microseconds) {
        return number_format($microseconds, 3) . 'μs';
    }
    if (1000 > $microseconds) {
        return number_format($microseconds, 3) . 'μs';
    }
    return number_format($microseconds / 1000, 3) . 'ms';
}
