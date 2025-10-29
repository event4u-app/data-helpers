---
title: Type Casting
description: Learn how SimpleDto automatically converts data types using built-in and custom casts
---

Learn how SimpleDto automatically converts data types using built-in and custom casts.

## What is Type Casting?

Type casting automatically converts input data to the correct PHP type. For example:

```php
// Input: string "30"
// Output: int 30

// Input: string "2024-01-15"
// Output: Carbon instance
```

## Automatic Type Casting with #[AutoCast]

**NEW in v2.0**: Automatic native PHP type casting is now **opt-in** using the `#[AutoCast]` attribute.

### Why #[AutoCast]?

By default, SimpleDtos do **NOT** automatically convert types. This provides:
- ✅ **Better Performance** - Skip unnecessary type conversion overhead
- ✅ **Strict Type Safety** - Catch type mismatches early
- ✅ **Explicit Behavior** - Clear when type conversion happens

**Important**: Explicit casts (like `#[Cast(DateTimeCast::class)]`) **ALWAYS work**, regardless of `#[AutoCast]`.

### Option 1: Class-level #[AutoCast] (All Properties)

Enable automatic type casting for **all properties**:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]  // ← Enable automatic type casting for ALL properties
class UserDtoAutoCast extends SimpleDto
{
    public function __construct(
        public readonly int $id,        // "123" → 123 ✅
        public readonly string $name,   // 123 → "123" ✅
        public readonly bool $active,   // "1" → true ✅
    ) {}
}

$dto = UserDtoAutoCast::fromArray([
    'id' => "123",      // String → Int (automatic)
    'name' => 456,      // Int → String (automatic)
    'active' => "1",    // String → Bool (automatic)
]);
```

### Option 2: Property-level #[AutoCast] (Specific Properties)

Enable automatic type casting for **specific properties only**:

```php
class UserDtoParamCast extends SimpleDto
{
    public function __construct(
        #[AutoCast]  // ← Only this property gets automatic type casting
        public readonly int $id,        // "123" → 123 ✅

        public readonly string $name,   // 123 → Type Error ❌ (no AutoCast)
    ) {}
}

$dto = UserDtoParamCast::fromArray([
    'id' => "123",      // String → Int (automatic) ✅
    'name' => "John",   // String → String (no conversion needed) ✅
]);
```

### Option 3: No #[AutoCast] (Strict Types)

**Best for performance** - No automatic type casting, strict type checking:

```php
class UserDtoNoCast extends SimpleDto
{
    public function __construct(
        public readonly int $id,        // Must be int, no conversion
        public readonly string $name,   // Must be string, no conversion
    ) {}
}

$dto = UserDtoNoCast::fromArray([
    'id' => 123,        // Int → Int ✅
    'name' => "John",   // String → String ✅
]);

// This will throw a TypeError:
$dto = UserDto::fromArray([
    'id' => "123",      // String → Type Error ❌
]);
```

### Option 4: Explicit Casts (Always Work)

Explicit cast attributes **ALWAYS work**, regardless of `#[AutoCast]`:

```php
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

class UserDtoMixedCasts extends SimpleDto
{
    public function __construct(
        // ✅ ALWAYS casted (explicit cast attribute)
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,

        // ❌ NOT casted (no AutoCast, no explicit cast)
        public readonly int $id,  // "123" → Type Error

        // ✅ Casted (AutoCast enabled)
        #[AutoCast]
        public readonly string $name,  // 123 → "123"
    ) {}
}
```

### When to Use #[AutoCast]

**Use #[AutoCast] when:**
- ✅ Working with CSV, XML, or other string-based formats
- ✅ Need automatic type conversion (string → int, int → string, etc.)
- ✅ Importing data from external sources with inconsistent types

**Don't use #[AutoCast] when:**
- ✅ Working with strictly typed APIs (JSON with correct types)
- ✅ Performance is critical
- ✅ You want strict type checking

### Casting Priority

