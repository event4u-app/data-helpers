---
title: Mapping Attributes
description: Reference for property mapping attributes
---

Reference for property mapping attributes.

## Overview

SimpleDTO provides 2 mapping attributes:

- **#[MapFrom(string $source)]** - Map from different input key
- **#[MapTo(string $target)]** - Map to different output key

## MapFrom Attribute

Map input data from a different key:

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

#[MapFrom('full_name')]
public readonly string $name;

#[MapFrom('email_address')]
public readonly string $email;
```

### Nested Path Mapping

```php
#[MapFrom('contact.email')]
public readonly string $email;

#[MapFrom('address.city.name')]
public readonly string $city;
```

### Multiple Sources (Fallback)

```php
#[MapFrom(['user.email', 'user.mail', 'email'])]
public readonly string $email;
```

## MapTo Attribute

Map output data to a different key:

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\MapTo;

#[MapTo('full_name')]
public readonly string $name;

#[MapTo('email_address')]
public readonly string $email;
```

### Nested Output

```php
#[MapTo('user.profile.email')]
public readonly string $email;
// Output: ['user' => ['profile' => ['email' => '...']]]
```

## Bidirectional Mapping

```php
#[MapFrom('user_name'), MapTo('user_name')]
public readonly string $userName;
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
    ) {}
}
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
    ) {}
}
```

## Combining with Other Attributes

```php
// MapFrom + Validation
#[MapFrom('user_email'), Required, Email]
public readonly string $email;

// MapFrom + Cast
#[MapFrom('created_at'), Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;
```

## Best Practices

### Use Descriptive Property Names

```php
// ✅ Good
#[MapFrom('usr_nm')]
public readonly string $userName;

// ❌ Bad
#[MapFrom('usr_nm')]
public readonly string $usrNm;
```

## See Also

- [Property Mapping](/simple-dto/property-mapping/) - Detailed guide
- [Validation Attributes](/attributes/validation/) - Validation reference
