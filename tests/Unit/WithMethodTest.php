<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\SimpleDTO;

// Test DTOs
class WithMethodDTO1 extends SimpleDTO { public function __construct(public readonly string $name, public readonly string $email) {} }
class WithMethodDTO2 extends SimpleDTO { public function __construct(public readonly string $name) {} }
class WithMethodDTO3 extends SimpleDTO { public function __construct(public readonly string $name, public readonly int $age) {} }
class WithMethodDTO4 extends SimpleDTO { public function __construct(public readonly string $firstName, public readonly string $lastName) {} }
class WithMethodDTO5 extends SimpleDTO { public function __construct(public readonly int $a, public readonly int $b) {} }
class WithMethodDTO6 extends SimpleDTO { public function __construct(public readonly string $street, public readonly string $city) {} }
class WithMethodDTO7 extends SimpleDTO { public function __construct(public readonly string $name, public readonly object $address) {} }
class WithMethodDTO8 extends SimpleDTO { public function __construct(public readonly string $name) {} }
class WithMethodDTO9 extends SimpleDTO { public function __construct(public readonly string $name, public readonly int $age) {} }

describe('With Method', function(): void {
    describe('Basic with() Method', function(): void {
        it('adds single property with key-value syntax', function(): void {
            $dto = new WithMethodDTO1('John', 'john@example.com');

            $result = $dto->with('role', 'admin')->toArray();

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('email')
                ->and($result)->toHaveKey('role')
                ->and($result['role'])->toBe('admin');
        });

        it('adds multiple properties with array syntax', function(): void {
            $dto = new WithMethodDTO2('John');

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

        it('chains multiple with() calls', function(): void {
            $dto = new WithMethodDTO2('John');

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

        it('does not modify original DTO', function(): void {
            $dto = new WithMethodDTO2('John');

            $original = $dto->toArray();
            $modified = $dto->with('role', 'admin')->toArray();

            expect($original)->not->toHaveKey('role')
                ->and($modified)->toHaveKey('role');
        });
    });

    describe('Lazy Evaluation', function(): void {
        it('evaluates callbacks lazily', function(): void {
            $dto = new WithMethodDTO3('John', 30);

            $result = $dto->with('isAdult', fn($dto): bool => 18 <= $dto->age)->toArray();

            expect($result)->toHaveKey('isAdult')
                ->and($result['isAdult'])->toBeTrue();
        });

        it('passes DTO instance to callback', function(): void {
            $dto = new WithMethodDTO4('John', 'Doe');

            $result = $dto->with('fullName', fn($dto): string => $dto->firstName . ' ' . $dto->lastName)->toArray();

            expect($result)->toHaveKey('fullName')
                ->and($result['fullName'])->toBe('John Doe');
        });

        it('evaluates multiple callbacks', function(): void {
            $dto = new WithMethodDTO5(10, 20);

            $result = $dto->with([
                'sum' => fn($dto): float|int|array => $dto->a + $dto->b,
                'product' => fn($dto): int|float => $dto->a * $dto->b,
                'difference' => fn($dto): int|float => $dto->a - $dto->b,
            ])->toArray();

            expect($result['sum'])->toBe(30)
                ->and($result['product'])->toBe(200)
                ->and($result['difference'])->toBe(-10);
        });
    });

    describe('Nested DTOs', function(): void {
        it('converts nested DTOs to arrays', function(): void {
            $addressDTO = new WithMethodDTO6('123 Main St', 'New York');

            $userDTO = new WithMethodDTO2('John');

            $result = $userDTO->with('address', $addressDTO)->toArray();

            expect($result)->toHaveKey('address')
                ->and($result['address'])->toBeArray()
                ->and($result['address'])->toHaveKey('street')
                ->and($result['address'])->toHaveKey('city')
                ->and($result['address']['street'])->toBe('123 Main St')
                ->and($result['address']['city'])->toBe('New York');
        });

        it('handles nested DTOs in callbacks', function(): void {
            $addressDTO = new WithMethodDTO6('123 Main St', 'New York');

            $userDTO = new WithMethodDTO7('John', $addressDTO);

            $result = $userDTO->with('location', fn($dto) => $dto->address)->toArray();

            expect($result)->toHaveKey('location')
                ->and($result['location'])->toBeArray()
                ->and($result['location']['city'])->toBe('New York');
        });
    });

    describe('JSON Serialization', function(): void {
        it('includes additional data in JSON serialization', function(): void {
            $dto = new WithMethodDTO8('John');

            $json = json_encode($dto->with('role', 'admin'));
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('name')
                ->and($decoded)->toHaveKey('role')
                ->and($decoded['role'])->toBe('admin');
        });

        it('evaluates callbacks in JSON serialization', function(): void {
            $dto = new WithMethodDTO9('John', 30);

            $json = json_encode($dto->with('isAdult', fn($dto): bool => 18 <= $dto->age));
            assert(is_string($json));
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('isAdult')
                ->and($decoded['isAdult'])->toBeTrue();
        });
    });

    describe('Edge Cases', function(): void {
        it('handles null values', function(): void {
            $dto = new WithMethodDTO2('John');

            $result = $dto->with('phone', null)->toArray();

            expect($result)->toHaveKey('phone')
                ->and($result['phone'])->toBeNull();
        });

        it('handles empty arrays', function(): void {
            $dto = new WithMethodDTO2('John');

            $result = $dto->with('tags', [])->toArray();

            expect($result)->toHaveKey('tags')
                ->and($result['tags'])->toBe([]);
        });

        it('overwrites existing properties', function(): void {
            $dto = new WithMethodDTO2('John');

            $result = $dto->with('name', 'Jane')->toArray();

            expect($result['name'])->toBe('Jane');
        });

        it('handles complex nested structures', function(): void {
            $dto = new WithMethodDTO2('John');

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
