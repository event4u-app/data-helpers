# Serialization

Learn how to convert DTOs to different formats: Array, JSON, XML, YAML, CSV, and more.

---

## 🎯 What is Serialization?

Serialization is the process of converting a DTO into a format that can be stored or transmitted:

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

$array = $dto->toArray();  // PHP array
$json = $dto->toJson();    // JSON string
$xml = $dto->toXml();      // XML string
$yaml = $dto->toYaml();    // YAML string
$csv = $dto->toCsv();      // CSV string
```

---

## 📦 To Array

### Basic Usage

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

$array = $dto->toArray();
// [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'age' => 30,
// ]
```

### With Nested DTOs

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
    ],
]);

$array = $dto->toArray();
// [
//     'name' => 'John Doe',
//     'address' => [
//         'street' => '123 Main St',
//         'city' => 'New York',
//     ],
// ]
```

### Only Specific Properties

```php
$array = $dto->only(['name', 'email']);
// ['name' => 'John Doe', 'email' => 'john@example.com']
```

### Exclude Specific Properties

```php
$array = $dto->except(['age']);
// ['name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 📄 To JSON

### Basic Usage

```php
$json = $dto->toJson();
// {"name":"John Doe","email":"john@example.com","age":30}
```

### Pretty Print

```php
$json = $dto->toJson(JSON_PRETTY_PRINT);
// {
//     "name": "John Doe",
//     "email": "john@example.com",
//     "age": 30
// }
```

### With Options

```php
$json = $dto->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

### JsonSerializable

```php
// DTOs implement JsonSerializable
$json = json_encode($dto);
// {"name":"John Doe","email":"john@example.com","age":30}

// In Laravel responses
return response()->json($dto);
```

---

## 📋 To XML

### Basic Usage

```php
$xml = $dto->toXml();
// <?xml version="1.0"?>
// <UserDTO>
//   <name>John Doe</name>
//   <email>john@example.com</email>
//   <age>30</age>
// </UserDTO>
```

### With Custom Root Element

```php
$xml = $dto->toXml('User');
// <?xml version="1.0"?>
// <User>
//   <name>John Doe</name>
//   <email>john@example.com</email>
//   <age>30</age>
// </User>
```

### With Nested DTOs

```php
$xml = $dto->toXml();
// <?xml version="1.0"?>
// <UserDTO>
//   <name>John Doe</name>
//   <address>
//     <street>123 Main St</street>
//     <city>New York</city>
//   </address>
// </UserDTO>
```

---

## 📝 To YAML

### Basic Usage

```php
$yaml = $dto->toYaml();
// name: John Doe
// email: john@example.com
// age: 30
```

### With Nested DTOs

```php
$yaml = $dto->toYaml();
// name: John Doe
// address:
//   street: 123 Main St
//   city: New York
```

---

## 📊 To CSV

### Basic Usage

```php
$csv = $dto->toCsv();
// "name","email","age"
// "John Doe","john@example.com",30
```

### Multiple DTOs

```php
$users = [
    UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]),
    UserDTO::fromArray(['name' => 'Jane', 'email' => 'jane@example.com', 'age' => 25]),
];

$csv = UserDTO::collectionToCsv($users);
// "name","email","age"
// "John","john@example.com",30
// "Jane","jane@example.com",25
```

### Custom Delimiter

```php
$csv = $dto->toCsv(delimiter: ';');
// "name";"email";"age"
// "John Doe";"john@example.com";30
```

---

## 🎨 Custom Serialization

### Custom toArray Method

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
    ) {}
    
    public function toArray(): array
    {
        return [
            'name' => $this->firstName . ' ' . $this->lastName,
            'email' => $this->email,
        ];
    }
}

$dto = new UserDTO(
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com'
);

$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com']
```

### Custom Serialization Method

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly Carbon $createdAt,
    ) {}
    
    public function toApiResponse(): array
    {
        return [
            'data' => [
                'type' => 'users',
                'id' => $this->id,
                'attributes' => [
                    'name' => $this->name,
                    'email' => $this->email,
                ],
                'meta' => [
                    'created_at' => $this->createdAt->toIso8601String(),
                ],
            ],
        ];
    }
}

