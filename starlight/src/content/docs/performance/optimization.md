---
title: Performance Optimization
description: Complete guide for optimizing Data Helpers performance
---

Complete guide for optimizing Data Helpers performance.

## Introduction

Data Helpers provides multiple optimization techniques:

- ✅ **Validation Caching** - 198x faster validation
- ✅ **Lazy Loading** - Defer expensive operations
- ✅ **Type Cast Caching** - Reuse cast instances
- ✅ **Path Compilation** - Pre-compile dot-notation paths
- ✅ **Performance Attributes** - Skip unnecessary operations (34-63% faster)

:::tip[Maximum Performance for SimpleDto]
Use `#[NoAttributes]`, `#[NoCasts]`, and `#[NoValidation]` attributes to skip unnecessary operations and achieve **34-63% faster** DTO instantiation!

See [Performance Attributes](/data-helpers/attributes/performance/#performance-attributes) for details.
:::

## Enable Validation Caching

### Laravel

```bash
php artisan dto:cache
```

### Symfony

```bash
bin/console dto:cache
```

### Plain PHP

<!-- skip-test: ValidationCache class does not exist, caching is automatic -->
```php
use event4u\DataHelpers\SimpleDto\Cache\ValidationCache;

ValidationCache::enable();
ValidationCache::warmup();
```

### Performance Impact

```
Without Cache:  5,000 validations/sec
With Cache:     990,000 validations/sec

Improvement: 198x faster
```

## Use Lazy Loading

```php
use event4u\DataHelpers\SimpleDto\Attributes\Lazy;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
}
```

## Optimize Type Casting

```php
// config/data-helpers.php
return [
    'casts' => [
        'cache_instances' => true,
    ],
];
```

## Avoid Deep Nesting

```php
// ✅ Good - 2-3 levels
$dto->address->city;

// ❌ Bad - too deep
$dto->company->department->team->manager->address->city;
```

## Use Specific Types

```php
// ✅ Good
public readonly int $age;

// ❌ Bad
public readonly mixed $age;
```

## Batch Operations

```php
// ✅ Good
$dtos = DataCollection::make($users, UserDto::class);

// ❌ Bad
foreach ($users as $user) {
    $dtos[] = UserDto::fromModel($user);
}
```

## Memory Optimization

```php
// Use chunking
User::chunk(1000, function($users) {
    $dtos = DataCollection::make($users, UserDto::class);
});
```

## Performance Attributes {#performance-attributes}

Skip unnecessary operations for maximum performance.

### #[NoAttributes] - Skip Attribute Processing

Skip all attribute reflection and processing when you don't need validation, visibility control, or other attribute features. Type hint casts remain active:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

#[NoAttributes]
class FastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// ✅ Type hint casts still work
$dto = FastDto::fromArray(['name' => 123, 'age' => '30']);
// Result: name = '123' (string), age = 30 (int)
```

**Performance Impact:**
- Skips reflection of all property attributes
- Reduces memory usage
- ~0-5% faster (only noticeable with many attributes)

**Disables:**
- ❌ Validation attributes (`#[Required]`, `#[Email]`, etc.)
- ❌ Visibility attributes (`#[Visible]`, `#[Hidden]`, etc.)
- ❌ Cast attributes (`#[Cast]`, `#[AutoCast]`, etc.)
- ❌ Conditional attributes (`#[WhenValue]`, `#[WhenContext]`, etc.)

**Still Active:**
- ✅ Type hint casts (string → int, int → string, etc.)

### #[NoCasts] - Skip Type Casting

Skip all type casting operations (including type hint casts) when your data is already in the correct types:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoCasts]
class StrictDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// ✅ Works - correct types
$dto = StrictDto::fromArray(['name' => 'John', 'age' => 30]);

