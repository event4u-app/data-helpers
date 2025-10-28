---
title: Casting Attributes
description: Reference for type casting with 20+ built-in cast classes
---

Reference for type casting attributes and 20+ built-in cast classes.

## Understanding Type Casting in SimpleDtos

:::note[Important: strict_types=1]
All SimpleDto files use `declare(strict_types=1)`, which means **PHP does NOT perform automatic type coercion**. Without casting, passing `'30'` to an `int` property will throw a `TypeError`.
:::

### Casting Priority (Highest to Lowest)

SimpleDtos apply casts in the following order:

1. **Explicit `#[Cast]` attributes** (highest priority)
2. **Attribute-based casts** (`#[DataCollectionOf]`, `#[ConvertEmptyToNull]`)
3. **Nested DTOs** (always auto-casted, unless `#[NoCasts]` is used)
4. **`#[AutoCast]` for native types** (lowest priority, opt-in)

### What Gets Casted Automatically?

| Type | Without `#[AutoCast]` | With `#[AutoCast]` | With `#[NoCasts]` |
|------|----------------------|-------------------|------------------|
| **Nested DTOs** | ✅ Auto-casted | ✅ Auto-casted | ❌ Disabled |
| **Native types** (int, string, etc.) | ❌ TypeError | ✅ Auto-casted | ❌ TypeError |
| **Explicit `#[Cast]`** | ✅ Applied | ✅ Applied | ❌ Disabled |

## The AutoCast Attribute

### #[AutoCast]

Enable automatic native PHP type casting for primitive types (opt-in).

```php
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

// Class-level: Enable for ALL properties
#[AutoCast]
class UserDto extends SimpleDto
{
    public readonly int $id;        // "123" → 123 ✅
    public readonly string $name;   // 123 → "123" ✅
}

// Property-level: Enable for specific properties
class UserDto extends SimpleDto
{
    #[AutoCast]
    public readonly int $id;        // "123" → 123 ✅

    public readonly string $name;   // Must be string (no conversion)
}
```

**Key Points:**
- ✅ Automatic type casting is **opt-in** (not enabled by default)
- ✅ Only affects **native PHP types** (int, string, float, bool, array)
- ✅ Nested DTOs are **always auto-casted** (even without `#[AutoCast]`)
- ✅ Explicit casts (like `#[Cast]`) **ALWAYS work**, regardless of `#[AutoCast]`
- ✅ Use `#[AutoCast]` for CSV/XML imports or inconsistent data sources
- ✅ Skip `#[AutoCast]` for better performance and strict type checking

## Nested DTOs (Always Auto-Casted)

Nested DTOs are **always automatically casted**, even without `#[AutoCast]`:

```php
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,  // ← Always auto-casted!
    ) {}
}

// Array is automatically converted to AddressDto
$user = UserDto::fromArray([
    'name' => 'John',
    'address' => ['street' => 'Main St', 'city' => 'NYC'],  // ← Array → AddressDto
]);
```

**To disable nested DTO casting**, use `#[NoCasts]`:

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoCasts]
class StrictUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,  // ← Must be AddressDto object!
    ) {}
}

// ❌ TypeError - array is not auto-casted with #[NoCasts]
$user = StrictUserDto::fromArray([
    'name' => 'John',
    'address' => ['street' => 'Main St', 'city' => 'NYC'],
]);

// ✅ Works - pass AddressDto object
$user = StrictUserDto::fromArray([
    'name' => 'John',
    'address' => AddressDto::fromArray(['street' => 'Main St', 'city' => 'NYC']),
]);
```

## The Cast Attribute

### #[Cast(string $castClass)]

Cast property to a specific type (always applied, regardless of `#[AutoCast]`):

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;
```

**Note:** `#[Cast]` attributes are disabled by `#[NoCasts]` for maximum performance.

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
use event4u\DataHelpers\SimpleDto\Contracts\Cast;

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
class UserDto extends SimpleDto
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

## Interaction with Performance Attributes

Performance attributes can disable casting operations:

### #[NoCasts] - Disables ALL Casts

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoCasts]
class StrictDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
        public readonly AddressDto $address,
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $date,
    ) {}
}

// ❌ ALL casts are disabled:
// - Native types: TypeError if wrong type
// - Nested DTOs: TypeError if array (must be object)
// - Explicit #[Cast]: Disabled
```

### #[NoAttributes] - Disables Attribute-Based Casts

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

#[NoAttributes]
class SimpleDto extends SimpleDto
{
    public function __construct(
        public readonly int $age,
        public readonly AddressDto $address,
        #[Cast(DateTimeCast::class)]  // ← Disabled!
        public readonly Carbon $date,
    ) {}
}

// ✅ Nested DTOs still work (always auto-casted)
// ❌ #[Cast] attributes are disabled
// ❌ Native types: TypeError if wrong type (no AutoCast)
```

### Comparison

| Attribute | Nested DTOs | Native Types | Explicit `#[Cast]` |
|-----------|-------------|--------------|-------------------|
| **None** | ✅ Auto | ❌ TypeError | ✅ Applied |
| **`#[AutoCast]`** | ✅ Auto | ✅ Auto | ✅ Applied |
| **`#[NoCasts]`** | ❌ TypeError | ❌ TypeError | ❌ Disabled |
| **`#[NoAttributes]`** | ✅ Auto | ❌ TypeError | ❌ Disabled |

See [Performance Attributes](/data-helpers/attributes/performance/) for more details.

## See Also

- [Type Casting](/data-helpers/simple-dto/type-casting/) - Detailed guide
- [Performance Attributes](/data-helpers/attributes/performance/) - Performance optimization
- [Validation Attributes](/data-helpers/attributes/validation/) - Validation reference
