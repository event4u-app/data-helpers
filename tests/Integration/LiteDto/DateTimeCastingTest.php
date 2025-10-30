<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\NoCasts;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

describe('LiteDto Automatic DateTime Casting', function(): void {
    describe('DateTime Casting', function(): void {
        test('it casts string to DateTime', function(): void {
            $dto = DateTimeCastingTestDto1::from(['createdAt' => '2024-01-15 10:30:00']);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        test('it casts timestamp to DateTime', function(): void {
            $timestamp = 1705315800; // 2024-01-15 10:30:00 UTC

            $dto = DateTimeCastingTestDto1::from(['createdAt' => $timestamp]);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->getTimestamp())->toBe($timestamp);
        });

        test('it accepts DateTime instance', function(): void {
            $dateTime = new DateTime('2024-01-15 10:30:00');

            $dto = DateTimeCastingTestDto1::from(['createdAt' => $dateTime]);

            expect($dto->createdAt)->toBe($dateTime);
        });

        test('it converts DateTimeImmutable to DateTime', function(): void {
            $dateTimeImmutable = new DateTimeImmutable('2024-01-15 10:30:00');

            $dto = DateTimeCastingTestDto1::from(['createdAt' => $dateTimeImmutable]);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });
    });

    describe('DateTimeImmutable Casting', function(): void {
        test('it casts string to DateTimeImmutable', function(): void {
            $dto = DateTimeCastingTestDto2::from(['createdAt' => '2024-01-15 10:30:00']);

            expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        test('it casts timestamp to DateTimeImmutable', function(): void {
            $timestamp = 1705315800; // 2024-01-15 10:30:00 UTC

            $dto = DateTimeCastingTestDto2::from(['createdAt' => $timestamp]);

            expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
            expect($dto->createdAt->getTimestamp())->toBe($timestamp);
        });

        test('it accepts DateTimeImmutable instance', function(): void {
            $dateTimeImmutable = new DateTimeImmutable('2024-01-15 10:30:00');

            $dto = DateTimeCastingTestDto2::from(['createdAt' => $dateTimeImmutable]);

            expect($dto->createdAt)->toBe($dateTimeImmutable);
        });

        test('it converts DateTime to DateTimeImmutable', function(): void {
            $dateTime = new DateTime('2024-01-15 10:30:00');

            $dto = DateTimeCastingTestDto2::from(['createdAt' => $dateTime]);

            expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });
    });

    describe('DateTime Casting with UltraFast', function(): void {
        test('it casts string to DateTime with UltraFast', function(): void {
            $dto = DateTimeCastingTestDto3::from(['createdAt' => '2024-01-15 10:30:00']);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
        });

        test('it casts timestamp to DateTimeImmutable with UltraFast', function(): void {
            $timestamp = 1705315800;

            $dto = DateTimeCastingTestDto4::from(['createdAt' => $timestamp]);

            expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
            expect($dto->createdAt->getTimestamp())->toBe($timestamp);
        });
    });

    describe('DateTime Casting with NoCasts', function(): void {
        test('it skips DateTime casting with NoCasts', function(): void {
            $dateTime = new DateTime('2024-01-15 10:30:00');

            $dto = DateTimeCastingTestDto5::from(['createdAt' => $dateTime]);

            // With NoCasts, the DateTime instance is passed through without casting
            expect($dto->createdAt)->toBe($dateTime);
        });
    });

    describe('DateTime Formats', function(): void {
        test('it handles ISO 8601 format', function(): void {
            $dto = DateTimeCastingTestDto1::from(['createdAt' => '2024-01-15T10:30:00+00:00']);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
        });

        test('it handles date only format', function(): void {
            $dto = DateTimeCastingTestDto1::from(['createdAt' => '2024-01-15']);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->format('Y-m-d'))->toBe('2024-01-15');
        });

        test('it handles relative formats', function(): void {
            $dto = DateTimeCastingTestDto1::from(['createdAt' => 'now']);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
        });
    });

    describe('Multiple DateTime Properties', function(): void {
        test('it casts multiple DateTime properties', function(): void {
            $dto = DateTimeCastingTestDto6::from([
                'createdAt' => '2024-01-15 10:30:00',
                'updatedAt' => '2024-01-16 15:45:00',
            ]);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->updatedAt)->toBeInstanceOf(DateTime::class);
            expect($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
            expect($dto->updatedAt->format('Y-m-d H:i:s'))->toBe('2024-01-16 15:45:00');
        });

        test('it casts mixed DateTime and DateTimeImmutable', function(): void {
            $dto = DateTimeCastingTestDto7::from([
                'createdAt' => '2024-01-15 10:30:00',
                'updatedAt' => '2024-01-16 15:45:00',
            ]);

            expect($dto->createdAt)->toBeInstanceOf(DateTime::class);
            expect($dto->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('Performance', function(): void {
        test('it has minimal overhead for DateTime casting', function(): void {
            $iterations = 10000;

            // Without DateTime casting
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $dto = DateTimeCastingTestDto8::from(['name' => 'John', 'age' => 30]);
            }
            $withoutDateTime = microtime(true) - $start;

            // With DateTime casting
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $dto = DateTimeCastingTestDto9::from([
                    'name' => 'John', 'age' => 30, 'createdAt' => '2024-01-15 10:30:00']
                );
            }
            $withDateTime = microtime(true) - $start;

            // DateTime casting should not add more than 3x overhead
            expect($withDateTime)->toBeLessThan($withoutDateTime * 3);
        });
    });
});

// Test DTOs
class DateTimeCastingTestDto1 extends LiteDto
{
    public function __construct(
        public readonly DateTime $createdAt,
    ) {}
}

class DateTimeCastingTestDto2 extends LiteDto
{
    public function __construct(
        public readonly DateTimeImmutable $createdAt,
    ) {}
}

#[UltraFast]
class DateTimeCastingTestDto3 extends LiteDto
{
    public function __construct(
        public readonly DateTime $createdAt,
    ) {}
}

#[UltraFast]
class DateTimeCastingTestDto4 extends LiteDto
{
    public function __construct(
        public readonly DateTimeImmutable $createdAt,
    ) {}
}

#[NoCasts]
class DateTimeCastingTestDto5 extends LiteDto
{
    public function __construct(
        public readonly DateTime $createdAt,
    ) {}
}

class DateTimeCastingTestDto6 extends LiteDto
{
    public function __construct(
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt,
    ) {}
}

class DateTimeCastingTestDto7 extends LiteDto
{
    public function __construct(
        public readonly DateTime $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}

class DateTimeCastingTestDto8 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class DateTimeCastingTestDto9 extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly DateTime $createdAt,
    ) {}
}
