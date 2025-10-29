<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// Custom caster for testing (uses static method like LiteDto casters)
class LiteDtoUpperCaseCaster
{
    public static function cast(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

// UltraFast with MapFrom enabled
#[UltraFast(allowMapFrom: true)]
class LiteDtoUltraFastWithMapFromDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with MapTo enabled
#[UltraFast(allowMapTo: true)]
class LiteDtoUltraFastWithMapToDto extends LiteDto
{
    public function __construct(
        #[MapTo('user_name')]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with CastWith enabled
#[UltraFast(allowCastWith: true)]
class LiteDtoUltraFastWithCastWithDto extends LiteDto
{
    public function __construct(
        #[CastWith(LiteDtoUpperCaseCaster::class)]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with all attributes enabled
#[UltraFast(allowMapFrom: true, allowMapTo: true, allowCastWith: true)]
class LiteDtoUltraFastWithAllAttributesDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name'), MapTo('full_name'), CastWith(LiteDtoUpperCaseCaster::class)]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast without any attributes (default)
#[UltraFast]
class LiteDtoUltraFastNoAttributesDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]  // Should be ignored
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('UltraFast with Attributes', function(): void {
    describe('MapFrom Support', function(): void {
        it('processes MapFrom when allowMapFrom is true', function(): void {
            $dto = LiteDtoUltraFastWithMapFromDto::from([
                'user_name' => 'John Doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });

        it('ignores MapFrom when allowMapFrom is false (default)', function(): void {
            $dto = LiteDtoUltraFastNoAttributesDto::from([
                'name' => 'John Doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });
    });

    describe('MapTo Support', function(): void {
        it('processes MapTo when allowMapTo is true', function(): void {
            $dto = LiteDtoUltraFastWithMapToDto::from([
                'name' => 'John Doe',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'user_name' => 'John Doe',
                'age' => 30,
            ]);
        });

        it('ignores MapTo when allowMapTo is false (default)', function(): void {
            $dto = LiteDtoUltraFastNoAttributesDto::from([
                'name' => 'John Doe',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John Doe',
                'age' => 30,
            ]);
        });
    });

    describe('CastWith Support', function(): void {
        it('processes CastWith when allowCastWith is true', function(): void {
            $dto = LiteDtoUltraFastWithCastWithDto::from([
                'name' => 'john doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('JOHN DOE');
            expect($dto->age)->toBe(30);
        });

        it('ignores CastWith when allowCastWith is false (default)', function(): void {
            $dto = LiteDtoUltraFastNoAttributesDto::from([
                'name' => 'john doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('john doe');
            expect($dto->age)->toBe(30);
        });
    });

    describe('Combined Attributes', function(): void {
        it('processes all attributes when all are enabled', function(): void {
            $dto = LiteDtoUltraFastWithAllAttributesDto::from([
                'user_name' => 'john doe',
                'age' => 30,
            ]);

            // MapFrom + CastWith on input
            expect($dto->name)->toBe('JOHN DOE');
            expect($dto->age)->toBe(30);

            // MapTo on output
            $array = $dto->toArray();
            expect($array)->toBe([
                'full_name' => 'JOHN DOE',
                'age' => 30,
            ]);
        });
    });

    describe('Performance', function(): void {
        it('maintains ultra-fast performance with selective attributes', function(): void {
            $iterations = 1000;
            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                LiteDtoUltraFastWithMapFromDto::from([
                    'user_name' => 'John Doe',
                    'age' => 30,
                ]);
            }

            $duration = microtime(true) - $start;
            $avgTime = ($duration / $iterations) * 1000000; // Convert to microseconds

            // Should still be fast (< 2μs per operation)
            expect($avgTime)->toBeLessThan(2.0);
        });
    });
});
