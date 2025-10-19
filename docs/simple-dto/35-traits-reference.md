# Traits Reference

Complete reference of all traits available in SimpleDTO.

---

## 🎯 Overview

SimpleDTO uses a trait-based architecture for modularity and flexibility.

---

## 📋 Core Traits

### SimpleDTOTrait
Main trait that orchestrates all functionality.

```php
use event4u\DataHelpers\SimpleDTO\Traits\SimpleDTOTrait;

class UserDTO
{
    use SimpleDTOTrait;
}
```

---

## 🔧 Specialized Traits

### CastsTrait
Handles type casting.

### ValidationTrait
Handles validation.

### MappingTrait
Handles property mapping.

### VisibilityTrait
Handles conditional visibility.

### ComputedTrait
Handles computed properties.

### ConditionalTrait
Handles conditional properties.

### SerializationTrait
Handles serialization.

---

## 🗄️ Framework Traits

### EloquentTrait (Laravel)
Eloquent model integration.

```php
use event4u\DataHelpers\SimpleDTO\Traits\EloquentTrait;

class UserDTO extends SimpleDTO
{
    use EloquentTrait;
}
```

### DoctrineTrait (Symfony)
Doctrine entity integration.

```php
use event4u\DataHelpers\SimpleDTO\Traits\DoctrineTrait;

class UserDTO extends SimpleDTO
{
    use DoctrineTrait;
}
```

---

## 📚 Complete Documentation

See individual feature documentation for detailed trait usage.

---

**Previous:** [Casts Reference](34-casts-reference.md)  
**Next:** [Interfaces Reference](36-interfaces-reference.md)

