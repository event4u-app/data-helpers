<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DTOInterface;

// Test DTOs
class TestUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

class TestProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly ?string $description = null,
    ) {}
}

class TestAddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class TestCustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly TestAddressDTO $address,
    ) {}
}

describe('SimpleDTO', function(): void {
    describe('Basic Functionality', function(): void {
        it('can be created from array', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            expect($dto)->toBeInstanceOf(TestUserDTO::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('can be converted to array', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $array = $dto->toArray();

            expect($array)->toBeArray();
            expect($array)->toBe([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);
        });

        it('implements DTOInterface', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            expect($dto)->toBeInstanceOf(DTOInterface::class);
        });

        it('implements JsonSerializable', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            expect($dto)->toBeInstanceOf(JsonSerializable::class);
        });
    });

    describe('JSON Serialization', function(): void {
        it('can be serialized to JSON', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toBe([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);
        });

        it('handles special characters in JSON', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John "The Boss" Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            $json = json_encode($dto);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded['name'])->toBe('John "The Boss" Doe');
        });
    });

    describe('Optional Properties', function(): void {
        it('handles optional properties with values', function(): void {
            $dto = TestProductDTO::fromArray([
                'name' => 'Laptop',
                'price' => 999.99,
                'description' => 'High-performance laptop',
            ]);

            expect($dto->name)->toBe('Laptop');
            expect($dto->price)->toBe(999.99);
            expect($dto->description)->toBe('High-performance laptop');
        });

        it('handles optional properties without values', function(): void {
            $dto = TestProductDTO::fromArray([
                'name' => 'Mouse',
                'price' => 29.99,
            ]);

            expect($dto->name)->toBe('Mouse');
            expect($dto->price)->toBe(29.99);
            expect($dto->description)->toBeNull();
        });

        it('includes null values in toArray', function(): void {
            $dto = TestProductDTO::fromArray([
                'name' => 'Mouse',
                'price' => 29.99,
            ]);

            $array = $dto->toArray();

            expect($array)->toHaveKey('description');
            expect($array['description'])->toBeNull();
        });
    });

    describe('Nested DTOs', function(): void {
        it('supports nested DTOs', function(): void {
            $address = TestAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'New York',
            ]);

            $customer = new TestCustomerDTO(
                name: 'Jane Smith',
                address: $address,
            );

            expect($customer->name)->toBe('Jane Smith');
            expect($customer->address)->toBeInstanceOf(TestAddressDTO::class);
            expect($customer->address->street)->toBe('123 Main St');
            expect($customer->address->city)->toBe('New York');
        });

        it('serializes nested DTOs to JSON', function(): void {
            $address = TestAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'New York',
            ]);

            $customer = new TestCustomerDTO(
                name: 'Jane Smith',
                address: $address,
            );

            $json = json_encode($customer);
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toBe([
                'name' => 'Jane Smith',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'New York',
                ],
            ]);
        });

        it('converts nested DTOs to array', function(): void {
            $address = TestAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'New York',
            ]);

            $customer = new TestCustomerDTO(
                name: 'Jane Smith',
                address: $address,
            );

            $array = $customer->toArray();

            expect($array['address'])->toBeInstanceOf(TestAddressDTO::class);
            /** @phpstan-ignore-next-line unknown */
            expect($array['address']->street)->toBe('123 Main St');
        });
    });

    describe('Immutability', function(): void {
        it('has readonly properties', function(): void {
            $dto = TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
            ]);

            /** @phpstan-ignore-next-line unknown */
            expect(fn(): string => $dto->name = 'Jane Doe')
                ->toThrow(Error::class);
        });
    });

    describe('Type Safety', function(): void {
        it('enforces type constraints', function(): void {
            expect(fn(): \TestUserDTO => TestUserDTO::fromArray([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 'thirty', // Wrong type
            ]))->toThrow(TypeError::class);
        });

        it('requires all mandatory properties', function(): void {
            expect(fn(): \TestUserDTO => TestUserDTO::fromArray([
                'name' => 'John Doe',
                // Missing email and age
            ]))->toThrow(ArgumentCountError::class);
        });
    });
});
