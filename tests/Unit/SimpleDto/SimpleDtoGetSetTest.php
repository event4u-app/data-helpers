<?php

declare(strict_types=1);

namespace Tests\Unit\SimpleDto;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

// Test Dtos for flat structure
#[AutoCast]
class FlatUserDto extends SimpleDto
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age,
    ) {}
}

// Test Dtos for nested structure
#[AutoCast]
class GetSetAddressDto extends SimpleDto
{
    public function __construct(
        public string $street,
        public string $city,
        public string $country,
    ) {}
}

#[AutoCast]
class GetSetEmailDto extends SimpleDto
{
    public function __construct(
        public string $email,
        public string $type,
        public bool $verified = false,
    ) {}
}

#[AutoCast]
class GetSetNestedUserDto extends SimpleDto
{
    /** @param array<int, GetSetEmailDto> $emails */
    public function __construct(
        public string $name,
        public GetSetAddressDto $address,
        public array $emails,
    ) {}
}

// Test Dto for multi-level nesting
#[AutoCast]
class GetSetOrderDto extends SimpleDto
{
    public function __construct(
        public int $id,
        public float $total,
        public string $status,
    ) {}
}

#[AutoCast]
class GetSetDepartmentDto extends SimpleDto
{
    /** @param array<int, GetSetEmployeeDto> $employees */
    public function __construct(
        public string $name,
        public array $employees,
    ) {}
}

