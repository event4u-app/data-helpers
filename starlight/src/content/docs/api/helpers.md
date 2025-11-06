---
title: Helpers API
description: Complete API reference for helper classes
---

Complete API reference for helper classes.

## EnvHelper

### `get(string $key, mixed $default = null): mixed`

Get raw environment variable value.

```php
$value = EnvHelper::get('APP_NAME', 'default');
```

### `has(string $key): bool`

Check if environment variable exists.

```php
if (EnvHelper::has('API_KEY')) {
    $apiKey = EnvHelper::get('API_KEY');
}
```

### `string(string $key, mixed $default = null, bool $forceCast = true): ?string`

Get environment variable as string.

```php
$appName = EnvHelper::string('APP_NAME', 'My App');
```

### `integer(string $key, mixed $default = null, bool $forceCast = true): ?int`

Get environment variable as integer.

```php
$port = EnvHelper::integer('APP_PORT', 8080);
```

### `boolean(string $key, mixed $default = null, bool $forceCast = true): ?bool`

Get environment variable as boolean.

```php
$debug = EnvHelper::boolean('APP_DEBUG', false);
```

### `float(string $key, mixed $default = null, bool $forceCast = true): ?float`

Get environment variable as float.

```php
$timeout = EnvHelper::float('REQUEST_TIMEOUT', 30.0);
```

### `array(string $key, ?array $default = [], string $separator = ','): ?array`

Get environment variable as array (comma-separated by default).

```php
$hosts = EnvHelper::array('ALLOWED_HOSTS', ['localhost']);
$tags = EnvHelper::array('TAGS', [], '|'); // Custom separator
```

## MathHelper

### `add(null|float|int|string $num1, null|float|int|string $num2, int $scale = 16): float`

Add two numbers with high precision.

```php
$result = MathHelper::add(0.1, 0.2, 2); // 0.3
$result = MathHelper::add('10.5', '20.3', 2); // 30.8
```

### `subtract(null|float|int|string $num1, null|float|int|string $num2, int $scale = 16): float`

Subtract two numbers with high precision.

```php
$result = MathHelper::subtract(1.0, 0.3, 2); // 0.7
$result = MathHelper::subtract('10', '5', 2); // 5.0
```

### `multiply(null|float|int|string $num1, null|float|int|string $num2, int $scale = 16): float`

Multiply two numbers with high precision.

```php
$result = MathHelper::multiply(2.5, 3.0, 2); // 7.5
$result = MathHelper::multiply('2.5', '4', 2); // 10.0
```

### `divide(null|float|int|string $num1, null|float|int|string $num2, int $scale = 16, bool $throwExceptionAtDivisionByZero = true): float`

Divide two numbers with high precision.

```php
$result = MathHelper::divide(10.0, 3.0, 4); // 3.3333
$result = MathHelper::divide('10', '3', 2); // 3.33
```

### `compare(null|float|int|string $num1, null|float|int|string $num2, int $scale = 16): int`

Compare two numbers. Returns -1 if num1 < num2, 0 if equal, 1 if num1 > num2.

```php
$cmp = MathHelper::compare(1.0, 2.0); // -1
$cmp = MathHelper::compare(2.0, 2.0); // 0
$cmp = MathHelper::compare(3.0, 2.0); // 1
```

### `sum(array $numbers, int $scale = 16): float`

Calculate sum of array values.

```php
$result = MathHelper::sum([10, 20, 30]); // 60.0
```

### `average(array $numbers, int $scale = 16): float`

Calculate average of array values.

```php
$result = MathHelper::average([10, 20, 30]); // 20.0
```

### `min(array $numbers, int $scale = 16): float`

Find minimum value in array.

```php
$result = MathHelper::min([10, 5, 20, 3]); // 3.0
```

### `max(array $numbers, int $scale = 16): float`

Find maximum value in array.

```php
$result = MathHelper::max([10, 5, 20, 3]); // 20.0
```

## ConfigHelper

### `getInstance(): ConfigHelper`

Get singleton instance.

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();
```

### `get(string $key, mixed $default = null): mixed`

Get configuration value with dot notation.

```php
$config = ConfigHelper::getInstance();
$value = $config->get('cache.max_entries', 1000);
```

### `getBoolean(string $key, bool $default = false): bool`

Get configuration value as boolean.

```php
$config = ConfigHelper::getInstance();
$enabled = $config->getBoolean('feature.enabled', false);
```

### `getInteger(string $key, int $default = 0): int`

Get configuration value as integer.

```php
$config = ConfigHelper::getInstance();
$maxEntries = $config->getInteger('cache.max_entries', 1000);
```

### `getFloat(string $key, float $default = 0.0): float`

Get configuration value as float.

```php
$config = ConfigHelper::getInstance();
$ratio = $config->getFloat('logging.sampling.errors', 1.5);
```

### `getString(string $key, string $default = ''): string`

Get configuration value as string.

```php
$config = ConfigHelper::getInstance();
$mode = $config->getString('performance_mode', 'fast');
```

### `getArray(string $key, array $default = []): array`

Get configuration value as array.

```php
$config = ConfigHelper::getInstance();
$cacheConfig = $config->getArray('cache', []);
```

### `has(string $key): bool`

Check if configuration key exists.

```php
$config = ConfigHelper::getInstance();
if ($config->has('database.host')) {
    // ...
}
```

### `set(string $key, mixed $value): void`

Set configuration value.

```php
$config = ConfigHelper::getInstance();
$config->set('app.debug', true);
```

### `reset(): void`

Reset configuration to original values.

```php
$config = ConfigHelper::getInstance();
$config->reset();
```

## DotPathHelper

### `segments(string $path): array`

Split dot-notation path into segments.

```php
use event4u\DataHelpers\Helpers\DotPathHelper;

$segments = DotPathHelper::segments('users.*.name');
// ['users', '*', 'name']
```

### `buildPrefix(string $prefix, int|string $segment): string`

Build dot-notation path from prefix and segment.

```php
$path = DotPathHelper::buildPrefix('users', 0);
// 'users.0'
```

### `isWildcard(?string $segment): bool`

Check if segment is a wildcard.

```php
$isWildcard = DotPathHelper::isWildcard('*'); // true
$isWildcard = DotPathHelper::isWildcard('name'); // false
```

### `containsWildcard(string $path): bool`

Check if path contains wildcards.

```php
$hasWildcard = DotPathHelper::containsWildcard('users.*.name'); // true
$hasWildcard = DotPathHelper::containsWildcard('users.0.name'); // false
```

## ObjectHelper

### `copy(object $object, bool $recursive = true, int $maxLevel = 10): object`

Create a deep copy of an object with recursion control.

```php
use event4u\DataHelpers\Helpers\ObjectHelper;

$object = (object)['name' => 'John', 'age' => 30];
$clone = ObjectHelper::copy($object);

// Shallow copy (no recursion)
$shallowClone = ObjectHelper::copy($object, false);

// Deep copy with max recursion level
$deepClone = ObjectHelper::copy($object, true, 5);
```

## See Also

- [EnvHelper Guide](/data-helpers/helpers/env-helper/) - EnvHelper guide
- [MathHelper Guide](/data-helpers/helpers/math-helper/) - MathHelper guide
- [ConfigHelper Guide](/data-helpers/helpers/config-helper/) - ConfigHelper guide
- [DotPathHelper Guide](/data-helpers/helpers/dot-path-helper/) - DotPathHelper guide
- [ObjectHelper Guide](/data-helpers/helpers/object-helper/) - ObjectHelper guide

