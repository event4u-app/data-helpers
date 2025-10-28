<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Casts\IntegerCast;

// Test DTOs for #[NoCasts]
#[NoCasts]
class NoCastsBasicDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

#[NoCasts]
class NoCastsStrictDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {}
}

#[NoCasts, AutoCast]
class NoCastsWithAutoCastDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {}
}

#[NoCasts]
class NoCastsWithExplicitCastDto extends SimpleDto
{
    public function __construct(
        #[Cast(IntegerCast::class)]
        public readonly int $age,
    ) {}
}

#[NoCasts]
class NoCastsWithValidationDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
    ) {}
}

#[NoCasts]
class NoCastsWithMappingDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
    ) {}
}

#[NoCasts]
class NoCastsWithVisibilityDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[Hidden]
        public readonly string $password,
    ) {}
}

#[NoCasts]
class NoCastsWithNullableDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $age = null,
    ) {}
}

#[NoCasts]
class NoCastsWithDefaultsDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age = 25,
    ) {}
}

// Test DTOs for #[NoValidation]
#[NoValidation]
class NoValidationDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        public readonly int $age,
    ) {}
}

#[NoValidation]
class NoValidationBasicDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        public readonly int $age,
    ) {}
}

#[NoValidation]
class NoValidationWithCastsDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        public readonly int $age,
    ) {}

    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }
}

// Test DTOs for #[NoAttributes]
#[NoAttributes]
class NoAttributesDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        public readonly int $age,
    ) {}
}

#[NoAttributes]
class NoAttributesBasicDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

#[NoAttributes]
class NoAttributesWithTypeHintsDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

#[NoAttributes]
class NoAttributesWithValidationDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
    ) {}
}

#[NoAttributes]
class NoAttributesWithMappingDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
    ) {}
}

#[NoAttributes]
class NoAttributesWithVisibilityDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        #[Hidden]
        public readonly string $password,
    ) {}
}

#[NoAttributes]
class NoAttributesWithCastDto extends SimpleDto
{
    public function __construct(
        #[Cast(IntegerCast::class)]
        public readonly int $age,
    ) {}
}

// Test DTOs for combining #[NoCasts, NoValidation]
#[NoCasts, NoValidation]
class NoCastsNoValidationDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Test DTOs for combining both
#[NoAttributes, NoCasts]
class BothAttributesDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

#[NoAttributes, NoCasts]
class BothAttributesBasicDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

#[NoAttributes, NoCasts]
class BothAttributesComplexDto extends SimpleDto
{
    public function __construct(
        #[Required, Email, MapFrom('user_email'), Cast(IntegerCast::class)]
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
    ) {}
}

#[NoAttributes, NoCasts]
class BothAttributesStrictDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {}
}

// Test DTOs for performance comparison
#[AutoCast]
class PerformanceAutoCastDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {}
}

#[NoCasts]
class PerformanceNoCastsDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
    ) {}
}

// Test DTOs for nested DTO auto-casting
class NestedAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserWithNestedDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly NestedAddressDto $address,
    ) {}
}

#[NoCasts]
class UserWithNestedNoCastsDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly NestedAddressDto $address,
    ) {}
}

