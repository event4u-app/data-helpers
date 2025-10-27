---
title: Common Issues
description: Common issues and solutions when using Data Helpers
---

Common issues and solutions when using Data Helpers.

## Introduction

This guide covers common issues and their solutions:

- **Installation Issues** - Composer and PHP version problems
- **Validation Errors** - Validation not working or slow
- **Type Casting Problems** - Casts not applied correctly
- **Performance Issues** - Slow Dto creation or high memory usage
- **Framework Integration** - Laravel and Symfony integration issues
- **TypeScript Generation** - TypeScript types not generated

## Installation Issues

### Composer Install Fails

**Problem:**
```bash
composer require event4u/data-helpers
# Error: Package not found
```

**Solution:**
```bash
# Make sure you have the correct package name
composer require event4u/data-helpers

# If still failing, update composer
composer self-update
composer clear-cache
composer require event4u/data-helpers
```

### PHP Version Mismatch

**Problem:**
```
Your PHP version (8.1.0) does not satisfy requirement: ^8.2
```

**Solution:**
```bash
# Upgrade to PHP 8.2 or higher
# Ubuntu/Debian
sudo apt-get install php8.2

# macOS (Homebrew)
brew install php@8.2

# Check version
php -v
```

## Validation Issues

### Validation Not Working

**Problem:**
<!-- skip-test: requires $data variable -->
```php
$dto = UserDto::fromArray($data);
// No validation happens
```

**Solution:**
<!-- skip-test: incomplete code snippet -->
```php
// Use validateAndCreate() instead
$dto = UserDto::validateAndCreate($data);

// Or enable auto-validation
#[ValidateRequest]
class UserDto extends SimpleDto { /* ... */ }
```

### Validation Rules Not Cached

**Problem:**
```bash
# Validation is slow
```

**Solution:**
```bash
# Laravel
php artisan dto:cache

# Symfony
bin/console dto:cache

# Verify cache is enabled
php artisan config:show simple-dto.validation.cache_rules
```

### Custom Validation Not Working

**Problem:**
<!-- skip-test: incomplete code snippet -->
```php
#[CustomRule]
public readonly string $field;
// Rule not applied
```

**Solution:**
<!-- skip-test: incomplete code snippet -->
```php
// Make sure attribute implements ValidationRule
use event4u\DataHelpers\SimpleDto\Attributes\ValidationRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CustomRule implements ValidationRule
{
    public function rule(): string
    {
        return 'custom_rule';
    }

    public function message(): ?string
    {
        return 'The :attribute is invalid.';
    }
}

// Register custom rule (Laravel)
Validator::extend('custom_rule', function($attribute, $value) {
    return /* validation logic */;
});
```

## Type Casting Issues

### Cast Not Applied

**Problem:**
<!-- skip-test: incomplete code snippet -->
```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $date;
// Still receives string
```

**Solution:**
<!-- skip-test: incomplete code snippet -->
```php
// Make sure Carbon is imported
use Carbon\Carbon;

// Make sure cast is registered
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

// Check if cast class exists
if (!class_exists(DateTimeCast::class)) {
    // Install carbon
    composer require nesbot/carbon
}
```

### Custom Cast Not Working

**Problem:**
<!-- skip-test: incomplete code snippet -->
```php
#[Cast(MyCast::class)]
public readonly string $field;
// Cast not applied
```

**Solution:**
```php
// Make sure cast implements Cast interface
use event4u\DataHelpers\SimpleDto\Casts\Cast;

class MyCast implements Cast
{
    public function cast(mixed $value): mixed
    {
        return /* cast logic */;
    }

    public function uncast(mixed $value): mixed
    {
        return /* uncast logic */;
    }
}
```

## Performance Issues

### Slow Dto Creation

**Problem:**
```php
// Takes 0.5 seconds to create Dto
$dto = UserDto::fromArray($data);
```

**Solution:**
```php
// 1. Enable validation caching
php artisan dto:cache

// 2. Use lazy loading
#[Lazy]
public readonly ?array $posts = null;

// 3. Avoid deep nesting
// Bad: $dto->a->b->c->d->e
// Good: $dto->a->b

// 4. Use batch operations
$dtos = DataCollection::make($users, UserDto::class);
```

### High Memory Usage

**Problem:**
```php
// Memory usage spikes when creating many Dtos
```

**Solution:**
```php
// 1. Use chunking
User::chunk(1000, function($users) {
    $dtos = DataCollection::make($users, UserDto::class);
    // Process dtos
});

// 2. Use lazy properties
#[Lazy]
public readonly ?array $largeData = null;

// 3. Clear cache periodically
Cache::flush();
```

## Framework Integration Issues

### Laravel: Auto-Validation Not Working

**Problem:**
<!-- skip-test: incomplete code snippet -->
```php
public function store(CreateUserDto $dto)
{
    // Dto not validated
}
```

**Solution:**
<!-- skip-test: incomplete code snippet -->
```php
// Add ValidateRequest attribute
#[ValidateRequest]
class CreateUserDto extends SimpleDto { /* ... */ }

// Or use validateAndCreate()
public function store(Request $request)
{
    $dto = CreateUserDto::validateAndCreate($request->all());
}
```

### Laravel: Eloquent Integration Not Working

**Problem:**
<!-- skip-test: method does not exist -->
```php
$dto = UserDto::fromModel($user);
// Error: Method not found
```

**Solution:**
<!-- skip-test: requires $user variable -->
```php
// Make sure you're using fromArray with model's toArray()
$dto = UserDto::fromArray($user->toArray());

// Or use DataMapper for complex mappings
$dto = DataMapper::source($user->toArray())
    ->target(UserDto::class)
    ->template([
        'name' => 'name',
        'email' => 'email',
    ])
    ->map()
    ->getTarget();
```

### Symfony: Doctrine Integration Not Working

**Problem:**
<!-- skip-test: method does not exist -->
```php
$dto = UserDto::fromEntity($user);
// Error: Method not found
```

**Solution:**
<!-- skip-test: requires $user variable -->
```php
// Use DataMapper for entity mapping
$dto = DataMapper::source($user)
    ->target(UserDto::class)
    ->template([
        'name' => 'name',
        'email' => 'email',
    ])
    ->map()
    ->getTarget();

// Or convert entity to array first
$dto = UserDto::fromArray([
    'name' => $user->getName(),
    'email' => $user->getEmail(),
]);
```

### Symfony: Security Integration Not Working

**Problem:**
<!-- skip-test: incomplete code snippet -->
```php
#[WhenGranted('ROLE_ADMIN')]
public readonly ?array $adminData = null;
// Always null
```

**Solution:**
```php
// Pass security context
$dto->withContext([
    'security' => $this->security,
])->toArray();
```

## TypeScript Generation Issues

### TypeScript Not Generated

**Problem:**
```bash
php artisan dto:typescript
# No files generated
```

**Solution:**
```bash
# Check output path exists
mkdir -p resources/js/types

# Check permissions
chmod 755 resources/js/types

# Specify output path
php artisan dto:typescript --output=resources/js/types

# Check for errors
php artisan dto:typescript --verbose
```

## See Also

- [Quick Start](/getting-started/quick-start/) - Get started guide
- [Validation](/simple-dto/validation/) - Validation details
- [Type Casting](/simple-dto/type-casting/) - Type casting guide
- [Performance](/performance/optimization/) - Performance optimization

