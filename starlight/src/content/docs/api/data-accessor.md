---
title: DataAccessor API
description: Complete API reference for DataAccessor
---

Complete API reference for DataAccessor.

## Constructor

### `__construct(array $data)`

Create a new DataAccessor instance.

```php
$accessor = new DataAccessor($data);
```

## Static Methods

### `make(array $data): self`

Create a new instance (fluent).

```php
$accessor = DataAccessor::make($data);
```

## Get Methods

### `get(string $path, mixed $default = null): mixed`

Get value at path.

```php
$value = $accessor->get('user.name');
$value = $accessor->get('user.age', 0);
```

### `getString(string $path, string $default = ''): string`

Get string value.

```php
$name = $accessor->getString('user.name');
```

### `getInt(string $path, int $default = 0): int`

Get integer value.

```php
$age = $accessor->getInt('user.age');
```

### `getFloat(string $path, float $default = 0.0): float`

Get float value.

```php
$price = $accessor->getFloat('product.price');
```

### `getBool(string $path, bool $default = false): bool`

Get boolean value.

```php
$active = $accessor->getBool('user.active');
```

### `getArray(string $path, array $default = []): array`

Get array value.

```php
$tags = $accessor->getArray('post.tags');
```

## Has Methods

### `has(string $path): bool`

Check if path exists.

```php
if ($accessor->has('user.email')) {
    // ...
}
```

### `hasAny(array $paths): bool`

Check if any path exists.

```php
if ($accessor->hasAny(['user.email', 'user.phone'])) {
    // ...
}
```

### `hasAll(array $paths): bool`

Check if all paths exist.

```php
if ($accessor->hasAll(['user.name', 'user.email'])) {
    // ...
}
```

## Wildcard Methods

### `getWildcard(string $pattern): array`

Get values matching wildcard pattern.

```php
$names = $accessor->getWildcard('users.*.name');
```

## Array Methods

### `toArray(): array`

Get underlying array.

```php
$data = $accessor->toArray();
```

### `keys(): array`

Get all keys.

```php
$keys = $accessor->keys();
```

### `values(): array`

Get all values.

```php
$values = $accessor->values();
```

## See Also

- [DataAccessor Guide](/main-classes/data-accessor/) - Complete guide
- [Dot-Notation](/core-concepts/dot-notation/) - Path syntax
- [Wildcards](/core-concepts/wildcards/) - Wildcard patterns

