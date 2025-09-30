# Data Accessor

Read values from nested data using dot-paths with wildcard support.

Namespace: `App\Helpers\DataAccessor`

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
use App\Helpers\DataAccessor;

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

## Notes

- Accessing a collection by index works the same as arrays (e.g. `items.0.id`).
- If you need to build a target structure rather than just read values, use DataMapper.

