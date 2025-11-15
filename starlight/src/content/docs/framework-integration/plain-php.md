---
title: Plain PHP Usage
description: Complete guide for using Data Helpers in plain PHP projects
---

Complete guide for using Data Helpers in plain PHP projects.

## Introduction

Data Helpers works perfectly in **plain PHP projects** without any framework:

- ✅ **No Dependencies** - Works standalone
- ✅ **Full Feature Set** - All features available
- ✅ **Arrays, Objects, JSON, XML** - Multiple input formats
- ✅ **Plain Object Integration** - Seamless DTO ↔ Object conversion
- ✅ **Type Safety** - Full type casting
- ✅ **Validation** - Built-in validation
- ✅ **Lightweight** - Minimal overhead

## Installation

```bash
composer require event4u/data-helpers
```

## Configuration

:::note[Plain PHP Only]
The `ConfigLoader` is **only for Plain PHP projects**. If you're using Laravel or Symfony, use their native configuration systems instead.
:::

Data Helpers can be configured in plain PHP projects using the `ConfigLoader`:

```php skip-test
<?php

require 'vendor/autoload.php';

use event4u\DataHelpers\Config\ConfigLoader;
use event4u\DataHelpers\DataHelpersConfig;

// Load configuration (merges with package defaults)
$config = ConfigLoader::load(__DIR__ . '/config/data-helpers.php');

// Initialize Data Helpers
DataHelpersConfig::initialize($config);
```

### Create Config File

You can publish a minimal config file:

```php
use event4u\DataHelpers\Config\ConfigLoader;

// Publish config file
ConfigLoader::publish('./.event4u/config/data-helpers.php');
```

Or create your own config file with only the values you want to change:

```php
// ./.event4u/config/data-helpers.php
<?php

return [
    'performance_mode' => 'fast',
    'cache' => [
        'ttl' => 3600,
        'code_generation' => true,
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'error',
    ],
];
```

The `ConfigLoader` performs **deep merging** - you only need to specify values you want to change. All other values use package defaults.

See [ConfigLoader](/data-helpers/config/config-loader/) for detailed documentation.

## Basic Usage

### Create Dto

```php
<?php

require 'vendor/autoload.php';

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
    ) {}
}
```

### From Array

```php
$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

echo $dto->name;  // 'John Doe'
echo $dto->email; // 'john@example.com'
```

### From JSON

```php
$json = '{"name":"John Doe","email":"john@example.com"}';
$dto = UserDto::fromJson($json);
```

### From XML

```php
$xml = '<user><name>John Doe</name><email>john@example.com</email></user>';
$dto = UserDto::fromXml($xml);
```

### From POST Data

```php
$dto = UserDto::fromArray($_POST);
```

## Validation

### Manual Validation

```php
use event4u\DataHelpers\Exceptions\ValidationException;

$data = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];

try {
    $dto = UserDto::validateAndCreate($data);
    // Validation passed
} catch (ValidationException $e) {
    // Validation failed
    print_r($e->errors());
}
```

### Validate and Create

```php
$data = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
$dto = UserDto::validateAndCreate($data);
// Dto is valid (throws exception if invalid)
```

## Plain Object Integration

Data Helpers provides seamless integration with plain PHP objects (like Zend Framework models, legacy classes, or any plain PHP objects).

### Basic Usage

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasObject;

// Plain PHP object
class Product
{
    public int $id;
    public string $name;
    public float $price;
}

// DTO with plain object integration
#[HasObject(Product::class)]
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Object → DTO
$product = new Product();
$product->id = 1;
$product->name = 'Laptop';
$product->price = 999.99;
$dto = ProductDto::fromObject($product);

// DTO → Object
$newProduct = $dto->toObject();  // Uses HasObject attribute
```

:::tip[No Manual Trait Import Needed]
The `SimpleDtoObjectTrait` is **automatically included** in `SimpleDto`. You don't need to manually import it!

The methods `fromObject()` and `toObject()` are always available, even without any framework.
:::

### With Getters and Setters

```php
class Customer
{
    private int $id;
    private string $name;

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
}

