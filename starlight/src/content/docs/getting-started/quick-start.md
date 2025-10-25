---
title: Quick Start
description: Get started with Data Helpers in 5 minutes
---

Get up and running with Data Helpers in just a few minutes.

## Installation

```bash
composer require event4u/data-helpers
```

## Basic Usage

### DataAccessor - Read Nested Data

```php
use event4u\DataHelpers\DataAccessor;

$data = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'orders' => [
            ['id' => 1, 'total' => 100],
            ['id' => 2, 'total' => 200],
        ],
    ],
];

$accessor = new DataAccessor($data);

// Simple access
$name = $accessor->get('user.name'); // 'John Doe'

// Wildcard access
$totals = $accessor->get('user.orders.*.total'); // [100, 200]
```

### DataMutator - Modify Nested Data

```php
use event4u\DataHelpers\DataMutator;

$mutator = new DataMutator($data);

// Set nested value
$mutator->set('user.phone', '+1234567890');

// Set multiple values with wildcards
$mutator->set('user.orders.*.status', 'shipped');

$result = $mutator->toArray();
```

### DataMapper - Transform Data (Fluent API)

```php
use event4u\DataHelpers\DataMapper;

// Fluent API with template-based mapping
$result = DataMapper::from($data)
    ->template([
        'customer_name' => '{{ user.name }}',
        'customer_email' => '{{ user.email }}',
        'total_orders' => '{{ user.orders | count }}',
        'order_total' => '{{ user.orders.*.total | sum }}',
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'customer_name' => 'John Doe',
//     'customer_email' => 'john@example.com',
//     'total_orders' => 2,
//     'order_total' => 300,
// ]

// With WHERE/ORDER BY in template (recommended for database-stored templates)
$result = DataMapper::from($data)
    ->template([
        'items' => [
            'WHERE' => [
                '{{ user.orders.*.total }}' => ['>', 100],
            ],
            'ORDER BY' => [
                '{{ user.orders.*.total }}' => 'DESC',
            ],
            'LIMIT' => 5,
            '*' => [
                'id' => '{{ user.orders.*.id }}',
                'total' => '{{ user.orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

### DataFilter - Query Data

```php
use event4u\DataHelpers\DataFilter;

$filter = new DataFilter($data['user']['orders']);

$expensive = $filter
    ->where('total', '>', 150)
    ->orderBy('total', 'DESC')
    ->toArray();
```

### SimpleDTO - Data Transfer Objects

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Email;

class UserDTO extends SimpleDTO
{
    #[Required]
    public string $name;

    #[Required, Email]
    public string $email;

    public ?string $phone = null;
}

// Create from array
$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Convert to array
$array = $user->toArray();

// Convert to JSON
$json = $user->toJson();
```

## Next Steps

- [Core Concepts](/core-concepts/dot-notation) - Learn about dot notation and wildcards
- [Main Classes](/main-classes/overview) - Explore all main classes
- [Examples](/examples) - Browse 90+ code examples
- [API Reference](/api) - Complete API documentation

