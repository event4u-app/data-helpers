# Validation Attributes Reference

Complete reference of all 30+ validation attributes available in SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides 30+ validation attributes that automatically generate validation rules:

- ✅ **Type Validation** - String, Integer, Boolean, Array, etc.
- ✅ **Size Validation** - Min, Max, Between, Size
- ✅ **Format Validation** - Email, URL, IP, UUID, etc.
- ✅ **Content Validation** - Required, In, NotIn, Regex
- ✅ **Date Validation** - Date, Before, After, DateFormat
- ✅ **Database Validation** - Exists, Unique
- ✅ **File Validation** - File, Image, Mimes, MaxFileSize
- ✅ **Numeric Validation** - Numeric, Integer, Decimal, Multiple

---

## 📋 Complete Attribute List

### Required & Nullable

#### #[Required]
Property must be present and not empty.

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
    ) {}
}
```

#### #[Nullable]
Property can be null (default for optional properties).

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Nullable]
        public readonly ?string $middleName = null,
    ) {}
}
```

---

### Type Validation

#### #[StringType]
Value must be a string.

```php
#[StringType]
public readonly string $name
```

#### #[Integer]
Value must be an integer.

```php
#[Integer]
public readonly int $age
```

#### #[Boolean]
Value must be a boolean.

```php
#[Boolean]
public readonly bool $active
```

#### #[ArrayType]
Value must be an array.

```php
#[ArrayType]
public readonly array $tags
```

#### #[Numeric]
Value must be numeric (int or float).

```php
#[Numeric]
public readonly float $price
```

---

### String Validation

#### #[Min(int $length)]
String must be at least $length characters.

```php
#[Min(8)]
public readonly string $password
```

#### #[Max(int $length)]
String must be at most $length characters.

```php
#[Max(255)]
public readonly string $title
```

#### #[Between(int $min, int $max)]
String length must be between $min and $max.

```php
#[Between(3, 50)]
public readonly string $username
```

#### #[Size(int $length)]
String must be exactly $length characters.

```php
#[Size(10)]
public readonly string $phoneNumber
```

#### #[Regex(string $pattern)]
String must match the regex pattern.

```php
#[Regex('/^[A-Z][a-z]+$/')]
public readonly string $firstName
```

---

### Format Validation

#### #[Email]
Value must be a valid email address.

```php
#[Email]
public readonly string $email
```

#### #[URL]
Value must be a valid URL.

```php
#[URL]
public readonly string $website
```

#### #[IP]
Value must be a valid IP address.

```php
#[IP]
public readonly string $ipAddress
```

#### #[IPv4]
Value must be a valid IPv4 address.

```php
#[IPv4]
public readonly string $ipv4
```

#### #[IPv6]
Value must be a valid IPv6 address.

```php
#[IPv6]
public readonly string $ipv6
```

#### #[UUID]
Value must be a valid UUID.

```php
#[UUID]
public readonly string $id
```

#### #[JSON]
Value must be valid JSON.

```php
#[JSON]
public readonly string $metadata
```

---

### Numeric Validation

#### #[Min(int|float $value)]
Number must be at least $value.

```php
#[Min(0)]
public readonly int $quantity
```

#### #[Max(int|float $value)]
Number must be at most $value.

```php
#[Max(100)]
public readonly int $percentage
```

#### #[Between(int|float $min, int|float $max)]
Number must be between $min and $max.

```php
#[Between(1, 10)]
public readonly int $rating
```

#### #[Positive]
Number must be positive (> 0).

```php
#[Positive]
public readonly float $price
```

#### #[PositiveOrZero]
Number must be positive or zero (>= 0).

```php
#[PositiveOrZero]
public readonly int $stock
```

#### #[Negative]
Number must be negative (< 0).

```php
#[Negative]
public readonly float $debt
```

#### #[NegativeOrZero]
Number must be negative or zero (<= 0).

```php
#[NegativeOrZero]
public readonly float $balance
```

#### #[Decimal(int $min, int $max)]
Number must have between $min and $max decimal places.

```php
#[Decimal(2, 2)]
public readonly float $price
```

#### #[MultipleOf(int|float $value)]
Number must be a multiple of $value.

```php
#[MultipleOf(5)]
public readonly int $quantity
```

---

### Content Validation

#### #[In(array $values)]
Value must be in the given array.

```php
#[In(['draft', 'published', 'archived'])]
public readonly string $status
```

#### #[NotIn(array $values)]
Value must not be in the given array.

```php
#[NotIn(['admin', 'root'])]
public readonly string $username
```

