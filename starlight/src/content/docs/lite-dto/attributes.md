---
title: Attributes Reference
description: Complete guide to all LiteDto attributes
---

LiteDto provides six attributes to control property mapping, serialization, data conversion, and performance.

## #[MapFrom]

**Purpose**: Map property from a different source key during hydration.

**Target**: Property or constructor parameter

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\MapFrom`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;

class UserDto extends LiteDto
{
    public function __construct(
        #[MapFrom('first_name')]
        public readonly string $name,

        #[MapFrom('user_email')]
        public readonly string $email,
    ) {}
}

$user = UserDto::from([
    'first_name' => 'John',
    'user_email' => 'john@example.com',
]);

echo $user->name;   // John
echo $user->email;  // john@example.com
```

### API Response Mapping

Perfect for mapping API responses with different naming conventions:

```php
class ProductDto extends LiteDto
{
    public function __construct(
        #[MapFrom('product_id')]
        public readonly int $id,

        #[MapFrom('product_name')]
        public readonly string $name,

        #[MapFrom('product_price')]
        public readonly float $price,

        #[MapFrom('created_at')]
        public readonly string $createdAt,
    ) {}
}

// API response
$apiData = [
    'product_id' => 123,
    'product_name' => 'Laptop',
    'product_price' => 999.99,
    'created_at' => '2024-01-15',
];

$product = ProductDto::from($apiData);
```

## #[MapTo]

**Purpose**: Map property to a different target key during serialization.

**Target**: Property or constructor parameter

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\MapTo`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\MapTo;

class UserDto extends LiteDto
{
    public function __construct(
        #[MapTo('full_name')]
        public readonly string $name,

        #[MapTo('email_address')]
        public readonly string $email,
    ) {}
}

$user = UserDto::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$array = $user->toArray();
// [
//     'full_name' => 'John Doe',
//     'email_address' => 'john@example.com'
// ]
```

### Database Column Mapping

Map DTO properties to database column names:

```php
class UserDto extends LiteDto
{
    public function __construct(
        #[MapTo('user_id')]
        public readonly int $id,

        #[MapTo('user_name')]
        public readonly string $name,

        #[MapTo('user_email')]
        public readonly string $email,

        #[MapTo('created_at')]
        public readonly string $createdAt,
    ) {}
}

$user = UserDto::from([
    'id' => 1,
    'name' => 'John',
    'email' => 'john@example.com',
    'createdAt' => '2024-01-15',
]);

// Ready for database insert
$dbData = $user->toArray();
// [
//     'user_id' => 1,
//     'user_name' => 'John',
//     'user_email' => 'john@example.com',
//     'created_at' => '2024-01-15'
// ]
```

### Combining #[MapFrom] and #[MapTo]

Use both attributes for bidirectional mapping:

```php
class UserDto extends LiteDto
{
    public function __construct(
        #[MapFrom('api_user_id'), MapTo('db_user_id')]
        public readonly int $id,

        #[MapFrom('api_name'), MapTo('db_name')]
        public readonly string $name,
    ) {}
}

// From API (uses #[MapFrom])
$user = UserDto::from([
    'api_user_id' => 123,
    'api_name' => 'John',
]);

// To Database (uses #[MapTo])
$dbData = $user->toArray();
// ['db_user_id' => 123, 'db_name' => 'John']
```

## #[Hidden]

**Purpose**: Exclude property from serialization (toArray, toJson).

**Target**: Property or constructor parameter

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\Hidden`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $apiKey,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'apiKey' => 'key_abc',
]);

// Hidden properties excluded
$array = $user->toArray();
// ['name' => 'John', 'email' => 'john@example.com']

// But still accessible
echo $user->password;  // secret123
```

### API Response Security

Perfect for hiding sensitive data in API responses:

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $passwordResetToken,

        #[Hidden]
        public readonly ?string $twoFactorSecret,
    ) {}
}

// Safe for API responses
return response()->json($user);
// {"id":1,"name":"John","email":"john@example.com"}
```

### Internal Properties

Hide internal/computed properties:

```php
class OrderDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $total,

        #[Hidden]
        public readonly array $rawData,

        #[Hidden]
        public readonly string $internalNotes,
    ) {}
}
```

## #[ConvertEmptyToNull]

**Purpose**: Convert empty strings and empty arrays to null during hydration.

**Target**: Property or constructor parameter

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?string $middleName,

        #[ConvertEmptyToNull]
        public readonly ?string $bio,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'middleName' => '',  // Converted to null
    'bio' => '',         // Converted to null
]);

var_dump($user->middleName);  // NULL
var_dump($user->bio);         // NULL
```

### Form Data Handling

Perfect for handling form submissions where empty fields are sent as empty strings:

