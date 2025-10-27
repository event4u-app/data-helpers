---
title: Creating Dtos
description: Learn all the different ways to create Dto instances in SimpleDto
---

Learn all the different ways to create Dto instances in SimpleDto.

## Creation Methods Overview

SimpleDto provides multiple ways to create instances:

| Method | Use Case | Example |
|--------|----------|---------|
| `fromArray()` | From associative array | `UserDto::fromArray($data)` |
| `fromJson()` | From JSON string | `UserDto::fromJson($json)` |
| `fromRequest()` | From HTTP request (Laravel) | `UserDto::fromRequest($request)` |
| `fromModel()` | From Eloquent model (Laravel) | `UserDto::fromModel($user)` |
| `fromEntity()` | From Doctrine entity (Symfony) | `UserDto::fromEntity($user)` |
| `fromXml()` | From XML string | `UserDto::fromXml($xml)` |
| `fromYaml()` | From YAML string | `UserDto::fromYaml($yaml)` |
| `fromCsv()` | From CSV string | `UserDto::fromCsv($csv)` |
| `validateAndCreate()` | With validation | `UserDto::validateAndCreate($data)` |
| `new` | Direct instantiation | `new UserDto(...)` |

## From Array

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);
// Result: UserDto instance
```

### With Nested Arrays

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);
// Result: UserDto instance with nested address array
```

### With Extra Keys (Ignored)

```php
use Tests\Utils\Docu\Dtos\UserDto;

// Extra keys are automatically ignored
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'extra_field' => 'ignored',  // Ignored
    'another_field' => 'ignored', // Ignored
]);
// Result: UserDto instance (extra fields ignored)
```

## From JSON

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$json = '{"name":"John Doe","email":"john@example.com","age":30}';
$dto = UserDto::fromJson($json);
// Result: UserDto instance
```

### From JSON File

<!-- skip-test: File doesn't exist -->
```php
$json = file_get_contents('user.json');
$dto = UserDto::fromJson($json);
```

### With Nested JSON

```php
use Tests\Utils\Docu\Dtos\UserDto;

$json = '{
    "name": "John Doe",
    "email": "john@example.com",
    "address": {
        "street": "123 Main St",
        "city": "New York"
    }
}';
$dto = UserDto::fromJson($json);
// Result: UserDto instance with nested address
```

## From HTTP Request

### Laravel Request

<!-- skip-test: Method not available or requires external dependencies -->
```php
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $dto = UserDto::fromRequest($request);

        // Or with validation
        $dto = UserDto::validateAndCreate($request->all());
    }
}
```

### Symfony Request

<!-- skip-test: Class definition example -->
```php
use Symfony\Component\HttpFoundation\Request;

class UserController
{
    public function store(Request $request)
    {
        $dto = UserDto::fromArray($request->request->all());
    }
}
```

### Plain PHP

<!-- skip-test: Method not available or requires external dependencies -->
```php
// From $_POST
$dto = UserDto::fromArray($_POST);

// From $_GET
$dto = UserDto::fromArray($_GET);

// From php://input
$json = file_get_contents('php://input');
$dto = UserDto::fromJson($json);
```

## From Database Models

### Laravel Eloquent

<!-- skip-test: Method not available or requires external dependencies -->
```php
use App\Models\User;

// From single model
$user = User::find(1);
$dto = UserDto::fromModel($user);

// From collection
$users = User::all();
$dtos = $users->map(fn($user) => UserDto::fromModel($user));

// Or use DataCollection
$dtos = DataCollection::make($users, UserDto::class);
```

### Symfony Doctrine

<!-- skip-test: Method not available or requires external dependencies -->
```php
use App\Entity\User;

// From single entity
$user = $entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);

// From multiple entities
$users = $repository->findAll();
$dtos = array_map(
    fn($user) => UserDto::fromEntity($user),
    $users
);

// Or use DataCollection
$dtos = DataCollection::make($users, UserDto::class);
```


## From XML

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$xml = '<?xml version="1.0"?>
<user>
    <name>John Doe</name>
    <email>john@example.com</email>
    <age>30</age>
</user>';

$dto = UserDto::fromXml($xml);
```

### From XML File

<!-- skip-test: Method not available or requires external dependencies -->
```php
$xml = file_get_contents('user.xml');
$dto = UserDto::fromXml($xml);
```

## From YAML

### Basic Usage

```php
$yaml = '
name: John Doe
email: john@example.com
age: 30
';

$dto = UserDto::fromYaml($yaml);
```

### From YAML File

<!-- skip-test: Method not available or requires external dependencies -->
```php
$yaml = file_get_contents('user.yaml');
$dto = UserDto::fromYaml($yaml);
```

## From CSV

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$csv = '"name","email","age"
"John Doe","john@example.com",30';

