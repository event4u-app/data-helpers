# Test Matrix Documentation

## Overview

The Data Helpers library uses a comprehensive test matrix to ensure compatibility across:
- **3 PHP versions** (8.2, 8.3, 8.4)
- **Plain PHP** (no frameworks)
- **3 frameworks** with multiple versions each:
  - Laravel (9, 10, 11)
  - Symfony (6, 7)
  - Doctrine (2, 3)

## Test Strategy

### Isolated Framework Testing

**Key Concept:** Each framework is tested in **isolation** - only one framework is installed at a time.

**Why?**
- Ensures the library works correctly with each framework independently
- Prevents dependency conflicts between frameworks
- Mimics real-world usage where projects typically use one framework
- Catches framework-specific issues that might be hidden when all frameworks are installed

### Test Types

1. **Unit Tests** - Fast, isolated tests of individual components
2. **Matrix Tests** - Comprehensive tests across all PHP versions and frameworks (isolated)
3. **E2E Tests** - Integration tests with all frameworks installed together

## Test Matrix Structure

### Complete Matrix (33 test combinations)

```
Plain PHP:
├── PHP 8.2 - Plain
├── PHP 8.3 - Plain
└── PHP 8.4 - Plain

Laravel (isolated):
├── PHP 8.2 - Laravel 9
├── PHP 8.2 - Laravel 10
├── PHP 8.2 - Laravel 11
├── PHP 8.3 - Laravel 10
├── PHP 8.3 - Laravel 11
└── PHP 8.4 - Laravel 11

Symfony (isolated):
├── PHP 8.2 - Symfony 6
├── PHP 8.2 - Symfony 7
├── PHP 8.3 - Symfony 6
├── PHP 8.3 - Symfony 7
├── PHP 8.4 - Symfony 6
└── PHP 8.4 - Symfony 7

Doctrine (isolated):
├── PHP 8.2 - Doctrine 2
├── PHP 8.2 - Doctrine 3
├── PHP 8.3 - Doctrine 2
├── PHP 8.3 - Doctrine 3
├── PHP 8.4 - Doctrine 2
└── PHP 8.4 - Doctrine 3
```

## Running Tests

### Complete Test Suite

```bash
# Run EVERYTHING (matrix + e2e)
task test:run
```

This runs:
1. Complete test matrix (33 isolated tests)
2. E2E tests (all frameworks combined)

### Test Matrix Only

```bash
# Complete matrix (all 33 tests)
task test:matrix
```

### By PHP Version

```bash
task test:matrix:82        # All PHP 8.2 tests (11 tests)
task test:matrix:83        # All PHP 8.3 tests (11 tests)
task test:matrix:84        # All PHP 8.4 tests (11 tests)
```

### By Framework

```bash
task test:matrix:plain     # Plain PHP only (3 tests)
task test:matrix:laravel   # All Laravel tests (6 tests)
task test:matrix:symfony   # All Symfony tests (6 tests)
task test:matrix:doctrine  # All Doctrine tests (6 tests)
```

### By Framework Version

```bash
# Laravel
task test:matrix:laravel9  # Laravel 9 on all compatible PHP versions (3 tests)
task test:matrix:laravel10 # Laravel 10 on all compatible PHP versions (2 tests)
task test:matrix:laravel11 # Laravel 11 on all compatible PHP versions (3 tests)

# Symfony
task test:matrix:symfony6  # Symfony 6 on all PHP versions (3 tests)
task test:matrix:symfony7  # Symfony 7 on all PHP versions (3 tests)

# Doctrine
task test:matrix:doctrine2 # Doctrine 2 on all PHP versions (3 tests)
task test:matrix:doctrine3 # Doctrine 3 on all PHP versions (3 tests)
```

### Individual Tests

```bash
# Quick access to specific framework tests
task test:laravel9         # Laravel 9 only (PHP 8.2)
task test:laravel10        # Laravel 10 only (PHP 8.3)
task test:laravel11        # Laravel 11 only (PHP 8.4)
task test:symfony6         # Symfony 6 only (PHP 8.4)
task test:symfony7         # Symfony 7 only (PHP 8.4)
task test:doctrine2        # Doctrine 2 only (PHP 8.4)
task test:doctrine3        # Doctrine 3 only (PHP 8.4)
task test:plain            # Plain PHP (PHP 8.4)
```

