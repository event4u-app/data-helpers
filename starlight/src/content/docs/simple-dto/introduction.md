---
title: Introduction to SimpleDto
description: Framework-agnostic DTOs with deep framework integration – get the power without the lock-in
---

SimpleDto is a powerful, **framework-agnostic** Data Transfer Object (Dto) library for PHP 8.2+ that makes working with structured data simple, type-safe and performant. Works standalone in **Pure PHP** or with **deep integration** for Laravel and Symfony – without framework lock-in.

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

### 🎯 Framework-Agnostic + Deep Integration

**The best of both worlds:** Use SimpleDto as a standalone library in pure PHP, or leverage deep framework integration for Laravel and Symfony – without framework lock-in.

#### Pure PHP - Zero Dependencies

```php
// Works everywhere - no framework needed
$dto = UserDto::fromArray($_POST);
$dto = UserDto::fromArray($apiResponse);
$dto = UserDto::fromArray(json_decode($json, true));
```

#### Optional Deep Integration

```php
// Laravel - Automatic controller injection & Eloquent integration
class UserController extends Controller
{
    public function store(UserDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled from request
        $user = User::create($dto->toArray());
        return response()->json($user, 201);
    }
}

$user = User::find(1);
$dto = UserDto::fromModel($user);     // From Eloquent Model (automatically available)
$newUser = $dto->toModel(User::class); // To Eloquent Model
// Note: Methods throw BadMethodCallException if Laravel is not installed

// Symfony - Automatic controller injection & Doctrine integration
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function create(UserDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled from request
        $user = $dto->toEntity(User::class);
        $this->entityManager->persist($user);
        return $this->json($user, 201);
    }
}

$user = $this->entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);        // From Doctrine Entity (automatically available)
$newUser = $dto->toEntity(User::class);   // To Doctrine Entity
// Note: Methods throw BadMethodCallException if Doctrine is not installed
```

**Key Benefits:**
- ✅ **No Framework Lock-In** - Core code works everywhere
- ✅ **Optional Integration** - Add framework features when needed
- ✅ **Portable** - Move between frameworks without code changes
- ✅ **Zero Dependencies** - No required dependencies

### Type Safety

Full PHP 8.2+ type support with automatic type casting by default.

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

// Default: Automatic type casting enabled
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,        // Auto-cast from any type
        public readonly float $price,        // Auto-cast from string/int
        public readonly Carbon $createdAt,   // Auto-cast from string/timestamp
        public readonly Status $status,      // Enum (always auto-cast)
        public readonly AddressDto $address, // Auto-cast nested DTO
    ) {}
}

// Disable automatic casting for better performance
#[NoCasts]
class StrictProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,        // Must be string, no conversion
        public readonly float $price,        // Must be float, no conversion
        public readonly AddressDto $address, // Must be AddressDto instance
    ) {}
}
```

**Note:** SimpleDto automatically casts ALL types by default (native types, nested DTOs, DataCollections, DateTime). Use `#[NoCasts]` to disable automatic casting for better performance.

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

Built-in collection support.

```php
$users = UserDto::collection($userArray);
// DataCollection of UserDto instances

// Access collection methods
$filtered = $users->filter(fn($user) => $user->age >= 18);
$mapped = $users->map(fn($user) => $user->name);
$count = $users->count();
```

## Key Features

### Core Features

- **Immutable by design** - Use readonly properties
- **Type-safe** - Full PHP type hinting support
- **Multi-format serialization** - JSON, XML, YAML, CSV, and custom formats
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

**Framework-Agnostic Core + Optional Deep Integration:**

#### Pure PHP (Zero Dependencies)
- ✅ **Multi-format Support** - JSON, XML, YAML, CSV, and custom formats
- ✅ **No Framework Required** - Standalone usage
- ✅ **Portable** - Move between frameworks

#### Laravel (Optional)
- ✅ **Controller Injection** - Automatic validation & filling
- ✅ **Eloquent Integration** - fromModel(), toModel()
- ✅ **Laravel Attributes** - WhenAuth, WhenCan, WhenRole
- ✅ **Artisan Commands** - make:dto, dto:typescript

#### Symfony (Optional)
- ✅ **Controller Injection** - Automatic validation & filling
- ✅ **Doctrine Integration** - fromEntity(), toEntity()
- ✅ **Symfony Attributes** - WhenGranted, WhenSymfonyRole
- ✅ **Console Commands** - make:dto, dto:typescript

**The Power:** Get framework-specific features when you need them, without framework dependencies in your core code.

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
