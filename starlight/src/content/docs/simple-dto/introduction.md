---
title: Introduction to SimpleDto
description: Powerful, framework-agnostic Data Transfer Objects for PHP 8.2+
---

SimpleDto is a powerful, framework-agnostic Data Transfer Object (Dto) library for PHP 8.2+ that makes working with structured data simple, type-safe, and performant.

## What is a Dto?

A **Data Transfer Object (Dto)** is a design pattern used to transfer data between different parts of an application. Dtos are simple objects that:

- Contain only data (no business logic)
- Are immutable (readonly properties)
- Are type-safe (PHP 8.2+ types)
- Can be easily serialized/deserialized

### Example: Without Dto

```php
// Controller - messy array handling
public function store(Request $request)
{
    $data = $request->all();

    // Manual validation
    if (empty($data['name']) || !is_string($data['name'])) {
        throw new ValidationException('Invalid name');
    }

    // Manual type casting
    $age = isset($data['age']) ? (int) $data['age'] : null;

    // Manual mapping
    $user = new User();
    $user->name = $data['name'];
    $user->age = $age;
    $user->email = $data['email'] ?? null;

    return response()->json([
        'name' => $user->name,
        'age' => $user->age,
        // Forgot to include email!
    ]);
}
```

### Example: With SimpleDto

```php
// Dto - clean and type-safe
class UserDto extends SimpleDto
{
    public function __construct(
        #[Required, StringType, Min(3)]
        public readonly string $name,

        #[Required, IntegerType, Between(18, 120)]
        public readonly int $age,

        #[Required, Email]
        public readonly string $email,
    ) {}
}

// Controller - simple and clean
public function store(UserDto $dto)
{
    $user = User::create($dto->toArray());
    return response()->json($dto);
}
```

## Why SimpleDto?

### Framework Agnostic

Works with Laravel, Symfony, and plain PHP. No framework lock-in.

```php
// Laravel
$dto = UserDto::fromRequest($request);

// Symfony
$dto = UserDto::fromArray($request->request->all());

// Plain PHP
$dto = UserDto::fromArray($_POST);
```

### Type Safety

Full PHP 8.2+ type support with automatic casting.

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly Carbon $createdAt,
        public readonly Status $status,  // Enum
    ) {}
}
```

### Validation

30+ built-in validation attributes.

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[Required, StringType, Min(3), Max(50)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, IntegerType, Between(18, 120)]
        public readonly int $age,

        #[Url]
        public readonly ?string $website = null,
    ) {}
}

// Automatic validation
$dto = UserDto::validateAndCreate($data);
```

### Conditional Properties

18 conditional attributes for dynamic visibility.

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]  // Only when authenticated
        public readonly ?string $email = null,

        #[WhenCan('view-admin')]  // Only with permission
        public readonly ?array $adminData = null,

        #[WhenNotNull]  // Only when not null
        public readonly ?string $phone = null,
    ) {}
}
```

### Property Mapping

Map source keys to different property names.

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('full_name')]
        public readonly string $name,

        #[MapFrom('email_address')]
        public readonly string $email,
    ) {}
}

$dto = UserDto::fromArray([
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);
```


### Type Casting

20+ built-in casts for automatic type conversion.

```php
class OrderDto extends SimpleDto
{
    public function __construct(
        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $orderDate,

        #[Cast(EnumCast::class, enum: Status::class)]
        public readonly Status $status,

        #[Cast(CollectionCast::class, of: OrderItemDto::class)]
        public readonly DataCollection $items,
    ) {}
}
```

### Lazy Properties

Defer expensive operations until needed.

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,  // Only loaded when accessed
    ) {}
}

$dto = UserDto::fromModel($user);
// Posts are NOT loaded yet

$posts = $dto->posts;
// Posts are loaded NOW
```

### Computed Properties

Calculate values on-the-fly.

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly float $price,
        public readonly float $taxRate,
    ) {}

    #[Computed]
    public function priceWithTax(): float
    {
        return $this->price * (1 + $this->taxRate);
    }
}

$dto = new ProductDto(price: 100, taxRate: 0.19);
echo $dto->priceWithTax();  // 119.0

$array = $dto->toArray();
// ['price' => 100, 'taxRate' => 0.19, 'priceWithTax' => 119.0]
```

### Collections

Built-in collection support with pagination.

```php
$users = UserDto::collection($userArray);
// DataCollection of UserDto instances

$paginated = UserDto::paginatedCollection($users, page: 1, perPage: 10);
// [
//     'data' => [...],
//     'meta' => ['current_page' => 1, 'per_page' => 10, ...],
// ]
```

## Key Features

### Core Features

- **Immutable by design** - Use readonly properties
- **Type-safe** - Full PHP type hinting support
- **JSON serializable** - Implements `JsonSerializable`
- **Array conversion** - `toArray()` and `fromArray()`
- **Nested Dtos** - Support for complex structures
- **Collections** - Built-in collection support

### Validation

- **30+ validation attributes** - Required, Email, Min, Max, Between, etc.
- **Custom validation** - Create your own validators
- **Automatic validation** - `validateAndCreate()` method
- **Validation caching** - 198x faster with caching

### Advanced Features

- **18 Conditional Attributes** - WhenAuth, WhenCan, WhenValue, etc.
- **with() Method** - Dynamic property addition
- **Context-Based Conditions** - Context-aware properties
- **Lazy properties** - Lazy loading and evaluation
- **Custom casts** - Create your own type casts
- **TypeScript generation** - Generate TypeScript interfaces
- **IDE support** - PHPStorm, VS Code

### Framework Integration

- **Laravel:** Eloquent, validation, Artisan commands
- **Symfony:** Doctrine, security, console commands
- **Plain PHP:** Works without any framework

### Performance

- **Validation caching** - 198x faster
- **Cast instance caching** - Reuse cast instances
- **Optimized reflection** - Minimal overhead
- **Lazy evaluation** - Only compute when needed

## Quick Start

### Installation

```bash
composer require event4u/data-helpers
```

### Create Your First Dto

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        public readonly ?int $age = null,
    ) {}
}

// Create from array
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Access properties
echo $dto->name;  // 'John Doe'

// Convert to array
$array = $dto->toArray();

// Convert to JSON
$json = json_encode($dto);
```


## Next Steps

### For Beginners

1. [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Learn how to create Dtos
2. [Type Casting](/data-helpers/simple-dto/type-casting/) - Automatic type conversion
3. [Validation](/data-helpers/simple-dto/validation/) - Validate your data

### For Experienced Developers

1. [Conditional Properties](/data-helpers/simple-dto/conditional-properties/) - Advanced visibility control
2. [Lazy Properties](/data-helpers/simple-dto/lazy-properties/) - Defer expensive operations
3. [Computed Properties](/data-helpers/simple-dto/computed-properties/) - Calculate values on-the-fly

### Advanced Topics

1. [Collections](/data-helpers/simple-dto/collections/) - Work with collections of Dtos
2. [Nested Dtos](/data-helpers/simple-dto/nested-dtos/) - Complex nested structures
3. [Security & Visibility](/data-helpers/simple-dto/security-visibility/) - Control data exposure

## See Also

- [DataMapper](/data-helpers/main-classes/data-mapper/) - Transform data with Fluent API
- [DataAccessor](/data-helpers/main-classes/data-accessor/) - Read nested data
- [DataFilter](/data-helpers/main-classes/data-filter/) - Query and filter data
