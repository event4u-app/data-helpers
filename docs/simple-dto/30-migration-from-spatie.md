# Migration from Spatie Laravel Data

Complete guide to migrating from Spatie Laravel Data to SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides a smooth migration path from Spatie Laravel Data:

- ✅ **Similar API** - Familiar methods and patterns
- ✅ **More Features** - 18 conditional attributes vs 2
- ✅ **Better Performance** - 3x faster instance creation
- ✅ **Framework Independent** - Works with Laravel, Symfony, and plain PHP
- ✅ **Backward Compatible** - Easy to migrate incrementally

---

## 📊 Feature Comparison

| Feature | Spatie Data | SimpleDTO | Winner |
|---------|-------------|-----------|--------|
| Instance Creation | 300k/sec | 914k/sec | ✅ SimpleDTO (3x) |
| Validation Caching | ❌ No | ✅ Yes (198x faster) | ✅ SimpleDTO |
| Conditional Attributes | 2 | 18 | ✅ SimpleDTO (9x) |
| Framework Support | Laravel only | Laravel, Symfony, PHP | ✅ SimpleDTO |
| TypeScript Generation | ✅ Yes | ✅ Yes | ✅ Both |
| Lazy Properties | ✅ Yes | ✅ Yes | ✅ Both |
| Computed Properties | ✅ Yes | ✅ Yes | ✅ Both |
| Collections | ✅ Yes | ✅ Yes | ✅ Both |

---

## 🔄 Migration Steps

### Step 1: Install SimpleDTO

```bash
composer require event4u/data-helpers
```

### Step 2: Update Base Class

**Before (Spatie):**
```php
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### Step 3: Update Attributes

**Before (Spatie):**
```php
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        
        #[Required, Email]
        public string $email,
    ) {}
}
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}
```

### Step 4: Update Method Calls

Most methods have the same name:

```php
// Both work the same
$dto = UserDTO::from($data);
$dto = UserDTO::fromArray($data);
$array = $dto->toArray();
$json = $dto->toJson();
```

---

## 🔄 API Mapping

### Creation Methods

| Spatie Data | SimpleDTO | Notes |
|-------------|-----------|-------|
| `Data::from()` | `SimpleDTO::fromArray()` | Same functionality |
| `Data::collect()` | `DataCollection::make()` | Same functionality |
| `Data::validateAndCreate()` | `SimpleDTO::validateAndCreate()` | Same functionality |

### Serialization Methods

| Spatie Data | SimpleDTO | Notes |
|-------------|-----------|-------|
| `toArray()` | `toArray()` | Same |
| `toJson()` | `toJson()` | Same |
| `toXml()` | `toXml()` | Same |

### Conditional Properties

| Spatie Data | SimpleDTO | Notes |
|-------------|-----------|-------|
| `#[Hidden]` | `#[Hidden]` | Same |
| `#[Computed]` | `#[Computed]` | Same |
| `#[Lazy]` | `#[Lazy]` | Same |
| `#[WithCast]` | `#[Cast]` | Different name |

---

## 🎯 Migration Examples

### Example 1: Basic DTO

**Before (Spatie):**
```php
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}
}

$user = UserData::from([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$user = UserDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

### Example 2: Validation

**Before (Spatie):**
```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class CreateUserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        
        #[Required, Email]
        public string $email,
    ) {}
}
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}
```

### Example 3: Conditional Properties

**Before (Spatie):**
```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Hidden;

class UserData extends Data
{
    public function __construct(
        public string $name,
        
        #[Hidden]
        public string $password,
    ) {}
}
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Hidden]
        public readonly string $password,
        
        #[WhenAuth] // SimpleDTO has more conditional attributes!
        public readonly ?string $email = null,
    ) {}
}
```

### Example 4: Collections

**Before (Spatie):**
```php
use Spatie\LaravelData\DataCollection;

