---
title: Property Mapping
description: Learn how to map source keys to different property names using MapFrom attribute
---

Learn how to map source keys to different property names using MapFrom attribute.

## What is Property Mapping?

Property mapping allows you to map source data keys to different property names in your DTO:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('full_name')]
        public readonly string $name,

        #[MapFrom('email_address')]
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);

echo $dto->name;  // 'John Doe'
echo $dto->email; // 'john@example.com'
```

## Basic Usage

### Simple Mapping

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('product_name')]
        public readonly string $name,

        #[MapFrom('product_price')]
        public readonly float $price,

        #[MapFrom('product_sku')]
        public readonly string $sku,
    ) {}
}
```

### Nested Path Mapping

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[MapFrom('contact.email')]
        public readonly string $email,

        #[MapFrom('contact.phone')]
        public readonly ?string $phone = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'contact' => [
        'email' => 'john@example.com',
        'phone' => '+1234567890',
    ],
]);
```

## Real-World Examples

### API Response Mapping

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')]
        public readonly int $id,

        #[MapFrom('user_name')]
        public readonly string $name,

        #[MapFrom('user_email')]
        public readonly string $email,

        #[MapFrom('created_at')]
        public readonly Carbon $createdAt,
    ) {}
}

// Map from API response
$dto = UserDTO::fromArray([
    'user_id' => 1,
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'created_at' => '2024-01-15',
]);
```

### Database Column Mapping

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('order_id')]
        public readonly int $id,

        #[MapFrom('customer_name')]
        public readonly string $customerName,

        #[MapFrom('order_total')]
        public readonly float $total,

        #[MapFrom('order_status')]
        public readonly string $status,
    ) {}
}
```

### Legacy System Integration

```php
class LegacyUserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('usr_id')]
        public readonly int $userId,

        #[MapFrom('usr_nm')]
        public readonly string $userName,

        #[MapFrom('usr_eml')]
        public readonly string $userEmail,

        #[MapFrom('usr_crt_dt')]
        public readonly Carbon $createdDate,
    ) {}
}
```

## Combining with Other Features

### MapFrom + Cast

```php
class EventDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('event_name')]
        public readonly string $name,

        #[MapFrom('event_date'), Cast(DateTimeCast::class)]
        public readonly Carbon $date,
    ) {}
}
```

### MapFrom + Validation

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('full_name'), Required, Min(3)]
        public readonly string $name,

        #[MapFrom('email_address'), Required, Email]
        public readonly string $email,
    ) {}
}
```

## Best Practices

### Use Descriptive Property Names

```php
// ✅ Good - clear property names
#[MapFrom('usr_nm')]
public readonly string $userName;

// ❌ Bad - unclear abbreviation
#[MapFrom('usr_nm')]
public readonly string $usrNm;
```

### Document Mappings

```php
/**
 * @property int $id Mapped from 'user_id'
 * @property string $name Mapped from 'full_name'
 */
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_id')]
        public readonly int $id,

        #[MapFrom('full_name')]
        public readonly string $name,
    ) {}
}
```

### Use for External APIs

```php
// ✅ Good - map external API to clean internal names
#[MapFrom('external_api_field_name')]
public readonly string $cleanPropertyName;
```


## Code Examples

The following working examples demonstrate this feature:

- [**Basic Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/property-mapping/basic-mapping.php) - Property name mapping

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [PropertyMappingTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/PropertyMappingTest.php) - Property mapping tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=PropertyMapping
```

## See Also

- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
- [Validation](/simple-dto/validation/) - Validate your data
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
