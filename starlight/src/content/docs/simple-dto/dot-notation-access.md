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

