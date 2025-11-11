---
title: Validation Attributes
description: Complete reference of all 40+ validation attributes
---

Complete reference of all 40+ validation attributes available in SimpleDto.

## Introduction

SimpleDto provides 40+ validation attributes organized into categories:

- **Type Validation** - String, Integer, Boolean, Array, Numeric
- **Size Validation** - Min, Max, Between, Size, Length
- **Format Validation** - Email, URL, IP, UUID, Ulid, Json, Regex, Alpha, AlphaNum, AlphaDash, Ascii
- **Content Validation** - Required, In, NotIn, Same, Different
- **Numeric Validation** - Unsigned, Positive, Negative, Decimal
- **Date Validation** - Date, Before, After, DateFormat
- **Database Validation** - Exists, Unique (marker attributes) + UniqueCallback, ExistsCallback (LiteDto)
- **File Validation** - File, Image, Mimes, MimeTypes (marker attributes) + FileCallback (LiteDto)
- **Conditional Validation** - RequiredIf, RequiredUnless, RequiredWith, RequiredWithout, Sometimes
- **Callback Validation** - UniqueCallback, ExistsCallback, FileCallback (custom validation logic)

## Quick Reference Table

| Attribute | Description | Example |
|-----------|-------------|---------|
| `#[Required]` | Must be present | `#[Required]` |
| `#[Email]` | Valid email | `#[Email]` |
| `#[Length(int $max)]` or `#[Length(int $min, int $max)]` | Maximum or range length | `#[Length(10)]` or `#[Length(3, 10)]` |
| `#[Min(int $value)]` | Minimum value/length | `#[Min(3)]` |
| `#[Max(int $value)]` | Maximum value/length | `#[Max(100)]` |
| `#[Between(int $min, int $max)]` | Between range | `#[Between(18, 65)]` |
| `#[In(array $values)]` | In array | `#[In(['a', 'b'])]` |
| `#[NotIn(array $values)]` | Not in array | `#[NotIn(['admin'])]` |
| `#[Url]` | Valid URL | `#[Url]` |
| `#[Uuid]` | Valid UUID | `#[Uuid]` |
| `#[Regex(string $pattern)]` | Match regex | `#[Regex('/^[A-Z]+$/')]` |
| `#[Unique(string $table, string $column)]` | Unique in DB (marker) | `#[Unique('users', 'email')]` |
| `#[Exists(string $table, string $column)]` | Exists in DB (marker) | `#[Exists('users', 'id')]` |
| `#[UniqueCallback(callable $callback)]` | Custom uniqueness check | `#[UniqueCallback([self::class, 'check'])]` |
| `#[ExistsCallback(callable $callback)]` | Custom existence check | `#[ExistsCallback([self::class, 'check'])]` |
| `#[FileCallback(callable $callback)]` | Custom file validation | `#[FileCallback([self::class, 'check'])]` |
| `#[Unsigned]` | Value >= 0 (MySQL UNSIGNED) | `#[Unsigned]` |
| `#[Positive]` | Value > 0 | `#[Positive]` |
| `#[Negative]` | Value < 0 | `#[Negative]` |
| `#[Decimal(int $precision, int $scale)]` | MySQL DECIMAL validation | `#[Decimal(10, 2)]` |
| `#[Alpha]` | Only alphabetic characters | `#[Alpha]` |
| `#[AlphaNum]` | Alphabetic and numeric | `#[AlphaNum]` |
| `#[AlphaDash]` | Alpha, numeric, dashes, underscores | `#[AlphaDash]` |
| `#[Ascii]` | Only ASCII characters | `#[Ascii]` |
| `#[Ulid]` | Valid ULID format | `#[Ulid]` |

See full list below for all 40+ attributes.

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
#[Length(10)]          // Exact length (strings/numbers/arrays)
#[Min(3)]              // Minimum value/length
#[Max(100)]            // Maximum value/length
#[Between(18, 65)]     // Between range
#[Size(10)]            // Exact size
```

### Length Attribute

The `#[Length]` attribute validates length (maximum or range) for strings, numbers, and arrays. Perfect for database column types like `varchar(10)` or `int(3)`.

**Syntax:**
- **One parameter**: Maximum length (0 to $max)
- **Two parameters**: Length range ($min to $max)

**Validation Rules:**
- **Strings**: Character length (using `mb_strlen`)
- **Numbers**: Number of digits (e.g., `123` = 3 digits, `-999` = 3 digits)
- **Arrays**: Number of items
- **Null values**: Always pass (use `#[Required]` for mandatory fields)

**Examples:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Length;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

class ProductDto extends SimpleDto
{
    public function __construct(
        // Maximum length (0 to max)
        #[Required, Length(10)]
        public readonly string $name,  // varchar(10) - 0-10 characters

        #[Required, Length(3)]
        public readonly int $countryCode,  // int(3) - 0-3 digits

        // Length range (min to max)
        #[Required, Length(3, 10)]
        public readonly string $username,  // 3-10 characters

        #[Required, Length(1, 3)]
        public readonly int $code,  // 1-3 digits

        // Array length
        #[Length(5)]
        public readonly ?array $tags = null,  // 0-5 items
    ) {}
}

// Valid examples
$dto = ProductDto::validateAndCreate([
    'name' => 'Product',     // ✅ 7 characters (0-10)
    'countryCode' => 123,    // ✅ 3 digits (0-3)
    'username' => 'john',    // ✅ 4 characters (3-10)
    'code' => 12,            // ✅ 2 digits (1-3)
    'tags' => ['a', 'b'],    // ✅ 2 items (0-5)
]);

