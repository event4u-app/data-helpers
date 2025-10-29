---
title: Caching
description: Persistent cache for maximum SimpleDto performance
sidebar:
  order: 15
---

Data Helpers uses an intelligent persistent cache system to dramatically improve SimpleDto performance. This guide explains the different cache invalidation strategies and their performance characteristics.

## Quick Start

```php
// config/data-helpers.php
use event4u\DataHelpers\Enums\CacheInvalidation;

return [
    'cache' => [
        'invalidation' => CacheInvalidation::MANUAL, // Recommended for production
    ],
];
```

```bash
# Warm cache in production deployment
php bin/warm-cache.php src/Dtos

# Clear cache when needed
php bin/clear-cache.php
```

## Cache Invalidation Strategies

Data Helpers supports four cache invalidation strategies, each with different trade-offs between performance and convenience:

### MANUAL (Recommended for Production)

**No automatic validation** - cache is only invalidated by running the clear-cache command.

```php
use event4u\DataHelpers\Enums\CacheInvalidation;

$config->set('cache.invalidation', CacheInvalidation::MANUAL);
```

**Characteristics:**
- ✅ **Best performance** - no validation overhead at runtime
- ✅ **Like Spatie Laravel Data** - cache is trusted completely
- ✅ **Production-ready** - use with cache warming in deployment pipeline
- ⚠️ **Requires manual cache clearing** after code changes

**Use Cases:**
- Production environments with deployment pipelines
- High-throughput applications
- When you control deployments and can warm cache

### MTIME (Recommended for Development)

**Automatic validation** using file modification time (`filemtime()`).

```php
use event4u\DataHelpers\Enums\CacheInvalidation;

$config->set('cache.invalidation', CacheInvalidation::MTIME);
```

**Characteristics:**
- ✅ **Automatic cache invalidation** when files change
- ✅ **Fast validation** - only checks file modification time
- ✅ **Good for development** - no manual cache clearing needed
- ⚠️ **~13% slower** than MANUAL mode

**Use Cases:**
- Development environments
- When you frequently modify DTO classes
- When you want automatic cache invalidation

### HASH (Maximum Accuracy)

**Automatic validation** using file content hash (`hash_file()`).

```php
use event4u\DataHelpers\Enums\CacheInvalidation;

$config->set('cache.invalidation', CacheInvalidation::HASH);
```

**Characteristics:**
- ✅ **Most accurate** - detects content changes even if mtime is unchanged
- ✅ **Automatic cache invalidation** when file content changes
- ⚠️ **~18% slower** than MANUAL mode
- ⚠️ **Slower than MTIME** - reads entire file to compute hash

**Use Cases:**
- When you use `git checkout` (mtime may not change)
- When you need maximum accuracy
- When performance is less critical than correctness

### BOTH (Maximum Safety)

**Automatic validation** using both `filemtime()` and `hash_file()`.

```php
use event4u\DataHelpers\Enums\CacheInvalidation;

$config->set('cache.invalidation', CacheInvalidation::BOTH);
```

**Characteristics:**
- ✅ **Maximum safety** - validates both mtime and content hash
- ⚠️ **Slowest option** - combines overhead of both methods
- ⚠️ **Rarely needed** - HASH alone is usually sufficient

**Use Cases:**
- When you need absolute certainty
- Debugging cache issues
- Rarely needed in practice

## Performance Benchmarks

The following benchmarks show the performance impact of each cache invalidation strategy:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.44 μs
- MTIME (auto-validation):    2.41 μs
- HASH (auto-validation):     2.35 μs
```
<!-- BENCHMARK_CACHE_INVALIDATION_END -->

:::tip[Performance Recommendation]
Use **MANUAL** in production with cache warming in your deployment pipeline for best performance.
Use **MTIME** in development for automatic cache invalidation without manual clearing.
:::

## Configuration

### Environment Variables

You can configure cache invalidation via environment variables:

```bash
# .env
DATA_HELPERS_CACHE_INVALIDATION=manual  # or mtime, hash, both
```

### Programmatic Configuration

```php
use event4u\DataHelpers\Helpers\ConfigHelper;
use event4u\DataHelpers\Enums\CacheInvalidation;

$config = ConfigHelper::getInstance();
$config->set('cache.invalidation', CacheInvalidation::MANUAL);
```

## Cache Warming

For production deployments, warm the cache during deployment:

```bash
# In your deployment script
php bin/warm-cache.php src/Dtos
```

This pre-generates cache for all DTOs, ensuring zero cold-start penalty.

:::note[Learn More]
See the [Cache Generation Guide](/data-helpers/performance/cache-generation/) for detailed instructions on cache warming and clearing.
:::

## How It Works

### MANUAL Mode (Spatie-Style)

1. First request: Reflection generates metadata → stored in persistent cache
2. Subsequent requests: Metadata loaded from cache **without validation**
3. Cache invalidation: Only via `bin/clear-cache.php` command

### MTIME/HASH/BOTH Modes

1. First request: Reflection generates metadata → stored in persistent cache
2. Subsequent requests:
   - Load metadata from cache
   - **Validate** using `filemtime()` and/or `hash_file()`
   - If invalid: Regenerate metadata via reflection
   - If valid: Use cached metadata

## Best Practices

### Production

```php
// config/data-helpers.php
return [
    'cache' => [
        'driver' => CacheDriver::AUTO,        // Auto-detect Laravel/Symfony
        'invalidation' => CacheInvalidation::MANUAL, // Best performance
    ],
];
```

```bash
# In deployment pipeline
php bin/clear-cache.php
php bin/warm-cache.php src/Dtos
```

### Development

```php
// config/data-helpers.php
return [
    'cache' => [
        'driver' => CacheDriver::AUTO,
        'invalidation' => CacheInvalidation::MTIME, // Auto-invalidation
    ],
];
```

No manual cache clearing needed - cache updates automatically when files change.

## Troubleshooting

### Cache Not Updating

If you're using **MANUAL** mode and cache isn't updating after code changes:

```bash
# Clear cache manually
php bin/clear-cache.php

# Or switch to MTIME for development
# config/data-helpers.php
'invalidation' => CacheInvalidation::MTIME,
```

### Performance Issues

If you're experiencing slow performance:

1. Check your cache invalidation mode:
   ```bash
   # Should be MANUAL in production
   echo $DATA_HELPERS_CACHE_INVALIDATION
   ```

2. Warm the cache:
   ```bash
   php bin/warm-cache.php src/Dtos
   ```

3. Verify cache is working:
   ```bash
   # Should show cached files
   ls -la storage/cache/data-helpers/
   ```

## See Also

- [Cache Warming Guide](/data-helpers/performance/cache-warming/) - Detailed cache warming documentation
- [Cache Generation Guide](/data-helpers/performance/cache-generation/) - Manual cache generation instructions
- [Performance Benchmarks](/data-helpers/performance/benchmarks/) - Complete performance comparison
- [Configuration](/data-helpers/getting-started/configuration/) - All configuration options

