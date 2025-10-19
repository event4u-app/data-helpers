# Property Mapping

Learn how to map property names between different formats using MapFrom, MapTo, and transformers.

---

## 🎯 What is Property Mapping?

Property mapping allows you to use different property names for input and output:

```php
// Input: { "user_id": 1, "full_name": "John Doe" }
// DTO:   { id: 1, name: "John Doe" }
// Output: { "id": 1, "name": "John Doe" }
```

---

## 📥 MapFrom - Input Mapping

### Basic MapFrom

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')]
        public readonly int $id,
        
        #[MapFrom('full_name')]
        public readonly string $name,
        
        #[MapFrom('email_address')]
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'user_id' => 1,
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);

echo $dto->id;    // 1
echo $dto->name;  // John Doe
echo $dto->email; // john@example.com
```

### Nested Property Mapping

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[MapFrom('profile.first_name')]
        public readonly string $firstName,
        
        #[MapFrom('profile.last_name')]
        public readonly string $lastName,
        
        #[MapFrom('contact.email')]
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'id' => 1,
    'profile' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
    ],
    'contact' => [
        'email' => 'john@example.com',
    ],
]);
```

### Array Index Mapping

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('items.0.name')]
        public readonly string $firstItemName,
        
        #[MapFrom('items.0.price')]
        public readonly float $firstItemPrice,
    ) {}
}

$dto = OrderDTO::fromArray([
    'items' => [
        ['name' => 'Widget', 'price' => 9.99],
        ['name' => 'Gadget', 'price' => 19.99],
    ],
]);
```

---

## 📤 MapTo - Output Mapping

### Basic MapTo

```php
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapTo('user_id')]
        public readonly int $id,
        
        #[MapTo('full_name')]
        public readonly string $name,
        
        #[MapTo('email_address')]
        public readonly string $email,
    ) {}
}

$dto = new UserDTO(
    id: 1,
    name: 'John Doe',
    email: 'john@example.com'
);

$array = $dto->toArray();
// [
//     'user_id' => 1,
//     'full_name' => 'John Doe',
//     'email_address' => 'john@example.com',
// ]
```

### Nested Output Mapping

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[MapTo('profile.first_name')]
        public readonly string $firstName,
        
        #[MapTo('profile.last_name')]
        public readonly string $lastName,
        
        #[MapTo('contact.email')]
        public readonly string $email,
    ) {}
}

$dto = new UserDTO(
    id: 1,
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com'
);

$array = $dto->toArray();
// [
//     'id' => 1,
//     'profile' => [
//         'first_name' => 'John',
//         'last_name' => 'Doe',
//     ],
//     'contact' => [
//         'email' => 'john@example.com',
//     ],
// ]
```

---

## 🔄 MapFrom + MapTo - Bidirectional Mapping

### Different Input and Output Names

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')]
        #[MapTo('id')]
        public readonly int $id,
        
        #[MapFrom('full_name')]
        #[MapTo('name')]
        public readonly string $name,
    ) {}
}

// Input
$dto = UserDTO::fromArray([
    'user_id' => 1,
    'full_name' => 'John Doe',
]);

// Output
$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe']
```

---

## 🎨 Transformers

### Basic Transformer

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Transform;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Transform(fn($value) => strtoupper($value))]
        public readonly string $name,
        
        #[Transform(fn($value) => strtolower($value))]
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'john doe',
    'email' => 'JOHN@EXAMPLE.COM',
]);

echo $dto->name;  // JOHN DOE
echo $dto->email; // john@example.com
```

### Transformer with Multiple Operations

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Transform(fn($value) => trim(strtolower($value)))]
        public readonly string $slug,
        
        #[Transform(fn($value) => round($value, 2))]
        public readonly float $price,
    ) {}
}
```

### Transformer Classes

```php
use event4u\DataHelpers\SimpleDTO\Contracts\Transformer;

class UpperCaseTransformer implements Transformer
{
    public function transform(mixed $value): mixed
    {
        return strtoupper($value);
    }
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Transform(UpperCaseTransformer::class)]
        public readonly string $name,
    ) {}
}
```

---

## 🗺️ Complex Mapping Examples

### API Response Mapping

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('data.id')]
        public readonly int $id,
        
        #[MapFrom('data.attributes.name')]
        public readonly string $name,
        
        #[MapFrom('data.attributes.email')]
        public readonly string $email,
        
        #[MapFrom('data.relationships.posts.data')]
        public readonly array $posts,
    ) {}
}

// JSON API format
$dto = UserDTO::fromArray([
    'data' => [
        'id' => 1,
        'type' => 'users',
        'attributes' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
        'relationships' => [
            'posts' => [
                'data' => [
                    ['id' => 1, 'type' => 'posts'],
                    ['id' => 2, 'type' => 'posts'],
                ],
            ],
        ],
    ],
]);
```

