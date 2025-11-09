---
title: Dot Notation Access
description: Access and modify Dto properties using dot notation with get() and set() methods
---

SimpleDto provides powerful `get()` and `set()` methods that allow you to access and modify nested Dto properties using dot notation. This makes working with complex, nested data structures much easier.

## Features

The `get()` and `set()` methods support:

- **Dot notation** for nested property access (`user.address.city`)
- **Wildcards** for array operations (`emails.*.email`)
- **Multi-level nesting** with wildcards (`employees.*.orders.*.total`)
- **Default values** for missing properties
- **Immutability** - `set()` returns a new Dto instance

## The get() Method

The `get()` method allows you to retrieve values from your Dto using dot notation.

### Basic Usage

```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$user = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

// Get simple property
$name = $user->get('name'); // 'John Doe'
$email = $user->get('email'); // 'john@example.com'
```

### Default Values

You can provide a default value as the second parameter:

```php
// Returns default if property doesn't exist
$phone = $user->get('phone', 'N/A'); // 'N/A'
$country = $user->get('address.country', 'Unknown'); // 'Unknown'
```

## Type-Safe Getters

SimpleDto provides strict type-safe getter methods that automatically convert values to the expected type or throw a `TypeMismatchException` if conversion fails. These methods delegate to the underlying `DataAccessor`.

### Single Value Getters

These methods return a single value with strict type conversion. They return `null` if the path doesn't exist or the value is `null`.

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\Attributes\AutoCast;

#[AutoCast]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $age,  // stored as string
        public readonly string $score,  // stored as string
        public readonly int $active,  // stored as int
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'age' => '30',
    'score' => '95.5',
    'active' => 1,
]);

// getString() - returns string or null
$name = $user->getString('name');  // 'John Doe'
$age = $user->getString('age');    // '30'
$missing = $user->getString('phone');  // null
$withDefault = $user->getString('phone', 'N/A');  // 'N/A'

// getInt() - converts to int or returns null
$age = $user->getInt('age');  // 30 (string → int)
$active = $user->getInt('active');  // 1
$missing = $user->getInt('salary');  // null
$withDefault = $user->getInt('salary', 0);  // 0

// getFloat() - converts to float or returns null
$score = $user->getFloat('score');  // 95.5 (string → float)
$age = $user->getFloat('age');  // 30.0 (string → float)

// getBool() - converts to bool or returns null
$active = $user->getBool('active');  // true (1 → true)
$inactive = $user->getBool('inactive');  // null

// getArray() - returns array or null
$tags = $user->getArray('tags');  // null (doesn't exist)
```

### Collection Getters

Collection getters are designed for wildcard paths and return typed arrays. They work with nested Dtos and array properties.

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\Attributes\AutoCast;

#[AutoCast]
class EmailDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified,
    ) {}
}

#[AutoCast]
class UserDto extends SimpleDto
{
    /**
     * @param array<int, EmailDto> $emails
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'emails' => [
        ['email' => 'john@work.com', 'type' => 'work', 'verified' => true],
        ['email' => 'john@home.com', 'type' => 'home', 'verified' => false],
    ],
]);

// getStringCollection() - returns array of strings
$addresses = $user->getStringCollection('emails.*.email');
// ['emails.0.email' => 'john@work.com', 'emails.1.email' => 'john@home.com']

$types = $user->getStringCollection('emails.*.type');
// ['emails.0.type' => 'work', 'emails.1.type' => 'home']

// getBoolCollection() - returns array of booleans
$verified = $user->getBoolCollection('emails.*.verified');
// ['emails.0.verified' => true, 'emails.1.verified' => false]
```

### Multi-Level Collection Getters

Collection getters work with deeply nested structures:

```php
#[AutoCast]
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $total,  // stored as string
        public readonly string $status,
    ) {}
}

#[AutoCast]
class EmployeeDto extends SimpleDto
{
    /**
     * @param array<int, OrderDto> $orders
     */
    public function __construct(
        public readonly string $name,
        public readonly array $orders,
    ) {}
}

#[AutoCast]
class DepartmentDto extends SimpleDto
{
    /**
     * @param array<int, EmployeeDto> $employees
     */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

$department = DepartmentDto::fromArray([
    'name' => 'Engineering',
    'employees' => [
        [
            'name' => 'Alice',
            'orders' => [
                ['id' => 1, 'total' => '100.50', 'status' => 'pending'],
                ['id' => 2, 'total' => '250.00', 'status' => 'shipped'],
            ],
        ],
        [
            'name' => 'Bob',
            'orders' => [
                ['id' => 3, 'total' => '75.25', 'status' => 'pending'],
            ],
        ],
    ],
]);

// Get all order totals as floats
$totals = $department->getFloatCollection('employees.*.orders.*.total');
// ['employees.0.orders.0.total' => 100.5, 'employees.0.orders.1.total' => 250.0, 'employees.1.orders.0.total' => 75.25]

// Get all order IDs as integers
$ids = $department->getIntCollection('employees.*.orders.*.id');
// ['employees.0.orders.0.id' => 1, 'employees.0.orders.1.id' => 2, 'employees.1.orders.0.id' => 3]

// Get all order statuses as strings
$statuses = $department->getStringCollection('employees.*.orders.*.status');
// ['employees.0.orders.0.status' => 'pending', 'employees.0.orders.1.status' => 'shipped', 'employees.1.orders.0.status' => 'pending']
```

