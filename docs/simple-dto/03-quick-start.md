# Quick Start Guide

Get started with SimpleDTO in 5 minutes! This guide will show you the basics.

---

## 🎯 Your First DTO

### Step 1: Create a DTO Class

```php
<?php

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}
```

### Step 2: Create an Instance

```php
// From array
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Or using constructor
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);
```

### Step 3: Use the DTO

```php
// Access properties
echo $dto->name;  // John Doe
echo $dto->email; // john@example.com
echo $dto->age;   // 30

// Convert to array
$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]

// Convert to JSON
$json = $dto->toJson();
// {"name":"John Doe","email":"john@example.com","age":30}
```

That's it! You've created your first DTO. 🎉

---

## 🚀 Adding Validation

### Step 1: Add Validation Attributes

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Between(18, 120)]
        public readonly int $age,
    ) {}
}
```

### Step 2: Validate and Create

```php
// This will validate automatically
$dto = UserDTO::validateAndCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Invalid data throws ValidationException
try {
    $dto = UserDTO::validateAndCreate([
        'name' => 'John',
        'email' => 'invalid-email',  // ❌ Invalid email
        'age' => 15,                  // ❌ Too young
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage();
}
```

---

## 🎨 Adding Type Casting

### Step 1: Add Cast Attributes

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDTO\Casts\EnumCast;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        
        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $createdAt,
        
        #[Cast(EnumCast::class)]
        public readonly Status $status,
    ) {}
}
```

### Step 2: Use with Automatic Casting

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'createdAt' => '2024-01-15',      // String → Carbon
    'status' => 'active',              // String → Status enum
]);

echo $dto->createdAt->format('F j, Y');  // January 15, 2024
echo $dto->status->value;                 // active
```

---

## 🔒 Adding Conditional Properties

### Step 1: Add Conditional Attributes

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]  // Only when authenticated
        public readonly ?string $email = null,
        
        #[WhenCan('view-admin')]  // Only with permission
        public readonly ?array $adminData = null,
    ) {}
}
```

### Step 2: Use with Context

```php
// Without authentication
$dto = UserDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'adminData' => ['role' => 'admin'],
]);

$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe']
// email and adminData are excluded!

// With authentication
$user = auth()->user();
$array = $dto->withContext(['user' => $user])->toArray();
// ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 🗺️ Adding Property Mapping

### Step 1: Add Mapping Attributes

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')]
        public readonly int $id,
        
        #[MapFrom('full_name')]
        public readonly string $name,
        
        #[MapFrom('email_address')]
        #[MapTo('email')]
        public readonly string $email,
    ) {}
}
```

### Step 2: Use with Different Input/Output Names

```php
// Input with different names
$dto = UserDTO::fromArray([
    'user_id' => 1,
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);

// Output with mapped names
$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 📦 Working with Collections

### Step 1: Create a Collection

```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$users = DataCollection::make([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
    ['name' => 'Bob', 'age' => 35],
], UserDTO::class);
```

### Step 2: Use Collection Methods

```php
// Filter
$adults = $users->filter(fn($user) => $user->age >= 18);

// Map
$names = $users->map(fn($user) => $user->name);

// Sort
$sorted = $users->sortBy('age');

// Paginate
$paginated = $users->paginate(perPage: 10, page: 1);

// Convert to array
$array = $users->toArray();
```

---

## 🎯 Real-World Example: API Endpoint

### Complete Example

```php
<?php

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Between(18, 120)]
        public readonly int $age,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
    ) {}
}

// Laravel Controller
class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        $dtos = DataCollection::make($users, UserDTO::class);
        
        return response()->json($dtos);
    }
    
    public function store(Request $request)
    {
        // Validate and create DTO
        $dto = UserDTO::validateAndCreate($request->all());
        
        // Create user from DTO
        $user = User::create($dto->toArray());
        
        // Return DTO
        return response()->json(
            UserDTO::fromModel($user),
            201
        );
    }
    
    public function show(User $user)
    {
        return response()->json(
            UserDTO::fromModel($user)
        );
    }
}
```

---

## 📚 What's Next?

### Learn More Features

1. [Basic Usage](04-basic-usage.md) - Core concepts in detail
2. [Type Casting](06-type-casting.md) - All 20+ built-in casts
3. [Validation](07-validation.md) - Advanced validation
4. [Conditional Properties](10-conditional-properties.md) - All 18 attributes

### Framework Integration

- **Laravel:** [Laravel Integration](17-laravel-integration.md)
- **Symfony:** [Symfony Integration](18-symfony-integration.md)
- **Plain PHP:** [Plain PHP Usage](19-plain-php.md)

### Advanced Topics

1. [with() Method](11-with-method.md) - Dynamic properties
2. [Context-Based Conditions](12-context-based-conditions.md) - Advanced conditions
3. [Collections](15-collections.md) - Working with collections
4. [TypeScript Generation](23-typescript-generation.md) - Generate types

---

## 💡 Quick Tips

### Tip 1: Use Named Arguments

```php
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);
```

### Tip 2: Chain Methods

```php
$json = UserDTO::fromArray($data)
    ->withContext(['user' => $user])
    ->with('timestamp', now())
    ->toJson();
```

### Tip 3: Use Type Hints

```php
public function processUser(UserDTO $dto): array
{
    return $dto->toArray();
}
```

### Tip 4: Leverage IDE Autocomplete

DTOs provide full IDE autocomplete for properties and methods!

---

**Previous:** [Installation](02-installation.md)  
**Next:** [Basic Usage](04-basic-usage.md)

