<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('TimestampCast', function(): void {
    describe('Unix Timestamp to DateTime', function(): void {
        it('casts Unix timestamp to DateTimeImmutable', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $timestamp = 1704067200; // 2024-01-01 00:00:00 UTC
            $instance = $dto::fromArray(['createdAt' => $timestamp]);

            expect($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->getTimestamp())->toBe($timestamp);
        });

        it('casts numeric string to DateTimeImmutable', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $timestamp = '1704067200';
            $instance = $dto::fromArray(['createdAt' => $timestamp]);

            expect($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->getTimestamp())->toBe(1704067200);
        });

        it('keeps DateTimeImmutable as-is', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $date = new DateTimeImmutable('2024-01-01 00:00:00');
            $instance = $dto::fromArray(['createdAt' => $date]);

            expect($instance->createdAt)->toBe($date);
        });

        it('handles null values', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?DateTimeImmutable $createdAt = null,
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $instance = $dto::fromArray(['createdAt' => null]);

            expect($instance->createdAt)->toBeNull();
        });
    });

    describe('DateTime to Unix Timestamp', function(): void {
        it('converts DateTimeImmutable to timestamp in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $date = new DateTimeImmutable('2024-01-01 00:00:00 UTC');
            $instance = $dto::fromArray(['createdAt' => $date]);

            $array = $instance->toArray();

            expect($array['createdAt'])->toBe(1704067200);
        });

        it('keeps timestamp as integer in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $timestamp = 1704067200;
            $instance = $dto::fromArray(['createdAt' => $timestamp]);

            $array = $instance->toArray();

            expect($array['createdAt'])->toBe($timestamp);
        });

        it('handles null in toArray', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?DateTimeImmutable $createdAt = null,
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $instance = $dto::fromArray(['createdAt' => null]);

            $array = $instance->toArray();

            expect($array['createdAt'])->toBeNull();
        });
    });

    describe('Edge Cases', function(): void {
        it('handles zero timestamp', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $instance = $dto::fromArray(['createdAt' => 0]);

            expect($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->getTimestamp())->toBe(0);
        });

        it('handles negative timestamp', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $instance = $dto::fromArray(['createdAt' => -86400]); // 1 day before epoch

            expect($instance->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($instance->createdAt->getTimestamp())->toBe(-86400);
        });

        it('handles invalid value gracefully', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly ?DateTimeImmutable $createdAt = null,
                ) {}

                protected function casts(): array
                {
                    return ['createdAt' => 'timestamp'];
                }
            };

            $instance = $dto::fromArray(['createdAt' => 'invalid']);

            expect($instance->createdAt)->toBeNull();
        });
    });
});

