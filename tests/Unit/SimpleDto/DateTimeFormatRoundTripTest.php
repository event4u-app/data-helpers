<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;

describe('DateTimeFormat Round-Trip (Input → Output)', function(): void {
    it('maintains German date format through full round-trip', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly DateTimeImmutable $germanDate,
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

        // Step 4: Verify final JSON output
        $json2 = $dto2->toJson();
        $decoded2 = json_decode($json2, true);
        expect($decoded2['germanDate'])->toBe('15.01.2024');
    });

    it('maintains US date format through full round-trip', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('m/d/Y')]
                public readonly DateTimeImmutable $usDate,
            ) {}
        };

        // Step 1: Parse from US format
        $dto1 = $dto::from(['usDate' => '01/15/2024']);
        expect($dto1->usDate->format('Y-m-d'))->toBe('2024-01-15');

        // Step 2: Serialize to JSON (should use US format)
        $json = $dto1->toJson();
        $decoded = json_decode($json, true);
        expect($decoded['usDate'])->toBe('01/15/2024');

        // Step 3: Parse again from JSON (round-trip)
        $dto2 = $dto::from($decoded);
        expect($dto2->usDate->format('Y-m-d'))->toBe('2024-01-15');

        // Step 4: Verify final JSON output
        $json2 = $dto2->toJson();
        $decoded2 = json_decode($json2, true);
        expect($decoded2['usDate'])->toBe('01/15/2024');
    });

    it('maintains ISO 8601 format through full round-trip', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('c')]
                public readonly DateTimeImmutable $isoDate,
            ) {}
        };

        // Step 1: Parse from ISO format
        $dto1 = $dto::from(['isoDate' => '2024-01-15T10:30:00+00:00']);
        expect($dto1->isoDate->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');

        // Step 2: Serialize to JSON (should use ISO format)
        $json = $dto1->toJson();
        $decoded = json_decode($json, true);
        expect($decoded['isoDate'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');

        // Step 3: Parse again from JSON (round-trip)
        $dto2 = $dto::from($decoded);
        expect($dto2->isoDate->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });

    it('handles multiple date formats in same DTO', function(): void {
        $dto = new class(
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2024-01-15 10:30:00')
        ) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly DateTimeImmutable $germanDate,

                #[DateTimeFormat('m/d/Y')]
                public readonly DateTimeImmutable $usDate,

                #[DateTimeFormat('Y-m-d H:i:s')]
                public readonly DateTimeImmutable $mysqlDate,
            ) {}
        };

        // Parse from different formats
        $dto1 = $dto::from([
            'germanDate' => '15.01.2024',
            'usDate' => '01/15/2024',
            'mysqlDate' => '2024-01-15 10:30:00',
        ]);

        expect($dto1->germanDate->format('Y-m-d'))->toBe('2024-01-15');
        expect($dto1->usDate->format('Y-m-d'))->toBe('2024-01-15');
        expect($dto1->mysqlDate->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');

        // Serialize to JSON (each should use its own format)
        $json = $dto1->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['germanDate'])->toBe('15.01.2024');
        expect($decoded['usDate'])->toBe('01/15/2024');
        expect($decoded['mysqlDate'])->toBe('2024-01-15 10:30:00');

        // Round-trip
        $dto2 = $dto::from($decoded);
        expect($dto2->germanDate->format('Y-m-d'))->toBe('2024-01-15');
        expect($dto2->usDate->format('Y-m-d'))->toBe('2024-01-15');
        expect($dto2->mysqlDate->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });
});
