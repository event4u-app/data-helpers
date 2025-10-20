# Symfony Validator - Optional Dependency

## Overview

The `symfony/validator` package is an **optional dependency** for this library. It is only required if you want to use Symfony-specific validation features.

## When is Symfony Validator Required?

Symfony Validator is **only required** if you:

1. **Call `constraint()` method** on validation attributes
2. **Use Symfony Validator integration** in your application
3. **Run Symfony validation integration tests**

## When is Symfony Validator NOT Required?

You can use this library **without** Symfony Validator if you:

1. **Only use Laravel validation** - All attributes implement `ValidationRule` interface
2. **Only use Plain PHP** - No framework-specific features
3. **Only use Doctrine** - Doctrine integration doesn't require Symfony Validator

## Installation

### For Symfony Projects

```bash
composer require symfony/validator
```

### For Laravel Projects

Symfony Validator is **not required**. All validation attributes work with Laravel's validation system out of the box.

### For Plain PHP Projects

Symfony Validator is **not required** unless you specifically want to use Symfony's validation features.

## How It Works

### Conditional Loading

All validation attributes that implement `SymfonyConstraint` interface use the `RequiresSymfonyValidator` trait:

```php
use event4u\DataHelpers\SimpleDTO\Concerns\RequiresSymfonyValidator;

class Email implements ValidationRule, SymfonyConstraint
{
    use RequiresSymfonyValidator;

    public function constraint(): Constraint
    {
        $this->ensureSymfonyValidatorAvailable();
        
        return new Assert\Email(
            message: $this->message
        );
    }
}
```

### Runtime Check

When you call `constraint()` method, the library checks if Symfony Validator is installed:

```php
$email = new Email();

// This will throw RuntimeException if Symfony Validator is not installed
$constraint = $email->constraint();
```

**Exception Message:**
```
Symfony Validator is not installed. Install it with: composer require symfony/validator
```

### Test Skipping

The Symfony validation integration tests are automatically skipped if Symfony Validator is not installed:

```php
// tests/Unit/SymfonyValidationIntegrationTest.php
if (!class_exists('Symfony\Component\Validator\Constraint')) {
    test('Symfony Validator not installed', function () {
        expect(true)->toBeTrue();
    })->skip('Symfony Validator is not installed. Install with: composer require symfony/validator');
    return;
}
```

## CI/CD Configuration

In CI/CD pipelines, you can test without Symfony Validator:

```yaml
# Remove Symfony Validator for Plain PHP tests
composer remove --dev symfony/validator --no-update
composer install --prefer-dist --no-interaction

# Run tests - Symfony validation tests will be skipped
vendor/bin/pest
```

## Examples

### Example 1: Laravel Project (No Symfony Validator)

```php
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

// Works without Symfony Validator!
$dto = UserDTO::from(['email' => 'test@example.com']);
```

### Example 2: Symfony Project (With Symfony Validator)

```php
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use Symfony\Component\Validator\Validation;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Email]
        public readonly string $email,
    ) {}
}

// Get Symfony constraints
$email = new Email();
$constraint = $email->constraint(); // Returns Assert\Email

// Use with Symfony Validator
$validator = Validation::createValidator();
$violations = $validator->validate('invalid-email', $constraint);
```

### Example 3: Plain PHP (No Symfony Validator)

```php
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Email]
        public readonly string $email,
    ) {}
}

// Works without Symfony Validator!
// Validation attributes are just metadata
$dto = new UserDTO(email: 'test@example.com');
```

## Benefits

### 1. **Smaller Dependencies**

Projects that don't use Symfony don't need to install `symfony/validator` and its dependencies.

### 2. **Faster Installation**

Fewer packages to download and install in CI/CD pipelines.

### 3. **Framework Agnostic**

The library works with Laravel, Symfony, Doctrine, and Plain PHP without forcing unnecessary dependencies.

### 4. **Clear Error Messages**

If you try to use Symfony-specific features without installing Symfony Validator, you get a clear error message with installation instructions.

## Migration Guide

If you're upgrading from a version where Symfony Validator was required:

### Before (Required)

```bash
# Symfony Validator was always installed
composer install
```

### After (Optional)

```bash
# For Laravel projects - no change needed
composer install

# For Symfony projects - explicitly install if needed
composer require symfony/validator
```

## Troubleshooting

### Error: "Symfony Validator is not installed"

**Solution:** Install Symfony Validator:

```bash
composer require symfony/validator
```

### Tests are skipped

If you see "Symfony Validator not installed" in test output, it means:

1. Symfony Validator is not installed (expected for non-Symfony projects)
2. Symfony validation integration tests are automatically skipped
3. All other tests still run normally

This is **expected behavior** and not an error!

## See Also

- [Symfony Validation Integration](symfony-validation-integration.md)
- [Laravel Validation Integration](laravel-validation-integration.md)
- [Contributing Guide](contributing.md)

