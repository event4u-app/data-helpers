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
    $apiKey = EnvHelper::get('API_KEY');
}
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
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();
$value = $config->get('app.name', 'default');
```

### `has(string $key): bool`

Check if config exists.

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();
if ($config->has('database.host')) {
    // ...
}
```

### `set(string $key, mixed $value): void`

Set configuration value.

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();
$config->set('app.debug', true);
```

## ObjectHelper

### `copy(object $object): object`

Copy an object.

```php
use event4u\DataHelpers\Helpers\ObjectHelper;

$object = (object)['name' => 'John', 'age' => 30];
$clone = ObjectHelper::copy($object);
```

## See Also

- [EnvHelper Guide](/helpers/env-helper/) - EnvHelper guide
- [MathHelper Guide](/helpers/math-helper/) - MathHelper guide
- [ConfigHelper Guide](/helpers/config-helper/) - ConfigHelper guide
- [DotPathHelper Guide](/helpers/dot-path-helper/) - DotPathHelper guide
- [ObjectHelper Guide](/helpers/object-helper/) - ObjectHelper guide

