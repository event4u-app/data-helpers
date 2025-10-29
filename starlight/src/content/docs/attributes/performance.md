---
title: Performance Attributes
description: Optimize DTO performance by skipping unnecessary operations
sidebar:
  order: 7
---

Performance attributes allow you to skip unnecessary operations for maximum DTO instantiation speed.

:::tip[Performance Modes Guide]
See the [Performance Modes Guide](/data-helpers/simple-dto/performance-modes/) for a comprehensive comparison of all performance optimization strategies and when to use each mode.
:::

:::note[Important: strict_types=1]
All SimpleDto files use `declare(strict_types=1)`, which means **PHP does NOT perform automatic type coercion**. The performance attributes control which casting mechanisms are active.
:::

## Quick Comparison

| Attribute | Nested DTOs | Native Type Casts | Explicit `#[Cast]` | Validation | Other Attributes | Performance Gain |
|-----------|-------------|-------------------|-------------------|------------|------------------|------------------|
| **None** | ✅ Auto | ❌ TypeError | ✅ Applied | ✅ Active | ✅ Active | Baseline (12.7μs) |
| **`#[AutoCast]`** | ✅ Auto | ✅ Auto | ✅ Applied | ✅ Active | ✅ Active | -50% slower |
| **`#[NoCasts]`** | ❌ TypeError | ❌ TypeError | ❌ Disabled | ✅ Active | ✅ Active | **+37% faster** |
| **`#[NoValidation]`** | ✅ Auto | ❌ TypeError | ✅ Applied | ❌ Disabled | ✅ Active | +5% faster |
| **`#[NoAttributes]`** | ✅ Auto | ❌ TypeError | ❌ Disabled | ❌ Disabled | ❌ Disabled | +5% faster |
| **`#[NoAttributes, NoCasts]`** | ❌ TypeError | ❌ TypeError | ❌ Disabled | ❌ Disabled | ❌ Disabled | **+34% faster** |
| **`#[UltraFast]`** | ✅ Auto | ❌ TypeError | ❌ Disabled | ❌ Disabled | ❌ Disabled | **+639% faster (1.7μs)** 🚀 |

:::caution[Key Insight: Nested DTOs]
**Nested DTOs are ALWAYS auto-casted** (even without `#[AutoCast]`), unless you use `#[NoCasts]`. This is different from native types which require `#[AutoCast]` or explicit `#[Cast]` attributes.
:::

## Available Attributes

### #[UltraFast] ⚡

**NEW!** Bypass ALL SimpleDto overhead for maximum performance.

**Use when:**
- You need maximum speed (7.4x faster than normal SimpleDto)
- You only need basic mapping (#[MapFrom], #[MapTo])
- Input data is already validated and correctly typed
- You're processing large datasets

**Performance Impact:**
- **639% faster** than normal SimpleDto! 🚀
- **1.723μs** vs 12.740μs (normal mode)
- Only **5.5x slower** than other minimalist Dto libraries
- **2.4x less memory** (2.7mb vs 6.7mb)

**What gets disabled:**
- ❌ All validation
- ❌ All type casting
- ❌ All lazy loading
- ❌ All optional properties
- ❌ All computed properties
- ❌ All pipeline steps
- ❌ Cache overhead

**What stays active:**
- ✅ `#[MapFrom]` attribute (configurable)
- ✅ `#[MapTo]` attribute (configurable)
- ✅ Nested DTO auto-casting
- ✅ Basic fromArray/toArray functionality

**Example:**

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
```

**Configuration Options:**

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

:::caution[Do Not Combine]
Do NOT combine `#[UltraFast]` with other performance attributes. `#[UltraFast]` bypasses all processing, making other attributes redundant.
:::

---

### #[NoCasts]

Skip **ALL** type casting operations for maximum performance.

**Use when:**
- Your data is already in the correct types
- You control the data source (e.g., from database)
- You need maximum performance
- You want strict type checking

**Performance Impact:**
- **34-63% faster** DTO instantiation! 🚀
- No type coercion overhead
- Strict type checking only

**What gets disabled:**
- ❌ **Nested DTO auto-casting** (arrays won't be converted to DTOs)
- ❌ Native type casts (even with `#[AutoCast]`)
- ❌ Explicit `#[Cast]` attributes
- ❌ All casting operations

**What stays active:**
- ✅ Validation attributes
- ✅ Visibility attributes
- ✅ Mapping attributes
- ✅ All other attributes

:::tip[Nested DTOs Require Objects]
With `#[NoCasts]`, nested DTOs must be passed as **already instantiated objects**, not arrays. This is the main difference from normal behavior where arrays are automatically converted to nested DTOs.
:::

**Example with Native Types:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoCasts]
class StrictDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly float $salary,
    ) {}
}

