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
| LiteDto | 2.399μs | 3.809μs | 2.403μs |
| SimpleDto #[UltraFast] | 1.949μs | 3.650μs | 1.901μs |
| SimpleDto Normal | 11.712μs | 15.336μs | 11.683μs |

**Average**: LiteDto is **4.6x faster** than SimpleDto Normal.
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

| Feature | LiteDto | SimpleDto Normal | SimpleDto #[UltraFast] |
|---------|---------|------------------|------------------------|
| Performance | ~2.3μs | ~18.5μs | ~2.8μs |
| Validation | ❌ | ✅ | ❌ |
| Type Casting | ❌ | ✅ | ❌ |
| Property Mapping | ✅ | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ | ✅ |
| Collections | ✅ | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ | ✅ |
| Converter Support | ✅ (optional) | ✅ | ❌ |

**When to use LiteDto**:
- You need maximum performance
- You don't need validation or type casting
- You want simple, clean code

**When to use SimpleDto**:
- You need validation (Required, Email, Min, Max, etc.)
- You need type casting (DateTime, Enum, etc.)
- You need computed properties or lazy loading

### LiteDto vs Other Dtos (Carapace)

| Metric | LiteDto | Other Dtos (Carapace) |
|--------|---------|----------------------|
| Performance | ~2.3μs | ~0.37μs |
| Property Mapping | ✅ | ✅ |
| Hidden Properties | ✅ | ✅ |
| Nested DTOs | ✅ | ✅ |
| Collections | ✅ | ✅ |
| Converter Support | ✅ (optional) | ❌ |
| ConvertEmptyToNull | ✅ | ❌ |

**Why is Carapace faster?**
- Carapace uses code generation at build time
- No reflection at runtime
- Minimal feature set

**Why choose LiteDto?**
- No build step required
- More features (Converter, ConvertEmptyToNull)
- Better developer experience
- Still very fast (7x slower than Carapace, but 7.6x faster than SimpleDto)

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
