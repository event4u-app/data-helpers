<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Casters\DateTimeCaster;
use event4u\DataHelpers\LiteDto\Casters\DateTimeImmutableCaster;
use event4u\DataHelpers\LiteDto\LiteDto;

// Custom caster for testing
class UpperCaseCaster
{
    public static function cast(mixed $value): string
    {
        return strtoupper((string)$value);
    }
}

class JsonCaster
{
    /** @return array<mixed> */
    public static function cast(mixed $value): array
    {
        if (is_string($value)) {
            /** @var array<mixed> $decoded */
            $decoded = json_decode($value, true) ?? [];
            return $decoded;
        }
        return (array)$value;
    }
}

// Test DTOs
class CastWithUserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[CastWith(DateTimeCaster::class)]
        public readonly ?DateTime $createdAt,
    ) {}
}

class CastWithEventDto extends LiteDto
{
    public function __construct(
        public readonly string $title,
        #[CastWith(DateTimeImmutableCaster::class)]
        public readonly ?DateTimeImmutable $startDate,
        #[CastWith(DateTimeImmutableCaster::class)]
        public readonly ?DateTimeImmutable $endDate,
    ) {}
}

class CastWithCustomDto extends LiteDto
{
    /** @param array<mixed> $metadata */
    public function __construct(
        #[CastWith(UpperCaseCaster::class)]
        public readonly string $name,
        #[CastWith(JsonCaster::class)]
        public readonly array $metadata,
    ) {}
}

describe('CastWith Attribute', function(): void {
    describe('DateTimeCaster', function(): void {
        it('casts string to DateTime', function(): void {
            $dto = CastWithUserDto::from([
                'name' => 'John',
                'createdAt' => '2024-01-15 10:30:00',
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->createdAt)->toBeInstanceOf(DateTime::class);
            /** @var DateTime $createdAt */
            $createdAt = $dto->createdAt;
            expect($createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        it('handles null values', function(): void {
            $dto = CastWithUserDto::from([
                'name' => 'John',
                'createdAt' => null,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->createdAt)->toBeNull();
        });

        it('handles empty string', function(): void {
            $dto = CastWithUserDto::from([
                'name' => 'John',
                'createdAt' => '',
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->createdAt)->toBeNull();
        });

        it('handles ISO 8601 format', function(): void {
            $dto = CastWithUserDto::from([
                'name' => 'John',
                'createdAt' => '2024-01-15T10:30:00+00:00',
            ]);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            /** @var DateTime $createdAt */
            $createdAt = $dto->createdAt;
            expect($createdAt->format('Y-m-d'))->toBe('2024-01-15');
        });
    });

    describe('DateTimeImmutableCaster', function(): void {
        it('casts string to DateTimeImmutable', function(): void {
            $dto = CastWithEventDto::from([
                'title' => 'Conference',
                'startDate' => '2024-01-15 09:00:00',
                'endDate' => '2024-01-15 17:00:00',
            ]);

            expect($dto->title)->toBe('Conference')
                ->and($dto->startDate)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($dto->endDate)->toBeInstanceOf(DateTimeImmutable::class);
            /** @var DateTimeImmutable $startDate */
            $startDate = $dto->startDate;
            /** @var DateTimeImmutable $endDate */
            $endDate = $dto->endDate;
            expect($startDate->format('H:i'))->toBe('09:00')
                ->and($endDate->format('H:i'))->toBe('17:00');
        });

        it('handles null values', function(): void {
            $dto = CastWithEventDto::from([
                'title' => 'Conference',
                'startDate' => null,
                'endDate' => null,
            ]);

            expect($dto->title)->toBe('Conference')
                ->and($dto->startDate)->toBeNull()
                ->and($dto->endDate)->toBeNull();
        });
    });

    describe('Custom Casters', function(): void {
        it('uses custom UpperCaseCaster', function(): void {
            $dto = CastWithCustomDto::from([
                'name' => 'john doe',
                'metadata' => '{"key":"value"}',
            ]);

            expect($dto->name)->toBe('JOHN DOE')
                ->and($dto->metadata)->toBe(['key' => 'value']);
        });

        it('uses custom JsonCaster', function(): void {
            $dto = CastWithCustomDto::from([
                'name' => 'test',
                'metadata' => '{"foo":"bar","count":42}',
            ]);

            expect($dto->metadata)->toBe([
                'foo' => 'bar',
                'count' => 42,
            ]);
        });

        it('handles invalid JSON gracefully', function(): void {
            $dto = CastWithCustomDto::from([
                'name' => 'test',
                'metadata' => 'invalid json',
            ]);

            expect($dto->metadata)->toBe([]);
        });
    });

    describe('Multiple Casters', function(): void {
        it('applies different casters to different properties', function(): void {
            $dto = CastWithCustomDto::from([
                'name' => 'alice',
                'metadata' => '{"role":"admin"}',
            ]);

            expect($dto->name)->toBe('ALICE')
                ->and($dto->metadata)->toBe(['role' => 'admin']);
        });
    });

    describe('toArray()', function(): void {
        it('converts DateTime back to array', function(): void {
            $dto = CastWithUserDto::from([
                'name' => 'John',
                'createdAt' => '2024-01-15 10:30:00',
            ]);

            $array = $dto->toArray();

            expect($array['name'])->toBe('John')
                ->and($array['createdAt'])->toBeInstanceOf(DateTime::class);
        });
    });
});
