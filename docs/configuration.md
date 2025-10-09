# Configuration

The Data Helpers package can be configured via environment variables or framework-specific configuration files.

## Laravel Configuration

### 1. Auto-Discovery

The package uses Laravel's **auto-discovery** feature. The service provider is **automatically registered** when you install the package via Composer.

**No manual registration needed!** ✅

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=data-helpers-config
```

This copies `config/laravel/data-helpers.php` to `config/data-helpers.php` in your Laravel application.

**Note:** Publishing is optional. The package works with default values if you don't publish the config.

### 3. Configure via Environment Variables

Add to your `.env` file:

```env
# Cache Settings
DATA_HELPERS_CACHE_MAX_ENTRIES=1000

# Performance Mode (fast|safe)
DATA_HELPERS_PERFORMANCE_MODE=fast
```

### 4. Configure via Config File

Edit `config/data-helpers.php`:

```php
return [
    'cache' => [
        'max_entries' => 5000, // Override default
    ],
    'performance_mode' => 'safe', // Use safe mode
];
```

## Symfony Configuration

### 1. Auto-Discovery

The package uses Symfony's **auto-discovery** feature (Symfony Flex). The bundle is **automatically registered** when you install the package via Composer.

**No manual registration needed!** ✅

### 2. Configuration File

**With Symfony Flex (Recommended):**

The configuration file is **automatically copied** to `config/packages/data_helpers.yaml` when you install the package.

**Without Symfony Flex:**

Manually copy the configuration file:

```bash
cp vendor/event4u/data-helpers/config/symfony/data_helpers.yaml config/packages/
```

**Note:** The package works with default values even without the config file.

### 3. Configure via Environment Variables

Add to your `.env` file:

```env
# Cache Settings
DATA_HELPERS_CACHE_MAX_ENTRIES=1000

# Performance Mode (fast|safe)
DATA_HELPERS_PERFORMANCE_MODE=fast
```

### 3. Configure via YAML

Edit `config/packages/data_helpers.yaml`:

```yaml
data_helpers:
    cache:
        max_entries: 5000 # Override default
    performance_mode: 'safe' # Use safe mode
```

## Plain PHP Configuration

If you're not using Laravel or Symfony, you can use the plain PHP configuration.

### 1. Copy Configuration File

Copy the plain PHP configuration to your project:

```bash
cp vendor/event4u/data-helpers/config/plain/data-helpers.php config/
```

### 2. Configure via Environment Variables

Set environment variables in your `.env` file or server configuration:

```env
# Cache Settings
DATA_HELPERS_CACHE_MAX_ENTRIES=1000

# Performance Mode (fast|safe)
DATA_HELPERS_PERFORMANCE_MODE=fast
```

### 3. Configure via PHP File

Edit `config/data-helpers.php`:

```php
return [
    'cache' => [
        'max_entries' => 5000, // Override default
    ],
    'performance_mode' => 'safe', // Use safe mode
];
```

## Configuration Options

### Cache Settings

#### `cache.max_entries`

**Type:** `int`
**Default:** `1000`
**Environment Variable:** `DATA_HELPERS_CACHE_MAX_ENTRIES`

Maximum number of parsed template expressions to cache. When the limit is reached, the least recently used (LRU) entries are discarded.

**Recommendations:**
- **Small applications:** 500-1000
- **Medium applications:** 1000-3000
- **Large applications:** 3000-5000
- **Disable caching:** Set to `0`

**Example:**
```env
DATA_HELPERS_CACHE_MAX_ENTRIES=2000
```

### Performance Mode

#### `performance_mode`

**Type:** `string` (`fast` | `safe`)
**Default:** `fast`
**Environment Variable:** `DATA_HELPERS_PERFORMANCE_MODE`

Controls the parsing mode for template expressions:

- **`fast`** (recommended): ~2x faster parsing, no escape sequence handling
  - Use for standard cases: `{{ value | trim }}`, `{{ tags | join:", " }}`
  - Does NOT process: `\n`, `\t`, `\"`, `\\`, etc.

