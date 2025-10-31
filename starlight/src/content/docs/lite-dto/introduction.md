---
title: Introduction to LiteDto
description: Ultra-fast, minimalistic Data Transfer Objects for PHP 8.2+
---

LiteDto is an **ultra-fast, minimalistic Data Transfer Object (Dto)** library for PHP 8.2+ that provides essential features with maximum performance.

## What is LiteDto?

**LiteDto** is designed for developers who need:
- ✅ **Maximum Performance** - 7.6x faster than SimpleDto Normal
- ✅ **Minimal Overhead** - Only essential features, no validation, no pipeline
- ✅ **Simple API** - Easy to learn and use
- ✅ **Attribute-Driven** - Clean, declarative syntax
- ✅ **Optional Converter** - JSON/XML support when needed

## Performance Comparison

<!-- LITEDTO_PERFORMANCE_START -->

### Standard Mode

| Library | Performance | Features |
|---------|-------------|----------|
| **LiteDto** | **~7.6μs** | Essential features, high performance |
| SimpleDto #[UltraFast] | ~4.5μs | Fast mode with limited features |
| SimpleDto Normal | ~4.8μs | Full features with validation |

**LiteDto is ~0.6x faster than SimpleDto Normal** while providing essential Dto features.

### UltraFast Mode

| Library | Performance | Features |
|---------|-------------|----------|
| Plain PHP | ~0.106μs | No features, manual work |
| Other Dtos | ~3.24μs | Minimal features, maximum speed |
| **LiteDto #[UltraFast]** | **~3.4μs** | Minimal overhead, maximum speed |
| SimpleDto #[UltraFast] | ~4.5μs | Fast mode with limited features |

**LiteDto #[UltraFast] is ~1x faster than SimpleDto Normal** and only **~31.8x slower than Plain PHP**!
<!-- LITEDTO_PERFORMANCE_END -->

## Quick Example

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,

        #[MapFrom('email_address')]
        public readonly string $email,

        #[Hidden]
        public readonly string $password,
    ) {}
}

// Create from array
$user = UserDto::from([
    'name' => 'John Doe',
    'age' => 30,
    'email_address' => 'john@example.com',
    'password' => 'secret123',
]);

// Serialize (password is hidden)
$array = $user->toArray();
// ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com']

$json = $user->toJson();
// {"name":"John Doe","age":30,"email":"john@example.com"}
```

## UltraFast Mode

For maximum performance, use the `#[UltraFast]` attribute:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
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

**UltraFast Mode Trade-offs**:
- ✅ **~0.8μs** per operation (only ~4.8x slower than Plain PHP)
- ✅ Direct property assignment (minimal overhead)
- ❌ No attribute processing (`#[MapFrom]`, `#[MapTo]`, `#[Hidden]`, etc.)
- ❌ No nested DTOs or collections
- ❌ No enum support or custom casters

**Use UltraFast when**:
- Maximum performance is critical
- Simple flat DTOs without nesting
- No special attribute features needed

## Core Features

### 1. Property Mapping

Map properties from different source keys:

```php
class ProductDto extends LiteDto
{
    public function __construct(
        #[From('product_name')]
        public readonly string $name,

        #[From('product_price')]
        public readonly float $price,
    ) {}
}

$product = ProductDto::from([
    'product_name' => 'Laptop',
    'product_price' => 999.99,
]);
```

### 2. Output Mapping

Map properties to different target keys when serializing:

```php
use event4u\DataHelpers\LiteDto\Attributes\MapTo;

class UserDto extends LiteDto
{
    public function __construct(
        #[MapTo('full_name')]
        public readonly string $name,

        #[MapTo('user_age')]
        public readonly int $age,
    ) {}
}

$user = UserDto::from(['name' => 'John', 'age' => 30]);
$array = $user->toArray();
// ['full_name' => 'John', 'user_age' => 30]
```

### 3. Hidden Properties

Exclude sensitive properties from serialization:

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $apiKey,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'password' => 'secret',
    'apiKey' => 'key123',
]);

$array = $user->toArray();
// ['name' => 'John'] - password and apiKey are hidden
```

### 4. Convert Empty to Null

Automatically convert empty strings and arrays to null:

```php
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?string $middleName,

        #[ConvertEmptyToNull]
        public readonly ?array $tags,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'middleName' => '',      // Converted to null
    'tags' => [],            // Converted to null
]);
```

### 5. Nested DTOs

Automatically hydrate nested DTOs:

```php
class AddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
    ],
]);
```

### 6. Collections

Handle arrays of DTOs:

```php
class TeamDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        /** @var array<UserDto> */
        public readonly array $members,
    ) {}
}

$team = TeamDto::from([
    'name' => 'Engineering',
    'members' => [
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
    ],
]);
```

### 7. Converter Mode (Optional)

Enable JSON/XML support with the `#[ConverterMode]` attribute:

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
```

**Note**: ConverterMode adds ~0.5μs overhead but enables multiple input formats.

## When to Use LiteDto?

### ✅ Use LiteDto When:
- You need **maximum performance** with essential Dto features
- You want **simple, clean code** without complex validation
- You need **property mapping** and **serialization**
- You want **nested DTOs** and **collections**
- Performance is critical (APIs, high-traffic applications)

### ❌ Use SimpleDto Instead When:
- You need **validation** (Required, Email, Min, Max, etc.)
- You need **custom casts** (DateTime, Enum, etc.)
- You need **computed properties** or **lazy loading**
- You need **conditional properties**
- You need **pipeline processing**

## Next Steps

- [Creating LiteDtos](./creating-litedtos) - Learn how to create and use LiteDtos
- [Attributes Reference](./attributes) - Complete guide to all attributes
- [Performance Tips](./performance) - Optimize your LiteDtos

