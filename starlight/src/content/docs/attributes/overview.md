---
title: Attributes Overview
description: Complete reference of all 50+ attributes available in SimpleDto
---

Complete reference of all 50+ attributes available in SimpleDto.

## Introduction

SimpleDto provides 60+ attributes organized into categories:

- ✅ **Validation Attributes** - Data validation
- ✅ **Transformation Attributes** - Value transformation
- ✅ **Conditional Attributes** - Visibility control
- ✅ **Cast Attributes** - Type casting
- ✅ **Mapping Attributes* - Property mapping
- ✅ **Computed Attributes* - Calculated properties
- ✅ **Lazy Attributes** - Deferred evaluation
- ✅ **Hidden Attributes** - Always hidden
- ✅ **Performance Attributes** - Performance optimization

## Quick Reference

### Validation Attributes

See [Validation Attributes](/data-helpers/attributes/validation/) for complete list of 40+ validation attributes.

**Most Common:**
- `#[Required]` - Property must be present
- `#[Email]` - Valid email address
- `#[Min(int $value)]` - Minimum value/length
- `#[Max(int $value)]` - Maximum value/length
- `#[Between(int $min, int $max)]` - Value between range
- `#[In(array $values)]` - Value in array
- `#[Unique(string $table, string $column)]` - Unique in database
- `#[Exists(string $table, string $column)]` - Exists in database

### Transformation Attributes

See [Transformation Attributes](/data-helpers/attributes/transformation/) for complete list of 13 transformation attributes.

**Most Common:**
- `#[DateTimeFormat(string $format)]` - Format DateTime objects (e.g., 'Y-m-d H:i:s', 'd.m.Y')
- `#[Lowercase]` - Convert to lowercase
- `#[Uppercase]` - Convert to uppercase
- `#[Trim]` - Remove whitespace
- `#[CamelCase]` - Convert to camelCase
- `#[SnakeCase]` - Convert to snake_case
- `#[Base64Encode]` - Encode to Base64
- `#[Base64Decode]` - Decode from Base64
- `#[Hash(string $algorithm)]` - Hash with algorithm (sha256, bcrypt, etc.)

### Conditional Attributes

See [Conditional Attributes](/data-helpers/attributes/conditional/) for complete list of 18 conditional attributes.

**Most Common:**
- `#[WhenAuth]` - Show when authenticated (Laravel)
- `#[WhenCan(string $permission)]` - Show when user has permission (Laravel)
- `#[WhenRole(string $role)]` - Show when user has role (Laravel)
- `#[WhenValue(string $property, mixed $value)]` - Show when property equals value
- `#[WhenNull(string $property)]` - Show when property is null

### Casting Attributes

See [Casting Attributes](/data-helpers/attributes/casting/) for complete list.

- `#[Cast(string $castClass)]` - Cast property to specific type

### Mapping Attributes

See [Mapping Attributes](/data-helpers/attributes/mapping/) for complete list.

- `#[Map(string|array $key)]` - Bidirectional mapping (recommended)
- `#[MapFrom(string|array $source)]` - Map from different input key
- `#[MapTo(string $target)]` - Map to different output key

### Other Attributes

- `#[Computed]` - Mark method as computed property
- `#[Lazy]` - Defer property evaluation
- `#[Hidden]` - Always hide property

### Performance Attributes {#performance-attributes}

**Optimize DTO performance by skipping unnecessary operations:**

- `#[NoCasts]` - Skip **ALL** casting operations (34-63% faster)
  - ❌ Disables nested DTO auto-casting
  - ❌ Disables native type casts
  - ❌ Disables explicit `#[Cast]` attributes
  - ✅ Keeps validation, visibility, mapping attributes

- `#[NoValidation]` - Skip all validation operations (+5% faster)
  - ❌ Disables all validation attributes
  - ✅ Keeps casting, visibility, mapping attributes

- `#[NoAttributes]` - Skip all attribute processing (+5% faster)
  - ❌ Disables validation, visibility, mapping, cast attributes
  - ❌ Disables automatic type casting
  - ✅ Use when you have simple DTOs without attributes

:::tip[Automatic Type Casting in SimpleDto]
**Key Insight:** SimpleDto automatically casts ALL types by default (native types, nested DTOs, DataCollections, DateTime). Use `#[NoCasts]` to disable automatic casting for better performance.

**What SimpleDto auto-casts by default:**
- ✅ Native type casting (int, float, string, bool, array)
- ✅ Nested DTO auto-casting (SimpleDto subclasses)
- ✅ DataCollection auto-casting (with `#[DataCollectionOf]`)
- ✅ DateTime/DateTimeImmutable auto-casting

**Performance optimization:**
- `#[NoCasts]` alone: +37% faster, keeps validation, disables all casting
- `#[NoCasts, NoValidation]`: +34% faster, maximum performance
- Default (no attributes): Full automatic type casting

See [Performance Attributes](/data-helpers/attributes/performance/) for detailed benchmarks and usage examples.
:::


## Combining Attributes

You can combine multiple attributes on a single property:

```php
class UserDto extends SimpleDto
{
    public function __construct(
        // Validation + Mapping
        #[Required, Email, Map('user_email')]
        public readonly string $email,

        // Validation + Cast
        #[Required, Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,

        // Conditional + Cast
        #[WhenAuth, Cast(EncryptedCast::class)]
        public readonly ?string $ssn = null,

        // Multiple Conditionals
        #[WhenAuth, WhenRole('admin')]
        public readonly ?array $adminData = null,
    ) {}
}
```

## Attribute Summary By Framework

**Framework-Agnostic:**
- All validation attributes
- Core conditional attributes
- Cast, mapping, computed, lazy, hidden

**Laravel-Specific:**
- WhenAuth, WhenGuest, WhenCan, WhenRole

**Symfony-Specific:**
- WhenGranted, WhenSymfonyRole

## Best Practices

### Use Specific Attributes

```php
// ✅ Good - specific attribute
#[WhenAuth]
public readonly ?string $email;

// ❌ Bad - generic callback
#[WhenCallback(fn() => auth()->check())]
public readonly ?string $email;
```

### Combine Attributes Logically

```php
// ✅ Good - logical combination
#[Required, Email, Map('user_email')]
public readonly string $email;

// ❌ Bad - conflicting attributes
#[Required, Nullable]
public readonly ?string $email;
```

### Use Type Hints

```php
// ✅ Good - with type hint
#[Cast(DateTimeCast::class)]
public readonly Carbon $date;

// ❌ Bad - no type hint
#[Cast(DateTimeCast::class)]
public readonly $date;
```

## See Also

- [Validation Attributes](/data-helpers/attributes/validation/) - Complete validation reference
- [Conditional Attributes](/data-helpers/attributes/conditional/) - Complete conditional reference
- [Casting Attributes](/data-helpers/attributes/casting/) - Type casting reference
- [Mapping Attributes](/data-helpers/attributes/mapping/) - Property mapping reference