### Database to API Mapping

```php
class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('id')]
        #[MapTo('userId')]
        public readonly int $id,
        
        #[MapFrom('first_name')]
        #[MapTo('firstName')]
        public readonly string $firstName,
        
        #[MapFrom('last_name')]
        #[MapTo('lastName')]
        public readonly string $lastName,
        
        #[MapFrom('created_at')]
        #[MapTo('createdAt')]
        #[Transform(fn($value) => $value->toIso8601String())]
        public readonly Carbon $createdAt,
    ) {}
}

// From database (snake_case)
$dto = UserResourceDTO::fromModel($user);

// To API (camelCase)
$json = $dto->toJson();
```

---

## 🎯 Real-World Examples

### Example 1: External API Integration

```php
class GitHubUserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('id')]
        public readonly int $id,
        
        #[MapFrom('login')]
        public readonly string $username,
        
        #[MapFrom('avatar_url')]
        public readonly string $avatarUrl,
        
        #[MapFrom('html_url')]
        public readonly string $profileUrl,
        
        #[MapFrom('public_repos')]
        public readonly int $repositoryCount,
    ) {}
}

$response = Http::get('https://api.github.com/users/octocat');
$dto = GitHubUserDTO::fromArray($response->json());
```

### Example 2: Form to Database

```php
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('firstName')]
        #[MapTo('first_name')]
        public readonly string $firstName,
        
        #[MapFrom('lastName')]
        #[MapTo('last_name')]
        public readonly string $lastName,
        
        #[MapFrom('email')]
        #[MapTo('email')]
        public readonly string $email,
        
        #[MapFrom('password')]
        #[MapTo('password')]
        #[Transform(fn($value) => Hash::make($value))]
        public readonly string $password,
    ) {}
}

// From form (camelCase)
$dto = CreateUserDTO::fromRequest($request);

// To database (snake_case)
$user = User::create($dto->toArray());
```

### Example 3: Multi-Source Mapping

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('order.id')]
        public readonly int $orderId,
        
        #[MapFrom('customer.name')]
        public readonly string $customerName,
        
        #[MapFrom('customer.email')]
        public readonly string $customerEmail,
        
        #[MapFrom('items')]
        public readonly array $items,
        
        #[MapFrom('payment.method')]
        public readonly string $paymentMethod,
    ) {}
}

$dto = OrderDTO::fromArray([
    'order' => ['id' => 123],
    'customer' => ['name' => 'John', 'email' => 'john@example.com'],
    'items' => [/* ... */],
    'payment' => ['method' => 'credit_card'],
]);
```

---

## 🔍 Debugging Mapping

### Get Mapping Configuration

```php
// Get all mappings for a DTO
$mappings = UserDTO::getMappings();
print_r($mappings);

// Output:
// [
//     'id' => ['from' => 'user_id', 'to' => 'id'],
//     'name' => ['from' => 'full_name', 'to' => 'name'],
// ]
```

### Validate Mapping

```php
// Check if a property has mapping
$hasMapping = UserDTO::hasMapping('id');

// Get source property name
$sourceName = UserDTO::getSourceProperty('id'); // 'user_id'

// Get target property name
$targetName = UserDTO::getTargetProperty('id'); // 'id'
```

---

## 💡 Best Practices

### 1. Use Consistent Naming Conventions

```php
// ✅ Good - consistent API naming
#[MapFrom('user_id')]
#[MapTo('userId')]
public readonly int $id;

// ❌ Bad - inconsistent naming
#[MapFrom('user_id')]
#[MapTo('ID')]
public readonly int $id;
```

### 2. Document Complex Mappings

```php
// ✅ Good - documented mapping
/**
 * Maps from GitHub API response format
 * Input: avatar_url
 * Output: avatarUrl
 */
#[MapFrom('avatar_url')]
#[MapTo('avatarUrl')]
public readonly string $avatarUrl;
```

### 3. Use Transformers for Data Manipulation

```php
// ✅ Good - use transformer
#[Transform(fn($value) => Hash::make($value))]
public readonly string $password;

// ❌ Bad - manual transformation
public readonly string $password;
// ... then manually hash later
```

### 4. Keep Mappings Simple

```php
// ✅ Good - simple mapping
#[MapFrom('user.name')]
public readonly string $name;

// ❌ Bad - overly complex
#[MapFrom('data.included.0.attributes.user.profile.name')]
public readonly string $name;
```

---

## 📚 Next Steps

1. [Serialization](09-serialization.md) - Output formats
2. [Nested DTOs](16-nested-dtos.md) - Complex structures
3. [Type Casting](06-type-casting.md) - Type conversion
4. [Validation](07-validation.md) - Validate data

---

**Previous:** [Validation](07-validation.md)  
**Next:** [Serialization](09-serialization.md)

