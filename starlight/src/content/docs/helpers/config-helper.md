---
title: ConfigHelper
description: Universal configuration helper with automatic framework detection
---

ConfigHelper is a universal configuration helper that automatically detects your framework (Laravel, Symfony, or plain PHP) and loads the appropriate configuration with dot-notation access.

## Quick Example

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();

// Get value with dot notation
$maxEntries = $config->get('cache.max_entries', 1000);

// Get typed values
$maxEntries = $config->getInteger('cache.max_entries', 1000);
$mode = $config->getString('performance_mode', 'fast');
$enabled = $config->getBoolean('feature.enabled', false);
$ratio = $config->getFloat('logging.sampling.errors', 1.5);
$items = $config->getArray('cache', []);
```

## Introduction

ConfigHelper provides a unified interface for accessing configuration across different frameworks.

### Key Features

- **Auto-Detection** - Automatically detects Laravel, Symfony, or falls back to plain PHP
- **Dot Notation** - Access nested configuration values with dot notation
- **Type-Safe Getters** - Get configuration values with automatic type casting
- **Framework Agnostic** - Works with Laravel, Symfony, or standalone PHP
- **Singleton Pattern** - Efficient single instance across your application

### Framework Detection Order

1. **Laravel** - Checks if `app()` and `config()` functions exist
2. **Symfony** - Checks if Symfony's `ContainerInterface` class exists
3. **Plain PHP** - Falls back to loading `config/data-helpers.php`
4. **Default** - Uses hardcoded defaults if no config file is found

## Basic Usage

### Getting Configuration Values

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();

// Get value with default
$value = $config->get('cache.max_entries', 1000);

// Get nested value
$value = $config->get('logging.sampling.errors', 1.0);

// Check if key exists
if ($config->has('performance_mode')) {
    $mode = $config->get('performance_mode');
}

// Get all configuration
$allConfig = $config->all();
```

### Type-Safe Getters

```php
$config = ConfigHelper::getInstance();

// Get as boolean
$enabled = $config->getBoolean('feature.enabled', false);
// Converts: 'true', '1', 'yes', 'on' → true

// Get as integer
$maxEntries = $config->getInteger('cache.max_entries', 1000);

// Get as float
$ratio = $config->getFloat('logging.sampling.errors', 1.5);

// Get as string
$mode = $config->getString('performance_mode', 'fast');

// Get as array
$cacheConfig = $config->getArray('cache', []);
```

### Check Configuration Source

```php
$config = ConfigHelper::getInstance();
$source = $config->getSource();
// Result: 'laravel' | 'symfony' | 'plain' | 'default'

switch ($source) {
    case 'laravel':
        echo "Using Laravel configuration\n";
        break;
    case 'symfony':
        echo "Using Symfony configuration\n";
        break;
    case 'plain':
        echo "Using plain PHP configuration\n";
        break;
    case 'default':
        echo "Using default configuration\n";
        break;
}
```


## API Reference

### get(string $key, mixed $default = null): mixed

Get configuration value using dot notation.

```php
$config = ConfigHelper::getInstance();
$value = $config->get('cache.max_entries');
$value = $config->get('non.existent.key', 'default');
```

### getBoolean(string $key, bool $default = false): bool

Get configuration value as boolean. Converts string values like 'true', '1', 'yes', 'on' to `true`.

```php
$config = ConfigHelper::getInstance();
$enabled = $config->getBoolean('feature.enabled', false);
```

### getInteger(string $key, int $default = 0): int

Get configuration value as integer.

```php
$config = ConfigHelper::getInstance();
$maxEntries = $config->getInteger('cache.max_entries', 1000);
```

### getFloat(string $key, float $default = 0.0): float

Get configuration value as float.

```php
$config = ConfigHelper::getInstance();
$ratio = $config->getFloat('logging.sampling.errors', 1.5);
```

### getString(string $key, string $default = ''): string

Get configuration value as string.

```php
$config = ConfigHelper::getInstance();
$mode = $config->getString('performance_mode', 'fast');
```

### getArray(string $key, array $default = []): array

Get configuration value as array.

```php
$config = ConfigHelper::getInstance();
$cacheConfig = $config->getArray('cache', []);
```

### has(string $key): bool

Check if configuration key exists.

```php
$config = ConfigHelper::getInstance();
if ($config->has('performance_mode')) {
    // Key exists
}
```

### all(): array

Get all configuration.

```php
$config = ConfigHelper::getInstance();
$allConfig = $config->all();
```

### getSource(): string

Get the configuration source (laravel, symfony, plain, or default).

```php
$config = ConfigHelper::getInstance();
$source = $config->getSource();
// Result: 'laravel' | 'symfony' | 'plain' | 'default'
```

## Configuration Files

### Laravel

The helper loads configuration from Laravel's config system:

```php
// config/data-helpers.php
return [
    'cache' => [
        'max_entries' => env('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000),
    ],
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),
];
```

### Symfony

The helper loads configuration from Symfony's parameter bag:

```yaml
# config/packages/data_helpers.yaml
data_helpers:
    cache:
        max_entries: '%env(int:DATA_HELPERS_CACHE_MAX_ENTRIES)%'
    performance_mode: '%env(DATA_HELPERS_PERFORMANCE_MODE)%'
```

### Plain PHP

The helper loads configuration from a plain PHP file:

