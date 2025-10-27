---
title: Package Verification
description: Automatic package verification for isolated framework testing
---

The test-isolated.sh script includes automatic package verification to ensure that only the correct framework packages are installed for each test variant.

## Introduction

Package verification prevents version conflicts and ensures test isolation by automatically checking that:

1. **Plain PHP** - No framework packages are installed
2. **Laravel Tests** - Only Laravel packages of the correct version
3. **Symfony Tests** - Only Symfony packages of the correct version
4. **Doctrine Tests** - Only Doctrine packages of the correct version

## How It Works

After installing dependencies, the script automatically verifies installed packages before running tests.

### Verification Process

1. Gets list of installed packages using `composer show --format=json`
2. Checks for forbidden packages based on test type
3. For framework tests, verifies that installed framework packages match the expected version
4. Exits with error code 1 if any forbidden packages are found
5. Prints detailed error messages showing which packages are problematic

## Verification Rules

### Plain PHP (`--plain`)

**Forbidden packages:**
- All `illuminate/*` packages
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`
- `doctrine/orm`, `doctrine/dbal`

**Example:**
```bash
./scripts/test-isolated.sh --plain --php 8.4
```

### Laravel Tests (`--laravel VERSION`)

**Allowed packages:**
- `illuminate/support`, `illuminate/validation`, `illuminate/database` (matching version)
- Other `illuminate/*` packages as dependencies (matching version)

**Forbidden packages:**
- `illuminate/*` packages with wrong version
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`
- `doctrine/orm`, `doctrine/dbal`

**Note:** Some Symfony components (like `symfony/console`, `symfony/process`) are allowed as they are dependencies of Laravel.

**Examples:**
```bash
./scripts/test-isolated.sh --laravel 10 --php 8.3
./scripts/test-isolated.sh --laravel 11 --php 8.4
```

### Symfony Tests (`--symfony VERSION`)

**Allowed packages:**
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation` (matching version)
- Other `symfony/*` packages as dependencies (matching version)

**Forbidden packages:**
- Symfony framework packages with wrong version
- All `illuminate/*` packages
- `doctrine/orm`, `doctrine/dbal`

**Example:**
```bash
./scripts/test-isolated.sh --symfony 7 --php 8.4
```

### Doctrine Tests (`--doctrine VERSION`)

**Allowed packages:**
- `doctrine/orm`, `doctrine/dbal` (matching version)
- Other `doctrine/*` packages as dependencies (matching version)

**Forbidden packages:**
- Doctrine packages with wrong version
- All `illuminate/*` packages
- `symfony/validator`, `symfony/http-kernel`, `symfony/http-foundation`

**Note:** Some Symfony components are allowed as they are dependencies of Doctrine.

**Example:**
```bash
./scripts/test-isolated.sh --doctrine 3 --php 8.4
```

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

### Automatic Verification

Package verification runs automatically during:

```bash
# Matrix tests
task test:matrix

# Individual framework tests
task test:laravel11
task test:symfony7
task test:doctrine3
```

### Manual Verification

To manually verify packages without running tests:

```bash
# Run with --no-tests to skip test execution
./scripts/test-isolated.sh --laravel 10 --php 8.2 --no-tests

# Check the output for "Package verification passed"
```

### Test All Variants

```bash
# Test all four variants (Plain PHP, Laravel, Symfony, Doctrine)
./scripts/test-package-verification.sh
```

## Benefits

### Prevents Version Conflicts

Ensures only one framework version is installed at a time, preventing dependency conflicts.

**Example:**
```bash
# ❌ Without verification: Laravel 11 + Symfony 7 installed together
# ✅ With verification: Only Laravel 11 installed
```

### Test Isolation

Guarantees that tests run in the correct environment, mimicking real-world usage.

**Example:**
```bash
# ❌ Without verification: Tests might pass due to other frameworks
# ✅ With verification: Tests only pass if they work with the target framework
```

### Early Detection

Catches dependency issues before running tests, saving time and providing clear feedback.

**Example:**
```bash
# ❌ Without verification: Tests fail with cryptic errors
# ✅ With verification: Clear error message about forbidden packages
```

### Clear Error Messages

Shows exactly which packages are problematic, making it easy to fix issues.

**Example:**
```bash
✗  Found forbidden Laravel packages in Symfony setup:
   ✗  illuminate/support v11.0.0
   ✗  illuminate/validation v11.0.0
```

## Implementation Details

### Verification Function

The `verify_installed_packages` function in `scripts/test-isolated.sh`:

```bash
verify_installed_packages() {
    local test_type=$1
    local version=$2

    # Get installed packages
    local packages=$(composer show --format=json)

    # Check for forbidden packages
    case $test_type in
        plain)
            check_no_frameworks
            ;;
        laravel)
            check_only_laravel "$version"
            ;;
        symfony)
            check_only_symfony "$version"
            ;;
        doctrine)
            check_only_doctrine "$version"
            ;;
    esac
}
```

### Package Detection

```bash
# Check if package is installed
if echo "$packages" | jq -e '.installed[] | select(.name == "illuminate/support")' > /dev/null; then
    echo "✗  illuminate/support found"
    exit 1
fi

# Check package version
local version=$(echo "$packages" | jq -r '.installed[] | select(.name == "illuminate/support") | .version')
if [[ ! "$version" =~ ^$expected_version ]]; then
    echo "✗  Wrong version: $version (expected: $expected_version)"
    exit 1
fi
```

## Troubleshooting

### Verification Fails

If package verification fails:

1. **Check the error message** - It shows which packages are problematic
2. **Clean composer cache:**
   ```bash
   task dev:clean
   ```
3. **Rebuild environment:**
   ```bash
   task dev:reset
   ```

### Wrong Packages Installed

If wrong packages are installed:

1. **Remove vendor and lock file:**
   ```bash
   rm -rf vendor composer.lock
   ```
2. **Reinstall dependencies:**
   ```bash
   task install
   ```

### Verification Too Strict

If verification is too strict for your use case:

1. **Check if the package is a dependency** - Some Symfony components are allowed as Laravel dependencies
2. **Update verification rules** - Edit `scripts/test-isolated.sh` if needed
3. **Report an issue** - If you think the rules are incorrect

## Summary

Package verification provides:
- ✅ **Prevents version conflicts** - Only one framework at a time
- ✅ **Test isolation** - Guarantees correct environment
- ✅ **Early detection** - Catches issues before tests
- ✅ **Clear error messages** - Shows exactly what's wrong
- ✅ **Automatic** - Runs during all matrix tests

## Next Steps

- [Test Matrix](/data-helpers/guides/test-matrix/) - Learn about the test matrix
- [Development Setup](/data-helpers/guides/development-setup/) - Setup your environment
- [Testing Guide](/data-helpers/testing/testing-dtos/) - Learn about testing Dtos

