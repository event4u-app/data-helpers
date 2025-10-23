---
title: Helpers API
description: Complete API reference for helper classes
---

Complete API reference for helper classes.

## EnvHelper

### `get(string $key, mixed $default = null): mixed`

Get environment variable.

```php
$value = EnvHelper::get('APP_NAME', 'default');
```

### `has(string $key): bool`

Check if variable exists.

```php
if (EnvHelper::has('API_KEY')) {
    // ...
}
```

### `set(string $key, mixed $value): void`

Set environment variable.

```php
EnvHelper::set('APP_ENV', 'production');
```

## MathHelper

### `add(string $a, string $b, int $scale = 2): string`

Add two numbers.

```php
$result = MathHelper::add('0.1', '0.2', 2); // '0.30'
```

### `subtract(string $a, string $b, int $scale = 2): string`

Subtract two numbers.

```php
$result = MathHelper::subtract('1.0', '0.3', 2); // '0.70'
```

### `multiply(string $a, string $b, int $scale = 2): string`

Multiply two numbers.

```php
$result = MathHelper::multiply('2.5', '3.0', 2); // '7.50'
```

### `divide(string $a, string $b, int $scale = 2): string`

Divide two numbers.

```php
$result = MathHelper::divide('10.0', '3.0', 2); // '3.33'
```

### `compare(string $a, string $b): int`

Compare two numbers.

```php
$cmp = MathHelper::compare('1.0', '2.0'); // -1
```

## ConfigHelper

### `get(string $key, mixed $default = null): mixed`

Get configuration value.

```php
$value = ConfigHelper::get('app.name', 'default');
```

### `has(string $key): bool`

Check if config exists.

```php
if (ConfigHelper::has('database.host')) {
    // ...
}
```

### `set(string $key, mixed $value): void`

Set configuration value.

```php
ConfigHelper::set('app.debug', true);
```

## DotPathHelper

### `get(array $data, string $path, mixed $default = null): mixed`

Get value at path.

```php
$value = DotPathHelper::get($data, 'user.name');
```

### `set(array &$data, string $path, mixed $value): void`

Set value at path.

```php
DotPathHelper::set($data, 'user.name', 'John');
```

### `has(array $data, string $path): bool`

Check if path exists.

```php
if (DotPathHelper::has($data, 'user.email')) {
    // ...
}
```

### `unset(array &$data, string $path): void`

Remove value at path.

```php
DotPathHelper::unset($data, 'user.password');
```

## ObjectHelper

### `deepClone(object $object): object`

Deep clone object.

```php
$clone = ObjectHelper::deepClone($object);
```

### `toArray(object $object): array`

Convert object to array.

```php
$array = ObjectHelper::toArray($object);
```

### `fromArray(array $data, string $class): object`

Create object from array.

```php
$object = ObjectHelper::fromArray($data, User::class);
```

### `getProperty(object $object, string $property): mixed`

Get property value.

```php
$value = ObjectHelper::getProperty($object, 'name');
```

### `setProperty(object $object, string $property, mixed $value): void`

Set property value.

```php
ObjectHelper::setProperty($object, 'name', 'John');
```

## See Also

- [EnvHelper Guide](/helpers/env-helper/) - EnvHelper guide
- [MathHelper Guide](/helpers/math-helper/) - MathHelper guide
- [ConfigHelper Guide](/helpers/config-helper/) - ConfigHelper guide
- [DotPathHelper Guide](/helpers/dot-path-helper/) - DotPathHelper guide
- [ObjectHelper Guide](/helpers/object-helper/) - ObjectHelper guide

