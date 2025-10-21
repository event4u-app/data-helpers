<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('Performance & Stress Testing', function(): void {
    describe('Performance Tests', function(): void {
        it('creates DTOs quickly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto::fromArray(['name' => 'User ' . $i, 'age' => 20 + $i]);
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 15ms for 1000 iterations (increased by 50%)
            expect($duration)->toBeLessThan(0.015);
        });

        it('serializes to array quickly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30]);

            $start = microtime(true);
            for ($i = 0; 10000 > $i; $i++) {
                $instance->toArray();
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 75ms for 10000 iterations (increased by 50%)
            expect($duration)->toBeLessThan(0.075);
        });

        it('serializes to JSON quickly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30]);

            $start = microtime(true);
            for ($i = 0; 10000 > $i; $i++) {
                json_encode($instance);
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 75ms for 10000 iterations (increased by 50%)
            expect($duration)->toBeLessThan(0.075);
        });

        it('handles complex DTOs efficiently', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                    public readonly array $tags = [],
                    public readonly ?string $description = null,
                ) {}
            };

            $data = [
                'name' => 'John Doe',
                'age' => 30,
                'email' => 'john@example.com',
                'tags' => ['php', 'laravel', 'symfony'],
                'description' => 'A test user',
            ];

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto::fromArray($data);
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 30ms for 1000 iterations (increased by 50%)
            expect($duration)->toBeLessThan(0.03);
        });

        it('handles nested DTOs efficiently', function(): void {
            $addressDto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $street = '',
                    public readonly string $city = '',
                ) {}
            };

            $userDto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?object $address = null,
                ) {}
            };

            $address = $addressDto::fromArray(['street' => 'Main St', 'city' => 'NYC']);
            $data = [
                'name' => 'John',
                'address' => $address,
            ];

            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $userDto::fromArray($data);
            }
            $duration = microtime(true) - $start;

            // Should complete in less than 30ms for 1000 iterations (increased by 50%)
            expect($duration)->toBeLessThan(0.03);
        });
    });

    describe('Memory Usage Tests', function(): void {
        it('does not leak memory on repeated instantiation', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $memoryBefore = memory_get_usage();

            for ($i = 0; 10000 > $i; $i++) {
                $dto::fromArray(['name' => 'User ' . $i, 'age' => 20 + $i]);
            }

            // Force garbage collection
            gc_collect_cycles();

            $memoryAfter = memory_get_usage();
            $memoryIncrease = $memoryAfter - $memoryBefore;

            // Memory increase should be reasonable (less than 5MB for 10000 instances)
            expect($memoryIncrease)->toBeLessThan(5 * 1024 * 1024);
        });

        it('uses reasonable memory per instance', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $memoryBefore = memory_get_usage();

            $instances = [];
            for ($i = 0; 1000 > $i; $i++) {
                $instances[] = $dto::fromArray(['name' => 'User ' . $i, 'age' => 20 + $i]);
            }

            $memoryAfter = memory_get_usage();
            $memoryPerInstance = ($memoryAfter - $memoryBefore) / 1000;

            // Each instance should use less than 2KB
            expect($memoryPerInstance)->toBeLessThan(2048);
        });

        it('handles large arrays efficiently', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $data = [],
                ) {}
            };

            $largeArray = array_fill(0, 1000, 'test');

            $memoryBefore = memory_get_usage();

            $instance = $dto::fromArray(['data' => $largeArray]);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Should not use excessive memory
            expect($memoryUsed)->toBeLessThan(100 * 1024); // Less than 100KB
        });
    });

    describe('Stress Tests', function(): void {
        it('handles 10000 instantiations', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $start = microtime(true);

            for ($i = 0; 10000 > $i; $i++) {
                $dto::fromArray(['name' => 'User ' . $i, 'age' => 20 + ($i % 50)]);
            }

            $duration = microtime(true) - $start;

            // Should complete in less than 150ms (increased by 50%)
            expect($duration)->toBeLessThan(0.15);
        });

        it('handles 100000 toArray calls', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30]);

            $start = microtime(true);

            for ($i = 0; 100000 > $i; $i++) {
                $instance->toArray();
            }

            $duration = microtime(true) - $start;

            // Should complete in less than 750ms (increased by 50%)
            expect($duration)->toBeLessThan(0.75);
        });

        it('handles 100000 JSON serializations', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'John', 'age' => 30]);

            $start = microtime(true);

            for ($i = 0; 100000 > $i; $i++) {
                json_encode($instance);
            }

            $duration = microtime(true) - $start;

            // Should complete in less than 750ms (increased by 50%)
            expect($duration)->toBeLessThan(0.75);
        });

        it('handles large batch processing', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                    public readonly string $email = '',
                ) {}
            };

            $start = microtime(true);

            $results = [];
            for ($i = 0; 5000 > $i; $i++) {
                $instance = $dto::fromArray([
                    'name' => 'User ' . $i,
                    'age' => 20 + ($i % 50),
                    'email' => sprintf('user%d@example.com', $i),
                ]);
                $results[] = json_encode($instance);
            }

            $duration = microtime(true) - $start;

            // Should complete in less than 150ms (increased by 50%)
            expect($duration)->toBeLessThan(0.15)
                ->and(count($results))->toBe(5000);
        });

        it('handles concurrent-like operations', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                    public readonly int $age = 0,
                ) {}
            };

            $start = microtime(true);

            // Simulate concurrent operations
            $operations = [];
            for ($i = 0; 1000 > $i; $i++) {
                $operations[] = function() use ($dto, $i) {
                    $instance = $dto::fromArray(['name' => 'User ' . $i, 'age' => 20 + $i]);
                    return json_encode($instance->toArray());
                };
            }

            // Execute all operations
            $results = array_map(fn($op) => $op(), $operations);

            $duration = microtime(true) - $start;

            // Should complete in less than 75ms (increased by 50%)
            expect($duration)->toBeLessThan(0.075)
                ->and(count($results))->toBe(1000);
        });
    });
});

