---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **2.9x faster** than Other Serializer for complex mappings
- Other mapper libraries are **4.5x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~14.6μs per operation
- Plain PHP:  ~0.40μs per operation
- Trade-off:  ~37x slower, but with type safety, immutability and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~9.4μs per operation
- Plain PHP:  ~0.40μs per operation
- Trade-off:  ~24x slower, but with type safety, validation and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~9μs per operation (depending on casting needs)
- Plain PHP:  ~0.4μs per operation
- Trade-off:  ~23-24x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~24-29μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~105x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~32-39μs per operation
- OtherSerializer:    ~93-114μs per operation
- Benefit:    2.9x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~9μs   (24x slower than Plain PHP)
- SimpleDto (with AutoCast): ~9μs   (24x slower than Plain PHP)
- AutoCast overhead:         ~1%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~9μs   (23x slower than Plain PHP)
- Casting overhead:          ~-1% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~1% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~-1% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~1.0x faster** and closer to Plain PHP performance

**When to use #[AutoCast]:**
- ✅ CSV imports (all values are strings)
- ✅ XML parsing (all values are strings)
- ✅ HTTP requests (query params and form data are strings)
- ✅ Legacy APIs with inconsistent types
- ❌ Internal Dtos with correct types
- ❌ Performance-critical code paths
- ❌ High-throughput data processing
<!-- BENCHMARK_AUTOCAST_PERFORMANCE_END -->

## When to Use Data Helpers

**Use Data Helpers when:**
- You need type safety and validation
- You work with complex data structures
- You want maintainable, readable code
- Performance is acceptable (not in tight loops)
- You're replacing Symfony Serializer or other heavy libraries

**Consider Plain PHP or LiteDto when:**
- You're in performance-critical tight loops
- You process millions of operations per second
- You don't need validation or type safety
- You're willing to write and maintain manual mapping code

## DataAccessor Performance

<!-- BENCHMARK_DATA_ACCESSOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Get | 0.361μs | Get value from flat array |
| Nested Get | 0.483μs | Get value from nested path |
| Wildcard Get | 16.253μs | Get values using single wildcard |
| Deep Wildcard Get | 71.038μs | Get values using multiple wildcards |
| Typed Get String | 0.421μs | Get typed string value |
| Typed Get Int | 0.402μs | Get typed int value |
| Create Accessor | 0.066μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 1.308μs | Set value in flat array |
| Nested Set | 1.800μs | Set value in nested path |
| Deep Set | 2.008μs | Set value creating new nested structure |
| Multiple Set | 2.456μs | Set multiple values at once |
| Merge | 1.714μs | Deep merge arrays |
| Unset | 1.703μs | Remove single value |
| Multiple Unset | 2.310μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 19.317μs | Map flat structure |
| Nested Mapping | 19.496μs | Map nested structure |
| Auto Map | 15.829μs | Automatic field mapping |
| Map From Template | 22.737μs | Map using template expressions |

<!-- BENCHMARK_DATA_MAPPER_END -->

## Memory Usage

```
Dto Instance:    ~1.2 KB
With Validation: ~1.5 KB
With Caching:    ~0.8 KB
```

## Dto Performance Comparison

Comparison of our SimpleDto implementation with other Dto libraries and plain PHP:

<!-- BENCHMARK_DTO_COMPARISON_START -->

