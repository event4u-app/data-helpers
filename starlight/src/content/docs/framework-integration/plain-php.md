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
- ✅ **Type Safety** - Full type casting
- ✅ **Validation** - Built-in validation
- ✅ **Lightweight** - Minimal overhead

## Installation

```bash
composer require event4u/data-helpers
```

## Basic Usage

### Create Dto

```php
<?php

require 'vendor/autoload.php';

use event4u\DataHelpers\SimpleDto\SimpleDto;
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

use event4u\DataHelpers\SimpleDto\SimpleDto;
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

use event4u\DataHelpers\SimpleDto\SimpleDto;
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

- [SimpleDto Introduction](/simple-dto/introduction/) - Dto basics
- [Validation](/simple-dto/validation/) - Validation guide
- [Type Casting](/simple-dto/type-casting/) - Type casting guide
