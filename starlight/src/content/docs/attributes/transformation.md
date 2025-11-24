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
| `#[DateTimeFormat]` | Format DateTime objects | `DateTime` → `"2024-01-15 10:30:00"` |
| `#[Lowercase]` | Convert to lowercase | `"USER"` → `"user"` |
| `#[Uppercase]` | Convert to uppercase | `"user"` → `"USER"` |
| `#[Ucfirst]` | Uppercase first letter | `"john"` → `"John"` |
| `#[Lcfirst]` | Lowercase first letter | `"John"` → `"john"` |
| `#[CamelCase]` | Convert to camelCase | `"user_name"` → `"userName"` |
| `#[SnakeCase]` | Convert to snake_case | `"userName"` → `"user_name"` |
| `#[Convert]` | Convert between RTF/HTML/Text | `"{\rtf1 Hello}"` → `"Hello"` |
| `#[Sanitize]` | Remove HTML & normalize text | `"<p>Hello</p>"` → `"Hello"` |
| `#[Trim]` | Remove whitespace | `"  hello  "` → `"hello"` |
| `#[Base64Encode]` | Encode to Base64 | `"hello"` → `"aGVsbG8="` |
| `#[Base64Decode]` | Decode from Base64 | `"aGVsbG8="` → `"hello"` |
| `#[Hash]` | Hash with algorithm | `"secret"` → SHA256 hash |
| `#[Md5]` | Hash with MD5 | `"secret"` → MD5 hash |
| `#[Leetspeak]` | Convert to leetspeak | `"leet"` → `"1337"` |

## DateTime Formatting

### DateTimeFormat

Format DateTime/DateTimeImmutable/Carbon objects with custom format strings for **both input (parsing) and output (serialization)**.

**Features:**
- ✅ **Bidirectional**: Works for both parsing (input) and formatting (output)
- ✅ **Input**: Parses string dates using the specified format when creating DTOs
- ✅ **Output**: Formats DateTime objects when serializing to JSON
- ✅ Supports DateTime, DateTimeImmutable, Carbon, CarbonImmutable
- ✅ Only applies during `toJsonArray()` / `toJson()` / `jsonSerialize()`
- ✅ `toArray()` keeps DateTime objects unchanged

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;

class EventDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTime $startDate,

        #[DateTimeFormat('d.m.Y')]
        public readonly DateTime $germanDate,

        #[DateTimeFormat('c')] // ISO 8601
        public readonly DateTime $isoDate,
    ) {}
}

$dto = EventDto::from([
    'title' => 'Conference',
    'startDate' => new DateTime('2024-01-15 10:30:00'),
    'germanDate' => new DateTime('2024-01-15'),
    'isoDate' => new DateTime('2024-01-15 10:30:00'),
]);

// toArray() keeps DateTime objects
$array = $dto->toArray();
// [
//     'title' => 'Conference',
//     'startDate' => DateTime object,
//     'germanDate' => DateTime object,
//     'isoDate' => DateTime object,
// ]

// toJsonArray() formats DateTime as strings
$jsonArray = $dto->toJsonArray();
// [
//     'title' => 'Conference',
//     'startDate' => '2024-01-15 10:30:00',
//     'germanDate' => '15.01.2024',
//     'isoDate' => '2024-01-15T10:30:00+00:00',
// ]

