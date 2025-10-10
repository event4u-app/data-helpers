# EnvHelper

The `EnvHelper` provides a **framework-agnostic** way to access environment variables with automatic type casting and framework detection.

## Features

- ✅ **Automatic framework detection** - No configuration needed
- ✅ **Type casting** - Get values as string, int, float, bool, or array
- ✅ **Optional Carbon support** - Parse dates when Carbon is installed
- ✅ **Default values** - Fallback values when environment variable is not set
- ✅ **Type-safe** - PHPStan Level 9 compliant

## Basic Usage

```php
use event4u\DataHelpers\Helpers\EnvHelper;

// Get string value
$appName = EnvHelper::string('APP_NAME', 'My App');

// Get integer value
$port = EnvHelper::int('APP_PORT', 8080);

// Get boolean value
$debug = EnvHelper::bool('APP_DEBUG', false);

// Get float value
$timeout = EnvHelper::float('TIMEOUT', 30.0);

// Get array value (comma-separated)
$allowedHosts = EnvHelper::array('ALLOWED_HOSTS', ['localhost']);
```

## Available Methods

### `get(string $key, mixed $default = null): mixed`

Get raw environment variable value.

```php
$value = EnvHelper::get('DATABASE_URL');
```

### `string(string $key, ?string $default = null): ?string`

Get value as string.

```php
$appName = EnvHelper::string('APP_NAME', 'Default App');
```

### `int(string $key, ?int $default = null): ?int`

Get value as integer.

```php
$port = EnvHelper::int('PORT', 3000);
```

### `float(string $key, ?float $default = null): ?float`

Get value as float.

```php
$timeout = EnvHelper::float('TIMEOUT', 30.5);
```

### `bool(string $key, ?bool $default = null): ?bool`

Get value as boolean. Recognizes: `true`, `false`, `1`, `0`, `yes`, `no`, `on`, `off`.

```php
$debug = EnvHelper::bool('DEBUG', false);
```

### `array(string $key, ?array $default = null): ?array`

Get value as array (comma-separated).

```php
// .env: ALLOWED_HOSTS=localhost,example.com,test.com
$hosts = EnvHelper::array('ALLOWED_HOSTS', []);
// ['localhost', 'example.com', 'test.com']
```

### `carbon(string $key, mixed $default = null): Carbon` (Optional)

Get value as Carbon instance. Requires `nesbot/carbon` package.

```php
// .env: DEPLOYMENT_DATE=2024-01-15
$deploymentDate = EnvHelper::carbon('DEPLOYMENT_DATE');
// Carbon instance
```

## Framework Detection

The `EnvHelper` automatically detects your framework:

### Laravel

```php
// Uses Laravel's env() helper
$value = EnvHelper::string('APP_NAME');
```

### Symfony

```php
// Uses $_ENV superglobal
$value = EnvHelper::string('APP_NAME');
```

### Plain PHP

```php
// Uses getenv() or $_ENV
$value = EnvHelper::string('APP_NAME');
```

## Type Casting Examples

### Boolean Values

```php
// .env: DEBUG=true
EnvHelper::bool('DEBUG'); // true

// .env: DEBUG=1
EnvHelper::bool('DEBUG'); // true

// .env: DEBUG=yes
EnvHelper::bool('DEBUG'); // true

// .env: DEBUG=on
EnvHelper::bool('DEBUG'); // true
```

### Array Values

```php
// .env: ALLOWED_IPS=192.168.1.1,192.168.1.2,192.168.1.3
$ips = EnvHelper::array('ALLOWED_IPS');
// ['192.168.1.1', '192.168.1.2', '192.168.1.3']
```

### Numeric Values

```php
// .env: MAX_CONNECTIONS=100
$max = EnvHelper::int('MAX_CONNECTIONS'); // 100

// .env: TIMEOUT=30.5
$timeout = EnvHelper::float('TIMEOUT'); // 30.5
```

## Error Handling

All methods return `null` or the default value if the environment variable is not set:

```php
// Variable not set
$value = EnvHelper::string('MISSING_VAR'); // null
$value = EnvHelper::string('MISSING_VAR', 'default'); // 'default'
```

Invalid type conversions throw `InvalidArgumentException`:

```php
// .env: PORT=not_a_number
EnvHelper::int('PORT'); // throws InvalidArgumentException
```

## Best Practices

### Use Type-Specific Methods

```php
// ❌ Don't use generic get() for typed values
$port = (int)EnvHelper::get('PORT', 3000);

// ✅ Use type-specific methods
$port = EnvHelper::int('PORT', 3000);
```

### Provide Sensible Defaults

```php
// ✅ Always provide defaults for optional settings
$timeout = EnvHelper::float('TIMEOUT', 30.0);
$debug = EnvHelper::bool('DEBUG', false);
```

### Use Carbon for Dates

```php
// ✅ Use carbon() for date values
$deploymentDate = EnvHelper::carbon('DEPLOYMENT_DATE');
```

## See Also

- [Configuration](configuration.md) - Package configuration
- [Framework Integration](framework-integration.md) - Framework-specific features
- [Optional Dependencies](optional-dependencies.md) - Carbon and other optional packages