// Invalid examples - throws ValidationException
$dto = ProductDto::validateAndCreate([
    'name' => 'Very Long Product Name',  // ❌ Too long (>10 characters)
    'countryCode' => 1234,                // ❌ Too many digits (>3)
    'username' => 'ab',                   // ❌ Too short (<3 characters)
    'code' => 1234,                       // ❌ Too many digits (>3)
    'tags' => ['a', 'b', 'c', 'd', 'e', 'f'], // ❌ Too many items (>5)
]);
```

**Custom Error Messages:**

```php
#[Length(10, message: 'Name must be at most 10 characters')]
public readonly string $name;

#[Length(3, 10, message: 'Username must be between 3 and 10 characters')]
public readonly string $username;
```

**Framework Integration:**

```php
// Laravel:
// - One parameter: Generates 'max:10' rule
// - Two parameters: Generates 'between:3,10' rule

// Symfony:
// - One parameter: Generates Assert\Length(max: 10) constraint
// - Two parameters: Generates Assert\Length(min: 3, max: 10) constraint
```

## Format Validation

```php
#[Email]                        // Valid email
#[Url]                          // Valid URL
#[Ip]                           // Valid IP address
#[Uuid]                         // Valid UUID
#[Ulid]                         // Valid ULID (26 characters, Base32)
#[Json]                         // Valid JSON
#[Regex('/^[A-Z]{2}\d{4}$/')]  // Match regex
#[Alpha]                        // Only alphabetic characters (a-z, A-Z)
#[AlphaNum]                     // Alphabetic and numeric (a-z, A-Z, 0-9)
#[AlphaDash]                    // Alpha, numeric, dashes, underscores (a-z, A-Z, 0-9, -, _)
#[Ascii]                        // Only ASCII characters (0-127)
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

## Numeric Validation

```php
#[Unsigned]                              // Value >= 0 (for MySQL UNSIGNED columns)
#[Positive]                              // Value > 0
#[Negative]                              // Value < 0
#[Decimal(10, 2)]                        // MySQL DECIMAL(precision, scale) validation
```

### Examples

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Unsigned]
        public readonly int $quantity,  // Must be >= 0

        #[Required]
        #[Positive]
        #[Decimal(10, 2)]
        public readonly float $price,   // Must be > 0 and fit DECIMAL(10,2)

        #[Negative]
        public readonly ?int $discount = null,  // Must be < 0 if present
    ) {}
}
```

## Date Validation

```php
#[Date]                          // Valid date
#[Before('2024-12-31')]          // Before date
#[After('2024-01-01')]           // After date
#[DateFormat('Y-m-d')]           // Match date format
```

## Database Validation

### Framework-Specific Attributes (Marker Only)

These attributes are **marker attributes** that generate validation rules for Laravel/Symfony validators. They do **not** perform validation in LiteDto itself:

```php
#[Unique('users', 'email')]              // Unique in database (Laravel/Symfony)
#[Exists('users', 'id')]                 // Exists in database (Laravel/Symfony)
```

### Callback-Based Validation (LiteDto)

For **custom validation logic** that works in LiteDto (including Plain PHP), use callback attributes:

```php
#[UniqueCallback([self::class, 'validateUniqueEmail'])]
public readonly string $email;

#[ExistsCallback([self::class, 'validateUserExists'])]
public readonly int $userId;

// Validation methods
public static function validateUniqueEmail(mixed $value, string $propertyName, array $allData): bool
{
    // Your custom uniqueness check (e.g., PDO, Eloquent, Doctrine)
    return !User::where('email', $value)->exists();
}

public static function validateUserExists(mixed $value, string $propertyName): bool
{
    // Your custom existence check
    return User::find($value) !== null;
}
```

**Key Differences:**
- `#[Unique]` / `#[Exists]` → Framework validators only (Laravel/Symfony)
- `#[UniqueCallback]` / `#[ExistsCallback]` → Works everywhere (Plain PHP, Laravel, Symfony)

## File Validation

### Framework-Specific Attributes (Marker Only)

These attributes are **marker attributes** for Laravel/Symfony validators:

```php
#[File]                                  // Must be file (Laravel/Symfony)
#[Image]                                 // Must be image (Laravel/Symfony)
#[Mimes(['jpg', 'png'])]                 // Allowed MIME types (Laravel/Symfony)
#[MimeTypes(['image/jpeg', 'image/png'])] // Allowed MIME types (Laravel/Symfony)
```

### Callback-Based Validation (LiteDto)

For **custom file validation** in LiteDto:

```php
#[FileCallback([self::class, 'validateFile'])]
public readonly mixed $file;

public static function validateFile(mixed $value, string $propertyName): bool
{
    // Your custom file validation (e.g., check file size, type, etc.)
    if (!is_array($value) || !isset($value['tmp_name'])) {
        return false;
    }

    return is_uploaded_file($value['tmp_name'])
        && $value['size'] <= 2048000; // 2MB
}
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
class UserRegistrationDto extends SimpleDto
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
class ProductDto extends SimpleDto
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

### Custom Validation with Callbacks (LiteDto)

```php
class UserDto extends LiteDto
{
    private static ?PDO $pdo = null;

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public function __construct(
        #[Required, Email]
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        #[Required, Min(3)]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateManagerExists'])]
        public readonly ?int $managerId = null,
    ) {}

    public static function validateUniqueEmail(mixed $value, string $propertyName, array $allData): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() === 0;
    }

    public static function validateManagerExists(mixed $value, string $propertyName): bool
    {
        if ($value === null) {
            return true; // Null is allowed
        }

        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $stmt->execute([$value]);

        return $stmt->fetchColumn() > 0;
    }
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

- [Validation](/data-helpers/simple-dto/validation/) - Validation guide
- [Conditional Attributes](/data-helpers/attributes/conditional/) - Conditional visibility
- [Custom Validation](/data-helpers/simple-dto/validation/#custom-validation) - Create custom validators