```php
// config/data-helpers.php
return [
    'cache' => [
        'max_entries' => (int) ($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? 1000),
    ],
    'performance_mode' => $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? 'fast',
];
```

## Integration with DataHelpersConfig

The `DataHelpersConfig` class uses `ConfigHelper` internally:

<!-- skip-test: DataHelpersConfig methods shown for documentation -->
```php
use event4u\DataHelpers\DataHelpersConfig;

// These methods use ConfigHelper automatically
$mode = DataHelpersConfig::getPerformanceMode();
$maxEntries = DataHelpersConfig::getCacheMaxEntries();
$isFast = DataHelpersConfig::isFastMode();

// You can also use the generic get() method
$value = DataHelpersConfig::get('cache.max_entries', 1000);
```

## Testing

### Reset Singleton

For testing, you can reset the singleton instance:

```php
use event4u\DataHelpers\DataHelpersConfig;use event4u\DataHelpers\Helpers\ConfigHelper;

ConfigHelper::resetInstance();
DataHelpersConfig::reset();
```

### Manual Initialization

You can manually initialize configuration for testing:

```php
use event4u\DataHelpers\DataHelpersConfig;

// Manually initialize (bypasses ConfigHelper)
DataHelpersConfig::initialize([
    'cache' => [
        'max_entries' => 500,
    ],
    'performance_mode' => 'safe',
]);
```

## Examples

### Example 1: Get Cache Configuration

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();

$maxEntries = $config->getInteger('cache.max_entries', 1000);
$cacheConfig = $config->getArray('cache', []);

echo "Max entries: {$maxEntries}\n";
echo "Cache config: " . json_encode($cacheConfig) . "\n";
```

### Example 2: Conditional Logic Based on Config

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();

if ($config->getString('performance_mode') === 'fast') {
    // Use fast mode
    echo "Using fast mode\n";
} else {
    // Use safe mode
    echo "Using safe mode\n";
}

if ($config->getBoolean('cache.enabled', false)) {
    // Caching is enabled
    echo "Caching enabled\n";
} else {
    // Caching is disabled
    echo "Caching disabled\n";
}
```

### Example 3: Framework-Specific Configuration

```php
use event4u\DataHelpers\Helpers\ConfigHelper;

$config = ConfigHelper::getInstance();
$source = $config->getSource();

if ($source === 'laravel') {
    // Laravel-specific logic
    $value = $config->get('cache.max_entries');
} elseif ($source === 'symfony') {
    // Symfony-specific logic
    $value = $config->get('cache.max_entries');
} else {
    // Plain PHP or default
    $value = $config->get('cache.max_entries', 1000);
}
```

## Best Practices

### Use Type-Safe Getters

Prefer type-specific methods over generic `get()` for better type safety.

```php
$config = ConfigHelper::getInstance();
// ✅ Correct - Type-safe
$maxEntries = $config->getInteger('cache.max_entries', 1000);
$enabled = $config->getBoolean('feature.enabled', false);

// ❌ Wrong - No type safety
$maxEntries = $config->get('cache.max_entries', 1000);
$enabled = $config->get('feature.enabled', false);
```

### Provide Defaults

Always provide sensible default values.

```php
$config = ConfigHelper::getInstance();
// ✅ Correct - Has default
$maxEntries = $config->getInteger('cache.max_entries', 1000);

// ❌ Wrong - No default
$maxEntries = $config->getInteger('cache.max_entries');
```

### Check Existence

Use `has()` to check if a key exists before accessing it.

```php
$config = ConfigHelper::getInstance();
// ✅ Correct - Check first
if ($config->has('performance_mode')) {
    $mode = $config->getString('performance_mode');
}

// ❌ Wrong - No check
$mode = $config->getString('performance_mode');
```

### Reset in Tests

Always reset the singleton in test setup/teardown.

```php
// ✅ Correct - Reset in tests
beforeEach(function () {
    ConfigHelper::resetInstance();
});

afterEach(function () {
    ConfigHelper::resetInstance();
});
```

### Use DataHelpersConfig

For package-specific config, use `DataHelpersConfig` which wraps `ConfigHelper`.

```php
// ✅ Correct - Use DataHelpersConfig
$mode = DataHelpersConfig::getPerformanceMode();

// ❌ Wrong - Direct ConfigHelper access
$mode = ConfigHelper::getInstance()->getString('performance_mode');
```

## Troubleshooting

### Config Not Loading

If your configuration is not loading:

1. Check that the config file exists in the correct location
2. Verify the file returns an array
3. Check file permissions
4. Use `getSource()` to see which source is being used

```php
$config = ConfigHelper::getInstance();
$source = $config->getSource();
echo "Using source: {$source}\n";
```

### Wrong Framework Detected

If the wrong framework is detected:

1. Check that framework-specific functions/classes are available
2. Verify your framework is properly installed
3. Use `getSource()` to confirm the detected source

### Values Not Updating

If configuration values are not updating:

1. Remember that `ConfigHelper` is a singleton
2. Call `ConfigHelper::resetInstance()` to reload configuration
3. In tests, always reset in `beforeEach()`/`afterEach()`

```php
// Reset and reload
ConfigHelper::resetInstance();
$config = ConfigHelper::getInstance();
```

## See Also

- [EnvHelper](/helpers/env-helper/) - Environment variable helper
- [DataHelpersConfig](/data-helpers/core-concepts/configuration/) - Package configuration
- [Framework Integration](/data-helpers/framework-integration/laravel/) - Framework-specific features
