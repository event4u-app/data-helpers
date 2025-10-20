<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\BooleanCast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDTO\Casts\DecimalCast;
use event4u\DataHelpers\SimpleDTO\Casts\FloatCast;
use event4u\DataHelpers\SimpleDTO\Casts\IntegerCast;
use event4u\DataHelpers\SimpleDTO\Casts\JsonCast;
use event4u\DataHelpers\SimpleDTO\Casts\StringCast;

// Test DTOs
class UserDtoWithCasts extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_active,
        public readonly array $roles,
        public readonly DateTimeImmutable $created_at,
    ) {}

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'roles' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

class ProductDtoWithCasts extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $in_stock,
        public readonly ?DateTimeImmutable $available_from = null,
    ) {}

    protected function casts(): array
    {
        return [
            'in_stock' => BooleanCast::class,
            'available_from' => DateTimeCast::class,
        ];
    }
}

class EventDtoWithCasts extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly DateTimeImmutable $event_date,
    ) {}

    protected function casts(): array
    {
        return [
            'event_date' => DateTimeCast::class . ':Y-m-d',
        ];
    }
}

describe('SimpleDTO Casts', function(): void {
    describe('Built-in Boolean Cast', function(): void {
        it('casts string "1" to true', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => '1',
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->is_active)->toBeTrue();
        });

        it('casts string "0" to false', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => '0',
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->is_active)->toBeFalse();
        });

        it('casts string "true" to true', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => 'true',
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->is_active)->toBeTrue();
        });

        it('casts string "yes" to true', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => 'yes',
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->is_active)->toBeTrue();
        });

        it('casts integer 1 to true', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => 1,
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->is_active)->toBeTrue();
        });
    });

    describe('Built-in Array Cast', function(): void {
        it('casts JSON string to array', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => '["admin","editor"]',
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->roles)
                ->toBeArray()
                ->toBe(['admin', 'editor']);
        });

        it('keeps array as array', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => ['admin', 'editor'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            expect($dto->roles)
                ->toBeArray()
                ->toBe(['admin', 'editor']);
        });
    });

    describe('Built-in DateTime Cast', function(): void {
        it('casts string to DateTimeImmutable', function(): void {
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => ['admin'],
                'created_at' => '2024-01-15 10:30:00',
            ]);

            expect($dto->created_at)
                ->toBeInstanceOf(DateTimeImmutable::class)
                ->and($dto->created_at->format('Y-m-d H:i:s'))
                ->toBe('2024-01-15 10:30:00');
        });

        it('casts timestamp to DateTimeImmutable', function(): void {
            $timestamp = 1705318200; // 2024-01-15 10:30:00 UTC
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => ['admin'],
                'created_at' => $timestamp,
            ]);

            expect($dto->created_at)
                ->toBeInstanceOf(DateTimeImmutable::class)
                ->and($dto->created_at->getTimestamp())
                ->toBe($timestamp);
        });

        it('keeps DateTimeImmutable as DateTimeImmutable', function(): void {
            $date = new DateTimeImmutable('2024-01-15 10:30:00');
            $dto = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => ['admin'],
                'created_at' => $date,
            ]);

            expect($dto->created_at)
                ->toBeInstanceOf(DateTimeImmutable::class)
                ->toBe($date);
        });
    });

    describe('Custom Cast Classes', function(): void {
        it('uses BooleanCast class', function(): void {
            $dto = ProductDtoWithCasts::fromArray([
                'name' => 'Laptop',
                'in_stock' => 'yes',
            ]);

            expect($dto->in_stock)->toBeTrue();
        });

        it('uses DateTimeCast class', function(): void {
            $dto = ProductDtoWithCasts::fromArray([
                'name' => 'Laptop',
                'in_stock' => true,
                'available_from' => '2024-02-01 00:00:00',
            ]);

            expect($dto->available_from)
                ->toBeInstanceOf(DateTimeImmutable::class)
                ->and($dto->available_from->format('Y-m-d'))
                ->toBe('2024-02-01');
        });

        it('handles null values', function(): void {
            $dto = ProductDtoWithCasts::fromArray([
                'name' => 'Laptop',
                'in_stock' => true,
                'available_from' => null,
            ]);

            expect($dto->available_from)->toBeNull();
        });
    });

    describe('Cast with Parameters', function(): void {
        it('uses custom date format', function(): void {
            $dto = EventDtoWithCasts::fromArray([
                'title' => 'Conference',
                'event_date' => '2024-06-15',
            ]);

            expect($dto->event_date)
                ->toBeInstanceOf(DateTimeImmutable::class)
                ->and($dto->event_date->format('Y-m-d'))
                ->toBe('2024-06-15');
        });
    });

    describe('Cast Caching', function(): void {
        it('caches cast instances', function(): void {
            $dto1 = UserDtoWithCasts::fromArray([
                'name' => 'John',
                'is_active' => true,
                'roles' => ['admin'],
                'created_at' => '2024-01-01 12:00:00',
            ]);

            $dto2 = UserDtoWithCasts::fromArray([
                'name' => 'Jane',
                'is_active' => false,
                'roles' => ['editor'],
                'created_at' => '2024-01-02 12:00:00',
            ]);

            // Both DTOs should work correctly
            expect($dto1->name)->toBe('John');
            expect($dto2->name)->toBe('Jane');
            expect($dto1->is_active)->toBeTrue();
            expect($dto2->is_active)->toBeFalse();
        });
    });

    describe('No Casts Defined', function(): void {
        it('works without casts', function(): void {
            // Create a simple DTO without casts
            $simpleDtoClass = new class ('John', 'john@example.com') extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                ) {}
            };

            $dto = $simpleDtoClass::fromArray([
                'name' => 'Jane',
                'email' => 'jane@example.com',
            ]);

            // Should work normally without casts
            expect($dto->name)->toBe('Jane');
            expect($dto->email)->toBe('jane@example.com');
        });
    });

    describe('Integer Cast', function(): void {
        it('casts string to integer', function(): void {
            $dto = new class ('Product', 10) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $quantity,
                ) {}

                protected function casts(): array
                {
                    return ['quantity' => 'integer'];
                }
            };

            $result = $dto::fromArray(['name' => 'Product', 'quantity' => '42']);
            expect($result->quantity)->toBe(42);
        });

        it('casts float to integer', function(): void {
            $dto = new class ('Product', 10) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $quantity,
                ) {}

                protected function casts(): array
                {
                    return ['quantity' => IntegerCast::class];
                }
            };

            $result = $dto::fromArray(['name' => 'Product', 'quantity' => 42.7]);
            expect($result->quantity)->toBe(42);
        });
    });

    describe('Float Cast', function(): void {
        it('casts string to float', function(): void {
            $dto = new class ('Product', 0.0) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly float $price,
                ) {}

                protected function casts(): array
                {
                    return ['price' => 'float'];
                }
            };

            $result = $dto::fromArray(['name' => 'Product', 'price' => '99.99']);
            expect($result->price)->toBe(99.99);
        });

        it('casts integer to float', function(): void {
            $dto = new class ('Product', 0.0) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly float $price,
                ) {}

                protected function casts(): array
                {
                    return ['price' => FloatCast::class];
                }
            };

            $result = $dto::fromArray(['name' => 'Product', 'price' => 100]);
            expect($result->price)->toBe(100.0);
        });
    });

    describe('String Cast', function(): void {
        it('casts integer to string', function(): void {
            $dto = new class ('') extends SimpleDTO {
                public function __construct(
                    public readonly string $code,
                ) {}

                protected function casts(): array
                {
                    return ['code' => 'string'];
                }
            };

            $result = $dto::fromArray(['code' => 12345]);
            expect($result->code)->toBe('12345');
        });

        it('casts boolean to string', function(): void {
            $dto = new class ('') extends SimpleDTO {
                public function __construct(
                    public readonly string $value,
                ) {}

                protected function casts(): array
                {
                    return ['value' => StringCast::class];
                }
            };

            $result = $dto::fromArray(['value' => true]);
            expect($result->value)->toBe('1');
        });
    });

    describe('Decimal Cast', function(): void {
        it('formats number with 2 decimal places', function(): void {
            $dto = new class ('') extends SimpleDTO {
                public function __construct(
                    public readonly string $price,
                ) {}

                protected function casts(): array
                {
                    return ['price' => 'decimal:2'];
                }
            };

            $result = $dto::fromArray(['price' => 99.9]);
            expect($result->price)->toBe('99.90');
        });

        it('formats number with 4 decimal places', function(): void {
            $dto = new class ('') extends SimpleDTO {
                public function __construct(
                    public readonly string $rate,
                ) {}

                protected function casts(): array
                {
                    return ['rate' => DecimalCast::class . ':4'];
                }
            };

            $result = $dto::fromArray(['rate' => 0.12345]);
            expect($result->rate)->toBe('0.1235');
        });
    });

    describe('Json Cast', function(): void {
        it('decodes JSON string to array', function(): void {
            $dto = new class ([]) extends SimpleDTO {
                public function __construct(
                    public readonly array $metadata,
                ) {}

                protected function casts(): array
                {
                    return ['metadata' => 'json'];
                }
            };

            $result = $dto::fromArray(['metadata' => '{"key":"value","count":42}']);
            expect($result->metadata)
                ->toBeArray()
                ->toBe(['key' => 'value', 'count' => 42]);
        });

        it('keeps array as array', function(): void {
            $dto = new class ([]) extends SimpleDTO {
                public function __construct(
                    public readonly array $metadata,
                ) {}

                protected function casts(): array
                {
                    return ['metadata' => JsonCast::class];
                }
            };

            $result = $dto::fromArray(['metadata' => ['key' => 'value']]);
            expect($result->metadata)
                ->toBeArray()
                ->toBe(['key' => 'value']);
        });
    });

    describe('Cast Edge Cases', function(): void {
        it('handles null values with nullable types', function(): void {
            $dto = new class(null, null, null) extends SimpleDTO {
                public function __construct(
                    public readonly ?bool $active,
                    public readonly ?int $count,
                    public readonly ?string $name,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'active' => 'boolean',
                        'count' => 'integer',
                        'name' => 'string',
                    ];
                }
            };

            $result = $dto::fromArray(['active' => null, 'count' => null, 'name' => null]);
            expect($result->active)->toBeNull()
                ->and($result->count)->toBeNull()
                ->and($result->name)->toBeNull();
        });

        it('handles empty strings correctly', function(): void {
            $dtoClass = new class('', 0, 0.0) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly ?int $count,
                    public readonly ?float $price,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'name' => 'string',
                        'count' => 'integer',
                        'price' => 'float',
                    ];
                }
            };

            $result = $dtoClass::fromArray(['name' => '', 'count' => '', 'price' => '']);
            expect($result->name)->toBe('')
                ->and($result->count)->toBeNull()  // Empty string is not numeric, so it becomes null
                ->and($result->price)->toBeNull(); // Empty string is not numeric, so it becomes null
        });

        it('handles negative numbers correctly', function(): void {
            $dto = new class(-42, -42.5, '-42.50') extends SimpleDTO {
                public function __construct(
                    public readonly int $temperature,
                    public readonly float $balance,
                    public readonly string $formatted,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'temperature' => 'integer',
                        'balance' => 'float',
                        'formatted' => 'decimal:2',
                    ];
                }
            };

            $result = $dto::fromArray(['temperature' => '-42', 'balance' => '-42.5', 'formatted' => -42.5]);
            expect($result->temperature)->toBe(-42)
                ->and($result->balance)->toBe(-42.5)
                ->and($result->formatted)->toBe('-42.50');
        });

        it('handles very large numbers', function(): void {
            $dto = new class(2147483647, 9999999999.99) extends SimpleDTO {
                public function __construct(
                    public readonly int $maxInt,
                    public readonly float $largeFloat,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'maxInt' => 'integer',
                        'largeFloat' => 'float',
                    ];
                }
            };

            $result = $dto::fromArray(['maxInt' => '2147483647', 'largeFloat' => '9999999999.99']);
            expect($result->maxInt)->toBe(2147483647)
                ->and($result->largeFloat)->toBe(9999999999.99);
        });

        it('handles boolean edge cases', function(): void {
            $dto = new class(false, false, false, false) extends SimpleDTO {
                public function __construct(
                    public readonly bool $empty,
                    public readonly bool $false,
                    public readonly bool $no,
                    public readonly bool $off,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'empty' => 'boolean',
                        'false' => 'boolean',
                        'no' => 'boolean',
                        'off' => 'boolean',
                    ];
                }
            };

            $result = $dto::fromArray(['empty' => '', 'false' => 'false', 'no' => 'no', 'off' => 'off']);
            expect($result->empty)->toBeFalse()
                ->and($result->false)->toBeFalse()
                ->and($result->no)->toBeFalse()
                ->and($result->off)->toBeFalse();
        });

        it('handles DateTime edge cases', function(): void {
            $dto = new class(new DateTimeImmutable(), new DateTimeImmutable()) extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $epoch,
                    public readonly DateTimeImmutable $iso,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'epoch' => 'datetime',
                        'iso' => 'datetime',
                    ];
                }
            };

            $result = $dto::fromArray(['epoch' => 0, 'iso' => '2024-01-17T12:00:00+00:00']);
            expect($result->epoch)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($result->epoch->getTimestamp())->toBe(0)
                ->and($result->iso)->toBeInstanceOf(DateTimeImmutable::class);
        });

        it('handles JSON edge cases', function(): void {
            $dto = new class([], [], []) extends SimpleDTO {
                public function __construct(
                    public readonly array $emptyObject,
                    public readonly array $emptyArray,
                    public readonly array $nested,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'emptyObject' => 'json',
                        'emptyArray' => 'json',
                        'nested' => 'json',
                    ];
                }
            };

            $result = $dto::fromArray([
                'emptyObject' => '{}',
                'emptyArray' => '[]',
                'nested' => '{"user":{"name":"John","age":30}}',
            ]);
            expect($result->emptyObject)->toBe([])
                ->and($result->emptyArray)->toBe([])
                ->and($result->nested)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });

        it('handles decimal rounding correctly', function(): void {
            $dto = new class('0.00', '43.00') extends SimpleDTO {
                public function __construct(
                    public readonly string $zero,
                    public readonly string $rounded,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'zero' => 'decimal:2',
                        'rounded' => 'decimal:2',
                    ];
                }
            };

            $result = $dto::fromArray(['zero' => 0, 'rounded' => 42.999]);
            expect($result->zero)->toBe('0.00')
                ->and($result->rounded)->toBe('43.00');
        });
    });
});

