<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto;

// Test Dtos for flat structure
class FlatUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Test Dtos for nested structure
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class EmailDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class NestedUserDto extends SimpleDto
{
    /** @param array<int, EmailDto> $emails */
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
        public readonly array $emails,
    ) {}
}

// Test Dto for multi-level nesting
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly string $status,
    ) {}
}

class DepartmentDto extends SimpleDto
{
    /** @param array<int, EmployeeDto> $employees */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

class EmployeeDto extends SimpleDto
{
    /**
     * @param array<int, EmailDto> $emails
     * @param array<int, OrderDto> $orders
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
        public readonly array $orders,
    ) {}
}

describe('SimpleDto get() method', function(): void {
    describe('Flat Dto', function(): void {
        test('can get simple property', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            expect($dto->get('name'))->toBe('John Doe');
            expect($dto->get('email'))->toBe('john@example.com');
            expect($dto->get('age'))->toBe(30);
        });

        test('returns default value for non-existent property', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            expect($dto->get('missing'))->toBeNull();
            expect($dto->get('missing', 'default'))->toBe('default');
            expect($dto->get('missing', 0))->toBe(0);
            expect($dto->get('missing', false))->toBe(false);
        });
    });

    describe('Nested Dto', function(): void {
        test('can get nested property with dot notation', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: []
            );

            expect($dto->get('address.city'))->toBe('Berlin');
            expect($dto->get('address.street'))->toBe('Main St 123');
            expect($dto->get('address.country'))->toBe('Germany');
        });

        test('returns default value for non-existent nested property', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: []
            );

            expect($dto->get('address.zipcode'))->toBeNull();
            expect($dto->get('address.zipcode', '12345'))->toBe('12345');
        });
    });

    describe('Array properties with wildcards', function(): void {
        test('can get all values from array with wildcard', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new EmailDto(email: 'john@work.com', type: 'work', verified: true),
                    new EmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new EmailDto(email: 'john@other.com', type: 'other', verified: true),
                ]
            );

            $emails = $dto->get('emails.*.email');

            expect($emails)->toBeArray();
            expect($emails)->toHaveCount(3);
            expect(array_values($emails))->toBe([
                'john@work.com',
                'john@home.com',
                'john@other.com',
            ]);
        });

        test('can get all verified flags from array', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new EmailDto(email: 'john@work.com', type: 'work', verified: true),
                    new EmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new EmailDto(email: 'john@other.com', type: 'other', verified: true),
                ]
            );

            $verified = $dto->get('emails.*.verified');

            expect($verified)->toBeArray();
            expect($verified)->toHaveCount(3);
            expect(array_values($verified))->toBe([true, false, true]);
        });
    });

    describe('Multi-level nesting with wildcards', function(): void {
        test('can get nested array values with multiple wildcards', function(): void {
            $dto = new DepartmentDto(
                name: 'Engineering',
                employees: [
                    new EmployeeDto(
                        name: 'Alice',
                        emails: [
                            new EmailDto(email: 'alice@work.com', type: 'work'),
                            new EmailDto(email: 'alice@home.com', type: 'home'),
                        ],
                        orders: []
                    ),
                    new EmployeeDto(
                        name: 'Bob',
                        emails: [
                            new EmailDto(email: 'bob@work.com', type: 'work'),
                        ],
                        orders: []
                    ),
                ]
            );

            $emails = $dto->get('employees.*.emails.*.email');

            expect($emails)->toBeArray();
            expect($emails)->toHaveCount(3);
            expect(array_values($emails))->toBe([
                'alice@work.com',
                'alice@home.com',
                'bob@work.com',
            ]);
        });

        test('can get deeply nested values', function(): void {
            $dto = new DepartmentDto(
                name: 'Sales',
                employees: [
                    new EmployeeDto(
                        name: 'Charlie',
                        emails: [],
                        orders: [
                            new OrderDto(id: 1, total: 100.50, status: 'completed'),
                            new OrderDto(id: 2, total: 250.00, status: 'pending'),
                        ]
                    ),
                    new EmployeeDto(
                        name: 'Diana',
                        emails: [],
                        orders: [
                            new OrderDto(id: 3, total: 75.25, status: 'completed'),
                        ]
                    ),
                ]
            );

            $totals = $dto->get('employees.*.orders.*.total');

            expect($totals)->toBeArray();
            expect($totals)->toHaveCount(3);
            expect(array_values($totals))->toBe([100.50, 250.00, 75.25]);
        });
    });
});

describe('SimpleDto set() method', function(): void {
    describe('Flat Dto', function(): void {
        test('can set simple property and returns new instance', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto->set('name', 'Jane Doe');

            // Original unchanged
            expect($dto->get('name'))->toBe('John Doe');

            // New instance has updated value
            expect($newDto->get('name'))->toBe('Jane Doe');
            expect($newDto->get('email'))->toBe('john@example.com');
            expect($newDto->get('age'))->toBe(30);
        });

        test('can set multiple properties', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto->set('name', 'Jane Doe')
                ->set('age', 25);

            expect($newDto->get('name'))->toBe('Jane Doe');
            expect($newDto->get('age'))->toBe(25);
            expect($newDto->get('email'))->toBe('john@example.com');
        });
    });

    describe('Nested Dto', function(): void {
        test('can set nested property with dot notation', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: []
            );

            $newDto = $dto->set('address.city', 'Munich');

            // Original unchanged
            expect($dto->get('address.city'))->toBe('Berlin');

            // New instance has updated value
            expect($newDto->get('address.city'))->toBe('Munich');
            expect($newDto->get('address.street'))->toBe('Main St 123');
            expect($newDto->get('address.country'))->toBe('Germany');
        });
    });

    describe('Array properties with wildcards', function(): void {
        test('can set all values in array with wildcard', function(): void {
            $dto = new NestedUserDto(
                name: 'John Doe',
                address: new AddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new EmailDto(email: 'john@work.com', type: 'work', verified: false),
                    new EmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new EmailDto(email: 'john@other.com', type: 'other', verified: false),
                ]
            );

            $newDto = $dto->set('emails.*.verified', true);

            // Original unchanged
            expect($dto->get('emails.0.verified'))->toBe(false);

            // All emails are now verified
            $verified = $newDto->get('emails.*.verified');
            expect(array_values($verified))->toBe([true, true, true]);
        });
    });

    describe('Multi-level nesting with wildcards', function(): void {
        test('can set nested array values with multiple wildcards', function(): void {
            $dto = new DepartmentDto(
                name: 'Engineering',
                employees: [
                    new EmployeeDto(
                        name: 'Alice',
                        emails: [
                            new EmailDto(email: 'alice@work.com', type: 'work', verified: false),
                            new EmailDto(email: 'alice@home.com', type: 'home', verified: false),
                        ],
                        orders: []
                    ),
                    new EmployeeDto(
                        name: 'Bob',
                        emails: [
                            new EmailDto(email: 'bob@work.com', type: 'work', verified: false),
                        ],
                        orders: []
                    ),
                ]
            );

            $newDto = $dto->set('employees.*.emails.*.verified', true);

            // All emails are now verified
            $verified = $newDto->get('employees.*.emails.*.verified');
            expect(array_values($verified))->toBe([true, true, true]);
        });

        test('can set deeply nested values', function(): void {
            $dto = new DepartmentDto(
                name: 'Sales',
                employees: [
                    new EmployeeDto(
                        name: 'Charlie',
                        emails: [],
                        orders: [
                            new OrderDto(id: 1, total: 100.50, status: 'pending'),
                            new OrderDto(id: 2, total: 250.00, status: 'pending'),
                        ]
                    ),
                    new EmployeeDto(
                        name: 'Diana',
                        emails: [],
                        orders: [
                            new OrderDto(id: 3, total: 75.25, status: 'pending'),
                        ]
                    ),
                ]
            );

            $newDto = $dto->set('employees.*.orders.*.status', 'shipped');

            // All orders are now shipped
            $statuses = $newDto->get('employees.*.orders.*.status');
            expect(array_values($statuses))->toBe(['shipped', 'shipped', 'shipped']);
        });
    });

    describe('Edge Cases', function(): void {
        test('get() returns default for non-existent path', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            expect($dto->get('nonexistent'))->toBeNull();
            expect($dto->get('nonexistent', 'default'))->toBe('default');
            expect($dto->get('nested.path.that.does.not.exist', 42))->toBe(42);
        });

        test('get() handles empty string path', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            // Empty path returns the whole array
            $result = $dto->get('');
            expect($result)->toBeArray();
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('age');
        });

        test('get() with wildcard on non-array returns null', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            // Wildcard on non-array returns null
            expect($dto->get('name.*'))->toBeNull();
            expect($dto->get('name.*', 'default'))->toBe('default');
        });

        test('get() with wildcard on empty array returns empty array', function(): void {
            $dto = new EmployeeDto(
                name: 'John',
                emails: [],
                orders: []
            );

            expect($dto->get('emails.*.email'))->toBe([]);
        });

        test('set() creates new instance (immutability)', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto->set('name', 'Jane');

            expect($dto->name)->toBe('John');
            expect($newDto->name)->toBe('Jane');
            expect($dto)->not->toBe($newDto);
        });

        test('set() handles empty string path gracefully', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto->set('', 'value');

            // Should return a new instance but data unchanged
            expect($newDto->name)->toBe('John');
            expect($newDto)->not->toBe($dto);
        });

        test('set() with wildcard on empty array returns unchanged Dto', function(): void {
            $dto = new EmployeeDto(
                name: 'John',
                emails: [],
                orders: []
            );

            $newDto = $dto->set('emails.*.verified', true);

            expect($newDto->emails)->toBe([]);
        });

        test('get() handles null values in nested structures', function(): void {
            $dto = new class (null) extends SimpleDto {
                public function __construct(
                    public readonly ?AddressDto $address,
                ) {}
            };

            expect($dto->get('address.city'))->toBeNull();
            expect($dto->get('address.city', 'default'))->toBe('default');
        });

        test('get() and set() work with numeric keys', function(): void {
            $dto = new NestedUserDto(
                name: 'John',
                address: new AddressDto(
                    street: 'Main St',
                    city: 'NYC',
                    country: 'USA'
                ),
                emails: [
                    new EmailDto(email: 'john@work.com', type: 'work', verified: false),
                    new EmailDto(email: 'john@home.com', type: 'home', verified: false),
                ]
            );

            // Access by numeric index
            expect($dto->get('emails.0.email'))->toBe('john@work.com');
            expect($dto->get('emails.1.email'))->toBe('john@home.com');

            // Set by numeric index
            $newDto = $dto->set('emails.0.verified', true);
            expect($newDto->get('emails.0.verified'))->toBeTrue();
            expect($newDto->get('emails.1.verified'))->toBeFalse();
        });

        test('get() handles very deep nesting', function(): void {
            $dto = new DepartmentDto(
                name: 'Engineering',
                employees: [
                    new EmployeeDto(
                        name: 'Alice',
                        emails: [
                            new EmailDto(email: 'alice@work.com', type: 'work', verified: false),
                        ],
                        orders: [
                            new OrderDto(id: 1, total: 100.50, status: 'pending'),
                        ]
                    ),
                ]
            );

            expect($dto->get('employees.0.orders.0.total'))->toBe(100.50);
            expect($dto->get('employees.0.orders.0.status'))->toBe('pending');
        });

        test('set() preserves other properties', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto->set('name', 'Jane');

            expect($newDto->name)->toBe('Jane');
            expect($newDto->email)->toBe('john@example.com');
            expect($newDto->age)->toBe(30);
        });

        test('get() returns correct type for different value types', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            expect($dto->get('name'))->toBeString();
            expect($dto->get('age'))->toBeInt();
            expect($dto->get('nonexistent'))->toBeNull();
        });

        test('set() can update nested Dto properties', function(): void {
            $dto = new NestedUserDto(
                name: 'John',
                address: new AddressDto(
                    street: 'Main St',
                    city: 'NYC',
                    country: 'USA'
                ),
                emails: []
            );

            $newDto = $dto->set('address.city', 'LA');

            expect($newDto->get('address.city'))->toBe('LA');
            expect($newDto->get('address.street'))->toBe('Main St');
            expect($newDto->get('address.country'))->toBe('USA');
        });

        test('chaining multiple set() calls', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $newDto = $dto
                ->set('name', 'Jane')
                ->set('age', 25)
                ->set('email', 'jane@example.com');

            expect($newDto->name)->toBe('Jane');
            expect($newDto->age)->toBe(25);
            expect($newDto->email)->toBe('jane@example.com');

            // Original unchanged
            expect($dto->name)->toBe('John');
            expect($dto->age)->toBe(30);
            expect($dto->email)->toBe('john@example.com');
        });
    });
});
