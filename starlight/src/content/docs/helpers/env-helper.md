---
title: EnvHelper
description: Framework-agnostic environment variable helper with automatic type casting
---

EnvHelper provides a framework-agnostic way to access environment variables with automatic type casting and framework detection.

## Quick Example

```php
use event4u\DataHelpers\Helpers\EnvHelper;

// Get string value
$appName = EnvHelper::string('APP_NAME', 'My App');

// Get integer value
$port = EnvHelper::integer('APP_PORT', 8080);

// Get boolean value
$debug = EnvHelper::boolean('APP_DEBUG', false);

// Get float value
$timeout = EnvHelper::float('TIMEOUT', 30.0);

// Get array value (comma-separated)
$allowedHosts = EnvHelper::array('ALLOWED_HOSTS', ['localhost']);
```

## Introduction

EnvHelper provides a unified interface for accessing environment variables across different frameworks.

### Key Features

- **Automatic framework detection** - No configuration needed
- **Type casting** - Get values as string, int, float, bool, or array
- **Optional Carbon support** - Parse dates when Carbon is installed
- **Default values** - Fallback values when environment variable is not set
- **Type-safe** - PHPStan Level 9 compliant

### Framework Detection

EnvHelper automatically detects your framework:

- **Laravel** - Uses Laravel's `env()` helper
- **Symfony** - Uses `$_ENV` superglobal
- **Plain PHP** - Uses `getenv()` or `$_ENV`

## Basic Usage

### Get Raw Value

```php
use event4u\DataHelpers\Helpers\EnvHelper;

// Get raw value
$value = EnvHelper::get('DATABASE_URL');

// With default
$value = EnvHelper::get('DATABASE_URL', 'mysql://localhost');
```

### Get Typed Values

```php
// String
$appName = EnvHelper::string('APP_NAME', 'Default App');

// Integer
$port = EnvHelper::integer('PORT', 3000);

// Float
$timeout = EnvHelper::float('TIMEOUT', 30.5);

// Boolean
$debug = EnvHelper::boolean('DEBUG', false);

// Array (comma-separated)
$hosts = EnvHelper::array('ALLOWED_HOSTS', []);
```


## API Reference

### get(string $key, mixed $default = null): mixed

Get raw environment variable value.

```php
$value = EnvHelper::get('DATABASE_URL');
$value = EnvHelper::get('DATABASE_URL', 'mysql://localhost');
```

### string(string $key, ?string $default = null): ?string

Get value as string.

```php
$appName = EnvHelper::string('APP_NAME', 'Default App');
$env = EnvHelper::string('APP_ENV', 'production');
```

### int(string $key, ?int $default = null): ?int

Get value as integer.

```php
$port = EnvHelper::integer('PORT', 3000);
$maxConnections = EnvHelper::integer('MAX_CONNECTIONS', 100);
```

### float(string $key, ?float $default = null): ?float

Get value as float.

```php
$timeout = EnvHelper::float('TIMEOUT', 30.5);
$ratio = EnvHelper::float('SAMPLING_RATIO', 0.1);
```

### bool(string $key, ?bool $default = null): ?bool

Get value as boolean. Recognizes: `true`, `false`, `1`, `0`, `yes`, `no`, `on`, `off`.

```php
$debug = EnvHelper::boolean('DEBUG', false);
$enabled = EnvHelper::boolean('FEATURE_ENABLED', true);
```

### array(string $key, ?array $default = null): ?array

Get value as array (comma-separated).

```php
// .env: ALLOWED_HOSTS=localhost,example.com,test.com
$hosts = EnvHelper::array('ALLOWED_HOSTS', []);
// ['localhost', 'example.com', 'test.com']
```

### carbon(string $key, mixed $default = null): Carbon (Optional)

Get value as Carbon instance. Requires `nesbot/carbon` package.

```php
// .env: DEPLOYMENT_DATE=2024-01-15
$deploymentDate = EnvHelper::carbon('DEPLOYMENT_DATE');
// Carbon instance
```

## Type Casting Examples

### Boolean Values

```php
// .env: DEBUG=true
EnvHelper::boolean('DEBUG'); // true

// .env: DEBUG=1
EnvHelper::boolean('DEBUG'); // true

// .env: DEBUG=yes
EnvHelper::boolean('DEBUG'); // true

// .env: DEBUG=on
EnvHelper::boolean('DEBUG'); // true

// .env: DEBUG=false
EnvHelper::boolean('DEBUG'); // false

// .env: DEBUG=0
EnvHelper::boolean('DEBUG'); // false

// .env: DEBUG=no
EnvHelper::boolean('DEBUG'); // false

// .env: DEBUG=off
EnvHelper::boolean('DEBUG'); // false
```

### Array Values

