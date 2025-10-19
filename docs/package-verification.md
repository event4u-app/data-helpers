# Package Verification

## Overview

The test-isolated.sh script includes automatic package verification to ensure that only the correct framework packages are installed for each test variant. This prevents version conflicts and ensures test isolation.

## How It Works

After installing dependencies, the script automatically verifies that:

1. **Plain PHP**: No framework packages (Laravel, Symfony, Doctrine) are installed
2. **Laravel Tests**: Only Laravel packages of the correct version are installed, no Symfony or Doctrine
3. **Symfony Tests**: Only Symfony packages of the correct version are installed, no Laravel or Doctrine
4. **Doctrine Tests**: Only Doctrine packages of the correct version are installed, no Laravel or Symfony

## Verification Rules

### Plain PHP (`--plain`)

**Forbidden packages:**
- All `illuminate/*` packages
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`
- `doctrine/orm`, `doctrine/dbal`

### Laravel Tests (`--laravel VERSION`)

**Allowed packages:**
- `illuminate/support`, `illuminate/validation`, `illuminate/database` (matching version)
- Other `illuminate/*` packages as dependencies (matching version)

**Forbidden packages:**
- `illuminate/*` packages with wrong version
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`
- `doctrine/orm`, `doctrine/dbal`

**Note:** Some Symfony components (like `symfony/console`, `symfony/process`) are allowed as they are dependencies of Laravel.

### Symfony Tests (`--symfony VERSION`)

**Allowed packages:**
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation` (matching version)
- Other `symfony/*` packages as dependencies (matching version)

**Forbidden packages:**
- Symfony framework packages with wrong version
- All `illuminate/*` packages
- `doctrine/orm`, `doctrine/dbal`

### Doctrine Tests (`--doctrine VERSION`)

**Allowed packages:**
- `doctrine/orm`, `doctrine/dbal` (matching version)
- Other `doctrine/*` packages as dependencies (matching version)

**Forbidden packages:**
- Doctrine packages with wrong version
- All `illuminate/*` packages
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`

**Note:** Some Symfony components are allowed as they are dependencies of Doctrine.

## Example Output

### Successful Verification

```bash
→  Verifying installed packages...
✓  Package verification passed
```

### Failed Verification

```bash
→  Verifying installed packages...
✗  Found forbidden Laravel packages in Symfony setup:
   ✗  illuminate/support
   ✗  illuminate/validation

✗  Package verification failed!
```

## Testing Package Verification

You can test the package verification with the provided test script:

```bash
./scripts/test-package-verification.sh
```

This script runs all four test variants (Plain PHP, Laravel, Symfony, Doctrine) with `--no-tests` flag to only verify package installation without running the actual tests.

## Manual Verification

To manually verify packages in a specific setup:

```bash
# Run with --no-tests to skip test execution
./scripts/test-isolated.sh --laravel 9 --php 8.2 --no-tests

# Check the output for "Package verification passed"
```

## Implementation Details

The verification function (`verify_installed_packages`) in `scripts/test-isolated.sh`:

1. Gets list of installed packages using `composer show --format=json`
2. Checks for forbidden packages based on test type
3. For framework tests, verifies that installed framework packages match the expected version
4. Exits with error code 1 if any forbidden packages are found
5. Prints detailed error messages showing which packages are forbidden

## Benefits

- **Prevents version conflicts**: Ensures only one framework version is installed at a time
- **Test isolation**: Guarantees that tests run in the correct environment
- **Early detection**: Catches dependency issues before running tests
- **Clear error messages**: Shows exactly which packages are problematic