// toJson() uses toJsonArray()
$json = $dto->toJson();
// {"title":"Conference","startDate":"2024-01-15 10:30:00","germanDate":"15.01.2024",...}
```

**Common Format Strings:**

| Format | Description | Example Output |
|--------|-------------|----------------|
| `'Y-m-d H:i:s'` | MySQL datetime | `2024-01-15 10:30:00` |
| `'Y-m-d'` | Date only | `2024-01-15` |
| `'d.m.Y'` | German date | `15.01.2024` |
| `'d/m/Y'` | UK date | `15/01/2024` |
| `'m/d/Y'` | US date | `01/15/2024` |
| `'c'` | ISO 8601 | `2024-01-15T10:30:00+00:00` |
| `'U'` | Unix timestamp | `1705318200` |

**Bidirectional Usage (Input & Output):**

The same format is used for **both parsing (input) and formatting (output)**:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DateTimeFormat;

class EventDto extends SimpleDto
{
    public function __construct(
        #[DateTimeFormat('d.m.Y')]
        public readonly DateTimeImmutable $germanDate,

        #[DateTimeFormat('m/d/Y')]
        public readonly DateTimeImmutable $usDate,

        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly DateTimeImmutable $mysqlDate,
    ) {}
}

// INPUT: Parse from custom formats
$dto = EventDto::from([
    'germanDate' => '15.01.2024',          // ← Parsed with 'd.m.Y'
    'usDate' => '01/15/2024',              // ← Parsed with 'm/d/Y'
    'mysqlDate' => '2024-01-15 10:30:00',  // ← Parsed with 'Y-m-d H:i:s'
]);

// OUTPUT: Format to custom formats
$json = $dto->toJson();
// {
//   "germanDate": "15.01.2024",          // ← Formatted with 'd.m.Y'
//   "usDate": "01/15/2024",              // ← Formatted with 'm/d/Y'
//   "mysqlDate": "2024-01-15 10:30:00"   // ← Formatted with 'Y-m-d H:i:s'
// }

// ROUND-TRIP: Parse JSON back to DTO
$dto2 = EventDto::from(json_decode($json, true));
// Works perfectly! Same formats are used for parsing
```

**Carbon Support:**

Works seamlessly with Carbon/CarbonImmutable:

```php
use Carbon\Carbon;

class EventDto extends SimpleDto
{
    public function __construct(
        #[DateTimeFormat('Y-m-d H:i:s')]
        public readonly Carbon $startDate,
    ) {}
}

$dto = EventDto::from([
    'startDate' => Carbon::now(),
]);

$json = $dto->toJson();
// {"startDate":"2024-01-15 10:30:00"}
```

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

## Format Conversion

### Convert

Convert between different text formats: RTF (Rich Text Format), HTML, and plain text. All conversions are XSS-safe.

**Syntax:**
```php
use event4u\DataHelpers\SimpleDto\Attributes\Convert;
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;

// Type-safe enum syntax (required)
class Example {
    #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
    public string $rtfToText;

    #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
    public string $htmlToText;

    #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
    public string $textToHtml;
}
```

**Supported Conversions:**
- ✅ RTF → Text: Extract plain text from RTF documents
- ✅ RTF → HTML: Convert RTF to HTML (XSS-safe)
- ✅ HTML → Text: Strip HTML tags and decode entities
- ✅ HTML → RTF: Convert HTML to RTF format
- ✅ Text → HTML: Escape HTML and convert newlines to `<br>` tags
- ✅ Text → RTF: Create RTF document from plain text

**XSS Protection:**
- All conversions sanitize input appropriately
- Text → HTML escapes all HTML special characters
- HTML → Text removes all HTML tags
- RTF conversions handle special characters safely

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Convert;
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;

class DocumentDto extends SimpleDto
{
    public function __construct(
        // Convert RTF to plain text
        #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
        public readonly string $description,

        // Convert HTML to plain text
        #[Convert(ConvertFormat::HTML, ConvertFormat::TEXT)]
        public readonly string $content,

        // Convert plain text to HTML (XSS-safe)
        #[Convert(ConvertFormat::TEXT, ConvertFormat::HTML)]
        public readonly string $htmlContent,

        // Convert RTF to HTML
        #[Convert(ConvertFormat::RTF, ConvertFormat::HTML)]
        public readonly string $richContent,
    ) {}
}

// RTF to Text
$dto = DocumentDto::from([
    'description' => '{\rtf1\ansi Hello\line World}',
    // → 'Hello
    //    World'
]);

// HTML to Text (removes tags, decodes entities)
$dto = DocumentDto::from([
    'content' => '<p>Hello &amp; <strong>World</strong></p>',
    // → 'Hello & World'
]);

// Text to HTML (XSS-safe, converts newlines)
$dto = DocumentDto::from([
    'htmlContent' => "Line 1\nLine 2",
    // → 'Line 1<br>Line 2'
]);

