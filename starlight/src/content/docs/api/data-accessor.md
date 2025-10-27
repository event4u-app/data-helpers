---
title: DataAccessor API
description: Complete API reference for DataAccessor
---

Complete API reference for DataAccessor.

## Constructor

### `__construct(array $data)`

Create a new DataAccessor instance.

```php
$data = ['user' => ['name' => 'John']];
$accessor = new DataAccessor($data);
```

## Static Methods

### `make(mixed $input): self`

Create a new instance (fluent).

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John']];
$accessor = DataAccessor::make($data);
```

## Get Methods

### `get(string $path, mixed $default = null): mixed`

Get value at path.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$value = $accessor->get('user.name');
$value = $accessor->get('user.age', 0);
```

### `getString(string $path, string $default = ''): string`

Get string value.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$name = $accessor->getString('user.name');
```

### `getInt(string $path, int $default = 0): int`

Get integer value.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$age = $accessor->getInt('user.age');
```

### `getFloat(string $path, float $default = 0.0): float`

Get float value.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$price = $accessor->getFloat('product.price');
```

### `getBool(string $path, bool $default = false): bool`

Get boolean value.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$active = $accessor->getBool('user.active');
```

### `getArray(string $path, array $default = []): array`

Get array value.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$tags = $accessor->getArray('post.tags');
```

## Has Methods

### `has(string $path): bool`

Check if path exists.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
if ($accessor->has('user.email')) {
    // ...
}
```

### `hasAny(array $paths): bool`

Check if any path exists.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
if ($accessor->hasAny(['user.email', 'user.phone'])) {
    // ...
}
```

### `hasAll(array $paths): bool`

Check if all paths exist.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
if ($accessor->hasAll(['user.name', 'user.email'])) {
    // ...
}
```

## Wildcard Methods

### `getWildcard(string $pattern): array`

Get values matching wildcard pattern.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$names = $accessor->getWildcard('users.*.name');
```

## Array Methods

### `toArray(): array`

Get underlying array.

```php
$accessor = new DataAccessor(['user' => ['name' => 'John']]);
$data = $accessor->toArray();
```

### `keys(): array`

Get all keys.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$keys = $accessor->keys();
```

### `values(): array`

Get all values.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$values = $accessor->values();
```

## Structure Introspection Methods

### `getStructure(): array`

Get data structure with type information as flat array with dot-notation.

Returns an array where keys are dot-notation paths (with wildcards for arrays) and values are type strings (with union types for mixed values).

**Return Format:**
- Primitive types: `'string'`, `'int'`, `'float'`, `'bool'`, `'null'`
- Arrays: `'array'`
- Objects: Full namespace with leading backslash (e.g., `'\EmailDto'`)
- Union types: Pipe-separated, alphabetically sorted (e.g., `'bool|int|null|string'`)
- Array elements: Wildcard notation (e.g., `'emails.*'`)

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

// Example output:
// [
//   'name' => 'string',
//   'age' => 'int',
//   'emails' => 'array',
//   'emails.*' => '\EmailDto',
//   'emails.*.email' => 'string',
//   'emails.*.verified' => 'bool',
// ]
```

### `getStructureMultidimensional(): array`

Get data structure with type information as multidimensional array.

Returns a nested array structure where leaf values are type strings (with union types for mixed values). Arrays use wildcards.

**Return Format:**
- Same type format as `getStructure()`
- Nested structure instead of flat dot-notation
- Array elements use `'*'` key

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$structure = $accessor->getStructureMultidimensional();

// Example output:
// [
//   'name' => 'string',
//   'age' => 'int',
//   'emails' => [
//     '*' => [
//       'email' => 'string',
//       'verified' => 'bool',
//     ],
//   ],
// ]
```

## See Also

- [DataAccessor Guide](/data-helpers/main-classes/data-accessor/) - Complete guide
- [Dot-Notation](/data-helpers/core-concepts/dot-notation/) - Path syntax
- [Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard patterns