```php
// .env: ALLOWED_IPS=192.168.1.1,192.168.1.2,192.168.1.3
$ips = EnvHelper::array('ALLOWED_IPS');
// ['192.168.1.1', '192.168.1.2', '192.168.1.3']

// .env: ADMIN_EMAILS=admin@example.com,support@example.com
$emails = EnvHelper::array('ADMIN_EMAILS');
// ['admin@example.com', 'support@example.com']
```

### Numeric Values

```php
// .env: MAX_CONNECTIONS=100
$max = EnvHelper::integer('MAX_CONNECTIONS'); // 100

// .env: TIMEOUT=30.5
$timeout = EnvHelper::float('TIMEOUT'); // 30.5

// .env: PORT=8080
$port = EnvHelper::integer('PORT'); // 8080
```

## Error Handling

### Missing Variables

All methods return `null` or the default value if the environment variable is not set:

```php
// Variable not set
$value = EnvHelper::string('MISSING_VAR'); // null
$value = EnvHelper::string('MISSING_VAR', 'default'); // 'default'

// With default values
$port = EnvHelper::integer('PORT', 3000); // 3000 if PORT not set
$debug = EnvHelper::boolean('DEBUG', false); // false if DEBUG not set
```

### Invalid Type Conversions

Invalid type conversions throw `InvalidArgumentException`:

```php
// .env: PORT=not_a_number
try {
    EnvHelper::integer('PORT');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Invalid integer value for PORT"
}

// .env: TIMEOUT=invalid
try {
    EnvHelper::float('TIMEOUT');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Invalid float value for TIMEOUT"
}
```

## Real-World Examples

### Database Configuration

```php
$dbConfig = [
    'host' => EnvHelper::string('DB_HOST', 'localhost'),
    'port' => EnvHelper::integer('DB_PORT', 3306),
    'database' => EnvHelper::string('DB_DATABASE', 'myapp'),
    'username' => EnvHelper::string('DB_USERNAME', 'root'),
    'password' => EnvHelper::string('DB_PASSWORD', ''),
];
```

### Application Configuration

```php
$appConfig = [
    'name' => EnvHelper::string('APP_NAME', 'My Application'),
    'env' => EnvHelper::string('APP_ENV', 'production'),
    'debug' => EnvHelper::boolean('APP_DEBUG', false),
    'url' => EnvHelper::string('APP_URL', 'http://localhost'),
    'timezone' => EnvHelper::string('APP_TIMEZONE', 'UTC'),
];
```

### Security Configuration

```php
$securityConfig = [
    'allowed_hosts' => EnvHelper::array('ALLOWED_HOSTS', ['localhost']),
    'allowed_ips' => EnvHelper::array('ALLOWED_IPS', []),
    'rate_limit' => EnvHelper::integer('RATE_LIMIT', 60),
    'session_lifetime' => EnvHelper::integer('SESSION_LIFETIME', 120),
    'csrf_enabled' => EnvHelper::boolean('CSRF_ENABLED', true),
];
```

### API Configuration

```php
$apiConfig = [
    'base_url' => EnvHelper::string('API_BASE_URL', 'https://api.example.com'),
    'timeout' => EnvHelper::float('API_TIMEOUT', 30.0),
    'retry_attempts' => EnvHelper::integer('API_RETRY_ATTEMPTS', 3),
    'verify_ssl' => EnvHelper::boolean('API_VERIFY_SSL', true),
];
```

## Best Practices

### Use Type-Specific Methods

```php
// ❌ Don't use generic get() for typed values
$port = (int)EnvHelper::get('PORT', 3000);

// ✅ Use type-specific methods
$port = EnvHelper::integer('PORT', 3000);
```

### Provide Sensible Defaults

```php
// ✅ Always provide defaults for optional settings
$timeout = EnvHelper::float('TIMEOUT', 30.0);
$debug = EnvHelper::boolean('DEBUG', false);
$maxConnections = EnvHelper::integer('MAX_CONNECTIONS', 100);
```

### Use Carbon for Dates

```php
// ✅ Use carbon() for date values
$deploymentDate = EnvHelper::carbon('DEPLOYMENT_DATE');
$expiryDate = EnvHelper::carbon('LICENSE_EXPIRY');
```

### Group Related Configuration

```php
// ✅ Group related configuration
class DatabaseConfig
{
    public static function get(): array
    {
        return [
            'host' => EnvHelper::string('DB_HOST', 'localhost'),
            'port' => EnvHelper::integer('DB_PORT', 3306),
            'database' => EnvHelper::string('DB_DATABASE'),
            'username' => EnvHelper::string('DB_USERNAME'),
            'password' => EnvHelper::string('DB_PASSWORD'),
        ];
    }
}
```

## See Also

- [ConfigHelper](/helpers/config-helper/) - Configuration helper
- [Framework Integration](/data-helpers/framework-integration/laravel/) - Framework-specific features
- [Core Concepts: Configuration](/data-helpers/core-concepts/configuration/) - Package configuration
