---
title: Validation Attributes
description: Complete reference of all 30+ validation attributes
---

Complete reference of all 30+ validation attributes available in SimpleDTO.

## Overview

SimpleDTO provides 30+ validation attributes organized into categories:

- **Type Validation** - String, Integer, Boolean, Array, Numeric
- **Size Validation** - Min, Max, Between, Size
- **Format Validation** - Email, URL, IP, UUID, Json, Regex
- **Content Validation** - Required, In, NotIn, Same, Different
- **Date Validation** - Date, Before, After, DateFormat
- **Database Validation** - Exists, Unique
- **File Validation** - File, Image, Mimes, MaxFileSize
- **Conditional Validation** - RequiredIf, RequiredUnless, RequiredWith, RequiredWithout, Sometimes

## Quick Reference Table

| Attribute | Description | Example |
|-----------|-------------|---------|
| `#[Required]` | Must be present | `#[Required]` |
| `#[Email]` | Valid email | `#[Email]` |
| `#[Min(int $value)]` | Minimum value/length | `#[Min(3)]` |
| `#[Max(int $value)]` | Maximum value/length | `#[Max(100)]` |
| `#[Between(int $min, int $max)]` | Between range | `#[Between(18, 65)]` |
| `#[In(array $values)]` | In array | `#[In(['a', 'b'])]` |
| `#[NotIn(array $values)]` | Not in array | `#[NotIn(['admin'])]` |
| `#[Url]` | Valid URL | `#[Url]` |
| `#[Uuid]` | Valid UUID | `#[Uuid]` |
| `#[Regex(string $pattern)]` | Match regex | `#[Regex('/^[A-Z]+$/')]` |
| `#[Unique(string $table, string $column)]` | Unique in DB | `#[Unique('users', 'email')]` |
| `#[Exists(string $table, string $column)]` | Exists in DB | `#[Exists('users', 'id')]` |

See full list below for all 30+ attributes.

## Type Validation

```php
#[StringType]    // Must be string
#[Integer]       // Must be integer
#[Boolean]       // Must be boolean
#[ArrayType]     // Must be array
#[Numeric]       // Must be numeric (int or float)
```

## Size Validation

```php
#[Min(3)]              // Minimum value/length
#[Max(100)]            // Maximum value/length
#[Between(18, 65)]     // Between range
#[Size(10)]            // Exact size
```

## Format Validation

```php
#[Email]                        // Valid email
#[Url]                          // Valid URL
#[Ip]                           // Valid IP address
#[Uuid]                         // Valid UUID
#[Json]                         // Valid JSON
#[Regex('/^[A-Z]{2}\d{4}$/')]  // Match regex
```

## Content Validation

```php
#[Required]                              // Must be present
#[Nullable]                              // Can be null
#[In(['active', 'inactive'])]            // In array
#[NotIn(['admin', 'root'])]              // Not in array
#[Same('password')]                      // Match another field
#[Different('oldPassword')]              // Differ from another field
#[StartsWith('https://')]                // Start with prefix
#[EndsWith('.com')]                      // End with suffix
```

## Date Validation

```php
#[Date]                          // Valid date
#[Before('2024-12-31')]          // Before date
#[After('2024-01-01')]           // After date
#[DateFormat('Y-m-d')]           // Match date format
```

## Database Validation

```php
#[Unique('users', 'email')]              // Unique in database
#[Exists('users', 'id')]                 // Exists in database
```

## File Validation

```php
#[File]                                  // Must be file
#[Image]                                 // Must be image
#[Mimes(['jpg', 'png'])]                 // Allowed MIME types
#[MaxFileSize(2048)]                     // Max file size in KB
```

## Conditional Validation

```php
#[RequiredIf('status', 'active')]        // Required if field equals value
#[RequiredUnless('status', 'draft')]     // Required unless field equals value
#[RequiredWith('email')]                 // Required with another field
#[RequiredWithout('phone')]              // Required without another field
#[Sometimes]                             // Only validate if present
```

## Real-World Examples

### User Registration

```php
class UserRegistrationDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(3), Max(50)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,

        #[Required, Same('password')]
        public readonly string $passwordConfirmation,

        #[Sometimes, Url]
        public readonly ?string $website = null,
    ) {}
}
```

### Product Creation

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $name,

        #[Required, Numeric, Min(0)]
        public readonly float $price,

        #[Required, In(['active', 'inactive', 'draft'])]
        public readonly string $status,

        #[Required, Unique('products', 'sku')]
        public readonly string $sku,

        #[Sometimes, ArrayType]
        public readonly ?array $tags = null,
    ) {}
}
```

## Best Practices

### Combine Validation Attributes

```php
// ✅ Good - multiple validations
#[Required, Email, Unique('users', 'email')]
public readonly string $email;
```

### Use Appropriate Types

```php
// ✅ Good - type hint matches validation
#[Integer, Min(0)]
public readonly int $age;

// ❌ Bad - type mismatch
#[Integer]
public readonly string $age;
```

### Provide Custom Messages

```php
#[Required(message: 'Name is required')]
#[Min(3, message: 'Name must be at least 3 characters')]
public readonly string $name;
```

## See Also

- [Validation](/simple-dto/validation/) - Validation guide
- [Conditional Attributes](/attributes/conditional/) - Conditional visibility
- [Custom Validation](/simple-dto/validation/#custom-validation) - Create custom validators
