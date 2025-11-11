---
title: Transformation Attributes
description: Complete reference of all transformation attributes that modify values before validation
---

Complete reference of all transformation attributes available in SimpleDto.

## Introduction

Transform attributes automatically modify input values **before validation** occurs. They are perfect for:

- **Normalizing data** - Convert to consistent format (lowercase, uppercase, trim)
- **Encoding/Decoding** - Base64, hashing, leetspeak
- **Case conversion** - camelCase, snake_case, PascalCase
- **Data sanitization** - Remove whitespace, normalize strings

:::tip[Transform vs Validate]
**Transform attributes** modify values before validation. **Validation attributes** check if values are valid.

Example: `#[Lowercase]` transforms `"USER@EXAMPLE.COM"` to `"user@example.com"` before `#[Email]` validates it.
:::

## Quick Reference Table

| Attribute | Description | Example Input → Output |
|-----------|-------------|------------------------|
| `#[Lowercase]` | Convert to lowercase | `"USER"` → `"user"` |
| `#[Uppercase]` | Convert to uppercase | `"user"` → `"USER"` |
| `#[Ucfirst]` | Uppercase first letter | `"john"` → `"John"` |
| `#[Lcfirst]` | Lowercase first letter | `"John"` → `"john"` |
| `#[CamelCase]` | Convert to camelCase | `"user_name"` → `"userName"` |
| `#[SnakeCase]` | Convert to snake_case | `"userName"` → `"user_name"` |
| `#[Trim]` | Remove whitespace | `"  hello  "` → `"hello"` |
| `#[Base64Encode]` | Encode to Base64 | `"hello"` → `"aGVsbG8="` |
| `#[Base64Decode]` | Decode from Base64 | `"aGVsbG8="` → `"hello"` |
| `#[Hash]` | Hash with algorithm | `"secret"` → SHA256 hash |
| `#[Md5]` | Hash with MD5 | `"secret"` → MD5 hash |
| `#[Leetspeak]` | Convert to leetspeak | `"leet"` → `"1337"` |

## Case Transformation

### Lowercase

Convert strings to lowercase using UTF-8 encoding.

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Lowercase;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Lowercase]
        #[Email]
        public readonly string $email,
        
        #[Lowercase]
        public readonly string $username,
    ) {}
}

$dto = UserDto::from([
    'email' => 'USER@EXAMPLE.COM',  // → 'user@example.com'
    'username' => 'JohnDoe',        // → 'johndoe'
]);
```

### Uppercase

Convert strings to uppercase using UTF-8 encoding.

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        #[Uppercase]
        #[Length(3, 10)]
        public readonly string $sku,
        
        #[Uppercase]
        public readonly string $countryCode,
    ) {}
}

$dto = ProductDto::from([
    'sku' => 'abc123',      // → 'ABC123'
    'countryCode' => 'de',  // → 'DE'
]);
```

### Ucfirst

Uppercase the first character of a string.

```php
class PersonDto extends SimpleDto
{
    public function __construct(
        #[Ucfirst]
        #[Alpha]
        public readonly string $firstName,
        
        #[Ucfirst]
        public readonly string $lastName,
    ) {}
}

$dto = PersonDto::from([
    'firstName' => 'john',  // → 'John'
    'lastName' => 'doe',    // → 'Doe'
]);
```

### Lcfirst

Lowercase the first character of a string.

```php
class ApiDto extends SimpleDto
{
    public function __construct(
        #[Lcfirst]
        public readonly string $variableName,
        
        #[Lcfirst]
        public readonly string $propertyName,
    ) {}
}

$dto = ApiDto::from([
    'variableName' => 'UserName',  // → 'userName'
    'propertyName' => 'FirstName', // → 'firstName'
]);
```

## Case Convention Transformation

### CamelCase

Convert strings to camelCase format.

```php
class ApiDto extends SimpleDto
{
    public function __construct(
        #[CamelCase]
        public readonly string $fieldName,
        
        #[CamelCase]
        public readonly string $propertyKey,
    ) {}
}

$dto = ApiDto::from([
    'fieldName' => 'user_name',    // → 'userName'
    'propertyKey' => 'first-name', // → 'firstName'
]);

// Also works with PascalCase input
$dto = ApiDto::from([
    'fieldName' => 'UserName',  // → 'userName'
]);
```

### SnakeCase

Convert strings to snake_case format.

```php
class DatabaseDto extends SimpleDto
{
    public function __construct(
        #[SnakeCase]
        public readonly string $columnName,
        
        #[SnakeCase]
        public readonly string $tableName,
    ) {}
}

$dto = DatabaseDto::from([
    'columnName' => 'userName',    // → 'user_name'
    'tableName' => 'UserProfile',  // → 'user_profile'
]);

// Also works with kebab-case input
$dto = DatabaseDto::from([
    'columnName' => 'user-name',  // → 'user_name'
]);
```

## String Sanitization

### Trim

Remove whitespace from the beginning and end of strings.

```php
class FormDto extends SimpleDto
{
    public function __construct(
        #[Trim]
        public readonly string $name,
        
        #[Trim]
        public readonly string $description,
        
        // Custom characters to trim
        #[Trim('.')]
        public readonly string $domain,
    ) {}
}

$dto = FormDto::from([
    'name' => '  John Doe  ',           // → 'John Doe'
    'description' => "\t\nHello\n\t",   // → 'Hello'
    'domain' => '...example.com...',    // → 'example.com'
]);
```

## Encoding & Hashing

### Base64Encode

