<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull;

describe('With Method', function () {
    describe('Basic with() Method', function () {
        it('adds single property with key-value syntax', function () {
            $dto = new class('John', 'john@example.com') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                ) {}
            };

            $result = $dto->with('role', 'admin')->toArray();

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('email')
                ->and($result)->toHaveKey('role')
                ->and($result['role'])->toBe('admin');
        });

        it('adds multiple properties with array syntax', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto->with([
                'role' => 'admin',
                'status' => 'active',
                'level' => 5,
            ])->toArray();

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('role')
                ->and($result)->toHaveKey('status')
                ->and($result)->toHaveKey('level')
                ->and($result['role'])->toBe('admin')
                ->and($result['status'])->toBe('active')
                ->and($result['level'])->toBe(5);
        });

        it('chains multiple with() calls', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto
                ->with('role', 'admin')
                ->with('status', 'active')
                ->with('level', 5)
                ->toArray();

            expect($result)->toHaveKey('role')
                ->and($result)->toHaveKey('status')
                ->and($result)->toHaveKey('level')
                ->and($result['role'])->toBe('admin')
                ->and($result['status'])->toBe('active')
                ->and($result['level'])->toBe(5);
        });

        it('does not modify original DTO', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $original = $dto->toArray();
            $modified = $dto->with('role', 'admin')->toArray();

            expect($original)->not->toHaveKey('role')
                ->and($modified)->toHaveKey('role');
        });
    });

    describe('Lazy Evaluation', function () {
        it('evaluates callbacks lazily', function () {
            $dto = new class('John', 30) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $result = $dto->with('isAdult', fn($dto) => $dto->age >= 18)->toArray();

            expect($result)->toHaveKey('isAdult')
                ->and($result['isAdult'])->toBeTrue();
        });

        it('passes DTO instance to callback', function () {
            $dto = new class('John', 'Doe') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $firstName,
                    public readonly string $lastName,
                ) {}
            };

            $result = $dto->with('fullName', fn($dto) => $dto->firstName . ' ' . $dto->lastName)->toArray();

            expect($result)->toHaveKey('fullName')
                ->and($result['fullName'])->toBe('John Doe');
        });

        it('evaluates multiple callbacks', function () {
            $dto = new class(10, 20) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly int $a,
                    public readonly int $b,
                ) {}
            };

            $result = $dto->with([
                'sum' => fn($dto) => $dto->a + $dto->b,
                'product' => fn($dto) => $dto->a * $dto->b,
                'difference' => fn($dto) => $dto->a - $dto->b,
            ])->toArray();

            expect($result['sum'])->toBe(30)
                ->and($result['product'])->toBe(200)
                ->and($result['difference'])->toBe(-10);
        });
    });

    describe('Nested DTOs', function () {
        it('converts nested DTOs to arrays', function () {
            $addressDTO = new class('123 Main St', 'New York') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $street,
                    public readonly string $city,
                ) {}
            };

            $userDTO = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $userDTO->with('address', $addressDTO)->toArray();

            expect($result)->toHaveKey('address')
                ->and($result['address'])->toBeArray()
                ->and($result['address'])->toHaveKey('street')
                ->and($result['address'])->toHaveKey('city')
                ->and($result['address']['street'])->toBe('123 Main St')
                ->and($result['address']['city'])->toBe('New York');
        });

        it('handles nested DTOs in callbacks', function () {
            $addressDTO = new class('123 Main St', 'New York') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $street,
                    public readonly string $city,
                ) {}
            };

            $userDTO = new class('John', $addressDTO) {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                    public readonly object $address,
                ) {}
            };

            $result = $userDTO->with('location', fn($dto) => $dto->address)->toArray();

            expect($result)->toHaveKey('location')
                ->and($result['location'])->toBeArray()
                ->and($result['location']['city'])->toBe('New York');
        });
    });

    describe('JSON Serialization', function () {
        it('includes additional data in JSON serialization', function () {
            $dto = new class('John') extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $json = json_encode($dto->with('role', 'admin'));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name')
                ->and($decoded)->toHaveKey('role')
                ->and($decoded['role'])->toBe('admin');
        });

        it('evaluates callbacks in JSON serialization', function () {
            $dto = new class('John', 30) extends SimpleDTO {
                public function __construct(
                    public readonly string $name,
                    public readonly int $age,
                ) {}
            };

            $json = json_encode($dto->with('isAdult', fn($dto) => $dto->age >= 18));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('isAdult')
                ->and($decoded['isAdult'])->toBeTrue();
        });
    });

    describe('Edge Cases', function () {
        it('handles null values', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto->with('phone', null)->toArray();

            expect($result)->toHaveKey('phone')
                ->and($result['phone'])->toBeNull();
        });

        it('handles empty arrays', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto->with('tags', [])->toArray();

            expect($result)->toHaveKey('tags')
                ->and($result['tags'])->toBe([]);
        });

        it('overwrites existing properties', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto->with('name', 'Jane')->toArray();

            expect($result['name'])->toBe('Jane');
        });

        it('handles complex nested structures', function () {
            $dto = new class('John') {
                use SimpleDTOTrait;

                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $result = $dto->with('metadata', [
                'created' => '2024-01-01',
                'tags' => ['admin', 'user'],
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ])->toArray();

            expect($result)->toHaveKey('metadata')
                ->and($result['metadata'])->toBeArray()
                ->and($result['metadata']['tags'])->toBe(['admin', 'user'])
                ->and($result['metadata']['settings']['theme'])->toBe('dark');
        });
    });
});

