# Data Accessor

Read values from nested data using dot-paths with wildcard support.

Namespace: `event4u\DataHelpers\DataAccessor`

## Overview

DataAccessor provides a uniform way to read values from:
- Arrays and nested arrays
- Plain PHP objects / DTOs (public props, getters where supported internally)
- Laravel Eloquent Models
- `Illuminate\Support\Collection`
- `Arrayable` and `JsonSerializable`

It supports:
- Dot paths: `user.profile.name`
- Numeric indices: `users.0.email`
- Wildcards `*`: `users.*.email`
- Deep wildcards (multiple `*`): `users.*.profile.*.city`

## Basic usage

```php
use event4u\DataHelpers\DataAccessor;

$accessor = new DataAccessor($data);
$value = $accessor->get('user.profile.name');
```

- If the path does not exist, `get()` returns `null`.
- Collections and Models are handled seamlessly.

## Wildcards

When you use a wildcard in the path, `get()` returns an associative array keyed by the resolved dot-path for each match. For example:

```php
$accessor = new DataAccessor([
  'users' => [
    ['email' => 'a@example.com'],
    ['email' => null],
    ['email' => 'b@example.com'],
  ],
]);

$result = $accessor->get('users.*.email');
// [
//   'users.0.email' => 'a@example.com',
//   'users.1.email' => null,
//   'users.2.email' => 'b@example.com',
// ]
```

This format is intentionally stable and is consumed by DataMutator and DataMapper.

## Deep wildcards

Multiple wildcards are supported in one path and will produce a flat associative array with the full dot-path key for each match:

```php
$accessor->get('users.*.profile.*.city');
// e.g. [
//   'users.0.profile.home.city' => 'Berlin',
//   'users.0.profile.work.city' => 'Hamburg',
//   'users.1.profile.home.city' => 'Munich',
// ]
```

## Default values

You can provide a default value as the second parameter:

```php
$name = $accessor->get('user.profile.name', 'Anonymous');
// Returns 'Anonymous' if path doesn't exist
```

## Working with Collections and Models

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
// ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com']
```

## JSON and XML input

DataAccessor can parse JSON and XML strings automatically:

```php
$json = '{"users":[{"name":"Alice"},{"name":"Bob"}]}';
$accessor = new DataAccessor($json);
$names = $accessor->get('users.*.name');
// ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']
```

## Best practices

- **Use wildcards for bulk reads**: When you need to extract the same field from multiple items, wildcards are more efficient than looping.
- **Combine with DataMutator**: Use Accessor to read values and Mutator to write them into a new structure.
- **Leverage Collections**: DataAccessor works seamlessly with Laravel Collections, so you can chain Collection methods after reading.
- **Default values**: Always provide sensible defaults when accessing optional paths to avoid null checks.

## Performance notes

- Wildcards traverse all matching elements, so performance scales with the number of matches.
- Deep wildcards (multiple `*`) can be expensive on large nested structures.
- For very large datasets, consider filtering data before passing to DataAccessor.

## Common patterns

### Extract all emails from nested users

```php
$data = [
  'departments' => [
    ['users' => [['email' => 'a@x.com'], ['email' => 'b@x.com']]],
    ['users' => [['email' => 'c@x.com']]],
  ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('departments.*.users.*.email');
// ['departments.0.users.0.email' => 'a@x.com', 'departments.0.users.1.email' => 'b@x.com', 'departments.1.users.0.email' => 'c@x.com']
```

### Safe access with default

```php
$config = $accessor->get('app.settings.theme', 'default');
```

### Access Eloquent relationships

```php
$user = User::with('posts.comments')->first();
$accessor = new DataAccessor($user);
$commentTexts = $accessor->get('posts.*.comments.*.text');
```

## Additional examples

### Filtering null values from wildcard results

```php
$data = ['users' => [['email' => 'a@x'], ['email' => null], ['email' => 'b@x']]];
$accessor = new DataAccessor($data);
$emails = array_filter($accessor->get('users.*.email'), fn($v) => $v !== null);
// ['users.0.email' => 'a@x', 'users.2.email' => 'b@x']
```

### Accessing nested Collections

```php
$data = [
  'orders' => collect([
    ['items' => collect([['sku' => 'A'], ['sku' => 'B']])],
    ['items' => collect([['sku' => 'C']])],
  ]),
];
$accessor = new DataAccessor($data);
$skus = $accessor->get('orders.*.items.*.sku');
// ['orders.0.items.0.sku' => 'A', 'orders.0.items.1.sku' => 'B', 'orders.1.items.0.sku' => 'C']
```

### Combining with array functions

```php
$accessor = new DataAccessor($data);
$prices = $accessor->get('products.*.price');
$totalPrice = array_sum($prices);
$avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0;
```

### Root-level numeric indices

```php
$data = [
  ['name' => 'Alice'],
  ['name' => 'Bob'],
];
$accessor = new DataAccessor($data);
$firstUser = $accessor->get('0.name'); // 'Alice'
$allNames = $accessor->get('*.name'); // ['0.name' => 'Alice', '1.name' => 'Bob']
```

## Notes

- Accessing a collection by index works the same as arrays (e.g. `items.0.id`).
- If you need to build a target structure rather than just read values, use DataMapper.

