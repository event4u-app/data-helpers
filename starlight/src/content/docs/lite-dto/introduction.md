---
title: Introduction to LiteDto
description: Ultra-fast, framework-agnostic DTOs with optional deep integration
---

LiteDto is an **ultra-fast, minimalistic Data Transfer Object (Dto)** library for PHP 8.2+ that provides essential features with maximum performance. **Framework-agnostic** with optional deep integration for Laravel and Symfony – without framework lock-in.

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
| **LiteDto** | **~3.1μs** | Essential features, high performance |
| SimpleDto #[UltraFast] | ~5.7μs | Fast mode with limited features |
| SimpleDto Normal | ~6.2μs | Full features with validation |

**LiteDto is ~2.0x faster than SimpleDto Normal** while providing essential Dto features.

### UltraFast Mode

| Library | Performance | Features |
|---------|-------------|----------|
| Plain PHP | ~0.108μs | No features, manual work |
| Other Dtos | ~3.23μs | Minimal features, maximum speed |
| **LiteDto #[UltraFast]** | **~2.5μs** | Minimal overhead, maximum speed |
| SimpleDto #[UltraFast] | ~5.7μs | Fast mode with limited features |

**LiteDto #[UltraFast] is ~2x faster than SimpleDto Normal** and only **~23.4x slower than Plain PHP**!
<!-- LITEDTO_PERFORMANCE_END -->

## Quick Example

```php
use event4u\DataHelpers\LiteDto;
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

// Type-safe getters with dot notation
$name = $user->getString('name');  // 'John Doe'
$age = $user->getInt('age');       // 30
$email = $user->getString('email'); // 'john@example.com'
```

## 🎯 Framework-Agnostic + Deep Integration

**The best of both worlds:** Use LiteDto as a standalone library in pure PHP, or leverage deep framework integration for Laravel and Symfony – without framework lock-in.

### Pure PHP - Zero Dependencies

```php
// Works everywhere - no framework needed
$dto = UserDto::from($_POST);
$dto = UserDto::from($apiResponse);
$dto = UserDto::from(json_decode($json, true));

// Serialize to any format
$array = $dto->toArray();
$json = $dto->toJson();
```

### Optional Deep Integration

LiteDto works seamlessly with framework features when available:

#### Laravel Integration (Optional)

```php
// Controller Injection - Automatic filling from request
class UserController extends Controller
{
    public function store(UserDto $dto): JsonResponse
    {
        // $dto is automatically filled from request
        $user = User::create($dto->toArray());
        return response()->json($user, 201);
    }
}

// Eloquent Model Integration (automatically available)
$user = User::find(1);
$dto = UserDto::fromModel($user);  // From Eloquent Model
$dto->toModel($user);              // To Eloquent Model
// Note: Methods throw BadMethodCallException if Laravel is not installed
```

#### Symfony Integration (Optional)

```php
// Controller Injection - Automatic filling from request
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function create(UserDto $dto): JsonResponse
    {
        // $dto is automatically filled from request
        $user = new User();
        $dto->toEntity($user);
        $this->entityManager->persist($user);
        return $this->json($user, 201);
    }
}

// Doctrine Entity Integration (automatically available)
$user = $this->entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);  // From Doctrine Entity
$dto->toEntity($user);              // To Doctrine Entity
// Note: Methods throw BadMethodCallException if Doctrine is not installed
```

### Key Benefits

- ✅ **No Framework Lock-In** - Core code works everywhere
- ✅ **Optional Integration** - Add framework features when needed
- ✅ **Portable** - Move between frameworks without code changes
- ✅ **Zero Dependencies** - No required dependencies
- ✅ **Maximum Performance** - 7.6x faster than SimpleDto Normal

**The Power:** Get framework-specific features when you need them, without framework dependencies in your core code.

## UltraFast Mode

For maximum performance, use the `#[UltraFast]` attribute:

