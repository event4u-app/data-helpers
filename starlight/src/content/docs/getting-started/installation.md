---
title: Installation
description: Install Data Helpers in your PHP project
---

Data Helpers can be installed via Composer and works with Laravel, Symfony, Doctrine, or plain PHP.

## Requirements

- PHP 8.2 or higher
- Composer

## Basic Installation

Install Data Helpers via Composer:

```bash
composer require event4u/data-helpers
```

That's it! Data Helpers is now ready to use in your project.

## Framework Integration

Data Helpers automatically detects which frameworks are available in your project and enables the appropriate integrations.

### Laravel

If you're using Laravel 9+, Data Helpers will automatically register its service provider and enable:

- Laravel Collections support
- Eloquent Model support
- Artisan commands
- Validation integration
- Authorization integration

No additional configuration is required.

### Symfony

If you're using Symfony 6+, Data Helpers will automatically enable:

- Symfony Collections support
- Console commands
- Validation integration
- Security integration

No additional configuration is required.

### Doctrine

If you're using Doctrine 2+, Data Helpers will automatically enable:

- Doctrine Collections support
- Entity support

No additional configuration is required.

### Plain PHP

Data Helpers works out of the box with plain PHP. No framework is required.

## Optional Dependencies

Data Helpers has zero required dependencies, but you can install optional packages for additional features:

### Laravel Integration

```bash
composer require illuminate/collections
composer require illuminate/database
```

### Symfony Integration

```bash
composer require symfony/console
composer require symfony/validator
composer require symfony/security-core
```

### Doctrine Integration

```bash
composer require doctrine/collections
composer require doctrine/orm
```

## Verification

Verify the installation by running:

```bash
composer show event4u/data-helpers
```

You should see the package information including version and dependencies.

## Next Steps

- [Requirements](/getting-started/requirements) - Detailed requirements and compatibility
- [Quick Start](/getting-started/quick-start) - Get started in 5 minutes
- [Configuration](/getting-started/configuration) - Configure Data Helpers for your project

