---
title: DataAccessor
description: Read values from nested data structures using dot-notation paths with wildcard support
---

DataAccessor provides a uniform way to read values from nested data structures including arrays, objects, Laravel Collections, and Eloquent Models. It supports dot-notation paths, numeric indices, and powerful wildcard operations.

## Quick Example

```php
use Event4u\DataHelpers\DataAccessor;

$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 25],
        ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 35],
    ],
];

$accessor = new DataAccessor($data);

// Simple path
$name = $accessor->get('users.0.name');
// Returns: 'Alice'

// Wildcard - extract all emails
$emails = $accessor->get('users.*.email');
// Returns: [
//   'users.0.email' => 'alice@example.com',
//   'users.1.email' => 'bob@example.com',
//   'users.2.email' => 'charlie@example.com',
// ]

// Default value
$country = $accessor->get('users.0.country', 'Unknown');
// Returns: 'Unknown' (path doesn't exist)
```

## Overview

DataAccessor works with multiple data types:

- **Arrays** - Nested arrays with any depth
- **Objects** - Plain PHP objects with public properties
- **DTOs** - Data Transfer Objects
- **Laravel Collections** - `Illuminate\Support\Collection`
- **Eloquent Models** - Including relationships
- **Arrayable** - Any object implementing `Arrayable`
- **JsonSerializable** - Any object implementing `JsonSerializable`
- **JSON strings** - Automatically parsed
- **XML strings** - Automatically parsed

## Basic Usage

### Creating an Accessor

```php
use Event4u\DataHelpers\DataAccessor;

// From array
$accessor = new DataAccessor($array);

// From object
$accessor = new DataAccessor($object);

// From Collection
$accessor = new DataAccessor($collection);

// From Eloquent Model
$accessor = new DataAccessor($model);

// From JSON string
$accessor = new DataAccessor('{"user":{"name":"Alice"}}');

// From XML string
$accessor = new DataAccessor('<user><name>Alice</name></user>');
```

### Reading Values

```php
$data = [
    'user' => [
        'profile' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
    ],
];

$accessor = new DataAccessor($data);

// Dot-notation path
$name = $accessor->get('user.profile.name');
// Returns: 'John Doe'

// Non-existent path returns null
$phone = $accessor->get('user.profile.phone');
// Returns: null
```

### Default Values

```php
// Provide default value as second parameter
$phone = $accessor->get('user.profile.phone', 'N/A');
// Returns: 'N/A' (path doesn't exist)

$name = $accessor->get('user.profile.name', 'Anonymous');
// Returns: 'John Doe' (path exists, default ignored)
```

## Wildcards

Wildcards allow you to extract values from multiple items at once.

### Basic Wildcards

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

