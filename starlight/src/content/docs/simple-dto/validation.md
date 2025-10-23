---
title: Validation
description: Learn how to validate DTOs using automatic rule inferring and validation attributes
---

Learn how to validate DTOs using automatic rule inferring and validation attributes.

## What is Validation?

Validation ensures that data meets specific requirements before being processed. SimpleDTO provides:

- **Automatic rule inferring** from types and attributes
- **30+ validation attributes**
- **Framework integration** (Laravel, Symfony)
- **Custom validation rules**
- **Validation caching** (198x faster)

## Quick Start

### Basic Validation

```php
use Event4u\DataHelpers\SimpleDTO;
use Event4u\DataHelpers\SimpleDTO\Attributes\Required;
use Event4u\DataHelpers\SimpleDTO\Attributes\Email;
use Event4u\DataHelpers\SimpleDTO\Attributes\Between;

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

// Validate and create
$dto = UserDTO::validateAndCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);
```

### Handling Validation Errors

```php
use Event4u\DataHelpers\SimpleDTO\Exceptions\ValidationException;

try {
    $dto = UserDTO::validateAndCreate([
        'name' => '',  // ❌ Required
        'email' => 'invalid',  // ❌ Invalid email
        'age' => 15,  // ❌ Too young
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage();
    print_r($e->errors());
}
```

## Validation Attributes

### Required Validation

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Required;

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        public readonly string $email,

        // Optional - no Required attribute
        public readonly ?string $phone = null,
    ) {}
}
```

### String Validation

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\StringType;
use Event4u\DataHelpers\SimpleDTO\Attributes\Min;
use Event4u\DataHelpers\SimpleDTO\Attributes\Max;
use Event4u\DataHelpers\SimpleDTO\Attributes\Between;

class PostDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3)]
        public readonly string $title,

        #[Required, StringType, Between(10, 1000)]
        public readonly string $content,

        #[StringType, Max(100)]
        public readonly ?string $excerpt = null,
    ) {}
}
```

### Numeric Validation

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\IntegerType;
use Event4u\DataHelpers\SimpleDTO\Attributes\Numeric;
use Event4u\DataHelpers\SimpleDTO\Attributes\Min;
use Event4u\DataHelpers\SimpleDTO\Attributes\Max;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $quantity,

        #[Required, Numeric, Min(0)]
        public readonly float $price,

        #[IntegerType, Between(0, 100)]
        public readonly ?int $discount = null,
    ) {}
}
```

### Email Validation

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Email;

class ContactDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Email]
        public readonly ?string $alternativeEmail = null,
    ) {}
}
```

### URL Validation

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Url;

class WebsiteDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Url]
        public readonly string $website,

        #[Url]
        public readonly ?string $blog = null,
    ) {}
}
```


## All Validation Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `Required` | Field is required | `#[Required]` |
| `Email` | Valid email address | `#[Email]` |
| `Url` | Valid URL | `#[Url]` |
| `Min` | Minimum value/length | `#[Min(3)]` |
| `Max` | Maximum value/length | `#[Max(100)]` |
| `Between` | Value between min and max | `#[Between(18, 120)]` |
| `StringType` | Must be string | `#[StringType]` |
| `IntegerType` | Must be integer | `#[IntegerType]` |
| `Numeric` | Must be numeric | `#[Numeric]` |
| `BooleanType` | Must be boolean | `#[BooleanType]` |
| `ArrayType` | Must be array | `#[ArrayType]` |
| `In` | Value in list | `#[In(['active', 'inactive'])]` |
| `NotIn` | Value not in list | `#[NotIn(['banned'])]` |
| `Regex` | Matches regex | `#[Regex('/^[A-Z]/')]` |
| `Alpha` | Only letters | `#[Alpha]` |
| `AlphaNum` | Letters and numbers | `#[AlphaNum]` |
| `AlphaDash` | Letters, numbers, dashes | `#[AlphaDash]` |
| `Uuid` | Valid UUID | `#[Uuid]` |
| `Json` | Valid JSON | `#[Json]` |
| `Date` | Valid date | `#[Date]` |
| `DateFormat` | Date in format | `#[DateFormat('Y-m-d')]` |
| `Before` | Date before | `#[Before('2024-12-31')]` |
| `After` | Date after | `#[After('2024-01-01')]` |
| `Ip` | Valid IP address | `#[Ip]` |
| `Ipv4` | Valid IPv4 | `#[Ipv4]` |
| `Ipv6` | Valid IPv6 | `#[Ipv6]` |
| `MacAddress` | Valid MAC address | `#[MacAddress]` |
| `Timezone` | Valid timezone | `#[Timezone]` |
| `Unique` | Unique in database | `#[Unique('users', 'email')]` |
| `Exists` | Exists in database | `#[Exists('users', 'id')]` |

## Custom Validation

### Creating Custom Validator

```php
use Event4u\DataHelpers\SimpleDTO\Contracts\ValidationRule;

class EvenNumber implements ValidationRule
{
    public function passes(mixed $value): bool
    {
        return is_int($value) && $value % 2 === 0;
    }

    public function message(): string
    {
        return 'The value must be an even number.';
    }
}

class NumberDTO extends SimpleDTO
{
    public function __construct(
        #[EvenNumber]
        public readonly int $number,
    ) {}
}
```

## Framework Integration

### Laravel Validation

```php
// Automatic Laravel validation
class UserController extends Controller
{
    public function store(Request $request)
    {
        $dto = UserDTO::validateAndCreate($request->all());
        // Automatically uses Laravel's validator
    }
}
```

### Symfony Validation

```php
// Automatic Symfony validation
class UserController
{
    public function store(Request $request)
    {
        $dto = UserDTO::validateAndCreate($request->request->all());
        // Automatically uses Symfony's validator
    }
}
```

## Validation Caching

SimpleDTO caches validation rules for 198x faster performance:

```php
// First call - builds and caches rules
$dto1 = UserDTO::validateAndCreate($data1); // ~10ms

// Subsequent calls - uses cached rules
$dto2 = UserDTO::validateAndCreate($data2); // ~0.05ms (198x faster!)
```

## Best Practices

### Combine Multiple Attributes

```php
// ✅ Good - multiple validation rules
#[Required, StringType, Min(3), Max(50), Alpha]
public readonly string $name;
```

### Use Type Hints with Validation

```php
// ✅ Good - type hint + validation
#[Required, Between(18, 120)]
public readonly int $age;

// ❌ Bad - no type hint
#[Required, Between(18, 120)]
public readonly $age;
```

### Validate Early

```php
// ✅ Good - validate at creation
$dto = UserDTO::validateAndCreate($data);

// ❌ Bad - create then validate
$dto = UserDTO::fromArray($data);
$dto->validate();
```


## Code Examples

The following working examples demonstrate this feature:

- [**Basic Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/basic-validation.php) - Simple validation rules
- [**Advanced Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/advanced-validation.php) - Complex validation scenarios
- [**Request Validation Core**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-core.php) - Core request validation
- [**Laravel Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-laravel.php) - Laravel integration
- [**Symfony Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/request-validation-symfony.php) - Symfony integration
- [**Validation Modes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/validation-modes.php) - Different validation modes
- [**Nested Validation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/validation/nested-validation.php) - Validating nested DTOs

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [ValidationModesTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/ValidationModesTest.php) - Validation mode tests
- [ValidationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/ValidationTest.php) - Core validation tests
- [NestedValidationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/NestedValidationTest.php) - Nested validation tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Validation
```

## See Also

- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
- [Property Mapping](/simple-dto/property-mapping/) - Map property names
