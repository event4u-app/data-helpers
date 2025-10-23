---
title: DataMutator
description: Write, merge, and unset values in nested data structures using dot-notation paths with wildcard support
---

DataMutator provides methods to modify nested data structures including arrays, objects, DTOs, Laravel Collections, and Eloquent Models. All operations are pure and return a new/updated structure without side effects.

## Quick Example

```php
use Event4u\DataHelpers\DataMutator;

// Set values in nested structure
$target = [];
$target = DataMutator::set($target, 'user.profile.name', 'Alice');
$target = DataMutator::set($target, 'user.profile.email', 'alice@example.com');
// Result: ['user' => ['profile' => ['name' => 'Alice', 'email' => 'alice@example.com']]]

// Merge data
$target = DataMutator::merge($target, 'user.profile', ['age' => 30, 'city' => 'Berlin']);
// Result: ['user' => ['profile' => ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30, 'city' => 'Berlin']]]

// Update multiple items with wildcard
$data = ['users' => [['active' => false], ['active' => false]]];
$data = DataMutator::set($data, 'users.*.active', true);
// Result: ['users' => [['active' => true], ['active' => true]]]

// Remove values
$data = ['users' => [['name' => 'Alice', 'password' => 'secret'], ['name' => 'Bob', 'password' => 'secret2']]];
$data = DataMutator::unset($data, 'users.*.password');
// Result: ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]
```

## Overview

DataMutator provides three main operations:

- **`set($target, $path, $value)`** - Set a value at a dot-path
- **`merge($target, $path, $data)`** - Merge data into an existing branch
- **`unset($target, $path)`** - Remove a value at a path

### Supported Data Types

DataMutator works with:

- **Arrays** - Nested arrays with any depth
- **Objects** - Plain PHP objects with public properties
- **DTOs** - Data Transfer Objects
- **Laravel Collections** - `Illuminate\Support\Collection`
- **Eloquent Models** - Including relationships

### Key Features

- **Pure operations** - All methods return new/updated structures
- **Wildcard support** - Modify multiple items at once
- **Deep wildcards** - Multiple wildcards in one path
- **Type preservation** - Maintains data types during operations
- **Immutable** - Original data is never modified

## set() - Setting Values

Set a value at a dot-notation path.

### Basic Usage

```php
use Event4u\DataHelpers\DataMutator;

// Start with empty array
$target = [];

// Set nested value
$target = DataMutator::set($target, 'user.profile.name', 'Alice');
// Result: ['user' => ['profile' => ['name' => 'Alice']]]

// Add more values
$target = DataMutator::set($target, 'user.profile.email', 'alice@example.com');
$target = DataMutator::set($target, 'user.settings.theme', 'dark');
// Result: [
//   'user' => [
//     'profile' => ['name' => 'Alice', 'email' => 'alice@example.com'],
//     'settings' => ['theme' => 'dark']
//   ]
// ]
```

### Overwriting Values

```php
$target = ['user' => ['name' => 'Alice']];

// Overwrite existing value
$target = DataMutator::set($target, 'user.name', 'Bob');
// Result: ['user' => ['name' => 'Bob']]
```

### Numeric Indices

```php
$target = [];

// Set values at specific indices
$target = DataMutator::set($target, 'users.0.name', 'Alice');
$target = DataMutator::set($target, 'users.1.name', 'Bob');
// Result: ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]
```

### Wildcards in set()

Use wildcards to set multiple values at once.

```php
// Set same value for all items
$data = ['users' => [['active' => false], ['active' => false], ['active' => false]]];
$data = DataMutator::set($data, 'users.*.active', true);
// Result: ['users' => [['active' => true], ['active' => true], ['active' => true]]]
```

### Wildcard with Accessor Results

When the source value comes from DataAccessor (with full path keys), wildcards expand automatically:

```php
use Event4u\DataHelpers\DataAccessor;

$source = ['users' => [
    ['email' => 'alice@example.com'],
    ['email' => 'bob@example.com'],
]];

$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');
// Returns: ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com']

// Write to new structure with wildcard
$target = [];
$target = DataMutator::set($target, 'contacts.*.email', $emails);
// Result: ['contacts' => [
//   0 => ['email' => 'alice@example.com'],
//   1 => ['email' => 'bob@example.com']
// ]]
```

### Deep Wildcards

Multiple wildcards are supported:

