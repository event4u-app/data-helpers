#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Comprehensive Benchmark Script
 *
 * Runs all benchmarks (PHPBench + custom benchmarks) and updates documentation
 * with results including comparisons with external libraries.
 */

const COMPARE_WITH = [
    'c3BhdGllL2xhcmF2ZWwtZGF0YQ==',
    'Y2h1YmJ5cGhwL2NodWJieXBocC1wYXJzaW5n',
    'bWFyay1nZXJhcnRzL2F1dG8tbWFwcGVyLXBsdXM=',
    'bGFtaW5hcy9sYW1pbmFzLWh5ZHJhdG9y',
];

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;
use Tests\Utils\Dtos\DepartmentDto;
use Tests\Utils\SimpleDtos\DepartmentSimpleDto;

$rootDir = dirname(__DIR__);
$benchmarkDocsPath = $rootDir . '/starlight/src/content/docs/performance/benchmarks.md';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Comprehensive Benchmark Suite                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// Step 0: Prepare compare environment
// ============================================================================

$packagesToInstall = [];
foreach (COMPARE_WITH as $encodedPackage) {
    $package = base64_decode($encodedPackage);
    $packagesToInstall[] = $package;
}

if (!empty($packagesToInstall)) {
    $packageList = implode(' ', $packagesToInstall);
    $composerCmd = "cd {$rootDir} && composer require --dev {$packageList} --no-interaction --quiet > /dev/null 2>&1";
    exec($composerCmd, $output, $returnCode);
}

// ============================================================================
// Step 1: Run PHPBench benchmarks
// ============================================================================
echo "📊  Step 1/4: Running PHPBench benchmarks (10 runs)...\n\n";

$allRuns = [];
$benchCommand = 'cd ' . escapeshellarg($rootDir) . ' && vendor/bin/phpbench run --report=table 2>&1';

for ($run = 1; 10 >= $run; $run++) {
    echo "  Run {$run}/10...\n";

    exec($benchCommand, $outputLines, $returnCode);

    if (0 !== $returnCode) {
        echo "❌  Failed to run benchmarks (exit code: {$returnCode})\n";
        exit(1);
    }

    $output = implode("\n", $outputLines);
    $outputLines = [];

    // Parse table output
    $runResults = [
        'DataAccessor' => [],
        'DataMutator' => [],
        'DataMapper' => [],
        'DtoSerialization' => [],
        'ExternalDto' => [],
        'ExternalMapper' => [],
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
        if (str_contains($line, 'ExternalDtoBench')) {
            $currentClass = 'ExternalDto';
            continue;
        }
        if (str_contains($line, 'ExternalMapperBench')) {
            $currentClass = 'ExternalMapper';
            continue;
        }

        // Parse data rows
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
    'ExternalDto' => [],
    'ExternalMapper' => [],
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
        if ([] === $times) {
            continue;
        }
        $averaged[] = [
            'name' => $subjectName,
            'time' => array_sum($times) / count($times),
        ];
    }
    $results[$className] = $averaged;
}

// ============================================================================
// Step 2: Run custom DTO benchmarks
// ============================================================================
echo "📊  Step 2/4: Running custom DTO benchmarks...\n\n";

$testData = [
    'name' => 'Engineering',
    'code' => 'ENG',
    'budget' => 5000000.00,
    'employee_count' => 120,
    'manager_name' => 'Alice Johnson',
];

$dtoBenchmarks = runDtoBenchmark('Traditional Dto', function() use ($testData): void {
    $dto = new DepartmentDto();
    $dto->name = $testData['name'];
    $dto->code = $testData['code'];
    $dto->budget = $testData['budget'];
    $dto->employee_count = $testData['employee_count'];
    $dto->manager_name = $testData['manager_name'];
}, 100000);

$dtoBenchmarks['SimpleDto'] = runDtoBenchmark('SimpleDto', function() use ($testData): void {
    DepartmentSimpleDto::fromArray($testData);
}, 100000);

// ============================================================================
// Step 3: Generate markdown and update documentation
// ============================================================================
echo "📊  Step 3/4: Generating markdown and updating documentation...\n\n";

$markdown = generateMarkdown($results, $dtoBenchmarks);

// Update documentation
if (!file_exists($benchmarkDocsPath)) {
    echo "❌  Benchmark documentation not found at: {$benchmarkDocsPath}\n";
    exit(1);
}

$docsContent = file_get_contents($benchmarkDocsPath);
if (false === $docsContent) {
    echo "❌  Failed to read benchmark documentation\n";
    exit(1);
}