```php
class ContactFormDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $message,

        #[ConvertEmptyToNull]
        public readonly ?string $phone,

        #[ConvertEmptyToNull]
        public readonly ?string $company,

        #[ConvertEmptyToNull]
        public readonly ?string $website,
    ) {}
}

// Form submission with empty optional fields
$form = ContactFormDto::from([
    'name' => 'John',
    'email' => 'john@example.com',
    'message' => 'Hello!',
    'phone' => '',      // null
    'company' => '',    // null
    'website' => '',    // null
]);
```

### Empty Arrays

Also works with empty arrays:

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?array $tags,

        #[ConvertEmptyToNull]
        public readonly ?array $preferences,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'tags' => [],         // Converted to null
    'preferences' => [],  // Converted to null
]);
```

## #[ConverterMode]

**Purpose**: Enable Converter support for JSON, XML, and other formats.

**Target**: Class

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\ConverterMode`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\ConverterMode;

#[ConverterMode]
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// Now accepts JSON
$user = UserDto::from('{"name":"John","age":30}');

// And XML
$user = UserDto::from('<root><name>John</name><age>30</age></root>');

// And arrays (still works)
$user = UserDto::from(['name' => 'John', 'age' => 30]);
```

### API Integration

Perfect for consuming external APIs:

```php
#[ConverterMode]
class GithubUserDto extends LiteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $login,
        public readonly string $name,
    ) {}
}

// Fetch from GitHub API
$response = file_get_contents('https://api.github.com/users/octocat');
$user = GithubUserDto::from($response);  // JSON string
```

### Performance Note

**ConverterMode adds ~0.5μs overhead** due to format detection and parsing. Only use it when you need to accept multiple input formats.

```php
// Without ConverterMode: ~2.3μs
class FastDto extends LiteDto { /* ... */ }

// With ConverterMode: ~2.8μs
#[ConverterMode]
class FlexibleDto extends LiteDto { /* ... */ }
```

## #[UltraFast]

**Purpose**: Enable ultra-fast mode for maximum performance (bypasses all attribute processing).

**Target**: Class

**Namespace**: `event4u\DataHelpers\LiteDto\Attributes\UltraFast`

### Basic Usage

```php
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;

#[UltraFast]
class ProductDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}

// ~0.8μs per operation (20x faster than SimpleDto Normal!)
$product = ProductDto::from([
    'name' => 'Laptop',
    'price' => 999.99,
    'stock' => 10,
]);
```

### Performance Comparison

| Mode | Performance | Features |
|------|-------------|----------|
| **LiteDto #[UltraFast]** | **~0.8μs** | Minimal overhead, maximum speed |
| LiteDto Normal | ~2.9μs | All attributes supported |
| SimpleDto #[UltraFast] | ~2.5μs | Fast mode with limited features |
| Plain PHP | ~0.17μs | No features, manual work |

**LiteDto #[UltraFast] is only ~4.8x slower than Plain PHP!**

### What's Disabled in UltraFast Mode

- ❌ No `#[MapFrom]` - Property names must match array keys
- ❌ No `#[MapTo]` - Output uses property names
- ❌ No `#[Hidden]` - All properties are serialized
- ❌ No `#[ConvertEmptyToNull]` - No empty value conversion
- ❌ No `#[ConverterMode]` - Only accepts arrays
- ❌ No nested DTOs or collections
- ❌ No enum support or custom casters

### When to Use UltraFast

✅ **Use UltraFast when**:
- Maximum performance is critical
- Simple flat DTOs without nesting
- Property names match data keys
- No special attribute features needed
- Processing large datasets

❌ **Don't use UltraFast when**:
- You need property mapping (`#[MapFrom]`, `#[MapTo]`)
- You need to hide sensitive data (`#[Hidden]`)
- You need nested DTOs or collections
- You need enum support or custom casters

### Example: High-Performance API

```php
#[UltraFast]
class LogEntryDto extends LiteDto
{
    public function __construct(
        public readonly string $timestamp,
        public readonly string $level,
        public readonly string $message,
        public readonly string $context,
    ) {}
}

// Process 10,000 log entries in ~8ms
$logs = array_map(
    fn($entry) => LogEntryDto::from($entry),
    $logData
);
```

## Combining Attributes

You can combine multiple attributes on the same property (except `#[UltraFast]` which disables all attributes):

```php
class UserDto extends LiteDto
{
    public function __construct(
        #[From('user_name'), To('full_name')]
        public readonly string $name,

        #[From('user_bio'), To('biography'), ConvertEmptyToNull]
        public readonly ?string $bio,

        #[From('api_key'), Hidden]
        public readonly string $apiKey,
    ) {}
}
```

## Next Steps

- [Converter Mode](./converter-mode) - Detailed guide to ConverterMode
- [Performance Tips](./performance) - Optimize your LiteDtos
- [Creating LiteDtos](./creating-litedtos) - Learn more about creating DTOs

