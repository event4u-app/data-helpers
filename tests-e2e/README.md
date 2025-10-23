# E2E Tests for Data Helpers

This directory contains End-to-End (E2E) tests for the Data Helpers package with real framework integrations.

## Overview

The E2E tests verify that the Data Helpers package works correctly when integrated with actual framework installations:

- **Laravel E2E Tests** - Tests with real Laravel framework
- **Symfony E2E Tests** - Tests with real Symfony framework

## Why E2E Tests?

While unit and integration tests are fast and cover most functionality, E2E tests provide:

1. **Real Framework Integration** - Tests run against actual framework installations
2. **Version Compatibility** - Verifies compatibility with different framework versions
3. **Service Provider/Bundle Registration** - Tests auto-discovery and configuration
4. **Real-World Scenarios** - Simulates how users will actually use the package

## Structure

```
tests-e2e/
├── Laravel/
│   ├── composer.json              # Laravel project dependencies
│   ├── bootstrap.php              # Minimal Laravel app setup
│   ├── Pest.php                   # Pest configuration
│   ├── app/
│   │   └── Models/                # Test models
│   └── tests/
│       └── Feature/               # E2E tests
├── Symfony/
│   ├── composer.json              # Symfony project dependencies
│   ├── bootstrap.php              # Minimal Symfony kernel
│   ├── Pest.php                   # Pest configuration
│   ├── src/
│   │   └── Model/                 # Test models
│   └── tests/
│       └── Feature/               # E2E tests
├── run-e2e-tests.sh              # Script to run all E2E tests
└── README.md                      # This file
```

## Running E2E Tests

### Run All E2E Tests

```bash
task test:e2e
```

This will:
1. Install dependencies for each framework (if needed)
2. Run Laravel E2E tests
3. Run Symfony E2E tests
4. Report results

### Run Individual Framework Tests

**Laravel only:**
```bash
task test:e2e:laravel
```

**Symfony only:**
```bash
task test:e2e:symfony
```

### Manual Execution

You can also run tests manually:

```bash
# Laravel
cd tests-e2e/Laravel
composer install
vendor/bin/pest

# Symfony
cd tests-e2e/Symfony
composer install
vendor/bin/pest
```

## Framework Versions

The E2E tests use framework versions defined in the main `composer.json` under `extra`:

- **Laravel:** `^9.0|^10.0|^11.0`
- **Symfony:** `^6.0|^7.0`

Each E2E project's `composer.json` references the Data Helpers package via a local path repository, ensuring tests run against the current development version.

## What's Tested

### Laravel E2E Tests

1. **Service Provider Registration**
   - Auto-discovery via package discovery
   - Configuration loading and merging
   - DataHelpersConfig initialization

2. **MappedDataModel Integration**
   - Automatic dependency injection
   - Request data auto-filling
   - Validation integration

3. **Configuration Publishing**
   - Config file availability
   - Publishable assets

### Symfony E2E Tests

1. **Bundle Registration**
   - Auto-registration via Symfony Flex
   - Extension loading
   - Service registration

2. **MappedDataModel Integration**
   - Value resolver registration
   - Automatic argument resolution
   - Request data mapping

## Adding New Tests

To add new E2E tests:

1. **Choose the framework directory** (`Laravel/` or `Symfony/`)
2. **Add test file** in `tests/Feature/`
3. **Follow existing patterns** for consistency
4. **Run tests** to verify

Example test structure:

```php
<?php

declare(strict_types=1);

describe('My New Feature E2E', function(): void {
    it('does something', function(): void {
        // Test code here
        expect(true)->toBeTrue();
    });
});
```

## CI/CD Integration

The E2E tests can be integrated into CI/CD pipelines:

```yaml
# GitHub Actions example
- name: Run E2E Tests
  run: task test:e2e
```

**Note:** E2E tests are slower than unit tests, so consider:
- Running them on a schedule (nightly builds)
- Running them only on main branch or PRs
- Caching framework dependencies

## Troubleshooting

### Dependencies Not Installing

```bash
cd tests-e2e/Laravel  # or Symfony
rm -rf vendor composer.lock
composer install
```

### Tests Failing

1. **Check framework version compatibility**
2. **Verify Data Helpers package changes**
3. **Review test output for specific errors**
4. **Run tests individually** to isolate issues

## Performance

E2E tests are slower than unit tests:

- **Unit Tests:** ~0.7s for 995 tests
- **E2E Tests:** ~5-10s per framework (includes dependency installation)

This is expected and acceptable for E2E testing.

## Maintenance

When updating framework support:

1. Update version constraints in main `composer.json` (`extra` section)
2. Update E2E project `composer.json` files if needed
3. Run E2E tests to verify compatibility
4. Update this README if necessary

