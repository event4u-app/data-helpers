<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('SimpleDTOCastsEdgeCases', function(): void {
    describe('Cast Combinations', function(): void {
        it('handles multiple casts in one DTO', function(): void {
            $dto = new class(
                createdAt: new DateTimeImmutable('2024-01-01'),
                price: '99.99',
                quantity: 5,
                active: true,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt,
                    public readonly string $price,
                    public readonly int $quantity,
                    public readonly bool $active,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'createdAt' => 'datetime',
                        'price' => 'decimal:2',
                        'quantity' => 'integer',
                        'active' => 'boolean',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'createdAt' => '2024-06-15 10:30:00',
                'price' => '149.99',
                'quantity' => '10',
                'active' => 1,
            ]);

            expect($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->format('Y-m-d'))->toBe('2024-06-15')
                ->and($instance->price)->toBe('149.99')
                ->and($instance->quantity)->toBe(10)
                ->and($instance->active)->toBeTrue();
        });

        it('handles datetime with custom format and decimal together', function(): void {
            $dto = new class(
                date: new DateTimeImmutable(),
                amount: '0.00',
            ) extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $date,
                    public readonly string $amount,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'date' => 'datetime:Y-m-d',
                        'amount' => 'decimal:4',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'date' => '2024-12-25',
                'amount' => '1234.5678',
            ]);

            expect($instance->date->format('Y-m-d'))->toBe('2024-12-25')
                ->and($instance->amount)->toBe('1234.5678');
        });

        it('handles json and array casts together', function(): void {
            $dto = new class(
                config: [],
                metadata: [],
            ) extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $config,
                    public readonly array $metadata,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'config' => 'json',
                        'metadata' => 'array',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'config' => '{"key":"value","nested":{"foo":"bar"}}',
                'metadata' => ['tag1', 'tag2', 'tag3'],
            ]);

            expect($instance->config)->toBe(['key' => 'value', 'nested' => ['foo' => 'bar']])
                ->and($instance->metadata)->toBe(['tag1', 'tag2', 'tag3']);
        });
    });

    describe('Cast Error Handling', function(): void {
        it('handles invalid datetime format gracefully', function(): void {
            $dto = new class(
                date: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?DateTimeImmutable $date,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['date' => 'datetime'];
                }
            };

            // DateTimeCast throws exception for invalid format - we expect this
            // PHP 8.3+ throws DateMalformedStringException, PHP 8.2 throws Exception
            $expectedException = PHP_VERSION_ID >= 80300 ? DateMalformedStringException::class : Exception::class;
            expect(fn(): object => $dto::fromArray(['date' => 'invalid-date-format']))
                ->toThrow($expectedException);
        });

        it('handles invalid json gracefully', function(): void {
            $dto = new class(
                data: null,
            ) extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly ?array $data,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['data' => 'json'];
                }
            };

            $instance = $dto::fromArray(['data' => '{invalid json}']);

            // JsonCast returns null for invalid JSON
            expect($instance->data)->toBeNull();
        });

        it('handles non-numeric string for integer cast', function(): void {
            $dto = new class(
                count: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?int $count,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['count' => 'integer'];
                }
            };

            $instance = $dto::fromArray(['count' => 'not-a-number']);

            // IntegerCast returns null for non-numeric strings
            expect($instance->count)->toBeNull();
        });

        it('handles non-numeric string for float cast', function(): void {
            $dto = new class(
                value: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?float $value,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['value' => 'float'];
                }
            };

            $instance = $dto::fromArray(['value' => 'not-a-number']);

            // FloatCast returns null for non-numeric strings
            expect($instance->value)->toBeNull();
        });

        it('handles empty string for decimal cast', function(): void {
            $dto = new class(
                price: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?string $price,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['price' => 'decimal:2'];
                }
            };

            $instance = $dto::fromArray(['price' => '']);

            // DecimalCast returns null for empty string
            expect($instance->price)->toBeNull();
        });

        it('handles array for string cast', function(): void {
            $dto = new class(
                text: null,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?string $text,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['text' => 'string'];
                }
            };

            $instance = $dto::fromArray(['text' => ['array', 'value']]);

            // StringCast returns null for arrays
            expect($instance->text)->toBeNull();
        });

        it('handles object for boolean cast', function(): void {
            $dto = new class(
                flag: false,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly bool $flag,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['flag' => 'boolean'];
                }
            };

            $instance = $dto::fromArray(['flag' => new stdClass()]);

            expect($instance->flag)->toBeTrue();
        });
    });

    describe('Null Handling', function(): void {
        it('handles null for all cast types', function(): void {
            $dto = new class(
                date: null,
                price: null,
                count: null,
                active: null,
                data: null,
                text: null,
            ) extends SimpleDTO {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly ?DateTimeImmutable $date,
                    public readonly ?string $price,
                    public readonly ?int $count,
                    public readonly ?bool $active,
                    public readonly ?array $data,
                    public readonly ?string $text,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'date' => 'datetime',
                        'price' => 'decimal:2',
                        'count' => 'integer',
                        'active' => 'boolean',
                        'data' => 'json',
                        'text' => 'string',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'date' => null,
                'price' => null,
                'count' => null,
                'active' => null,
                'data' => null,
                'text' => null,
            ]);

            expect($instance->date)->toBeNull()
                ->and($instance->price)->toBeNull()
                ->and($instance->count)->toBeNull()
                ->and($instance->active)->toBeNull()
                ->and($instance->data)->toBeNull()
                ->and($instance->text)->toBeNull();
        });
    });

    describe('Special Values', function(): void {
        it('handles empty string for various casts', function(): void {
            $dto = new class(
                count: null,
                value: null,
                flag: false,
                text: '',
            ) extends SimpleDTO {
                public function __construct(
                    public readonly ?int $count,
                    public readonly ?float $value,
                    public readonly bool $flag,
                    public readonly string $text,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'count' => 'integer',
                        'value' => 'float',
                        'flag' => 'boolean',
                        'text' => 'string',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'count' => '',
                'value' => '',
                'flag' => '',
                'text' => '',
            ]);

            // Empty strings return null for int/float, false for bool, empty string for string
            expect($instance->count)->toBeNull()
                ->and($instance->value)->toBeNull()
                ->and($instance->flag)->toBeFalse()
                ->and($instance->text)->toBe('');
        });

        it('handles zero values correctly', function(): void {
            $dto = new class(
                count: 0,
                value: 0.0,
                flag: false,
                price: '0.00',
            ) extends SimpleDTO {
                public function __construct(
                    public readonly int $count,
                    public readonly float $value,
                    public readonly bool $flag,
                    public readonly string $price,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'count' => 'integer',
                        'value' => 'float',
                        'flag' => 'boolean',
                        'price' => 'decimal:2',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'count' => 0,
                'value' => 0.0,
                'flag' => 0,
                'price' => '0.00',
            ]);

            expect($instance->count)->toBe(0)
                ->and($instance->value)->toBe(0.0)
                ->and($instance->flag)->toBeFalse()
                ->and($instance->price)->toBe('0.00');
        });

        it('handles false values correctly', function(): void {
            $dto = new class(
                flag1: false,
                flag2: false,
                flag3: false,
            ) extends SimpleDTO {
                public function __construct(
                    public readonly bool $flag1,
                    public readonly bool $flag2,
                    public readonly bool $flag3,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'flag1' => 'boolean',
                        'flag2' => 'boolean',
                        'flag3' => 'boolean',
                    ];
                }
            };

            $instance = $dto::fromArray([
                'flag1' => false,
                'flag2' => 0,
                'flag3' => '',
            ]);

            expect($instance->flag1)->toBeFalse()
                ->and($instance->flag2)->toBeFalse()
                ->and($instance->flag3)->toBeFalse();
        });
    });
});
