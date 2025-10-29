---
title: Cache Warming
description: Pre-generate persistent cache for maximum performance
sidebar:
  order: 3
---

Data Helpers uses an intelligent persistent cache system to dramatically improve performance. This guide explains how to warm up the cache before running your application or tests.

**Phase 11a** introduced persistent caching with automatic invalidation. The cache stores metadata about your SimpleDtos and survives PHP process restarts, making subsequent requests much faster.

## Quick Start

```bash
# Warm up cache (recommended)
task dev:cache:warmup

# Clear cache
task dev:cache:clear

# Or use CLI scripts directly
php bin/warm-cache.php src/Dtos
php bin/clear-cache.php
```

## Benefits

- **🚀 Faster First Request**: No cold start penalty
- **⚡ Improved Test Performance**: Tests run ~37% faster (18.45s → 11.50s)
- **🔄 Shared Between Workers**: Cache is shared across PHP-FPM workers
- **🎯 Production Ready**: Warm cache during deployment
- **✅ Automatic Invalidation**: Cache updates when source files change

## Cache Warming Commands

### Task Commands (Recommended)

The easiest way to manage cache is using the Taskfile commands:

```bash
# Warm up cache (default: PHP 8.4)
task dev:cache:warmup

# Clear cache
task dev:cache:clear

# Use specific PHP version
task dev:cache:warmup PHP=8.2
task dev:cache:clear PHP=8.3

# Alias also available
task dev:cache:warm
```

**Features:**
- ✅ Works in Docker containers
- ✅ Automatic PHP version selection
- ✅ Beautiful colored output
- ✅ Error handling included
- ✅ Consistent with other project tasks

### CLI Scripts

You can also use the CLI scripts directly:

#### Warm Cache

```bash
# Warm cache for specific directories
php bin/warm-cache.php tests/Utils/SimpleDtos tests/Utils/Dtos

# Verbose output (shows each class)
php bin/warm-cache.php -v src/Dtos

# Skip validation (faster)
php bin/warm-cache.php --no-validate src/Dtos

# Show help
php bin/warm-cache.php --help
```

**Options:**
- `-v, --verbose` - Show detailed output with each class
- `-q, --quiet` - Suppress all output except errors
- `--no-validate` - Skip cache validation after warming
- `-h, --help` - Show help message

#### Clear Cache

```bash
# Clear all persistent cache
php bin/clear-cache.php

# Verbose output
php bin/clear-cache.php -v

# Show help
php bin/clear-cache.php --help
```

**Options:**
- `-v, --verbose` - Show detailed output
- `-h, --help` - Show help message

## Automatic Cache Warming

### Test Bootstrap

The test suite automatically warms the cache before running tests via `tests/bootstrap.php`:

```php
// tests/bootstrap.php
require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Console\WarmCacheCommand;

$command = new WarmCacheCommand();
$directories = [
    __DIR__ . '/Utils/SimpleDtos',
    __DIR__ . '/Utils/Dtos',
];

// Execute cache warming silently
$command->execute($directories, verbose: false, validate: false);
```

This is configured in `phpunit.xml`:

```xml
<phpunit bootstrap="tests/bootstrap.php" ...>
```

### Production Deployment

Add cache warming to your deployment pipeline:

```bash
# In your deployment script
php bin/warm-cache.php src/Dtos app/Dtos

# Or with Composer scripts
composer dump-autoload --optimize
php bin/warm-cache.php src/Dtos
```

## How It Works

### Cache Storage

The cache is stored in `.event4u/data-helpers/cache/` by default (configurable via `config/data-helpers.php`).

### Automatic Invalidation

The cache automatically invalidates when source files change:

```php
// config/data-helpers.php
'cache' => [
    'invalidation' => CacheInvalidation::MTIME, // or HASH, BOTH
],
```

**Invalidation Strategies:**

- **`MTIME`** (default): Fast, checks file modification time
- **`HASH`**: Accurate, checks file content hash (xxh128)
- **`BOTH`**: Most accurate, checks both mtime and hash

### What Gets Cached

Only SimpleDtos with:
- ✅ Valid source file (for invalidation tracking)
- ✅ Constructor parameters
- ✅ Metadata that can be extracted

Skipped:
- ❌ Abstract classes
- ❌ Classes without constructor parameters
- ❌ Classes without source file

## Cache Warming Output

### Normal Mode

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Data Helpers - Cache Warming
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Found 9 SimpleDto classes

........

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Classes found:   9
  Classes warmed:  8
  Classes skipped: 1
  Classes failed:  0

✅  Cache warming completed successfully
```

### Verbose Mode

```bash
php bin/warm-cache.php -v src/Dtos
```

Shows detailed information:
- ✅ Each class warmed with parameter count
- ⚠️ Skipped classes with reason
- ❌ Failed classes with error message
- 📊 Cache validation results

## Configuration

### Cache Path

```php
<?php
// config/data-helpers.php

