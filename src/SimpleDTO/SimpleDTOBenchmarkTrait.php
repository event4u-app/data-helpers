<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;
use Exception;

/**
 * Provides benchmarking capabilities for SimpleDTO.
 *
 * This trait allows you to measure performance of various DTO operations
 * including instantiation, serialization, validation, and more.
 */
trait SimpleDTOBenchmarkTrait
{
    /** @var array<class-string, array<string, array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}>> */
    private static array $benchmarkResults = [];

    /**
     * Benchmark DTO instantiation.
     *
     * @param array<string, mixed> $data Data to use for instantiation
     * @param int $iterations Number of iterations
     * @return array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}
     */
    public static function benchmarkInstantiation(array $data, int $iterations = 1000): array
    {
        $memoryBefore = memory_get_usage();
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            static::fromArray($data);
        }

        $duration = microtime(true) - $start;
        $memoryAfter = memory_get_usage();

        return [
            'duration' => $duration,
            'memory' => $memoryAfter - $memoryBefore,
            'throughput' => $iterations / $duration,
            'avgDuration' => $duration / $iterations,
            'avgMemory' => ($memoryAfter - $memoryBefore) / $iterations,
        ];
    }

    /**
     * Benchmark toArray() serialization.
     *
     * @param array<string, mixed> $data Data to use for instantiation
     * @param int $iterations Number of iterations
     * @return array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}
     */
    public static function benchmarkToArray(array $data, int $iterations = 1000): array
    {
        $dto = static::fromArray($data);

        $memoryBefore = memory_get_usage();
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $dto->toArray();
        }

        $duration = microtime(true) - $start;
        $memoryAfter = memory_get_usage();

        return [
            'duration' => $duration,
            'memory' => $memoryAfter - $memoryBefore,
            'throughput' => $iterations / $duration,
            'avgDuration' => $duration / $iterations,
            'avgMemory' => ($memoryAfter - $memoryBefore) / $iterations,
        ];
    }

    /**
     * Benchmark JSON serialization.
     *
     * @param array<string, mixed> $data Data to use for instantiation
     * @param int $iterations Number of iterations
     * @return array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}
     */
    public static function benchmarkJsonSerialize(array $data, int $iterations = 1000): array
    {
        $dto = static::fromArray($data);

        $memoryBefore = memory_get_usage();
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            json_encode($dto);
        }

        $duration = microtime(true) - $start;
        $memoryAfter = memory_get_usage();

        return [
            'duration' => $duration,
            'memory' => $memoryAfter - $memoryBefore,
            'throughput' => $iterations / $duration,
            'avgDuration' => $duration / $iterations,
            'avgMemory' => ($memoryAfter - $memoryBefore) / $iterations,
        ];
    }

    /**
     * Benchmark validation.
     *
     * @param array<string, mixed> $data Data to use for instantiation
     * @param int $iterations Number of iterations
     * @return array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}
     */
    public static function benchmarkValidation(array $data, int $iterations = 1000): array
    {
        $memoryBefore = memory_get_usage();
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            try {
                static::fromArray($data);
            } catch (Exception) {
                // Ignore validation errors
            }
        }

        $duration = microtime(true) - $start;
        $memoryAfter = memory_get_usage();

        return [
            'duration' => $duration,
            'memory' => $memoryAfter - $memoryBefore,
            'throughput' => $iterations / $duration,
            'avgDuration' => $duration / $iterations,
            'avgMemory' => ($memoryAfter - $memoryBefore) / $iterations,
        ];
    }

    /**
     * Run a comprehensive benchmark suite.
     *
     * @param array<string, mixed> $data Data to use for benchmarking
     * @param int $iterations Number of iterations per benchmark
     * @return array<string, array{duration: float, memory: int, throughput: float}>
     */
    public static function runBenchmarkSuite(array $data, int $iterations = 1000): array
    {
        $results = [
            'instantiation' => static::benchmarkInstantiation($data, $iterations),
            'toArray' => static::benchmarkToArray($data, $iterations),
            'jsonSerialize' => static::benchmarkJsonSerialize($data, $iterations),
        ];

        self::$benchmarkResults[static::class] = $results;

        return $results;
    }

    /**
     * Compare performance with and without cache.
     *
     * @param array<string, mixed> $data Data to use for benchmarking
     * @param int $iterations Number of iterations
     * @return array{withCache: array{duration: float, memory: int, throughput: float}, withoutCache: array{duration: float, memory: int, throughput: float}, speedup: array{duration: float, memory: float, throughput: float}}
     */
    public static function compareCachePerformance(array $data, int $iterations = 1000): array
    {
        // With cache
        static::warmUpCache();
        $withCache = static::benchmarkInstantiation($data, $iterations);

        // Without cache
        static::clearPerformanceCache();
        $withoutCache = static::benchmarkInstantiation($data, $iterations);

        return [
            'withCache' => $withCache,
            'withoutCache' => $withoutCache,
            'speedup' => [
                'duration' => $withoutCache['duration'] / $withCache['duration'],
                'memory' => 0 < $withCache['memory'] ? $withoutCache['memory'] / $withCache['memory'] : 1.0,
                'throughput' => $withCache['throughput'] / $withoutCache['throughput'],
            ],
        ];
    }

    /**
     * Generate a benchmark report.
     *
     * @param array<string, array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}> $results Benchmark results
     * @return string Formatted report
     */
    public static function generateBenchmarkReport(array $results): string
    {
        $report = "=== Benchmark Report ===\n\n";
        $report .= "Class: " . static::class . "\n\n";

        foreach ($results as $operation => $metrics) {
            $report .= ucfirst($operation) . ":\n";
            $report .= "  Duration: " . number_format($metrics['duration'] * 1000, 2) . " ms\n";
            $report .= "  Memory: " . number_format($metrics['memory'] / 1024, 2) . " KB\n";
            $report .= "  Throughput: " . number_format($metrics['throughput']) . " ops/sec\n";
            $report .= "  Avg Duration: " . number_format($metrics['avgDuration'] * 1000000, 2) . " μs\n";
            $report .= "  Avg Memory: " . number_format($metrics['avgMemory']) . " bytes\n";
            $report .= "\n";
        }

        return $report;
    }

    /**
     * Get all benchmark results.
     *
     * @return array<class-string, array<string, array{duration: float, memory: int, throughput: float, avgDuration: float, avgMemory: float}>>
     */
    public static function getBenchmarkResults(): array
    {
        return self::$benchmarkResults;
    }

    /** Clear all benchmark results. */
    public static function clearBenchmarkResults(): void
    {
        self::$benchmarkResults = [];
    }
}
