---
title: ConfigLoader
description: Load and merge configuration for Plain PHP projects
sidebar:
  order: 3
---

The `ConfigLoader` provides an easy way to load and customize Data Helpers configuration in **Plain PHP projects** without Laravel or Symfony.

:::note[Framework Support]
**Laravel and Symfony** have native configuration support and **do not need** the `ConfigLoader`.

- **Laravel**: Use `php artisan vendor:publish --tag=data-helpers-config` and edit `config/data-helpers.php`
- **Symfony**: Edit `config/packages/data_helpers.yaml`
- **Plain PHP**: Use `ConfigLoader` (this guide)

See [Framework Integration](/data-helpers/framework-integration/overview/) for details.
:::

## Features

- **Deep Merging** - Merges user config with package defaults (similar to Laravel)
- **Partial Configuration** - Only specify values you want to change
- **File or Array** - Load from config file or pass array directly
- **Config Publishing** - Generate minimal config file for your project
- **Type-Safe** - Full PHPStan level 9 support
- **Plain PHP Only** - Laravel and Symfony use native config systems

## Basic Usage

### Load with Config File

Create a config file in your project:

```php
// config/data-helpers.php
<?php

return [
    'performance_mode' => 'safe',
    'cache' => [
        'ttl' => 3600,
    ],
];
```

Load and initialize:

```php skip-test
use event4u\DataHelpers\Config\ConfigLoader;
use event4u\DataHelpers\DataHelpersConfig;

// Load config with deep merging
$config = ConfigLoader::load(__DIR__ . '/config/data-helpers.php');

// Initialize DataHelpersConfig
DataHelpersConfig::initialize($config);
```

### Load with Array

You can also pass configuration directly as an array:

```php
use event4u\DataHelpers\Config\ConfigLoader;
use event4u\DataHelpers\DataHelpersConfig;

$config = ConfigLoader::load([
    'performance_mode' => 'safe',
    'cache' => [
        'ttl' => 3600,
        'code_generation' => true,
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'debug',
    ],
]);

DataHelpersConfig::initialize($config);
```

## Deep Merging

The `ConfigLoader` performs **deep merging** of configuration arrays, similar to Laravel's config system.

### How It Works

```php
// Package default config
[
    'cache' => [
        'path' => './.event4u/data-helpers/cache/',
        'driver' => 'auto',
        'ttl' => null,
        'code_generation' => true,
    ],
]

// Your config (only what you want to change)
[
    'cache' => [
        'ttl' => 3600,
    ],
]

// Result after merging
[
    'cache' => [
        'path' => './.event4u/data-helpers/cache/',  // ✅ Preserved from default
        'driver' => 'auto',                           // ✅ Preserved from default
        'ttl' => 3600,                                // ✅ Overridden by your config
        'code_generation' => true,                    // ✅ Preserved from default
    ],
]
```

### Multi-Level Nesting

Deep merging works at any nesting level:

```php
$config = ConfigLoader::load([
    'logging' => [
        'events' => [
            'mapping_error' => false,  // Override just this one event
        ],
        'sampling' => [
            'errors' => 0.5,           // Override just this one sampling rate
        ],
    ],
]);

// All other logging.events.* and logging.sampling.* values are preserved!
```

## Publishing Config File

Generate a minimal config file for your project:

```php
use event4u\DataHelpers\Config\ConfigLoader;

// Publish to your project
ConfigLoader::publish('./config/data-helpers.php');

// Overwrite existing file
ConfigLoader::publish('./config/data-helpers.php', overwrite: true);
```

The published config file contains only commonly changed settings with helpful comments.

## Bootstrap Example

Here's a complete example of bootstrapping Data Helpers in a Plain PHP project:

```php
// bootstrap.php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\Config\ConfigLoader;
use event4u\DataHelpers\DataHelpersConfig;

// Load configuration
$config = ConfigLoader::load(__DIR__ . '/config/data-helpers.php');

// Initialize Data Helpers
DataHelpersConfig::initialize($config);

// Now you can use Data Helpers
use event4u\DataHelpers\SimpleDto\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

## Environment Variables

The default config file uses `env()` helper for environment variables. If you don't have this helper, you can use PHP's native `$_ENV` or `getenv()`:

```php
// config/data-helpers.php
<?php

return [
    'performance_mode' => $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? 'fast',
    'cache' => [
        'ttl' => isset($_ENV['DATA_HELPERS_CACHE_TTL'])
            ? (int) $_ENV['DATA_HELPERS_CACHE_TTL']
            : null,
    ],
];
```

Or use the package's `EnvHelper`:

```php
use event4u\DataHelpers\Helpers\EnvHelper;

return [
    'performance_mode' => EnvHelper::string('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),
    'cache' => [
        'ttl' => EnvHelper::integer('DATA_HELPERS_CACHE_TTL', null),
    ],
];
```

## API Reference

### `ConfigLoader::load()`

Load configuration with deep merging.

```php
public static function load(string|array $userConfig = []): array
```

**Parameters:**
- `$userConfig` - Path to config file or config array

**Returns:** Merged configuration array

**Throws:** `RuntimeException` if config file not found

### `ConfigLoader::getDefaultConfigPath()`

Get the path to the package default config file.

```php
public static function getDefaultConfigPath(): string
```

**Returns:** Absolute path to `config/data-helpers.php`

### `ConfigLoader::publish()`

Create a minimal config file.

```php
public static function publish(string $targetPath, bool $overwrite = false): bool
```

**Parameters:**
- `$targetPath` - Where to create the config file
- `$overwrite` - Whether to overwrite existing file (default: false)

**Returns:** `true` if file was created, `false` if file exists and overwrite is false

## Examples

### Minimal Config

Only change what you need:

```php skip-test
$config = ConfigLoader::load([
    'performance_mode' => 'safe',
]);
```

### Production Config

Optimize for production:

```php skip-test
$config = ConfigLoader::load([
    'performance_mode' => 'fast',
    'cache' => [
        'code_generation' => true,
        'invalidation' => 'manual',
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'error',
    ],
]);
```

### Development Config

Enable debugging:

```php skip-test
$config = ConfigLoader::load([
    'performance_mode' => 'safe',
    'cache' => [
        'invalidation' => 'mtime',
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'debug',
    ],
]);
```

## See Also

- [Configuration Guide](/data-helpers/getting-started/configuration/) - Overview of all configuration options
- [ConfigHelper](/data-helpers/helpers/config-helper/) - Low-level config access
- [DataHelpersConfig](/data-helpers/api/data-helpers-config/) - High-level config facade