$dto = UserDto::fromCsv($csv);
// Result: UserDto instance with auto-casted types
```

### From CSV File

<!-- skip-test: Method not available or requires external dependencies -->
```php
$csv = file_get_contents('user.csv');
$dto = UserDto::fromCsv($csv);
```

## With Validation

### Validate and Create

<!-- skip-test: Class definition example -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,
    ) {}
}

// Validates automatically
$dto = UserDto::validateAndCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Throws ValidationException if invalid
try {
    $dto = UserDto::validateAndCreate([
        'name' => '',  // ❌ Required
        'email' => 'invalid',  // ❌ Invalid email
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage();
}
```

## Direct Instantiation

### Using Constructor

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);
```

### With Named Arguments

```php
use Tests\Utils\Docu\Dtos\UserDto;

// ✅ Good - clear and explicit
$dto = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    age: 30
);

// ❌ Bad - unclear order
$dto = new UserDto('John Doe', 'john@example.com', 30);
```

## From Other Dtos

### Clone and Modify

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto1 = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Create new Dto with modified values
$dto2 = new UserDto(
    name: 'Jane Doe',  // Changed
    email: $dto1->email,
    age: $dto1->age
);
```

### Convert Between Dtos

<!-- skip-test: Class definition example -->
```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class UserResourceDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $createdAt,
    ) {}

    public static function fromUserDto(UserDto $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            createdAt: now()->toIso8601String()
        );
    }
}

$userDto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$resourceDto = UserResourceDto::fromUserDto($userDto);
```


## Factory Methods

### Custom Factory Methods

<!-- skip-test: Class definition example -->
```php
class UserDto extends SimpleDto
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
$admin = UserDto::admin('John Doe', 'john@example.com');
$guest = UserDto::guest('Jane Doe', 'jane@example.com');
```

## Bulk Creation

### From Array of Arrays

```php
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
];

$dtos = array_map(
    fn($item) => UserDto::fromArray($item),
    $data
);
```

### Using DataCollection

```php
use event4u\DataHelpers\SimpleDto\DataCollection;
use Tests\Utils\Docu\Dtos\UserDto;

$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
];

$collection = DataCollection::make($data, UserDto::class);

$filtered = $collection->filter(fn($dto) => str_contains($dto->email, 'john'));
$mapped = $collection->map(fn($dto) => $dto->name);
// Result: DataCollection with filtered/mapped Dtos
```

## Best Practices

### Use Static Factory Methods

```php
// ✅ Good - clear intent
$dto = UserDto::fromArray($data);

// ❌ Bad - unclear
$dto = new UserDto(...$data);
```

### Validate Early

```php
// ✅ Good - validate at creation
$dto = UserDto::validateAndCreate($request->all());

// ❌ Bad - validate later
$dto = UserDto::fromArray($request->all());
$dto->validate();
```

### Use Type-Specific Methods

```php
// ✅ Good - use specific method
$dto = UserDto::fromJson($json);

// ❌ Bad - manual parsing
$data = json_decode($json, true);
$dto = UserDto::fromArray($data);
```

### Handle Errors Gracefully

```php
try {
    $dto = UserDto::validateAndCreate($data);
} catch (ValidationException $e) {
    // Handle validation errors
    return response()->json([
        'errors' => $e->errors()
    ], 422);
}
```

## Code Examples

The following working examples demonstrate Dto creation:

- [**Basic Dto**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/creating-dtos/basic-dto.php) - Simple Dto with required properties
- [**Dto Factory**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/creating-dtos/dto-factory.php) - Factory pattern for Dtos
- [**Wrapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/creating-dtos/wrapping.php) - Wrapping existing data
- [**Optional Properties**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/creating-dtos/optional-properties.php) - Handling optional properties

All examples are fully tested and can be run directly:

```bash
php examples/simple-dto/creating-dtos/basic-dto.php
php examples/simple-dto/creating-dtos/dto-factory.php
```

## Related Tests

The functionality is thoroughly tested. Key test files:

- [SimpleDtoTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/SimpleDtoTest.php) - Core Dto functionality
- [DtoFactoryTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/DtoFactoryTest.php) - Factory pattern tests
- [OptionalPropertiesTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/OptionalPropertiesTest.php) - Optional properties tests

Run the tests:

```bash
# Run all SimpleDto tests
task test:unit -- --filter=SimpleDto

# Run specific test file
vendor/bin/pest tests/Unit/SimpleDto/SimpleDtoTest.php
```
## See Also

- [Type Casting](/data-helpers/simple-dto/type-casting/) - Automatic type conversion
- [Validation](/data-helpers/simple-dto/validation/) - Validate your data
- [Property Mapping](/data-helpers/simple-dto/property-mapping/) - Map property names
- [Collections](/data-helpers/simple-dto/collections/) - Working with collections
