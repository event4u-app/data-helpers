---
title: Duplicate DTO Detection
description: Automatic detection of duplicate DTO class names in tests
---

The Duplicate DTO Checker automatically scans test files for duplicate DTO class names before running tests, preventing cryptic PHP errors with clear, actionable error messages.

## Why This Matters

When multiple test files define DTOs with the same class name, PHP throws a fatal error:

```
Fatal error: Cannot declare class UserDto, because the name is already in use
```

This error:
- **Stops all tests** from running
- **Doesn't show** which files have the conflict
- **Wastes time** debugging

The Duplicate DTO Checker solves this by detecting conflicts **before** tests run and showing **exactly** which files need to be fixed.

## How It Works

The checker runs automatically when you execute tests:

1. **Scans** all PHP files in the `tests/` directory
2. **Finds** all classes extending `SimpleDto` or `LiteDto`
3. **Checks** for duplicate class names (considering namespaces)
4. **Reports** any duplicates with file locations
5. **Exits** with an error if duplicates are found

## Example Output

When duplicates are detected, you'll see:

```
⚠️  DUPLICATE DTO CLASSES FOUND!

The following DTO classes are defined in multiple test files:

📦 UserDto:
   - tests/Unit/SimpleDto/SimpleDtoTest.php
   - tests/Integration/Symfony/DtoValueResolverTest.php
   - tests/Integration/Laravel/DtoValueResolverTest.php

📦 AddressDto:
   - tests/Unit/SimpleDto/DotNotationAccessTest.php
   - tests/Unit/LiteDto/DotNotationAccessTest.php

⚠️  This can cause test failures with unclear error messages.
Please rename the DTOs to make them unique (e.g., SimpleDtoUserDto, SymfonyUserDto, etc.)

To disable this check, set the environment variable: SKIP_DUPLICATE_DTO_CHECK=1
```

## Configuration

### Disabling the Check

Temporarily disable the check during development:

```bash
SKIP_DUPLICATE_DTO_CHECK=1 vendor/bin/pest
```

Or in your shell:

```bash
export SKIP_DUPLICATE_DTO_CHECK=1
vendor/bin/pest
```

### Excluded Directories

The checker automatically excludes:

- **Fixtures directories**: DTOs in `tests/*/Fixtures/` can have duplicate names
- **Helper files**: `DtoTestHelper.php` and `DuplicateDtoChecker.php` are excluded
- **Test files**: `DuplicateDtoCheckerTest.php` is excluded

## Namespace Handling

The checker considers namespaces when detecting duplicates:

### ✅ No Conflict (Different Namespaces)

```php
// tests/Unit/SimpleDto/SimpleDtoTest.php
namespace Tests\Unit\SimpleDto;
class UserDto extends SimpleDto { ... }

// tests/Integration/Symfony/DtoValueResolverTest.php
namespace Tests\Integration\Symfony;
class UserDto extends SimpleDto { ... }
```

These are **different classes** because they have different namespaces.

### ❌ Conflict (Same Namespace)

```php
// tests/Unit/SimpleDto/SimpleDtoTest.php
namespace Tests\Unit;
class UserDto extends SimpleDto { ... }

// tests/Unit/LiteDto/LiteDtoTest.php
namespace Tests\Unit;
class UserDto extends SimpleDto { ... }
```

These are **duplicate classes** because they have the same namespace.

### ❌ Conflict (No Namespace)

```php
// tests/Unit/SimpleDto/SimpleDtoTest.php
class UserDto extends SimpleDto { ... }

// tests/Integration/Symfony/DtoValueResolverTest.php
class UserDto extends SimpleDto { ... }
```

These are **duplicate classes** because neither has a namespace.

## Fixing Duplicates

### Option 1: Use Descriptive Prefixes

Add context-specific prefixes to make names unique:

```php
// tests/Unit/SimpleDto/SimpleDtoTest.php
class SimpleDtoUserDto extends SimpleDto { ... }

// tests/Integration/Symfony/DtoValueResolverTest.php
class SymfonyUserDto extends SimpleDto { ... }

// tests/Integration/Laravel/DtoValueResolverTest.php
class LaravelUserDto extends SimpleDto { ... }
```

### Option 2: Use Namespaces

Put DTOs in different namespaces:

```php
// tests/Unit/SimpleDto/SimpleDtoTest.php
namespace Tests\Unit\SimpleDto;
class UserDto extends SimpleDto { ... }

// tests/Integration/Symfony/DtoValueResolverTest.php
namespace Tests\Integration\Symfony;
class UserDto extends SimpleDto { ... }
```

### Option 3: Use Fixtures

If a DTO is used across multiple tests, move it to a Fixtures directory:

```php
// tests/Unit/Fixtures/UserDto.php
namespace Tests\Unit\Fixtures;
class UserDto extends SimpleDto { ... }

// tests/Unit/SimpleDto/SimpleDtoTest.php
use Tests\Unit\Fixtures\UserDto;
test('creates user dto', function() {
    $dto = new UserDto(...);
});

// tests/Unit/LiteDto/LiteDtoTest.php
use Tests\Unit\Fixtures\UserDto;
test('converts to lite dto', function() {
    $dto = new UserDto(...);
});
```

## Best Practices

### 1. Use Descriptive Names

Make DTO names specific to their test context:

```php
// ✅ Good - Clear context
class SimpleDtoValidationUserDto extends SimpleDto { ... }
class SymfonyControllerUserDto extends SimpleDto { ... }
class LaravelRequestUserDto extends SimpleDto { ... }

// ❌ Bad - Generic names
class UserDto extends SimpleDto { ... }
class TestDto extends SimpleDto { ... }
```

### 2. Use Namespaces for Organization

Group related DTOs in namespaces:

```php
// tests/Unit/SimpleDto/Validation/ValidationTest.php
namespace Tests\Unit\SimpleDto\Validation;
class UserDto extends SimpleDto { ... }

// tests/Unit/SimpleDto/Casting/CastingTest.php
namespace Tests\Unit\SimpleDto\Casting;
class UserDto extends SimpleDto { ... }
```

### 3. Share Common DTOs via Fixtures

Avoid duplication by using Fixtures:

```php
// tests/Fixtures/Dtos/UserDto.php
namespace Tests\Fixtures\Dtos;
class UserDto extends SimpleDto { ... }

// Use in multiple tests
use Tests\Fixtures\Dtos\UserDto;
```

## Manual Usage

You can also run the checker manually in your code:

```php skip-test
use Tests\Unit\Helpers\DuplicateDtoChecker;

// Check and throw exception on duplicates
DuplicateDtoChecker::check(__DIR__ . '/tests');

// Check and only print warning (don't throw)
$duplicates = DuplicateDtoChecker::check(__DIR__ . '/tests', false);

if (!empty($duplicates)) {
    foreach ($duplicates as $className => $files) {
        echo "Duplicate: $className\n";
        foreach ($files as $file) {
            echo "  - $file\n";
        }
    }
}
```

## Implementation Details

The checker uses:

- **RecursiveDirectoryIterator**: Scans all PHP files recursively
- **Regular expressions**: Finds class definitions and namespaces
- **Static analysis**: No classes are loaded or instantiated

This makes it **fast** and **safe** to run before tests.

### Performance

The checker is optimized for speed:

- **Scans ~100 test files** in less than 50ms
- **No impact** on test execution time
- **Runs once** at startup, not per test

## See Also

- [Testing DTOs](/testing/testing-dtos/) - Complete guide to testing DTOs
- [Creating DTOs](/simple-dto/creating-dtos/) - DTO creation guide
- [Best Practices](/simple-dto/best-practices/) - DTO best practices

