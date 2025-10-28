<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\ImmutableLiteDto;

// Test DTO
class ImmutableUserDto extends ImmutableLiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

describe('ImmutableLiteDto', function (): void {
    describe('Basic Functionality', function (): void {
        it('can be created from array', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });

        it('can be converted to array', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBe([
                'name' => 'John',
                'age' => 30,
            ]);
        });

        it('can be converted to JSON', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            $json = $dto->toJson();

            expect($json)->toBe('{"name":"John","age":30}');
        });
    });

    describe('Immutability', function (): void {
        it('prevents property modification (readonly protection)', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            // Readonly properties throw Error when modified
            $dto->name = 'Jane';
        })->throws(Error::class, 'Cannot modify readonly property');

        it('prevents property unsetting (readonly protection)', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            // Readonly properties throw Error when unset
            unset($dto->name);
        })->throws(Error::class, 'Cannot unset readonly property');

        it('prevents adding new properties via __set', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            // Attempt to add new property (not readonly, so __set is called)
            $dto->email = 'john@example.com';
        })->throws(
            RuntimeException::class,
            'Cannot modify property "email" on immutable DTO'
        );
    });

    describe('Readonly Properties', function (): void {
        it('readonly properties cannot be modified directly', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            // This should throw an error because properties are readonly
            $dto->name = 'Jane';
        })->throws(Error::class);

        it('readonly properties can be read', function (): void {
            $dto = ImmutableUserDto::from([
                'name' => 'John',
                'age' => 30,
            ]);

            expect($dto->name)->toBe('John')
                ->and($dto->age)->toBe(30);
        });
    });
});

