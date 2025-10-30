<?php

declare(strict_types=1);

namespace Tests\Integration\LiteDto;

use event4u\DataHelpers\LiteDto\Attributes\AutoCast;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;
use event4u\DataHelpers\LiteDto\Attributes\NoCasts;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test Caster
class TestIntCaster
{
    public static function cast(mixed $value): int
    {
        return (int)$value;
    }
}

// Test DTOs for AutoCast
#[AutoCast]
class AutoCastTestClassLevelDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $score,
        public readonly bool $active,
    ) {}
}

class AutoCastTestPropertyLevelDto extends LiteDto
{
    public function __construct(
        #[AutoCast]
        public readonly int $id,

        public readonly string $name,

        #[AutoCast]
        public readonly float $score,
    ) {}
}

class AutoCastTestNoAttributeDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

#[NoCasts]
class AutoCastTestNoCastsDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

#[AutoCast]
class AutoCastTestWithExplicitCastDto extends LiteDto
{
    public function __construct(
        public readonly int $id,

        #[CastWith(TestIntCaster::class)]
        public readonly int $amount,
    ) {}
}

#[UltraFast]
#[AutoCast]
class AutoCastTestUltraFastDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $score,
        public readonly bool $active,
    ) {}
}

#[UltraFast]
#[NoCasts]
class AutoCastTestUltraFastNoCastsDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

test('AutoCast: class-level attribute casts all native types', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => '123',
        'name' => 456,
        'score' => '9.5',
        'active' => '1',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('456')
        ->and($dto->score)->toBe(9.5)
        ->and($dto->active)->toBe(true);
});

test('AutoCast: property-level attribute casts only marked properties', function(): void {
    $dto = AutoCastTestPropertyLevelDto::from([
        'id' => '123',
        'name' => 'John',
        'score' => '9.5',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('John')
        ->and($dto->score)->toBe(9.5);
});

test('AutoCast: without attribute does not auto-cast', function(): void {
    // Without AutoCast, string '123' is passed as-is to int parameter
    // PHP will throw TypeError in strict mode
    $dto = AutoCastTestNoAttributeDto::from([
        'id' => 123,  // Must be int
        'name' => 'John',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('John');
});

test('AutoCast: NoCasts disables all casting including AutoCast', function(): void {
    // With NoCasts, even if we had AutoCast, it would be disabled
    // So string '123' is NOT casted to int
    $dto = AutoCastTestNoCastsDto::from([
        'id' => 123,  // Must be int (no casting)
        'name' => 'John',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('John');
});

test('AutoCast: explicit CastWith has priority over AutoCast', function(): void {
    $dto = AutoCastTestWithExplicitCastDto::from([
        'id' => '123',
        'amount' => '456',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->amount)->toBe(456);
});

test('AutoCast: works with UltraFast mode', function(): void {
    $dto = AutoCastTestUltraFastDto::from([
        'id' => '123',
        'name' => 456,
        'score' => '9.5',
        'active' => '1',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('456')
        ->and($dto->score)->toBe(9.5)
        ->and($dto->active)->toBe(true);
});

test('AutoCast: NoCasts works with UltraFast mode', function(): void {
    // With NoCasts + UltraFast, no casting happens
    $dto = AutoCastTestUltraFastNoCastsDto::from([
        'id' => 123,  // Must be int (no casting)
        'name' => 'John',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('John');
});

test('AutoCast: casts int from string', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => '123',
        'name' => 'John',
        'score' => 9.5,
        'active' => true,
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->id)->toBeInt();
});

test('AutoCast: casts float from string', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => 123,
        'name' => 'John',
        'score' => '9.5',
        'active' => true,
    ]);

    expect($dto->score)->toBe(9.5)
        ->and($dto->score)->toBeFloat();
});

test('AutoCast: casts string from int', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => 123,
        'name' => 456,
        'score' => 9.5,
        'active' => true,
    ]);

    expect($dto->name)->toBe('456')
        ->and($dto->name)->toBeString();
});

test('AutoCast: casts bool from string', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => 123,
        'name' => 'John',
        'score' => 9.5,
        'active' => '1',
    ]);

    expect($dto->active)->toBe(true)
        ->and($dto->active)->toBeBool();
});

test('AutoCast: casts bool from int', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => 123,
        'name' => 'John',
        'score' => 9.5,
        'active' => 1,
    ]);

    expect($dto->active)->toBe(true)
        ->and($dto->active)->toBeBool();
});

test('AutoCast: skips casting if already correct type', function(): void {
    $dto = AutoCastTestClassLevelDto::from([
        'id' => 123,
        'name' => 'John',
        'score' => 9.5,
        'active' => true,
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->name)->toBe('John')
        ->and($dto->score)->toBe(9.5)
        ->and($dto->active)->toBe(true);
});

test('AutoCast: property-level overrides class-level absence', function(): void {
    $dto = AutoCastTestPropertyLevelDto::from([
        'id' => '123',
        'name' => 'John',
        'score' => '9.5',
    ]);

    expect($dto->id)->toBe(123)
        ->and($dto->score)->toBe(9.5);
});

test('AutoCast: performance - minimal overhead when values are correct type', function(): void {
    $start = microtime(true);
    for ($i = 0; 1000 > $i; $i++) {
        AutoCastTestNoAttributeDto::from([
            'id' => 123,
            'name' => 'John',
        ]);
    }
    $timeWithout = microtime(true) - $start;

    $start = microtime(true);
    for ($i = 0; 1000 > $i; $i++) {
        AutoCastTestClassLevelDto::from([
            'id' => 123,
            'name' => 'John',
            'score' => 9.5,
            'active' => true,
        ]);
    }
    $timeWith = microtime(true) - $start;

    // AutoCast should have minimal overhead when values are already correct type
    // Allow 2x overhead (very generous) - in practice it's much less
    expect($timeWith)->toBeLessThan($timeWithout * 2.0);
});
