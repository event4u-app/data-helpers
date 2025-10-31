---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **2.8x faster** than Other Serializer for complex mappings
- Other mapper libraries are **5.5x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~4.6μs per operation
- Plain PHP:  ~0.20μs per operation
- Trade-off:  ~23x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~2.8μs per operation
- Plain PHP:  ~0.20μs per operation
- Trade-off:  ~14x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~9μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~45-46x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~18-22μs per operation
- Plain PHP:  ~0.1-0.3μs per operation
- Trade-off:  ~89x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~29-36μs per operation
- OtherSerializer:    ~81-99μs per operation
- Benefit:    2.8x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~3μs   (14x slower than Plain PHP)
- SimpleDto (with AutoCast): ~9μs   (45x slower than Plain PHP)
- AutoCast overhead:         ~227%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~9μs   (46x slower than Plain PHP)
- Casting overhead:          ~2% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~227% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~2% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~3.3x faster** and closer to Plain PHP performance

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
| Simple Get | 0.360μs | Get value from flat array |
| Nested Get | 0.431μs | Get value from nested path |
| Wildcard Get | 14.186μs | Get values using single wildcard |
| Deep Wildcard Get | 67.443μs | Get values using multiple wildcards |
| Typed Get String | 0.426μs | Get typed string value |
| Typed Get Int | 0.405μs | Get typed int value |
| Create Accessor | 0.066μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 1.316μs | Set value in flat array |
| Nested Set | 1.611μs | Set value in nested path |
| Deep Set | 1.684μs | Set value creating new nested structure |
| Multiple Set | 2.147μs | Set multiple values at once |
| Merge | 1.678μs | Deep merge arrays |
| Unset | 1.726μs | Remove single value |
| Multiple Unset | 2.073μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 16.878μs | Map flat structure |
| Nested Mapping | 19.618μs | Map nested structure |
| Auto Map | 15.984μs | Automatic field mapping |
| Map From Template | 19.051μs | Map using template expressions |

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
| SimpleDto Normal | 31.609μs | 37.990μs | 30.194μs |
| SimpleDto #[UltraFast] | 4.189μs<br>(**7.5x faster**) | 5.871μs<br>(**6.5x faster**) | 4.307μs<br>(**7.0x faster**) |
| LiteDto | 9.651μs<br>(**3.3x faster**) | 11.431μs<br>(**3.3x faster**) | 9.853μs<br>(**3.1x faster**) |
| LiteDto #[UltraFast] | 4.647μs<br>(**6.8x faster**) | 4.664μs<br>(**8.1x faster**) | 4.201μs<br>(**7.2x faster**) |
| Plain PHP | 0.146μs<br>(**217.1x faster**) | - | - |
| Other Dtos | 4.008μs<br>(**7.9x faster**) | 5.091μs<br>(**7.5x faster**) | 3.957μs<br>(**7.6x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **7.2x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~37x slower** than Plain PHP (vs ~266x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~1x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 15.750μs | 22.903μs | 20.119μs |
| SimpleDto #[UltraFast] | 4.189μs<br>(**3.8x faster**) | 7.550μs<br>(**3.0x faster**) | - |
| Plain PHP | 0.069μs<br>(**226.9x faster**) | 0.151μs<br>(**152.1x faster**) | - |
| Other Mappers | 3.306μs<br>(**4.8x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **4.7x faster** than DataMapper for simple mapping
- Other mapper libraries are **5.5x faster** than DataMapper, but **1.2x slower** than #[UltraFast]
- Plain PHP is **~178x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 37.379μs | 27.175μs |
| SimpleDto #[UltraFast] | 4.189μs<br>(**8.9x faster**) | 4.189μs<br>(**6.5x faster**) |
| Plain PHP | 0.382μs<br>(**97.8x faster**) | 0.382μs<br>(**71.1x faster**) |
| Other Serializer | 90.009μs<br>(**2.4x slower**) | 90.009μs<br>(**3.3x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **21.5x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **7.7x faster** than DataMapper for simple mappings
- DataMapper is **2.8x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.23 μs
- MTIME (auto-validation):    2.31 μs
- HASH (auto-validation):     2.06 μs
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
Normal Dto:                1.58 μs (baseline)
#[UltraFast]:              1.46 μs (7.4% faster)
#[NoCasts]:                1.02 μs (35.4% faster)
#[NoValidation]:           1.71 μs (8.0% slower)
#[NoAttributes]:           1.67 μs (5.5% slower)
#[NoCasts, NoValidation]:  1.04 μs (33.9% faster)
#[NoAttributes, NoCasts]:  1.05 μs (33.4% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              3.18 μs (with type casting)
#[NoCasts]:                1.05 μs (67.0% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.58 ms
#[UltraFast]:              1.46 ms (7.4% faster)
#[NoCasts]:                1.02 ms (35.4% faster)
#[NoAttributes, NoCasts]:  1.05 ms (33.4% faster)

Savings per 1M requests:   ~117ms (0.1s) with #[UltraFast]
```
<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_END -->

:::tip[Better Performance for SimpleDto]
Use `#[NoAttributes]`, `#[NoCasts]`, and `#[NoValidation]` attributes to skip unnecessary operations and achieve **34-63% faster** DTO instantiation!

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
