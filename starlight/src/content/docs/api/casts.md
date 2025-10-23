---
title: Casts API
description: Complete API reference for cast classes
---

Complete API reference for cast classes.

## Primitive Casts

### StringCast

```php
#[Cast(StringCast::class)]
public readonly string $name;
```

### IntCast

```php
#[Cast(IntCast::class)]
public readonly int $age;
```

### FloatCast

```php
#[Cast(FloatCast::class)]
public readonly float $price;
```

### BoolCast

```php
#[Cast(BoolCast::class)]
public readonly bool $active;
```

### ArrayCast

```php
#[Cast(ArrayCast::class)]
public readonly array $tags;
```

## Date & Time Casts

### DateTimeCast

```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(DateTimeCast::class, format: 'Y-m-d H:i:s')]
public readonly Carbon $updatedAt;
```

### DateCast

```php
#[Cast(DateCast::class)]
public readonly Carbon $birthDate;
```

### TimeCast

```php
#[Cast(TimeCast::class)]
public readonly Carbon $startTime;
```

### TimestampCast

```php
#[Cast(TimestampCast::class)]
public readonly Carbon $timestamp;
```

## Enum Casts

### EnumCast

```php
#[Cast(EnumCast::class)]
public readonly Status $status;
```

### BackedEnumCast

```php
#[Cast(BackedEnumCast::class)]
public readonly Role $role;
```

## Collection Casts

### CollectionCast

```php
#[Cast(CollectionCast::class)]
public readonly Collection $items;
```

### DataCollectionCast

```php
#[Cast(DataCollectionCast::class, itemClass: UserDTO::class)]
public readonly DataCollection $users;
```

## Object Casts

### ObjectCast

```php
#[Cast(ObjectCast::class, class: Address::class)]
public readonly Address $address;
```

### DTOCast

```php
#[Cast(DTOCast::class, class: UserDTO::class)]
public readonly UserDTO $user;
```

## Security Casts

### EncryptedCast

```php
#[Cast(EncryptedCast::class)]
public readonly string $ssn;
```

### HashedCast

```php
#[Cast(HashedCast::class)]
public readonly string $password;
```

## Special Casts

### JsonCast

```php
#[Cast(JsonCast::class)]
public readonly array $metadata;
```

### DecimalCast

```php
#[Cast(DecimalCast::class, precision: 2)]
public readonly string $price;
```

### UuidCast

```php
#[Cast(UuidCast::class)]
public readonly string $uuid;
```

### IpAddressCast

```php
#[Cast(IpAddressCast::class)]
public readonly string $ipAddress;
```

### UrlCast

```php
#[Cast(UrlCast::class)]
public readonly string $website;
```

## Custom Casts

### Creating a Custom Cast

```php
use event4u\DataHelpers\SimpleDTO\Contracts\Cast;

class UpperCaseCast implements Cast
{
    public function cast(mixed $value): string
    {
        return strtoupper((string) $value);
    }
    
    public function uncast(mixed $value): string
    {
        return strtolower((string) $value);
    }
}

// Usage
#[Cast(UpperCaseCast::class)]
public readonly string $name;
```

## See Also

- [Type Casting Guide](/simple-dto/type-casting/) - Complete guide
- [Custom Casts](/advanced/custom-casts/) - Create custom casts
- [Casting Attributes](/attributes/casting/) - Cast attribute reference

