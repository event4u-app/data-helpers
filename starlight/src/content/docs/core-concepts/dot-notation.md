---
title: Dot-Notation Paths
description: Access nested data structures with simple dot notation syntax
---

All Data Helpers classes (DataAccessor, DataMutator, DataMapper) use the same dot-path syntax for accessing nested data structures. This provides a consistent and intuitive way to work with complex data.

## Basic Syntax

Segments are separated by `.` (dot) to navigate through nested structures:

```php
$accessor = new DataAccessor([
    'user' => [
        'profile' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
    ],
]);

$name = $accessor->get('user.profile.name');
// $name = 'John Doe'
$email = $accessor->get('user.profile.email');
// $email = 'john@example.com'
```

## Numeric Indices

Numeric segments index arrays and collections:

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
        ['name' => 'Charlie', 'email' => 'charlie@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$firstUser = $accessor->get('users.0.name');
// $firstUser = 'Alice'
$secondEmail = $accessor->get('users.1.email');
// $secondEmail = 'bob@example.com'
```

## Root-Level Indices

Paths can start with numeric indices when the root data is an array:

```php
$data = [
    ['name' => 'Product A'],
    ['name' => 'Product B'],
];

$accessor = new DataAccessor($data);
$product = $accessor->get('0.name');
// $product = 'Product A'
```

## Wildcards

Use `*` to match any single segment at that position:

```php
$data = [
    'users' => [
        ['email' => 'alice@example.com'],
        ['email' => 'bob@example.com'],
        ['email' => 'charlie@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');
// ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com', 'users.2.email' => 'charlie@example.com']
```

See [Wildcards](/data-helpers/core-concepts/wildcards/) for detailed wildcard documentation.

## Empty Path

An empty path `""` returns the entire data structure:

```php
$accessor = new DataAccessor(['a' => 1, 'b' => 2]);
$all = $accessor->get('');
// $all = ['a' => 1, 'b' => 2]
```

## Missing Keys

When a path doesn't exist, `null` is returned:

```php
$accessor = new DataAccessor(['user' => ['name' => 'John']]);
$missing = $accessor->get('user.age');
$nested = $accessor->get('user.profile.bio');
// Result: $missing = null, $nested = null
```

## Path Validation

Invalid paths throw `InvalidArgumentException`:

```php
// ❌ Invalid paths
'.user'      // Leading dot
'user.'      // Trailing dot
'user..name' // Double dots
```

## Escaping Special Characters

Paths with dots in actual keys are not supported via escaping. Use structured mappings or nested objects instead:

```php
// ❌ Not supported
$accessor->get('user.email.address'); // Where 'email.address' is a single key

// ✅ Use structured data instead
$data = [
    'user' => [
        'email' => [
            'address' => 'john@example.com',
        ],
    ],
];
$accessor = new DataAccessor($data);
$email = $accessor->get('user.email.address');
// $email = 'john@example.com'
```

## Best Practices

### Use Explicit Indices for Reproducible Behavior

When you know the index, use it explicitly:

```php
// ✅ Explicit
$firstUser = $accessor->get('users.0.name');

// ⚠️ Wildcard (when you need all items)
$allNames = $accessor->get('users.*.name');
```

### Wildcards for Bulk Operations

Use wildcards when you need to extract or update multiple items:

```php
// Extract all emails
$emails = $accessor->get('users.*.email');

// Update all statuses
$mutator = new DataMutator($data);
$mutator->set('orders.*.status', 'shipped');
```

### Validate User-Provided Paths

Always validate paths from user input to avoid exceptions:

```php
use event4u\DataHelpers\Helpers\DotPathHelper;

try {
    $_GET['path'] = 'user.name'; // Simulate user input
    $path = $_GET['path'];
    DotPathHelper::segments($path); // Throws on invalid syntax
    $accessor = new DataAccessor(['user' => ['name' => 'John']]);
    $value = $accessor->get($path);
} catch (InvalidArgumentException $e) {
    // Handle invalid path
}
```

### Template-Based Mapping for Complex Transformations

For building new structures from multiple sources, use `DataMapper`:

```php
$source = [
    'profile' => ['name' => 'John', 'contact' => ['email' => 'john@example.com']],
    'orders' => [['amount' => 100], ['amount' => 200]]
];
$result = DataMapper::source($source)->template([
    'user_name' => '{{ profile.name }}',
    'user_email' => '{{ profile.contact.email }}',
    'order_amounts' => '{{ orders.*.amount }}',
    'total_orders_amount' => '{{ orders.*.amount | sum }}',
])->map()->getTarget();
// $result = ['user_name' => 'John', 'user_email' => 'john@example.com', 'order_amounts' => [100, 200], 'total_orders_amount' => 300]
```

## See Also

- [Wildcards](/data-helpers/core-concepts/wildcards/) - Detailed wildcard documentation
- [DataAccessor](/data-helpers/main-classes/data-accessor/) - Reading data
- [DataMutator](/data-helpers/main-classes/data-mutator/) - Modifying data
- [DataMapper](/data-helpers/main-classes/data-mapper/) - Transforming data
