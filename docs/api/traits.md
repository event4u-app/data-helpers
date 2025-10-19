# Traits API Reference

Complete API reference for all SimpleDTO traits.

---

## 📋 Table of Contents

- [Core Traits](#core-traits)
- [Framework Traits](#framework-traits)
- [Trait Architecture](#trait-architecture)

---

## Core Traits

### SimpleDTOTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\SimpleDTOTrait`

**Description:** Main orchestrator trait that provides all DTO functionality.

**Usage:**
```php
use event4u\DataHelpers\SimpleDTO\Traits\SimpleDTOTrait;

class UserDTO
{
    use SimpleDTOTrait;
    
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

**Provides:**
- Factory methods (fromArray, fromJson, validateAndCreate)
- Serialization methods (toArray, toJson, toXml, toYaml, toCsv)
- Dynamic properties (with, withContext)
- Type casting
- Validation
- Property mapping
- Conditional visibility
- Computed properties

---

### CastsTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\CastsTrait`

**Description:** Handles type casting for properties.

**Used By:** SimpleDTOTrait

**Features:**
- Automatic type casting based on #[Cast] attribute
- 20+ built-in casts
- Custom cast support
- Cast instance caching

**Internal Methods:**
```php
protected function applyCasts(array $data): array
protected function getCastForProperty(string $property): ?CastInterface
```

---

### ValidationTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\ValidationTrait`

**Description:** Handles validation for properties.

**Used By:** SimpleDTOTrait

**Features:**
- Automatic rule inferring from attributes
- 30+ validation attributes
- Custom validation rules
- Validation caching (198x faster)

**Internal Methods:**
```php
protected function validate(array $data): void
protected function getRules(): array
protected function inferRulesFromAttributes(): array
```

---

### MappingTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\MappingTrait`

**Description:** Handles property mapping (MapFrom/MapTo).

**Used By:** SimpleDTOTrait

**Features:**
- Input mapping with #[MapFrom]
- Output mapping with #[MapTo]
- Dot notation support (e.g., 'user.name')
- Nested property mapping

**Internal Methods:**
```php
protected function mapInputProperties(array $data): array
protected function mapOutputProperties(array $data): array
```

---

### VisibilityTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\VisibilityTrait`

**Description:** Handles conditional visibility for properties.

**Used By:** SimpleDTOTrait

**Features:**
- 18 conditional attributes
- Context-based visibility
- Framework-specific conditions (Laravel, Symfony)
- #[Hidden] attribute support

**Internal Methods:**
```php
protected function shouldIncludeProperty(string $property): bool
protected function evaluateCondition(object $attribute): bool
```

---

### ComputedTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\ComputedTrait`

**Description:** Handles computed properties.

**Used By:** SimpleDTOTrait

**Features:**
- #[Computed] attribute support
- Automatic method detection
- Lazy evaluation
- Caching of computed values

**Internal Methods:**
```php
protected function getComputedProperties(): array
protected function evaluateComputedProperty(string $method): mixed
```

---

### ConditionalTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\ConditionalTrait`

**Description:** Handles conditional property logic.

**Used By:** VisibilityTrait

**Features:**
- Core conditional attributes (9)
- Context-based conditions (4)
- Framework-specific conditions (6)

**Internal Methods:**
```php
protected function evaluateWhenCallback(\Closure $callback): bool
protected function evaluateWhenValue(string $property, mixed $value): bool
protected function evaluateWhenContext(string $key): bool
```

---

### SerializationTrait

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\SerializationTrait`

**Description:** Handles serialization to different formats.

**Used By:** SimpleDTOTrait

**Features:**
- JSON serialization
- XML serialization
- YAML serialization
- CSV serialization
- Custom serializers

**Internal Methods:**
```php
protected function serializeToJson(array $data, int $options): string
protected function serializeToXml(array $data, string $rootElement): string
protected function serializeToYaml(array $data): string
protected function serializeToCsv(array $data, array $headers): string
```

---

## Framework Traits

### EloquentTrait (Laravel)

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\EloquentTrait`

**Description:** Provides Eloquent model integration.

**Usage:**
```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Traits\EloquentTrait;

class UserDTO extends SimpleDTO
{
    use EloquentTrait;
}

// From model
$user = User::find(1);
$dto = UserDTO::fromModel($user);

// To model
$dto = new UserDTO(name: 'John', email: 'john@example.com');
$user = $dto->toModel(User::class);
$user->save();
```

**Methods:**
```php
public static function fromModel(Model $model): static
public function toModel(string $modelClass): Model
public function updateModel(Model $model): Model
```

---

### DoctrineTrait (Symfony)

**Namespace:** `event4u\DataHelpers\SimpleDTO\Traits\DoctrineTrait`

**Description:** Provides Doctrine entity integration.

**Usage:**
```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Traits\DoctrineTrait;

class UserDTO extends SimpleDTO
{
    use DoctrineTrait;
}

// From entity
$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);

// To entity
$dto = new UserDTO(name: 'John', email: 'john@example.com');
$user = $dto->toEntity(User::class);
$entityManager->persist($user);
$entityManager->flush();
```

**Methods:**
```php
public static function fromEntity(object $entity): static
public function toEntity(string $entityClass): object
public function updateEntity(object $entity): object
```

---

## Trait Architecture

### Composition

SimpleDTOTrait uses composition to organize functionality:

```
SimpleDTOTrait (Orchestrator)
├── CastsTrait (Type casting)
├── ValidationTrait (Validation)
├── MappingTrait (Property mapping)
├── VisibilityTrait (Conditional visibility)
│   └── ConditionalTrait (Conditional logic)
├── ComputedTrait (Computed properties)
└── SerializationTrait (Serialization)
```

### Separation of Concerns

Each trait has a single responsibility:

- **SimpleDTOTrait**: Orchestrates all functionality
- **CastsTrait**: Type casting only
- **ValidationTrait**: Validation only
- **MappingTrait**: Property mapping only
- **VisibilityTrait**: Conditional visibility only
- **ComputedTrait**: Computed properties only
- **SerializationTrait**: Serialization only

### Extensibility

You can use individual traits for custom implementations:

```php
use event4u\DataHelpers\SimpleDTO\Traits\CastsTrait;
use event4u\DataHelpers\SimpleDTO\Traits\ValidationTrait;

class CustomDTO
{
    use CastsTrait;
    use ValidationTrait;
    
    // Custom implementation
}
```

---

## Best Practices

### 1. Use SimpleDTOTrait for Full Functionality

```php
// ✅ Good - full functionality
class UserDTO extends SimpleDTO
{
    // SimpleDTOTrait is already used in SimpleDTO
}
```

### 2. Use Framework Traits When Needed

```php
// ✅ Good - Laravel integration
class UserDTO extends SimpleDTO
{
    use EloquentTrait;
}

// ✅ Good - Symfony integration
class UserDTO extends SimpleDTO
{
    use DoctrineTrait;
}
```

### 3. Don't Mix Framework Traits

```php
// ❌ Bad - mixing frameworks
class UserDTO extends SimpleDTO
{
    use EloquentTrait;
    use DoctrineTrait; // Don't mix
}
```

### 4. Extend SimpleDTO Instead of Using Traits Directly

```php
// ✅ Good - extend SimpleDTO
class UserDTO extends SimpleDTO
{
    // ...
}

// ❌ Bad - use traits directly
class UserDTO
{
    use SimpleDTOTrait;
    // ...
}
```

---

## Performance

### Trait Overhead

Traits have minimal performance overhead:

- **Trait composition**: Compiled at parse time (zero runtime overhead)
- **Method calls**: Same performance as regular methods
- **Memory**: No additional memory per instance

### Optimization

Traits are optimized for performance:

- **Cast caching**: Cast instances are cached
- **Reflection caching**: Property metadata is cached
- **Validation caching**: Validation rules are cached

---

**See Also:**
- [Attributes API](attributes.md)
- [Methods API](methods.md)
- [Architecture Guide](../architecture.md)

