# Configuration

The Data Helpers package can be configured via environment variables or framework-specific configuration files.

**New in v1.x:** All frameworks (Laravel, Symfony, Plain PHP) now use a **unified configuration file** (`config/data-helpers.php`) with the
`EnvHelper` class for automatic environment detection. This eliminates code duplication and provides consistent type-casting across all
environments.

## Quick Setup

### Laravel

```bash
# 1. Publish config (optional)
php artisan vendor:publish --tag=data-helpers-config

# 2. Configure via .env
DATA_HELPERS_CACHE_MAX_ENTRIES=1000
DATA_HELPERS_CACHE_TTL=3600
```

### Symfony

```bash
# 1. Create config file (auto-discovered with Flex)
# config/packages/data_helpers.yaml

data_helpers:
  cache:
    max_entries: '%env(int:DATA_HELPERS_CACHE_MAX_ENTRIES)%'
    default_ttl: '%env(int:DATA_HELPERS_CACHE_TTL)%'

# 2. Add to .env
DATA_HELPERS_CACHE_MAX_ENTRIES=1000
DATA_HELPERS_CACHE_TTL=3600
```

### Plain PHP

```php
// bootstrap.php
$config = require __DIR__ . '/vendor/event4u/data-helpers/config/data-helpers.php';
event4u\DataHelpers\DataHelpersConfig::initialize($config);

// Or use custom config
$config = require __DIR__ . '/config/data-helpers.php';
event4u\DataHelpers\DataHelpersConfig::initialize($config);
```

---

## EnvHelper - Unified Environment Variable Handling

The `EnvHelper` class provides a **framework-agnostic** way to read environment variables with automatic type-casting:

```php
use event4u\DataHelpers\Helpers\EnvHelper;

// Automatically detects Laravel env(), Symfony $_ENV, or Plain PHP $_ENV
$driver = EnvHelper::string('DATA_HELPERS_CACHE_DRIVER', 'memory');
$maxEntries = EnvHelper::integer('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000);
$ttl = EnvHelper::integer('DATA_HELPERS_CACHE_DEFAULT_TTL', 3600);
$enabled = EnvHelper::boolean('DATA_HELPERS_CACHE_ENABLED', true);
```

**Available Methods:**

- `EnvHelper::get($key, $default)` - Raw value (auto-detects environment)
- `EnvHelper::string($key, $default, $forceCast)` - String with type-casting
- `EnvHelper::integer($key, $default, $forceCast)` - Integer with type-casting
- `EnvHelper::float($key, $default, $forceCast)` - Float with type-casting
- `EnvHelper::boolean($key, $default, $forceCast)` - Boolean with type-casting
- `EnvHelper::carbon($key, $default)` - Carbon date/time instance *(requires nesbot/carbon)*
- `EnvHelper::hasCarbonSupport()` - Check if Carbon is available

**How it works:**

1. **Laravel:** Uses `env()` function if available
2. **Symfony/Plain PHP:** Falls back to `$_ENV` superglobal

**Carbon Support (Optional):**
The `carbon()` method is only available when `nesbot/carbon` is installed. If Carbon is not available, calling `EnvHelper::carbon()` will
throw an `InvalidArgumentException`. Use `EnvHelper::hasCarbonSupport()` to check availability before calling.

This allows the **same configuration file** to work across all frameworks! 🎉

---

## Laravel Configuration

### 1. Auto-Discovery

The package uses Laravel's **auto-discovery** feature. The service provider is **automatically registered** when you install the package via
Composer.

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

The package uses Symfony's **auto-discovery** feature (Symfony Flex). The bundle is **automatically registered** when you install the
package via Composer.

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

#### Composer Commands

```bash
# Clear all caches
composer cache:clear

# Show cache statistics
composer cache:stats

# Clear cache for specific class
php scripts/cache-clear.php TemplateParser
```

#### Programmatic Cache Management

```php
use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\ClassScopedCache;
use event4u\DataHelpers\Cache\HashValidatedCache;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;
use event4u\DataHelpers\DataMapper\Support\TemplateParser;

// Get cache statistics for ExpressionParser
$stats = ExpressionParser::getCacheStats();
// Returns: ['size' => 150, 'max_size' => 1000, 'usage_percentage' => 15.0]

// Get cache statistics for TemplateParser (ClassScopedCache)
$stats = ClassScopedCache::getClassStats(TemplateParser::class);
// Returns: ['count' => 45, 'keys' => [...]]

// Clear specific caches
ExpressionParser::clearCache();
ClassScopedCache::clearClass(TemplateParser::class);
HashValidatedCache::clearClass(TemplateParser::class);

// Clear all caches
CacheHelper::clear();
```

