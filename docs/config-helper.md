# ConfigHelper

The `ConfigHelper` is a universal configuration helper that automatically detects your framework (Laravel, Symfony, or plain PHP) and loads
the appropriate configuration.

## Features

- ✅ **Auto-Detection**: Automatically detects Laravel, Symfony, or falls back to plain PHP
- ✅ **Dot Notation**: Access nested configuration values with dot notation (e.g., `cache.max_entries`)
- ✅ **Type-Safe Getters**: Get configuration values with automatic type casting
- ✅ **Framework Agnostic**: Works with Laravel, Symfony, or standalone PHP
- ✅ **Singleton Pattern**: Efficient single instance across your application

## Usage

### Basic Usage

```php
use event4u\DataHelpers\Config\ConfigHelper;

$config = ConfigHelper::getInstance();

// Get value with dot notation
$maxEntries = $config->get('cache.max_entries', 1000);

// Get typed values
$maxEntries = $config->getInteger('cache.max_entries', 1000);
$mode = $config->getString('performance_mode', 'fast');
$enabled = $config->getBoolean('some.feature', false);
$ratio = $config->getFloat('some.ratio', 1.5);
$items = $config->getArray('some.items', []);
```

### Available Methods

#### `get(string $key, mixed $default = null): mixed`

Get configuration value using dot notation.

```php
$value = $config->get('cache.max_entries');
$value = $config->get('non.existent.key', 'default');
```

#### `getBoolean(string $key, bool $default = false): bool`

Get configuration value as boolean. Converts string values like 'true', '1', 'yes', 'on' to `true`.

```php
$enabled = $config->getBoolean('feature.enabled', false);
```

#### `getInteger(string $key, int $default = 0): int`

Get configuration value as integer.

```php
$maxEntries = $config->getInteger('cache.max_entries', 1000);
```

#### `getFloat(string $key, float $default = 0.0): float`

Get configuration value as float.

```php
$ratio = $config->getFloat('cache.ratio', 1.5);
```

#### `getString(string $key, string $default = ''): string`

Get configuration value as string.

```php
$mode = $config->getString('performance_mode', 'fast');
```

#### `getArray(string $key, array $default = []): array`

Get configuration value as array.

```php
$cache = $config->getArray('cache', []);
```

#### `has(string $key): bool`

Check if configuration key exists.

```php
if ($config->has('cache.max_entries')) {
    // Key exists
}
```

#### `all(): array`

Get all configuration.

```php
$allConfig = $config->all();
```

#### `getSource(): string`

Get the configuration source (laravel, symfony, plain, or default).

```php
$source = $config->getSource();
// Returns: 'laravel', 'symfony', 'plain', or 'default'
```

## Framework Detection

The `ConfigHelper` automatically detects your framework in the following order:

1. **Laravel**: Checks if `app()` and `config()` functions exist
2. **Symfony**: Checks if Symfony's `ContainerInterface` class exists
3. **Plain PHP**: Falls back to loading `config/data-helpers.php`
4. **Default**: Uses hardcoded defaults if no config file is found

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

```php
use event4u\DataHelpers\DataHelpersConfig;

// These methods use ConfigHelper automatically
$maxEntries = DataHelpersConfig::getCacheMaxEntries();
$mode = DataHelpersConfig::getPerformanceMode();
$isFast = DataHelpersConfig::isFastMode();

// You can also use the generic get() method
$value = DataHelpersConfig::get('cache.max_entries', 1000);
```

## Testing

For testing, you can reset the singleton instance:

```php
use event4u\DataHelpers\Config\ConfigHelper;

// Reset singleton
ConfigHelper::resetInstance();

// Or use DataHelpersConfig::reset() which also resets ConfigHelper (stored values)
DataHelpersConfig::reset();
```

You can also manually initialize configuration for testing:

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
use event4u\DataHelpers\Config\ConfigHelper;

$config = ConfigHelper::getInstance();

$maxEntries = $config->getInteger('cache.max_entries', 1000);
$cacheConfig = $config->getArray('cache', []);

echo "Max cache entries: {$maxEntries}\n";
echo "Cache config: " . json_encode($cacheConfig) . "\n";
```

### Example 2: Check Configuration Source

```php
use event4u\DataHelpers\Config\ConfigHelper;

$config = ConfigHelper::getInstance();

$source = $config->getSource();

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

### Example 3: Conditional Logic Based on Config

```php
use event4u\DataHelpers\Config\ConfigHelper;

$config = ConfigHelper::getInstance();

if ($config->getString('performance_mode') === 'fast') {
    // Use fast mode
    echo "Using fast mode\n";
} else {
    // Use safe mode
    echo "Using safe mode\n";
}

if ($config->getInteger('cache.max_entries') > 0) {
    // Caching is enabled
    echo "Caching enabled\n";
} else {
    // Caching is disabled
    echo "Caching disabled\n";
}
```

## Best Practices

1. **Use Type-Safe Getters**: Prefer `getInteger()`, `getString()`, etc. over `get()` for better type safety
2. **Provide Defaults**: Always provide sensible default values
3. **Check Existence**: Use `has()` to check if a key exists before accessing it
4. **Reset in Tests**: Always reset the singleton in test setup/teardown
5. **Use DataHelpersConfig**: For package-specific config, use `DataHelpersConfig` which wraps `ConfigHelper`

## Troubleshooting

### Config Not Loading

If your configuration is not loading:

1. Check that the config file exists in the correct location
2. Verify the file returns an array
3. Check file permissions
4. Use `getSource()` to see which source is being used

### Wrong Framework Detected

If the wrong framework is detected:

1. Check that framework-specific functions/classes are available
2. Verify your framework is properly installed
3. Use `getSource()` to confirm the detected source

### Values Not Updating

If configuration values are not updating:

1. Remember that `ConfigHelper` is a singleton, and `DataHelpersConfig` is the Fascade
2. Call `DataHelpersConfig::reset()` to reload configuration
3. In tests, always reset in `beforeEach()`/`afterEach()`