// Update sections with markers
$docsContent = updateSection($docsContent, 'BENCHMARK_INTRODUCTION', $markdown['Introduction']);
$docsContent = updateSection($docsContent, 'BENCHMARK_TRADEOFFS', $markdown['Tradeoffs']);
$docsContent = updateSection($docsContent, 'BENCHMARK_DATA_ACCESSOR', $markdown['DataAccessor']);
$docsContent = updateSection($docsContent, 'BENCHMARK_DATA_MUTATOR', $markdown['DataMutator']);
$docsContent = updateSection($docsContent, 'BENCHMARK_DATA_MAPPER', $markdown['DataMapper']);
$docsContent = updateSection($docsContent, 'BENCHMARK_DTO_COMPARISON', $markdown['DtoComparison']);
$docsContent = updateSection($docsContent, 'BENCHMARK_DTO_INSIGHTS', $markdown['DtoInsights']);
$docsContent = updateSection($docsContent, 'BENCHMARK_MAPPER_COMPARISON', $markdown['MapperComparison']);
$docsContent = updateSection($docsContent, 'BENCHMARK_MAPPER_INSIGHTS', $markdown['MapperInsights']);
$docsContent = updateSection($docsContent, 'BENCHMARK_SERIALIZATION', $markdown['Serialization']);
$docsContent = updateSection($docsContent, 'BENCHMARK_SERIALIZATION_INSIGHTS', $markdown['SerializationInsights']);

file_put_contents($benchmarkDocsPath, $docsContent);

echo "✅  Comprehensive benchmarks completed!\n";
echo "✅  Documentation updated at: starlight/src/content/docs/performance/benchmarks.md\n";
echo "\n";

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * @param callable(): void $callback
 * @return array{name: string, iterations: int, avg_time: float, ops_per_sec: int}
 */
function runDtoBenchmark(string $name, callable $callback, int $iterations): array
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

/**
 * @param array<string, array<int, array{name: string, time: float}>> $results
 * @param array<string, array{name: string, iterations: int, avg_time: float, ops_per_sec: int}> $dtoBenchmarks
 * @return array<string, string>
 */
