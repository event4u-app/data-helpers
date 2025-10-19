# Creating DTOs

Learn all the different ways to create DTO instances in SimpleDTO.

---

## 🎯 Creation Methods Overview

SimpleDTO provides multiple ways to create instances:

| Method | Use Case | Example |
|--------|----------|---------|
| `fromArray()` | From associative array | `UserDTO::fromArray($data)` |
| `fromJson()` | From JSON string | `UserDTO::fromJson($json)` |
| `fromRequest()` | From HTTP request (Laravel) | `UserDTO::fromRequest($request)` |
| `fromModel()` | From Eloquent model (Laravel) | `UserDTO::fromModel($user)` |
| `fromEntity()` | From Doctrine entity (Symfony) | `UserDTO::fromEntity($user)` |
| `fromXml()` | From XML string | `UserDTO::fromXml($xml)` |
| `fromYaml()` | From YAML string | `UserDTO::fromYaml($yaml)` |
| `fromCsv()` | From CSV string | `UserDTO::fromCsv($csv)` |
| `validateAndCreate()` | With validation | `UserDTO::validateAndCreate($data)` |
| `new` | Direct instantiation | `new UserDTO(...)` |

---

## 📥 From Array

### Basic Usage

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);
```

### With Nested Arrays

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);
```

### With Extra Keys (Ignored)

```php
// Extra keys are automatically ignored
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'extra_field' => 'ignored',  // Ignored
    'another_field' => 'ignored', // Ignored
]);
```

---

## 📄 From JSON

### Basic Usage

```php
$json = '{"name":"John Doe","email":"john@example.com","age":30}';
$dto = UserDTO::fromJson($json);
```

### From JSON File

```php
$json = file_get_contents('user.json');
$dto = UserDTO::fromJson($json);
```

### With Nested JSON

```php
$json = '{
    "name": "John Doe",
    "email": "john@example.com",
    "address": {
        "street": "123 Main St",
        "city": "New York"
    }
}';
$dto = UserDTO::fromJson($json);
```

---

## 🌐 From HTTP Request

### Laravel Request

```php
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $dto = UserDTO::fromRequest($request);
        
        // Or with validation
        $dto = UserDTO::validateAndCreate($request->all());
    }
}
```

### Symfony Request

```php
use Symfony\Component\HttpFoundation\Request;

class UserController
{
    public function store(Request $request)
    {
        $dto = UserDTO::fromArray($request->request->all());
    }
}
```

### Plain PHP

```php
// From $_POST
$dto = UserDTO::fromArray($_POST);

// From $_GET
$dto = UserDTO::fromArray($_GET);

// From php://input
$json = file_get_contents('php://input');
$dto = UserDTO::fromJson($json);
```

---

## 🗄️ From Database Models

### Laravel Eloquent

```php
use App\Models\User;

// From single model
$user = User::find(1);
$dto = UserDTO::fromModel($user);

// From collection
$users = User::all();
$dtos = $users->map(fn($user) => UserDTO::fromModel($user));

// Or use DataCollection
$dtos = DataCollection::make($users, UserDTO::class);
```

### Symfony Doctrine

```php
use App\Entity\User;

// From single entity
$user = $entityManager->find(User::class, 1);
$dto = UserDTO::fromEntity($user);

// From multiple entities
$users = $repository->findAll();
$dtos = array_map(
    fn($user) => UserDTO::fromEntity($user),
    $users
);

// Or use DataCollection
$dtos = DataCollection::make($users, UserDTO::class);
```

---

## 📋 From XML

### Basic Usage

```php
$xml = '<?xml version="1.0"?>
<user>
    <name>John Doe</name>
    <email>john@example.com</email>
    <age>30</age>
</user>';

$dto = UserDTO::fromXml($xml);
```

### From XML File

```php
$xml = file_get_contents('user.xml');
$dto = UserDTO::fromXml($xml);
```

---

## 📝 From YAML

### Basic Usage

```php
$yaml = '
name: John Doe
email: john@example.com
age: 30
';

$dto = UserDTO::fromYaml($yaml);
```