### Exception Handling

Type-safe getters throw `TypeMismatchException` when:

1. **Single value getters** receive an array (use collection getters instead)
2. **Collection getters** are used without wildcards in the path
3. **Any getter** receives a value that cannot be converted to the expected type

<!-- skip-test: requires UserDto -->
```php
use event4u\DataHelpers\Exceptions\TypeMismatchException;

$user = UserDto::fromArray([
    'name' => 'John',
    'age' => 'invalid',
    'emails' => [
        ['email' => 'john@work.com'],
        ['email' => 'john@home.com'],
    ],
]);

// ❌ Throws TypeMismatchException - array returned for single value getter
try {
    $emails = $user->getInt('emails.*.email');
} catch (TypeMismatchException $e) {
    // Use collection getter instead
    $emails = $user->getStringCollection('emails.*.email');
}

// ❌ Throws TypeMismatchException - no wildcard in path
try {
    $emails = $user->getStringCollection('emails.0.email');
} catch (TypeMismatchException $e) {
    // Use single value getter instead
    $email = $user->getString('emails.0.email');
}

// ❌ Throws TypeMismatchException - cannot convert to int
try {
    $age = $user->getInt('age');  // 'invalid' → int fails
} catch (TypeMismatchException $e) {
    // Handle error or use default
    $age = $user->getInt('age', 0);  // Still throws!
    // Better: check value first or use get()
    $age = $user->get('age', 0);
}
```

### Nested Properties

Access nested Dto properties using dot notation:

```php
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$user = new UserDto(
    name: 'John Doe',
    address: new AddressDto(
        street: 'Main St',
        city: 'New York',
        country: 'USA'
    )
);

// Access nested properties
$city = $user->get('address.city'); // 'New York'
$country = $user->get('address.country'); // 'USA'
```

### Array Properties with Wildcards

Use wildcards (`*`) to access values from arrays:

```php
class EmailDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class UserDto extends SimpleDto
{
    /**
     * @param array<int, EmailDto> $emails
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
    ) {}
}

$user = new UserDto(
    name: 'John Doe',
    emails: [
        new EmailDto(email: 'john@work.com', type: 'work', verified: true),
        new EmailDto(email: 'john@home.com', type: 'home', verified: false),
    ]
);

// Get all email addresses
$addresses = $user->get('emails.*.email');
// ['john@work.com', 'john@home.com']

// Get all verified flags
$verified = $user->get('emails.*.verified');
// [true, false]
```

### Multi-Level Wildcards

Combine multiple wildcards for deeply nested structures:

```php
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly string $status,
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

class DepartmentDto extends SimpleDto
{
    /**
     * @param array<int, EmployeeDto> $employees
     */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

$department = new DepartmentDto(
    name: 'Engineering',
    employees: [
        new EmployeeDto(
            name: 'Alice',
            emails: [
                new EmailDto(email: 'alice@work.com', type: 'work', verified: true),
            ],
            orders: [
                new OrderDto(id: 1, total: 100.50, status: 'pending'),
                new OrderDto(id: 2, total: 250.00, status: 'shipped'),
            ]
        ),
        new EmployeeDto(
            name: 'Bob',
            emails: [
                new EmailDto(email: 'bob@work.com', type: 'work', verified: false),
            ],
            orders: [
                new OrderDto(id: 3, total: 75.25, status: 'pending'),
            ]
        ),
    ]
);

// Get all employee emails
$allEmails = $department->get('employees.*.emails.*.email');
// ['alice@work.com', 'bob@work.com']

// Get all order totals
$allTotals = $department->get('employees.*.orders.*.total');
// [100.50, 250.00, 75.25]

// Get all order statuses
$allStatuses = $department->get('employees.*.orders.*.status');
// ['pending', 'shipped', 'pending']
```