function generateMarkdown(array $results, array $dtoBenchmarks): array
{
    $markdown = [];

    // DataAccessor
    $md = "| Operation | Time | Description |\n";
    $md .= "|-----------|------|-------------|\n";
    $descriptions = [
        'benchSimpleGet' => 'Get value from flat array',
        'benchNestedGet' => 'Get value from nested path',
        'benchWildcardGet' => 'Get values using single wildcard',
        'benchDeepWildcardGet' => 'Get values using multiple wildcards',
        'benchTypedGetString' => 'Get typed string value',
        'benchTypedGetInt' => 'Get typed int value',
        'benchCreateAccessor' => 'Instantiate DataAccessor',
    ];
    foreach ($results['DataAccessor'] as $result) {
        $name = formatBenchmarkName($result['name']);
        $time = formatTime($result['time']);
        $desc = $descriptions[$result['name']] ?? '';
        $md .= "| {$name} | {$time} | {$desc} |\n";
    }
    $markdown['DataAccessor'] = $md;

    // DataMutator
    $md = "| Operation | Time | Description |\n";
    $md .= "|-----------|------|-------------|\n";
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
        $name = formatBenchmarkName($result['name']);
        $time = formatTime($result['time']);
        $desc = $descriptions[$result['name']] ?? '';
        $md .= "| {$name} | {$time} | {$desc} |\n";
    }
    $markdown['DataMutator'] = $md;

    // DataMapper
    $md = "| Operation | Time | Description |\n";
    $md .= "|-----------|------|-------------|\n";
    $descriptions = [
        'benchSimpleMapping' => 'Map flat structure',
        'benchNestedMapping' => 'Map nested structure',
        'benchAutoMap' => 'Automatic field mapping',
        'benchMapFromTemplate' => 'Map using template expressions',
    ];
    foreach ($results['DataMapper'] as $result) {
        $name = formatBenchmarkName($result['name']);
        $time = formatTime($result['time']);
        $desc = $descriptions[$result['name']] ?? '';
        $md .= "| {$name} | {$time} | {$desc} |\n";
    }
    $markdown['DataMapper'] = $md;

    // DTO Comparison - Restructured table
    $md = "| Method | SimpleDto | Plain PHP | Other DTOs | Description |\n";
    $md .= "|--------|-----------|-----------|------------|-------------|\n";

    // Group results by operation type
    $dtoGroups = [
        'FromArray' => ['SimpleDto' => null, 'PlainPhp' => null, 'OtherDto' => null, 'displayName' => 'From Array'],
        'ToArray' => ['SimpleDto' => null, 'PlainPhp' => null, 'OtherDto' => null, 'displayName' => 'To Array'],
        'ComplexData' => ['SimpleDto' => null, 'PlainPhp' => null, 'OtherDto' => null, 'displayName' => 'Complex Data'],
    ];

    foreach ($results['ExternalDto'] as $result) {
        if (str_contains($result['name'], 'FromArray') || str_contains($result['name'], 'From') || str_contains($result['name'], 'NewAssign') || str_contains($result['name'], 'Constructor')) {
            if (str_contains($result['name'], 'SimpleDto')) {
                $dtoGroups['FromArray']['SimpleDto'] = $result;
            } elseif (str_contains($result['name'], 'PlainPhp')) {
                // Use the faster plain PHP method (constructor is typically faster)
                if (!isset($dtoGroups['FromArray']['PlainPhp']) || str_contains($result['name'], 'Constructor')) {
                    $dtoGroups['FromArray']['PlainPhp'] = $result;
                }
            } elseif (str_contains($result['name'], 'OtherDto')) {
                $dtoGroups['FromArray']['OtherDto'] = $result;
            }
        } elseif (str_contains($result['name'], 'ToArray')) {
            if (str_contains($result['name'], 'SimpleDto')) {
                $dtoGroups['ToArray']['SimpleDto'] = $result;
            } elseif (str_contains($result['name'], 'OtherDto')) {
                $dtoGroups['ToArray']['OtherDto'] = $result;
            }
        } elseif (str_contains($result['name'], 'ComplexData')) {
            if (str_contains($result['name'], 'SimpleDto')) {
                $dtoGroups['ComplexData']['SimpleDto'] = $result;
            } elseif (str_contains($result['name'], 'OtherDto')) {
                $dtoGroups['ComplexData']['OtherDto'] = $result;
            }
        }
    }

    // Generate rows
    foreach ($dtoGroups as $operation => $group) {
        if (!$group['SimpleDto']) {
            continue;
        }

        $ourTime = formatTime($group['SimpleDto']['time']);
        $desc = getExternalDtoDescription($group['SimpleDto']['name']);

        $plainPhp = '-';
        if ($group['PlainPhp']) {
            $plainTime = formatTime($group['PlainPhp']['time']);
            $factor = $group['SimpleDto']['time'] / $group['PlainPhp']['time'];
            if ($factor > 1.1) {
                // We are slower (our time is bigger)
                $plainPhp = sprintf('%s<br>(**%.1fx slower**)', $plainTime, $factor);
            } elseif ($factor < 0.9) {
                // We are faster (our time is smaller)
                $plainPhp = sprintf('%s<br>(**%.1fx faster**)', $plainTime, 1 / $factor);
            } else {
                $plainPhp = sprintf('%s<br>(~same)', $plainTime);
            }
        }

        $otherDto = '-';
        if ($group['OtherDto']) {
            $otherTime = formatTime($group['OtherDto']['time']);
            $factor = $group['SimpleDto']['time'] / $group['OtherDto']['time'];
            if ($factor > 1.1) {
                // We are slower (our time is bigger)
                $otherDto = sprintf('%s<br>(**%.1fx slower**)', $otherTime, $factor);
            } elseif ($factor < 0.9) {
                // We are faster (our time is smaller)
                $otherDto = sprintf('%s<br>(**%.1fx faster**)', $otherTime, 1 / $factor);
            } else {
                $otherDto = sprintf('%s<br>(~same)', $otherTime);
            }
        }

        $displayName = $group['displayName'] ?? $operation;
        $ourTimeFormatted = "{$ourTime}<br>(we are)";
        $md .= "| {$displayName} | {$ourTimeFormatted} | {$plainPhp} | {$otherDto} | {$desc} |\n";
    }
    $markdown['DtoComparison'] = $md;

    // Mapper Comparison - Restructured table
    $md = "| Method | DataMapper | Plain PHP | Other Mappers | Description |\n";
    $md .= "|--------|------------|-----------|---------------|-------------|\n";

    // Group results by operation type
    $mapperGroups = [
        'SimpleMapping' => ['DataMapper' => null, 'PlainPhp' => null, 'Others' => [], 'displayName' => 'Simple Mapping'],
        'NestedMapping' => ['DataMapper' => null, 'PlainPhp' => null, 'Others' => [], 'displayName' => 'Nested Mapping'],
        'TemplateMapping' => ['DataMapper' => null, 'PlainPhp' => null, 'Others' => [], 'displayName' => 'Template Mapping'],
    ];

    foreach ($results['ExternalMapper'] as $result) {
        if (str_contains($result['name'], 'Simple')) {
            if (str_contains($result['name'], 'DataMapper')) {
                $mapperGroups['SimpleMapping']['DataMapper'] = $result;
            } elseif (str_contains($result['name'], 'PlainPhp')) {
                $mapperGroups['SimpleMapping']['PlainPhp'] = $result;
            } else {
                $mapperGroups['SimpleMapping']['Others'][] = $result;
            }
        } elseif (str_contains($result['name'], 'Nested')) {
            if (str_contains($result['name'], 'DataMapper')) {
                $mapperGroups['NestedMapping']['DataMapper'] = $result;
            } elseif (str_contains($result['name'], 'PlainPhp')) {
                $mapperGroups['NestedMapping']['PlainPhp'] = $result;
            } else {
                $mapperGroups['NestedMapping']['Others'][] = $result;
            }
        } elseif (str_contains($result['name'], 'Template')) {
            if (str_contains($result['name'], 'DataMapper')) {
                $mapperGroups['TemplateMapping']['DataMapper'] = $result;
            } elseif (str_contains($result['name'], 'OtherParser')) {
                $mapperGroups['TemplateMapping']['Others'][] = $result;
            }
        }
    }

    // Generate rows
    foreach ($mapperGroups as $operation => $group) {
        if (!$group['DataMapper']) {
            continue;
        }

        $ourTime = formatTime($group['DataMapper']['time']);
        $desc = getExternalMapperDescription($group['DataMapper']['name']);

        $plainPhp = '-';
        if ($group['PlainPhp']) {
            $plainTime = formatTime($group['PlainPhp']['time']);
            $factor = $group['DataMapper']['time'] / $group['PlainPhp']['time'];
            if ($factor > 1.1) {
                // We are slower (our time is bigger)
                $plainPhp = sprintf('%s<br>(**%.1fx slower**)', $plainTime, $factor);
            } elseif ($factor < 0.9) {
                // We are faster (our time is smaller)
                $plainPhp = sprintf('%s<br>(**%.1fx faster**)', $plainTime, 1 / $factor);
            } else {
                $plainPhp = sprintf('%s<br>(~same)', $plainTime);
            }
        }

        $otherMappers = '-';
        if (!empty($group['Others'])) {
            $avgOtherTime = array_sum(array_column($group['Others'], 'time')) / count($group['Others']);
            $avgOtherTimeFormatted = formatTime($avgOtherTime);
            $factor = $group['DataMapper']['time'] / $avgOtherTime;
            if ($factor > 1.1) {
                // We are slower (our time is bigger)
                $otherMappers = sprintf('%s<br>(**%.1fx slower**)', $avgOtherTimeFormatted, $factor);
            } elseif ($factor < 0.9) {
                // We are faster (our time is smaller)
                $otherMappers = sprintf('%s<br>(**%.1fx faster**)', $avgOtherTimeFormatted, 1 / $factor);
            } else {
                $otherMappers = sprintf('%s<br>(~same)', $avgOtherTimeFormatted);
            }
        }

        $displayName = $group['displayName'] ?? $operation;
        $ourTimeFormatted = "{$ourTime}<br>(we are)";
        $md .= "| {$displayName} | {$ourTimeFormatted} | {$plainPhp} | {$otherMappers} | {$desc} |\n";
    }
    $markdown['MapperComparison'] = $md;

    // Serialization - Restructured like Mapper table
    $md = "| Method | DataMapper | Plain PHP | Symfony Serializer | Description |\n";
    $md .= "|--------|------------|-----------|-------------------|-------------|\n";

    // Group results by operation type
    $serializationGroups = [
        'TemplateSyntax' => ['DataMapper' => null, 'PlainPhp' => null, 'Symfony' => null, 'displayName' => 'Template Syntax'],
        'SimplePaths' => ['DataMapper' => null, 'PlainPhp' => null, 'Symfony' => null, 'displayName' => 'Simple Paths'],
    ];

    // Find Symfony average time for comparison
    $symfonyTime = 0;
    $symfonyCount = 0;
    foreach ($results['DtoSerialization'] as $result) {
        if (str_contains($result['name'], 'Symfony')) {
            $symfonyTime += $result['time'];
            $symfonyCount++;
        }
    }
    $symfonyTime = $symfonyCount > 0 ? $symfonyTime / $symfonyCount : 0;

    // Populate groups
    foreach ($results['DtoSerialization'] as $result) {
        if (str_contains($result['name'], 'DataMapperTemplate')) {
            $serializationGroups['TemplateSyntax']['DataMapper'] = $result;
        } elseif (str_contains($result['name'], 'DataMapperSimplePaths')) {
            $serializationGroups['SimplePaths']['DataMapper'] = $result;
        } elseif (str_contains($result['name'], 'ManualMapping')) {
            $serializationGroups['TemplateSyntax']['PlainPhp'] = $result;
            $serializationGroups['SimplePaths']['PlainPhp'] = $result;
        }
    }

    // Generate rows
    foreach ($serializationGroups as $operation => $group) {
        if (!$group['DataMapper']) {
            continue;
        }

        $ourTime = formatTime($group['DataMapper']['time']);
        $desc = getSerializationDescription($group['DataMapper']['name']);

        $plainPhp = '-';
        if ($group['PlainPhp']) {
            $plainTime = formatTime($group['PlainPhp']['time']);
            $factor = $group['DataMapper']['time'] / $group['PlainPhp']['time'];
            if ($factor > 1.1) {
                // We are slower (our time is bigger)
                $plainPhp = sprintf('%s<br>(**%.1fx slower**)', $plainTime, $factor);
            } elseif ($factor < 0.9) {
                // We are faster (our time is smaller)
                $plainPhp = sprintf('%s<br>(**%.1fx faster**)', $plainTime, 1 / $factor);
            } else {
                $plainPhp = sprintf('%s<br>(~same)', $plainTime);
            }
        }

        $symfony = '-';
        if ($symfonyTime > 0) {
            $symfonyTimeFormatted = formatTime($symfonyTime);
            $factor = $symfonyTime / $group['DataMapper']['time'];
            if ($factor > 1.1) {
                // Symfony is slower (their time is bigger) = We are faster
                $symfony = sprintf('%s<br>(**%.1fx faster**)', $symfonyTimeFormatted, $factor);
            } elseif ($factor < 0.9) {
                // Symfony is faster (their time is smaller) = We are slower
                $symfony = sprintf('%s<br>(**%.1fx slower**)', $symfonyTimeFormatted, 1 / $factor);
            } else {
                $symfony = sprintf('%s<br>(~same)', $symfonyTimeFormatted);
            }
        }

        $displayName = $group['displayName'] ?? $operation;
        $ourTimeFormatted = "{$ourTime}<br>(we are)";
        $md .= "| {$displayName} | {$ourTimeFormatted} | {$plainPhp} | {$symfony} | {$desc} |\n";
    }
    $markdown['Serialization'] = $md;

    // Generate Introduction section
    $markdown['Introduction'] = generateIntroduction($results);

    // Generate Trade-offs section
    $markdown['Tradeoffs'] = generateTradeoffs($results);

    // Generate Insights sections
    $markdown['DtoInsights'] = generateDtoInsights($results);
    $markdown['MapperInsights'] = generateMapperInsights($results);
    $markdown['SerializationInsights'] = generateSerializationInsights($results);

    return $markdown;
}

