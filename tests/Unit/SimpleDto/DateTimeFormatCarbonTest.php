<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;

describe('DateTimeFormat with Carbon', function(): void {
    beforeEach(function(): void {
        if (!class_exists(Carbon::class)) {
            $this->markTestSkipped('Carbon is not installed');
        }
    });

    it('formats Carbon with DateTimeFormat in toJson', function(): void {
        $dto = new class(Carbon::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly Carbon $date,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['date'])->toBe('2024-01-15');
    });

    it('formats CarbonImmutable with DateTimeFormat in toJson', function(): void {
        $dto = new class(CarbonImmutable::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly CarbonImmutable $date,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['date'])->toBe('2024-01-15');
    });

    it('formats Carbon with custom format in toJson', function(): void {
        $dto = new class(Carbon::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y H:i')]
                public readonly Carbon $dateTime,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['dateTime'])->toBe('15.01.2024 10:30');
    });

    it('does NOT serialize Carbon as array when DateTimeFormat is set', function(): void {
        $dto = new class(Carbon::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly Carbon $date,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        // Should be a string, not an array
        expect($decoded['date'])->toBeString();
        expect($decoded['date'])->not->toBeArray();
    });

    it('parses string to Carbon with DateTimeFormat', function(): void {
        $dto = new class(Carbon::now()) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly Carbon $germanDate,
            ) {}
        };

        // Parse from German format
        $dto = $dto::from(['germanDate' => '15.01.2024']);

        expect($dto->germanDate)->toBeInstanceOf(Carbon::class);
        expect($dto->germanDate->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('maintains Carbon format through round-trip', function(): void {
        $dto = new class(Carbon::now()) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly Carbon $germanDate,
            ) {}
        };

        // Step 1: Parse from German format
        $dto1 = $dto::from(['germanDate' => '15.01.2024']);
        expect($dto1->germanDate->format('Y-m-d'))->toBe('2024-01-15');

        // Step 2: Serialize to JSON (should use German format)
        $json = $dto1->toJson();
        $decoded = json_decode($json, true);
        expect($decoded['germanDate'])->toBe('15.01.2024');

        // Step 3: Parse again from JSON (round-trip)
        $dto2 = $dto::from($decoded);
        expect($dto2->germanDate->format('Y-m-d'))->toBe('2024-01-15');
    });
});