// Returns associative array with full paths as keys:
// [
//   'users.0.email' => 'alice@example.com',
//   'users.1.email' => 'bob@example.com',
//   'users.2.email' => 'charlie@example.com',
// ]
```

### Why Full Path Keys?

The full path keys are intentional and provide:

1. **Stability** - Keys remain consistent across operations
2. **Traceability** - You know exactly where each value came from
3. **Integration** - DataMutator and DataMapper consume this format
4. **Uniqueness** - No key collisions in complex structures

### Null Values in Wildcards

```php
$data = [
    'users' => [
        ['email' => 'alice@example.com'],
        ['email' => null],
        ['email' => 'bob@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');

// Returns:
// [
//   'users.0.email' => 'alice@example.com',
//   'users.1.email' => null,
//   'users.2.email' => 'bob@example.com',
// ]

// Filter out nulls if needed
$validEmails = array_filter($emails, fn($v) => $v !== null);
// [
//   'users.0.email' => 'alice@example.com',
//   'users.2.email' => 'bob@example.com',
// ]
```


## Deep Wildcards

Multiple wildcards in one path create a flat associative array with full dot-path keys.

### Multiple Wildcards

```php
$data = [
    'users' => [
        [
            'name' => 'Alice',
            'addresses' => [
                'home' => ['city' => 'Berlin'],
                'work' => ['city' => 'Hamburg'],
            ],
        ],
        [
            'name' => 'Bob',
            'addresses' => [
                'home' => ['city' => 'Munich'],
            ],
        ],
    ],
];

$accessor = new DataAccessor($data);
$cities = $accessor->get('users.*.addresses.*.city');

// Returns:
// [
//   'users.0.addresses.home.city' => 'Berlin',
//   'users.0.addresses.work.city' => 'Hamburg',
//   'users.1.addresses.home.city' => 'Munich',
// ]
```

### Three-Level Wildcards

```php
$data = [
    'departments' => [
        [
            'users' => [
                ['posts' => [['title' => 'Post 1'], ['title' => 'Post 2']]],
                ['posts' => [['title' => 'Post 3']]],
            ],
        ],
        [
            'users' => [
                ['posts' => [['title' => 'Post 4']]],
            ],
        ],
    ],
];

$accessor = new DataAccessor($data);
$titles = $accessor->get('departments.*.users.*.posts.*.title');

// Returns:
// [
//   'departments.0.users.0.posts.0.title' => 'Post 1',
//   'departments.0.users.0.posts.1.title' => 'Post 2',
//   'departments.0.users.1.posts.0.title' => 'Post 3',
//   'departments.1.users.0.posts.0.title' => 'Post 4',
// ]
```

## Working with Collections

DataAccessor seamlessly handles Laravel Collections.

### Collection Input

```php
use Illuminate\Support\Collection;

$data = [
    'users' => collect([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]),
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');

// Returns:
// [
//   'users.0.email' => 'alice@example.com',
//   'users.1.email' => 'bob@example.com',
// ]
```

### Nested Collections

```php
$data = [
    'orders' => collect([
        [
            'items' => collect([
                ['sku' => 'A', 'price' => 10],
                ['sku' => 'B', 'price' => 20],
            ]),
        ],
        [
            'items' => collect([
                ['sku' => 'C', 'price' => 30],
            ]),
        ],
    ]),
];

$accessor = new DataAccessor($data);
$skus = $accessor->get('orders.*.items.*.sku');

// Returns:
// [
//   'orders.0.items.0.sku' => 'A',
//   'orders.0.items.1.sku' => 'B',
//   'orders.1.items.0.sku' => 'C',
// ]
```

### Accessing by Index

```php
$data = [
    'users' => collect([
        ['name' => 'Alice'],
        ['name' => 'Bob'],
    ]),
];

$accessor = new DataAccessor($data);

// Access specific index
$firstUser = $accessor->get('users.0.name');
// Returns: 'Alice'

// Wildcard still works
$allNames = $accessor->get('users.*.name');
// Returns: ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']
```

## Working with Eloquent Models

DataAccessor works with Eloquent Models and their relationships.

### Basic Model Access

```php
$user = User::find(1);
$accessor = new DataAccessor($user);

$name = $accessor->get('name');
$email = $accessor->get('email');
```

### Accessing Relationships

```php
$user = User::with('posts.comments')->first();
$accessor = new DataAccessor($user);

// Access relationship
$postTitles = $accessor->get('posts.*.title');

// Deep relationship access
$commentTexts = $accessor->get('posts.*.comments.*.text');
```

### Model Collections

```php
$users = User::with('posts')->get();
$accessor = new DataAccessor(['users' => $users]);

// Extract all post titles from all users
$allPostTitles = $accessor->get('users.*.posts.*.title');
```


## JSON and XML Input

DataAccessor automatically parses JSON and XML strings.

### JSON Strings

```php
$json = '{"users":[{"name":"Alice","age":30},{"name":"Bob","age":25}]}';
$accessor = new DataAccessor($json);

$names = $accessor->get('users.*.name');
// Returns: ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']

$firstAge = $accessor->get('users.0.age');
// Returns: 30
```

### XML Strings

```php
$xml = '<users><user><name>Alice</name></user><user><name>Bob</name></user></users>';
$accessor = new DataAccessor($xml);

$names = $accessor->get('users.user.*.name');
// Returns parsed XML as array structure
```

## Common Patterns

### Extract All Values from Nested Structure

```php
$data = [
    'departments' => [
        ['users' => [['email' => 'a@x.com'], ['email' => 'b@x.com']]],
        ['users' => [['email' => 'c@x.com']]],
    ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('departments.*.users.*.email');

// Returns:
// [
//   'departments.0.users.0.email' => 'a@x.com',
//   'departments.0.users.1.email' => 'b@x.com',
//   'departments.1.users.0.email' => 'c@x.com',
// ]
```

### Safe Access with Default

```php
$accessor = new DataAccessor($config);

// Always provide sensible defaults
$theme = $accessor->get('app.settings.theme', 'default');
$timeout = $accessor->get('app.settings.timeout', 30);
$debug = $accessor->get('app.settings.debug', false);
```

### Combining with Array Functions

```php
$accessor = new DataAccessor($data);
$prices = $accessor->get('products.*.price');

// Calculate total
$totalPrice = array_sum($prices);

// Calculate average
$avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0;

// Find max/min
$maxPrice = max($prices);
$minPrice = min($prices);
```

### Root-Level Numeric Indices

```php
$data = [
    ['name' => 'Alice', 'age' => 30],
    ['name' => 'Bob', 'age' => 25],
];

$accessor = new DataAccessor($data);

// Access specific index
$firstUser = $accessor->get('0.name');
// Returns: 'Alice'

// Use wildcard at root level
$allNames = $accessor->get('*.name');
// Returns: ['0.name' => 'Alice', '1.name' => 'Bob']
```

### Filter Null Values

```php
$data = ['users' => [
    ['email' => 'alice@x.com'],
    ['email' => null],
    ['email' => 'bob@x.com'],
]];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');

// Filter out nulls
$validEmails = array_filter($emails, fn($v) => $v !== null);
// Returns: ['users.0.email' => 'alice@x.com', 'users.2.email' => 'bob@x.com']

// Get only values (remove keys)
$emailList = array_values($validEmails);
// Returns: ['alice@x.com', 'bob@x.com']
```

## Best Practices

### Use Wildcards for Bulk Reads

When you need to extract the same field from multiple items, wildcards are more efficient than looping:

```php
// ❌ Inefficient
$emails = [];
foreach ($data['users'] as $user) {
    $emails[] = $user['email'];
}

// ✅ Efficient
$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');
```

### Combine with DataMutator

Use DataAccessor to read values and DataMutator to write them into a new structure:

```php
$accessor = new DataAccessor($sourceData);
$emails = $accessor->get('users.*.email');

$mutator = new DataMutator([]);
$mutator->set('contacts.*.email', $emails);
```

### Always Provide Defaults

Avoid null checks by providing sensible defaults:

```php
// ❌ Requires null check
$theme = $accessor->get('settings.theme');
if ($theme === null) {
    $theme = 'default';
}

// ✅ Clean and safe
$theme = $accessor->get('settings.theme', 'default');
```

### Leverage Collections

DataAccessor works seamlessly with Laravel Collections:

```php
$accessor = new DataAccessor($data);
$prices = $accessor->get('products.*.price');

// Convert to Collection for chaining
$collection = collect($prices);
$filtered = $collection->filter(fn($p) => $p > 100)->values();
```

## Performance Notes

### Wildcard Performance

- Wildcards traverse all matching elements
- Performance scales with the number of matches
- For large datasets, consider filtering data first

```php
// ❌ Slow on large datasets
$accessor = new DataAccessor($hugeDataset);
$allEmails = $accessor->get('users.*.email');

// ✅ Filter first
$activeUsers = array_filter($hugeDataset['users'], fn($u) => $u['active']);
$accessor = new DataAccessor(['users' => $activeUsers]);
$emails = $accessor->get('users.*.email');
```

### Deep Wildcards

Multiple wildcards can be expensive on large nested structures:

```php
// Can be slow on large datasets
$accessor->get('departments.*.teams.*.users.*.email');

// Consider limiting depth or filtering
```

### Caching

DataAccessor uses internal caching for path resolution, so repeated calls with the same path are fast:

```php
$accessor = new DataAccessor($data);

// First call parses path
$value1 = $accessor->get('user.profile.name');

// Subsequent calls use cached path (fast)
$value2 = $accessor->get('user.profile.name');
```

## See Also

- [DataMutator](/main-classes/data-mutator/) - Modify nested data
- [DataMapper](/main-classes/data-mapper/) - Transform data structures
- [DataFilter](/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Dot-Notation](/core-concepts/dot-notation/) - Path syntax
- [Core Concepts: Wildcards](/core-concepts/wildcards/) - Wildcard operators
- [Examples](/examples/) - 90+ code examples
