# Troubleshooting

Common issues and solutions when using SimpleDTO.

---

## 🎯 Overview

This guide covers common issues and their solutions:

- ✅ **Installation Issues**
- ✅ **Validation Errors**
- ✅ **Type Casting Problems**
- ✅ **Performance Issues**
- ✅ **Framework Integration**
- ✅ **TypeScript Generation**

---

## 🔧 Installation Issues

### Issue: Composer Install Fails

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

### Issue: PHP Version Mismatch

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

---

## ✅ Validation Issues

### Issue: Validation Not Working

**Problem:**
```php
$dto = UserDTO::fromArray($data);
// No validation happens
```

**Solution:**
```php
// Use validateAndCreate() instead
$dto = UserDTO::validateAndCreate($data);

// Or enable auto-validation
#[ValidateRequest]
class UserDTO extends SimpleDTO { /* ... */ }
```

### Issue: Validation Rules Not Cached

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

### Issue: Custom Validation Not Working

**Problem:**
```php
#[CustomRule]
public readonly string $field;
// Rule not applied
```

**Solution:**
```php
// Make sure attribute extends ValidationAttribute
#[Attribute(Attribute::TARGET_PROPERTY)]
class CustomRule extends ValidationAttribute
{
    public function rules(): array
    {
        return ['custom_rule'];
    }
}

// Register custom rule
Validator::extend('custom_rule', function($attribute, $value) {
    return /* validation logic */;
});
```

---

## 🎨 Type Casting Issues

### Issue: Cast Not Applied

**Problem:**
```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $date;
// Still receives string
```

**Solution:**
```php
// Make sure Carbon is imported
use Carbon\Carbon;

// Make sure cast is registered
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

// Check if cast class exists
if (!class_exists(DateTimeCast::class)) {
    // Install carbon
    composer require nesbot/carbon
}
```

### Issue: Custom Cast Not Working

**Problem:**
```php
#[Cast(MyCast::class)]
public readonly string $field;
// Cast not applied
```

**Solution:**
```php
// Make sure cast implements CastInterface
class MyCast implements CastInterface
{
    public function cast(mixed $value): mixed
    {
        return /* cast logic */;
    }
}

// Register cast
$this->app->bind(MyCast::class, function() {
    return new MyCast();
});
```

---

## 🚀 Performance Issues

### Issue: Slow DTO Creation

**Problem:**
```php
// Takes 0.5 seconds to create DTO
$dto = UserDTO::fromArray($data);
```

**Solution:**
```php
// 1. Enable validation caching
php artisan dto:cache

// 2. Use lazy loading
#[Lazy]
public readonly ?array $posts = null

// 3. Avoid deep nesting
// Bad: $dto->a->b->c->d->e
// Good: $dto->a->b

// 4. Use batch operations
$dtos = DataCollection::make($users, UserDTO::class);
```

### Issue: High Memory Usage

**Problem:**
```php
// Memory usage spikes when creating many DTOs
```

**Solution:**
```php
// 1. Use chunking
User::chunk(1000, function($users) {
    $dtos = DataCollection::make($users, UserDTO::class);
    // Process dtos
});

// 2. Use lazy properties
#[Lazy]
public readonly ?array $largeData = null

// 3. Clear cache periodically
Cache::flush();
```

---

## 🔄 Framework Integration Issues

### Laravel Issues

#### Issue: Auto-Validation Not Working

**Problem:**
```php
public function store(CreateUserDTO $dto)
{
    // DTO not validated
}
```

**Solution:**
```php
// Add ValidateRequest attribute
#[ValidateRequest]
class CreateUserDTO extends SimpleDTO { /* ... */ }

// Or use validateAndCreate()
public function store(Request $request)
{
    $dto = CreateUserDTO::validateAndCreate($request->all());
}
```

#### Issue: Eloquent Integration Not Working

**Problem:**
```php
$dto = UserDTO::fromModel($user);
// Error: Method not found
```

**Solution:**
```php
// Make sure EloquentTrait is used
use event4u\DataHelpers\SimpleDTO\Traits\EloquentTrait;

class UserDTO extends SimpleDTO
{
    use EloquentTrait;
    
    // ...
}
```

### Symfony Issues

#### Issue: Doctrine Integration Not Working

**Problem:**
```php
$dto = UserDTO::fromEntity($user);
// Error: Method not found
```

