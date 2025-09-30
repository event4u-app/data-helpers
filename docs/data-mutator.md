# Data Mutator

Write, merge, and unset values in nested data using dot-paths with wildcard support.

Namespace: `event4u\DataHelpers\DataMutator`

## Overview

DataMutator can modify:
- Arrays and nested arrays
- Plain PHP objects / DTOs
- Laravel Eloquent Models
- `Illuminate\Support\Collection`

It supports:
- `set($target, $path, $value)`
- `merge($target, $path, $data)`
- `unset($target, $path)`
- Dot paths and wildcards (including deep wildcards)

All methods are pure and return a new/updated target value.

## set()

Set a value at a dot-path.

```php
use event4u\DataHelpers\DataMutator;

$target = [];
$target = DataMutator::set($target, 'user.profile.name', 'Alice');
// ['user' => ['profile' => ['name' => 'Alice']]]
```

### Wildcards in set()

If the source value is produced by a wildcard accessor, you can write multiple values by expanding the wildcard in the target path:

```php
// Suppose $values = ['users.0.email' => 'a@example.com', 'users.2.email' => 'b@example.com']
$target = DataMutator::set([], 'emails.*', $values);
// ['emails' => [0 => 'a@example.com', 2 => 'b@example.com']]
```

Deep wildcards are also supported (multiple `*`), applying the same replacement strategy.

## merge()

Merge data into an existing branch at a path.

```php
$target = ['cfg' => ['limits' => ['cpu' => 1]]];
$target = DataMutator::merge($target, 'cfg.limits', ['mem' => 512]);
// ['cfg' => ['limits' => ['cpu' => 1, 'mem' => 512]]]
```

### Merge strategy

- Associative arrays: deep-merged recursively
- Numeric-indexed arrays: index-based replace (not append) to ensure deterministic mapping semantics

Example:

```php
$target = ['list' => [10 => 'x', 11 => 'y']];
$target = DataMutator::merge($target, 'list', [11 => 'Y', 12 => 'Z']);
// ['list' => [10 => 'x', 11 => 'Y', 12 => 'Z']]
```

## unset()

Remove a value at a path; supports wildcards (including deep wildcards):

```php
$target = ['users' => [
  ['email' => 'a@example.com'],
  ['email' => 'b@example.com'],
]];
$target = DataMutator::unset($target, 'users.*.email');
// ['users' => [['email' => null], ['email' => null]]]
```

## Writing into DTOs and Models

```php
$dto = new #[\AllowDynamicProperties] class {
  public string $name = '';
  public array $tags = [];
};

$dto = DataMutator::set($dto, 'name', 'Alice');
$dto = DataMutator::merge($dto, 'tags', ['php', 'laravel']);
```

For Eloquent Models:

```php
$user = User::first();
$user = DataMutator::set($user, 'profile.bio', 'Software Engineer');
// Model attributes are updated, but not saved automatically
```

## Best practices

- **Immutability**: Always assign the return value back to your variable (`$target = DataMutator::set($target, ...)`).
- **Bulk operations**: For multiple writes, consider using DataMapper with structured mappings.
- **Wildcard writes**: Use wildcards to update multiple items at once instead of looping.
- **DTOs/Models**: DataMutator works with public properties and uses reflection for private properties.

## Performance notes

- Wildcards traverse all matching elements, so performance scales with the number of matches.
- Deep wildcards can be expensive on large nested structures.
- For very large datasets, consider batching operations or using direct array manipulation.

## Common patterns

### Build nested structure from flat data

```php
$target = [];
$target = DataMutator::set($target, 'user.profile.name', 'Alice');
$target = DataMutator::set($target, 'user.profile.email', 'alice@example.com');
$target = DataMutator::set($target, 'user.settings.theme', 'dark');
// ['user' => ['profile' => ['name' => 'Alice', 'email' => 'alice@example.com'], 'settings' => ['theme' => 'dark']]]
```

### Update all items with wildcard

```php
$data = ['users' => [['active' => false], ['active' => false]]];
$data = DataMutator::set($data, 'users.*.active', true);
// ['users' => [['active' => true], ['active' => true]]]
```

### Merge configuration

```php
$config = ['db' => ['host' => 'localhost']];
$config = DataMutator::merge($config, 'db', ['port' => 3306, 'charset' => 'utf8mb4']);
// ['db' => ['host' => 'localhost', 'port' => 3306, 'charset' => 'utf8mb4']]
```

### Remove sensitive data

```php
$data = ['users' => [['name' => 'Alice', 'password' => 'secret'], ['name' => 'Bob', 'password' => 'secret2']]];
$data = DataMutator::unset($data, 'users.*.password');
// ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]
```

## Additional examples

### Conditional merge

```php
$target = ['profile' => ['name' => 'Alice']];
$updates = ['email' => 'alice@example.com', 'name' => 'Alice Updated'];
$target = DataMutator::merge($target, 'profile', $updates);
// ['profile' => ['name' => 'Alice Updated', 'email' => 'alice@example.com']]
```

### Deep wildcard unset for cleanup

```php
$data = [
  'orders' => [
    ['items' => [['temp_id' => 'x', 'sku' => 'A'], ['temp_id' => 'y', 'sku' => 'B']]],
    ['items' => [['temp_id' => 'z', 'sku' => 'C']]],
  ],
];
$data = DataMutator::unset($data, 'orders.*.items.*.temp_id');
// All temp_id fields removed from nested items
```

### Working with Eloquent models

```php
$user = User::with('posts')->first();
$user = DataMutator::set($user, 'posts.0.title', 'Updated Title');
// Updates the first post's title attribute (not saved to DB)
```

### Bulk writes using Accessor results

```php
use event4u\DataHelpers\DataAccessor;

$source = ['users' => [['email' => 'a@x'], ['email' => 'b@x']]];
$accessor = new DataAccessor($source);
$emails = $accessor->get('users.*.email');

$target = [];
$target = DataMutator::set($target, 'contacts.*.email', $emails);
// ['contacts' => [['email' => 'a@x'], ['email' => 'b@x']]]
```

## Notes

- All operations are side-effect free; they return the updated structure.
- Works with arrays, DTOs, Eloquent models, and collections.
- For bulk reads/writes consider using DataMapper.

