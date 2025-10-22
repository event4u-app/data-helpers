<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

// Helper function for test setup
// Needed because Pest 2.x doesn't inherit beforeEach from outer describe blocks
function setupBenchmark(): void
{
    // Clear benchmark results before each test
    $dto = new class extends SimpleDTO {
        public function __construct(
            public readonly string $name = '',
        ) {}
    };
    $dto::clearBenchmarkResults();
}

describe('SimpleDTO Benchmarking', function(): void {
    beforeEach(function(): void {
        // Clear benchmark results before each test
        $dto = new class extends SimpleDTO {
            public function __construct(
                public readonly string $name = '',
            ) {}
        };
        $dto::clearBenchmarkResults();
    });

    describe('Benchmark Instantiation', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('benchmarks DTO instantiation', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::benchmarkInstantiation(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('duration')
                ->and($results)->toHaveKey('memory')
                ->and($results)->toHaveKey('throughput')
                ->and($results)->toHaveKey('avgDuration')
                ->and($results)->toHaveKey('avgMemory')
                ->and($results['duration'])->toBeGreaterThan(0)
                ->and($results['throughput'])->toBeGreaterThan(0);
        });

        it('completes quickly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $results = $dto::benchmarkInstantiation(['name' => 'John'], 1000);

            // Should complete in less than 100ms for 1000 iterations
            expect($results['duration'])->toBeLessThan(0.1);
        });
    });

    describe('Benchmark toArray', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('benchmarks toArray serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::benchmarkToArray(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('duration')
                ->and($results)->toHaveKey('memory')
                ->and($results)->toHaveKey('throughput')
                ->and($results['duration'])->toBeGreaterThan(0)
                ->and($results['throughput'])->toBeGreaterThan(0);
        });
    });

    describe('Benchmark JSON Serialization', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('benchmarks JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::benchmarkJsonSerialize(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('duration')
                ->and($results)->toHaveKey('memory')
                ->and($results)->toHaveKey('throughput')
                ->and($results['duration'])->toBeGreaterThan(0)
                ->and($results['throughput'])->toBeGreaterThan(0);
        });
    });

    describe('Benchmark Validation', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('benchmarks validation', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::benchmarkValidation(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('duration')
                ->and($results)->toHaveKey('memory')
                ->and($results)->toHaveKey('throughput')
                ->and($results['duration'])->toBeGreaterThan(0)
                ->and($results['throughput'])->toBeGreaterThan(0);
        });
    });

    describe('Benchmark Suite', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('runs comprehensive benchmark suite', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::runBenchmarkSuite(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('instantiation')
                ->and($results)->toHaveKey('toArray')
                ->and($results)->toHaveKey('jsonSerialize')
                ->and($results['instantiation'])->toHaveKey('duration')
                ->and($results['toArray'])->toHaveKey('duration')
                ->and($results['jsonSerialize'])->toHaveKey('duration');
        });

        it('stores results', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $dto::runBenchmarkSuite(['name' => 'John'], 100);

            $allResults = $dto::getBenchmarkResults();

            expect($allResults)->toHaveKey($dto::class)
                ->and($allResults[$dto::class])->toHaveKey('instantiation');
        });
    });

    describe('Cache Performance Comparison', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('compares performance with and without cache', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $results = $dto::compareCachePerformance(['name' => 'John', 'age' => 30], 100);

            expect($results)->toHaveKey('withCache')
                ->and($results)->toHaveKey('withoutCache')
                ->and($results)->toHaveKey('speedup')
                ->and($results['speedup'])->toHaveKey('duration')
                ->and($results['speedup'])->toHaveKey('memory');
        });
    });

    describe('Benchmark Report', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('generates benchmark report', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $results = $dto::runBenchmarkSuite(['name' => 'John'], 100);
            /** @phpstan-ignore-next-line unknown */
            $report = $dto::generateBenchmarkReport($results);

            expect($report)->toBeString()
                ->and($report)->toContain('Benchmark Report')
                ->and($report)->toContain('Instantiation')
                ->and($report)->toContain('Duration')
                ->and($report)->toContain('Memory')
                ->and($report)->toContain('Throughput');
        });
    });

    describe('Benchmark Results Management', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('clears benchmark results', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}
            };

            $dto::runBenchmarkSuite(['name' => 'John'], 100);

            $resultsBefore = $dto::getBenchmarkResults();
            expect($resultsBefore)->not->toBeEmpty();

            $dto::clearBenchmarkResults();

            $resultsAfter = $dto::getBenchmarkResults();
            expect($resultsAfter)->toBeEmpty();
        });
    });

    describe('Complex DTO Benchmarking', function(): void {
        beforeEach(function(): void {
            setupBenchmark();
        });

        it('benchmarks complex DTO with nested structures', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly array $tags = [],
                    public readonly ?string $email = null,
                ) {}
            };

            $data = [
                'name' => 'John Doe',
                'age' => 30,
                'tags' => ['php', 'laravel', 'symfony'],
                'email' => 'john@example.com',
            ];

            $results = $dto::runBenchmarkSuite($data, 100);

            expect($results)->toHaveKey('instantiation')
                ->and($results['instantiation']['throughput'])->toBeGreaterThan(0);
        });
    });
});
