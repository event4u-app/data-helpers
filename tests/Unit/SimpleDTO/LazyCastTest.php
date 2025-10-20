<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('SimpleDTO Lazy Cast Resolution', function(): void {
    describe('Skip Missing Properties', function(): void {
        it('does not apply casts to missing properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly ?int $age = null,
                    public readonly ?string $email = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'name' => 'string',
                        'age' => 'int',
                        'email' => 'string',
                    ];
                }
            };

            // Only provide 'name', skip 'age' and 'email'
            $instance = $dto::fromArray(['name' => 'John']);

            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBeNull()
                ->and($instance->email)->toBeNull();
        });

        it('applies casts only to present properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?int $count = null,
                    public readonly ?float $price = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'count' => 'int',
                        'price' => 'float',
                    ];
                }
            };

            // Only provide 'count'
            $instance = $dto::fromArray(['count' => '42']);

            expect($instance->count)->toBe(42)
                ->and($instance->price)->toBeNull();
        });
    });

    describe('Skip Null Values', function(): void {
        it('does not apply casts to null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly ?int $age = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'name' => 'string',
                        'age' => 'int',
                    ];
                }
            };

            // Provide null values explicitly
            $instance = $dto::fromArray(['name' => null, 'age' => null]);

            expect($instance->name)->toBeNull()
                ->and($instance->age)->toBeNull();
        });

        it('applies casts to non-null values only', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly ?int $age = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'name' => 'string',
                        'age' => 'int',
                    ];
                }
            };

            // Mix of null and non-null
            $instance = $dto::fromArray(['name' => 'John', 'age' => null]);

            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBeNull();
        });
    });

    describe('Performance Optimization', function(): void {
        it('is faster with lazy cast resolution', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $field1 = null,
                    public readonly ?string $field2 = null,
                    public readonly ?string $field3 = null,
                    public readonly ?string $field4 = null,
                    public readonly ?string $field5 = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'field1' => 'string',
                        'field2' => 'string',
                        'field3' => 'string',
                        'field4' => 'string',
                        'field5' => 'string',
                    ];
                }
            };

            // Only provide 1 field out of 5
            $start = microtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto::fromArray(['field1' => 'value']);
            }
            $duration = microtime(true) - $start;

            // Should complete quickly (< 100ms for 1000 iterations)
            expect($duration)->toBeLessThan(0.1);
        });

        it('handles large DTOs efficiently', function(): void {
            $properties = [];
            $casts = [];
            for ($i = 1; 50 >= $i; $i++) {
                $properties['field' . $i] = null;
                $casts['field' . $i] = 'string';
            }

            $dto = new class(...$properties) extends SimpleDTO {
                public function __construct(
                    public readonly ?string $field1 = null,
                    public readonly ?string $field2 = null,
                    public readonly ?string $field3 = null,
                    public readonly ?string $field4 = null,
                    public readonly ?string $field5 = null,
                    public readonly ?string $field6 = null,
                    public readonly ?string $field7 = null,
                    public readonly ?string $field8 = null,
                    public readonly ?string $field9 = null,
                    public readonly ?string $field10 = null,
                    public readonly ?string $field11 = null,
                    public readonly ?string $field12 = null,
                    public readonly ?string $field13 = null,
                    public readonly ?string $field14 = null,
                    public readonly ?string $field15 = null,
                    public readonly ?string $field16 = null,
                    public readonly ?string $field17 = null,
                    public readonly ?string $field18 = null,
                    public readonly ?string $field19 = null,
                    public readonly ?string $field20 = null,
                    public readonly ?string $field21 = null,
                    public readonly ?string $field22 = null,
                    public readonly ?string $field23 = null,
                    public readonly ?string $field24 = null,
                    public readonly ?string $field25 = null,
                    public readonly ?string $field26 = null,
                    public readonly ?string $field27 = null,
                    public readonly ?string $field28 = null,
                    public readonly ?string $field29 = null,
                    public readonly ?string $field30 = null,
                    public readonly ?string $field31 = null,
                    public readonly ?string $field32 = null,
                    public readonly ?string $field33 = null,
                    public readonly ?string $field34 = null,
                    public readonly ?string $field35 = null,
                    public readonly ?string $field36 = null,
                    public readonly ?string $field37 = null,
                    public readonly ?string $field38 = null,
                    public readonly ?string $field39 = null,
                    public readonly ?string $field40 = null,
                    public readonly ?string $field41 = null,
                    public readonly ?string $field42 = null,
                    public readonly ?string $field43 = null,
                    public readonly ?string $field44 = null,
                    public readonly ?string $field45 = null,
                    public readonly ?string $field46 = null,
                    public readonly ?string $field47 = null,
                    public readonly ?string $field48 = null,
                    public readonly ?string $field49 = null,
                    public readonly ?string $field50 = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    $casts = [];
                    for ($i = 1; 50 >= $i; $i++) {
                        $casts['field' . $i] = 'string';
                    }
                    return $casts;
                }
            };

            // Only provide 5 fields out of 50
            $data = [
                'field1' => 'value1',
                'field10' => 'value10',
                'field20' => 'value20',
                'field30' => 'value30',
                'field50' => 'value50',
            ];

            $start = microtime(true);
            for ($i = 0; 100 > $i; $i++) {
                $dto::fromArray($data);
            }
            $duration = microtime(true) - $start;

            // Should complete quickly (< 50ms for 100 iterations)
            expect($duration)->toBeLessThan(0.05);
        });
    });

    describe('Cast Statistics', function(): void {
        it('provides cast statistics', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly ?int $age = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'name' => 'string',
                        'age' => 'int',
                    ];
                }
            };

            $instance = $dto::fromArray(['name' => 'John']);

            $stats = $instance->getCastStatistics();

            expect($stats)->toHaveKey('total')
                ->and($stats)->toHaveKey('casted')
                ->and($stats)->toHaveKey('uncasted');
        });
    });

    describe('Mixed Scenarios', function(): void {
        it('handles mix of present, null, and missing properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $field1 = null,
                    public readonly ?string $field2 = null,
                    public readonly ?string $field3 = null,
                    public readonly ?string $field4 = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'field1' => 'string',
                        'field2' => 'string',
                        'field3' => 'string',
                        'field4' => 'string',
                    ];
                }
            };

            // field1: present with value
            // field2: present but null
            // field3: missing
            // field4: missing
            $instance = $dto::fromArray([
                'field1' => 'value1',
                'field2' => null,
            ]);

            expect($instance->field1)->toBe('value1')
                ->and($instance->field2)->toBeNull()
                ->and($instance->field3)->toBeNull()
                ->and($instance->field4)->toBeNull();
        });

        it('works with complex casts', function(): void {
            $dto = new class extends SimpleDTO {
/** @phpstan-ignore-next-line argument.type (Lazy cast test) */
                public function __construct(
                    public readonly ?array $data = null,
                    public readonly ?string $json = null,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'data' => 'array',
                        'json' => 'json',
                    ];
                }
            };

            // Only provide 'data'
            $instance = $dto::fromArray(['data' => ['key' => 'value']]);

            expect($instance->data)->toBe(['key' => 'value'])
                ->and($instance->json)->toBeNull();
        });
    });
});

