---
title: Serialization
description: Learn how to serialize Dtos to arrays, JSON, XML, and other formats
---

Learn how to serialize Dtos to arrays, JSON, XML, and other formats.

## What is Serialization?

Serialization converts Dtos to different formats for storage or transmission:

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');

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
use Tests\Utils\Docu\Dtos\UserDto;

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
```

### Nested Dtos

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = UserDto::fromArray([
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
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');

$json = json_encode($dto);
// {"name":"John Doe","email":"john@example.com"}
```

### Pretty Print

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');
$json = json_encode($dto, JSON_PRETTY_PRINT);
// $json contains pretty-printed JSON
```

### Using toJson()

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');
$json = $dto->toJson();
// Result: {"name":"John Doe","email":"john@example.com","age":0,"address":null}
```

## To XML

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');

$xml = $dto->toXml();
// Returns XML string with user data
```

### Custom Root Element

```php
use event4u\DataHelpers\SimpleDto\Config\SerializerOptions;
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');
$options = SerializerOptions::xml(rootElement: 'customer');
$xml = $dto->toXml($options);
// $xml contains XML with <customer> root element
```

## To YAML

### Basic Usage

<!-- skip-test: YAML extension not available -->
```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');

$yaml = $dto->toYaml();
// name: John Doe
// email: john@example.com
```

## To CSV

### Basic Usage

```php
use Tests\Utils\Docu\Dtos\UserDto;

$dto = new UserDto(name: 'John Doe', email: 'john@example.com');

$csv = $dto->toCsv();
// Result: name,email,age,address
//         "John Doe",john@example.com,0,
```

### Collection to CSV

<!-- skip-test: DataCollection::toCsv() not implemented yet -->
```php
use Tests\Utils\Docu\Dtos\UserDto;

$users = UserDto::collection($userArray);
$csv = $users->toCsv();
```

## Conditional Serialization

### With Conditional Attributes

<!-- skip-test: Conditional attributes example -->
```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
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

<!-- skip-test: Hidden attribute example -->
```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
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

<!-- skip-test: Custom toArray() example -->
```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
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

<!-- skip-test: Custom serializer example -->
```php
use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
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

<!-- skip-test: Best practices example -->
```php
// ✅ Good - use appropriate format
$json = $dto->toJson();  // For APIs
$xml = $dto->toXml();    // For XML APIs
$csv = $dto->toCsv();    // For exports
```

### Handle Sensitive Data

<!-- skip-test: Best practices example -->
```php
// ✅ Good - hide sensitive data
#[Hidden]
public readonly string $password;

// ❌ Bad - expose sensitive data
public readonly string $password;
```

### Use Conditional Attributes

<!-- skip-test: Best practices example -->
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

- [SerializationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/SerializationTest.php) - Serialization tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Serialization
```

## See Also

- [Conditional Properties](/data-helpers/simple-dto/conditional-properties/) - Dynamic visibility
- [Security & Visibility](/data-helpers/simple-dto/security-visibility/) - Control data exposure
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Automatic type conversion
