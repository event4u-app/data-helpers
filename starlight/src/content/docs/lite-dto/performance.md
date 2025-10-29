---
title: Performance Tips
description: Optimize your LiteDtos for maximum performance
---

LiteDto is designed for maximum performance, but there are still ways to optimize your usage.

## Performance Benchmarks

Here are the actual benchmark results from our comprehensive tests:

<!-- LITEDTO_BENCHMARKS_START -->

| Implementation | From Array | To Array | Complex Data |
|----------------|------------|----------|---------------|
| LiteDto | 2.779μs | 4.791μs | 2.940μs |
| LiteDto #[UltraFast] | 0.659μs | 0.825μs | 0.641μs |
| SimpleDto #[UltraFast] | 2.402μs | 4.737μs | 2.351μs |
| SimpleDto Normal | 16.359μs | 22.669μs | 16.192μs |

**Average**: LiteDto is **5.4x faster** than SimpleDto Normal.
<!-- LITEDTO_BENCHMARKS_END -->

## Optimization Tips

### 1. Avoid ConverterMode When Not Needed

**ConverterMode adds ~0.5μs overhead** due to format detection and parsing.

```php
// ❌ Slower: ConverterMode when only using arrays
#[ConverterMode]
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

$user = UserDto::from(['name' => 'John', 'age' => 30]);  // ~2.8μs

// ✅ Faster: No ConverterMode for array-only usage
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

$user = UserDto::from(['name' => 'John', 'age' => 30]);  // ~2.3μs
```

**Rule**: Only use `#[ConverterMode]` when you need to accept JSON, XML, or other formats.

### 2. Minimize Nested DTOs

Each nested DTO adds overhead for reflection and instantiation.

```php
// ❌ Slower: Deep nesting
class CountryDto extends LiteDto { /* ... */ }
class CityDto extends LiteDto {
    public function __construct(
        public readonly string $name,
        public readonly CountryDto $country,
    ) {}
}
class AddressDto extends LiteDto {
    public function __construct(
        public readonly string $street,
        public readonly CityDto $city,
    ) {}
}
class UserDto extends LiteDto {
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

// ✅ Faster: Flatten when possible
class UserDto extends LiteDto {
    public function __construct(
        public readonly string $name,
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}
```

**Rule**: Only use nested DTOs when the structure truly benefits from it.

### 3. Use Readonly Properties

LiteDto requires `readonly` properties for immutability and performance.

```php
// ✅ Correct: Readonly properties
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

// ❌ Wrong: Non-readonly properties (won't work)
class UserDto extends LiteDto
{
    public function __construct(
        public string $name,  // Missing readonly
        public int $age,
    ) {}
}
```

### 4. Minimize Attribute Usage

Each attribute adds a small overhead for reflection and processing.

```php
// ❌ Slower: Many attributes
class UserDto extends LiteDto
{
    public function __construct(
        #[From('user_name'), To('full_name'), ConvertEmptyToNull]
        public readonly ?string $name,

        #[From('user_age'), To('age_years')]
        public readonly int $age,
    ) {}
}

// ✅ Faster: Only necessary attributes
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}
```

**Rule**: Only use attributes when you need the functionality.

### 5. Batch Operations

When creating many DTOs, consider batching:

```php
// ❌ Slower: Creating DTOs one by one in a loop
$users = [];
foreach ($apiData as $userData) {
    $users[] = UserDto::from($userData);
}

// ✅ Faster: Use array_map for better performance
$users = array_map(
    fn($userData) => UserDto::from($userData),
    $apiData
);
```

### 6. Cache DTO Instances

If you're using the same DTO multiple times, cache it:

```php
// ❌ Slower: Creating same DTO multiple times
for ($i = 0; $i < 1000; $i++) {
    $config = ConfigDto::from($configData);
    // Use $config
}

// ✅ Faster: Create once, reuse
$config = ConfigDto::from($configData);
for ($i = 0; $i < 1000; $i++) {
    // Use $config
}
```

## Performance Comparison

### LiteDto vs SimpleDto

<!-- LITEDTO_VS_SIMPLEDTO_START -->

| Feature | LiteDto | LiteDto #[UltraFast] | SimpleDto Normal | SimpleDto #[UltraFast] |
|---------|---------|----------------------|------------------|------------------------|
| Performance | ~3.5μs | ~0.7μs | ~18.4μs | ~3.2μs |
| Validation | ❌ | ❌ | ✅ | ❌ |
| Type Casting | ❌ | ❌ | ✅ | ❌ |
| Property Mapping | ✅ | ✅ | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ | ✅ | ✅ |
| Collections | ✅ | ✅ | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ | ✅ | ✅ |
| Converter Support | ✅ (optional) | ✅ (optional) | ✅ | ❌ |

**When to use LiteDto**:
- You need maximum performance
- You don't need validation or type casting
- You want simple, clean code

**When to use SimpleDto**:
- You need validation (Required, Email, Min, Max, etc.)
- You need type casting (DateTime, Enum, etc.)
- You need computed properties or lazy loading
<!-- LITEDTO_VS_SIMPLEDTO_END -->

### LiteDto vs Other Dtos

<!-- LITEDTO_VS_OTHERDTO_START -->

| Metric | LiteDto | LiteDto #[UltraFast] | Other Dtos |
|--------|---------|----------------------|------------|
| Performance | ~3.5μs | ~0.7μs | N/A |
| Property Mapping | ✅ | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ | ✅ |
| Collections | ✅ | ✅ | ✅ |
| Converter Support | ✅ (optional) | ✅ (optional) | ❌ |
| ConvertEmptyToNull | ✅ | ✅ | ❌ |

**Why choose LiteDto?**
- No build step required
- More features (Converter, ConvertEmptyToNull)
- Better developer experience
- Competitive performance with other Dto libraries
<!-- LITEDTO_VS_OTHERDTO_END -->

## Real-World Performance

### API Endpoint Example

```php
// 1000 requests/second
// LiteDto: ~2.3ms total overhead
// SimpleDto: ~18.5ms total overhead
// Savings: ~16.2ms per 1000 requests

Route::post('/users', function (Request $request) {
    $user = UserDto::from($request->all());  // ~2.3μs

    // Save to database
    User::create($user->toArray());

    return response()->json($user);
});
```

### Batch Processing Example

```php
// Processing 10,000 records
// LiteDto: ~23ms total
// SimpleDto: ~185ms total
// Savings: ~162ms per 10,000 records

$users = array_map(
    fn($data) => UserDto::from($data),
    $csvData  // 10,000 rows
);
```

## Profiling Your DTOs

Use PHPBench to profile your DTOs:

```php
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Iterations;

class MyDtoBench
{
    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMyDto(): void
    {
        MyDto::from([
            'name' => 'John',
            'age' => 30,
        ]);
    }
}
```

Run benchmarks:

```bash
vendor/bin/phpbench run benchmarks/MyDtoBench.php --report=default
```

## Next Steps

- [Attributes Reference](./attributes) - Learn about all attributes
- [Creating LiteDtos](./creating-litedtos) - Best practices
- [Benchmarks](/performance/benchmarks) - See all performance comparisons
