# Symfony Recipe for event4u/data-helpers

This document describes the Symfony Flex recipe for the Data Helpers package and how to install it in Symfony projects.

## What is a Symfony Flex Recipe?

A Symfony Flex recipe is an automation script that configures packages automatically when installed via Composer. It can:
- Register bundles in `config/bundles.php`
- Copy configuration files to `config/packages/` and `config/services/`
- Set up environment variables
- Create directories and files

## What this recipe does

When installed via Symfony Flex, this recipe automatically:

1. ✅ **Registers the DataHelpersBundle** in `config/bundles.php`
2. ✅ **Copies configuration** to `config/packages/data_helpers.yaml`
3. ✅ **Copies service definitions** to `config/services/data_helpers.yaml`

## Installation

### Option 1: Automatic Installation (Symfony Flex)

**Requirements:**
- Symfony Flex must be installed in your project
- The recipe must be available in a Flex-compatible repository

```bash
composer require event4u/data-helpers
```

Symfony Flex will automatically:
- Register the bundle
- Copy configuration files
- Set up default environment variables

### Option 2: Manual Installation (Without Symfony Flex)

If Symfony Flex is not available or you prefer manual installation:

**Step 1:** Install the package

```bash
composer require event4u/data-helpers
```

**Step 2:** Copy configuration files

```bash
cp vendor/event4u/data-helpers/recipe/config/packages/data_helpers.yaml config/packages/
cp vendor/event4u/data-helpers/recipe/config/services/data_helpers.yaml config/services/
```

**Step 3:** Register the bundle in `config/bundles.php`

```php
<?php

return [
    // ... other bundles
    event4u\DataHelpers\Symfony\DataHelpersBundle::class => ['all' => true],
];
```

**Step 4:** Configure environment variables in `.env`

```env
# Cache Settings

# Performance Mode (fast|safe)
DATA_HELPERS_PERFORMANCE_MODE=fast
```

## Configuration

### Package Configuration (`config/packages/data_helpers.yaml`)

```yaml
data_helpers:
  # Cache configuration
  cache:
    # Cache driver: memory, framework, none
    driver: '%env(DATA_HELPERS_CACHE_DRIVER)%'

    # Default TTL in seconds

    symfony:
      pool: '@cache.app'

  # Performance mode: fast or safe
  performance_mode: '%env(DATA_HELPERS_PERFORMANCE_MODE)%'

# Default values
parameters:
  env(DATA_HELPERS_PERFORMANCE_MODE): 'fast'
```

### Service Configuration (`config/services/data_helpers.yaml`)

```yaml
services:
  # DataMapper service (optional, can be used directly as static class)
  event4u\DataHelpers\DataMapper:
    public: true

  # MappedModel Value Resolver for automatic dependency injection
  event4u\DataHelpers\Symfony\MappedModelResolver:
    tags:
      - { name: controller.argument_value_resolver, priority: 50 }
```

## Recipe Directory Structure

The recipe is located in the `recipe/` directory of the package:

```
recipe/
├── manifest.json                           # Recipe manifest
└── config/
    ├── packages/
    │   └── data_helpers.yaml              # Package configuration
    └── services/
        └── data_helpers.yaml              # Service definitions
```

### Recipe Manifest (`recipe/manifest.json`)

```json
{
    "bundles": {
        "event4u\\DataHelpers\\Symfony\\DataHelpersBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/packages/": "%CONFIG_DIR%/packages/",
        "config/services/": "%CONFIG_DIR%/services/"
    }
}
```

## Publishing to Symfony Recipes

To make this recipe available via Symfony Flex for public use, it needs to be submitted to:

- **Official recipes:** https://github.com/symfony/recipes (for Symfony-endorsed packages)
- **Community recipes:** https://github.com/symfony/recipes-contrib (for community packages)

**Submission process:**

1. Fork the recipes repository
2. Create a directory: `event4u/data-helpers/1.0/`
3. Copy `manifest.json` and `config/` directory
4. Submit a pull request
5. Wait for review and approval

**Note:** For private packages, manual installation is recommended.

## Troubleshooting

### Bundle not registered

If the bundle is not automatically registered, manually add it to `config/bundles.php`:

```php
event4u\DataHelpers\Symfony\DataHelpersBundle::class => ['all' => true],
```

### Configuration files not copied

Manually copy the files from `vendor/event4u/data-helpers/recipe/config/`:

```bash
cp -r vendor/event4u/data-helpers/recipe/config/packages/* config/packages/
cp -r vendor/event4u/data-helpers/recipe/config/services/* config/services/
```

### Cache not working

Check that the performance mode is configured correctly in `.env`:

```env
```

And that the Symfony cache pool exists (default: `cache.app`).

## See Also

- [Configuration Documentation](configuration.md)
- [Main README](../README.md)
- [Symfony Bundle Documentation](https://symfony.com/doc/current/bundles.html)
- [Symfony Flex Documentation](https://symfony.com/doc/current/setup/flex.html)