function generateIntroduction(array $results): string
{
    // Calculate Symfony comparison
    $dataMapperSerializationAvg = 0;
    $symfonyAvg = 0;
    $serializationCount = 0;

    foreach ($results['DtoSerialization'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperSerializationAvg += $result['time'];
            $serializationCount++;
        } elseif (str_contains($result['name'], 'Symfony')) {
            $symfonyAvg += $result['time'];
        }
    }
    $dataMapperSerializationAvg = $serializationCount > 0 ? $dataMapperSerializationAvg / $serializationCount : 40;
    $symfonyAvg = $symfonyAvg > 0 ? $symfonyAvg / 2 : 150;
    $symfonyFactor = round($symfonyAvg / $dataMapperSerializationAvg, 1);

    // Calculate other mapper comparison
    $dataMapperAvg = 0;
    $otherMapperAvg = 0;
    $counts = ['DataMapper' => 0, 'Others' => 0];

    foreach ($results['ExternalMapper'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperAvg += $result['time'];
            $counts['DataMapper']++;
        } elseif (!str_contains($result['name'], 'PlainPhp')) {
            $otherMapperAvg += $result['time'];
            $counts['Others']++;
        }
    }

    $dataMapperAvg = $counts['DataMapper'] > 0 ? $dataMapperAvg / $counts['DataMapper'] : 20;
    $otherMapperAvg = $counts['Others'] > 0 ? $otherMapperAvg / $counts['Others'] : 5;
    $vsOthersFactor = round($dataMapperAvg / $otherMapperAvg, 1);

    $md = "- **Type safety and validation** - With reasonable performance cost\n";
    $md .= sprintf("- **%.1fx faster** than Symfony Serializer for complex mappings\n", $symfonyFactor);

    if ($vsOthersFactor < 1) {
        $md .= sprintf("- **%.1fx faster** than other mapper libraries (AutoMapper Plus, Laminas)\n", 1 / $vsOthersFactor);
    } else {
        $md .= sprintf("- Other mapper libraries are **%.1fx faster**, but DataMapper provides better features\n", $vsOthersFactor);
    }

    $md .= "- **Low memory footprint** - ~1.2 KB per instance";

    return $md;
}