$response = $dto->toApiResponse();
```

---

## 🔄 Serialization with Transformations

### Transform on Serialization

```php
use event4u\DataHelpers\SimpleDTO\Attributes\SerializeAs;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[SerializeAs(fn($value) => strtolower($value))]
        public readonly string $email,
        
        #[SerializeAs(fn($value) => $value->format('Y-m-d'))]
        public readonly Carbon $createdAt,
    ) {}
}

$dto = new UserDTO(
    name: 'John Doe',
    email: 'JOHN@EXAMPLE.COM',
    createdAt: now()
);

$array = $dto->toArray();
// [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'createdAt' => '2024-01-15',
// ]
```

---

## 🎯 Conditional Serialization

### With Conditional Attributes

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
        
        #[WhenCan('view-admin')]
        public readonly ?array $adminData = null,
    ) {}
}

// Without authentication
$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe']

// With authentication
$array = $dto->withContext(['user' => $user])->toArray();
// ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 📦 Collection Serialization

### Array of DTOs

```php
$users = [
    UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']),
    UserDTO::fromArray(['name' => 'Jane', 'email' => 'jane@example.com']),
];

$array = array_map(fn($dto) => $dto->toArray(), $users);
```

### DataCollection

```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$collection = DataCollection::make([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
], UserDTO::class);

$array = $collection->toArray();
$json = $collection->toJson();
```

---

## 🎨 Response Formats

### Laravel API Response

```php
class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        $dtos = DataCollection::make($users, UserDTO::class);
        
        return response()->json($dtos);
    }
    
    public function show(User $user)
    {
        $dto = UserDTO::fromModel($user);
        
        return response()->json($dto);
    }
}
```

### Symfony JSON Response

```php
class UserController
{
    public function index(): JsonResponse
    {
        $users = $repository->findAll();
        $dtos = array_map(
            fn($user) => UserDTO::fromEntity($user)->toArray(),
            $users
        );
        
        return new JsonResponse($dtos);
    }
}
```

---

## 🔍 Debugging Serialization

### Dump Array

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

// Laravel
dd($dto->toArray());

// Symfony
dump($dto->toArray());

// Plain PHP
var_dump($dto->toArray());
print_r($dto->toArray());
```

### Validate JSON

```php
$json = $dto->toJson();

// Check if valid JSON
$isValid = json_validate($json);

// Decode and check
$decoded = json_decode($json, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Valid JSON";
}
```

---

## 💡 Best Practices

### 1. Use Appropriate Format

```php
// ✅ Good - use appropriate format
$json = $dto->toJson();  // For APIs
$array = $dto->toArray(); // For internal use
$csv = $dto->toCsv();    // For exports

// ❌ Bad - always using JSON
$json = $dto->toJson();
$array = json_decode($json, true);
```

### 2. Handle Nested DTOs

```php
// ✅ Good - automatic nested serialization
$array = $dto->toArray();

// ❌ Bad - manual nested serialization
$array = [
    'name' => $dto->name,
    'address' => $dto->address->toArray(),
];
```

### 3. Use Conditional Serialization

```php
// ✅ Good - conditional properties
#[WhenAuth]
public readonly ?string $email = null;

// ❌ Bad - manual filtering
public function toArray(): array
{
    $array = parent::toArray();
    if (!auth()->check()) {
        unset($array['email']);
    }
    return $array;
}
```

### 4. Cache Serialized Data

```php
// ✅ Good - cache expensive serialization
$json = Cache::remember('user.' . $user->id, 3600, function () use ($dto) {
    return $dto->toJson();
});

// ❌ Bad - serialize on every request
$json = $dto->toJson();
```

---

## 📚 Next Steps

1. [Conditional Properties](10-conditional-properties.md) - Dynamic serialization
2. [Collections](15-collections.md) - Serialize collections
3. [Property Mapping](08-property-mapping.md) - Map output names
4. [API Resources](38-api-resources.md) - REST API examples

---

**Previous:** [Property Mapping](08-property-mapping.md)  
**Next:** [Conditional Properties](10-conditional-properties.md)

