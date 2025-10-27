---
title: DataMutator
description: Write, merge, and unset values in nested data structures using dot-notation paths with wildcard support
---

DataMutator provides methods to modify nested data structures including arrays, objects, Dtos, Laravel Collections, and Eloquent Models. All operations work with references and modify the target in-place using a fluent API.

## Quick Example

```php
use event4u\DataHelpers\DataMutator;

// Set values in nested structure (fluent chaining)
$target = [];
DataMutator::make($target)
    ->set('user.profile.name', 'Alice')
    ->set('user.profile.email', 'alice@example.com');
// $target is now: ['user' => ['profile' => ['name' => 'Alice', 'email' => 'alice@example.com']]]

// Merge data
DataMutator::make($target)->merge('user.profile', ['age' => 30, 'city' => 'Berlin']);
// $target is now: ['user' => ['profile' => ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30, 'city' => 'Berlin']]]

// Update multiple items with wildcard
$data = ['users' => [['active' => false], ['active' => false]]];
DataMutator::make($data)->set('users.*.active', true);
// $data is now: ['users' => [['active' => true], ['active' => true]]]

// Remove values
$data = ['users' => [['name' => 'Alice', 'password' => 'secret'], ['name' => 'Bob', 'password' => 'secret2']]];
DataMutator::make($data)->unset('users.*.password');
// $data is now: ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]
```

## Introduction

DataMutator provides three main operations:

- **`set($path, $value)`** - Set a value at a dot-path
- **`merge($path, $data)`** - Merge data into an existing branch
- **`unset($path)`** - Remove a value at a path

All operations are chainable and modify the target in-place.

### Supported Data Types

DataMutator works with:

- **Arrays** - Nested arrays with any depth
- **Objects** - Plain PHP objects with public properties
- **Dtos** - Data Transfer Objects
- **Laravel Collections** - `Illuminate\Support\Collection`
- **Eloquent Models** - Including relationships

### Key Features

- **Fluent API** - Chainable methods for clean code
- **Reference mutations** - Modifies target in-place for performance
- **Wildcard support** - Modify multiple items at once
- **Deep wildcards** - Multiple wildcards in one path
- **Type preservation** - Maintains data types during operations

## set() - Setting Values

Set a value at a dot-notation path.

### Basic Usage

```php
use event4u\DataHelpers\DataMutator;

// Start with empty array
$target = [];

// Set nested value (fluent chaining)
DataMutator::make($target)
    ->set('user.profile.name', 'Alice')
    ->set('user.profile.email', 'alice@example.com')
    ->set('user.settings.theme', 'dark');

// $target is now: [
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
DataMutator::make($target)->set('user.name', 'Bob');
// $target = ['user' => ['name' => 'Bob']]
```

### Numeric Indices

```php
$target = [];

// Set values at specific indices
DataMutator::make($target)->set('users.0.name', 'Alice');
DataMutator::make($target)->set('users.1.name', 'Bob');
// $target = ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]
```

### Wildcards in set()

Use wildcards to set multiple values at once.

```php
// Set same value for all items
$data = ['users' => [['active' => false], ['active' => false], ['active' => false]]];
DataMutator::make($data)->set('users.*.active', true);
// $data = ['users' => [['active' => true], ['active' => true], ['active' => true]]]
```

### Wildcard with Accessor Results

When the source value comes from DataAccessor (with full path keys), wildcards expand automatically:

```php
use event4u\DataHelpers\DataAccessor;

$source = ['users' => [
    ['email' => 'alice@example.com'],
    ['email' => 'bob@example.com'],
]];

$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');
// $emails = ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com']

// Write to new structure with wildcard
$target = [];
DataMutator::make($target)->set('contacts.*.email', $emails);
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

DataMutator::make($data)->set('departments.*.users.*.active', true);
// $data = All 'active' fields set to true across all departments and users
```


## merge() - Merging Data

Merge data into an existing branch at a path.

### Basic Usage

```php
$target = ['config' => ['limits' => ['cpu' => 1]]];

// Merge additional data
DataMutator::make($target)->merge('config.limits', ['memory' => 512, 'disk' => 1024]);
// $target = ['config' => ['limits' => ['cpu' => 1, 'memory' => 512, 'disk' => 1024]]]
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

DataMutator::make($target)->merge('config', [
    'database' => ['charset' => 'utf8mb4'],
    'queue' => ['driver' => 'sync'],
]);

// $target = [
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

DataMutator::make($target)->merge('list', [11 => 'Y', 12 => 'Z']);
// $target = ['list' => [10 => 'x', 11 => 'Y', 12 => 'Z']]
```

### Merge Configuration