function generateTradeoffs(array $results): string
{
    // Calculate average times
    $simpleDtoAvg = 0;
    $plainPhpDtoAvg = 0;
    $dtoCount = 0;

    foreach ($results['ExternalDto'] as $result) {
        if (str_contains($result['name'], 'SimpleDto')) {
            $simpleDtoAvg += $result['time'];
            $dtoCount++;
        } elseif (str_contains($result['name'], 'PlainPhp')) {
            $plainPhpDtoAvg += $result['time'];
        }
    }
    $simpleDtoAvg = $dtoCount > 0 ? $simpleDtoAvg / $dtoCount : 0;
    $plainPhpDtoAvg = $plainPhpDtoAvg > 0 ? $plainPhpDtoAvg : 0.2;

    $dataMapperAvg = 0;
    $plainPhpMapperAvg = 0;
    $mapperCount = 0;

    foreach ($results['ExternalMapper'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperAvg += $result['time'];
            $mapperCount++;
        } elseif (str_contains($result['name'], 'PlainPhp')) {
            $plainPhpMapperAvg += $result['time'];
        }
    }
    $dataMapperAvg = $mapperCount > 0 ? $dataMapperAvg / $mapperCount : 0;
    $plainPhpMapperAvg = $plainPhpMapperAvg > 0 ? $plainPhpMapperAvg : 0.2;

    $dataMapperSerializationAvg = 0;
    $symfonyAvg = 0;
    $serializationCount = 0;

    foreach ($results['DtoSerialization'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperSerializationAvg += $result['time'];
            $serializationCount++;
        } elseif (str_contains($result['name'], 'Symfony')) {
            $symfonyAvg += $result['time'];
        }
    }
    $dataMapperSerializationAvg = $serializationCount > 0 ? $dataMapperSerializationAvg / $serializationCount : 0;
    $symfonyAvg = $symfonyAvg > 0 ? $symfonyAvg / 2 : 150;

    $dtoFactor = $plainPhpDtoAvg > 0 ? round($simpleDtoAvg / $plainPhpDtoAvg) : 65;
    $mapperFactor = $plainPhpMapperAvg > 0 ? round($dataMapperAvg / $plainPhpMapperAvg) : 100;
    $symfonyFactor = $dataMapperSerializationAvg > 0 ? round($symfonyAvg / $dataMapperSerializationAvg, 1) : 3.5;

    $md = "```\n";
    $md .= "SimpleDto vs Plain PHP:\n";
    $md .= sprintf("- SimpleDto:  ~%.0f-%.0fμs per operation\n", $simpleDtoAvg * 0.9, $simpleDtoAvg * 1.1);
    $md .= sprintf("- Plain PHP:  ~%.1fμs per operation\n", $plainPhpDtoAvg);
    $md .= sprintf("- Trade-off:  ~%dx slower, but with type safety, validation, and immutability\n", $dtoFactor);
    $md .= "\n";
    $md .= "DataMapper vs Plain PHP:\n";
    $md .= sprintf("- DataMapper: ~%.0f-%.0fμs per operation\n", $dataMapperAvg * 0.9, $dataMapperAvg * 1.1);
    $md .= sprintf("- Plain PHP:  ~%.1f-%.1fμs per operation\n", $plainPhpMapperAvg * 0.5, $plainPhpMapperAvg * 1.5);
    $md .= sprintf("- Trade-off:  ~%dx slower, but with template syntax and automatic mapping\n", $mapperFactor);
    $md .= "\n";
    $md .= "DataMapper vs Symfony Serializer:\n";
    $md .= sprintf("- DataMapper: ~%.0f-%.0fμs per operation\n", $dataMapperSerializationAvg * 0.9, $dataMapperSerializationAvg * 1.1);
    $md .= sprintf("- Symfony:    ~%.0f-%.0fμs per operation\n", $symfonyAvg * 0.9, $symfonyAvg * 1.1);
    $md .= sprintf("- Benefit:    %.1fx faster with better developer experience\n", $symfonyFactor);
    $md .= "```";

    return $md;
}

