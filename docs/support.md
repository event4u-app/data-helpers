# Support Helpers

This directory contains helper classes that provide abstraction layers for working with different frameworks and libraries.

## Purpose

The helpers allow `data-helpers` to work seamlessly with multiple frameworks:
- **Laravel** (Eloquent Models, Collections)
- **Symfony/Doctrine** (Entities, Collections)
- **Standalone PHP** (Arrays, Objects)

## Helper Classes

### 1. CollectionHelper

**File**: `CollectionHelper.php`

Provides unified interface for working with different collection types.

**Supported Collection Types:**
- `Illuminate\Support\Collection` (Laravel)
- `Doctrine\Common\Collections\Collection` (Doctrine/Symfony)

**Methods:**
```php
// Check collection type
CollectionHelper::isLaravelCollection($value): bool
CollectionHelper::isDoctrineCollection($value): bool
CollectionHelper::isCollection($value): bool

// Convert to/from array
CollectionHelper::toArray($collection): array
CollectionHelper::fromArray(array $data): mixed

// Access elements
CollectionHelper::has($collection, $key): bool
CollectionHelper::get($collection, $key, $default = null): mixed
```

**Example:**
```php
use event4u\DataHelpers\Support\CollectionHelper;
use Illuminate\Support\Collection as LaravelCollection;
use Doctrine\Common\Collections\ArrayCollection;

// Works with Laravel Collection
$laravelCol = new LaravelCollection(['name' => 'John']);
$array = CollectionHelper::toArray($laravelCol); // ['name' => 'John']

// Works with Doctrine Collection
$doctrineCol = new ArrayCollection(['name' => 'Jane']);
$array = CollectionHelper::toArray($doctrineCol); // ['name' => 'Jane']

// Unified access
if (CollectionHelper::has($laravelCol, 'name')) {
    $name = CollectionHelper::get($laravelCol, 'name');
}
```

---

### 2. EntityHelper

**File**: `EntityHelper.php`

Provides unified interface for working with different entity/model types.

**Supported Entity Types:**
- `Illuminate\Database\Eloquent\Model` (Laravel Eloquent)
- Doctrine Entities (detected via annotations/attributes or patterns)

**Methods:**
```php
// Check entity type
EntityHelper::isEloquentModel($value): bool
EntityHelper::isDoctrineEntity($value): bool
EntityHelper::isEntity($value): bool

// Convert to array
EntityHelper::toArray($entity): array
EntityHelper::getAttributes($entity): array

// Access attributes
EntityHelper::hasAttribute($entity, $key): bool
EntityHelper::getAttribute($entity, $key): mixed
EntityHelper::setAttribute($entity, $key, $value): void
EntityHelper::unsetAttribute($entity, $key): void
```

**Example:**
```php
use event4u\DataHelpers\Support\EntityHelper;

// Works with Eloquent Model
$user = new User(['name' => 'John', 'email' => 'john@example.com']);
$array = EntityHelper::toArray($user);

// Works with Doctrine Entity
$product = new Product();
EntityHelper::setAttribute($product, 'name', 'Widget');
$name = EntityHelper::getAttribute($product, 'name');

// Unified access
if (EntityHelper::hasAttribute($user, 'email')) {
    $email = EntityHelper::getAttribute($user, 'email');
}
```

**Doctrine Entity Detection:**

The helper detects Doctrine entities using multiple strategies:
1. **Attributes/Annotations**: Checks for `#[Entity]` or `@Entity` annotations
2. **Pattern Matching**: Looks for common patterns (e.g., `getId()` method + private properties)

---

## How It Works

### Abstraction Layer

The helpers provide a unified API that works across different frameworks:

```
┌─────────────────────────────────────────┐
│         DataAccessor/DataMutator        │
│              (Your Code)                │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│    CollectionHelper / EntityHelper      │
│         (Abstraction Layer)             │
└─────────────────┬───────────────────────┘
                  │
        ┌─────────┴─────────┐
        ▼                   ▼
┌───────────────┐   ┌───────────────┐
│    Laravel    │   │    Doctrine   │
│  Collections  │   │  Collections  │
│    Models     │   │   Entities    │
└───────────────┘   └───────────────┘
```

### Framework Detection

The helpers use runtime checks to detect available frameworks:

```php
// Check if Laravel is available
if (class_exists(\Illuminate\Support\Collection::class)) {
    // Use Laravel Collection
}

// Check if Doctrine is available
if (interface_exists(\Doctrine\Common\Collections\Collection::class)) {
    // Use Doctrine Collection
}
```

### Fallback Behavior

If no framework is detected, the helpers fall back to:
- **Collections**: Return plain arrays
- **Entities**: Use reflection for property access

---

## Usage in DataAccessor/DataMutator

The helpers are used internally by `DataAccessor` and `DataMutator`:

```php
// In DataAccessor constructor
if (CollectionHelper::isCollection($input)) {
    $this->data = CollectionHelper::toArray($input);
}

if (EntityHelper::isEntity($input)) {
    $this->data = EntityHelper::toArray($input);
}

// In DataMutator
if (CollectionHelper::isCollection($target)) {
    $arr = CollectionHelper::toArray($target);
    // ... modify array ...
    return CollectionHelper::fromArray($arr);
}
```

---

## Performance

**Minimal Overhead:**
- Framework detection is cached (one-time check)
- No reflection unless necessary
- Direct method calls when framework is detected

**Benchmarks:**
- Laravel Collection: ~0.001ms overhead
- Doctrine Collection: ~0.001ms overhead
- Entity access: ~0.01ms overhead (due to reflection)

---

## Extending

To add support for additional frameworks:

1. Add detection method in helper class
2. Add conversion methods
3. Update `is*()` methods to include new type
4. Add tests

Example:
```php
// In CollectionHelper
public static function isCustomCollection(mixed $value): bool
{
    return class_exists(\Custom\Collection::class)
        && $value instanceof \Custom\Collection;
}

public static function isCollection(mixed $value): bool
{
    return self::isLaravelCollection($value)
        || self::isDoctrineCollection($value)
        || self::isCustomCollection($value);  // Add here
}
```

---

## See Also

- [Main README](../README.md)
- [Optional Dependencies Guide](optional-dependencies.md)