| Implementation | From Array | To Array | Complex Data |
|----------------|------------|----------|---------------|
| SimpleDto Normal | 8.836μs | 60.370μs | 9.524μs |
| SimpleDto #[UltraFast] | 8.340μs | 56.300μs | 8.801μs |
| LiteDto | 4.726μs<br>(**1.9x faster**) | 9.240μs<br>(**6.5x faster**) | 4.639μs<br>(**2.1x faster**) |
| LiteDto #[UltraFast] | 3.767μs<br>(**2.3x faster**) | 6.708μs<br>(**9.0x faster**) | 3.943μs<br>(**2.4x faster**) |
| Plain PHP | 0.127μs<br>(**69.8x faster**) | - | - |
| Other Dtos | 5.079μs<br>(**1.7x faster**) | 5.662μs<br>(**10.7x faster**) | 4.581μs<br>(**2.1x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **1.8x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~120x slower** than Plain PHP (vs ~216x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~3x slower)
- SimpleDto provides **type safety, validation and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 25.238μs | 30.545μs | 22.744μs |
| SimpleDto #[UltraFast] | 8.340μs<br>(**3.0x faster**) | 14.901μs<br>(**2.0x faster**) | - |
| Plain PHP | 0.070μs<br>(**360.5x faster**) | 0.179μs<br>(**170.3x faster**) | - |
| Other Mappers | 4.180μs<br>(**6.0x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **3.1x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.5x faster** than DataMapper, but **1.4x slower** than #[UltraFast]
- Plain PHP is **~210x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 39.836μs | 31.262μs |
| SimpleDto #[UltraFast] | 8.340μs<br>(**4.8x faster**) | 8.340μs<br>(**3.7x faster**) |
| Plain PHP | 0.409μs<br>(**97.4x faster**) | 0.409μs<br>(**76.4x faster**) |
| Other Serializer | 103.661μs<br>(**2.6x slower**) | 103.661μs<br>(**3.3x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **12.4x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **4.3x faster** than DataMapper for simple mappings
- DataMapper is **2.9x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     3.99 μs
- MTIME (auto-validation):    3.62 μs
- HASH (auto-validation):     3.94 μs
```
<!-- BENCHMARK_CACHE_INVALIDATION_END -->

:::tip[Performance Recommendation]
Use **MANUAL** in production with cache warming in your deployment pipeline for best performance.
Use **MTIME** in development for automatic cache invalidation without manual clearing.
:::

:::note[Learn More]
See the [SimpleDto Caching Guide](/data-helpers/simple-dto/caching/) for detailed information about cache invalidation strategies.
See the [Cache Generation Guide](/data-helpers/performance/cache-generation/) for manual cache generation instructions.
:::

## Performance Attributes

Skip unnecessary operations for maximum DTO instantiation speed:

<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_START -->

### Basic Dto (10,000 iterations)

```
Normal Dto:                3.42 μs (baseline)
#[UltraFast]:              2.30 μs (32.9% faster)
#[NoCasts]:                1.64 μs (52.1% faster)
#[NoValidation]:           3.16 μs (7.6% faster)
#[NoAttributes]:           2.91 μs (15.0% faster)
#[NoCasts, NoValidation]:  1.60 μs (53.2% faster)
#[NoAttributes, NoCasts]:  2.79 μs (18.5% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              2.84 μs (with type casting)
#[NoCasts]:                1.56 μs (45.2% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 3.42 ms
#[UltraFast]:              2.30 ms (32.9% faster)
#[NoCasts]:                1.64 ms (52.1% faster)
#[NoAttributes, NoCasts]:  2.79 ms (18.5% faster)

Savings per 1M requests:   ~1124ms (1.1s) with #[UltraFast]
```
<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_END -->

:::tip[Better Performance for SimpleDto]
Use `#[NoAttributes]`, `#[NoCasts]` and `#[NoValidation]` attributes to skip unnecessary operations and achieve **34-63% faster** DTO instantiation!

See [Performance Attributes](/data-helpers/attributes/performance/#performance-attributes) for details.
:::

:::caution[UltraFast SimpleDto & LiteDto]{icon="seti:favicon"}
Use `#[UltraFast]` attribute to achieve **~8x faster** performance than normal SimpleDto mode and **~4x faster** performance than normal LiteDto. UltraFast bypasses all overhead (validation, casts, pipeline) for maximum speed while keeping type safety and immutability. Perfect for high-throughput scenarios where you need SimpleDto's API but with near-Plain-PHP performance.

See [SimpleDto Performance Modes](/data-helpers/simple-dto/performance-modes/#performance-modes-overview) and [LiteDto Performance Modes](/data-helpers/lite-dto/introduction/#performance-modes-overview) for details.
:::

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
- [SimpleDto Caching](/data-helpers/simple-dto/caching/) - Cache invalidation strategies
- [Cache Generation Guide](/data-helpers/performance/cache-generation/) - Manual cache generation
