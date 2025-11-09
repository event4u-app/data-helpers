<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\TypeMismatchException;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class TypedGettersTestDto extends SimpleDto
{
    /**
     * @param array<int, array{name: string, age: int}> $users
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly ?string $email,
        public readonly array $users,
        public readonly array $metadata,
    ) {}
}

describe('SimpleDto Typed Getters', function(): void {
    describe('getString', function(): void {
        it('returns string value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getString('name'))->toBe('John');
        });

        it('converts numeric to string', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 42, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getString('age'))->toBe('42');
        });

        it('throws exception for array value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [],
                'metadata' => ['key' => 'value'],
            ]);

            expect(fn(): ?string => $dto->getString('metadata'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "metadata", but got an array');
        });

        it('returns null for null value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getString('email'))->toBeNull();
        });

        it('returns default for null value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getString('email', 'default@example.com'))->toBe('default@example.com');
        });
    });

    describe('getInt', function(): void {
        it('returns integer value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 42, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getInt('age'))->toBe(42);
        });

        it('converts numeric string to integer', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 42,
                'email' => null,
                'users' => [],
                'metadata' => ['count' => '10'],
            ]);

            expect($dto->getInt('metadata.count'))->toBe(10);
        });

        it('throws exception for array value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): ?int => $dto->getInt('users'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "users", but got an array');
        });

        it('throws exception for non-numeric value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): ?int => $dto->getInt('name'))
                ->toThrow(TypeMismatchException::class, 'Cannot convert value at path "name" to int');
        });
    });

    describe('getBool', function(): void {
        it('returns boolean value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [],
                'metadata' => ['active' => true],
            ]);

            expect($dto->getBool('metadata.active'))->toBeTrue();
        });

        it('converts integer to boolean', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 1, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getBool('age'))->toBeTrue();
        });

        it('throws exception for array value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): ?bool => $dto->getBool('users'))
                ->toThrow(TypeMismatchException::class, 'Expected single value for path "users", but got an array');
        });
    });

    describe('getFloat', function(): void {
        it('returns float value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [],
                'metadata' => ['price' => 19.99],
            ]);

            expect($dto->getFloat('metadata.price'))->toBe(19.99);
        });

        it('converts integer to float', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 42, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect($dto->getFloat('age'))->toBe(42.0);
        });

        it('throws exception for non-numeric value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): ?float => $dto->getFloat('name'))
                ->toThrow(TypeMismatchException::class, 'Cannot convert value at path "name" to float');
        });
    });

    describe('getArray', function(): void {
        it('returns array value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => ['tags' => [
                    'admin',
                    'user',
                ]]]
            );

            expect($dto->getArray('metadata.tags'))->toBe(['admin', 'user']);
        });

        it('throws exception for non-array value', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): ?array => $dto->getArray('name'))
                ->toThrow(TypeMismatchException::class, 'Expected array for path "name"');
        });
    });

    describe('Collection Getters', function(): void {
        it('getIntCollection returns array of integers', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [
                    ['name' => 'Alice', 'age' => 25],
                    ['name' => 'Bob', 'age' => 30],
                ],
                'metadata' => [],
            ]);

            expect($dto->getIntCollection('users.*.age')->toArray())->toBe([
                'users.0.age' => 25,
                'users.1.age' => 30,
            ]);
        });

        it('getStringCollection returns array of strings', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [
                    ['name' => 'Alice', 'age' => 25],
                    ['name' => 'Bob', 'age' => 30],
                ],
                'metadata' => [],
            ]);

            expect($dto->getStringCollection('users.*.name')->toArray())->toBe([
                'users.0.name' => 'Alice',
                'users.1.name' => 'Bob',
            ]);
        });

        it('getBoolCollection returns array of booleans', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [
                    ['name' => 'Alice', 'age' => 25],
                    ['name' => 'Bob', 'age' => 30],
                ],
                'metadata' => ['flags' => [true, false, 1, 0]],
            ]);

            expect($dto->getBoolCollection('metadata.flags.*')->toArray())->toBe([
                'metadata.flags.0' => true,
                'metadata.flags.1' => false,
                'metadata.flags.2' => true,
                'metadata.flags.3' => false,
            ]);
        });

        it('getFloatCollection returns array of floats', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [],
                'metadata' => ['prices' => [19.99, 29.99, '39.99']],
            ]);

            expect($dto->getFloatCollection('metadata.prices.*')->toArray())->toBe([
                'metadata.prices.0' => 19.99,
                'metadata.prices.1' => 29.99,
                'metadata.prices.2' => 39.99,
            ]);
        });

        it('getArrayCollection returns array of arrays', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John',
                'age' => 30,
                'email' => null,
                'users' => [],
                'metadata' => ['items' => [['a' => 1], ['b' => 2]]],
            ]);

            expect($dto->getArrayCollection('metadata.items.*')->toArray())->toBe([
                'metadata.items.0' => ['a' => 1],
                'metadata.items.1' => ['b' => 2],
            ]);
        });

        it('throws exception when collection getter used without wildcard', function(): void {
            $dto = TypedGettersTestDto::fromArray([
                'name' => 'John', 'age' => 30, 'email' => null, 'users' => [], 'metadata' => []]
            );

            expect(fn(): array => $dto->getIntCollection('age'))
                ->toThrow(TypeMismatchException::class, 'Path "age" does not contain wildcards');
        });
    });
});