- **`safe`**: Full escape sequence handling
  - Use when you need: `{{ value | default:"Line1\nLine2" }}`
  - Processes: `\n` (newline), `\t` (tab), `\"` (quote), `\\` (backslash), etc.

**Example:**
```env
DATA_HELPERS_PERFORMANCE_MODE=fast
```

## Programmatic Configuration

### Direct Configuration

```php
use event4u\DataHelpers\DataHelpersConfig;

// Initialize with custom config
DataHelpersConfig::initialize([
    'cache' => [
        'max_entries' => 2000,
    ],
    'performance_mode' => 'safe',
]);

// Get configuration values
$maxEntries = DataHelpersConfig::getCacheMaxEntries(); // 2000
$isFast = DataHelpersConfig::isFastMode(); // false
```

### Runtime Mode Switching

```php
use event4u\DataHelpers\DataMapper\Template\FilterEngine;

// Switch to safe mode for specific operations
FilterEngine::useFastSplit(false);

// Process templates with escape sequences
$result = DataMapper::mapFromTemplate($template, $sources);

// Switch back to fast mode
FilterEngine::useFastSplit(true);
```

### Cache Management

```php
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;

// Get cache statistics
$stats = ExpressionParser::getCacheStats();
// Returns: ['size' => 150, 'max_size' => 1000, 'usage_percentage' => 15.0]

// Clear cache
ExpressionParser::clearCache();
```

## Performance Considerations

### Cache Size

The cache stores parsed template expressions to avoid re-parsing. Each entry contains:
- Expression string (key)
- Parsed result (type, path, default, filters)

**Memory usage estimation:**
- ~500 bytes per entry (average)
- 1000 entries ≈ 500 KB
- 5000 entries ≈ 2.5 MB

### Fast vs. Safe Mode Performance

Based on benchmarks with 10,000 iterations:

| Scenario | Fast Mode | Safe Mode | Speedup |
|----------|-----------|-----------|---------|
| Simple expressions | 10.0 ms | 18.6 ms | **1.85x faster** |
| With quotes | 15.2 ms | 29.9 ms | **1.97x faster** |
| Complex expressions | 26.2 ms | 52.5 ms | **2.01x faster** |

**Recommendation:** Use fast mode (default) for 90%+ of use cases.

## Examples

### Laravel Example

```php
// .env
DATA_HELPERS_CACHE_MAX_ENTRIES=2000
DATA_HELPERS_PERFORMANCE_MODE=fast

// Usage
use event4u\DataHelpers\DataMapper;

$template = [
    'name' => '{{ user.name | trim | upper }}',
    'tags' => '{{ user.tags | join:", " }}',
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

### Symfony Example

```yaml
# config/packages/data_helpers.yaml
data_helpers:
    cache:
        max_entries: 2000
    performance_mode: 'fast'
```

```php
// Usage
use event4u\DataHelpers\DataMapper;

$template = [
    'name' => '{{ user.name | trim | upper }}',
    'tags' => '{{ user.tags | join:", " }}',
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

## Troubleshooting

### Cache Not Working

1. Check configuration is loaded:
   ```php
   $maxEntries = DataHelpersConfig::getCacheMaxEntries();
   var_dump($maxEntries); // Should not be 0
   ```

2. Check cache statistics:
   ```php
   $stats = ExpressionParser::getCacheStats();
   var_dump($stats); // Check 'size' is increasing
   ```

### Escape Sequences Not Working

Make sure you're using safe mode:

```php
// Option 1: Via config
DATA_HELPERS_PERFORMANCE_MODE=safe

// Option 2: Runtime
FilterEngine::useFastSplit(false);
```

### Memory Issues

If you're experiencing memory issues, reduce cache size:

```env
DATA_HELPERS_CACHE_MAX_ENTRIES=500
```

Or disable caching completely:

```env
DATA_HELPERS_CACHE_MAX_ENTRIES=0
```