return [
    'cache' => [
        'path' => './.event4u/data-helpers/cache/',
    ],
];
```

### Cache Driver

```php
<?php
// config/data-helpers.php

use event4u\DataHelpers\Enums\CacheDriver;

return [
    'cache' => [
        'driver' => CacheDriver::AUTO, // AUTO, LARAVEL, SYMFONY, FILESYSTEM
    ],
];
```

**Auto-Detection Order:**
1. Laravel Cache (if available and working)
2. Symfony Cache (if available and working)
3. Filesystem Cache (always available)

### Invalidation Strategy

```php
<?php
// config/data-helpers.php

use event4u\DataHelpers\Enums\CacheInvalidation;

return [
    'cache' => [
        'invalidation' => CacheInvalidation::MTIME, // MTIME, HASH, BOTH
    ],
];
```

## Performance Impact

### Test Suite Performance

**Before Cache Warming:**
- Duration: 18.45s
- Cold start on every test run

**After Cache Warming:**
- Duration: 11.50s
- **37% faster** ⚡
- Cache persists between test runs

### Production Performance

**First Request (Cold Start):**
- Without cache: ~6.1μs per DTO operation
- With warmed cache: ~4.5-5μs per DTO operation
- **20-30% faster** 🚀

**Subsequent Requests:**
- Cache hit: ~4.5-5μs per DTO operation
- Shared between PHP-FPM workers
- Automatic invalidation on file changes

## Best Practices

### Development

**Use Task Commands** (recommended for local development):

```bash
# Warm cache after pulling changes
git pull
task dev:cache:warmup

# Clear cache when debugging cache issues
task dev:cache:clear

# Warm cache with specific PHP version
task dev:cache:warmup PHP=8.2

# Quick workflow
task dev:cache:clear && task dev:cache:warmup && task test:run
```

**Why Task Commands?**
- ✅ Works seamlessly with Docker containers
- ✅ Automatic PHP version handling
- ✅ Beautiful colored output
- ✅ Consistent with other project tasks
- ✅ Error handling included

**Alternative: Direct CLI Scripts**

```bash
# If not using Docker/Taskfile
php bin/warm-cache.php tests/Utils/SimpleDtos
php bin/clear-cache.php
```

### CI/CD Pipeline

**GitHub Actions:**

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Warm cache
        run: php bin/warm-cache.php tests/Utils/SimpleDtos tests/Utils/Dtos

      - name: Run tests
        run: vendor/bin/pest
```

**GitLab CI:**

```yaml
# .gitlab-ci.yml
test:
  image: php:8.4
  before_script:
    - composer install --prefer-dist --no-progress
    - php bin/warm-cache.php tests/Utils/SimpleDtos tests/Utils/Dtos
  script:
    - vendor/bin/pest
```

**Benefits:**
- ✅ Faster test execution (37% improvement)
- ✅ Consistent cache state
- ✅ No cold start penalty

### Production Deployment

**Deployment Script:**

```bash
#!/bin/bash
# deploy.sh

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Warm cache for production DTOs
echo "🔥 Warming cache..."
php bin/warm-cache.php src/Dtos app/Dtos

# Clear OPcache
echo "🧹 Clearing OPcache..."
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset --fcgi=/var/run/php/php8.4-fpm.sock
fi

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.4-fpm

echo "✅ Deployment complete!"
```

**Docker Deployment:**

```dockerfile
# Dockerfile
FROM php:8.4-fpm

# ... (other setup)

# Copy application
COPY . /var/www/html

# Install dependencies and warm cache
RUN composer install --no-dev --optimize-autoloader && \
    php bin/warm-cache.php src/Dtos app/Dtos

# ... (rest of Dockerfile)
```

**Kubernetes Init Container:**

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: app
spec:
  template:
    spec:
      initContainers:
        - name: cache-warmup
          image: your-app:latest
          command:
            - php
            - bin/warm-cache.php
            - src/Dtos
            - app/Dtos
      containers:
        - name: app
          image: your-app:latest
```

**Benefits:**
- ✅ No cold start on first request
- ✅ Consistent performance from start
- ✅ Cache shared between workers
- ✅ 20-30% faster first request

## Troubleshooting

### Cache Not Working

Check if cache directory is writable:

```bash
ls -la .event4u/data-helpers/cache/
```

### Cache Not Invalidating

Try using `HASH` or `BOTH` invalidation strategy:

```php
<?php
// config/data-helpers.php

use event4u\DataHelpers\Enums\CacheInvalidation;

return [
    'cache' => [
        'invalidation' => CacheInvalidation::BOTH,
    ],
];
```

### Performance Not Improving

Verify cache is being used:

```bash
# Warm cache
php bin/warm-cache.php -v src/Dtos

# Check cache files
ls -lh .event4u/data-helpers/cache/
```

## Related

- [Performance Optimization](/performance/optimization)
- [Running Benchmarks](/performance/running-benchmarks)
- [Configuration](/getting-started/configuration)

