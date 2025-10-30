---
title: Performance Modes
description: Choose the right performance mode for your use case
---

SimpleDto provides multiple performance modes to balance features and speed based on your needs.

## Performance Modes Overview

| Mode | Speed | Features | Use Case |
|------|-------|----------|----------|
| **Normal** | 12.7μs | Full features | Default mode with all features |
| **#[UltraFast]** | 1.7μs | Minimal | Maximum speed with basic mapping |
| **#[NoCasts]** | ~8μs | No casting | Skip type casting |
| **#[NoValidation]** | ~10μs | No validation | Skip validation |
| **#[NoAttributes]** | ~9μs | No attributes | Skip attribute processing |

## #[UltraFast] Mode

The `#[UltraFast]` attribute provides maximum performance by bypassing all SimpleDto overhead.

### Features

- ✅ **7.4x faster** than normal SimpleDto
- ✅ **Direct reflection** (no cache overhead)
- ✅ **Minimal memory** (2.7mb vs 6.7mb)
- ✅ Supports `#[MapFrom]` and `#[MapTo]` attributes
- ✅ Handles nested DTOs recursively
- ❌ No validation
- ❌ No type casting
- ❌ No lazy loading
- ❌ No optional properties
- ❌ No computed properties

### Usage

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;

#[UltraFast]
class FastUserDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Create from array (1.7μs)
$user = FastUserDto::fromArray([
    'user_name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Convert to array
$array = $user->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
```

### Nested DTOs

```php
#[UltraFast]
class FastAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

#[UltraFast]
class FastUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly FastAddressDto $address,
    ) {}
}

$user = FastUserDto::fromArray([
    'name' => 'John',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
    ],
]);
```

### Configuration Options

```php
#[UltraFast(
    allowMapFrom: true,   // Allow #[MapFrom] attributes (default: true)
    allowMapTo: true,     // Allow #[MapTo] attributes (default: true)
    allowCastWith: false, // Allow #[CastWith] attributes (default: false)
)]
class ConfiguredDto extends SimpleDto
{
    // ...
}
```

### JSON/XML Support with #[ConverterMode]

Combine `#[UltraFast]` with `#[ConverterMode]` to enable JSON/XML/CSV parsing with minimal overhead:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;

#[UltraFast]
#[ConverterMode]
class ApiDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Accept JSON (1.3-1.5μs)
$dto = ApiDto::from('{"name": "John", "email": "john@example.com"}');

// Accept XML (1.3-1.5μs)
$dto = ApiDto::from('<root><name>John</name><email>john@example.com</email></root>');

// Accept arrays (0.8μs - no parsing overhead)
$dto = ApiDto::from(['name' => 'John', 'email' => 'john@example.com']);
```

**Performance:**
- **Array only** (without `#[ConverterMode]`): ~0.8μs
- **With JSON/XML** (with `#[ConverterMode]`): ~1.3-1.5μs
- **Still 12x faster** than normal SimpleDto (~18.4μs)

**Note:** Use `from()` method for mixed input types. `fromArray()` only accepts arrays.

## #[NoCasts] Mode

Skip all type casting for better performance when input data is already correctly typed.

### Features

- ✅ **34-63% faster** than normal SimpleDto
- ✅ All other features work (validation, mapping, etc.)
- ❌ No automatic type conversion
- ❌ No nested DTO auto-casting

### Usage

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoCasts]
class StrictDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// This works (correct types)
$dto = StrictDto::fromArray(['name' => 'John', 'age' => 30]);

// This throws TypeError (wrong types, no casting)
$dto = StrictDto::fromArray(['name' => 'John', 'age' => '30']);
```

## #[NoValidation] Mode

Skip validation for better performance when input data is already validated.

### Features

- ✅ **20-30% faster** than normal SimpleDto
- ✅ All other features work (casting, mapping, etc.)
- ❌ No validation attributes processed

### Usage

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;

#[NoValidation]
class TrustedDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

## #[NoAttributes] Mode

Skip all attribute processing for maximum performance.

### Features

- ✅ **25-40% faster** than normal SimpleDto
- ✅ Basic DTO functionality works
- ❌ No validation attributes
- ❌ No visibility attributes
- ❌ No mapping attributes
- ❌ No cast attributes

### Usage

```php
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

#[NoAttributes]
class MinimalDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

## Combining Attributes

You can combine multiple performance attributes:

```php
#[NoCasts, NoValidation]
class FastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

:::caution[Attribute Conflicts]
Do NOT combine `#[UltraFast]` with other performance attributes. `#[UltraFast]` bypasses all processing, making other attributes redundant.
:::

## Performance Comparison

```
Benchmark Results (10,000 iterations):

Normal SimpleDto:           12.740μs (baseline)
#[NoValidation]:            ~10.000μs (20% faster)
#[NoAttributes]:            ~9.000μs  (29% faster)
#[NoCasts]:                 ~8.000μs  (37% faster)
#[NoCasts, NoValidation]:   ~6.000μs  (53% faster)
#[UltraFast]:               1.723μs   (639% faster)

Plain PHP Constructor:      0.141μs   (9,000% faster)
Other Dto Libraries:        0.315μs   (4,000% faster)
```

## Choosing the Right Mode

### Use Normal Mode When:
- ✅ You need full features (validation, casts, mapping)
- ✅ Performance is acceptable (not in tight loops)
- ✅ Developer experience is priority
- ✅ Type safety and validation are important

### Use #[UltraFast] When:
- ✅ You need maximum speed
- ✅ You only need basic mapping (#[MapFrom], #[MapTo])
- ✅ Input data is already validated
- ✅ Input data is already correctly typed
- ✅ You're processing large datasets

### Use #[NoCasts] When:
- ✅ Input data is already correctly typed
- ✅ You need validation but not casting
- ✅ You want better performance without losing features

### Use #[NoValidation] When:
- ✅ Input data is already validated (e.g., from database)
- ✅ You need casting but not validation
- ✅ You trust the data source

### Use #[NoAttributes] When:
- ✅ You don't use any attributes
- ✅ You want simple DTOs with better performance
- ✅ You only need basic fromArray/toArray functionality

## Real-World Example

```php
// API endpoint - needs validation and casting
class CreateUserRequest extends SimpleDto
{
    #[Required, Email]
    public readonly string $email;

    #[Required, Min(3)]
    public readonly string $name;
}

// Internal DTO - already validated, use #[UltraFast]
#[UltraFast]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly int $id,
    ) {}
}

// Database result - already typed, use #[NoCasts]
#[NoCasts]
class UserFromDatabase extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
```

## See Also

- [Performance Benchmarks](/data-helpers/performance/benchmarks/) - Detailed benchmark results
- [Caching Guide](/data-helpers/simple-dto/caching/) - Cache optimization strategies
- [Performance Attributes](/data-helpers/simple-dto/attributes/performance/) - Detailed attribute documentation

