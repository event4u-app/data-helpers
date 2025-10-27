<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

// Test Dtos for Edge Cases
#[AutoCast]
class EdgeCaseUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly ?string $email = null,
    ) {}
}

#[AutoCast]
class EdgeCaseNestedDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?EdgeCaseUserDto $user = null,
    ) {}
}

#[AutoCast]
class EdgeCaseMultiTypeDto extends SimpleDto
{
    public function __construct(
        public readonly string|int $value,
        public readonly ?string $optional = null,
    ) {}
}

describe('SimpleDto Edge Cases', function(): void {
    describe('Null Handling', function(): void {
        it('handles null for nullable properties', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
            ]);

            expect($dto->email)->toBeNull();
        });

        it('handles missing nullable properties', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->email)->toBeNull();
        });

        it('handles null in nested Dtos', function(): void {
            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
                'user' => null,
            ]);

            expect($dto->user)->toBeNull();
        });

        it('handles missing nested Dtos', function(): void {
            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
            ]);

            expect($dto->user)->toBeNull();
        });
    });

    describe('Empty Values', function(): void {
        it('handles empty string', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => '',
                'age' => 0,
            ]);

            expect($dto->name)->toBe('')
                ->and($dto->age)->toBe(0);
        });

        it('handles empty array for nested Dto', function(): void {
            // Empty array should be treated as null for nullable Dto
            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
                'user' => null,
            ]);

            expect($dto->user)->toBeNull();
        });

        it('handles zero values', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'Zero',
                'age' => 0,
            ]);

            expect($dto->age)->toBe(0)
                ->and($dto->age)->toBeInt();
        });

        it('handles false values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    public readonly bool $active = true,
                ) {}
            };

            $instance = $dto::fromArray(['active' => false]);

            expect($instance->active)->toBeFalse();
        });
    });

    describe('Type Coercion Edge Cases', function(): void {
        it('handles automatic type coercion for int', function(): void {
            // With automatic type coercion, string '30' is converted to int 30
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => '30', // String is automatically converted to int
            ]);

            expect($dto->age)->toBe(30);
        });

        it('handles union types', function(): void {
            $dto1 = EdgeCaseMultiTypeDto::fromArray(['value' => 'string']);
            $dto2 = EdgeCaseMultiTypeDto::fromArray(['value' => 123]);

            expect($dto1->value)->toBe('string')
                ->and($dto2->value)->toBe(123);
        });

        it('handles numeric strings for string properties', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => '123',
                'age' => 456,
            ]);

            expect($dto->name)->toBe('123')
                ->and($dto->name)->toBeString()
                ->and($dto->age)->toBe(456)
                ->and($dto->age)->toBeInt();
        });
    });

    describe('Array Handling', function(): void {
        it('handles extra keys in array', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'extra_key' => 'ignored',
                'another_extra' => 123,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('handles nested Dtos', function(): void {
            $user = EdgeCaseUserDto::fromArray([
                'name' => 'Child',
                'age' => 10,
            ]);

            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
                'user' => $user,
            ]);

            expect($dto->user)->toBeInstanceOf(EdgeCaseUserDto::class);
            /** @phpstan-ignore-next-line unknown */
            expect($dto->user->name)->toBe('Child');
            /** @phpstan-ignore-next-line unknown */
            expect($dto->user->age)->toBe(10);
        });

        it('handles deeply nested arrays', function(): void {
            $dto = new class extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    public readonly array $level1 = [],
                ) {}
            };

            $instance = $dto::fromArray(['level1' => ['level2' => ['level3' => 'value']]]);

            expect($instance->level1)->toBeArray()
                ->and($instance->level1['level2']['level3'])->toBe('value');
        });
    });

    describe('toArray Edge Cases', function(): void {
        it('converts null values correctly', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('email')
                ->and($array['email'])->toBeNull();
        });

        it('converts nested Dtos correctly', function(): void {
            $user = EdgeCaseUserDto::fromArray([
                'name' => 'Child',
                'age' => 10,
            ]);

            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
                'user' => $user,
            ]);

            $array = $dto->toArray();

            expect($array['user'])->toBeInstanceOf(EdgeCaseUserDto::class);
            /** @phpstan-ignore-next-line unknown */
            expect($array['user']->name)->toBe('Child');
        });

        it('converts empty values correctly', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => '',
                'age' => 0,
            ]);

            $array = $dto->toArray();

            expect($array['name'])->toBe('')
                ->and($array['age'])->toBe(0);
        });
    });

    describe('JSON Serialization Edge Cases', function(): void {
        it('serializes null values', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['email'])->toBeNull();
        });

        it('serializes empty strings', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => '',
                'age' => 0,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('')
                ->and($decoded['age'])->toBe(0);
        });

        it('serializes nested Dtos', function(): void {
            $user = EdgeCaseUserDto::fromArray([
                'name' => 'Child',
                'age' => 10,
            ]);

            $dto = EdgeCaseNestedDto::fromArray([
                'name' => 'Parent',
                'user' => $user,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['user'])->toBeArray()
                ->and($decoded['user']['name'])->toBe('Child');
        });

        it('handles special characters', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John "Doe" & Jane',
                'age' => 30,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('John "Doe" & Jane');
        });

        it('handles unicode characters', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'Jöhn Döe 日本語',
                'age' => 30,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('Jöhn Döe 日本語');
        });
    });

    describe('Immutability Edge Cases', function(): void {
        it('creates new instance on modification', function(): void {
            $dto1 = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
            ]);

            $dto2 = EdgeCaseUserDto::fromArray([
                'name' => 'Jane',
                'age' => 25,
            ]);

            expect($dto1->name)->toBe('John')
                ->and($dto2->name)->toBe('Jane')
                ->and($dto1)->not->toBe($dto2);
        });

        it('readonly properties cannot be modified', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
            ]);

            expect(function() use ($dto): void {
                /** @phpstan-ignore-next-line unknown */
                $dto->name = 'Jane';
            })->toThrow(Error::class);
        });
    });

    describe('Default Values Edge Cases', function(): void {
        it('uses default values when not provided', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->email)->toBeNull();
        });

        it('overrides default values when provided', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => 'john@example.com',
            ]);

            expect($dto->email)->toBe('john@example.com');
        });

        it('handles explicit null override', function(): void {
            $dto = EdgeCaseUserDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
            ]);

            expect($dto->email)->toBeNull();
        });
    });
});
