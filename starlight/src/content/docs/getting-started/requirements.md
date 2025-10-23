---
title: Requirements
description: System requirements and compatibility information for Data Helpers
---

Data Helpers is designed to work with modern PHP applications and supports multiple frameworks.

## System Requirements

### PHP Version

- **Minimum:** PHP 8.2
- **Recommended:** PHP 8.3 or 8.4
- **Tested:** PHP 8.2, 8.3, 8.4

### PHP Extensions

Required extensions (usually included in standard PHP installations):

- `json` - For JSON support
- `mbstring` - For string operations
- `xml` - For XML support (optional)

### Composer

- Composer 2.0 or higher

## Framework Compatibility

Data Helpers is framework-agnostic and works with or without frameworks.

### Laravel

| Laravel Version | Supported | Notes |
|----------------|-----------|-------|
| Laravel 11.x   | ✅ Yes    | Fully tested |
| Laravel 10.x   | ✅ Yes    | Fully tested |
| Laravel 9.x    | ✅ Yes    | Fully tested |
| Laravel 8.x    | ❌ No     | Use Laravel 9+ |

### Symfony

| Symfony Version | Supported | Notes |
|----------------|-----------|-------|
| Symfony 7.x    | ✅ Yes    | Fully tested |
| Symfony 6.x    | ✅ Yes    | Fully tested |
| Symfony 5.x    | ❌ No     | Use Symfony 6+ |

### Doctrine

| Doctrine Version | Supported | Notes |
|-----------------|-----------|-------|
| Doctrine ORM 3.x | ✅ Yes   | Fully tested |
| Doctrine ORM 2.x | ✅ Yes   | Fully tested |
| Doctrine ORM 1.x | ❌ No    | Use Doctrine 2+ |

### Plain PHP

Data Helpers works with plain PHP 8.2+ without any framework.

## Optional Dependencies

All dependencies are optional. Data Helpers will automatically detect and use them if available.

### Laravel Collections

```bash
composer require illuminate/collections
```

Enables Laravel Collection support in DataAccessor and DataMutator.

### Laravel Database

```bash
composer require illuminate/database
```

Enables Eloquent Model support.

### Symfony Console

```bash
composer require symfony/console
```

Enables Symfony console commands.

### Symfony Validator

```bash
composer require symfony/validator
```

Enables Symfony validation integration.

### Doctrine Collections

```bash
composer require doctrine/collections
```

Enables Doctrine Collection support.

### Doctrine ORM

```bash
composer require doctrine/orm
```

Enables Doctrine Entity support.

## Development Requirements

For development and testing:

- PHPUnit 10+
- PHPStan 1.10+
- PHP CS Fixer 3+
- Pest 2+

## Test Matrix

Data Helpers is tested against multiple PHP versions and framework combinations:

- PHP 8.2, 8.3, 8.4
- Laravel 10, 11
- Symfony 6, 7
- Doctrine 2, 3

See [Test Matrix](/testing/test-matrix) for detailed test results.

## Browser Requirements

For TypeScript generation feature:

- Modern browser with ES6+ support
- Node.js 18+ (for development)

## Next Steps

- [Installation](/getting-started/installation) - Install Data Helpers
- [Quick Start](/getting-started/quick-start) - Get started in 5 minutes
- [Configuration](/getting-started/configuration) - Configure for your environment