// ✅ Works - correct types
$dto = StrictDto::fromArray([
    'name' => 'John Doe',
    'age' => 30,
    'salary' => 50000.00,
]);

// ❌ TypeError - wrong types, no casting at all
$dto = StrictDto::fromArray([
    'name' => 'John Doe',
    'age' => '30',        // ← TypeError: string instead of int
    'salary' => 50000.00,
]);
```

**Example with Nested DTOs:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

#[NoCasts]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

// ❌ TypeError - array is not auto-casted with #[NoCasts]
$user = UserDto::fromArray([
    'name' => 'John',
    'address' => ['street' => 'Main St', 'city' => 'NYC'],  // ← TypeError!
]);

// ✅ Works - pass AddressDto object
$user = UserDto::fromArray([
    'name' => 'John',
    'address' => AddressDto::fromArray(['street' => 'Main St', 'city' => 'NYC']),
]);
```

### #[NoAttributes]

Skip all attribute reflection and processing.

**Use when:**
- You don't need any attributes on your DTO
- You want to reduce memory usage
- You have a simple DTO with just properties

**Performance Impact:**
- Skips reflection of all property attributes
- Reduces memory usage
- ~0-5% faster (only noticeable with many attributes)

**What gets disabled:**
- ❌ Validation attributes (`#[Required]`, `#[Email]`, etc.)
- ❌ Visibility attributes (`#[Visible]`, `#[Hidden]`, etc.)
- ❌ Cast attributes (`#[Cast]`, `#[AutoCast]`, etc.)
- ❌ Conditional attributes (`#[WhenValue]`, `#[WhenContext]`, etc.)
- ❌ Mapping attributes (`#[MapFrom]`, `#[MapTo]`)
- ❌ All other property attributes

**What stays active:**
- ✅ Nested DTO auto-casting (always active unless `#[NoCasts]` is used)

**Example:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

#[NoAttributes]
class FastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly AddressDto $address,
    ) {}
}

// ✅ Nested DTOs still work!
$dto = FastDto::fromArray([
    'name' => 'John',
    'age' => 30,
    'address' => ['street' => 'Main St', 'city' => 'NYC'],  // ← Auto-casted to AddressDto
]);

// ❌ TypeError - native types need correct types (no AutoCast)
$dto = FastDto::fromArray([
    'name' => 'John',
    'age' => '30',  // ← TypeError: string instead of int
]);
```



### #[NoValidation]

Skip all validation operations.

**Use when:**
- You trust your data source
- Data is already validated elsewhere
- You need maximum performance
- You're in a high-throughput scenario

**Performance Impact:**
- Skips all validation logic
- No validation rule extraction
- Faster DTO instantiation

**Example:**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

#[NoValidation]
class TrustedDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]  // These are ignored!
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// ✅ Works - no validation performed
$dto = TrustedDto::fromArray([
    'email' => 'invalid-email',  // No validation!
    'age' => 25,
]);
```

**What gets disabled:**
- ❌ Validation attributes (`#[Required]`, `#[Email]`, etc.)
- ❌ Auto-inferred validation rules
- ❌ Custom validation rules