**Solution:**
```php
// Make sure DoctrineTrait is used
use event4u\DataHelpers\SimpleDTO\Traits\DoctrineTrait;

class UserDTO extends SimpleDTO
{
    use DoctrineTrait;
    
    // ...
}
```

#### Issue: Security Integration Not Working

**Problem:**
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

---

## 📝 TypeScript Generation Issues

### Issue: TypeScript Not Generated

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

### Issue: Wrong TypeScript Types

**Problem:**
```typescript
// Generated
export interface UserDTO {
  age: any; // Should be number
}
```

**Solution:**
```php
// Add type hint
public readonly int $age; // Not just 'readonly $age'

// Add PHPDoc for arrays
/** @var int[] */
public readonly array $ages;

// Regenerate
php artisan dto:typescript
```

---

## 🎯 Common Errors

### Error: "Property must be readonly"

**Problem:**
```php
public string $name; // Not readonly
```

**Solution:**
```php
public readonly string $name; // Add readonly
```

### Error: "Class does not extend SimpleDTO"

**Problem:**
```php
class UserDTO
{
    // Missing extends
}
```

**Solution:**
```php
class UserDTO extends SimpleDTO
{
    // ...
}
```

### Error: "Undefined property"

**Problem:**
```php
echo $dto->nonExistentProperty;
// Error: Undefined property
```

**Solution:**
```php
// Check property exists
if (property_exists($dto, 'nonExistentProperty')) {
    echo $dto->nonExistentProperty;
}

// Or use null coalescing
echo $dto->nonExistentProperty ?? 'default';
```

### Error: "Cannot modify readonly property"

**Problem:**
```php
$dto->name = 'New Name';
// Error: Cannot modify readonly property
```

**Solution:**
```php
// DTOs are immutable, create new instance
$newDto = new UserDTO(
    name: 'New Name',
    email: $dto->email,
);

// Or use with()
$newDto = $dto->with('name', 'New Name');
```

---

## 🔍 Debugging

### Enable Debug Mode

**Laravel:**
```php
// config/simple-dto.php
return [
    'debug' => env('DTO_DEBUG', false),
];

// .env
DTO_DEBUG=true
```

**Symfony:**
```yaml
# config/packages/simple_dto.yaml
simple_dto:
  debug: '%kernel.debug%'
```

### Log DTO Operations

```php
use Illuminate\Support\Facades\Log;

class UserDTO extends SimpleDTO
{
    public static function fromArray(array $data): static
    {
        Log::debug('Creating DTO from array', ['data' => $data]);
        
        try {
            $dto = parent::fromArray($data);
            Log::debug('DTO created successfully');
            return $dto;
        } catch (\Exception $e) {
            Log::error('DTO creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

### Validate DTO Structure

```bash
# Laravel
php artisan dto:validate UserDTO

# Symfony
bin/console dto:validate UserDTO
```

---

## 💡 Best Practices to Avoid Issues

### 1. Always Use Type Hints

```php
// ✅ Good
public readonly string $name

// ❌ Bad
public readonly $name
```

### 2. Always Use Readonly

```php
// ✅ Good
public readonly string $name

// ❌ Bad
public string $name
```

### 3. Cache Validation in Production

```bash
# Always cache in production
php artisan dto:cache
```

### 4. Test DTOs Thoroughly

```php
public function test_dto_creation(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John',
    ]);
    
    $this->assertEquals(1, $dto->id);
    $this->assertEquals('John', $dto->name);
}
```

### 5. Use IDE Support

Install IDE plugins for better error detection:
- PhpStorm: Laravel Idea, Symfony Plugin
- VS Code: PHP Intelephense

---

## 📚 Getting Help

### Documentation

- [Quick Start](03-quick-start.md)
- [Validation](07-validation.md)
- [Type Casting](06-type-casting.md)
- [Best Practices](29-best-practices.md)

### Community

- GitHub Issues: Report bugs and request features
- Discussions: Ask questions and share ideas

### Support

- Check existing issues on GitHub
- Read the documentation thoroughly
- Enable debug mode for detailed errors
- Use validation command to check DTO structure

---

## 📚 Next Steps

1. [Best Practices](29-best-practices.md) - Avoid common issues
2. [Performance](27-performance.md) - Optimize performance
3. [Validation](07-validation.md) - Validation details
4. [Quick Start](03-quick-start.md) - Get started

---

**Previous:** [Comparison with Spatie](31-comparison-with-spatie.md)  
**Next:** [Attributes Reference](33-attributes-reference.md)

