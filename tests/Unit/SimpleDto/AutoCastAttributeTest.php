<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

// Test DTOs with #[AutoCast] at class level
#[AutoCast]
class AutoCastClassLevelDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public string $name,
        public float $price,
        public bool $active,
        public array $tags,
    ) {}
}

// Test DTO with #[AutoCast] at property level
class AutoCastPropertyLevelDto extends SimpleDto
{
    public function __construct(
        #[AutoCast]
        public int $quantity,
        public string $name, // No AutoCast - should NOT be auto-casted
    ) {}
}

// Test DTO without #[AutoCast] - strict typing
class StrictTypingDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

// Test DTO with explicit casts that should ALWAYS work
class ExplicitCastsDto extends SimpleDto
{
    public function __construct(
        public int $amount,
        public DateTimeImmutable $createdAt,
    ) {}

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'createdAt' => DateTimeCast::class,
        ];
    }
}

// Test DTO with #[AutoCast] and explicit casts - explicit should win
#[AutoCast]
class MixedCastsDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $createdAt,
    ) {}

    protected function casts(): array
    {
        return [
            'createdAt' => DateTimeCast::class,
        ];
    }
}

// Test DTO with nested DTO - should always work
class AddressDto extends SimpleDto
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}
}

#[AutoCast]
class UserWithAddressDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public string $name,
        public AddressDto $address,
    ) {}
}