Encode strings to Base64 format.

```php
class ApiDto extends SimpleDto
{
    public function __construct(
        #[Base64Encode]
        public readonly string $token,
        
        #[Base64Encode]
        public readonly string $payload,
    ) {}
}

$dto = ApiDto::from([
    'token' => 'my-secret-token',  // → 'bXktc2VjcmV0LXRva2Vu'
    'payload' => 'Hello World!',   // → 'SGVsbG8gV29ybGQh'
]);
```

### Base64Decode

Decode Base64 encoded strings.

```php
class ApiDto extends SimpleDto
{
    public function __construct(
        #[Base64Decode]
        public readonly string $token,
        
        #[Base64Decode]
        public readonly string $payload,
    ) {}
}

$dto = ApiDto::from([
    'token' => 'bXktc2VjcmV0LXRva2Vu',  // → 'my-secret-token'
    'payload' => 'SGVsbG8gV29ybGQh',     // → 'Hello World!'
]);

// Invalid Base64 returns original value
$dto = ApiDto::from([
    'token' => 'not-valid-base64!!!',  // → 'not-valid-base64!!!'
]);
```

### Hash

Hash strings using various algorithms.

```php
class SecurityDto extends SimpleDto
{
    public function __construct(
        #[Hash] // Default: sha256
        public readonly string $password,
        
        #[Hash('sha512')]
        public readonly string $apiKey,
        
        #[Hash('bcrypt')]
        public readonly string $securePassword,
        
        #[Hash('md5')]
        public readonly string $legacyHash,
    ) {}
}

$dto = SecurityDto::from([
    'password' => 'secret',        // → SHA256 hash (64 chars)
    'apiKey' => 'my-key',          // → SHA512 hash (128 chars)
    'securePassword' => 'pass123', // → Bcrypt hash
    'legacyHash' => 'data',        // → MD5 hash (32 chars)
]);

// Verify bcrypt password
password_verify('pass123', $dto->securePassword); // true
```

**Supported algorithms:**
- `sha256` (default) - SHA-256 hash
- `sha512` - SHA-512 hash
- `sha1` - SHA-1 hash
- `md5` - MD5 hash
- `bcrypt` - Bcrypt password hash
- `argon2i` - Argon2i password hash
- `argon2id` - Argon2id password hash

:::caution[Security Warning]
MD5 and SHA1 are **not cryptographically secure**. Use `bcrypt`, `argon2i`, or `argon2id` for passwords.
:::

### Md5

Shortcut for MD5 hashing.

```php
class CacheDto extends SimpleDto
{
    public function __construct(
        #[Md5]
        public readonly string $cacheKey,
        
        #[Md5]
        public readonly string $etag,
    ) {}
}

$dto = CacheDto::from([
    'cacheKey' => 'user:123:profile',  // → MD5 hash
    'etag' => 'content-v1',            // → MD5 hash
]);
```

## Fun Transformations

### Leetspeak

Convert strings to leetspeak (1337sp34k) format.

```php
class GameDto extends SimpleDto
{
    public function __construct(
        #[Leetspeak]
        public readonly string $username,
        
        #[Leetspeak]
        public readonly string $message,
    ) {}
}

$dto = GameDto::from([
    'username' => 'leet',        // → '1337'
    'message' => 'elite hacker', // → '31!73 h4ck3r'
]);
```

**Character mapping:**
- `l/L` → `1`
- `e/E` → `3`
- `t/T` → `7`
- `a/A` → `4`
- `s/S` → `5`
- `o/O` → `0`
- `b/B` → `8`
- `g/G` → `9`
- `i/I` → `!`

## Combining Transformations

Transform attributes can be combined with validation attributes:

```php
class UserDto extends SimpleDto
{
    public function __construct(
        // Transform then validate
        #[Lowercase]
        #[Trim]
        #[Email]
        #[Required]
        public readonly string $email,
        
        // Multiple transformations
        #[Trim]
        #[Ucfirst]
        #[Alpha]
        #[Length(2, 50)]
        public readonly string $name,
        
        // Transform for consistency
        #[Uppercase]
        #[AlphaNum]
        #[Length(3, 10)]
        public readonly string $sku,
    ) {}
}

$dto = UserDto::from([
    'email' => '  USER@EXAMPLE.COM  ',  // → 'user@example.com'
    'name' => '  john  ',                // → 'John'
    'sku' => 'abc123',                   // → 'ABC123'
]);
```

## Best Practices

### Order Matters

Transformations happen **before** validation:

```php
// ✅ Good - transform then validate
#[Lowercase]
#[Email]
public readonly string $email;

// ✅ Good - sanitize then check length
#[Trim]
#[Length(3, 50)]
public readonly string $name;
```

### Use for Normalization

```php
// ✅ Good - normalize email addresses
#[Lowercase]
#[Trim]
#[Email]
public readonly string $email;

// ✅ Good - normalize database identifiers
#[SnakeCase]
#[Lowercase]
public readonly string $columnName;
```

### Security Considerations

```php
// ✅ Good - use strong hashing for passwords
#[Hash('bcrypt')]
public readonly string $password;

// ❌ Bad - MD5 is not secure for passwords
#[Md5]
public readonly string $password;

// ✅ Good - MD5 is fine for cache keys
#[Md5]
public readonly string $cacheKey;
```

## See Also

- [Validation Attributes](/data-helpers/attributes/validation/) - Validate transformed values
- [Custom Attributes](/data-helpers/advanced/custom-attributes/) - Create custom transformations
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Type conversion

