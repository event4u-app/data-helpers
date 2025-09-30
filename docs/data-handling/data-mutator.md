# Data Mutator

Write, merge, and unset values in nested data using dot-paths with wildcard support.

Namespace: `App\Helpers\DataMutator`

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
use App\Helpers\DataMutator;

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

## Notes

- All operations are side-effect free; they return the updated structure.
- Works with arrays, DTOs, Eloquent models, and collections.
- For bulk reads/writes consider using DataMapper.

