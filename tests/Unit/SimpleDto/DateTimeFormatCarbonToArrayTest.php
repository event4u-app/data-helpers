<?php

declare(strict_types=1);

use Carbon\Carbon;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

describe('DateTimeFormat with Carbon - toArray() behavior', function(): void {
    beforeEach(function(): void {
        if (!class_exists(Carbon::class)) {
            $this->markTestSkipped('Carbon is not installed');
        }
    });

    it('keeps Carbon object in toArray() (does NOT format)', function(): void {
        $dto = new class(Carbon::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly Carbon $date,
            ) {}
        };

        $array = $dto->toArray();

        // toArray() should keep Carbon objects, NOT format them
        expect($array['date'])->toBeInstanceOf(Carbon::class);
        expect($array['date'])->not->toBeString();
        expect($array['date'])->not->toBeArray();
    });

    it('formats Carbon in json_encode()', function(): void {
        $dto = new class(Carbon::parse('2024-01-15 10:30:00')) extends SimpleDto {
            public function __construct(
                #[DateTimeFormat('Y-m-d')]
                public readonly Carbon $date,
            ) {}
        };

        $json = json_encode($dto);
        $decoded = json_decode($json, true);

        // json_encode() should format Carbon as string
        expect($decoded['date'])->toBeString();
        expect($decoded['date'])->toBe('2024-01-15');
    });

    it('reproduces the issue from user description', function(): void {
        // Simulate the ProjectDto from the user's example
        $dto = new class(Carbon::now()) extends SimpleDto {
            public function __construct(
                #[MapFrom('bauende')]
                #[DateTimeFormat('Y-m-d')]
                public readonly Carbon $completionDate,
            ) {}
        };

        // Create from data (like from XML)
        $dto = $dto::from(['bauende' => '2025-05-16']);

        // Check that Carbon object is created correctly
        expect($dto->completionDate)->toBeInstanceOf(Carbon::class);
        expect($dto->completionDate->format('Y-m-d'))->toBe('2025-05-16');

        // toArray() should keep Carbon object (NOT formatted)
        $array = $dto->toArray();
        expect($array['completionDate'])->toBeInstanceOf(Carbon::class);
        expect($array['completionDate'])->not->toBeArray();

        // json_encode should format as string
        $json = json_encode($dto);
        $decoded = json_decode($json, true);
        expect($decoded['completionDate'])->toBeString();
        expect($decoded['completionDate'])->toBe('2025-05-16');
    });
});