#### Hash-Validated Cache

The package includes **automatic cache invalidation** when source data changes:

```php
use event4u\DataHelpers\Cache\HashValidatedCache;

// Cache with hash validation
$template = ['name' => '{{ user.name | upper }}'];
$cacheKey = 'my_template';

// Store with hash validation
HashValidatedCache::set(
    TemplateParser::class,
    $cacheKey,
    $parsedTemplate,
    $template  // Source data for hash validation
);

// Retrieve - automatically invalidates if template changed
$cached = HashValidatedCache::get(
    TemplateParser::class,
    $cacheKey,
    $template  // Current template for comparison
);

// If $template changed, $cached will be null
```

**How it works:**

1. When storing, calculates hash of source data (template, class file, etc.)
2. Stores both value and hash in cache
3. When retrieving, recalculates hash and compares
4. If hash differs, cache is invalidated and null is returned

**Use cases:**

- Template caching (invalidate when template changes)
- Class-based caching (invalidate when class file changes)
- Configuration caching (invalidate when config changes)

**Supported source data types:**

- **Strings**: Direct hash of string content
- **Arrays**: Serialized and hashed
- **Objects**: Serialized and hashed
- **File paths**: Reads file content and hashes it
- **Class names**: Reads class file and hashes it

```php
// Example: Template caching with auto-invalidation
$template = ['name' => '{{ user.name }}'];

$parsed = HashValidatedCache::remember(
    TemplateParser::class,
    'template_key',
    $template,  // Source data
    fn() => TemplateParser::parseMapping($template)
);

// If template changes, cache is automatically invalidated
$template = ['name' => '{{ user.fullName }}'];  // Changed!
$parsed = HashValidatedCache::remember(
    TemplateParser::class,
    'template_key',
    $template,  // New template
    fn() => TemplateParser::parseMapping($template)  // Recalculated
);
```

## Performance Considerations

### Cache Size

The package uses multiple caching layers to optimize performance:

**1. Template Expression Cache (ExpressionParser)**

- Stores parsed template expressions to avoid re-parsing
- Each entry contains: expression string (key), parsed result (type, path, default, filters)
- Memory usage: ~500 bytes per entry (average)

**2. Template Mapping Cache (TemplateParser)**

- Stores parsed mapping arrays using ClassScopedCache
- Max 100 entries per class with LRU eviction
- Memory usage: ~1-2 KB per entry (depends on mapping complexity)

**3. File Content Cache (FileLoader)**

- Caches loaded JSON/XML files to avoid repeated I/O
- Unlimited size (static cache)
- Memory usage: depends on file size

**4. Filter Instance Cache (FilterEngine)**

- Reuses filter instances instead of creating new ones
- Unlimited size (static cache)
- Memory usage: ~1-2 KB per filter class

**5. String Operation Caches**

- `toCamelCase()` - caches string transformations
- `singularize()` - caches pluralization results
- `parseFilterWithArgs()` - caches filter parsing
- Memory usage: ~100-200 bytes per entry

**6. Reflection Caches**

- `ReflectionCache` - caches ReflectionClass and ReflectionProperty instances
- `EntityHelper` - caches property existence and relation type checks
- Memory usage: ~500 bytes per class/property

**Total memory usage estimation:**

- 1000 template expressions ≈ 500 KB
- 100 template mappings ≈ 100-200 KB
- 50 loaded files ≈ 50-500 KB (depends on file size)
- 20 filter instances ≈ 20-40 KB
- 500 string operations ≈ 50-100 KB
- 100 reflection entries ≈ 50 KB
- **Total: ~1-2 MB for typical applications**

### Fast vs. Safe Mode Performance

Based on benchmarks with 10,000 iterations:

| Scenario            | Fast Mode | Safe Mode | Speedup          |
|---------------------|-----------|-----------|------------------|
| Simple expressions  | 10.0 ms   | 18.6 ms   | **1.85x faster** |
| With quotes         | 15.2 ms   | 29.9 ms   | **1.97x faster** |
| Complex expressions | 26.2 ms   | 52.5 ms   | **2.01x faster** |

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