```php
$data = [
    'departments' => [
        ['users' => [['active' => false], ['active' => false]]],
        ['users' => [['active' => false]]],
    ],
];

$data = DataMutator::set($data, 'departments.*.users.*.active', true);
// Result: All 'active' fields set to true across all departments and users
```


## merge() - Merging Data

Merge data into an existing branch at a path.

### Basic Usage

```php
$target = ['config' => ['limits' => ['cpu' => 1]]];

// Merge additional data
$target = DataMutator::merge($target, 'config.limits', ['memory' => 512, 'disk' => 1024]);
// Result: ['config' => ['limits' => ['cpu' => 1, 'memory' => 512, 'disk' => 1024]]]
```

### Merge Strategy

DataMutator uses different merge strategies depending on array type:

#### Associative Arrays

Associative arrays are deep-merged recursively:

```php
$target = [
    'config' => [
        'database' => ['host' => 'localhost', 'port' => 3306],
        'cache' => ['driver' => 'redis'],
    ],
];

$target = DataMutator::merge($target, 'config', [
    'database' => ['charset' => 'utf8mb4'],
    'queue' => ['driver' => 'sync'],
]);

// Result: [
//   'config' => [
//     'database' => ['host' => 'localhost', 'port' => 3306, 'charset' => 'utf8mb4'],
//     'cache' => ['driver' => 'redis'],
//     'queue' => ['driver' => 'sync']
//   ]
// ]
```

#### Numeric-Indexed Arrays

Numeric-indexed arrays use index-based replacement (not append) for deterministic mapping:

```php
$target = ['list' => [10 => 'x', 11 => 'y']];

$target = DataMutator::merge($target, 'list', [11 => 'Y', 12 => 'Z']);
// Result: ['list' => [10 => 'x', 11 => 'Y', 12 => 'Z']]
```

### Merge Configuration

```php
$config = ['database' => ['host' => 'localhost']];

// Merge additional settings
$config = DataMutator::merge($config, 'database', [
    'port' => 3306,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

// Result: [
//   'database' => [
//     'host' => 'localhost',
//     'port' => 3306,
//     'charset' => 'utf8mb4',
//     'collation' => 'utf8mb4_unicode_ci'
//   ]
// ]
```

### Merge with Wildcards

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'role' => 'user'],
        ['name' => 'Bob', 'role' => 'user'],
    ],
];

// Merge additional data for all users
$data = DataMutator::merge($data, 'users.*', ['active' => true, 'verified' => true]);

// Result: [
//   'users' => [
//     ['name' => 'Alice', 'role' => 'user', 'active' => true, 'verified' => true],
//     ['name' => 'Bob', 'role' => 'user', 'active' => true, 'verified' => true]
//   ]
// ]
```

## unset() - Removing Values

Remove values at a path. Supports wildcards for bulk removal.

### Basic Usage

```php
$target = [
    'user' => [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'secret',
    ],
];

// Remove password
$target = DataMutator::unset($target, 'user.password');
// Result: ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']]
```

### Remove with Wildcards

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'password' => 'secret1'],
        ['name' => 'Bob', 'password' => 'secret2'],
        ['name' => 'Charlie', 'password' => 'secret3'],
    ],
];

// Remove all passwords
$data = DataMutator::unset($data, 'users.*.password');
// Result: ['users' => [
//   ['name' => 'Alice'],
//   ['name' => 'Bob'],
//   ['name' => 'Charlie']
// ]]
```

### Deep Wildcard Removal

```php
$data = [
    'orders' => [
        [
            'items' => [
                ['temp_id' => 'x', 'sku' => 'A', 'price' => 10],
                ['temp_id' => 'y', 'sku' => 'B', 'price' => 20],
            ],
        ],
        [
            'items' => [
                ['temp_id' => 'z', 'sku' => 'C', 'price' => 30],
            ],
        ],
    ],
];

// Remove all temp_id fields
$data = DataMutator::unset($data, 'orders.*.items.*.temp_id');
// Result: All temp_id fields removed from nested items
```

### Remove Sensitive Data

```php
$response = [
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'password' => 'hash1', 'api_key' => 'key1'],
        ['id' => 2, 'name' => 'Bob', 'password' => 'hash2', 'api_key' => 'key2'],
    ],
];

// Remove sensitive fields
$response = DataMutator::unset($response, 'users.*.password');
$response = DataMutator::unset($response, 'users.*.api_key');
// Result: Only id and name remain for each user
```


## Working with DTOs and Objects

DataMutator works with plain PHP objects and DTOs.