## How It Works

### Isolated Testing Process

For each test in the matrix:

1. **Backup** - Save current `composer.json` and `composer.lock`
2. **Remove** - Remove ALL framework packages
3. **Install** - Install ONLY the target framework (or none for plain PHP)
4. **Test** - Run the test suite
5. **Restore** - Restore original composer files

### Scripts

- `scripts/test-isolated.sh` - Runs isolated framework tests locally
- `docker/test-matrix.sh` - Runs complete matrix in Docker containers

### Example: Testing Laravel 11 Isolated

```bash
# What happens internally:
1. Backup composer.json and composer.lock
2. Remove illuminate/*, symfony/*, doctrine/*
3. Install ONLY illuminate/support:^11.0, illuminate/database:^11.0, illuminate/http:^11.0
4. Run composer test
5. Restore original composer files
```

## CI/CD Integration

### GitHub Actions

The test matrix is designed to work seamlessly with GitHub Actions:

```yaml
strategy:
  matrix:
    php: [8.2, 8.3, 8.4]
    framework:
      - { name: 'plain', version: '' }
      - { name: 'laravel', version: '9' }
      - { name: 'laravel', version: '10' }
      - { name: 'laravel', version: '11' }
      - { name: 'symfony', version: '6' }
      - { name: 'symfony', version: '7' }
      - { name: 'doctrine', version: '2' }
      - { name: 'doctrine', version: '3' }
```

### Running Locally (Like CI)

```bash
# Run complete test suite (like CI)
task test:run

# Or step by step
task test:matrix           # Matrix tests
task test:e2e              # E2E tests
```

## Test Output

### Matrix Test Output

```
╔════════════════════════════════════════════════════════════╗
║  Running Comprehensive Test Matrix (Isolated)             ║
╚════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Test 1: PHP 8.2 - Plain (no frameworks)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅  Test passed: PHP 8.2 - Plain

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Test 2: PHP 8.2 - laravel 9 (isolated)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅  Test passed: PHP 8.2 - laravel 9

...

╔════════════════════════════════════════════════════════════╗
║  Test Summary                                              ║
╚════════════════════════════════════════════════════════════╝

Total Tests:   33
Passed:        33
Failed:        0

╔════════════════════════════════════════════════════════════╗
║  All tests passed successfully! 🎉                        ║
╚════════════════════════════════════════════════════════════╝
```

## Best Practices

### Development Workflow

```bash
# Quick development test
task test:unit             # Fast unit tests

# Test specific framework
task test:laravel11        # Test Laravel 11 only

# Before commit
task dev:pre-commit        # Quality checks + unit tests

# Before push
task dev:pre-push          # Quality checks + matrix tests

# Full CI simulation
task test:run              # Everything
```

### Debugging Failed Tests

```bash
# Run specific PHP version
task test:matrix:82

# Run specific framework
task test:matrix:laravel

# Run specific combination
task test:laravel9

# With PHPStan
docker exec data-helpers-php82 ./scripts/test-isolated.sh --laravel 9 --phpstan
```

## Performance

- **Unit tests**: ~5 seconds
- **Single isolated test**: ~30 seconds (includes composer operations)
- **Complete matrix**: ~15-20 minutes (33 tests)
- **E2E tests**: ~2 minutes
- **Full suite**: ~20-25 minutes

## Troubleshooting

### Composer Lock Issues

If you see composer lock errors:

```bash
# Reset development environment
task dev:reset

# Or manually
rm -rf vendor composer.lock
task dev:install
```

### Docker Issues

```bash
# Rebuild containers
task docker:rebuild

# Check container status
task docker:ps

# View logs
task docker:logs
```

### Test Failures

1. Check which specific test failed in the summary
2. Run that specific test individually
3. Check the test output for error details
4. Verify framework compatibility

## Summary

The test matrix provides:
- ✅ **Comprehensive coverage** - 33 isolated tests + E2E tests
- ✅ **Real-world scenarios** - Tests frameworks in isolation
- ✅ **Flexible execution** - Run all, by group, or individually
- ✅ **CI-ready** - Designed for GitHub Actions
- ✅ **Fast feedback** - Run only what you need during development