#[HasObject(Customer::class)]
class CustomerDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

$customer = new Customer();
$customer->setId(42);
$customer->setName('John Doe');

// fromObject() uses getters, toObject() uses setters
$dto = CustomerDto::fromObject($customer);
$newCustomer = $dto->toObject();
```

### Object to DTO Conversion

You can also add the `ObjectMappingTrait` to your plain objects:

```php
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\Traits\ObjectMappingTrait;

#[HasDto(ProductDto::class)]
class Product
{
    use ObjectMappingTrait;

    public int $id;
    public string $name;
    public float $price;
}

$product = new Product();
$product->id = 1;
$product->name = 'Laptop';
$product->price = 999.99;

// Convert to DTO using attribute
$dto = $product->toDto();
```

📖 **[Complete Plain Object Integration Guide](/data-helpers/attributes/detailed/has-object/)** - Detailed documentation with all features

## Type Casting

### Automatic Type Casting

```php
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\IntCast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

class OrderDto extends SimpleDto
{
    public function __construct(
        #[Cast(IntCast::class)]
        public readonly int $orderId,

        #[Cast(DateTimeCast::class)]
        public readonly \Carbon\Carbon $orderDate,
    ) {}
}

$dto = OrderDto::fromArray([
    'orderId' => '123',        // String to int
    'orderDate' => '2024-01-01', // String to Carbon
]);
```

## Serialization

### To Array

```php
$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = UserDto::fromArray($data);
$array = $dto->toArray();
```

### To JSON

```php
$data = ['name' => 'John', 'email' => 'john@example.com'];
$dto = UserDto::fromArray($data);
$json = $dto->toJson();
```

### To XML

```php
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
$xml = $dto->toXml();
```

## Real-World Example

### API Endpoint

```php
<?php

require 'vendor/autoload.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\*;
use event4u\DataHelpers\SimpleDto\Exceptions\ValidationException;

class CreateUserDto extends SimpleDto
{
    public function __construct(
        #[Required, Min(3)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = CreateUserDto::validateAndCreate($_POST);

        // Save to database
        $pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([
            $dto->name,
            $dto->email,
            password_hash($dto->password, PASSWORD_DEFAULT),
        ]);

        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } catch (ValidationException $e) {
        // Return validation errors
        header('Content-Type: application/json', true, 422);
        echo json_encode(['errors' => $e->getErrors()]);
    }
}
```

### Form Processing

```php
<?php

require 'vendor/autoload.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\*;
use event4u\DataHelpers\SimpleDto\Exceptions\ValidationException;

class ContactFormDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(10)]
        public readonly string $message,
    ) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dto = ContactFormDto::validateAndCreate($_POST);

        // Send email
        mail(
            'admin@example.com',
            'Contact Form',
            "Name: {$dto->name}\nEmail: {$dto->email}\nMessage: {$dto->message}"
        );

        echo 'Message sent successfully!';

    } catch (ValidationException $e) {
        echo 'Validation errors:';
        print_r($e->getErrors());
    }
}
?>

<form method="POST">
    <input type="text" name="name" placeholder="Name">
    <input type="email" name="email" placeholder="Email">
    <textarea name="message" placeholder="Message"></textarea>
    <button type="submit">Send</button>
</form>
```

## Best Practices

### Use Type Hints

```php
// ✅ Good - type hints
public readonly string $name;
public readonly int $age;

// ❌ Bad - no type hints
public readonly $name;
public readonly $age;
```

### Validate User Input

```php
// ✅ Good - validate
$dto = UserDto::validateAndCreate($_POST);

// ❌ Bad - no validation
$dto = UserDto::fromArray($_POST);
```

### Use Validation Attributes

```php
// ✅ Good - validation attributes
#[Required, Email]
public readonly string $email;

// ❌ Bad - no validation
public readonly string $email;
```

## See Also

- [SimpleDto Introduction](/data-helpers/simple-dto/introduction/) - Dto basics
- [Validation](/data-helpers/simple-dto/validation/) - Validation guide
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Type casting guide
