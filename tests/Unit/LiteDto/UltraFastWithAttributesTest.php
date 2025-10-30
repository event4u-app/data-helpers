<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// Custom caster for testing (uses static method like LiteDto casters)
class LiteDtoFastUpperCaseCaster
{
    public static function cast(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

// UltraFast with MapFrom (auto-detected)
#[UltraFast]
class LiteDtoFastWithMapFromDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with MapTo (auto-detected)
#[UltraFast]
class LiteDtoFastWithMapToDto extends LiteDto
{
    public function __construct(
        #[MapTo('user_name')]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with CastWith (auto-detected)
#[UltraFast]
class LiteDtoFastWithCastWithDto extends LiteDto
{
    public function __construct(
        #[CastWith(LiteDtoFastUpperCaseCaster::class)]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast with all attributes (auto-detected)
#[UltraFast]
class LiteDtoFastWithAllAttributesDto extends LiteDto
{
    public function __construct(
        #[MapFrom('user_name'), MapTo('full_name'), CastWith(LiteDtoFastUpperCaseCaster::class)]
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// UltraFast without any attributes
#[UltraFast]
class LiteDtoFastNoAttributesDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('UltraFast with Attributes', function(): void {
    describe('MapFrom Support', function(): void {
        it('auto-detects and processes MapFrom', function(): void {
            $dto = LiteDtoFastWithMapFromDto::from([
                'user_name' => 'John Doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });

        it('works without MapFrom attribute', function(): void {
            $dto = LiteDtoFastNoAttributesDto::from([
                'name' => 'John Doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(30);
        });
    });

    describe('MapTo Support', function(): void {
        it('auto-detects and processes MapTo', function(): void {
            $dto = LiteDtoFastWithMapToDto::from([
                'name' => 'John Doe',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'user_name' => 'John Doe',
                'age' => 30,
            ]);
        });

        it('works without MapTo attribute', function(): void {
            $dto = LiteDtoFastNoAttributesDto::from([
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
        it('auto-detects and processes CastWith', function(): void {
            $dto = LiteDtoFastWithCastWithDto::from([
                'name' => 'john doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('JOHN DOE');
            expect($dto->age)->toBe(30);
        });

        it('works without CastWith attribute', function(): void {
            $dto = LiteDtoFastNoAttributesDto::from([
                'name' => 'john doe',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('john doe');
            expect($dto->age)->toBe(30);
        });
    });

    describe('Combined Attributes', function(): void {
        it('auto-detects and processes all attributes', function(): void {
            $dto = LiteDtoFastWithAllAttributesDto::from([
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
        it('maintains ultra-fast performance with auto-detection', function(): void {
            $iterations = 1000;
            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                LiteDtoFastWithMapFromDto::from([
                    'user_name' => 'John Doe',
                    'age' => 30,
                ]);
            }

            $duration = microtime(true) - $start;
            $avgTime = ($duration / $iterations) * 1000000; // Convert to microseconds

            // Should still be fast (< 10μs per operation with auto-detection)
            expect($avgTime)->toBeLessThan(10.0);
        });
    });
});
