---
title: Configuration
description: Configure Data Helpers for your environment
---

Data Helpers works out of the box with zero configuration, but you can customize its behavior for your specific needs.

## Laravel Configuration

Data Helpers automatically registers its service provider in Laravel. No configuration is required.

### Publishing Configuration

You can publish the configuration file:

```bash
php artisan vendor:publish --tag=data-helpers-config
```

This creates `config/data-helpers.php`:

```php
return [
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', false),
    'cache_enabled' => env('DATA_HELPERS_CACHE_ENABLED', true),
];
```

## Symfony Configuration

Data Helpers automatically registers its bundle in Symfony. No configuration is required.

### Bundle Configuration

Create `config/packages/data_helpers.yaml`:

```yaml
data_helpers:
  performance_mode: '%env(bool:DATA_HELPERS_PERFORMANCE_MODE)%'
  cache_enabled: '%env(bool:DATA_HELPERS_CACHE_ENABLED)%'
```

## Plain PHP Configuration

For plain PHP projects, you can configure Data Helpers programmatically:

```php
use event4u\DataHelpers\DataHelpersConfig;

DataHelpersConfig::set('performance_mode', 'fast');
DataHelpersConfig::set('cache.max_entries', 1000);
```

## Configuration Options

### Performance Mode

Enable performance mode for production environments:

```php
use event4u\DataHelpers\DataHelpersConfig;

DataHelpersConfig::set('performance_mode', 'fast');
// Options: 'fast' or 'safe'
```

This disables debug features and enables optimizations.

### Cache

Configure caching:

```php
use event4u\DataHelpers\DataHelpersConfig;

DataHelpersConfig::set('cache.max_entries', 1000);
DataHelpersConfig::set('cache.default_ttl', 3600);
```

Caching improves performance for repeated operations.

## Environment Variables

You can use environment variables to configure Data Helpers:

```env
DATA_HELPERS_PERFORMANCE_MODE=true
DATA_HELPERS_CACHE_ENABLED=true
```

## Next Steps

- [Core Concepts](/core-concepts/dot-notation) - Learn the fundamentals
- [Quick Start](/getting-started/quick-start) - Get started in 5 minutes
- [Performance](/performance/benchmarks) - Optimize for production