#[AutoCast]
class GetSetEmployeeDto extends SimpleDto
{
    /**
     * @param array<int, GetSetEmailDto> $emails
     * @param array<int, GetSetOrderDto> $orders
     */
    public function __construct(
        public string $name,
        public array $emails,
        public array $orders,
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
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
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
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
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
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new GetSetEmailDto(email: 'john@work.com', type: 'work', verified: true),
                    new GetSetEmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new GetSetEmailDto(email: 'john@other.com', type: 'other', verified: true),
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
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new GetSetEmailDto(email: 'john@work.com', type: 'work', verified: true),
                    new GetSetEmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new GetSetEmailDto(email: 'john@other.com', type: 'other', verified: true),
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
            $dto = new GetSetDepartmentDto(
                name: 'Engineering',
                employees: [
                    new GetSetEmployeeDto(
                        name: 'Alice',
                        emails: [
                            new GetSetEmailDto(email: 'alice@work.com', type: 'work'),
                            new GetSetEmailDto(email: 'alice@home.com', type: 'home'),
                        ],
                        orders: []
                    ),
                    new GetSetEmployeeDto(
                        name: 'Bob',
                        emails: [
                            new GetSetEmailDto(email: 'bob@work.com', type: 'work'),
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
            $dto = new GetSetDepartmentDto(
                name: 'Sales',
                employees: [
                    new GetSetEmployeeDto(
                        name: 'Charlie',
                        emails: [],
                        orders: [
                            new GetSetOrderDto(id: 1, total: 100.50, status: 'completed'),
                            new GetSetOrderDto(id: 2, total: 250.00, status: 'pending'),
                        ]
                    ),
                    new GetSetEmployeeDto(
                        name: 'Diana',
                        emails: [],
                        orders: [
                            new GetSetOrderDto(id: 3, total: 75.25, status: 'completed'),
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
        test('can set simple property directly', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            $dto->set('name', 'Jane Doe');

            // Property modified directly
            expect($dto->get('name'))->toBe('Jane Doe');
            expect($dto->get('email'))->toBe('john@example.com');
            expect($dto->get('age'))->toBe(30);
        });

        test('can set multiple properties', function(): void {
            $dto = new FlatUserDto(
                name: 'John Doe',
                email: 'john@example.com',
                age: 30
            );

            $dto->set('name', 'Jane Doe');
            $dto->set('age', 25);

            expect($dto->get('name'))->toBe('Jane Doe');
            expect($dto->get('age'))->toBe(25);
            expect($dto->get('email'))->toBe('john@example.com');
        });
    });

    describe('Nested Dto', function(): void {
        test('can set nested property with dot notation', function(): void {
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: []
            );

            $dto->set('address.city', 'Munich');

            // Property modified directly
            expect($dto->get('address.city'))->toBe('Munich');
            expect($dto->get('address.street'))->toBe('Main St 123');
            expect($dto->get('address.country'))->toBe('Germany');
        });
    });

    describe('Array properties with wildcards', function(): void {
        test('can set all values in array with wildcard', function(): void {
            $dto = new GetSetNestedUserDto(
                name: 'John Doe',
                address: new GetSetAddressDto(
                    street: 'Main St 123',
                    city: 'Berlin',
                    country: 'Germany'
                ),
                emails: [
                    new GetSetEmailDto(email: 'john@work.com', type: 'work', verified: false),
                    new GetSetEmailDto(email: 'john@home.com', type: 'home', verified: false),
                    new GetSetEmailDto(email: 'john@other.com', type: 'other', verified: false),
                ]
            );

            $dto->set('emails.*.verified', true);

            // All emails are now verified
            $verified = $dto->get('emails.*.verified');
            expect(array_values($verified))->toBe([true, true, true]);
        });
    });

    describe('Multi-level nesting with wildcards', function(): void {
        test('can set nested array values with multiple wildcards', function(): void {
            $dto = new GetSetDepartmentDto(
                name: 'Engineering',
                employees: [
                    new GetSetEmployeeDto(
                        name: 'Alice',
                        emails: [
                            new GetSetEmailDto(email: 'alice@work.com', type: 'work', verified: false),
                            new GetSetEmailDto(email: 'alice@home.com', type: 'home', verified: false),
                        ],
                        orders: []
                    ),
                    new GetSetEmployeeDto(
                        name: 'Bob',
                        emails: [
                            new GetSetEmailDto(email: 'bob@work.com', type: 'work', verified: false),
                        ],
                        orders: []
                    ),
                ]
            );

            $dto->set('employees.*.emails.*.verified', true);

            // All emails are now verified
            $verified = $dto->get('employees.*.emails.*.verified');
            expect(array_values($verified))->toBe([true, true, true]);
        });

        test('can set deeply nested values', function(): void {
            $dto = new GetSetDepartmentDto(
                name: 'Sales',
                employees: [
                    new GetSetEmployeeDto(
                        name: 'Charlie',
                        emails: [],
                        orders: [
                            new GetSetOrderDto(id: 1, total: 100.50, status: 'pending'),
                            new GetSetOrderDto(id: 2, total: 250.00, status: 'pending'),
                        ]
                    ),
                    new GetSetEmployeeDto(
                        name: 'Diana',
                        emails: [],
                        orders: [
                            new GetSetOrderDto(id: 3, total: 75.25, status: 'pending'),
                        ]
                    ),
                ]
            );

            $dto->set('employees.*.orders.*.status', 'shipped');

            // All orders are now shipped
            $statuses = $dto->get('employees.*.orders.*.status');
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
            $dto = new GetSetEmployeeDto(
                name: 'John',
                emails: [],
                orders: []
            );

            expect($dto->get('emails.*.email'))->toBe([]);
        });

        test('set() modifies mutable DTO directly', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $dto->set('name', 'Jane');

            expect($dto->name)->toBe('Jane');
        });

        test('set() handles empty string path gracefully', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $dto->set('', 'value');

            // Data unchanged
            expect($dto->name)->toBe('John');
        });

        test('set() with wildcard on empty array keeps empty array', function(): void {
            $dto = new GetSetEmployeeDto(
                name: 'John',
                emails: [],
                orders: []
            );

            $dto->set('emails.*.verified', true);

            expect($dto->emails)->toBe([]);
        });

        test('get() handles null values in nested structures', function(): void {
            $dto = new class (null) extends SimpleDto {
                public function __construct(
                    public readonly ?GetSetAddressDto $address,
                ) {}
            };

            expect($dto->get('address.city'))->toBeNull();
            expect($dto->get('address.city', 'default'))->toBe('default');
        });

        test('get() and set() work with numeric keys', function(): void {
            $dto = new GetSetNestedUserDto(
                name: 'John',
                address: new GetSetAddressDto(
                    street: 'Main St',
                    city: 'NYC',
                    country: 'USA'
                ),
                emails: [
                    new GetSetEmailDto(email: 'john@work.com', type: 'work', verified: false),
                    new GetSetEmailDto(email: 'john@home.com', type: 'home', verified: false),
                ]
            );

            // Access by numeric index
            expect($dto->get('emails.0.email'))->toBe('john@work.com');
            expect($dto->get('emails.1.email'))->toBe('john@home.com');

            // Set by numeric index
            $dto->set('emails.0.verified', true);
            expect($dto->get('emails.0.verified'))->toBeTrue();
            expect($dto->get('emails.1.verified'))->toBeFalse();
        });

        test('get() handles very deep nesting', function(): void {
            $dto = new GetSetDepartmentDto(
                name: 'Engineering',
                employees: [
                    new GetSetEmployeeDto(
                        name: 'Alice',
                        emails: [
                            new GetSetEmailDto(email: 'alice@work.com', type: 'work', verified: false),
                        ],
                        orders: [
                            new GetSetOrderDto(id: 1, total: 100.50, status: 'pending'),
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

            $dto->set('name', 'Jane');

            expect($dto->name)->toBe('Jane');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
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
            $dto = new GetSetNestedUserDto(
                name: 'John',
                address: new GetSetAddressDto(
                    street: 'Main St',
                    city: 'NYC',
                    country: 'USA'
                ),
                emails: []
            );

            $dto->set('address.city', 'LA');

            expect($dto->get('address.city'))->toBe('LA');
            expect($dto->get('address.street'))->toBe('Main St');
            expect($dto->get('address.country'))->toBe('USA');
        });

        test('multiple set() calls modify same instance', function(): void {
            $dto = new FlatUserDto(
                name: 'John',
                email: 'john@example.com',
                age: 30
            );

            $dto->set('name', 'Jane');
            $dto->set('age', 25);
            $dto->set('email', 'jane@example.com');

            expect($dto->name)->toBe('Jane');
            expect($dto->age)->toBe(25);
            expect($dto->email)->toBe('jane@example.com');
        });
    });
});
