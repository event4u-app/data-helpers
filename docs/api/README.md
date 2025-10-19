# SimpleDTO API Documentation

Complete API reference for SimpleDTO.

---

## 📚 Documentation Structure

This directory contains the complete API documentation for SimpleDTO:

1. **[Attributes API](attributes.md)** - All 50+ attributes
2. **[Casts API](casts.md)** - All 20+ type casts
3. **[Methods API](methods.md)** - All public methods
4. **[Traits API](traits.md)** - All traits

---

## 🎯 Quick Links

### Core Classes

- **[SimpleDTO](methods.md#simpledto)** - Main DTO class
- **[DataCollection](methods.md#datacollection)** - Collection of DTOs

### Attributes

- **[Validation Attributes](attributes.md#validation-attributes)** - 30+ validation attributes
- **[Conditional Attributes](attributes.md#conditional-attributes)** - 18 conditional attributes
- **[Cast Attributes](attributes.md#cast-attributes)** - Type casting
- **[Mapping Attributes](attributes.md#mapping-attributes)** - Property mapping

### Casts

- **[Primitive Casts](casts.md#primitive-casts)** - String, Integer, Boolean, Float
- **[Date Casts](casts.md#date-casts)** - DateTime, Date, Time
- **[Enum Casts](casts.md#enum-casts)** - Enum, BackedEnum
- **[Collection Casts](casts.md#collection-casts)** - Array, Collection
- **[Security Casts](casts.md#security-casts)** - Encrypted, Hashed

---

## 🚀 Getting Started

```php
use event4u\DataHelpers\SimpleDTO;
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

// Create from array
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Validate and create
$dto = UserDTO::validateAndCreate($data);

// Serialize
$array = $dto->toArray();
$json = $dto->toJson();
```

---

## 📖 Documentation

### User Guides

For user-friendly documentation, see:
- [Getting Started](../simple-dto/01-introduction.md)
- [Quick Start](../simple-dto/03-quick-start.md)
- [Basic Usage](../simple-dto/04-basic-usage.md)

### API Reference

For detailed API documentation, see:
- [Attributes API](attributes.md)
- [Casts API](casts.md)
- [Methods API](methods.md)
- [Traits API](traits.md)

---

## 🔍 Search

Use your IDE's search functionality to find specific methods, attributes, or casts:

- **PhpStorm**: `Cmd+Shift+F` (Mac) or `Ctrl+Shift+F` (Windows/Linux)
- **VS Code**: `Cmd+Shift+F` (Mac) or `Ctrl+Shift+F` (Windows/Linux)

---

## 📚 Related Documentation

- [SimpleDTO User Guide](../simple-dto/README.md)
- [Examples](../../examples/)
- [Tests](../../tests/)

---

**Last Updated:** 2025-01-19

