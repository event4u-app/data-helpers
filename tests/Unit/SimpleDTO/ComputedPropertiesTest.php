<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;

describe('Computed Properties', function(): void {
    describe('Basic Computed Properties', function(): void {
        it('includes computed property in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $price = 100.0,
                    public readonly int $quantity = 2,
                ) {}

                #[Computed]
                public function total(): float
                {
                    return $this->price * $this->quantity;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('price');
            expect($array)->toHaveKey('quantity');
            expect($array)->toHaveKey('total');
            expect($array['total'])->toBe(200.0);
        });

        it('includes computed property in JSON', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $price = 100.0,
                    public readonly int $quantity = 2,
                ) {}

                #[Computed]
                public function total(): float
                {
                    return $this->price * $this->quantity;
                }
            };

            $instance = $dto::fromArray([]);
            $json = json_encode($instance);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('total');
            expect($decoded['total'])->toBe(200);
        });

        it('can call computed property directly', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $price = 100.0,
                    public readonly int $quantity = 2,
                ) {}

                #[Computed]
                public function total(): float
                {
                    return $this->price * $this->quantity;
                }
            };

            $instance = $dto::fromArray([]);

            expect($instance->total())->toBe(200.0);
        });

        it('supports multiple computed properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $price = 100.0,
                    public readonly int $quantity = 2,
                    public readonly float $taxRate = 0.19,
                ) {}

                #[Computed]
                public function subtotal(): float
                {
                    return $this->price * $this->quantity;
                }

                #[Computed]
                public function tax(): float
                {
                    return $this->subtotal() * $this->taxRate;
                }

                #[Computed]
                public function total(): float
                {
                    return $this->subtotal() + $this->tax();
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('subtotal');
            expect($array)->toHaveKey('tax');
            expect($array)->toHaveKey('total');
            expect($array['subtotal'])->toBe(200.0);
            expect($array['tax'])->toBe(38.0);
            expect($array['total'])->toBe(238.0);
        });
    });

    describe('Custom Names', function(): void {
        it('uses custom name for computed property', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $amount = 1000.0,
                ) {}

                #[Computed(name: 'totalAmount')]
                public function calculateTotal(): float
                {
                    return $this->amount * 1.19;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('totalAmount');
            expect($array)->not()->toHaveKey('calculateTotal');
            expect($array['totalAmount'])->toBe(1190.0);
        });

        it('supports multiple computed properties with custom names', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly float $price = 100.0,
                    public readonly int $quantity = 2,
                ) {}

                #[Computed(name: 'orderSubtotal')]
                public function calculateSubtotal(): float
                {
                    return $this->price * $this->quantity;
                }

                #[Computed(name: 'orderTotal')]
                public function calculateTotal(): float
                {
                    return $this->calculateSubtotal() * 1.19;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('orderSubtotal');
            expect($array)->toHaveKey('orderTotal');
            expect($array['orderSubtotal'])->toBe(200.0);
            expect($array['orderTotal'])->toBe(238.0);
        });
    });

    describe('Lazy Computed Properties', function(): void {
        it('does not include lazy computed property by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}

                #[Computed(lazy: true)]
                public function expensiveCalculation(): string
                {
                    return strtoupper($this->name);
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not()->toHaveKey('expensiveCalculation');
        });

        it('includes lazy computed property when explicitly requested', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}

                #[Computed(lazy: true)]
                public function expensiveCalculation(): string
                {
                    return strtoupper($this->name);
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->includeComputed(['expensiveCalculation'])->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('expensiveCalculation');
            expect($array['expensiveCalculation'])->toBe('TEST');
        });

        it('supports multiple lazy computed properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}

                #[Computed(lazy: true)]
                public function uppercase(): string
                {
                    return strtoupper($this->name);
                }

                #[Computed(lazy: true)]
                public function lowercase(): string
                {
                    return strtolower($this->name);
                }
            };

            $instance = $dto::fromArray([]);

            // Include only one
            $array1 = $instance->includeComputed(['uppercase'])->toArray();
            expect($array1)->toHaveKey('uppercase');
            expect($array1)->not()->toHaveKey('lowercase');

            // Include both
            $array2 = $instance->includeComputed(['uppercase', 'lowercase'])->toArray();
            expect($array2)->toHaveKey('uppercase');
            expect($array2)->toHaveKey('lowercase');
        });

        it('works with JSON serialization', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}

                #[Computed(lazy: true)]
                public function uppercase(): string
                {
                    return strtoupper($this->name);
                }
            };

            $instance = $dto::fromArray([]);

            // Without include
            $json1 = json_encode($instance);
            assert(is_string($json1));
            $decoded1 = json_decode($json1, true);
            expect($decoded1)->not()->toHaveKey('uppercase');

            // With include
            $json2 = json_encode($instance->includeComputed(['uppercase']));
            assert(is_string($json2));
            $decoded2 = json_decode($json2, true);
            expect($decoded2)->toHaveKey('uppercase');
            expect($decoded2['uppercase'])->toBe('TEST');
        });
    });

    describe('Caching', function(): void {
        it('caches computed property values', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: true)]
                public function expensive(): int
                {
                    self::$callCount++;

                    return $this->value * 2;
                }
            };

            $dto::$callCount = 0;
            $instance = $dto::fromArray([]);

            // First toArray() call
            $instance->toArray();

            expect($dto::$callCount)->toBe(1);

            // Second toArray() call - should use cache
            $instance->toArray();
            expect($dto::$callCount)->toBe(1);

            // Third toArray() call - should still use cache
            $instance->toArray();
            expect($dto::$callCount)->toBe(1);
        });

        it('can clear cache for specific property', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: true)]
                public function expensive(): int
                {
                    self::$callCount++;

                    return $this->value * 2;
                }
            };

            $dto::$callCount = 0;
            $instance = $dto::fromArray([]);

            // First call
            $instance->toArray();

            expect($dto::$callCount)->toBe(1);

            // Clear cache
            $instance->clearComputedCache('expensive');

            // Should recompute
            $instance->toArray();

            expect($dto::$callCount)->toBe(2);
        });

        it('can clear all cache', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount1 = 0;
                public static int $callCount2 = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: true)]
                public function expensive1(): int
                {
                    self::$callCount1++;

                    return $this->value * 2;
                }

                #[Computed(cache: true)]
                public function expensive2(): int
                {
                    self::$callCount2++;

                    return $this->value * 3;
                }
            };

            $dto::$callCount1 = 0;
            $dto::$callCount2 = 0;
            $instance = $dto::fromArray([]);

            // First call
            $instance->toArray();

            expect($dto::$callCount1)->toBe(1);
            expect($dto::$callCount2)->toBe(1);

            // Clear all cache
            $instance->clearComputedCache();

            // Should recompute both
            $instance->toArray();

            expect($dto::$callCount1)->toBe(2);
            expect($dto::$callCount2)->toBe(2);
        });
    });
});
