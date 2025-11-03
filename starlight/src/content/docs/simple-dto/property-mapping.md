---
title: Property Mapping
description: Learn how to map source keys to different property names using MapFrom attribute
---

Learn how to map source keys to different property names using MapFrom attribute.

## What is Property Mapping?

Property mapping allows you to map source data keys to different property names in your Dto:

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('full_name')]
        public readonly string $name,

        #[MapFrom('email_address')]
        public readonly string $email,
    ) {}
}

$dto = UserDto::fromArray([
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);

echo $dto->name;  // 'John Doe'
echo $dto->email; // 'john@example.com'
```

## Basic Usage

### Simple Mapping

```php
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

class ProductDto extends SimpleDto
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
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[MapFrom('contact.email')]
        public readonly string $email,

        #[MapFrom('contact.phone')]
        public readonly ?string $phone = null,
    ) {}
}

$dto = UserDto::fromArray([
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
class UserDto extends SimpleDto
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
$dto = UserDto::fromArray([
    'user_id' => 1,
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'created_at' => '2024-01-15',
]);
```

### Database Column Mapping

```php
class OrderDto extends SimpleDto
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
class LegacyUserDto extends SimpleDto
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
class EventDto extends SimpleDto
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
class UserDto extends SimpleDto
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
class UserDto extends SimpleDto
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


## Advanced Mapping with Templates

### Using mapperTemplate()

For complex mappings, you can define a template method in your DTO:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;

class UserDto extends SimpleDto
{
    use SimpleDtoMapperTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $city,
    ) {}

    protected function mapperTemplate(): array
    {
        return [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
            'city' => '{{ user.address.city }}',
        ];
    }
}

// Template is automatically applied
$dto = UserDto::fromArray([
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => [
            'city' => 'New York',
        ],
    ],
]);

echo $dto->name;  // 'John Doe'
echo $dto->city;  // 'New York'
```

### Using mapperFilters()

Apply filters to specific properties during mapping:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;
use event4u\DataHelpers\Filters\TrimStrings;
use event4u\DataHelpers\Filters\LowercaseStrings;

class UserDto extends SimpleDto
{
    use SimpleDtoMapperTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    protected function mapperFilters(): array
    {
        return [
            'name' => new TrimStrings(),
            'email' => [new TrimStrings(), new LowercaseStrings()],
        ];
    }
}

// Filters are automatically applied
$dto = UserDto::fromArray([
    'name' => '  John Doe  ',
    'email' => '  JOHN@EXAMPLE.COM  ',
]);

echo $dto->name;  // 'John Doe' (trimmed)
echo $dto->email; // 'john@example.com' (trimmed and lowercased)
```

### Using mapperPipeline()

Apply global filters to all properties:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;
use event4u\DataHelpers\Filters\TrimStrings;

class UserDto extends SimpleDto
{
    use SimpleDtoMapperTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $city,
    ) {}

    protected function mapperPipeline(): array
    {
        return [new TrimStrings()];
    }
}

// Pipeline is automatically applied to all properties
$dto = UserDto::fromArray([
    'name' => '  John Doe  ',
    'email' => '  john@example.com  ',
    'city' => '  New York  ',
]);

echo $dto->name;  // 'John Doe' (trimmed)
echo $dto->email; // 'john@example.com' (trimmed)
echo $dto->city;  // 'New York' (trimmed)
```

### Combining Template, Filters, and Pipeline

You can combine all three for powerful data transformation:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoMapperTrait;
use event4u\DataHelpers\Filters\TrimStrings;
use event4u\DataHelpers\Filters\LowercaseStrings;

class UserDto extends SimpleDto
{
    use SimpleDtoMapperTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $city,
    ) {}

    protected function mapperTemplate(): array
    {
        return [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
            'city' => '{{ user.address.city }}',
        ];
    }

    protected function mapperFilters(): array
    {
        return [
            'email' => new LowercaseStrings(),
        ];
    }

    protected function mapperPipeline(): array
    {
        return [new TrimStrings()];
    }
}

// All transformations are automatically applied
$dto = UserDto::fromArray([
    'user' => [
        'name' => '  John Doe  ',
        'email' => '  JOHN@EXAMPLE.COM  ',
        'address' => [
            'city' => '  New York  ',
        ],
    ],
]);

echo $dto->name;  // 'John Doe' (template + pipeline)
echo $dto->email; // 'john@example.com' (template + filters + pipeline)
echo $dto->city;  // 'New York' (template + pipeline)
```

### Overriding at Runtime

You can override DTO configuration at runtime:

```php
// Override template
$dto = UserDto::from($data, [
    'name' => '{{ custom.path }}',
]);

// Override filters
$dto = UserDto::from($data, null, [
    'name' => new CustomFilter(),
]);

// Override pipeline (merged with DTO pipeline)
$dto = UserDto::from($data, null, null, [
    new AdditionalFilter(),
]);
```

**Priority:**
1. Runtime parameters (highest priority)
2. DTO configuration (`mapperTemplate()`, `mapperFilters()`, `mapperPipeline()`)
3. `#[MapFrom]` attributes (only when no template is applied)
4. Auto-mapping (lowest priority)

## Code Examples

The following working examples demonstrate this feature:

- [**Basic Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/property-mapping/basic-mapping.php) - Property name mapping

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [PropertyMappingTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/PropertyMappingTest.php) - Property mapping tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=PropertyMapping
```

## See Also

- [Type Casting](/data-helpers/simple-dto/type-casting/) - Automatic type conversion
- [Validation](/data-helpers/simple-dto/validation/) - Validate your data
- [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Creation methods