```php
$config = ['database' => ['host' => 'localhost']];

// Merge additional settings
DataMutator::make($config)->merge('database', [
    'port' => 3306,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

// $config = [
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
DataMutator::make($data)->merge('users.*', ['active' => true, 'verified' => true]);

// $data = [
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
DataMutator::make($target)->unset('user.password');
// $target = ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']]
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
DataMutator::make($data)->unset('users.*.password');
// $data = ['users' => [
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
DataMutator::make($data)->unset('orders.*.items.*.temp_id');
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
DataMutator::make($response)->unset('users.*.password');
DataMutator::make($response)->unset('users.*.api_key');
// $response = Only id and name remain for each user
```


## Working with Dtos and Objects

DataMutator works with plain PHP objects and Dtos.

### Basic Object Mutation

```php
$dto = new #[\AllowDynamicProperties] class {
    public string $name = '';
    public array $tags = [];
};

// Set property
DataMutator::make($dto)->set('name', 'Alice');

// Merge array property
DataMutator::make($dto)->merge('tags', ['php', 'laravel']);

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
DataMutator::make($user)->set('name', 'Alice');
DataMutator::make($user)->set('profile.bio', 'Software Engineer');
DataMutator::make($user)->set('profile.website', 'https://example.com');
```

### Working with Dtos

```php
$dto = new UserWithRolesDto();
DataMutator::make($dto)->set('name', 'Alice');
DataMutator::make($dto)->set('email', 'alice@example.com');
DataMutator::make($dto)->merge('roles', ['admin', 'editor']);
```

## Working with Eloquent Models

DataMutator works with Eloquent Models and their relationships.

### Basic Model Mutation

<!-- skip-test: Requires Laravel Eloquent -->
```php
$user = User::first();

// Update model attributes
DataMutator::make($user)->set('name', 'Alice Updated');
DataMutator::make($user)->set('email', 'alice.updated@example.com');

// Note: Changes are not automatically saved to database
$user->save();
```

### Updating Relationships

<!-- skip-test: Requires Laravel Eloquent -->
```php
$user = User::with('posts')->first();

// Update first post's title
DataMutator::make($user)->set('posts.0.title', 'Updated Title');

// Update all posts' status
DataMutator::make($user)->set('posts.*.published', true);

// Save changes
$user->push(); // Saves model and relationships
```

### Nested Relationships

<!-- skip-test: Requires Laravel Eloquent -->
```php
$user = User::with('posts.comments')->first();

// Update nested relationship
DataMutator::make($user)->set('posts.0.comments.0.text', 'Updated comment');

// Remove sensitive data from all comments
DataMutator::make($user)->unset('posts.*.comments.*.author_ip');
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
DataMutator::make($data)->set('users.*.active', true);
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
DataMutator::make($data)->set('orders.0.items.*.qty', 5);
```

## Common Patterns

### Build Nested Structure from Flat Data

```php
$target = [];

// Build structure step by step
DataMutator::make($target)->set('user.profile.name', 'Alice');
DataMutator::make($target)->set('user.profile.email', 'alice@example.com');
DataMutator::make($target)->set('user.profile.age', 30);
DataMutator::make($target)->set('user.settings.theme', 'dark');
DataMutator::make($target)->set('user.settings.language', 'en');

// $target = [
//   'user' => [
//     'profile' => ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
//     'settings' => ['theme' => 'dark', 'language' => 'en']
//   ]
// ]
```

### Bulk Update with Accessor Results

```php
use event4u\DataHelpers\DataAccessor;

// Read from source
$source = ['users' => [
    ['email' => 'alice@example.com', 'verified' => false],
    ['email' => 'bob@example.com', 'verified' => false],
]];

$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');

// Write to target
$target = [];
DataMutator::make($target)->set('contacts.*.email', $emails);
DataMutator::make($target)->set('contacts.*.verified', true);
```

### Configuration Management

```php
// Start with base config
$config = [
    'app' => ['name' => 'MyApp', 'env' => 'production'],
    'database' => ['host' => 'localhost'],
];

// Merge environment-specific config
DataMutator::make($config)->merge('database', [
    'port' => 3306,
    'charset' => 'utf8mb4',
    'prefix' => 'app_',
]);

// Add cache config
DataMutator::make($config)->merge('cache', [
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
DataMutator::make($data)->unset('users.*.password');
DataMutator::make($data)->unset('users.*.api_key');
DataMutator::make($data)->unset('users.*.internal_notes');

// $data = Only id and name remain
```


## Best Practices

### Use Fluent API

DataMutator uses a fluent API with `make()` factory method:

