---
title: Mapping Attributes
description: Reference for property mapping attributes
---

Reference for property mapping attributes.

## Introduction

SimpleDto provides 3 mapping attributes:

- **#[Map(string|array $key)]** - Bidirectional mapping (combines MapFrom and MapTo)
- **#[MapFrom(string|array $source)]** - Map from different input key
- **#[MapTo(string $target)]** - Map to different output key

:::tip[Recommended]
Use `#[Map]` for bidirectional mapping to reduce code duplication. It combines both `#[MapFrom]` and `#[MapTo]` in a single attribute.
:::

## Map Attribute

The `#[Map]` attribute provides bidirectional mapping - it works for both input (`fromArray()`) and output (`toArray()`).

### Basic Usage

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\Map;

// Instead of: #[MapFrom('user_name'), MapTo('user_name')]
#[Map('user_name')]
public readonly string $name;

#[Map('email_address')]
public readonly string $email;
```

### Nested Path Mapping

<!-- skip-test: property declaration only -->
```php
#[Map('user.profile.name')]
public readonly string $name;

#[Map('contact.email')]
public readonly string $email;
```

**Input:**
```php
$dto = UserDto::fromArray([
    'user' => ['profile' => ['name' => 'John']],
    'contact' => ['email' => 'john@example.com'],
]);
```

**Output:**
```php
$array = $dto->toArray();
// ['user' => ['profile' => ['name' => 'John']], 'contact' => ['email' => 'john@example.com']]
```

### Multiple Sources (Fallback)

For input mapping, you can provide multiple source keys as fallback:

<!-- skip-test: property declaration only -->
```php
#[Map(['email', 'email_address', 'mail'])]
public readonly string $email;
```

The first available key will be used. For output, only the first key is used.

### Complete Example

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Map;

class UserDto extends SimpleDto
{
    public function __construct(
        #[Map('user_id')]
        public readonly int $id,

        #[Map('user_name')]
        public readonly string $name,

        #[Map(['email', 'email_address'])]
        public readonly string $email,
    ) {}
}

// Input mapping
$user = UserDto::fromArray([
    'user_id' => 123,
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com',
]);

// Output mapping
$array = $user->toArray();
// ['user_id' => 123, 'user_name' => 'John Doe', 'email' => 'john@example.com']
```

### Priority

`#[Map]` has priority over `#[MapFrom]` and `#[MapTo]`:

<!-- skip-test: property declaration only -->
```php
// Map takes precedence
#[Map('mapped_name'), MapFrom('old_name'), MapTo('old_name')]
public readonly string $name;
// Will use 'mapped_name' for both input and output
```

## MapFrom Attribute

Map input data from a different key:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

#[MapFrom('full_name')]
public readonly string $name;

#[MapFrom('email_address')]
public readonly string $email;
```

### Nested Path Mapping

<!-- skip-test: property declaration only -->
```php
#[MapFrom('contact.email')]
public readonly string $email;

#[MapFrom('address.city.name')]
public readonly string $city;
```

### Multiple Sources (Fallback)

<!-- skip-test: property declaration only -->
```php
#[MapFrom(['user.email', 'user.mail', 'email'])]
public readonly string $email;
```

## MapTo Attribute

Map output data to a different key:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;

#[MapTo('full_name')]
public readonly string $name;

#[MapTo('email_address')]
public readonly string $email;
```

### Nested Output

<!-- skip-test: property declaration only -->
```php
#[MapTo('user.profile.email')]
public readonly string $email;
// Output: ['user' => ['profile' => ['email' => '...']]]
```

## Bidirectional Mapping

### Using Map Attribute (Recommended)

<!-- skip-test: property declaration only -->
```php
// ✅ Recommended: Single attribute
#[Map('user_name')]
public readonly string $userName;
```

### Using MapFrom + MapTo

<!-- skip-test: property declaration only -->
```php
// ⚠️ Works but verbose
#[MapFrom('user_name'), MapTo('user_name')]
public readonly string $userName;
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
    ) {}
}
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
    ) {}
}
```

## Combining with Other Attributes

```php
// Map + Validation
#[Map('user_email'), Required, Email]
public readonly string $email;

// Map + Cast
#[Map('created_at'), Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

// MapFrom + Validation (also works)
#[MapFrom('user_email'), Required, Email]
public readonly string $email;
```

## Best Practices

### Use Map for Bidirectional Mapping

```php
// ✅ Good: Single attribute
#[Map('user_name')]
public readonly string $userName;

// ❌ Bad: Two attributes for the same mapping
#[MapFrom('user_name'), MapTo('user_name')]
public readonly string $userName;
```

### Use Descriptive Property Names

```php
// ✅ Good
#[Map('usr_nm')]
public readonly string $userName;

// ❌ Bad
#[Map('usr_nm')]
public readonly string $usrNm;
```

### Use Fallback Sources for Flexible APIs

```php
// ✅ Good: Handles different API versions
#[Map(['email', 'email_address', 'user_email'])]
public readonly string $email;
```

## See Also

- [Property Mapping](/data-helpers/simple-dto/property-mapping/) - Detailed guide
- [Validation Attributes](/data-helpers/attributes/validation/) - Validation reference