### Basic Object Mutation

```php
$dto = new #[\AllowDynamicProperties] class {
    public string $name = '';
    public array $tags = [];
};

// Set property
$dto = DataMutator::set($dto, 'name', 'Alice');

// Merge array property
$dto = DataMutator::merge($dto, 'tags', ['php', 'laravel']);

// Result: Object with name='Alice' and tags=['php', 'laravel']
```

### Nested Object Properties

```php
class Profile {
    public string $bio = '';
    public string $website = '';
}

class User {
    public string $name = '';
    public Profile $profile;

    public function __construct() {
        $this->profile = new Profile();
    }
}

$user = new User();
$user = DataMutator::set($user, 'name', 'Alice');
$user = DataMutator::set($user, 'profile.bio', 'Software Engineer');
$user = DataMutator::set($user, 'profile.website', 'https://example.com');
```

### Working with DTOs

```php
class UserDTO {
    public function __construct(
        public string $name = '',
        public string $email = '',
        public array $roles = [],
    ) {}
}

$dto = new UserDTO();
$dto = DataMutator::set($dto, 'name', 'Alice');
$dto = DataMutator::set($dto, 'email', 'alice@example.com');
$dto = DataMutator::merge($dto, 'roles', ['admin', 'editor']);
```

## Working with Eloquent Models

DataMutator works with Eloquent Models and their relationships.

### Basic Model Mutation

```php
$user = User::first();

// Update model attributes
$user = DataMutator::set($user, 'name', 'Alice Updated');
$user = DataMutator::set($user, 'email', 'alice.updated@example.com');

// Note: Changes are not automatically saved to database
$user->save();
```

### Updating Relationships

```php
$user = User::with('posts')->first();

// Update first post's title
$user = DataMutator::set($user, 'posts.0.title', 'Updated Title');

// Update all posts' status
$user = DataMutator::set($user, 'posts.*.published', true);

// Save changes
$user->push(); // Saves model and relationships
```

### Nested Relationships

```php
$user = User::with('posts.comments')->first();

// Update nested relationship
$user = DataMutator::set($user, 'posts.0.comments.0.text', 'Updated comment');

// Remove sensitive data from all comments
$user = DataMutator::unset($user, 'posts.*.comments.*.author_ip');
```

## Working with Collections

DataMutator works with Laravel Collections.

### Collection Mutation

```php
use Illuminate\Support\Collection;

$data = [
    'users' => collect([
        ['name' => 'Alice', 'active' => false],
        ['name' => 'Bob', 'active' => false],
    ]),
];

// Update all users
$data = DataMutator::set($data, 'users.*.active', true);
```

### Nested Collections

```php
$data = [
    'orders' => collect([
        [
            'items' => collect([
                ['sku' => 'A', 'qty' => 1],
                ['sku' => 'B', 'qty' => 2],
            ]),
        ],
    ]),
];

// Update nested collection items
$data = DataMutator::set($data, 'orders.0.items.*.qty', 5);
```

## Common Patterns

### Build Nested Structure from Flat Data

```php
$target = [];

// Build structure step by step
$target = DataMutator::set($target, 'user.profile.name', 'Alice');
$target = DataMutator::set($target, 'user.profile.email', 'alice@example.com');
$target = DataMutator::set($target, 'user.profile.age', 30);
$target = DataMutator::set($target, 'user.settings.theme', 'dark');
$target = DataMutator::set($target, 'user.settings.language', 'en');

// Result: [
//   'user' => [
//     'profile' => ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
//     'settings' => ['theme' => 'dark', 'language' => 'en']
//   ]
// ]
```

### Bulk Update with Accessor Results

```php
use Event4u\DataHelpers\DataAccessor;

// Read from source
$source = ['users' => [
    ['email' => 'alice@example.com', 'verified' => false],
    ['email' => 'bob@example.com', 'verified' => false],
]];

$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');

// Write to target
$target = [];
$target = DataMutator::set($target, 'contacts.*.email', $emails);
$target = DataMutator::set($target, 'contacts.*.verified', true);
```

### Configuration Management

```php
// Start with base config
$config = [
    'app' => ['name' => 'MyApp', 'env' => 'production'],
    'database' => ['host' => 'localhost'],
];

// Merge environment-specific config
$config = DataMutator::merge($config, 'database', [
    'port' => 3306,
    'charset' => 'utf8mb4',
    'prefix' => 'app_',
]);

// Add cache config
$config = DataMutator::merge($config, 'cache', [
    'driver' => 'redis',
    'prefix' => 'myapp',
]);
```