```php
// ✅ Correct - fluent API
$data = [];
DataMutator::make($data)->set('user.name', 'Alice');

// ✅ Also correct - chaining
DataMutator::make($data)
    ->set('user.name', 'Alice')
    ->set('user.email', 'alice@example.com');
```

### Use Wildcards for Bulk Operations

Instead of looping, use wildcards for better performance:

```php
// ❌ Inefficient
foreach ($data['users'] as $key => $user) {
    $data['users'][$key]['active'] = true;
}

// ✅ Efficient
DataMutator::make($data)->set('users.*.active', true);
```

### Combine with DataAccessor

Use DataAccessor to read and DataMutator to write:

```php
use event4u\DataHelpers\DataAccessor;

// Read from source
$source = ['users' => [['email' => 'alice@example.com'], ['email' => 'bob@example.com']]];
$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');

// Write to target
$target = [];
DataMutator::make($target)->set('contacts.*.email', $emails);
```

### Use DataMapper for Complex Transformations

For complex data transformations, use DataMapper instead of multiple DataMutator calls:

```php
// ❌ Multiple mutations
$target = [];
DataMutator::make($target)->set('name', $source['user']['name']);
DataMutator::make($target)->set('email', $source['user']['email']);
DataMutator::make($target)->set('age', $source['user']['profile']['age']);

// ✅ Use DataMapper
$target = DataMapper::source($source)
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
DataMutator::make($config)->set('app.name', 'MyApp');
DataMutator::make($config)->set('app.env', 'production');
DataMutator::make($config)->merge('database', ['host' => 'localhost', 'port' => 3306]);
DataMutator::make($config)->merge('cache', ['driver' => 'redis']);
```

## Performance Notes

### Wildcard Performance

- Wildcards traverse all matching elements
- Performance scales with the number of matches
- For large datasets, consider filtering data first

```php
// ❌ Slow on large datasets
DataMutator::make($hugeDataset)->set('users.*.active', true);

// ✅ Filter first
$activeUsers = array_filter($hugeDataset['users'], fn($u) => $u['status'] === 'pending');
$data = ['users' => $activeUsers];
DataMutator::make($data)->set('users.*.active', true);
```

### Deep Wildcards

Multiple wildcards can be expensive on large nested structures:

```php
// Can be slow on large datasets
DataMutator::make($data)->set('departments.*.teams.*.users.*.active', true);

// Consider limiting depth or using DataMapper
```

### Batch Operations

For very large datasets, consider batching operations:

```php
// Process in chunks
$chunks = array_chunk($data['users'], 1000);
foreach ($chunks as $chunk) {
    $chunkData = ['users' => $chunk];
    DataMutator::make($chunkData)->set('users.*.active', true);
    // Process chunk
}
```

## Reference Mutations

DataMutator works with references and modifies the target in-place:

```php
$data = ['user' => ['name' => 'Alice']];
DataMutator::make($data)->set('user.name', 'Bob');

// $data is now modified
// ['user' => ['name' => 'Bob']]

// To preserve the original, clone it first
$original = ['user' => ['name' => 'Alice']];
$copy = $original;
DataMutator::make($copy)->set('user.name', 'Bob');
// $original is unchanged, $copy is modified
```

This ensures:
- **Predictability** - Original data is never modified
- **Safety** - No unexpected side effects
- **Testability** - Easy to test and debug
- **Functional style** - Supports functional programming patterns

## Code Examples

The following working examples demonstrate DataMutator in action:

- [**Basic Usage**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mutator/basic-usage.php) - Complete example showing set, merge, and unset operations with wildcards

All examples are fully tested and can be run directly:

```bash
php examples/main-classes/data-mutator/basic-usage.php
```

## Related Tests

The functionality is thoroughly tested. Key test files:

- [DataMutatorTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMutator/DataMutatorTest.php) - Core functionality tests
- [DataMutatorDoctrineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMutator/DataMutatorDoctrineTest.php) - Doctrine integration tests
- [DataMutatorLaravelTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMutator/DataMutatorLaravelTest.php) - Laravel integration tests
- [DataMutatorIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Integration/DataMutatorIntegrationTest.php) - End-to-end scenarios

Run the tests:

```bash
# Run all DataMutator tests
task test:unit -- --filter=DataMutator

# Run specific test file
vendor/bin/pest tests/Unit/DataMutator/DataMutatorTest.php
```
## See Also

- [DataAccessor](/data-helpers/main-classes/data-accessor/) - Read nested data
- [DataMapper](/data-helpers/main-classes/data-mapper/) - Transform data structures
- [DataFilter](/data-helpers/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Dot-Notation](/data-helpers/core-concepts/dot-notation/) - Path syntax
- [Core Concepts: Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard operators
- [Examples](/data-helpers/examples/) - 90+ code examples