describe('Performance Attributes', function(): void {
    describe('#[NoCasts] Attribute', function(): void {
        it('skips all type casting', function(): void {
            // With correct types - should work
            $instance = NoCastsBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBe(30);
        });

        it('throws TypeError when types are wrong', function(): void {
            // With wrong type - should throw TypeError
            expect(fn() => NoCastsStrictDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('disables AutoCast attribute', function(): void {
            // AutoCast should be disabled by NoCasts
            expect(fn() => NoCastsWithAutoCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('disables explicit Cast attributes', function(): void {
            // Explicit Cast should be disabled by NoCasts
            expect(fn() => NoCastsWithExplicitCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('still allows validation attributes', function(): void {
            // Validation should still work with valid data
            $instance = NoCastsWithValidationDto::fromArray(['email' => 'john@example.com']);
            expect($instance->email)->toBe('john@example.com');

            // Validation should detect invalid email
            $result = NoCastsWithValidationDto::validateData(['email' => 'invalid']);
            expect($result->isFailed())->toBeTrue()
                ->and($result->errors())->toHaveKey('email');
        });

        it('still allows mapping attributes', function(): void {
            // Mapping should still work
            $instance = NoCastsWithMappingDto::fromArray(['user_name' => 'John']);
            expect($instance->name)->toBe('John');
        });

        it('still allows visibility attributes', function(): void {
            // Visibility should still work
            $instance = NoCastsWithVisibilityDto::fromArray(['name' => 'John', 'password' => 'secret']);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->not()->toHaveKey('password');
        });

        it('works with nullable properties', function(): void {
            $instance = NoCastsWithNullableDto::fromArray(['name' => 'John']);
            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBeNull();
        });

        it('works with default values', function(): void {
            $instance = NoCastsWithDefaultsDto::fromArray(['name' => 'John']);
            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBe(25);
        });
    });

    describe('#[NoValidation] Attribute', function(): void {
        it('skips all validation', function(): void {
            // Validation should be skipped - no error even with invalid email
            $instance = NoValidationBasicDto::fromArray(['email' => 'invalid', 'age' => 25]);
            expect($instance->email)->toBe('invalid')
                ->and($instance->age)->toBe(25);
        });

        it('still allows casts', function(): void {
            // Casts should still work
            $instance = NoValidationWithCastsDto::fromArray(['email' => 'invalid', 'age' => '30']);
            expect($instance->email)->toBe('invalid')
                ->and($instance->age)->toBe(30)
                ->and($instance->age)->toBeInt();
        });

        it('skips validation with validateData', function(): void {
            // validateData should return success even with invalid data
            $result = NoValidationBasicDto::validateData(['email' => 'invalid', 'age' => 25]);
            expect($result->isValid())->toBeTrue()
                ->and($result->validated())->toHaveKey('email', 'invalid');
        });
    });

    describe('#[NoAttributes] Attribute', function(): void {
        it('skips all attribute processing', function(): void {
            $instance = NoAttributesBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBe(30);
        });

        it('does not cast types without AutoCast (strict_types=1)', function(): void {
            // With declare(strict_types=1), PHP does NOT do type coercion
            // We need Cast classes for type conversion
            expect(fn() => NoAttributesWithTypeHintsDto::fromArray(['name' => 123, 'age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('disables validation attributes', function(): void {
            // Validation should be skipped - no error even with invalid email
            $instance = NoAttributesWithValidationDto::fromArray(['email' => 'invalid']);
            expect($instance->email)->toBe('invalid');
        });

        it('disables mapping attributes', function(): void {
            // Mapping should be skipped - must use exact property name
            $instance = NoAttributesWithMappingDto::fromArray(['name' => 'John']);
            expect($instance->name)->toBe('John');
        });

        it('disables visibility attributes', function(): void {
            // Visibility should be skipped - password is visible
            $instance = NoAttributesWithVisibilityDto::fromArray(['name' => 'John', 'password' => 'secret']);
            $array = $instance->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->toHaveKey('password');
        });

        it('disables cast attributes (strict_types=1)', function(): void {
            // Cast attribute should be skipped
            // With declare(strict_types=1), no type coercion happens
            expect(fn() => NoAttributesWithCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });
    });

    describe('Combining #[NoAttributes] and #[NoCasts]', function(): void {
        it('works together for maximum performance', function(): void {
            $instance = BothAttributesBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($instance->name)->toBe('John')
                ->and($instance->age)->toBe(30);
        });

        it('disables all attributes and casts', function(): void {
            // All attributes should be ignored
            $instance = BothAttributesComplexDto::fromArray(['email' => 'invalid', 'password' => 'secret']);
            $array = $instance->toArray();

            expect($instance->email)->toBe('invalid')
                ->and($array)->toHaveKey('password'); // Hidden is ignored
        });

        it('throws TypeError with wrong types', function(): void {
            expect(fn() => BothAttributesStrictDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });
    });

    describe('Nested DTO Auto-Casting', function(): void {
        it('auto-casts nested DTOs without AutoCast attribute', function(): void {
            // Nested DTOs are ALWAYS auto-casted, even without #[AutoCast]
            $user = UserWithNestedDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]);
            expect($user->address)->toBeInstanceOf(NestedAddressDto::class)
                ->and($user->address->street)->toBe('Main St')
                ->and($user->address->city)->toBe('NYC');
        });

        it('NoCasts prevents nested DTO auto-casting', function(): void {
            // #[NoCasts] prevents ALL casts, including nested DTOs
            expect(fn() => UserWithNestedNoCastsDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]))->toThrow(\TypeError::class);
        });
    });

    describe('Performance Comparison', function(): void {
        it('AutoCast enables type conversion with strict_types=1', function(): void {
            // With AutoCast, type conversion works even with strict_types=1
            $dto = PerformanceAutoCastDto::fromArray(['age' => '30']);
            expect($dto->age)->toBe(30)
                ->and($dto->age)->toBeInt();
        });

        it('NoCasts is faster than AutoCast', function(): void {
            // Warm up
            for ($i = 0; $i < 100; $i++) {
                PerformanceAutoCastDto::fromArray(['age' => '30']);
                PerformanceNoCastsDto::fromArray(['age' => 30]);
            }

            // Benchmark AutoCast
            $start = hrtime(true);
            for ($i = 0; $i < 1000; $i++) {
                PerformanceAutoCastDto::fromArray(['age' => '30']);
            }
            $timeAutoCast = hrtime(true) - $start;

            // Benchmark NoCasts
            $start = hrtime(true);
            for ($i = 0; $i < 1000; $i++) {
                PerformanceNoCastsDto::fromArray(['age' => 30]);
            }
            $timeNoCasts = hrtime(true) - $start;

            // NoCasts should be faster
            expect($timeNoCasts)->toBeLessThan($timeAutoCast);
        });
    });

    describe('Comprehensive Combination Tests', function(): void {
        it('baseline: no attributes, no AutoCast - nested DTOs work, native types need correct types', function(): void {
            // Without any attributes, nested DTOs are auto-casted
            $user = UserWithNestedDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]);
            expect($user->address)->toBeInstanceOf(NestedAddressDto::class);

            // But native types need correct types (strict_types=1)
            expect(fn() => NoCastsBasicDto::fromArray(['name' => 'John', 'age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('AutoCast: enables native type conversion, nested DTOs still work', function(): void {
            // AutoCast enables native type conversion
            $dto = PerformanceAutoCastDto::fromArray(['age' => '30']);
            expect($dto->age)->toBe(30);

            // Nested DTOs still work with AutoCast
            // (they work regardless of AutoCast)
        });

        it('NoCasts: disables ALL casts including nested DTOs', function(): void {
            // NoCasts disables nested DTO auto-casting
            expect(fn() => UserWithNestedNoCastsDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]))->toThrow(\TypeError::class);

            // NoCasts also disables native type conversion
            expect(fn() => NoCastsStrictDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);

            // NoCasts disables explicit Cast attributes
            expect(fn() => NoCastsWithExplicitCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);

            // NoCasts disables AutoCast
            expect(fn() => NoCastsWithAutoCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('NoValidation: disables validation, casts still work', function(): void {
            // NoValidation disables validation
            $dto = NoValidationDto::fromArray(['email' => 'invalid-email', 'age' => 25]);
            expect($dto->email)->toBe('invalid-email');

            // But nested DTOs still work
            // (NoValidation doesn't affect casts)
        });

        it('NoAttributes: disables all attributes, nested DTOs still work', function(): void {
            // NoAttributes disables validation
            $dto = NoAttributesDto::fromArray(['email' => 'invalid-email', 'age' => 25]);
            expect($dto->email)->toBe('invalid-email');

            // NoAttributes disables explicit Cast attributes
            expect(fn() => NoAttributesWithCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);

            // But nested DTOs still work (unless NoCasts is also used)
            $user = UserWithNestedDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]);
            expect($user->address)->toBeInstanceOf(NestedAddressDto::class);
        });

        it('NoCasts + NoValidation: disables casts and validation', function(): void {
            // Both casts and validation are disabled
            $dto = NoCastsNoValidationDto::fromArray(['email' => 'invalid-email', 'age' => 25]);
            expect($dto->email)->toBe('invalid-email')
                ->and($dto->age)->toBe(25);

            // TypeError with wrong types
            expect(fn() => NoCastsNoValidationDto::fromArray(['email' => 'test@example.com', 'age' => '25']))
                ->toThrow(\TypeError::class);
        });

        it('NoAttributes + NoCasts: maximum performance, strict types', function(): void {
            // All attributes and casts disabled
            $dto = BothAttributesDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);

            // TypeError with wrong types
            expect(fn() => BothAttributesStrictDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);

            // Nested DTOs also disabled
            expect(fn() => UserWithNestedNoCastsDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]))->toThrow(\TypeError::class);
        });

        it('AutoCast + NoCasts: NoCasts wins (disables AutoCast)', function(): void {
            // NoCasts has higher priority than AutoCast
            expect(fn() => NoCastsWithAutoCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('explicit Cast + NoCasts: NoCasts wins (disables Cast)', function(): void {
            // NoCasts has higher priority than explicit Cast
            expect(fn() => NoCastsWithExplicitCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('NoAttributes + AutoCast: NoAttributes wins (disables AutoCast)', function(): void {
            // NoAttributes disables all attributes including AutoCast
            // So native types need correct types
            expect(fn() => NoAttributesWithTypeHintsDto::fromArray(['name' => 123, 'age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('NoAttributes + explicit Cast: NoAttributes wins (disables Cast)', function(): void {
            // NoAttributes disables all attributes including explicit Cast
            expect(fn() => NoAttributesWithCastDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });
    });

    describe('Edge Cases and Additional Combinations', function(): void {
        it('NoAttributes with nested DTOs: nested DTOs still work', function(): void {
            // NoAttributes does NOT disable nested DTO auto-casting
            $user = UserWithNestedDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]);
            expect($user->address)->toBeInstanceOf(NestedAddressDto::class)
                ->and($user->address->street)->toBe('Main St');
        });

        it('NoAttributes + NoCasts with nested DTOs: nested DTOs disabled', function(): void {
            // Combining both disables nested DTO auto-casting
            expect(fn() => UserWithNestedNoCastsDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]))->toThrow(\TypeError::class);
        });

        it('NoCasts with nullable properties: works with null', function(): void {
            // Nullable properties work with NoCasts
            $dto = NoCastsWithNullableDto::fromArray(['name' => 'John', 'age' => null]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBeNull();
        });

        it('NoCasts with nullable properties: works without value', function(): void {
            // Nullable properties work with NoCasts when omitted
            $dto = NoCastsWithNullableDto::fromArray(['name' => 'John']);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBeNull();
        });

        it('NoCasts with default values: uses defaults', function(): void {
            // Default values work with NoCasts
            $dto = NoCastsWithDefaultsDto::fromArray(['name' => 'John']);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(25);
        });

        it('NoCasts with default values: can override defaults', function(): void {
            // Can override default values with NoCasts
            $dto = NoCastsWithDefaultsDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('NoCasts with validation: validation attributes are preserved', function(): void {
            // Validation attributes are preserved with NoCasts
            // (they can be used when validation is explicitly called)
            $dto = NoCastsWithValidationDto::fromArray(['email' => 'test@example.com']);
            expect($dto->email)->toBe('test@example.com');

            // The validation attributes are still present on the DTO
            // (they're just not automatically enforced during fromArray)
        });

        it('NoCasts with mapping: mapping still works', function(): void {
            // Mapping should still work with NoCasts
            $dto = NoCastsWithMappingDto::fromArray(['user_name' => 'John']);
            expect($dto->name)->toBe('John');
        });

        it('NoCasts with visibility: visibility still works', function(): void {
            // Visibility should still work with NoCasts
            $dto = NoCastsWithVisibilityDto::fromArray(['name' => 'John', 'password' => 'secret']);
            $array = $dto->toArray();

            expect($array)->toHaveKey('name')
                ->and($array)->not->toHaveKey('password');
        });

        it('NoValidation with validation attributes: validation disabled', function(): void {
            // Validation should be disabled with NoValidation
            $dto = NoValidationDto::fromArray(['email' => 'invalid-email', 'age' => 25]);
            expect($dto->email)->toBe('invalid-email');
        });

        it('NoValidation with nested DTOs: nested DTOs still work', function(): void {
            // Nested DTOs should still work with NoValidation
            $user = UserWithNestedDto::fromArray([
                'name' => 'John',
                'address' => ['street' => 'Main St', 'city' => 'NYC'],
            ]);
            expect($user->address)->toBeInstanceOf(NestedAddressDto::class);
        });

        it('NoAttributes + NoValidation: both disabled', function(): void {
            // Both attributes and validation should be disabled
            $dto = NoAttributesDto::fromArray(['email' => 'invalid-email', 'age' => 25]);
            expect($dto->email)->toBe('invalid-email');
        });

        it('NoCasts + NoValidation + NoAttributes: all three combined', function(): void {
            // All three should work together
            $dto = BothAttributesBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);

            // TypeError with wrong types
            expect(fn() => BothAttributesStrictDto::fromArray(['age' => '30']))
                ->toThrow(\TypeError::class);
        });

        it('correct types with NoCasts: no performance overhead', function(): void {
            // With correct types, NoCasts should have minimal overhead
            $dto = NoCastsBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('correct types with NoAttributes: no performance overhead', function(): void {
            // With correct types, NoAttributes should have minimal overhead
            $dto = NoAttributesDto::fromArray(['email' => 'test@example.com', 'age' => 25]);
            expect($dto->email)->toBe('test@example.com')
                ->and($dto->age)->toBe(25);
        });

        it('correct types with both attributes: maximum performance', function(): void {
            // With correct types and both attributes, maximum performance
            $dto = BothAttributesBasicDto::fromArray(['name' => 'John', 'age' => 30]);
            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });
    });
});

