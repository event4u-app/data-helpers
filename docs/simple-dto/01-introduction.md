# Introduction to SimpleDTO

**SimpleDTO** is a powerful, framework-agnostic Data Transfer Object (DTO) library for PHP 8.2+ that makes working with structured data simple, type-safe, and performant.

---

## 🎯 What is a DTO?

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

---

## ✨ Why SimpleDTO?

### 1. **Framework Agnostic**
Works with Laravel, Symfony, and plain PHP. No framework lock-in.

```php
// Laravel
$dto = UserDTO::fromRequest($request);

// Symfony
$dto = UserDTO::fromArray($request->request->all());

// Plain PHP
$dto = UserDTO::fromArray($_POST);
```

### 2. **Type Safety**
Full PHP 8.2+ type support with automatic casting.

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly Carbon $createdAt,
        public readonly Status $status,  // Enum
        public readonly ?array $tags = null,
    ) {}
}
```

### 3. **Automatic Validation**
Infer validation rules from types and attributes.

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// Automatic validation
$dto = UserDTO::validateAndCreate($request->all());
```

### 4. **Conditional Properties**
18 conditional attributes for dynamic data inclusion.

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenAuth]  // Only when authenticated
        public readonly ?string $email = null,
        
        #[WhenCan('view-admin')]  // Only with permission
        public readonly ?array $adminData = null,
    ) {}
}
```

### 5. **Performance**
3x faster than Spatie Laravel Data with built-in caching.

```
SimpleDTO:    914,000 instances/sec
Spatie Data:  300,000 instances/sec
```

### 6. **Zero Dependencies**
Core library has zero dependencies. Framework integrations are optional.

---

## 🚀 Key Features

### Core Features
- ✅ Immutable DTOs with readonly properties
- ✅ Automatic type casting (20+ built-in casts)
- ✅ Validation with auto rule inferring
- ✅ Property mapping (MapFrom, MapTo)
- ✅ Multiple serialization formats (JSON, XML, YAML, CSV)
- ✅ Nested DTOs and collections
- ✅ Computed properties

### Advanced Features
- ✅ **18 Conditional Attributes** (9x more than Spatie)
- ✅ **with() Method** for dynamic properties
- ✅ **Context-Based Conditions** with withContext()
- ✅ Lazy properties and lazy loading
- ✅ Custom casts and transformers
- ✅ TypeScript generation
- ✅ IDE support (PHPStorm, VS Code)

### Framework Integration
- ✅ **Laravel:** Eloquent, validation, Artisan commands
- ✅ **Symfony:** Doctrine, security, console commands
- ✅ **Plain PHP:** Works without any framework

### Performance
- ✅ Validation caching (198x faster)
- ✅ Cast instance caching
- ✅ Optimized reflection
- ✅ Lazy evaluation

---

## 📊 Comparison with Spatie Laravel Data

| Feature | SimpleDTO | Spatie Data |
|---------|-----------|-------------|
| **Framework** | Agnostic | Laravel-only |
| **Conditional Attributes** | 18 | 2 |
| **Performance** | 914k/sec | 300k/sec |
| **Tests** | 2900+ | ~500 |
| **Symfony Support** | ✅ | ❌ |
| **Doctrine Support** | ✅ | ❌ |
| **with() Method** | ✅ | ❌ |
| **Context-Based** | ✅ | ❌ |

[Full comparison →](31-comparison-with-spatie.md)

---

## 🎯 Use Cases

### 1. API Resources
Replace Laravel API Resources with type-safe DTOs.

```php
class UserResource extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
    ) {}
}

// Controller
return UserResource::fromModel($user);
```

### 2. Form Requests
Replace Laravel Form Requests with validated DTOs.

```php
#[ValidateRequest]
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email, Unique('users')]
        public readonly string $email,
        
        #[Required, Min(8), Confirmed]
        public readonly string $password,
    ) {}
}

// Controller - automatic validation
public function store(CreateUserDTO $dto)
{
    $user = User::create($dto->toArray());
    return response()->json($user, 201);
}
```

### 3. Data Transformation
Transform data between different formats.

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('order_id')]
        public readonly int $id,
        
        #[MapFrom('customer.name')]
        public readonly string $customerName,
        
        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $orderDate,
    ) {}
}

$dto = OrderDTO::fromArray($apiResponse);
$array = $dto->toArray();  // Transformed data
```

### 4. Multi-Framework Projects
Use the same DTOs across Laravel and Symfony.

```php
// Works in both Laravel and Symfony
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Laravel
$dto = ProductDTO::fromRequest($request);

// Symfony
$dto = ProductDTO::fromArray($request->request->all());
```

---

## 🏗️ Architecture

SimpleDTO uses a trait-based architecture for modularity:

```
SimpleDTOTrait (Orchestrator)
├── SimpleDTOCastsTrait (Type casting)
├── SimpleDTOValidationTrait (Validation)
├── SimpleDTOMappingTrait (Property mapping)
├── SimpleDTOVisibilityTrait (Hidden/visible)
├── SimpleDTOComputedTrait (Computed properties)
├── SimpleDTOConditionalTrait (Conditional properties)
└── SimpleDTOSerializationTrait (Serialization)
```

Each trait is:
- ✅ Under 400 lines
- ✅ Single responsibility
- ✅ Independently testable
- ✅ Optional (use only what you need)

---

## 📚 Next Steps

### For Beginners
1. [Installation](02-installation.md) - Install SimpleDTO
2. [Quick Start](03-quick-start.md) - Your first DTO
3. [Basic Usage](04-basic-usage.md) - Core concepts

### For Experienced Developers
1. [Conditional Properties](10-conditional-properties.md) - Advanced features
2. [Laravel Integration](17-laravel-integration.md) - Laravel-specific
3. [Migration from Spatie](30-migration-from-spatie.md) - Switch from Spatie

### For Framework Users
- **Laravel:** [Laravel Integration](17-laravel-integration.md)
- **Symfony:** [Symfony Integration](18-symfony-integration.md)
- **Plain PHP:** [Plain PHP Usage](19-plain-php.md)

---

**Next:** [Installation](02-installation.md) - Learn how to install and configure SimpleDTO.