### From YAML File

```php
$yaml = file_get_contents('user.yaml');
$dto = UserDTO::fromYaml($yaml);
```

---

## 📊 From CSV

### Basic Usage

```php
$csv = '"name","email","age"
"John Doe","john@example.com",30';

$dto = UserDTO::fromCsv($csv);
```

### From CSV File

```php
$csv = file_get_contents('user.csv');
$dto = UserDTO::fromCsv($csv);
```

---

## ✅ With Validation

### Validate and Create

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}

// Validates automatically
$dto = UserDTO::validateAndCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Throws ValidationException if invalid
try {
    $dto = UserDTO::validateAndCreate([
        'name' => '',  // ❌ Required
        'email' => 'invalid',  // ❌ Invalid email
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage();
}
```

---

## 🏗️ Direct Instantiation

### Using Constructor

```php
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);
```

### With Named Arguments

```php
// ✅ Good - clear and explicit
$dto = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

// ❌ Bad - unclear order
$dto = new UserDTO('John Doe', 'john@example.com', 30);
```

---

## 🔄 From Other DTOs

### Clone and Modify

```php
$dto1 = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Create new DTO with modified values
$dto2 = new UserDTO(
    name: 'Jane Doe',  // Changed
    email: $dto1->email,
    age: $dto1->age
);
```

### Convert Between DTOs

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $createdAt,
    ) {}
    
    public static function fromUserDTO(UserDTO $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            createdAt: now()->toIso8601String()
        );
    }
}

$userDto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$resourceDto = UserResourceDTO::fromUserDTO($userDto);
```

---

## 🎯 Factory Methods

### Custom Factory Methods

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
    ) {}
    
    public static function admin(string $name, string $email): self
    {
        return new self(
            name: $name,
            email: $email,
            role: 'admin'
        );
    }
    
    public static function guest(string $name, string $email): self
    {
        return new self(
            name: $name,
            email: $email,
            role: 'guest'
        );
    }
}

// Use factory methods
$admin = UserDTO::admin('John Doe', 'john@example.com');
$guest = UserDTO::guest('Jane Doe', 'jane@example.com');
```

---

## 📦 Bulk Creation

### From Array of Arrays

```php
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
];

$dtos = array_map(
    fn($item) => UserDTO::fromArray($item),
    $data
);
```

### Using DataCollection

```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
];

$collection = DataCollection::make($data, UserDTO::class);

// Now you can use collection methods
$filtered = $collection->filter(fn($dto) => str_contains($dto->email, 'john'));
$mapped = $collection->map(fn($dto) => $dto->name);
```

---

## 💡 Best Practices

### 1. Use Static Factory Methods

```php
// ✅ Good - clear intent
$dto = UserDTO::fromArray($data);

// ❌ Bad - unclear
$dto = new UserDTO(...$data);
```

### 2. Validate Early

```php
// ✅ Good - validate at creation
$dto = UserDTO::validateAndCreate($request->all());

// ❌ Bad - validate later
$dto = UserDTO::fromArray($request->all());
$dto->validate();
```

### 3. Use Type-Specific Methods

```php
// ✅ Good - use specific method
$dto = UserDTO::fromJson($json);

// ❌ Bad - manual parsing
$data = json_decode($json, true);
$dto = UserDTO::fromArray($data);
```

### 4. Handle Errors Gracefully

```php
try {
    $dto = UserDTO::validateAndCreate($data);
} catch (ValidationException $e) {
    // Handle validation errors
    return response()->json([
        'errors' => $e->errors()
    ], 422);
}
```

---

## 📚 Next Steps

1. [Type Casting](06-type-casting.md) - Automatic type conversion
2. [Validation](07-validation.md) - Validate your data
3. [Property Mapping](08-property-mapping.md) - Map property names
4. [Collections](15-collections.md) - Working with collections

---

**Previous:** [Basic Usage](04-basic-usage.md)  
**Next:** [Type Casting](06-type-casting.md)

