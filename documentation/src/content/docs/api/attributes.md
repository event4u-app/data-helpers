---
title: Attributes API
description: Complete API reference for attributes
---

Complete API reference for attributes.

## Validation Attributes

### Required

```php
#[Required]
public readonly string $name;
```

### Email

```php
#[Email]
public readonly string $email;
```

### Min / Max

```php
#[Min(3), Max(50)]
public readonly string $name;
```

### Between

```php
#[Between(18, 65)]
public readonly int $age;
```

### In / NotIn

```php
#[In(['active', 'inactive'])]
public readonly string $status;
```

### Unique / Exists

```php
#[Unique('users', 'email')]
public readonly string $email;

#[Exists('users', 'id')]
public readonly int $userId;
```

## Conditional Attributes

### WhenAuth / WhenGuest

```php
#[WhenAuth]
public readonly ?string $privateData = null;

#[WhenGuest]
public readonly ?string $publicData = null;
```

### WhenCan

```php
#[WhenCan('edit')]
public readonly ?string $editUrl = null;
```

### WhenRole

```php
#[WhenRole('admin')]
public readonly ?string $adminNotes = null;
```

### WhenValue

```php
#[WhenValue('status', 'published')]
public readonly ?Carbon $publishedAt = null;
```

## Cast Attribute

### Cast

```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(IntCast::class)]
public readonly int $age;
```

## Mapping Attributes

### MapFrom

```php
#[MapFrom('user.full_name')]
public readonly string $name;
```

### MapTo

```php
#[MapTo('user.full_name')]
public readonly string $name;
```

## Visibility Attributes

### Hidden

```php
#[Hidden]
public readonly string $password;
```

### Visible

```php
#[Visible(['admin', 'owner'])]
public readonly string $secret;
```

## Other Attributes

### Lazy

```php
#[Lazy]
public readonly ?array $posts = null;
```

### Computed

```php
#[Computed]
public function fullName(): string
{
    return "{$this->firstName} {$this->lastName}";
}
```

## See Also

- [Attributes Overview](/attributes/overview/) - Complete overview
- [Validation Attributes](/attributes/validation/) - Validation guide
- [Conditional Attributes](/attributes/conditional/) - Conditional guide
- [Casting Attributes](/attributes/casting/) - Casting guide

