# Best Practices

Comprehensive guide to best practices for using SimpleDTO effectively.

---

## 🎯 Overview

Follow these best practices to get the most out of SimpleDTO:

- ✅ **Naming Conventions** - Clear and consistent names
- ✅ **Structure** - Organize DTOs effectively
- ✅ **Validation** - Validate early and cache
- ✅ **Performance** - Optimize for speed
- ✅ **Security** - Protect sensitive data
- ✅ **Testing** - Test DTOs thoroughly
- ✅ **Documentation** - Document complex DTOs

---

## 📝 Naming Conventions

### DTO Class Names

```php
// ✅ Good - descriptive and consistent
CreateUserDTO
UpdateUserDTO
UserResourceDTO
UserListItemDTO

// ❌ Bad - unclear or inconsistent
UserDTO
User
UserData
CreateUser
```

### Property Names

```php
// ✅ Good - camelCase, descriptive
public readonly string $firstName
public readonly string $emailAddress
public readonly Carbon $createdAt

// ❌ Bad - unclear or inconsistent
public readonly string $fn
public readonly string $email_address
public readonly Carbon $created
```

### Method Names

```php
// ✅ Good - clear intent
public function getFullName(): string
public function isActive(): bool
public function hasPermission(string $permission): bool

// ❌ Bad - unclear
public function name(): string
public function active(): bool
public function permission(string $permission): bool
```

---

## 🗂️ Organization

### Directory Structure

```
app/DTO/
├── Api/
│   ├── Requests/
│   │   ├── CreateUserDTO.php
│   │   └── UpdateUserDTO.php
│   └── Resources/
│       ├── UserResourceDTO.php
│       └── UserListItemDTO.php
├── Internal/
│   ├── UserDTO.php
│   └── OrderDTO.php
└── Shared/
    ├── AddressDTO.php
    └── PaginationDTO.php
```

### Namespace Organization

```php
// ✅ Good - organized by feature
namespace App\DTO\User;
namespace App\DTO\Order;
namespace App\DTO\Payment;

// ❌ Bad - flat structure
namespace App\DTO;
```

---

## ✅ Validation

### 1. Validate Early

```php
// ✅ Good - validate at entry point
public function store(CreateUserDTO $dto)
{
    $user = User::create($dto->toArray());
}

// ❌ Bad - validate late
public function store(Request $request)
{
    $user = User::create($request->all());
    // Validation happens in model or later
}
```

### 2. Use Specific Attributes

```php
// ✅ Good - specific validation
#[Required, Email, Unique('users', 'email')]
public readonly string $email

// ❌ Bad - generic validation
#[Required]
public readonly string $email
```

### 3. Cache Validation Rules

```bash
# Always cache in production
php artisan dto:cache
```

### 4. Combine Related Validations

```php
// ✅ Good - combined validation
#[Required, StringType, Min(8), Max(255), Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
public readonly string $password

// ❌ Bad - separate DTOs for each rule
```

---

## 🚀 Performance

### 1. Use Lazy Loading

```php
// ✅ Good - lazy loaded
#[Lazy]
public readonly ?array $posts = null

// ❌ Bad - always loaded
public readonly array $posts
```

### 2. Avoid Deep Nesting

```php
// ✅ Good - shallow nesting (2-3 levels)
$dto->address->city

// ❌ Bad - deep nesting
$dto->company->department->team->manager->address->city
```

### 3. Use Batch Operations

```php
// ✅ Good - batch processing
$dtos = DataCollection::make($users, UserDTO::class);

// ❌ Bad - individual processing
$dtos = array_map(fn($user) => UserDTO::fromModel($user), $users);
```

### 4. Cache Expensive Operations

```php
// ✅ Good - cached
return Cache::remember('users', 300, fn() =>
    DataCollection::make(User::all(), UserDTO::class)
);

// ❌ Bad - no caching
return DataCollection::make(User::all(), UserDTO::class);
```

---

## 🔒 Security

### 1. Hide Sensitive Data

```php
// ✅ Good - sensitive data hidden
#[Hidden]
public readonly string $password

#[Hidden]
public readonly ?string $apiToken = null

// ❌ Bad - sensitive data exposed
public readonly string $password
```

### 2. Use Conditional Visibility

```php
// ✅ Good - conditional visibility
#[WhenAuth]
public readonly ?string $email = null

#[WhenRole('admin')]
public readonly ?array $adminData = null

// ❌ Bad - always visible
public readonly string $email
public readonly array $adminData
```

### 3. Encrypt Sensitive Data

```php
// ✅ Good - encrypted
#[Cast(EncryptedCast::class)]
public readonly string $ssn

// ❌ Bad - plain text
public readonly string $ssn
```

### 4. Validate Input

```php
// ✅ Good - validated input
$dto = CreateUserDTO::validateAndCreate($request->all());

// ❌ Bad - unvalidated input
$dto = CreateUserDTO::fromArray($request->all());
```

---

## 🎨 Code Style

### 1. Use Type Hints

```php
// ✅ Good - type hinted
public readonly string $name
public readonly int $age
public readonly ?string $middleName = null

// ❌ Bad - no type hints
public readonly $name
public readonly $age
```

### 2. Use Readonly Properties

```php
// ✅ Good - readonly
public readonly string $name

// ❌ Bad - mutable
public string $name
```