```php
use event4u\DataHelpers\LiteDto;
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
        #[MapFrom('product_name')]
        public readonly string $name,

        #[MapFrom('product_price')]
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

### 8. Type-Safe Getters

LiteDto provides strict type-safe getter methods with dot notation support for accessing nested properties. These methods automatically convert values to the expected type or throw a `TypeMismatchException` if conversion fails.

#### Single Value Getters

```php
class UserDto extends LiteDto
{
    public function __construct(
        public string $name,
        public string $age,  // stored as string
        public int $active,
    ) {}
}

$user = UserDto::from([
    'name' => 'John Doe',
    'age' => '30',
    'active' => 1,
]);

// Type-safe getters with automatic conversion
$name = $user->getString('name');  // 'John Doe'
$age = $user->getInt('age');       // 30 (string → int)
$active = $user->getBool('active'); // true (1 → true)

// Returns null if property doesn't exist
$missing = $user->getString('phone');  // null

// Provide default values
$phone = $user->getString('phone', 'N/A');  // 'N/A'
```

#### Collection Getters with Wildcards

Collection getters work with nested DTOs and array properties using wildcard notation:

```php
class EmailDto extends LiteDto
{
    public function __construct(
        public string $email,
        public string $type,
        public bool $verified,
    ) {}
}

class UserDto extends LiteDto
{
    public function __construct(
        public string $name,
        public array $emails,  // array of EmailDto
    ) {}
}

$user = UserDto::from([
    'name' => 'John Doe',
    'emails' => [
        ['email' => 'john@work.com', 'type' => 'work', 'verified' => true],
        ['email' => 'john@home.com', 'type' => 'home', 'verified' => false],
    ],
]);

// Get all email addresses as strings
$addresses = $user->getStringCollection('emails.*.email');
// ['emails.0.email' => 'john@work.com', 'emails.1.email' => 'john@home.com']

// Get all verified flags as booleans
$verified = $user->getBoolCollection('emails.*.verified');
// ['emails.0.verified' => true, 'emails.1.verified' => false]
```

#### Available Type-Safe Getters

**Single Value Getters** (return `?type`):
- `getString(string $path, ?string $default = null): ?string`
- `getInt(string $path, ?int $default = null): ?int`
- `getFloat(string $path, ?float $default = null): ?float`
- `getBool(string $path, ?bool $default = null): ?bool`
- `getArray(string $path, ?array $default = null): ?array`

**Collection Getters** (for wildcards, return `array<int|string, type>`):
- `getStringCollection(string $path): array`
- `getIntCollection(string $path): array`
- `getFloatCollection(string $path): array`
- `getBoolCollection(string $path): array`
- `getArrayCollection(string $path): array`

**Exception Handling:**
- Single value getters throw `TypeMismatchException` if an array is returned (use collection getters)
- Collection getters throw `TypeMismatchException` if path has no wildcard
- All getters throw `TypeMismatchException` if value cannot be converted to expected type

## When to Use LiteDto?

### ✅ Use LiteDto When:
- You need **maximum performance** with essential Dto features
- You want **simple, clean code** without complex validation
- You need **property mapping** and **serialization**
- You want **nested DTOs** and **collections**
- Performance is critical (APIs, high-traffic applications)
- You want **framework-agnostic** code with optional integration

### ❌ Use SimpleDto Instead When:
- You need **validation** (Required, Email, Min, Max, etc.)
- You need **custom casts** (DateTime, Enum, etc.)
- You need **computed properties** or **lazy loading**
- You need **conditional properties**
- You need **pipeline processing**
- You need **framework-specific attributes** (WhenAuth, WhenGranted, etc.)

## Next Steps

- [Creating LiteDtos](./creating-litedtos) - Learn how to create and use LiteDtos
- [Attributes Reference](./attributes) - Complete guide to all attributes
- [Performance Tips](./performance) - Optimize your LiteDtos