**What still works:**
- ✅ Type casts (unless `#[NoCasts]` is also used)
- ✅ Visibility attributes
- ✅ Conditional attributes
- ✅ Mapping attributes
- ✅ All other non-validation attributes

## How Attributes Interact

Understanding how performance attributes interact is crucial for choosing the right optimization:

### Casting Behavior Matrix

| Scenario | Nested DTOs | Native Types | Explicit `#[Cast]` |
|----------|-------------|--------------|-------------------|
| **No attributes** | ✅ Auto-casted | ❌ TypeError | ✅ Applied |
| **`#[AutoCast]`** | ✅ Auto-casted | ✅ Auto-casted | ✅ Applied |
| **`#[NoCasts]`** | ❌ TypeError | ❌ TypeError | ❌ Disabled |
| **`#[NoAttributes]`** | ✅ Auto-casted | ❌ TypeError | ❌ Disabled |
| **`#[NoAttributes, NoCasts]`** | ❌ TypeError | ❌ TypeError | ❌ Disabled |

### Key Insights

1. **Nested DTOs are special**: They're auto-casted by default (unlike native types)
2. **`#[NoCasts]` is the only way** to disable nested DTO auto-casting
3. **`#[NoAttributes]` disables `#[Cast]` attributes** but NOT nested DTO auto-casting
4. **`#[AutoCast]` is opt-in** for native types only
5. **Combining both** gives maximum performance but requires exact types

### Decision Tree

```
Do you have nested DTOs?
├─ YES → Use #[NoCasts] only if you can pass DTO objects
│        Otherwise, don't use #[NoCasts]
└─ NO  → Use #[NoCasts] for maximum performance

Do you need validation?
├─ YES → Don't use #[NoValidation] or #[NoAttributes]
└─ NO  → Use #[NoValidation] for +5% performance

Do you use any attributes (#[Cast], #[MapFrom], etc.)?
├─ YES → Don't use #[NoAttributes]
└─ NO  → Use #[NoAttributes] for cleaner code
```

## Combining Attributes

You can combine multiple performance attributes for maximum optimization:

### Common Combinations

| Combination | Use Case | Performance Gain |
|-------------|----------|------------------|
| `#[NoCasts]` | Trusted data with correct types | **+37% faster** |
| `#[NoValidation]` | Pre-validated data | +5% faster |
| `#[NoCasts, NoValidation]` | Trusted, pre-validated data | **+34% faster** |
| `#[NoAttributes]` | Simple DTOs without attributes | +5% faster |
| `#[NoAttributes, NoCasts]` | Maximum performance | **+34% faster** |

### Option 1: Skip Casts and Validation (Recommended)

Best for trusted data sources (e.g., internal APIs, database queries):

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;

#[NoCasts, NoValidation]
class FastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

**Best for:** Trusted data sources where you control the types and validation is done elsewhere.

### Option 2: Skip All Attributes (Keep Nested DTO Casts)

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

#[NoAttributes]
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly AddressDto $address,
    ) {}
}

// ✅ Nested DTOs still work!
$dto = UserDto::fromArray([
    'name' => 'John',
    'age' => 30,
    'address' => ['street' => 'Main St', 'city' => 'NYC'],  // ← Auto-casted!
]);

// ❌ TypeError - native types need correct types (no AutoCast)
$dto = UserDto::fromArray([
    'name' => 'John',
    'age' => '30',  // ← TypeError: string instead of int
]);
```

**Best for:** Simple DTOs with nested DTOs where you don't need validation or other attributes.

### Option 3: Maximum Performance (No Casts, No Attributes)

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoAttributes, NoCasts]
class UltraFastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// ✅ Works - correct types
$dto = UltraFastDto::fromArray([
    'name' => 'John',
    'age' => 30,
]);

// ❌ TypeError - no casting at all
$dto = UltraFastDto::fromArray([
    'name' => 'John',
    'age' => '30',  // ← TypeError
]);
```