### Data Sanitization

```php
$data = [
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'password' => 'hash1', 'api_key' => 'key1', 'internal_notes' => 'notes1'],
        ['id' => 2, 'name' => 'Bob', 'password' => 'hash2', 'api_key' => 'key2', 'internal_notes' => 'notes2'],
    ],
];

// Remove all sensitive fields
$data = DataMutator::unset($data, 'users.*.password');
$data = DataMutator::unset($data, 'users.*.api_key');
$data = DataMutator::unset($data, 'users.*.internal_notes');

// Result: Only id and name remain
```


## Best Practices

### Always Assign Return Value

DataMutator operations are pure and return a new/updated structure. Always assign the return value:

```php
// ✅ Correct
$target = DataMutator::set($target, 'user.name', 'Alice');

// ❌ Wrong - changes are lost
DataMutator::set($target, 'user.name', 'Alice');
```

### Use Wildcards for Bulk Operations

Instead of looping, use wildcards for better performance:

```php
// ❌ Inefficient
foreach ($data['users'] as $key => $user) {
    $data['users'][$key]['active'] = true;
}

// ✅ Efficient
$data = DataMutator::set($data, 'users.*.active', true);
```

### Combine with DataAccessor

Use DataAccessor to read and DataMutator to write:

```php
use Event4u\DataHelpers\DataAccessor;

// Read from source
$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');

// Write to target
$target = [];
$target = DataMutator::set($target, 'contacts.*.email', $emails);
```

### Use DataMapper for Complex Transformations

For complex data transformations, use DataMapper instead of multiple DataMutator calls:

```php
// ❌ Multiple mutations
$target = [];
$target = DataMutator::set($target, 'name', $source['user']['name']);
$target = DataMutator::set($target, 'email', $source['user']['email']);
$target = DataMutator::set($target, 'age', $source['user']['profile']['age']);

// ✅ Use DataMapper
$target = DataMapper::from($source)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'age' => '{{ user.profile.age }}',
    ])
    ->map()
    ->getTarget();
```

### Chain Operations

Chain multiple operations for cleaner code:

```php
$config = [];
$config = DataMutator::set($config, 'app.name', 'MyApp');
$config = DataMutator::set($config, 'app.env', 'production');
$config = DataMutator::merge($config, 'database', ['host' => 'localhost', 'port' => 3306]);
$config = DataMutator::merge($config, 'cache', ['driver' => 'redis']);
```

## Performance Notes

### Wildcard Performance

- Wildcards traverse all matching elements
- Performance scales with the number of matches
- For large datasets, consider filtering data first

```php
// ❌ Slow on large datasets
$data = DataMutator::set($hugeDataset, 'users.*.active', true);

// ✅ Filter first
$activeUsers = array_filter($hugeDataset['users'], fn($u) => $u['status'] === 'pending');
$data = ['users' => $activeUsers];
$data = DataMutator::set($data, 'users.*.active', true);
```

### Deep Wildcards

Multiple wildcards can be expensive on large nested structures:

```php
// Can be slow on large datasets
$data = DataMutator::set($data, 'departments.*.teams.*.users.*.active', true);

// Consider limiting depth or using DataMapper
```

### Batch Operations

For very large datasets, consider batching operations:

```php
// Process in chunks
$chunks = array_chunk($data['users'], 1000);
foreach ($chunks as $chunk) {
    $chunk = DataMutator::set(['users' => $chunk], 'users.*.active', true);
    // Process chunk
}
```

## Immutability

All DataMutator operations are pure and return new/updated structures:

```php
$original = ['user' => ['name' => 'Alice']];
$updated = DataMutator::set($original, 'user.name', 'Bob');

// $original is unchanged
// ['user' => ['name' => 'Alice']]

// $updated has the new value
// ['user' => ['name' => 'Bob']]
```

This ensures:
- **Predictability** - Original data is never modified
- **Safety** - No unexpected side effects
- **Testability** - Easy to test and debug
- **Functional style** - Supports functional programming patterns

## See Also

- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataMapper](/main-classes/data-mapper/) - Transform data structures
- [DataFilter](/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Dot-Notation](/core-concepts/dot-notation/) - Path syntax
- [Core Concepts: Wildcards](/core-concepts/wildcards/) - Wildcard operators
- [Examples](/examples/) - 90+ code examples