1. **Explicit cast attributes** (`#[Cast]`, `#[DataCollectionOf]`, etc.) → **ALWAYS applied**
2. **casts() method** → **ALWAYS applied**
3. **#[AutoCast] + native PHP types** → Only if `#[AutoCast]` present
4. **No casting** → If none of the above

## Built-in Casts

SimpleDto provides 20+ built-in casts for common data types.

## Date and Time Casts

### DateTimeCast

```php
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;

class EventDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[Cast(DateTimeCast::class)]
        public readonly Carbon $startDate,

        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $endDate,
    ) {}
}

$dto = EventDto::fromArray([
    'title' => 'Conference',
    'startDate' => '2024-01-15 10:00:00',
    'endDate' => '2024-01-15',
]);

echo $dto->startDate->format('F j, Y'); // January 15, 2024
```

### DateCast

```php
use event4u\DataHelpers\SimpleDto\Casts\DateCast;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Cast(DateCast::class)]
        public readonly Carbon $birthDate,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'birthDate' => '1990-05-15',
]);
```

## Enum Casts

### EnumCast

```php
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Casts\EnumCast;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Cast(EnumCast::class)]
        public readonly Status $status,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'status' => 'active',  // String → Status::ACTIVE
]);

echo $dto->status->value; // active
```

### BackedEnumCast

```php
enum Priority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

class TaskDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[Cast(EnumCast::class)]
        public readonly Priority $priority,
    ) {}
}

$dto = TaskDto::fromArray([
    'title' => 'Important Task',
    'priority' => 3,  // Int → Priority::HIGH
]);
```

## Collection Casts

### ArrayCast

```php
use event4u\DataHelpers\SimpleDto\Casts\ArrayCast;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Cast(ArrayCast::class)]
        public readonly array $tags,

        #[Cast(ArrayCast::class, itemType: 'int')]
        public readonly array $scores,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'tags' => 'tag1,tag2,tag3',  // String → Array
    'scores' => ['10', '20', '30'],  // String[] → Int[]
]);
```


### CollectionCast

```php
use event4u\DataHelpers\SimpleDto\Casts\CollectionCast;

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $orderId,

        #[Cast(CollectionCast::class, itemType: OrderItemDto::class)]
        public readonly array $items,
    ) {}
}

$dto = OrderDto::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2],
        ['product' => 'Gadget', 'quantity' => 1],
    ],
]);
```

## Object Casts

### ObjectCast

```php
use event4u\DataHelpers\SimpleDto\Casts\ObjectCast;

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

        #[Cast(ObjectCast::class, type: AddressDto::class)]
        public readonly AddressDto $address,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
    ],
]);
```

## Encrypted Casts

### EncryptedCast

```php
use event4u\DataHelpers\SimpleDto\Casts\EncryptedCast;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Cast(EncryptedCast::class)]
        public readonly string $ssn,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'John Doe',
    'ssn' => '123-45-6789',  // Encrypted when stored
]);

// Automatically decrypted when accessed
echo $dto->ssn; // 123-45-6789
```

## Custom Casts

### Creating a Custom Cast

```php
use event4u\DataHelpers\SimpleDto\Contracts\Cast;

class UpperCaseCast implements Cast
{
    public function cast(mixed $value): mixed
    {
        return strtoupper($value);
    }

    public function uncast(mixed $value): mixed
    {
        return strtolower($value);
    }
}

class UserDto extends SimpleDto
{
    public function __construct(
        #[Cast(UpperCaseCast::class)]
        public readonly string $name,
    ) {}
}

$dto = UserDto::fromArray([
    'name' => 'john doe',  // → "JOHN DOE"
]);
```

### Custom Cast with Parameters

```php
class TruncateCast implements Cast
{
    public function __construct(
        private int $length = 100
    ) {}

    public function cast(mixed $value): mixed
    {
        return substr($value, 0, $this->length);
    }

    public function uncast(mixed $value): mixed
    {
        return $value;
    }
}

class PostDto extends SimpleDto
{
    public function __construct(
        #[Cast(TruncateCast::class, length: 50)]
        public readonly string $title,
    ) {}
}
```

## All Built-in Casts

