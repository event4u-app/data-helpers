---
title: Migration from Spatie Laravel Data
description: Complete guide to migrating from Spatie Laravel Data to SimpleDTO
---

Complete guide to migrating from Spatie Laravel Data to SimpleDTO.

## Why Migrate?

SimpleDTO provides a smooth migration path from Spatie Laravel Data:

- **Similar API** - Familiar methods and patterns
- **More Features** - 18 conditional attributes for fine-grained control
- **Framework Independent** - Works with Laravel, Symfony, and plain PHP
- **Backward Compatible** - Easy to migrate incrementally
- **Validation Caching** - Built-in caching for better performance

## Feature Comparison

| Feature | Spatie Data | SimpleDTO |
|---------|-------------|-----------|
| Framework Support | Laravel only | Laravel, Symfony, PHP |
| TypeScript Generation | ✅ Yes | ✅ Yes |
| Lazy Properties | ✅ Yes | ✅ Yes |
| Computed Properties | ✅ Yes | ✅ Yes |
| Collections | ✅ Yes | ✅ Yes |
| Validation Caching | ❌ No | ✅ Yes |
| Conditional Attributes | 2 attributes | 18 attributes |

## Automated Migration

### Using Artisan Command (Recommended)

The easiest way to migrate is using the built-in Artisan command:

```bash
# Migrate all Spatie Data classes in app/Data
php artisan dto:migrate-spatie

# Migrate specific directory
php artisan dto:migrate-spatie --path=app/Data/Api

# Dry run (preview changes without modifying files)
php artisan dto:migrate-spatie --dry-run

# Backup files before migration
php artisan dto:migrate-spatie --backup
```

The command will:
1. Find all Spatie Data classes
2. Replace base class (`Data` → `SimpleDTO`)
3. Update namespace imports
4. Add `readonly` to properties
5. Update attribute namespaces
6. Create backup files (if `--backup` flag is used)

### Manual Migration Steps

If you prefer manual migration:

#### Step 1: Install SimpleDTO

```bash
composer require event4u/data-helpers
```

#### Step 2: Update Base Class

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
use event4u\DataHelpers\SimpleDTO\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

#### Step 3: Update Attributes

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

## API Mapping

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

## Migration Examples

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
use event4u\DataHelpers\SimpleDTO\SimpleDTO;

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
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
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

### Example 3: Collections

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

## New Features in SimpleDTO

### More Conditional Attributes

SimpleDTO has 18 conditional attributes for fine-grained control:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        // Authentication-based
        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenGuest]
        public readonly ?string $publicProfile = null,

        // Permission-based
        #[WhenCan('view-sensitive-data')]
        public readonly ?string $ssn = null,

        // Role-based
        #[WhenRole('admin')]
        public readonly ?array $adminPanel = null,

        // Value-based
        #[WhenValue('status', 'active')]
        public readonly ?string $activeFeatures = null,
    ) {}
}
```

### Validation Caching

```bash
# Cache validation rules for better performance
php artisan dto:cache
```

### Framework Independence

```php
// Works in Laravel
$dto = UserDTO::fromModel($user);

// Works in Symfony
$dto = UserDTO::fromEntity($user);

// Works in plain PHP
$dto = UserDTO::fromArray($data);
```

## Migration Checklist

### Before Migration
- [ ] Review current Spatie Data usage
- [ ] Identify custom casts and transformers
- [ ] Document conditional logic
- [ ] Backup codebase

### During Migration
- [ ] Install SimpleDTO
- [ ] Run `dto:migrate-spatie` command or migrate manually
- [ ] Update method calls if needed
- [ ] Test thoroughly

### After Migration
- [ ] Remove Spatie Data package
- [ ] Update tests
- [ ] Generate TypeScript types
- [ ] Cache validation rules

## Troubleshooting

### Issue: Properties are not readonly

**Solution:** Add `readonly` keyword to all properties:

```php
// Before
public string $name;

// After
public readonly string $name;
```

### Issue: Validation not working

**Solution:** Use `validateAndCreate()` instead of `fromArray()`:

```php
// This validates
$dto = UserDTO::validateAndCreate($data);

// This doesn't validate
$dto = UserDTO::fromArray($data);
```

### Issue: Collections not working

**Solution:** Use `DataCollection::make()` with class name:

```php
// Before (Spatie)
$users = UserData::collect($data);

// After (SimpleDTO)
$users = DataCollection::make($data, UserDTO::class);
```

## Next Steps

- [Artisan Commands](/framework-integration/artisan-commands/) - Learn about all available commands
- [Validation](/simple-dto/validation/) - Explore validation features
- [Conditional Properties](/simple-dto/conditional-properties/) - Use advanced conditional logic
- [Performance](/performance/optimization/) - Optimize your DTOs