### 3. Document Complex Types

```php
// ✅ Good - documented
/** @var UserDTO[] */
public readonly array $users

/** @var array<string, mixed> */
public readonly array $metadata

// ❌ Bad - undocumented
public readonly array $users
public readonly array $metadata
```

### 4. Use Strict Types

```php
// ✅ Good - strict types
<?php

declare(strict_types=1);

namespace App\DTO;

// ❌ Bad - no strict types
<?php

namespace App\DTO;
```

---

## 🧪 Testing

### 1. Test DTO Creation

```php
public function test_creates_dto_from_array(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $this->assertEquals(1, $dto->id);
    $this->assertEquals('John Doe', $dto->name);
    $this->assertEquals('john@example.com', $dto->email);
}
```

### 2. Test Validation

```php
public function test_validates_required_fields(): void
{
    $this->expectException(ValidationException::class);
    
    CreateUserDTO::validateAndCreate([
        'name' => 'John Doe',
        // email is missing
    ]);
}
```

### 3. Test Serialization

```php
public function test_serializes_to_array(): void
{
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John Doe',
    ]);
    
    $array = $dto->toArray();
    
    $this->assertArrayHasKey('id', $array);
    $this->assertArrayHasKey('name', $array);
}
```

### 4. Test Conditional Properties

```php
public function test_hides_email_when_not_authenticated(): void
{
    Auth::logout();
    
    $dto = UserDTO::fromArray([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $array = $dto->toArray();
    
    $this->assertArrayNotHasKey('email', $array);
}
```

---

## 📚 Documentation

### 1. Document DTO Purpose

```php
/**
 * User data transfer object for API responses
 * 
 * This DTO is used to serialize user data for API endpoints.
 * It includes conditional properties based on authentication.
 */
class UserResourceDTO extends SimpleDTO
{
    // ...
}
```

### 2. Document Complex Properties

```php
/**
 * Order items with product details
 * 
 * @var array<int, array{product: ProductDTO, quantity: int, price: float}>
 */
public readonly array $items
```

### 3. Document Custom Methods

```php
/**
 * Get user's full name
 * 
 * Combines first name and last name with a space.
 * 
 * @return string The user's full name
 */
public function getFullName(): string
{
    return $this->firstName . ' ' . $this->lastName;
}
```

---

## 🎯 Common Patterns

### 1. Request/Response Separation

```php
// ✅ Good - separate DTOs
class CreateUserDTO extends SimpleDTO { /* ... */ }
class UserResourceDTO extends SimpleDTO { /* ... */ }

// ❌ Bad - same DTO for both
class UserDTO extends SimpleDTO { /* ... */ }
```

### 2. Factory Methods

```php
// ✅ Good - factory methods
class UserDTO extends SimpleDTO
{
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
        );
    }
    
    public static function fromRequest(Request $request): self
    {
        return self::validateAndCreate($request->all());
    }
}
```

### 3. Computed Properties

```php
// ✅ Good - computed properties
class OrderDTO extends SimpleDTO
{
    #[Computed]
    public function total(): float
    {
        return array_sum(array_column($this->items, 'price'));
    }
}
```

### 4. Nested DTOs

```php
// ✅ Good - nested DTOs
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly CustomerDTO $customer,
        /** @var OrderItemDTO[] */
        public readonly array $items,
    ) {}
}
```

---

## 🚫 Anti-Patterns

### 1. Mutable DTOs

```php
// ❌ Bad - mutable properties
class UserDTO extends SimpleDTO
{
    public string $name; // Not readonly
}
```

### 2. Business Logic in DTOs

```php
// ❌ Bad - business logic
class UserDTO extends SimpleDTO
{
    public function sendEmail(): void
    {
        Mail::to($this->email)->send(new WelcomeEmail());
    }
}
```

### 3. Database Queries in DTOs

```php
// ❌ Bad - database queries
class UserDTO extends SimpleDTO
{
    public function getPosts(): array
    {
        return Post::where('user_id', $this->id)->get();
    }
}
```

### 4. Too Many Properties

```php
// ❌ Bad - too many properties (>20)
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        // ... 30 more properties
    ) {}
}

// ✅ Good - split into multiple DTOs
class UserDTO extends SimpleDTO { /* ... */ }
class UserProfileDTO extends SimpleDTO { /* ... */ }
class UserSettingsDTO extends SimpleDTO { /* ... */ }
```

---

## 📋 Checklist

### Before Committing
- [ ] All properties are readonly
- [ ] Type hints are used
- [ ] Validation attributes are added
- [ ] Sensitive data is hidden
- [ ] Complex types are documented
- [ ] Tests are written
- [ ] Code is formatted

### Before Deploying
- [ ] Validation rules are cached
- [ ] TypeScript types are generated
- [ ] Performance is tested
- [ ] Security is reviewed
- [ ] Documentation is updated

---

## 📚 Next Steps

1. [Performance](27-performance.md) - Performance optimization
2. [Caching](28-caching.md) - Caching strategies
3. [Security & Visibility](22-security-visibility.md) - Security features
4. [Testing DTOs](40-testing-dtos.md) - Testing guide

---

**Previous:** [Caching](28-caching.md)  
**Next:** [Migration from Spatie](30-migration-from-spatie.md)

