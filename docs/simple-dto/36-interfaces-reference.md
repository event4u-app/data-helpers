# Interfaces Reference

Complete reference of all interfaces in SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides interfaces for extensibility and type safety.

---

## 📋 Core Interfaces

### CastInterface
Interface for custom casts.

```php
interface CastInterface
{
    public function cast(mixed $value): mixed;
}
```

**Example:**
```php
class CustomCast implements CastInterface
{
    public function cast(mixed $value): mixed
    {
        return strtoupper($value);
    }
}
```

### ValidationAttributeInterface
Interface for custom validation attributes.

```php
interface ValidationAttributeInterface
{
    public function rules(): array;
}
```

**Example:**
```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class CustomRule extends ValidationAttribute
{
    public function rules(): array
    {
        return ['custom_rule'];
    }
}
```

---

## 📚 Complete Documentation

See [Custom Validation](21-custom-validation.md) for creating custom validators.

---

**Previous:** [Traits Reference](35-traits-reference.md)  
**Next:** [Real-World Examples](37-real-world-examples.md)