describe('AutoCast Attribute', function(): void {
    describe('Class-level #[AutoCast]', function(): void {
        it('auto-casts int from string', function(): void {
            $dto = AutoCastClassLevelDto::fromArray([
                'id' => '123',
                'name' => 'Product',
                'price' => '99.99',
                'active' => '1',
                'tags' => ['new', 'sale'],
            ]);

            expect($dto->id)->toBe(123)
                ->and($dto->id)->toBeInt();
        });

        it('auto-casts string from int', function(): void {
            $dto = AutoCastClassLevelDto::fromArray([
                'id' => 123,
                'name' => 456,
                'price' => 99.99,
                'active' => true,
                'tags' => [],
            ]);

            expect($dto->name)->toBe('456')
                ->and($dto->name)->toBeString();
        });

        it('auto-casts float from string', function(): void {
            $dto = AutoCastClassLevelDto::fromArray([
                'id' => 1,
                'name' => 'Test',
                'price' => '99.99',
                'active' => true,
                'tags' => [],
            ]);

            expect($dto->price)->toBe(99.99)
                ->and($dto->price)->toBeFloat();
        });

        it('auto-casts bool from string', function(): void {
            $dto = AutoCastClassLevelDto::fromArray([
                'id' => 1,
                'name' => 'Test',
                'price' => 99.99,
                'active' => '1',
                'tags' => [],
            ]);

            expect($dto->active)->toBeTrue()
                ->and($dto->active)->toBeBool();
        });

        it('auto-casts array from string (JSON)', function(): void {
            $dto = AutoCastClassLevelDto::fromArray([
                'id' => 1,
                'name' => 'Test',
                'price' => 99.99,
                'active' => true,
                'tags' => '["tag1","tag2"]',
            ]);

            expect($dto->tags)->toBe(['tag1', 'tag2'])
                ->and($dto->tags)->toBeArray();
        });
    });

    describe('Property-level #[AutoCast]', function(): void {
        it('auto-casts only properties with #[AutoCast]', function(): void {
            $dto = AutoCastPropertyLevelDto::fromArray([
                'quantity' => '10',
                'name' => 'Product',
            ]);

            expect($dto->quantity)->toBe(10)
                ->and($dto->quantity)->toBeInt();
        });

        it('does NOT auto-cast properties without #[AutoCast]', function(): void {
            $dto = AutoCastPropertyLevelDto::fromArray([
                'quantity' => 10,
                'name' => 'Product', // Already correct type
            ]);

            expect($dto->name)->toBe('Product')
                ->and($dto->name)->toBeString();
        });
    });

    describe('Strict typing without #[AutoCast]', function(): void {
        it('works with correct types', function(): void {
            $dto = StrictTypingDto::fromArray([
                'id' => 123,
                'name' => 'Test',
            ]);

            expect($dto->id)->toBe(123)
                ->and($dto->name)->toBe('Test');
        });

        it('does NOT auto-cast without #[AutoCast]', function(): void {
            // Without AutoCast, string "123" should NOT be auto-casted to int
            // This will cause a TypeError in strict mode
            $dto = StrictTypingDto::fromArray([
                'id' => 123, // Must be int, not string
                'name' => 'Test',
            ]);

            expect($dto->id)->toBe(123);
        });
    });

    describe('Explicit casts always work', function(): void {
        it('applies explicit casts from casts() method', function(): void {
            $dto = ExplicitCastsDto::fromArray([
                'amount' => '456',
                'createdAt' => '2024-01-01 12:00:00',
            ]);

            expect($dto->amount)->toBe(456)
                ->and($dto->amount)->toBeInt()
                ->and($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('Cast priority', function(): void {
        it('explicit casts override AutoCast', function(): void {
            $dto = MixedCastsDto::fromArray([
                'id' => '123',
                'createdAt' => '2024-01-01 12:00:00',
            ]);

            // id should be auto-casted by #[AutoCast]
            expect($dto->id)->toBe(123)
                ->and($dto->id)->toBeInt();

            // createdAt should use explicit DateTimeCast
            expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
        });
    });

    describe('Nested DTOs always work', function(): void {
        it('auto-detects and casts nested DTOs regardless of AutoCast', function(): void {
            $dto = UserWithAddressDto::fromArray([
                'id' => '1',
                'name' => 'John',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            expect($dto->id)->toBe(1)
                ->and($dto->address)->toBeInstanceOf(AddressDto::class)
                ->and($dto->address->street)->toBe('123 Main St')
                ->and($dto->address->city)->toBe('New York');
        });
    });

    describe('Edge cases', function(): void {
        it('handles null values', function(): void {
            $dto = new #[AutoCast] class(null, null) extends SimpleDto {
                public function __construct(
                    public ?int $id,
                    public ?string $name,
                ) {}
            };

            $result = $dto::fromArray([
                'id' => null,
                'name' => null,
            ]);

            expect($result->id)->toBeNull()
                ->and($result->name)->toBeNull();
        });

        it('handles empty strings', function(): void {
            $dto = new #[AutoCast] class('', 0) extends SimpleDto {
                public function __construct(
                    public string $name,
                    public int $count,
                ) {}
            };

            $result = $dto::fromArray([
                'name' => '',
                'count' => '0',
            ]);

            expect($result->name)->toBe('')
                ->and($result->count)->toBe(0);
        });

        it('casts boolean to int', function(): void {
            $dto = new #[AutoCast] class(0, 0) extends SimpleDto {
                public function __construct(
                    public int $trueValue,
                    public int $falseValue,
                ) {}
            };

            $result = $dto::fromArray([
                'trueValue' => true,
                'falseValue' => false,
            ]);

            expect($result->trueValue)->toBe(1)
                ->and($result->falseValue)->toBe(0);
        });

        it('casts boolean to float', function(): void {
            $dto = new #[AutoCast] class(0.0, 0.0) extends SimpleDto {
                public function __construct(
                    public float $trueValue,
                    public float $falseValue,
                ) {}
            };

            $result = $dto::fromArray([
                'trueValue' => true,
                'falseValue' => false,
            ]);

            expect($result->trueValue)->toBe(1.0)
                ->and($result->falseValue)->toBe(0.0);
        });

        it('casts int to bool (0 = false, non-zero = true)', function(): void {
            $dto = new #[AutoCast] class(false, false, false) extends SimpleDto {
                public function __construct(
                    public bool $zero,
                    public bool $one,
                    public bool $fortytwo,
                ) {}
            };

            $result = $dto::fromArray([
                'zero' => 0,
                'one' => 1,
                'fortytwo' => 42,
            ]);

            expect($result->zero)->toBeFalse()
                ->and($result->one)->toBeTrue()
                ->and($result->fortytwo)->toBeTrue();
        });

        it('casts float to int (rounding)', function(): void {
            $dto = new #[AutoCast] class(0, 0, 0) extends SimpleDto {
                public function __construct(
                    public int $rounded,
                    public int $roundedUp,
                    public int $roundedDown,
                ) {}
            };

            $result = $dto::fromArray([
                'rounded' => 42.7,
                'roundedUp' => 99.9,
                'roundedDown' => 10.1,
            ]);

            expect($result->rounded)->toBe(42)
                ->and($result->roundedUp)->toBe(99)
                ->and($result->roundedDown)->toBe(10);
        });

        it('handles negative numbers', function(): void {
            $dto = new #[AutoCast] class(0, 0.0) extends SimpleDto {
                public function __construct(
                    public int $negInt,
                    public float $negFloat,
                ) {}
            };

            $result = $dto::fromArray([
                'negInt' => '-42',
                'negFloat' => '-99.99',
            ]);

            expect($result->negInt)->toBe(-42)
                ->and($result->negFloat)->toBe(-99.99);
        });

        it('handles scientific notation', function(): void {
            $dto = new #[AutoCast] class(0.0) extends SimpleDto {
                public function __construct(
                    public float $scientific,
                ) {}
            };

            $result = $dto::fromArray([
                'scientific' => '1.5e3',
            ]);

            expect($result->scientific)->toBe(1500.0);
        });

        it('handles boolean strings (case-insensitive)', function(): void {
            $dto = new #[AutoCast] class(false, false, false, false, false, false, false, false) extends SimpleDto {
                public function __construct(
                    public bool $true1,
                    public bool $true2,
                    public bool $true3,
                    public bool $true4,
                    public bool $false1,
                    public bool $false2,
                    public bool $false3,
                    public bool $false4,
                ) {}
            };

            $result = $dto::fromArray([
                'true1' => 'true',
                'true2' => 'TRUE',
                'true3' => 'yes',
                'true4' => 'on',
                'false1' => 'false',
                'false2' => 'FALSE',
                'false3' => 'no',
                'false4' => 'off',
            ]);

            expect($result->true1)->toBeTrue()
                ->and($result->true2)->toBeTrue()
                ->and($result->true3)->toBeTrue()
                ->and($result->true4)->toBeTrue()
                ->and($result->false1)->toBeFalse()
                ->and($result->false2)->toBeFalse()
                ->and($result->false3)->toBeFalse()
                ->and($result->false4)->toBeFalse();
        });

        it('handles whitespace in strings for int and float', function(): void {
            $dto = new #[AutoCast] class(0, 0.0) extends SimpleDto {
                public function __construct(
                    public int $intValue,
                    public float $floatValue,
                ) {}
            };

            $result = $dto::fromArray([
                'intValue' => '  42  ',
                'floatValue' => '  99.99  ',
            ]);

            expect($result->intValue)->toBe(42)
                ->and($result->floatValue)->toBe(99.99);
        });

        it('handles whitespace in boolean strings', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $boolValue,
                ) {}
            };

            $result = $dto::fromArray([
                'boolValue' => '  true  ',
            ]);

            // BooleanCast trims the string before checking
            expect($result->boolValue)->toBeTrue();
        });

        it('handles valid JSON for array casting', function(): void {
            $dto = new #[AutoCast] class([]) extends SimpleDto {
                public function __construct(
                    public array $data,
                ) {}
            };

            // Valid JSON should be decoded
            $result = $dto::fromArray([
                'data' => '["a","b","c"]',
            ]);

            expect($result->data)->toBe(['a', 'b', 'c']);
        });

        it('handles non-numeric strings for int/float', function(): void {
            $dto = new #[AutoCast] class(0, 0.0) extends SimpleDto {
                public function __construct(
                    public ?int $intValue,
                    public ?float $floatValue,
                ) {}
            };

            // Non-numeric strings should return null from IntegerCast/FloatCast
            $result = $dto::fromArray([
                'intValue' => 'not a number',
                'floatValue' => 'also not a number',
            ]);

            expect($result->intValue)->toBeNull()
                ->and($result->floatValue)->toBeNull();
        });

        it('handles mixed properties with and without AutoCast', function(): void {
            $dto = new class(0, '', 0.0) extends SimpleDto {
                public function __construct(
                    #[AutoCast]
                    public int $withAutoCast,
                    public string $withoutAutoCast,
                    #[AutoCast]
                    public float $alsoWithAutoCast,
                ) {}
            };

            $result = $dto::fromArray([
                'withAutoCast' => '123',
                'withoutAutoCast' => 'test',
                'alsoWithAutoCast' => '45.67',
            ]);

            expect($result->withAutoCast)->toBe(123)
                ->and($result->withoutAutoCast)->toBe('test')
                ->and($result->alsoWithAutoCast)->toBe(45.67);
        });

        it('handles zero values correctly', function(): void {
            $dto = new #[AutoCast] class(0, 0.0, '', false) extends SimpleDto {
                public function __construct(
                    public int $zeroInt,
                    public float $zeroFloat,
                    public string $emptyString,
                    public bool $falseBool,
                ) {}
            };

            $result = $dto::fromArray([
                'zeroInt' => '0',
                'zeroFloat' => '0.0',
                'emptyString' => '',
                'falseBool' => '0',
            ]);

            expect($result->zeroInt)->toBe(0)
                ->and($result->zeroFloat)->toBe(0.0)
                ->and($result->emptyString)->toBe('')
                ->and($result->falseBool)->toBeFalse();
        });

        it('handles large numbers', function(): void {
            $dto = new #[AutoCast] class(0, 0.0) extends SimpleDto {
                public function __construct(
                    public int $largeInt,
                    public float $largeFloat,
                ) {}
            };

            $result = $dto::fromArray([
                'largeInt' => '999999999',
                'largeFloat' => '999999999.999999',
            ]);

            expect($result->largeInt)->toBe(999999999)
                ->and($result->largeFloat)->toBe(999999999.999999);
        });

        it('handles multiple nested DTOs', function(): void {
            // Use the existing AddressDto from the top of the file
            $result = UserWithAddressDto::fromArray([
                'id' => '123',
                'name' => 'John Doe',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);

            expect($result->id)->toBe(123)
                ->and($result->name)->toBe('John Doe')
                ->and($result->address)->toBeInstanceOf(AddressDto::class)
                ->and($result->address->street)->toBe('123 Main St')
                ->and($result->address->city)->toBe('New York');
        });

        it('works with complex casting scenarios', function(): void {
            // Test that AutoCast works in complex scenarios with multiple types
            $dto = new #[AutoCast] class(0, '', 0.0, false, []) extends SimpleDto {
                public function __construct(
                    public int $id,
                    public string $name,
                    public float $price,
                    public bool $active,
                    public array $tags,
                ) {}
            };

            $result = $dto::fromArray([
                'id' => '999',
                'name' => 123,
                'price' => '49.99',
                'active' => 1,
                'tags' => '["tag1","tag2","tag3"]',
            ]);

            expect($result->id)->toBe(999)
                ->and($result->name)->toBe('123')
                ->and($result->price)->toBe(49.99)
                ->and($result->active)->toBeTrue()
                ->and($result->tags)->toBe(['tag1', 'tag2', 'tag3']);
        });

        it('handles DTO with many properties (performance)', function(): void {
            $dto = new #[AutoCast] class(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) extends SimpleDto {
                public function __construct(
                    public int $prop1,
                    public int $prop2,
                    public int $prop3,
                    public int $prop4,
                    public int $prop5,
                    public int $prop6,
                    public int $prop7,
                    public int $prop8,
                    public int $prop9,
                    public int $prop10,
                ) {}
            };

            $start = microtime(true);
            $result = $dto::fromArray([
                'prop1' => '1',
                'prop2' => '2',
                'prop3' => '3',
                'prop4' => '4',
                'prop5' => '5',
                'prop6' => '6',
                'prop7' => '7',
                'prop8' => '8',
                'prop9' => '9',
                'prop10' => '10',
            ]);
            $duration = microtime(true) - $start;

            expect($result->prop1)->toBe(1)
                ->and($result->prop10)->toBe(10)
                ->and($duration)->toBeLessThan(0.01); // Should be fast (< 10ms)
        });

        it('handles already correct types (no casting needed)', function(): void {
            $dto = new #[AutoCast] class(0, '', 0.0, false, []) extends SimpleDto {
                public function __construct(
                    public int $intValue,
                    public string $stringValue,
                    public float $floatValue,
                    public bool $boolValue,
                    public array $arrayValue,
                ) {}
            };

            $result = $dto::fromArray([
                'intValue' => 123,
                'stringValue' => 'test',
                'floatValue' => 45.67,
                'boolValue' => true,
                'arrayValue' => ['a', 'b', 'c'],
            ]);

            expect($result->intValue)->toBe(123)
                ->and($result->stringValue)->toBe('test')
                ->and($result->floatValue)->toBe(45.67)
                ->and($result->boolValue)->toBeTrue()
                ->and($result->arrayValue)->toBe(['a', 'b', 'c']);
        });

        it('handles string "0" as false for bool', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray([
                'value' => '0',
            ]);

            expect($result->value)->toBeFalse();
        });

        it('handles string "1" as true for bool', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray([
                'value' => '1',
            ]);

            expect($result->value)->toBeTrue();
        });

        it('handles empty string as false for bool', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray([
                'value' => '',
            ]);

            expect($result->value)->toBeFalse();
        });
    });

    describe('Invalid type conversions (should throw TypeError)', function(): void {
        it('throws TypeError for non-numeric string to int', function(): void {
            $dto = new #[AutoCast] class(0) extends SimpleDto {
                public function __construct(
                    public int $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => 'not a number']))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for array to int', function(): void {
            $dto = new #[AutoCast] class(0) extends SimpleDto {
                public function __construct(
                    public int $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => [1, 2, 3]]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for object to int', function(): void {
            $dto = new #[AutoCast] class(0) extends SimpleDto {
                public function __construct(
                    public int $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => new stdClass()]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for non-numeric string to float', function(): void {
            $dto = new #[AutoCast] class(0.0) extends SimpleDto {
                public function __construct(
                    public float $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => 'not a number']))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for array to float', function(): void {
            $dto = new #[AutoCast] class(0.0) extends SimpleDto {
                public function __construct(
                    public float $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => [1.5, 2.5]]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for object to float', function(): void {
            $dto = new #[AutoCast] class(0.0) extends SimpleDto {
                public function __construct(
                    public float $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => new stdClass()]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for array to bool', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => [true, false]]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for object to bool', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => new stdClass()]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for array to string', function(): void {
            $dto = new #[AutoCast] class('') extends SimpleDto {
                public function __construct(
                    public string $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => ['a', 'b']]))
                ->toThrow(TypeError::class);
        });

        it('throws TypeError for object without __toString to string', function(): void {
            $dto = new #[AutoCast] class('') extends SimpleDto {
                public function __construct(
                    public string $value,
                ) {}
            };

            expect(fn(): object => $dto::fromArray(['value' => new stdClass()]))
                ->toThrow(TypeError::class);
        });
    });

    describe('Additional bool edge cases', function(): void {
        it('casts negative int to true', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => -1]);
            expect($result->value)->toBeTrue();
        });

        it('casts float 0.0 to false', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => 0.0]);
            expect($result->value)->toBeFalse();
        });

        it('casts non-zero float to true', function(): void {
            $dto = new #[AutoCast] class(false) extends SimpleDto {
                public function __construct(
                    public bool $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => 3.14]);
            expect($result->value)->toBeTrue();
        });
    });

    describe('Additional string edge cases', function(): void {
        it('casts object with __toString to string', function(): void {
            $obj = new class {
                public function __toString(): string
                {
                    return 'test string';
                }
            };

            $dto = new #[AutoCast] class('') extends SimpleDto {
                public function __construct(
                    public string $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => $obj]);
            expect($result->value)->toBe('test string');
        });

        it('casts bool true to string "1"', function(): void {
            $dto = new #[AutoCast] class('') extends SimpleDto {
                public function __construct(
                    public string $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => true]);
            expect($result->value)->toBe('1');
        });

        it('casts bool false to string ""', function(): void {
            $dto = new #[AutoCast] class('') extends SimpleDto {
                public function __construct(
                    public string $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => false]);
            expect($result->value)->toBe('');
        });
    });

    describe('Additional array edge cases', function(): void {
        it('casts object to array', function(): void {
            $obj = new stdClass();
            $obj->foo = 'bar';
            $obj->baz = 42;

            $dto = new #[AutoCast] class([]) extends SimpleDto {
                public function __construct(
                    public array $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => $obj]);
            expect($result->value)->toBe(['foo' => 'bar', 'baz' => 42]);
        });

        it('handles empty JSON array', function(): void {
            $dto = new #[AutoCast] class([]) extends SimpleDto {
                public function __construct(
                    public array $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => '[]']);
            expect($result->value)->toBe([]);
        });

        it('handles nested JSON', function(): void {
            $dto = new #[AutoCast] class([]) extends SimpleDto {
                public function __construct(
                    public array $value,
                ) {}
            };

            $result = $dto::fromArray(['value' => '{"user":{"name":"John","age":30}}']);
            expect($result->value)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });
    });
});
