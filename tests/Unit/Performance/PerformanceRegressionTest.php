<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

/**
 * Performance Regression Tests
 *
 * These tests ensure that performance optimizations from Phase 4 are maintained
 * and that no regressions are introduced in future changes.
 *
 * Baseline values are from Phase 4 optimizations (2025-01-18).
 */
describe('Performance Regression Tests', function(): void {
    describe('DataAccessor Performance', function(): void {
        it('maintains simple get performance', function(): void {
            $data = ['user' => ['name' => 'John', 'age' => 30]];
            $accessor = new DataAccessor($data);

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $accessor->get('user.name');
            }
            $duration = microtime(true) - $start;

            // Baseline: 0.39 μs per operation = 0.39ms for 1000 operations
            // Allow 50% margin: 0.585ms
            expect($duration)->toBeLessThan(0.001);
        });

        it('maintains wildcard get performance', function(): void {
            $data = [
                'users' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                    ['name' => 'Charlie', 'age' => 35],
                ],
            ];
            $accessor = new DataAccessor($data);

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $accessor->get('users.*.name');
            }
            $duration = microtime(true) - $start;

            // Baseline: 0.88 μs per operation = 0.88ms for 1000 operations
            // Allow 50% margin: 1.32ms
            expect($duration)->toBeLessThan(0.002);
        });

        it('maintains deep wildcard get performance', function(): void {
            $data = [
                'companies' => [
                    [
                        'departments' => [
                            ['employees' => [['name' => 'Alice'], ['name' => 'Bob']]],
                            ['employees' => [['name' => 'Charlie'], ['name' => 'David']]],
                        ],
                    ],
                    [
                        'departments' => [
                            ['employees' => [['name' => 'Eve'], ['name' => 'Frank']]],
                            ['employees' => [['name' => 'Grace'], ['name' => 'Henry']]],
                        ],
                    ],
                ],
            ];
            $accessor = new DataAccessor($data);

            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $accessor->get('companies.*.departments.*.employees.*.name');
            }
            $duration = microtime(true) - $start;

            // Baseline: 38.34 μs per operation = 3.834ms for 100 operations
            // Allow 50% margin: 5.75ms
            expect($duration)->toBeLessThan(0.006);
        });
    });

    describe('DataMutator Performance', function(): void {
        it('maintains simple set performance', function(): void {
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $data = ['name' => 'Alice', 'age' => 30];
                DataMutator::make($data)->set('age', 31);
            }
            $duration = microtime(true) - $start;

            // Baseline: 1.15 μs per operation = 1.15ms for 1000 operations
            // Allow 50% margin: 1.725ms
            expect($duration)->toBeLessThan(0.002);
        });

        it('maintains wildcard set performance', function(): void {
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $data = [
                    'users' => [
                        ['name' => 'Alice', 'age' => 30],
                        ['name' => 'Bob', 'age' => 25],
                        ['name' => 'Charlie', 'age' => 35],
                    ],
                ];
                DataMutator::make($data)->set('users.*.age', 40);
            }
            $duration = microtime(true) - $start;

            // Baseline: 1.89 μs per operation = 1.89ms for 1000 operations
            // Allow 50% margin: 2.835ms
            expect($duration)->toBeLessThan(0.003);
        });

        it('maintains deep wildcard set performance', function(): void {
            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $data = [
                    'companies' => [
                        [
                            'departments' => [
                                ['employees' => [['name' => 'Alice'], ['name' => 'Bob']]],
                                ['employees' => [['name' => 'Charlie'], ['name' => 'David']]],
                            ],
                        ],
                        [
                            'departments' => [
                                ['employees' => [['name' => 'Eve'], ['name' => 'Frank']]],
                                ['employees' => [['name' => 'Grace'], ['name' => 'Henry']]],
                            ],
                        ],
                    ],
                ];
                DataMutator::make($data)->set('companies.*.departments.*.employees.*.active', true);
            }
            $duration = microtime(true) - $start;

            // Baseline: 5.25 μs per operation = 0.525ms for 100 operations
            // Allow 50% margin: 0.7875ms
            expect($duration)->toBeLessThan(0.001);
        });
    });

    describe('SimpleDto Performance', function(): void {
        it('maintains fromArray performance', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            $data = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto::fromArray($data);
            }
            $duration = microtime(true) - $start;

            // Baseline: 8.47 μs per operation = 8.47ms for 1000 operations
            // Allow 50% margin: 12.7ms
            expect($duration)->toBeLessThan(0.013);
        });

        it('maintains toArray performance', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30, 'email' => 'john@example.com']);

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $instance->toArray();
            }
            $duration = microtime(true) - $start;

            // Baseline: 57.51 μs per operation = 57.51ms for 1000 operations
            // Allow 50% margin: 86.3ms
            expect($duration)->toBeLessThan(0.09);
        });
    });

    describe('LiteDto Performance', function(): void {
        it('maintains from performance', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $data = ['name' => 'John', 'age' => 30];

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto::from($data);
            }
            $duration = microtime(true) - $start;

            // Baseline: 4.26 μs per operation = 4.26ms for 1000 operations
            // Allow 50% margin: 6.39ms
            expect($duration)->toBeLessThan(0.007);
        });

        it('maintains toArray performance', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $instance = $dto::from(['name' => 'John', 'age' => 30]);

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $instance->toArray();
            }
            $duration = microtime(true) - $start;

            // Baseline: 8.52 μs per operation = 8.52ms for 1000 operations
            // Allow 50% margin: 12.78ms
            expect($duration)->toBeLessThan(0.013);
        });
    });

    describe('Complex Scenarios Performance', function(): void {
        it('maintains collection performance', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $data = array_map(
                fn($i) => ['name' => 'User ' . $i, 'age' => 20 + $i],
                range(1, 100)
            );

            $start = microtime(true);
            for ($i = 0; 50 > $i; $i++) {
                foreach ($data as $item) {
                    $dto::fromArray($item);
                }
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 50ms for 50 iterations of 100 items
            expect($duration)->toBeLessThan(0.05);
        });

        it('maintains large array performance', function(): void {
            $dto = new class extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $data = [],
                ) {}
            };

            $largeArray = array_fill(0, 1000, 'test');

            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $dto::fromArray(['data' => $largeArray]);
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 20ms for 100 iterations
            expect($duration)->toBeLessThan(0.02);
        });
    });
});