// ❌ TypeError - wrong types, no casting at all
$dto = StrictDto::fromArray(['name' => 'John', 'age' => '30']);
```

**Performance Impact:**
- **34-63% faster** DTO instantiation! 🚀
- No type coercion overhead
- Strict type checking only

**Disables:**
- ❌ AutoCast attribute
- ❌ Explicit Cast attributes
- ❌ Type hint casts (string → int, etc.)

**Still Active:**
- ✅ Validation attributes
- ✅ Visibility attributes
- ✅ Mapping attributes

### #[NoValidation] - Skip Validation

Skip all validation operations when you trust your data source:

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoValidation;

#[NoValidation]
class TrustedDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]  // These are ignored
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// ✅ Works - no validation performed
$dto = TrustedDto::fromArray(['email' => 'invalid', 'age' => 25]);
```

**Performance Impact:**
- Skips all validation logic
- No validation rule extraction
- Faster DTO instantiation

**Disables:**
- ❌ Validation attributes (`#[Required]`, `#[Email]`, etc.)
- ❌ Auto-inferred validation rules
- ❌ Custom validation rules

**Still Active:**
- ✅ Type casts (unless `#[NoCasts]` is also used)
- ✅ Visibility attributes
- ✅ Mapping attributes

### Combine Attributes for Maximum Performance

**Option 1: Skip Casts and Validation (Recommended)**

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

**Option 2: Skip All Attributes (Maximum Performance)**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;

#[NoAttributes]
class UltraFastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
// Type hint casts still work!
```

**Option 3: Skip Everything (Absolute Maximum)**

```php
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\NoAttributes;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

#[NoAttributes, NoCasts]
class AbsoluteFastDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
// No attributes, no casts at all
```

**Performance Impact:**
- **~32-35% faster** than normal DTOs
- Minimal overhead
- Perfect for high-performance APIs

### Benchmarks

<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_START -->

### Basic Dto (10,000 iterations)

```
Normal Dto:                1.76 μs (baseline)
#[UltraFast]:              1.17 μs (33.3% faster)
#[NoCasts]:                1.17 μs (33.6% faster)
#[NoValidation]:           1.74 μs (1.0% faster)
#[NoAttributes]:           1.72 μs (2.1% faster)
#[NoCasts, NoValidation]:  1.13 μs (35.6% faster)
#[NoAttributes, NoCasts]:  1.13 μs (35.6% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              3.43 μs (with type casting)
#[NoCasts]:                1.13 μs (67.2% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.76 ms
#[UltraFast]:              1.17 ms (33.3% faster)
#[NoCasts]:                1.17 ms (33.6% faster)
#[NoAttributes, NoCasts]:  1.13 ms (35.6% faster)

Savings per 1M requests:   ~584ms (0.6s) with #[UltraFast]
```
<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_END -->

### When to Use

**Use `#[NoCasts]` when:**
- ✅ Your data is already in the correct types (e.g., from database)
- ✅ You control the data source
- ✅ You need maximum performance
- ✅ You want strict type checking

**Use `#[NoAttributes]` when:**
- ✅ You don't need any attributes
- ✅ You have a simple DTO with just properties
- ✅ You want to reduce memory usage

**Don't use when:**
- ❌ You need type casting (e.g., from API responses)
- ❌ You need validation
- ❌ You need visibility control
- ❌ You need conditional properties

:::tip[See Also]
For complete attribute reference, see [Performance Attributes](/data-helpers/attributes/overview/#performance-attributes) in the Attributes Overview.
:::

## Best Practices

- [ ] Enable validation caching in production
- [ ] Use lazy loading for expensive operations
- [ ] Enable cast caching
- [ ] Avoid deep nesting (max 3 levels)
- [ ] Use specific types instead of mixed
- [ ] Use batch operations
- [ ] Use chunking for large datasets
- [ ] Use `#[NoCasts]` for high-performance DTOs with known types
- [ ] Use `#[NoAttributes]` for simple DTOs without attributes

## See Also

- [Performance Benchmarks](/data-helpers/performance/benchmarks/) - Detailed benchmarks
- [Lazy Properties](/data-helpers/simple-dto/lazy-properties/) - Lazy loading guide
- [Performance Attributes](/data-helpers/attributes/overview/#performance-attributes) - Complete attribute reference