**Performance Impact:**
- **~32-35% faster** than normal DTOs
- Minimal overhead
- Perfect for high-performance APIs

**Best for:** Absolute maximum performance when data is already in correct types.

:::caution[No Nested DTOs with Both Attributes]
With `#[NoAttributes, NoCasts]`, even nested DTOs won't be auto-casted. All data must be in the exact correct types and formats.
:::

### Selective Optimization

Use only what you need:

```php
// Only skip casts, keep validation
#[NoCasts]
class ValidatedDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]  // Validation still works!
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Only skip validation, keep casts
#[NoValidation]
class CastedDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly int $age,
    ) {}

    protected function casts(): array
    {
        return ['age' => 'integer'];  // Casts still work!
    }
}
```

## Performance Benchmarks

### Basic DTO (10,000 iterations)

```
Normal DTO:       1.6 μs (baseline)
#[NoCasts]:       1.05 μs (34% faster)
#[NoAttributes]:  1.61 μs (no difference without attributes)
Both:             1.08 μs (32% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast DTO:     3.09 μs (with type casting)
#[NoCasts]:       1.13 μs (63% faster!)
```

### Real-World API (1,000 DTOs)

```
Normal DTO:       1.6ms
#[NoCasts]:       1.05ms (34% faster)
Both:             1.08ms (32% faster)

Savings per 1M requests: ~550ms
```

## Use Cases

### High-Performance API

```php
#[NoCasts]
class ApiResponseDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

// Data from database is already typed correctly
$dto = ApiResponseDto::fromArray($dbRow);
```

### Internal Data Transfer

```php
#[NoAttributes, NoCasts]
class InternalDto extends SimpleDto
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
    ) {}
}

// Fast internal data transfer between services
$dto = InternalDto::fromArray(['key' => 'cache_key', 'value' => $data]);
```

### Batch Processing

```php
#[NoCasts]
class BatchItemDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
    ) {}
}

// Process 10,000 items 34% faster
$dtos = array_map(
    fn($item) => BatchItemDto::fromArray($item),
    $items
);
```

## When NOT to Use

### Don't use #[NoCasts] when:

❌ **Data from external APIs** - Types may vary
```php
// Bad - API might return strings
#[NoCasts]
class ApiDto extends SimpleDto { ... }
```

❌ **User input** - Always needs validation and casting
```php
// Bad - user input needs casting
#[NoCasts]
class FormDto extends SimpleDto { ... }
```

❌ **Mixed data sources** - Types not guaranteed
```php
// Bad - data from multiple sources
#[NoCasts]
class MixedDto extends SimpleDto { ... }
```

### Don't use #[NoAttributes] when:

❌ **Need validation** - Validation attributes won't work
```php
// Bad - validation won't work
#[NoAttributes]
class UserDto extends SimpleDto {
    public function __construct(
        #[Required, Email]  // Won't work!
        public readonly string $email,
    ) {}
}
```

❌ **Need visibility control** - Conditional attributes won't work
```php
// Bad - visibility control won't work
#[NoAttributes]
class SecureDto extends SimpleDto {
    public function __construct(
        #[WhenAuth]  // Won't work!
        public readonly string $secret,
    ) {}
}
```

## Best Practices

1. **Use `#[NoCasts]` for database DTOs** - Data is already typed
2. **Use both for internal DTOs** - Maximum performance
3. **Don't use for user input** - Always validate and cast
4. **Benchmark your use case** - Measure the impact
5. **Document the decision** - Explain why you're using them

## See Also

- [Performance Optimization](/data-helpers/performance/optimization/#performance-attributes) - Complete optimization guide
- [Attributes Overview](/data-helpers/attributes/overview/#performance-attributes) - All available attributes
- [Performance Benchmarks](/data-helpers/performance/benchmarks/) - Detailed benchmarks

