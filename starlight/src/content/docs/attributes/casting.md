---
title: Casting Attributes
description: Reference for type casting with 20+ built-in cast classes
---

Reference for type casting attributes and 20+ built-in cast classes.

## The Cast Attribute

### #[Cast(string $castClass)]

Cast property to a specific type:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;
```

## Built-in Cast Classes

### Primitive Types

<!-- skip-test: property declaration only -->
```php
#[Cast(StringCast::class)]   // Cast to string
#[Cast(IntCast::class)]      // Cast to integer
#[Cast(FloatCast::class)]    // Cast to float
#[Cast(BoolCast::class)]     // Cast to boolean
#[Cast(ArrayCast::class)]    // Cast to array
```

### Date & Time

<!-- skip-test: property declaration only -->
```php
#[Cast(DateTimeCast::class)]  // Cast to Carbon
#[Cast(DateCast::class)]      // Cast to Carbon (date only)
#[Cast(TimestampCast::class)] // Cast from timestamp
```

### Enums

<!-- skip-test: property declaration only -->
```php
#[Cast(EnumCast::class)]       // Cast to enum
#[Cast(BackedEnumCast::class)] // Cast to backed enum
```

### Collections

<!-- skip-test: property declaration only -->
```php
#[Cast(CollectionCast::class)]     // Cast to Collection
#[Cast(DataCollectionCast::class)] // Cast to DataCollection
```

### Objects & JSON

<!-- skip-test: property declaration only -->
```php
#[Cast(ObjectCast::class)]  // Cast to object
#[Cast(JsonCast::class)]    // Cast JSON to array
```

### Security

<!-- skip-test: property declaration only -->
```php
#[Cast(EncryptedCast::class)]  // Encrypt/decrypt
#[Cast(HashCast::class)]       // One-way hash
```

### Other

<!-- skip-test: property declaration only -->
```php
#[Cast(DecimalCast::class)]    // Cast to decimal
#[Cast(UuidCast::class)]       // Cast to UUID
#[Cast(IpAddressCast::class)]  // Cast to IP address
#[Cast(UrlCast::class)]        // Cast to URL
```

## Cast Options

```php
// Date format
#[Cast(DateTimeCast::class, format: 'Y-m-d H:i:s')]
public readonly Carbon $createdAt;

// Nullable cast
#[Cast(IntCast::class, nullable: true)]
public readonly ?int $age;

// Default value
#[Cast(StringCast::class, default: 'N/A')]
public readonly string $name;
```

## Custom Casts

```php
use event4u\DataHelpers\SimpleDTO\Contracts\Cast;

class UpperCaseCast implements Cast
{
    public function cast(mixed $value): string
    {
        return strtoupper((string) $value);
    }
}

#[Cast(UpperCaseCast::class)]
public readonly string $name;
```

## Real-World Example

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Cast(StringCast::class)]
        public readonly string $name,

        #[Cast(DateTimeCast::class)]
        public readonly Carbon $birthDate,

        #[Cast(EnumCast::class)]
        public readonly Status $status,

        #[Cast(EncryptedCast::class)]
        public readonly string $ssn,
    ) {}
}
```

## See Also

- [Type Casting](/simple-dto/type-casting/) - Detailed guide
- [Validation Attributes](/attributes/validation/) - Validation reference
