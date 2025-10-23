---
title: Serialization
description: Learn how to serialize DTOs to arrays, JSON, XML, and other formats
---

Learn how to serialize DTOs to arrays, JSON, XML, and other formats.

## What is Serialization?

Serialization converts DTOs to different formats for storage or transmission:

```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');

// To array
$array = $dto->toArray();

// To JSON
$json = json_encode($dto);

// To XML
$xml = $dto->toXml();
```

## To Array

### Basic Usage

```php
$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
```

### Nested DTOs

```php
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

## To JSON

### Basic Usage

```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');

$json = json_encode($dto);
// {"name":"John Doe","email":"john@example.com"}
```

### Pretty Print

```php
$json = json_encode($dto, JSON_PRETTY_PRINT);
// {
//     "name": "John Doe",
//     "email": "john@example.com"
// }
```

### Using toJson()

```php
$json = $dto->toJson();
// {"name":"John Doe","email":"john@example.com"}

$json = $dto->toJson(JSON_PRETTY_PRINT);
// Pretty printed JSON
```

## To XML

### Basic Usage

```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');

$xml = $dto->toXml();
// <?xml version="1.0"?>
// <user>
//     <name>John Doe</name>
//     <email>john@example.com</email>
// </user>
```

### Custom Root Element

```php
$xml = $dto->toXml(rootElement: 'customer');
// <?xml version="1.0"?>
// <customer>
//     <name>John Doe</name>
//     <email>john@example.com</email>
// </customer>
```

## To YAML

### Basic Usage

```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');

$yaml = $dto->toYaml();
// name: John Doe
// email: john@example.com
```

## To CSV

### Basic Usage

```php
$dto = new UserDTO(name: 'John Doe', email: 'john@example.com');

$csv = $dto->toCsv();
// "name","email"
// "John Doe","john@example.com"
```

### Collection to CSV

```php
$users = UserDTO::collection($userArray);
$csv = $users->toCsv();
```

## Conditional Serialization

### With Conditional Attributes

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenCan('view-admin')]
        public readonly ?array $adminData = null,
    ) {}
}

// Only includes properties based on conditions
$array = $dto->toArray();
```

### With Hidden Attribute

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Hidden]
        public readonly string $password,
    ) {}
}

$array = $dto->toArray();
// ['name' => 'John Doe']
// password is excluded
```

## Custom Serialization

### Override toArray()

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    public function toArray(): array
    {
        return [
            'full_name' => $this->firstName . ' ' . $this->lastName,
        ];
    }
}
```

### Custom Serializer

```php
class UserDTO extends SimpleDTO
{
    public function toCustomFormat(): array
    {
        return [
            'user' => [
                'name' => $this->name,
                'contact' => [
                    'email' => $this->email,
                ],
            ],
        ];
    }
}
```

## Best Practices

### Use Appropriate Format

```php
// ✅ Good - use appropriate format
$json = $dto->toJson();  // For APIs
$xml = $dto->toXml();    // For XML APIs
$csv = $dto->toCsv();    // For exports
```

### Handle Sensitive Data

```php
// ✅ Good - hide sensitive data
#[Hidden]
public readonly string $password;

// ❌ Bad - expose sensitive data
public readonly string $password;
```

### Use Conditional Attributes

```php
// ✅ Good - conditional serialization
#[WhenAuth]
public readonly ?string $email;

// ❌ Bad - always include
public readonly string $email;
```


## Code Examples

The following working examples demonstrate this feature:

- [**Serializers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/serializers.php) - Serialization examples
- [**Transformers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/transformers.php) - Data transformation
- [**Normalizers**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/normalizers.php) - Data normalization
- [**Serializer Options**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/serialization/serializer-options.php) - Customizing serialization

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [SerializationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/SerializationTest.php) - Serialization tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Serialization
```

## See Also

- [Conditional Properties](/simple-dto/conditional-properties/) - Dynamic visibility
- [Security & Visibility](/simple-dto/security-visibility/) - Control data exposure
- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
