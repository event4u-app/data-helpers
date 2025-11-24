<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;

describe('DateTimeFormat Parsing (Input)', function(): void {
    it('parses German date format (d.m.Y) when reading', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly DateTimeImmutable $germanDate,
            ) {}
        };

        // Create from German format
        $dto = $dto::from(['germanDate' => '15.01.2024']);

        expect($dto->germanDate)->toBeInstanceOf(DateTimeImmutable::class);
        expect($dto->germanDate->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('serializes with German date format (d.m.Y) when writing', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y')]
                public readonly DateTimeImmutable $germanDate,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['germanDate'])->toBe('15.01.2024');
    });

    it('parses US date format (m/d/Y) when reading', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('m/d/Y')]
                public readonly DateTimeImmutable $usDate,
            ) {}
        };

        // Create from US format
        $dto = $dto::from(['usDate' => '01/15/2024']);

        expect($dto->usDate)->toBeInstanceOf(DateTimeImmutable::class);
        expect($dto->usDate->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('serializes with US date format (m/d/Y) when writing', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('m/d/Y')]
                public readonly DateTimeImmutable $usDate,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['usDate'])->toBe('01/15/2024');
    });

    it('parses custom datetime format when reading', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y H:i')]
                public readonly DateTimeImmutable $customDateTime,
            ) {}
        };

        // Create from custom format
        $dto = $dto::from(['customDateTime' => '15.01.2024 10:30']);

        expect($dto->customDateTime)->toBeInstanceOf(DateTimeImmutable::class);
        expect($dto->customDateTime->format('Y-m-d H:i'))->toBe('2024-01-15 10:30');
    });

    it('serializes with custom datetime format when writing', function(): void {
        $dto = new class(new DateTimeImmutable('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('d.m.Y H:i')]
                public readonly DateTimeImmutable $customDateTime,
            ) {}
        };

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['customDateTime'])->toBe('15.01.2024 10:30');
    });
});