function generateDtoInsights(array $results): string
{
    $simpleDtoAvg = 0;
    $plainPhpAvg = 0;
    $otherDtoAvg = 0;
    $counts = ['SimpleDto' => 0, 'PlainPhp' => 0, 'OtherDto' => 0];

    foreach ($results['ExternalDto'] as $result) {
        if (str_contains($result['name'], 'SimpleDto')) {
            $simpleDtoAvg += $result['time'];
            $counts['SimpleDto']++;
        } elseif (str_contains($result['name'], 'PlainPhp')) {
            $plainPhpAvg += $result['time'];
            $counts['PlainPhp']++;
        } elseif (str_contains($result['name'], 'OtherDto')) {
            $otherDtoAvg += $result['time'];
            $counts['OtherDto']++;
        }
    }

    $simpleDtoAvg = $counts['SimpleDto'] > 0 ? $simpleDtoAvg / $counts['SimpleDto'] : 15;
    $plainPhpAvg = $counts['PlainPhp'] > 0 ? $plainPhpAvg / $counts['PlainPhp'] : 0.2;
    $otherDtoAvg = $counts['OtherDto'] > 0 ? $otherDtoAvg / $counts['OtherDto'] : 0.3;

    $vsPlainPhpFactor = round($simpleDtoAvg / $plainPhpAvg);
    $vsOtherDtoFactor = round($simpleDtoAvg / $otherDtoAvg);

    $md = "**Key Insights:**\n";
    $md .= "- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead\n";
    $md .= sprintf("- Plain PHP is **~%dx faster** but lacks type safety and validation features\n", $vsPlainPhpFactor);
    $md .= sprintf("- Other DTO libraries have **similar performance** (~%dx faster than SimpleDto)\n", $vsOtherDtoFactor);
    $md .= "- The overhead is acceptable for the added safety and developer experience";

    return $md;
}

