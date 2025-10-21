<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromJson;

describe('Computed Properties - Edge Cases', function(): void {
    describe('Exception Handling', function(): void {
        it('returns null when computed method throws exception', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function throwsException(): int
                {
                    throw new RuntimeException('Computation failed');
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('throwsException');
            expect($array['throwsException'])->toBeNull();
        });

        it('continues with other computed properties after exception', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function first(): int
                {
                    return $this->value * 2;
                }

                #[Computed]
                public function throwsException(): int
                {
                    throw new RuntimeException('Computation failed');
                }

                #[Computed]
                public function third(): int
                {
                    return $this->value * 3;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array['first'])->toBe(84);
            expect($array['throwsException'])->toBeNull();
            expect($array['third'])->toBe(126);
        });
    });

    describe('Null and Empty Returns', function(): void {
        it('handles computed method returning null', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?string $name = null,
                ) {}

                #[Computed]
                public function nullable(): ?string
                {
                    return $this->name;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('nullable');
            expect($array['nullable'])->toBeNull();
        });

        it('handles computed method returning empty array', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $items = [],
                ) {}

                /** @phpstan-ignore-next-line unknown */
                #[Computed]
                public function emptyArray(): array
                {
                    return $this->items;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('emptyArray');
            expect($array['emptyArray'])->toBe([]);
        });

        it('handles computed method returning empty string', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = '',
                ) {}

                #[Computed]
                public function emptyString(): string
                {
                    return $this->name;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('emptyString');
            expect($array['emptyString'])->toBe('');
        });

        it('handles computed method returning false', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly bool $active = false,
                ) {}

                #[Computed]
                public function isActive(): bool
                {
                    return $this->active;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('isActive');
            expect($array['isActive'])->toBeFalse();
        });

        it('handles computed method returning zero', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $count = 0,
                ) {}

                #[Computed]
                public function total(): int
                {
                    return $this->count;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('total');
            expect($array['total'])->toBe(0);
        });
    });

    describe('Visibility Integration', function(): void {
        it('hides computed property with Hidden attribute', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                /** @phpstan-ignore-next-line unknown */
                #[Hidden]
                public function secret(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->not()->toHaveKey('secret');
        });

        it('hides computed property from array with HiddenFromArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                /** @phpstan-ignore-next-line unknown */
                #[HiddenFromArray]
                public function secretFromArray(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);

            // Not in toArray()
            $array = $instance->toArray();
            expect($array)->not()->toHaveKey('secretFromArray');

            // But in JSON
            $json = json_encode($instance);
            assert(is_string($json));
            $decoded = json_decode($json, true);
            expect($decoded)->toHaveKey('secretFromArray');
            expect($decoded['secretFromArray'])->toBe(84);
        });

        it('hides computed property from JSON with HiddenFromJson', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                /** @phpstan-ignore-next-line unknown */
                #[HiddenFromJson]
                public function secretFromJson(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);

            // In toArray()
            $array = $instance->toArray();
            expect($array)->toHaveKey('secretFromJson');
            expect($array['secretFromJson'])->toBe(84);

            // Not in JSON
            $json = json_encode($instance);
            assert(is_string($json));
            $decoded = json_decode($json, true);
            expect($decoded)->not()->toHaveKey('secretFromJson');
        });

        it('respects only() with computed properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function double(): int
                {
                    return $this->value * 2;
                }

                #[Computed]
                public function triple(): int
                {
                    return $this->value * 3;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->only(['value', 'double'])->toArray();

            expect($array)->toHaveKey('value');
            expect($array)->toHaveKey('double');
            expect($array)->not()->toHaveKey('triple');
        });

        it('respects except() with computed properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function double(): int
                {
                    return $this->value * 2;
                }

                #[Computed]
                public function triple(): int
                {
                    return $this->value * 3;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->except(['triple'])->toArray();

            expect($array)->toHaveKey('value');
            expect($array)->toHaveKey('double');
            expect($array)->not()->toHaveKey('triple');
        });
    });

    describe('Different Return Types', function(): void {
        it('handles computed method returning array', function(): void {
            $dto = new class extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $items = [1, 2, 3],
                ) {}

                /** @phpstan-ignore-next-line unknown */
                #[Computed]
                public function stats(): array
                {
                    return [
                        'count' => count($this->items),
                        'sum' => array_sum($this->items),
                    ];
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array['stats'])->toBe(['count' => 3, 'sum' => 6]);
        });

        it('handles computed method returning object', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'Test',
                ) {}

                #[Computed]
                public function metadata(): object
                {
                    return (object)[
                        'name' => $this->name,
                        'length' => strlen($this->name),
                    ];
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array['metadata'])->toBeObject();
            $metadata = $array['metadata'];
            assert(is_object($metadata) && property_exists($metadata, 'name') && property_exists($metadata, 'length'));
            expect($metadata->name)->toBe('Test');
            expect($metadata->length)->toBe(4);
        });

        it('handles computed method returning nested DTO', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'John',
                ) {}

                /** @phpstan-ignore-next-line unknown */
                #[Computed]
                public function address(): array
                {
                    return [
                        'city' => 'Berlin',
                        'country' => 'Germany',
                    ];
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array['address'])->toBeArray();
            expect($array['address']['city'])->toBe('Berlin');
        });
    });

    describe('Caching Edge Cases', function(): void {
        it('caches lazy computed properties when included', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(lazy: true, cache: true)]
                public function expensive(): int
                {
                    self::$callCount++;

                    return $this->value * 2;
                }
            };

            $dto::$callCount = 0;
            $instance = $dto::fromArray([]);

            // First call with include
            $clone1 = $instance->includeComputed(['expensive']);
            $clone1->toArray();

            expect($dto::$callCount)->toBe(1);

            // Second call with include on same clone - should use cache
            $clone1->toArray();
            expect($dto::$callCount)->toBe(1);

            // Third call with include on original - creates new clone, recomputes
            $clone2 = $instance->includeComputed(['expensive']);
            $clone2->toArray();

            expect($dto::$callCount)->toBe(2);
        });

        it('does not cache when cache is disabled', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: false)]
                public function noCache(): int
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

            // Second call - should NOT use cache
            $instance->toArray();
            expect($dto::$callCount)->toBe(2);

            // Third call - should NOT use cache
            $instance->toArray();
            expect($dto::$callCount)->toBe(3);
        });

        it('hasComputedCache returns false for non-existent property', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function computed(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);

            expect($instance->hasComputedCache('nonExistent'))->toBeFalse();
        });

        it('hasComputedCache returns true after computation', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: true)]
                public function computed(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);

            expect($instance->hasComputedCache('computed'))->toBeFalse();

            $instance->toArray();

            expect($instance->hasComputedCache('computed'))->toBeTrue();
        });
    });

    describe('include() Edge Cases', function(): void {
        it('include with non-existent property does not cause error', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(lazy: true)]
                public function lazy(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->includeComputed(['nonExistent'])->toArray();

            expect($array)->toHaveKey('value');
            expect($array)->not()->toHaveKey('nonExistent');
        });

        it('include with non-lazy property does not cause error', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed]
                public function eager(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->includeComputed(['eager'])->toArray();

            // Eager is already included, include() has no effect
            expect($array)->toHaveKey('eager');
        });

        it('include with empty array does not cause error', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(lazy: true)]
                public function lazy(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->includeComputed([])->toArray();

            expect($array)->toHaveKey('value');
            expect($array)->not()->toHaveKey('lazy');
        });

        it('can chain multiple include calls', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(lazy: true)]
                public function lazy1(): int
                {
                    return $this->value * 2;
                }

                #[Computed(lazy: true)]
                public function lazy2(): int
                {
                    return $this->value * 3;
                }

                #[Computed(lazy: true)]
                public function lazy3(): int
                {
                    return $this->value * 4;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance
                ->includeComputed(['lazy1'])
                ->includeComputed(['lazy2'])
                ->includeComputed(['lazy3'])
                ->toArray();

            expect($array)->toHaveKey('lazy1');
            expect($array)->toHaveKey('lazy2');
            expect($array)->toHaveKey('lazy3');
        });
    });

    describe('Clone Behavior', function(): void {
        it('include creates a clone and does not modify original', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(lazy: true)]
                public function lazy(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);

            // Original should not include lazy
            $array1 = $instance->toArray();
            expect($array1)->not()->toHaveKey('lazy');

            // Clone should include lazy
            $array2 = $instance->includeComputed(['lazy'])->toArray();
            expect($array2)->toHaveKey('lazy');

            // Original should still not include lazy
            $array3 = $instance->toArray();
            expect($array3)->not()->toHaveKey('lazy');
        });

        it('cache is not shared between clones', function(): void {
            $dto = new class extends SimpleDTO {
                public static int $callCount = 0;

                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(cache: true)]
                public function computed(): int
                {
                    self::$callCount++;

                    return $this->value * 2;
                }
            };

            $dto::$callCount = 0;
            $instance = $dto::fromArray([]);

            // First instance computes
            $instance->toArray();

            expect($dto::$callCount)->toBe(1);

            // Clone should have its own cache
            $clone = clone $instance;
            $clone->toArray();

            expect($dto::$callCount)->toBe(2);
        });
    });

    describe('Name Conflicts', function(): void {
        it('computed property with custom name can override property name', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 42,
                ) {}

                #[Computed(name: 'value')]
                public function computedValue(): int
                {
                    return $this->value * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            // Computed property should override the regular property
            expect($array['value'])->toBe(84);
        });
    });

    describe('Performance', function(): void {
        it('handles many computed properties efficiently', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 1,
                ) {}

                #[Computed]
                public function c1(): int { return $this->value; }

                #[Computed]
                public function c2(): int { return $this->value * 2; }

                #[Computed]
                public function c3(): int { return $this->value * 3; }

                #[Computed]
                public function c4(): int { return $this->value * 4; }

                #[Computed]
                public function c5(): int { return $this->value * 5; }

                #[Computed]
                public function c6(): int { return $this->value * 6; }

                #[Computed]
                public function c7(): int { return $this->value * 7; }

                #[Computed]
                public function c8(): int { return $this->value * 8; }

                #[Computed]
                public function c9(): int { return $this->value * 9; }

                #[Computed]
                public function c10(): int { return $this->value * 10; }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array)->toHaveKey('c1');
            expect($array)->toHaveKey('c10');
            expect(count($array))->toBe(11); // value + 10 computed
        });

        it('handles recursive computed properties', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly int $value = 10,
                ) {}

                #[Computed]
                public function level1(): int
                {
                    return $this->value * 2;
                }

                #[Computed]
                public function level2(): int
                {
                    return $this->level1() * 2;
                }

                #[Computed]
                public function level3(): int
                {
                    return $this->level2() * 2;
                }
            };

            $instance = $dto::fromArray([]);
            $array = $instance->toArray();

            expect($array['level1'])->toBe(20);
            expect($array['level2'])->toBe(40);
            expect($array['level3'])->toBe(80);
        });
    });
});