#### #[Confirmed]
Field must have a matching confirmation field.

```php
#[Confirmed]
public readonly string $password
// Expects: password_confirmation
```

#### #[Same(string $field)]
Value must match another field.

```php
#[Same('password')]
public readonly string $passwordConfirmation
```

#### #[Different(string $field)]
Value must be different from another field.

```php
#[Different('oldPassword')]
public readonly string $newPassword
```

---

### Date Validation

#### #[Date]
Value must be a valid date.

```php
#[Date]
public readonly string $birthDate
```

#### #[DateFormat(string $format)]
Date must match the given format.

```php
#[DateFormat('Y-m-d')]
public readonly string $date
```

#### #[Before(string $date)]
Date must be before the given date.

```php
#[Before('2025-12-31')]
public readonly string $startDate
```

#### #[BeforeOrEqual(string $date)]
Date must be before or equal to the given date.

```php
#[BeforeOrEqual('today')]
public readonly string $deadline
```

#### #[After(string $date)]
Date must be after the given date.

```php
#[After('2024-01-01')]
public readonly string $endDate
```

#### #[AfterOrEqual(string $date)]
Date must be after or equal to the given date.

```php
#[AfterOrEqual('today')]
public readonly string $futureDate
```

---

### Database Validation

#### #[Exists(string $table, string $column = 'id')]
Value must exist in database table.

```php
#[Exists('users', 'id')]
public readonly int $userId
```

#### #[Unique(string $table, string $column, ?int $ignoreId = null)]
Value must be unique in database table.

```php
#[Unique('users', 'email')]
public readonly string $email

// Ignore current record when updating
#[Unique('users', 'email', ignoreId: $this->id)]
public readonly string $email
```

---

### File Validation

#### #[File]
Value must be a file.

```php
#[File]
public readonly mixed $document
```

#### #[Image]
Value must be an image file.

```php
#[Image]
public readonly mixed $avatar
```

#### #[Mimes(array $types)]
File must have one of the given MIME types.

```php
#[Mimes(['jpg', 'png', 'gif'])]
public readonly mixed $image
```

#### #[MimeTypes(array $types)]
File must have one of the given MIME types (full).

```php
#[MimeTypes(['image/jpeg', 'image/png'])]
public readonly mixed $image
```

#### #[MaxFileSize(int $kilobytes)]
File size must not exceed $kilobytes.

```php
#[MaxFileSize(2048)] // 2MB
public readonly mixed $upload
```

#### #[Dimensions(array $constraints)]
Image dimensions must match constraints.

```php
#[Dimensions(['min_width' => 100, 'max_width' => 1000])]
public readonly mixed $image
```

---

### Array Validation

#### #[ArrayType]
Value must be an array.

```php
#[ArrayType]
public readonly array $items
```

#### #[Min(int $count)]
Array must have at least $count items.

```php
#[Min(1)]
public readonly array $tags
```

#### #[Max(int $count)]
Array must have at most $count items.

```php
#[Max(10)]
public readonly array $categories
```

#### #[Between(int $min, int $max)]
Array must have between $min and $max items.

```php
#[Between(1, 5)]
public readonly array $options
```

---

## 🎯 Combining Attributes

You can combine multiple attributes:

```php
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3), Max(50)]
        public readonly string $name,
        
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, StringType, Min(8), Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
        public readonly string $password,
        
        #[Required, Integer, Between(18, 120)]
        public readonly int $age,
        
        #[Required, In(['male', 'female', 'other'])]
        public readonly string $gender,
    ) {}
}
```

---

## 💡 Best Practices

### 1. Use Specific Attributes

```php
// ✅ Good - specific validation
#[Email]
public readonly string $email

// ❌ Bad - generic regex
#[Regex('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/')]
public readonly string $email
```

### 2. Combine Related Attributes

```php
// ✅ Good - combined validation
#[Required, StringType, Min(8), Max(255)]
public readonly string $password

// ❌ Bad - separate DTOs for each validation
```

### 3. Use Type Hints with Attributes

```php
// ✅ Good - type hint + attribute
#[Email]
public readonly string $email

// ❌ Bad - attribute only
#[Email]
public readonly $email
```

---

## 📚 Next Steps

1. [Custom Validation](21-custom-validation.md) - Create custom rules
2. [Validation System](07-validation.md) - How validation works
3. [Security & Visibility](22-security-visibility.md) - Hidden properties
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [Plain PHP Usage](19-plain-php.md)  
**Next:** [Custom Validation](21-custom-validation.md)