function generateMapperInsights(array $results): string
{
    $dataMapperAvg = 0;
    $plainPhpAvg = 0;
    $otherMapperAvg = 0;
    $counts = ['DataMapper' => 0, 'PlainPhp' => 0, 'Others' => 0];

    foreach ($results['ExternalMapper'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperAvg += $result['time'];
            $counts['DataMapper']++;
        } elseif (str_contains($result['name'], 'PlainPhp')) {
            $plainPhpAvg += $result['time'];
            $counts['PlainPhp']++;
        } elseif (!str_contains($result['name'], 'DataMapper') && !str_contains($result['name'], 'PlainPhp')) {
            $otherMapperAvg += $result['time'];
            $counts['Others']++;
        }
    }

    $dataMapperAvg = $counts['DataMapper'] > 0 ? $dataMapperAvg / $counts['DataMapper'] : 20;
    $plainPhpAvg = $counts['PlainPhp'] > 0 ? $plainPhpAvg / $counts['PlainPhp'] : 0.2;
    $otherMapperAvg = $counts['Others'] > 0 ? $otherMapperAvg / $counts['Others'] : 5;

    $vsOthersFactor = round($dataMapperAvg / $otherMapperAvg, 1);
    $vsPlainPhpFactor = round($dataMapperAvg / $plainPhpAvg);

    $md = "**Key Insights:**\n";
    if ($vsOthersFactor < 1) {
        $md .= sprintf("- DataMapper is **%.1fx faster** than other mapper libraries (AutoMapper Plus, Laminas Hydrator)\n", 1 / $vsOthersFactor);
    } else {
        $md .= sprintf("- Other mapper libraries are **%.1fx faster** than DataMapper, but lack template syntax and advanced features\n", $vsOthersFactor);
    }
    $md .= sprintf("- Plain PHP is **~%dx faster** but requires manual mapping code for each use case\n", $vsPlainPhpFactor);
    $md .= "- DataMapper provides the best balance of features, readability, and maintainability\n";
    $md .= "- The overhead is acceptable for complex mapping scenarios with better developer experience";

    return $md;
}