| Cast | Description | Example |
|------|-------------|---------|
| `StringCast` | Convert to string | `123` → `"123"` |
| `IntegerCast` | Convert to integer | `"30"` → `30` |
| `FloatCast` | Convert to float | `"19.99"` → `19.99` |
| `BooleanCast` | Convert to boolean | `"1"` → `true` |
| `ArrayCast` | Convert to array | `"a,b"` → `["a","b"]` |
| `DateTimeCast` | Convert to Carbon | `"2024-01-15"` → `Carbon` |
| `DateCast` | Convert to Carbon (date only) | `"2024-01-15"` → `Carbon` |
| `EnumCast` | Convert to enum | `"active"` → `Status::ACTIVE` |
| `ObjectCast` | Convert to object | `[...]` → `AddressDto` |
| `CollectionCast` | Convert to collection | `[...]` → `Collection` |
| `JsonCast` | Parse JSON | `"{...}"` → `array` |
| `EncryptedCast` | Encrypt/decrypt | `"secret"` → encrypted |
| `HashCast` | Hash value | `"password"` → hashed |
| `UrlCast` | Validate URL | `"example.com"` → `"https://example.com"` |
| `EmailCast` | Normalize email | `"JOHN@EXAMPLE.COM"` → `"john@example.com"` |
| `PhoneCast` | Format phone | `"1234567890"` → `"+1 (234) 567-8900"` |
| `MoneyCast` | Format money | `1999` → `"$19.99"` |
| `PercentageCast` | Format percentage | `0.15` → `"15%"` |
| `SlugCast` | Create slug | `"Hello World"` → `"hello-world"` |
| `UuidCast` | Validate UUID | `"..."` → UUID |

## Cast Options

### Format Option

```php
#[Cast(DateTimeCast::class, format: 'Y-m-d H:i:s')]
public readonly Carbon $createdAt;
```

### Nullable Option

```php
#[Cast(DateTimeCast::class, nullable: true)]
public readonly ?Carbon $deletedAt;
```

### Default Option

```php
#[Cast(IntegerCast::class, default: 0)]
public readonly int $count;
```

## Best Practices

### Use Specific Casts

```php
// ✅ Good - explicit cast
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

// ❌ Bad - relying on type hint only
public readonly Carbon $createdAt;
```

### Specify Format for Dates

```php
// ✅ Good - explicit format
#[Cast(DateTimeCast::class, format: 'Y-m-d')]
public readonly Carbon $date;

// ❌ Bad - ambiguous format
#[Cast(DateTimeCast::class)]
public readonly Carbon $date;
```

### Use Nullable for Optional Fields

```php
// ✅ Good
#[Cast(DateTimeCast::class, nullable: true)]
public readonly ?Carbon $deletedAt;

// ❌ Bad
#[Cast(DateTimeCast::class)]
public readonly ?Carbon $deletedAt;
```


## Code Examples

The following working examples demonstrate this feature:

- [**Basic Casts**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/basic-casts.php) - Common type casts
- [**All Casts**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/all-casts.php) - Complete cast overview
- [**Enum Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/enum-cast.php) - Enum casting
- [**Collection Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/collection-cast.php) - Collection casting
- [**Timestamp Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/timestamp-cast.php) - Date/time casting
- [**Hashed Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/hashed-cast.php) - Password hashing
- [**Encrypted Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/encrypted-cast.php) - Data encryption
- [**Lazy Cast**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/type-casting/lazy-cast.php) - Lazy loading casts

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [CastTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/Casts/CastTest.php) - Cast functionality tests
- [EnumCastTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/Casts/EnumCastTest.php) - Enum cast tests
- [CollectionCastTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/Casts/CollectionCastTest.php) - Collection cast tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Cast
```

## See Also

- [Validation](/data-helpers/simple-dto/validation/) - Validate your data
- [Property Mapping](/data-helpers/simple-dto/property-mapping/) - Map property names
- [Nested Dtos](/data-helpers/simple-dto/nested-dtos/) - Complex structures
- [Creating Dtos](/data-helpers/simple-dto/creating-dtos/) - Creation methods
