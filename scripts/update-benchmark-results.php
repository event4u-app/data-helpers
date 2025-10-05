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

echo "🚀  Running benchmarks...\n\n";

// Run PHPBench with table output
$benchCommand = 'cd ' . escapeshellarg($rootDir) . ' && vendor/bin/phpbench run --report=table 2>&1';
exec($benchCommand, $outputLines, $returnCode);

if (0 !== $returnCode) {
    echo "❌  Failed to run benchmarks (exit code: {$returnCode})\n";
    exit(1);
}

$output = implode("\n", $outputLines);

// Parse table output
$results = [
    'DataAccessor' => [],
    'DataMutator' => [],
    'DataMapper' => [],
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

    // Parse data rows (format: | subject | set | revs | its | mem_peak | mode | rstdev |)
    if ($currentClass && preg_match('/\|\s*(\w+)\s*\|.*\|\s*([\d.]+)μs\s*\|/', $line, $matches)) {
        $subjectName = $matches[1];
        $time = (float)$matches[2];

        $results[$currentClass][] = [
            'name' => $subjectName,
            'time' => $time,
        ];
    }
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
    $name = str_replace('bench', '', $result['name']);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime($result['time']);
    $desc = $descriptions[$result['name']] ?? '';
    $markdown .= "| {$name} | {$time} | {$desc} |\n";
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
    $name = str_replace('bench', '', $result['name']);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime($result['time']);
    $desc = $descriptions[$result['name']] ?? '';
    $markdown .= "| {$name} | {$time} | {$desc} |\n";
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
    $name = str_replace('bench', '', $result['name']);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    $name = trim((string)$name);
    $time = formatTime($result['time']);
    $desc = $descriptions[$result['name']] ?? '';
    $markdown .= "| {$name} | {$time} | {$desc} |\n";
}

// Read README
$readme = file_get_contents($readmePath);

if (false === $readme) {
    echo "❌  Failed to read README.md\n";
    exit(1);
}

// Find markers
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

// Write README
file_put_contents($readmePath, $newReadme);

echo "✅  Benchmark results updated in README.md\n";

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