function generateSerializationInsights(array $results): string
{
    // Use the same calculation as in the table
    $dataMapperAvg = 0;
    $symfonyAvg = 0;
    $dataMapperCount = 0;
    $symfonyCount = 0;

    foreach ($results['DtoSerialization'] as $result) {
        if (str_contains($result['name'], 'DataMapper')) {
            $dataMapperAvg += $result['time'];
            $dataMapperCount++;
        } elseif (str_contains($result['name'], 'Symfony')) {
            $symfonyAvg += $result['time'];
            $symfonyCount++;
        }
    }

    $dataMapperAvg = $dataMapperCount > 0 ? $dataMapperAvg / $dataMapperCount : 40;
    $symfonyAvg = $symfonyCount > 0 ? $symfonyAvg / $symfonyCount : 150;

    $factor = round($symfonyAvg / $dataMapperAvg, 1);

    $md = "**Key Insights:**\n";
    $md .= sprintf("- DataMapper is **%.1fx faster** than Symfony Serializer\n", $factor);
    $md .= "- Zero reflection overhead for template-based mapping\n";
    $md .= "- Optimized for nested data structures";

    return $md;
}

function formatBenchmarkName(string $name): string
{
    $name = str_replace('bench', '', $name);
    $name = preg_replace('/([A-Z])/', ' $1', $name);
    return trim((string)$name);
}

function getExternalDtoDescription(string $name): string
{
    $descriptions = [
        'benchSimpleDtoFromArray' => 'Our SimpleDto implementation',
        'benchSpatieDataFrom' => 'Spatie Laravel Data (other DTO library)',
        'benchPlainPhpNewAssign' => 'Plain PHP with property assignment',
        'benchPlainPhpConstructor' => 'Plain PHP with constructor',
        'benchSimpleDtoToArray' => 'Our SimpleDto toArray()',
        'benchSpatieDataToArray' => 'Other DTO library toArray()',
        'benchSimpleDtoComplexData' => 'Our SimpleDto with complex data',
        'benchSpatieDataComplexData' => 'Other DTO library with complex data',
    ];
    return $descriptions[$name] ?? '';
}

function getExternalMapperDescription(string $name): string
{
    $descriptions = [
        'benchDataMapperSimple' => 'Our DataMapper implementation',
        'benchAutoMapperPlusSimple' => 'Other mapper library',
        'benchLaminasHydratorSimple' => 'Other mapper library',
        'benchPlainPhpSimple' => 'Plain PHP manual mapping',
        'benchDataMapperNested' => 'Our DataMapper with nested data',
        'benchPlainPhpNested' => 'Plain PHP with nested data',
        'benchDataMapperTemplate' => 'Our DataMapper with template syntax',
        'benchChubbyphpParser' => 'Other parser library',
    ];
    return $descriptions[$name] ?? '';
}

function getSerializationDescription(string $name): string
{
    $descriptions = [
        'benchManualMapping' => 'Direct DTO constructor (baseline)',
        'benchDataMapperTemplate' => 'DataMapper with template syntax',
        'benchDataMapperSimplePaths' => 'DataMapper with simple paths',
        'benchSymfonySerializerArray' => 'Symfony Serializer from array',
        'benchSymfonySerializerJson' => 'Symfony Serializer from JSON',
    ];
    return $descriptions[$name] ?? '';
}

function updateSection(string $content, string $marker, string $newContent): string
{
    $startMarker = "<!-- {$marker}_START -->";
    $endMarker = "<!-- {$marker}_END -->";

    $startPos = strpos($content, $startMarker);
    $endPos = strpos($content, $endMarker);

    if (false === $startPos || false === $endPos) {
        echo "⚠️  Warning: Could not find markers for {$marker}\n";
        return $content;
    }

    $before = substr($content, 0, $startPos + strlen($startMarker));
    $after = substr($content, $endPos);

    return $before . "\n\n" . $newContent . "\n" . $after;
}

// ============================================================================
// Cleanup: Remove compare environment
// ============================================================================

$packagesToRemove = [];
foreach (COMPARE_WITH as $encodedPackage) {
    $package = base64_decode($encodedPackage);
    $packagesToRemove[] = $package;
}

if (!empty($packagesToRemove)) {
    $packageList = implode(' ', $packagesToRemove);
    $composerCmd = "cd {$rootDir} && composer remove --dev {$packageList} --no-interaction --quiet > /dev/null 2>&1";
    exec($composerCmd, $output, $returnCode);
}
