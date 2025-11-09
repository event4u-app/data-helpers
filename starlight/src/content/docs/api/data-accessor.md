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

Get value at path. Returns mixed type without type conversion.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$value = $accessor->get('user.name');
$value = $accessor->get('user.age', 0);
```

## Type-Safe Single Value Getters

These methods return a single value with strict type conversion. They return `null` if the path doesn't exist or the value is `null`. They throw `TypeMismatchException` if the value cannot be converted to the expected type or if an array is returned (use collection getters for wildcards).

### `getString(string $path, ?string $default = null): ?string`

Get string value with type conversion. Converts numbers and booleans to strings. Throws `TypeMismatchException` for arrays or objects without `__toString()`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$name = $accessor->getString('user.name');  // 'John'
$age = $accessor->getString('user.age');    // '25' (int → string)
$missing = $accessor->getString('user.phone');  // null
$withDefault = $accessor->getString('user.phone', 'N/A');  // 'N/A'
```

### `getInt(string $path, ?int $default = null): ?int`

Get integer value with type conversion. Converts numeric strings, floats, and booleans to integers. Throws `TypeMismatchException` for non-numeric strings or arrays.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => '25', 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$age = $accessor->getInt('user.age');  // 25 (string → int)
$active = $accessor->getInt('user.active');  // 1 (bool → int)
$missing = $accessor->getInt('user.salary');  // null
$withDefault = $accessor->getInt('user.salary', 0);  // 0
```

### `getFloat(string $path, ?float $default = null): ?float`

Get float value with type conversion. Converts numeric strings, integers, and booleans to floats. Throws `TypeMismatchException` for non-numeric strings or arrays.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => '99.99'], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$price = $accessor->getFloat('product.price');  // 99.99 (string → float)
$age = $accessor->getFloat('user.age');  // 25.0 (int → float)
$missing = $accessor->getFloat('product.discount');  // null
```

### `getBool(string $path, ?bool $default = null): ?bool`

Get boolean value with type conversion. Converts any value to boolean using PHP's truthiness rules. Throws `TypeMismatchException` for arrays.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => 1], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$active = $accessor->getBool('user.active');  // true (1 → true)
$inactive = $accessor->getBool('user.inactive');  // null
$withDefault = $accessor->getBool('user.inactive', false);  // false
```

### `getArray(string $path, ?array $default = null): ?array`

Get array value. Only accepts arrays. Throws `TypeMismatchException` for non-array values.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com', 'active' => true], 'product' => ['price' => 99.99], 'post' => ['tags' => ['php', 'laravel']]];
$accessor = new DataAccessor($data);
$tags = $accessor->getArray('post.tags');  // ['php', 'laravel']
$missing = $accessor->getArray('post.categories');  // null
$withDefault = $accessor->getArray('post.categories', []);  // []
```

## Type-Safe Collection Getters

These methods are designed for wildcard paths and return typed arrays. They throw `TypeMismatchException` if the path doesn't contain a wildcard or if any value cannot be converted to the expected type.

### `getIntCollection(string $path): array`

Get array of integers from wildcard path. Returns `array<int|string, int>`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['users' => [
    ['name' => 'Alice', 'age' => '30'],
    ['name' => 'Bob', 'age' => '25'],
]];
$accessor = new DataAccessor($data);
$ages = $accessor->getIntCollection('users.*.age');
// ['users.0.age' => 30, 'users.1.age' => 25]
```

### `getStringCollection(string $path): array`

Get array of strings from wildcard path. Returns `array<int|string, string>`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['users' => [
    ['name' => 'Alice', 'age' => 30],
    ['name' => 'Bob', 'age' => 25],
]];
$accessor = new DataAccessor($data);
$names = $accessor->getStringCollection('users.*.name');
// ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']
```

### `getBoolCollection(string $path): array`

Get array of booleans from wildcard path. Returns `array<int|string, bool>`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['users' => [
    ['name' => 'Alice', 'active' => true],
    ['name' => 'Bob', 'active' => false],
]];
$accessor = new DataAccessor($data);
$activeFlags = $accessor->getBoolCollection('users.*.active');
// ['users.0.active' => true, 'users.1.active' => false]
```

### `getFloatCollection(string $path): array`

Get array of floats from wildcard path. Returns `array<int|string, float>`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['products' => [
    ['name' => 'Product A', 'price' => '10.50'],
    ['name' => 'Product B', 'price' => '25.00'],
]];
$accessor = new DataAccessor($data);
$prices = $accessor->getFloatCollection('products.*.price');
// ['products.0.price' => 10.5, 'products.1.price' => 25.0]
```

### `getArrayCollection(string $path): array`

Get array of arrays from wildcard path. Returns `array<int|string, array>`.

```php
use event4u\DataHelpers\DataAccessor;

$data = ['orders' => [
    ['items' => ['A', 'B']],
    ['items' => ['C', 'D']],
]];
$accessor = new DataAccessor($data);
$items = $accessor->getArrayCollection('orders.*.items');
// ['orders.0.items' => ['A', 'B'], 'orders.1.items' => ['C', 'D']]
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

