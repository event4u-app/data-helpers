<?php

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\DateTimeFormat;

describe('LiteDto DateTimeFormat Attribute', function(): void {
    it('does NOT format DateTime in toArray (keeps DateTime object)', function(): void {
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');
        $dto = new class($dateTime) extends LiteDto {
            public function __construct(
                #[DateTimeFormat]
                public readonly DateTimeImmutable $createdAt,
            ) {}
        };

        $array = $dto->toArray();

        // toArray() should keep DateTime objects, NOT format them
        expect($array)->toHaveKey('createdAt')
            ->and($array['createdAt'])->toBeInstanceOf(DateTimeImmutable::class)
            ->and($array['createdAt'])->toBe($dateTime);
    });

    it('formats DateTime with default format in toJson', function(): void {
        $dto = new class(new DateTimeImmutable('2024-12-31 23:59:59')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d H:i:s')]
                public readonly DateTimeImmutable $timestamp,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('timestamp')
            ->and($decoded['timestamp'])->toBe('2024-12-31 23:59:59');
    });

    it('formats DateTime with custom format in toJson', function(): void {
        $dto = new class(new DateTimeImmutable('2024-06-25 14:30:00')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly DateTimeImmutable $date,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['date'])->toBe('2024-06-25');
    });

    it('formats DateTime with ISO 8601 format in jsonSerialize', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('c')]
                public readonly DateTimeImmutable $timestamp,
            ) {}
        };

        $json = json_encode($dto);
        $decoded = json_decode($json, true);

        expect($decoded['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
    });

    it('formats DateTime with timezone conversion in toJson', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00', new DateTimeZone(
            'Europe/Berlin'
        ))) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d H:i:s', timezone: 'UTC')]
                public readonly DateTimeImmutable $timestamp,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        // Berlin is UTC+1 in winter, so 10:30 Berlin = 09:30 UTC
        expect($decoded['timestamp'])->toBe('2024-01-15 09:30:00');
    });

    it('formats multiple DateTime properties with different formats in jsonSerialize', function(): void {
        $dto = new class(
            new DateTimeImmutable('2024-01-15 10:30:00'),
            new DateTimeImmutable('2024-06-25'),
            new DateTimeImmutable('2024-12-31 23:59:59')
        ) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d H:i:s')]
                public readonly DateTimeImmutable $createdAt,

                #[DateTimeFormat('Y-m-d')]
                public readonly DateTimeImmutable $date,

                #[DateTimeFormat('U')]
                public readonly DateTimeImmutable $timestamp,
            ) {}
        };

        $json = json_encode($dto);
        $decoded = json_decode($json, true);

        expect($decoded['createdAt'])->toBe('2024-01-15 10:30:00')
            ->and($decoded['date'])->toBe('2024-06-25')
            ->and($decoded['timestamp'])->toBe('1735689599');
    });

    it('works with DateTime (not just DateTimeImmutable) in toJson', function(): void {
        $dto = new class(new DateTime('2024-01-15 10:30:00')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d H:i:s')]
                public readonly DateTime $createdAt,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['createdAt'])->toBe('2024-01-15 10:30:00');
    });

    it('does not format DateTime without DateTimeFormat attribute', function(): void {
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:00');

        $dto = new class($dateTime) extends LiteDto {
            public function __construct(
                public readonly DateTimeImmutable $createdAt,
            ) {}
        };

        $array = $dto->toArray();

        // Without DateTimeFormat, DateTime objects are returned as-is in toArray()
        expect($array['createdAt'])->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('formats DateTime with nested DTOs in toJson', function(): void {
        $innerDto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly DateTimeImmutable $date,
            ) {}
        };

        $outerDto = new class($innerDto) extends LiteDto {
            public function __construct(
                public readonly LiteDto $inner,
            ) {}
        };

        $json = $outerDto->toJson();
        $decoded = json_decode($json, true);

        // Nested DTOs should be converted to arrays in JSON, and DateTime should be formatted
        expect($decoded['inner'])->toBeArray()
            ->and($decoded['inner']['date'])->toBe('2024-01-15');
    });

    it('preserves DateTime objects through serialize/unserialize', function(): void {
        // Create a named class for serialization (anonymous classes can't be serialized)
        // @phpstan-ignore-next-line (uniqid is fine for test class names)
        $className = 'TestSerializableLiteDto_' . uniqid();
        // @phpstan-ignore-next-line (eval is necessary to create named class for serialization test)
        eval("
            class {$className} extends event4u\\DataHelpers\\LiteDto\\LiteDto {
                public function __construct(
                    #[event4u\\DataHelpers\\LiteDto\\Attributes\\DateTimeFormat('Y-m-d H:i:s')]
                    public readonly DateTimeImmutable \$createdAt,
                ) {}
            }
        ");

        $dto = new $className(new DateTimeImmutable('2024-01-15 10:30:00'));

        $serialized = serialize($dto);
        $unserialized = unserialize($serialized);

        // After unserialize, the DTO should still have the DateTime object (not formatted)
        // @phpstan-ignore-next-line (dynamic class name from eval)
        expect($unserialized)->toBeInstanceOf($className);
        // @phpstan-ignore-next-line (unserialize returns mixed, but we know it's the correct type)
        expect($unserialized->createdAt)->toBeInstanceOf(DateTimeImmutable::class);

        // But when we call toJson(), it should be formatted
        // @phpstan-ignore-next-line (unserialize returns mixed, but we know it's the correct type)
        $json = $unserialized->toJson();
        $decoded = json_decode($json, true);
        expect($decoded['createdAt'])->toBe('2024-01-15 10:30:00');
    });

    it('implements Stringable interface', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends LiteDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly DateTimeImmutable $date,
            ) {}
        };

        // __toString() should return JSON
        $string = (string)$dto;
        $decoded = json_decode($string, true);
        expect($decoded['date'])->toBe('2024-01-15');
    });
});
