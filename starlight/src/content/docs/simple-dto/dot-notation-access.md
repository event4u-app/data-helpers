---
title: Dot Notation Access
description: Access and modify DTO properties using dot notation with get() and set() methods
---

SimpleDTO provides powerful `get()` and `set()` methods that allow you to access and modify nested DTO properties using dot notation. This makes working with complex, nested data structures much easier.

## Features

The `get()` and `set()` methods support:

- **Dot notation** for nested property access (`user.address.city`)
- **Wildcards** for array operations (`emails.*.email`)
- **Multi-level nesting** with wildcards (`employees.*.orders.*.total`)
- **Default values** for missing properties
- **Immutability** - `set()` returns a new DTO instance

## The get() Method

The `get()` method allows you to retrieve values from your DTO using dot notation.

### Basic Usage

```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

$user = new UserDTO(
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

Access nested DTO properties using dot notation:

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}

$user = new UserDTO(
    name: 'John Doe',
    address: new AddressDTO(
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
class EmailDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class UserDTO extends SimpleDTO
{
    /**
     * @param array<int, EmailDTO> $emails
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
    ) {}
}

$user = new UserDTO(
    name: 'John Doe',
    emails: [
        new EmailDTO(email: 'john@work.com', type: 'work', verified: true),
        new EmailDTO(email: 'john@home.com', type: 'home', verified: false),
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
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly string $status,
    ) {}
}

class EmployeeDTO extends SimpleDTO
{
    /**
     * @param array<int, EmailDTO> $emails
     * @param array<int, OrderDTO> $orders
     */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
        public readonly array $orders,
    ) {}
}

class DepartmentDTO extends SimpleDTO
{
    /**
     * @param array<int, EmployeeDTO> $employees
     */
    public function __construct(
        public readonly string $name,
        public readonly array $employees,
    ) {}
}

$department = new DepartmentDTO(
    name: 'Engineering',
    employees: [
        new EmployeeDTO(
            name: 'Alice',
            emails: [
                new EmailDTO(email: 'alice@work.com', type: 'work', verified: true),
            ],
            orders: [
                new OrderDTO(id: 1, total: 100.50, status: 'pending'),
                new OrderDTO(id: 2, total: 250.00, status: 'shipped'),
            ]
        ),
        new EmployeeDTO(
            name: 'Bob',
            emails: [
                new EmailDTO(email: 'bob@work.com', type: 'work', verified: false),
            ],
            orders: [
                new OrderDTO(id: 3, total: 75.25, status: 'pending'),
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

The `set()` method allows you to update DTO properties using dot notation. Since DTOs are immutable, `set()` returns a **new instance** with the updated value.

### Basic Usage

```php
use Tests\Utils\Docu\DTOs\UserDTO;

$user = new UserDTO(
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

Update nested DTO properties:

```php
use Tests\Utils\Docu\DTOs\UserDTO;
use Tests\Utils\Docu\DTOs\AddressDTO;

$user = new UserDTO(
    name: 'John Doe',
    address: new AddressDTO(
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
$user = new UserWithEmailsDTO(
    name: 'John Doe',
    emails: [
        new EmailDTO(email: 'john@work.com', type: 'work', verified: false),
        new EmailDTO(email: 'john@home.com', type: 'home', verified: false),
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
$department = new DepartmentDTO(
    name: 'Sales',
    employees: [
        new EmployeeDTO(
            name: 'Charlie',
            emails: [],
            orders: [
                new OrderDTO(id: 1, total: 100.50, status: 'pending'),
                new OrderDTO(id: 2, total: 250.00, status: 'pending'),
            ]
        ),
        new EmployeeDTO(
            name: 'Diana',
            emails: [],
            orders: [
                new OrderDTO(id: 3, total: 75.25, status: 'pending'),
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
use Tests\Utils\Docu\DTOs\UserDTO;

$user = new UserDTO(
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
use Tests\Utils\Docu\DTOs\UserDTO;

$user = new UserDTO(name: 'John', email: 'john@example.com', age: 30);

// Returns null for non-existent paths
$result = $user->get('nonexistent'); // null

// Use default value
$result = $user->get('nonexistent', 'default'); // 'default'
```

### Empty Arrays

```php
$user = new UserWithEmailsDTO(name: 'John', emails: []);

// Wildcard on empty array returns empty array
$emails = $user->get('emails.*.email');
// Result: []

// Set on empty array returns unchanged DTO
$updated = $user->set('emails.*.verified', true);
// $updated->emails is still []
```

### Numeric Indices

You can access array elements by numeric index:

```php
$user = new UserWithEmailsDTO(
    name: 'John',
    emails: [
        new EmailDTO(email: 'first@example.com', type: 'work', verified: false),
        new EmailDTO(email: 'second@example.com', type: 'home', verified: false),
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
- Both methods convert the DTO to an array recursively
- `set()` creates a new DTO instance (immutability)
- For bulk operations, consider using `DataMapper` or `DataMutator` directly

## See Also

- [Dot Notation](/data-helpers/core-concepts/dot-notation/) - Core concept documentation
- [Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard patterns
- [DataAccessor](/data-helpers/api/data-accessor/) - Low-level data access
- [DataMutator](/data-helpers/api/data-mutator/) - Low-level data mutation
- [Nested DTOs](/data-helpers/simple-dto/nested-dtos/) - Working with nested structures

