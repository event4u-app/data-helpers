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

$data = [
    'user' => [
        'name' => 'John Doe',
        'orders' => [
            ['id' => 1, 'total' => 100],
            ['id' => 2, 'total' => 200],
        ],
    ],
];

// Set nested value
$data = DataMutator::set($data, 'user.phone', '+1234567890');

// Set multiple values with wildcards
$data = DataMutator::set($data, 'user.orders.*.status', 'shipped');
// $data = ['user' => ['name' => 'John Doe', 'orders' => [['id' => 1, 'total' => 100, 'status' => 'shipped'], ['id' => 2, 'total' => 200, 'status' => 'shipped']], 'phone' => '+1234567890']]
```

### DataMapper - Transform Data (Fluent API)

```php
use event4u\DataHelpers\DataMapper;

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

// Fluent API with template-based mapping
$result = DataMapper::from($data)
    ->template([
        'customer_name' => '{{ user.name }}',
        'customer_email' => '{{ user.email }}',
        'total_orders' => '{{ user.orders | count }}',
        'order_ids' => '{{ user.orders.*.id }}',
    ])
    ->map()
    ->getTarget();
// $result = ['customer_name' => 'John Doe', 'customer_email' => 'john@example.com', 'total_orders' => 2, 'order_ids' => [1, 2]]
```

With WHERE/ORDER BY in template (recommended for database-stored templates):

```php
use event4u\DataHelpers\DataMapper;

$data = [
    'user' => [
        'orders' => [
            ['id' => 1, 'total' => 100],
            ['id' => 2, 'total' => 200],
            ['id' => 3, 'total' => 150],
        ],
    ],
];

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
// $result = ['items' => [['id' => 2, 'total' => 200], ['id' => 3, 'total' => 150]]]
```

### DataFilter - Query Data

```php
use event4u\DataHelpers\DataFilter;

$orders = [
    ['id' => 1, 'total' => 100],
    ['id' => 2, 'total' => 200],
    ['id' => 3, 'total' => 150],
];

$expensive = DataFilter::query($orders)
    ->where('total', '>', 150)
    ->orderBy('total', 'DESC')
    ->get();
// $expensive = [['id' => 2, 'total' => 200]]
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

