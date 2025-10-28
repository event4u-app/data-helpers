---
title: Creating LiteDtos
description: Learn how to create and use LiteDtos
---

This guide covers everything you need to know about creating and using LiteDtos.

## Basic LiteDto

The simplest LiteDto is just a class that extends `LiteDto`:

```php
use event4u\DataHelpers\LiteDto\LiteDto;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}
```

### Creating Instances

Use the `from()` method to create instances:

```php
// From array
$user = UserDto::from([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

// Access properties
echo $user->name;  // John Doe
echo $user->age;   // 30
```

### Serialization

Convert back to array or JSON:

```php
// To array
$array = $user->toArray();
// ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com']

// To JSON
$json = $user->toJson();
// {"name":"John Doe","age":30,"email":"john@example.com"}

// JsonSerializable support
echo json_encode($user);
// {"name":"John Doe","age":30,"email":"john@example.com"}
```

## Property Mapping

### Input Mapping with #[MapFrom]

Map properties from different source keys:

```php
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;

class ProductDto extends LiteDto
{
    public function __construct(
        #[MapFrom('product_name')]
        public readonly string $name,

        #[MapFrom('product_price')]
        public readonly float $price,

        #[MapFrom('product_description')]
        public readonly string $description,
    ) {}
}

$product = ProductDto::from([
    'product_name' => 'Laptop',
    'product_price' => 999.99,
    'product_description' => 'High-performance laptop',
]);

echo $product->name;  // Laptop
```

### Output Mapping with #[MapTo]

Map properties to different target keys when serializing:

```php
use event4u\DataHelpers\LiteDto\Attributes\MapTo;

class UserDto extends LiteDto
{
    public function __construct(
        #[MapTo('full_name')]
        public readonly string $name,

        #[MapTo('user_age')]
        public readonly int $age,

        #[MapTo('email_address')]
        public readonly string $email,
    ) {}
}

$user = UserDto::from([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

$array = $user->toArray();
// [
//     'full_name' => 'John Doe',
//     'user_age' => 30,
//     'email_address' => 'john@example.com'
// ]
```

### Combining #[MapFrom] and #[MapTo]

You can use both attributes together:

```php
class UserDto extends LiteDto
{
    public function __construct(
        #[MapFrom('first_name'), MapTo('full_name')]
        public readonly string $name,

        #[MapFrom('user_age'), MapTo('age_years')]
        public readonly int $age,
    ) {}
}

// Input uses 'first_name' and 'user_age'
$user = UserDto::from([
    'first_name' => 'John',
    'user_age' => 30,
]);

// Output uses 'full_name' and 'age_years'
$array = $user->toArray();
// ['full_name' => 'John', 'age_years' => 30]
```

## Hidden Properties

Exclude sensitive properties from serialization:

```php
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $apiKey,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'apiKey' => 'key_abc123',
]);

// Hidden properties are excluded from serialization
$array = $user->toArray();
// ['name' => 'John', 'email' => 'john@example.com']

$json = $user->toJson();
// {"name":"John","email":"john@example.com"}

// But you can still access them directly
echo $user->password;  // secret123
```

## Convert Empty to Null

Automatically convert empty strings and arrays to null:

```php
use event4u\DataHelpers\LiteDto\Attributes\ConvertEmptyToNull;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[ConvertEmptyToNull]
        public readonly ?string $middleName,

        #[ConvertEmptyToNull]
        public readonly ?string $bio,

        #[ConvertEmptyToNull]
        public readonly ?array $tags,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'middleName' => '',      // Converted to null
    'bio' => '',             // Converted to null
    'tags' => [],            // Converted to null
]);

var_dump($user->middleName);  // NULL
var_dump($user->bio);         // NULL
var_dump($user->tags);        // NULL
```

## Nested DTOs

LiteDto automatically hydrates nested DTOs:

```php
class AddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
    ) {}
}

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$user = UserDto::from([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'zipCode' => '10001',
    ],
]);

echo $user->address->city;  // New York
```

### Multiple Nested Levels

You can nest DTOs as deep as needed:

```php
class CountryDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
    ) {}
}

class CityDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly CountryDto $country,
    ) {}
}

class AddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly CityDto $city,
    ) {}
}

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'address' => [
        'street' => '123 Main St',
        'city' => [
            'name' => 'New York',
            'country' => [
                'name' => 'United States',
                'code' => 'US',
            ],
        ],
    ],
]);

echo $user->address->city->country->name;  // United States
```

## Collections

Handle arrays of DTOs using docblock type hints:

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class TeamDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        /** @var array<UserDto> */
        public readonly array $members,
    ) {}
}

$team = TeamDto::from([
    'name' => 'Engineering',
    'members' => [
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
        ['name' => 'Bob', 'age' => 35],
    ],
]);

// Access collection items
foreach ($team->members as $member) {
    echo $member->name . ' - ' . $member->age . "\n";
}
// John - 30
// Jane - 25
// Bob - 35
```

### Alternative Docblock Syntax

Both syntaxes are supported:

```php
// Generic syntax
/** @var array<UserDto> */
public readonly array $members;

// Array syntax
/** @var UserDto[] */
public readonly array $members;
```

## Next Steps

- [Attributes Reference](./attributes) - Complete guide to all attributes
- [Converter Mode](./converter-mode) - Enable JSON/XML support
- [Performance Tips](./performance) - Optimize your LiteDtos

