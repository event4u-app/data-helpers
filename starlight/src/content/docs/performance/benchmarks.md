---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.6x faster** than Other Serializer for complex mappings
- Other mapper libraries are up to **4.8x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~9.9μs per operation
- Plain PHP:  ~0.37μs per operation
- Trade-off:  ~27x slower, but with type safety, immutability and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~17.1μs per operation
- Plain PHP:  ~0.37μs per operation
- Trade-off:  ~46x slower, but with type safety, validation and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~17μs per operation (depending on casting needs)
- Plain PHP:  ~0.4μs per operation
- Trade-off:  ~47x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~13-15μs per operation
- Plain PHP:  ~0.1-0.3μs per operation
- Trade-off:  ~69x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~17-21μs per operation
- OtherSerializer:    ~62-76μs per operation
- Benefit:    3.6x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~17μs   (46x slower than Plain PHP)
- SimpleDto (with AutoCast): ~17μs   (47x slower than Plain PHP)
- AutoCast overhead:         ~0%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~17μs   (47x slower than Plain PHP)
- Casting overhead:          ~1% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~0% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~1% overhead** on top of the AutoCast overhead
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
| Simple Get | 0.061μs | Get value from flat array |
| Nested Get | 0.490μs | Get value from nested path |
| Wildcard Get | 0.917μs | Get values using single wildcard |
| Deep Wildcard Get | 38.993μs | Get values using multiple wildcards |
| Typed Get String | 0.086μs | Get typed string value |
| Typed Get Int | 0.083μs | Get typed int value |
| Create Accessor | 0.060μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 1.162μs | Set value in flat array |
| Nested Set | 1.444μs | Set value in nested path |
| Deep Set | 1.579μs | Set value creating new nested structure |
| Multiple Set | 2.048μs | Set multiple values at once |
| Merge | 1.467μs | Deep merge arrays |
| Unset | 1.395μs | Remove single value |
| Multiple Unset | 1.850μs | Remove multiple values |
| Wildcard Set | 1.976μs |  |
| Deep Wildcard Set | 5.321μs |  |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.234μs | Map flat structure |
| Nested Mapping | 12.735μs | Map nested structure |
| Auto Map | 10.091μs | Automatic field mapping |
| Map From Template | 13.389μs | Map using template expressions |

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
| SimpleDto Normal | 6.299μs | 38.534μs | 6.321μs |
| SimpleDto #[UltraFast] | 5.708μs<br>(**1.1x faster**) | 37.787μs | 5.742μs<br>(**1.1x faster**) |
| LiteDto | 3.247μs<br>(**1.9x faster**) | 6.447μs<br>(**6.0x faster**) | 3.226μs<br>(**2.0x faster**) |
| LiteDto #[UltraFast] | 2.642μs<br>(**2.4x faster**) | 4.728μs<br>(**8.1x faster**) | 2.598μs<br>(**2.4x faster**) |
| Plain PHP | 0.111μs<br>(**56.5x faster**) | - | - |
| Other Dtos | 3.305μs<br>(**1.9x faster**) | 4.002μs<br>(**9.6x faster**) | 3.328μs<br>(**1.9x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **1.7x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~96x slower** than Plain PHP (vs ~166x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~3x slower)
- SimpleDto provides **type safety, validation and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 12.279μs | 14.700μs | 15.123μs |
| SimpleDto #[UltraFast] | 5.708μs<br>(**2.2x faster**) | 10.751μs<br>(**1.4x faster**) | - |
| Plain PHP | 0.067μs<br>(**184.1x faster**) | 0.136μs<br>(**107.7x faster**) | - |
| Other Mappers | 2.566μs<br>(**4.8x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **2.2x faster** than DataMapper for simple mapping
- Other mapper libraries are up to **4.8x faster** than DataMapper, and **2.2x faster** than #[UltraFast]
- Plain PHP is **~184x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability and maintainability for complex mappings

<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 22.625μs | 15.933μs |
| SimpleDto #[UltraFast] | 5.708μs<br>(**4.0x faster**) | 5.708μs<br>(**2.8x faster**) |
| Plain PHP | 0.322μs<br>(**70.3x faster**) | 0.322μs<br>(**49.5x faster**) |
| Other Serializer | 69.100μs<br>(**3.1x slower**) | 69.100μs<br>(**4.3x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **12.1x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **3.4x faster** than DataMapper for simple mappings
- DataMapper is **3.6x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.62 μs
- MTIME (auto-validation):    2.68 μs
- HASH (auto-validation):     2.84 μs
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
Normal Dto:                1.72 μs (baseline)
#[UltraFast]:              1.56 μs (9.1% faster)
#[NoCasts]:                1.05 μs (39.0% faster)
#[NoValidation]:           1.83 μs (6.5% slower)
#[NoAttributes]:           1.75 μs (1.6% slower)
#[NoCasts, NoValidation]:  1.10 μs (36.2% faster)
#[NoAttributes, NoCasts]:  1.72 μs (0.2% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              1.79 μs (with type casting)
#[NoCasts]:                1.02 μs (43.0% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.72 ms
#[UltraFast]:              1.56 ms (9.1% faster)
#[NoCasts]:                1.05 ms (39.0% faster)
#[NoAttributes, NoCasts]:  1.72 ms (0.2% faster)

Savings per 1M requests:   ~157ms (0.2s) with #[UltraFast]
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
