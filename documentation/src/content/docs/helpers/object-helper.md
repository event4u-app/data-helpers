---
title: ObjectHelper
description: Helper class for object operations with deep cloning support
---

Helper class for object operations with deep cloning support.

## Overview

ObjectHelper provides utilities for copying objects with deep cloning support for nested objects and arrays.

### Key Features

- **Deep cloning** - Recursively copy nested objects
- **Array support** - Deep copy arrays containing objects
- **Reflection-based** - Access private/protected properties
- **Configurable depth** - Control recursion depth
- **Type-safe** - PHPStan Level 9 compliant

## Quick Example

```php
use Event4u\DataHelpers\Helpers\ObjectHelper;

class User
{
    public function __construct(
        public string $name,
        public Address $address,
    ) {}
}

class Address
{
    public function __construct(
        public string $city,
    ) {}
}

$original = new User('John', new Address('New York'));

// Deep copy
$copy = ObjectHelper::copy($original);

// Modify copy without affecting original
$copy->address->city = 'Los Angeles';

echo $original->address->city; // 'New York'
echo $copy->address->city;     // 'Los Angeles'
```

## Basic Usage

### Shallow Copy

```php
$original = new User('Alice', 30);

// Shallow copy (recursive = false)
$copy = ObjectHelper::copy($original, recursive: false);

// Copy is a different object
$copy !== $original; // true

// But nested objects are shared
$copy->address === $original->address; // true
```

### Deep Copy

```php
$original = new User('Alice', new Address('New York'));

// Deep copy (recursive = true, default)
$copy = ObjectHelper::copy($original, recursive: true);

// Copy is a different object
$copy !== $original; // true

// Nested objects are also copied
$copy->address !== $original->address; // true
```

## Advanced Usage

### Control Recursion Depth

```php
// Limit recursion to 5 levels
$copy = ObjectHelper::copy($original, recursive: true, maxLevel: 5);

// Default is 10 levels
$copy = ObjectHelper::copy($original, recursive: true, maxLevel: 10);
```

### Copy Arrays with Objects

```php
class Team
{
    public function __construct(
        public string $name,
        public array $members, // Array of User objects
    ) {}
}

$team = new Team('Dev Team', [
    new User('Alice', new Address('NYC')),
    new User('Bob', new Address('LA')),
]);

// Deep copy including array of objects
$copy = ObjectHelper::copy($team);

// All nested objects are copied
$copy->members[0] !== $team->members[0]; // true
$copy->members[0]->address !== $team->members[0]->address; // true
```

## Real-World Examples

### Clone User Profile

```php
class UserProfile
{
    public function __construct(
        public string $name,
        public string $email,
        public Address $address,
        public array $preferences,
    ) {}
}

$profile = new UserProfile(
    'John Doe',
    'john@example.com',
    new Address('New York', '10001'),
    ['theme' => 'dark', 'language' => 'en']
);

// Create a copy for testing
$testProfile = ObjectHelper::copy($profile);

// Modify test profile without affecting original
$testProfile->email = 'test@example.com';
$testProfile->address->city = 'Test City';
```

### Clone Order with Items

```php
class Order
{
    public function __construct(
        public int $orderId,
        public array $items, // Array of OrderItem objects
        public Address $shippingAddress,
    ) {}
}

class OrderItem
{
    public function __construct(
        public string $product,
        public int $quantity,
        public float $price,
    ) {}
}

$order = new Order(
    123,
    [
        new OrderItem('Widget', 2, 10.00),
        new OrderItem('Gadget', 1, 20.00),
    ],
    new Address('New York', '10001')
);

// Clone order for modification
$draftOrder = ObjectHelper::copy($order);

// Modify draft without affecting original
$draftOrder->items[0]->quantity = 5;
$draftOrder->shippingAddress->city = 'Los Angeles';
```

## How It Works

### Reflection-Based Copying

ObjectHelper uses PHP's Reflection API to access all properties, including private and protected ones:

```php
class User
{
    private string $password;
    protected string $apiToken;
    public string $name;
}

// All properties are copied, including private/protected
$copy = ObjectHelper::copy($user);
```

### Handles Inheritance

```php
class Person
{
    public string $name;
}

class Employee extends Person
{
    public string $employeeId;
}

// Copies all properties from parent and child classes
$copy = ObjectHelper::copy($employee);
```

### Dynamic Properties

```php
$obj = new stdClass();
$obj->name = 'John';
$obj->email = 'john@example.com';

// Copies dynamic properties
$copy = ObjectHelper::copy($obj);
```

## Best Practices

### Use Deep Copy for Nested Objects

```php
// ✅ Good - deep copy for nested objects
$copy = ObjectHelper::copy($user, recursive: true);

// ❌ Bad - shallow copy leaves nested objects shared
$copy = ObjectHelper::copy($user, recursive: false);
```

### Set Appropriate Max Level

```php
// ✅ Good - set max level for deeply nested structures
$copy = ObjectHelper::copy($data, recursive: true, maxLevel: 15);

// ❌ Bad - default may not be enough for very deep structures
$copy = ObjectHelper::copy($data);
```

### Use for Testing

```php
// ✅ Good - copy objects for testing
$testUser = ObjectHelper::copy($user);
$testUser->email = 'test@example.com';

// ❌ Bad - modify original
$user->email = 'test@example.com';
```

## Performance Considerations

### Shallow vs Deep Copy

- **Shallow copy** - Fast, but nested objects are shared
- **Deep copy** - Slower, but creates independent copies

```php
// Fast - shallow copy
$copy = ObjectHelper::copy($user, recursive: false);

// Slower - deep copy
$copy = ObjectHelper::copy($user, recursive: true);
```

### Recursion Depth

Higher max levels allow deeper nesting but may impact performance:

```php
// Faster - lower max level
$copy = ObjectHelper::copy($data, recursive: true, maxLevel: 5);

// Slower - higher max level
$copy = ObjectHelper::copy($data, recursive: true, maxLevel: 20);
```

## See Also

- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataMutator](/main-classes/data-mutator/) - Modify nested data
- [SimpleDTO](/simple-dto/introduction/) - Immutable DTOs