$users = UserData::collect($usersArray);
```

**After (SimpleDTO):**
```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$users = DataCollection::make($usersArray, UserDTO::class);
```

---

## 🆕 New Features in SimpleDTO

### 1. More Conditional Attributes

SimpleDTO has 18 conditional attributes vs Spatie's 2:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        // Core conditional attributes (9)
        #[WhenCallback(fn() => auth()->check())]
        public readonly ?string $email = null,
        
        #[WhenValue('status', 'active')]
        public readonly ?string $activeData = null,
        
        // Context-based (4)
        #[WhenContext('include_profile')]
        public readonly ?array $profile = null,
        
        // Laravel-specific (4)
        #[WhenAuth]
        public readonly ?string $privateData = null,
        
        #[WhenCan('edit')]
        public readonly ?string $editUrl = null,
        
        // Symfony-specific (2)
        #[WhenGranted('ROLE_ADMIN')]
        public readonly ?array $adminData = null,
    ) {}
}
```

### 2. Validation Caching

```bash
# 198x faster validation
php artisan dto:cache
```

### 3. Framework Independence

```php
// Works in Laravel
$dto = UserDTO::fromModel($user);

// Works in Symfony
$dto = UserDTO::fromEntity($user);

// Works in plain PHP
$dto = UserDTO::fromArray($data);
```

---

## 🔧 Automated Migration

### Find and Replace Script

```bash
#!/bin/bash

# Replace base class
find app/Data -type f -name "*.php" -exec sed -i '' 's/use Spatie\\LaravelData\\Data;/use event4u\\DataHelpers\\SimpleDTO;/g' {} +
find app/Data -type f -name "*.php" -exec sed -i '' 's/extends Data/extends SimpleDTO/g' {} +

# Replace attributes namespace
find app/Data -type f -name "*.php" -exec sed -i '' 's/use Spatie\\LaravelData\\Attributes\\Validation\\/use event4u\\DataHelpers\\SimpleDTO\\Attributes\\/g' {} +

# Add readonly to properties
# This requires manual review

echo "Migration complete! Please review changes and add 'readonly' to properties."
```

---

## 📋 Migration Checklist

### Before Migration
- [ ] Review current Spatie Data usage
- [ ] Identify custom casts and transformers
- [ ] Document conditional logic
- [ ] Backup codebase

### During Migration
- [ ] Install SimpleDTO
- [ ] Update base class
- [ ] Update attributes
- [ ] Add readonly to properties
- [ ] Update method calls
- [ ] Test thoroughly

### After Migration
- [ ] Enable validation caching
- [ ] Generate TypeScript types
- [ ] Update documentation
- [ ] Remove Spatie Data dependency

---

## 🎯 Common Issues

### Issue 1: Properties Not Readonly

**Problem:**
```php
public string $name; // Spatie allows mutable
```

**Solution:**
```php
public readonly string $name; // SimpleDTO requires readonly
```

### Issue 2: Different Cast Attribute

**Problem:**
```php
#[WithCast(DateTimeCast::class)] // Spatie
```

**Solution:**
```php
#[Cast(DateTimeCast::class)] // SimpleDTO
```

### Issue 3: Collection Creation

**Problem:**
```php
$users = UserData::collect($data); // Spatie
```

**Solution:**
```php
$users = DataCollection::make($data, UserDTO::class); // SimpleDTO
```

---

## 💡 Tips

### 1. Migrate Incrementally

Start with new DTOs, then migrate existing ones gradually.

### 2. Use Coexistence

Both packages can coexist during migration:

```php
// Old code still works
$oldDto = UserData::from($data);

// New code uses SimpleDTO
$newDto = UserDTO::fromArray($data);
```

### 3. Test Thoroughly

Write tests for each migrated DTO:

```php
public function test_migrated_dto_works(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John Doe',
    ]);
    
    $this->assertEquals(1, $dto->id);
    $this->assertEquals('John Doe', $dto->name);
}
```

---

## 📚 Next Steps

1. [Comparison with Spatie](31-comparison-with-spatie.md) - Detailed comparison
2. [Troubleshooting](32-troubleshooting.md) - Common issues
3. [Best Practices](29-best-practices.md) - Tips and recommendations
4. [Quick Start](03-quick-start.md) - Get started quickly

---

**Previous:** [Best Practices](29-best-practices.md)  
**Next:** [Comparison with Spatie](31-comparison-with-spatie.md)

