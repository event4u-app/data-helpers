---
title: Introduction to SimpleDTO
description: Powerful, framework-agnostic Data Transfer Objects for PHP 8.2+
---

SimpleDTO is a powerful, framework-agnostic Data Transfer Object (DTO) library for PHP 8.2+ that makes working with structured data simple, type-safe, and performant.

## What is a DTO?

A **Data Transfer Object (DTO)** is a design pattern used to transfer data between different parts of an application. DTOs are simple objects that:

- Contain only data (no business logic)
- Are immutable (readonly properties)
- Are type-safe (PHP 8.2+ types)
- Can be easily serialized/deserialized

### Example: Without DTO

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

### Example: With SimpleDTO

```php
// DTO - clean and type-safe
class UserDTO extends SimpleDTO
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
public function store(UserDTO $dto)
{
    $user = User::create($dto->toArray());
    return response()->json($dto);
}
```

## Why SimpleDTO?

### Framework Agnostic

Works with Laravel, Symfony, and plain PHP. No framework lock-in.

```php
// Laravel
$dto = UserDTO::fromRequest($request);

// Symfony
$dto = UserDTO::fromArray($request->request->all());

// Plain PHP
$dto = UserDTO::fromArray($_POST);
```

### Type Safety

Full PHP 8.2+ type support with automatic casting.

```php
class ProductDTO extends SimpleDTO
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
class UserDTO extends SimpleDTO
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
$dto = UserDTO::validateAndCreate($data);
```

### Conditional Properties

18 conditional attributes for dynamic visibility.

```php
class UserDTO extends SimpleDTO
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
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('full_name')]
        public readonly string $name,

        #[MapFrom('email_address')]
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);
```


### Type Casting

20+ built-in casts for automatic type conversion.

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $orderDate,

        #[Cast(EnumCast::class, enum: Status::class)]
        public readonly Status $status,

        #[Cast(CollectionCast::class, of: OrderItemDTO::class)]
        public readonly DataCollection $items,
    ) {}
}
```

### Lazy Properties

Defer expensive operations until needed.

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,  // Only loaded when accessed
    ) {}
}

$dto = UserDTO::fromModel($user);
// Posts are NOT loaded yet

$posts = $dto->posts;
// Posts are loaded NOW
```

### Computed Properties

Calculate values on-the-fly.

```php
class ProductDTO extends SimpleDTO
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

$dto = new ProductDTO(price: 100, taxRate: 0.19);
echo $dto->priceWithTax();  // 119.0

$array = $dto->toArray();
// ['price' => 100, 'taxRate' => 0.19, 'priceWithTax' => 119.0]
```

### Collections

Built-in collection support with pagination.

```php
$users = UserDTO::collection($userArray);
// DataCollection of UserDTO instances

$paginated = UserDTO::paginatedCollection($users, page: 1, perPage: 10);
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
- **Nested DTOs** - Support for complex structures
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

### Create Your First DTO

```php
use Event4u\DataHelpers\SimpleDTO;
use Event4u\DataHelpers\SimpleDTO\Attributes\Required;
use Event4u\DataHelpers\SimpleDTO\Attributes\Email;

class UserDTO extends SimpleDTO
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
$dto = UserDTO::fromArray([
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

1. [Creating DTOs](/simple-dto/creating-dtos/) - Learn how to create DTOs
2. [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
3. [Validation](/simple-dto/validation/) - Validate your data

### For Experienced Developers

1. [Conditional Properties](/simple-dto/conditional-properties/) - Advanced visibility control
2. [Lazy Properties](/simple-dto/lazy-properties/) - Defer expensive operations
3. [Computed Properties](/simple-dto/computed-properties/) - Calculate values on-the-fly

### Advanced Topics

1. [Collections](/simple-dto/collections/) - Work with collections of DTOs
2. [Nested DTOs](/simple-dto/nested-dtos/) - Complex nested structures
3. [Security & Visibility](/simple-dto/security-visibility/) - Control data exposure

## See Also

- [DataMapper](/main-classes/data-mapper/) - Transform data with Fluent API
- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataFilter](/main-classes/data-filter/) - Query and filter data