## The set() Method

The `set()` method allows you to update Dto properties using dot notation. Since Dtos are immutable, `set()` returns a **new instance** with the updated value.

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$user = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

// Set simple property - returns new instance
$updatedUser = $user->set('name', 'Jane Doe');

echo $user->name; // 'John Doe' (original unchanged)
echo $updatedUser->name; // 'Jane Doe' (new instance)
```

### Nested Properties

Update nested Dto properties:

```php
use Tests\Utils\Docu\Dtos\UserDto;
use Tests\Utils\Docu\Dtos\AddressDto;

$user = new UserDto(
    name: 'John Doe',
    address: new AddressDto(
        street: 'Main St',
        city: 'New York',
        country: 'USA'
    )
);

// Update nested property
$updatedUser = $user->set('address.city', 'Los Angeles');

echo $user->get('address.city'); // 'New York' (original)
echo $updatedUser->get('address.city'); // 'Los Angeles' (new)
```

### Array Properties with Wildcards

Update all items in an array using wildcards:

```php
$user = new UserWithEmailsDto(
    name: 'John Doe',
    emails: [
        new EmailDto(email: 'john@work.com', type: 'work', verified: false),
        new EmailDto(email: 'john@home.com', type: 'home', verified: false),
    ]
);

// Verify all emails at once
$updatedUser = $user->set('emails.*.verified', true);

$verified = $updatedUser->get('emails.*.verified');
// Result: [true, true]
```

### Multi-Level Wildcards

Update deeply nested values:

```php
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly string $status,
    ) {}
}

class EmployeeDto extends SimpleDto
{
    /**
     * @param array<int, OrderDto> $orders
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
        public readonly array $orders,
    ) {}
}

class DepartmentDto extends SimpleDto
{
    /**
     * @param array<int, EmployeeDto> $employees
     */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

$department = new DepartmentDto(
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

// Ship all orders at once
$updated = $department->set('employees.*.orders.*.status', 'shipped');

$statuses = $updated->get('employees.*.orders.*.status');
// ['shipped', 'shipped', 'shipped'] - all orders shipped
```

### Chaining set() Calls

Since `set()` returns a new instance, you can chain multiple calls:

```php
use Tests\Utils\Docu\Dtos\UserDto;

$user = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

$updatedUser = $user
    ->set('name', 'Jane Doe')
    ->set('age', 25)
    ->set('email', 'jane@example.com');

// Original unchanged
echo $user->name; // 'John Doe'

// New instance with all updates
echo $updatedUser->name; // 'Jane Doe'
echo $updatedUser->age; // 25
echo $updatedUser->email; // 'jane@example.com'
```

## Edge Cases

### Non-Existent Paths

```php
use Tests\Utils\Docu\Dtos\UserDto;

$user = new UserDto(name: 'John', email: 'john@example.com', age: 30);

// Returns null for non-existent paths
$result = $user->get('nonexistent'); // null

// Use default value
$result = $user->get('nonexistent', 'default'); // 'default'
```

### Empty Arrays

```php
$user = new UserWithEmailsDto(name: 'John', emails: []);

// Wildcard on empty array returns empty array
$emails = $user->get('emails.*.email');
// Result: []

// Set on empty array returns unchanged Dto
$updated = $user->set('emails.*.verified', true);
// $updated->emails is still []
```

### Numeric Indices

You can access array elements by numeric index:

```php
$user = new UserWithEmailsDto(
    name: 'John',
    emails: [
        new EmailDto(email: 'first@example.com', type: 'work', verified: false),
        new EmailDto(email: 'second@example.com', type: 'home', verified: false),
    ]
);

// Access by index
$first = $user->get('emails.0.email'); // 'first@example.com'
$second = $user->get('emails.1.email'); // 'second@example.com'

// Update by index
$updated = $user->set('emails.0.verified', true);
// Only first email is verified
```

## Performance Considerations

- `get()` and `set()` use the underlying `DataAccessor` and `DataMutator` classes
- Both methods convert the Dto to an array recursively
- `set()` creates a new Dto instance (immutability)
- For bulk operations, consider using `DataMapper` or `DataMutator` directly

## See Also

- [Dot Notation](/data-helpers/core-concepts/dot-notation/) - Core concept documentation
- [Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard patterns
- [DataAccessor](/data-helpers/api/data-accessor/) - Low-level data access
- [DataMutator](/data-helpers/api/data-mutator/) - Low-level data mutation
- [Nested Dtos](/data-helpers/simple-dto/nested-dtos/) - Working with nested structures

