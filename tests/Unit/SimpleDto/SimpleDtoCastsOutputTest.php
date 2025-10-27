<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use Tests\Unit\SimpleDto\Fixtures\StatusEnum;

describe('SimpleDtoCastsOutput', function(): void {
    describe('DateTime Output Cast', function(): void {
        it('converts DateTime to string in toArray', function(): void {
            $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends SimpleDto {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['createdAt' => 'datetime'];
                }
            };

            $array = $dto->toArray();

            expect($array)->toHaveKey('createdAt')
                ->and($array['createdAt'])->toBeString()
                ->and($array['createdAt'])->toBe('2024-01-15 10:30:00');
        });

        it('converts DateTime with custom format in toArray', function(): void {
            $dto = new class(new DateTimeImmutable('2024-06-25')) extends SimpleDto {
                public function __construct(
                    public readonly DateTimeImmutable $date,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['date' => 'datetime:Y-m-d'];
                }
            };

            $array = $dto->toArray();

            expect($array['date'])->toBe('2024-06-25');
        });

        it('converts DateTime in jsonSerialize', function(): void {
            $dto = new class(new DateTimeImmutable('2024-12-31 23:59:59')) extends SimpleDto {
                public function __construct(
                    public readonly DateTimeImmutable $timestamp,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['timestamp' => 'datetime'];
                }
            };

            $json = json_encode($dto);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('timestamp')
                ->and($decoded['timestamp'])->toBe('2024-12-31 23:59:59');
        });
    });

    describe('Decimal Output Cast', function(): void {
        it('keeps decimal format in toArray', function(): void {
            $dto = new class('99.99') extends SimpleDto {
                public function __construct(
                    public readonly string $price,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['price' => 'decimal:2'];
                }
            };

            $array = $dto->toArray();

            expect($array['price'])->toBe('99.99');
        });

        it('formats decimal with different precision in toArray', function(): void {
            $dto = new class('1234.5678') extends SimpleDto {
                public function __construct(
                    public readonly string $amount,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['amount' => 'decimal:4'];
                }
            };

            $array = $dto->toArray();

            expect($array['amount'])->toBe('1234.5678');
        });
    });

    describe('Boolean Output Cast', function(): void {
        it('converts boolean to int in toArray', function(): void {
            $dto = new class(true) extends SimpleDto {
                public function __construct(
                    public readonly bool $active,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['active' => 'boolean'];
                }
            };

            $array = $dto->toArray();

            expect($array['active'])->toBe(1);
        });

        it('converts false to 0 in toArray', function(): void {
            $dto = new class(false) extends SimpleDto {
                public function __construct(
                    public readonly bool $active,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['active' => 'boolean'];
                }
            };

            $array = $dto->toArray();

            expect($array['active'])->toBe(0);
        });
    });

    describe('Integer Output Cast', function(): void {
        it('keeps integer in toArray', function(): void {
            $dto = new class(42) extends SimpleDto {
                public function __construct(
                    public readonly int $count,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['count' => 'integer'];
                }
            };

            $array = $dto->toArray();

            expect($array['count'])->toBe(42);
        });
    });

    describe('Float Output Cast', function(): void {
        it('keeps float in toArray', function(): void {
            $dto = new class(3.14159) extends SimpleDto {
                public function __construct(
                    public readonly float $pi,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['pi' => 'float'];
                }
            };

            $array = $dto->toArray();

            expect($array['pi'])->toBe(3.14159);
        });
    });

    describe('String Output Cast', function(): void {
        it('keeps string in toArray', function(): void {
            $dto = new class('Hello World') extends SimpleDto {
                public function __construct(
                    public readonly string $message,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['message' => 'string'];
                }
            };

            $array = $dto->toArray();

            expect($array['message'])->toBe('Hello World');
        });
    });

    describe('Array Output Cast', function(): void {
        it('converts array to json string in toArray', function(): void {
            $dto = new class(['a', 'b', 'c']) extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $tags,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['tags' => 'array'];
                }
            };

            $array = $dto->toArray();

            // ArrayCast converts arrays to JSON strings in set()
            expect($array['tags'])->toBeString()
                ->and($array['tags'])->toBe('["a","b","c"]');
        });
    });

    describe('Json Output Cast', function(): void {
        it('converts array to json string in toArray', function(): void {
            $dto = new class(['key' => 'value', 'nested' => ['foo' => 'bar']]) extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $config,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['config' => 'json'];
                }
            };

            $array = $dto->toArray();

            expect($array['config'])->toBeString()
                ->and($array['config'])->toBe('{"key":"value","nested":{"foo":"bar"}}');
        });
    });

    describe('Enum Output Cast', function(): void {
        it('converts backed string enum to value in toArray', function(): void {
            $dto = new class(StatusEnum::ACTIVE) extends SimpleDto {
                public function __construct(
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['status' => 'enum:Tests\Unit\SimpleDto\Fixtures\StatusEnum'];
                }
            };

            $array = $dto->toArray();

            expect($array['status'])->toBe('active');
        });
    });

    describe('Multiple Output Casts', function(): void {
        it('applies all output casts in toArray', function(): void {
            $dto = new class(
                createdAt: new DateTimeImmutable('2024-01-01 12:00:00'),
                price: '99.99',
                active: true,
                status: StatusEnum::PENDING,
            ) extends SimpleDto {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt,
                    public readonly string $price,
                    public readonly bool $active,
                    public readonly StatusEnum $status,
                ) {}

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'createdAt' => 'datetime',
                        'price' => 'decimal:2',
                        'active' => 'boolean',
                        'status' => 'enum:Tests\Unit\SimpleDto\Fixtures\StatusEnum',
                    ];
                }
            };

            $array = $dto->toArray();

            expect($array['createdAt'])->toBe('2024-01-01 12:00:00')
                ->and($array['price'])->toBe('99.99')
                ->and($array['active'])->toBe(1)
                ->and($array['status'])->toBe('pending');
        });
    });
});