// XSS Protection
$dto = DocumentDto::from([
    'htmlContent' => '<script>alert("xss")</script>',
    // → '&lt;script&gt;alert("xss")&lt;/script&gt;'
]);
```

**Options:**

```php
// Disable newline to <br> conversion
#[Convert(ConvertFormat::TEXT, ConvertFormat::HTML, nl2br: false)]
public readonly string $content;
```

**RTF Support:**

The RTF converter handles:
- Font tables and formatting
- Unicode escapes (`\u252?` → `ü`)
- Hex escapes (`\'e4` → `ä`)
- Line breaks (`\line`, `\par`)
- Special characters (`\{`, `\}`, `\\`)

```php
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;

class ImportDto extends SimpleDto
{
    public function __construct(
        #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
        public readonly string $description,
    ) {}
}

$dto = ImportDto::from([
    'description' => "{\rtf1\ansi\deff0{\fonttbl{\f0\fnil Arial;}}" .
        "\viewkind4\uc1\pard\lang1031\fs20 Einfassungen, Gossen, Einzelabl\'e4ufe und \line Rinnen \par}"
]);

// Result: 'Einfassungen, Gossen, Einzelabläufe und
//          Rinnen'
```

**Use Cases:**

1. **Import from Rich Text Editors**: Convert RTF from desktop applications to plain text or HTML
2. **Database Migration**: Convert legacy RTF content to modern formats
3. **User Input Sanitization**: Convert HTML to plain text for safe storage
4. **Display Formatting**: Convert plain text to HTML for web display
5. **Export to Desktop Apps**: Convert HTML/Text to RTF for Word/Excel

## String Sanitization

### Sanitize

Remove HTML tags, decode entities, and normalize whitespace. Perfect for cleaning user input from rich text editors or HTML content.

**Features:**
- ✅ Removes all HTML tags
- ✅ Converts RTF format to plain text
- ✅ Decodes HTML entities (`&amp;` → `&`)
- ✅ Normalizes whitespace (multiple spaces → single space)
- ✅ Trims leading/trailing whitespace
- ✅ Can be applied to individual properties or entire class

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Sanitize;

class CommentDto extends SimpleDto
{
    public function __construct(
        #[Sanitize]
        public readonly string $content,

        #[Sanitize]
        public readonly string $title,
    ) {}
}

$dto = CommentDto::from([
    'content' => '<p>Hello <b>World</b>!</p>',  // → 'Hello World!'
    'title' => '  Multiple   spaces  ',         // → 'Multiple spaces'
]);
```

**Class-Level Sanitize:**

Apply sanitization to all string properties at once:

```php
#[Sanitize]
class UserInputDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,        // Sanitized
        public readonly string $bio,         // Sanitized
        public readonly int $age,            // Not sanitized (not a string)
        public readonly ?string $website,    // Sanitized if not null
    ) {}
}

$dto = UserInputDto::from([
    'name' => '<script>alert("xss")</script>John',  // → 'John'
    'bio' => '<p>Developer &amp; Designer</p>',     // → 'Developer & Designer'
    'age' => 25,                                     // → 25 (unchanged)
    'website' => '<a href="example.com">Link</a>',  // → 'Link'
]);
```

**RTF Support:**

Automatically converts RTF format to plain text:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Convert;
use event4u\DataHelpers\SimpleDto\Enums\ConvertFormat;

class RtfDto extends SimpleDto
{
    public function __construct(
        #[Convert(ConvertFormat::RTF, ConvertFormat::TEXT)]
        public readonly string $content,
    ) {}
}

$dto = RtfDto::from([
    'content' => '{\rtf1\ansi Hello World}',  // → 'Hello World'
]);
```

### Trim

Remove whitespace from the beginning and end of strings.

**Features:**
- ✅ Removes leading/trailing whitespace
- ✅ Supports custom character mask
- ✅ Can be applied to individual properties or entire class

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

**Class-Level Trim:**

Apply trimming to all string properties at once:

```php
#[Trim]
class FormInputDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,   // Trimmed
        public readonly string $lastName,    // Trimmed
        public readonly int $age,            // Not trimmed (not a string)
        public readonly ?string $email,      // Trimmed if not null
    ) {}
}

$dto = FormInputDto::from([
    'firstName' => '  John  ',    // → 'John'
    'lastName' => '  Doe  ',      // → 'Doe'
    'age' => 25,                  // → 25 (unchanged)
    'email' => '  test@test.com  ',  // → 'test@test.com'
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

