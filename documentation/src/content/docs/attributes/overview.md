---
title: Attributes Overview
description: Complete reference of all 50+ attributes available in SimpleDTO
---

Complete reference of all 50+ attributes available in SimpleDTO.

## Overview

SimpleDTO provides 50+ attributes organized into categories:

- ✅ **Validation Attributes** (30+) - Data validation
- ✅ **Conditional Attributes** (18) - Visibility control
- ✅ **Cast Attributes** (1) - Type casting
- ✅ **Mapping Attributes** (2) - Property mapping
- ✅ **Computed Attributes** (1) - Calculated properties
- ✅ **Lazy Attributes** (1) - Deferred evaluation
- ✅ **Hidden Attributes** (1) - Always hidden

## Quick Reference

### Validation Attributes

See [Validation Attributes](/attributes/validation/) for complete list of 30+ validation attributes.

**Most Common:**
- `#[Required]` - Property must be present
- `#[Email]` - Valid email address
- `#[Min(int $value)]` - Minimum value/length
- `#[Max(int $value)]` - Maximum value/length
- `#[Between(int $min, int $max)]` - Value between range
- `#[In(array $values)]` - Value in array
- `#[Unique(string $table, string $column)]` - Unique in database
- `#[Exists(string $table, string $column)]` - Exists in database

### Conditional Attributes

See [Conditional Attributes](/attributes/conditional/) for complete list of 18 conditional attributes.

**Most Common:**
- `#[WhenAuth]` - Show when authenticated (Laravel)
- `#[WhenCan(string $permission)]` - Show when user has permission (Laravel)
- `#[WhenRole(string $role)]` - Show when user has role (Laravel)
- `#[WhenValue(string $property, mixed $value)]` - Show when property equals value
- `#[WhenNull(string $property)]` - Show when property is null

### Casting Attributes

See [Casting Attributes](/attributes/casting/) for complete list.

- `#[Cast(string $castClass)]` - Cast property to specific type

### Mapping Attributes

See [Mapping Attributes](/attributes/mapping/) for complete list.

- `#[MapFrom(string $source)]` - Map from different input key
- `#[MapTo(string $target)]` - Map to different output key

### Other Attributes

- `#[Computed]` - Mark method as computed property
- `#[Lazy]` - Defer property evaluation
- `#[Hidden]` - Always hide property


## Combining Attributes

You can combine multiple attributes on a single property:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        // Validation + Mapping
        #[Required, Email, MapFrom('user_email')]
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

## Attribute Categories Summary

### By Purpose

**Data Validation:**
- 30+ validation attributes
- See [Validation Attributes](/attributes/validation/)

**Visibility Control:**
- 18 conditional attributes
- 1 hidden attribute
- See [Conditional Attributes](/attributes/conditional/)

**Data Transformation:**
- 1 cast attribute (20+ cast classes)
- 2 mapping attributes
- See [Casting](/attributes/casting/) and [Mapping](/attributes/mapping/)

**Computed Values:**
- 1 computed attribute
- 1 lazy attribute

### By Framework

**Framework-Agnostic:**
- All validation attributes
- Core conditional attributes (9)
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
#[Required, Email, MapFrom('user_email')]
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

- [Validation Attributes](/attributes/validation/) - Complete validation reference
- [Conditional Attributes](/attributes/conditional/) - Complete conditional reference
- [Casting Attributes](/attributes/casting/) - Type casting reference
- [Mapping Attributes](/attributes/mapping/) - Property mapping reference
