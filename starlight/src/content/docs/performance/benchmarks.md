---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.0x faster** than Other Serializer for complex mappings
- Other mapper libraries are **6.1x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~2.1μs per operation
- Plain PHP:  ~0.20μs per operation
- Trade-off:  ~11x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~3.1μs per operation
- Plain PHP:  ~0.20μs per operation
- Trade-off:  ~16x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~9-10μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~47-48x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~14-17μs per operation
- Plain PHP:  ~0.1-0.3μs per operation
- Trade-off:  ~76x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~20-25μs per operation
- OtherSerializer:    ~62-76μs per operation
- Benefit:    3.0x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~3μs   (16x slower than Plain PHP)
- SimpleDto (with AutoCast): ~9μs   (47x slower than Plain PHP)
- AutoCast overhead:         ~202%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~10μs   (48x slower than Plain PHP)
- Casting overhead:          ~3% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~202% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~3% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~3.0x faster** and closer to Plain PHP performance

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
| Simple Get | 0.273μs | Get value from flat array |
| Nested Get | 0.340μs | Get value from nested path |
| Wildcard Get | 10.300μs | Get values using single wildcard |
| Deep Wildcard Get | 51.046μs | Get values using multiple wildcards |
| Typed Get String | 0.298μs | Get typed string value |
| Typed Get Int | 0.294μs | Get typed int value |
| Create Accessor | 0.063μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.914μs | Set value in flat array |
| Nested Set | 1.211μs | Set value in nested path |
| Deep Set | 1.311μs | Set value creating new nested structure |
| Multiple Set | 1.712μs | Set multiple values at once |
| Merge | 1.180μs | Deep merge arrays |
| Unset | 1.161μs | Remove single value |
| Multiple Unset | 1.587μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.871μs | Map flat structure |
| Nested Mapping | 13.393μs | Map nested structure |
| Auto Map | 10.889μs | Automatic field mapping |
| Map From Template | 12.891μs | Map using template expressions |

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
| SimpleDto Normal | 22.948μs | 26.305μs | 22.899μs |
| SimpleDto #[UltraFast] | 2.217μs<br>(**10.3x faster**) | 4.412μs<br>(**6.0x faster**) | 2.233μs<br>(**10.3x faster**) |
| LiteDto | 2.674μs<br>(**8.6x faster**) | 4.002μs<br>(**6.6x faster**) | 2.693μs<br>(**8.5x faster**) |
| LiteDto #[UltraFast] | 1.252μs<br>(**18.3x faster**) | 1.481μs<br>(**17.8x faster**) | 1.287μs<br>(**17.8x faster**) |
| Plain PHP | 0.107μs<br>(**215.3x faster**) | - | - |
| Other Dtos | 3.204μs<br>(**7.2x faster**) | 3.921μs<br>(**6.7x faster**) | 3.179μs<br>(**7.2x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **11.2x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~22x slower** than Plain PHP (vs ~244x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~1x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 12.545μs | 17.930μs | 14.627μs |
| SimpleDto #[UltraFast] | 2.217μs<br>(**5.7x faster**) | 4.701μs<br>(**3.8x faster**) | - |
| Plain PHP | 0.066μs<br>(**190.6x faster**) | 0.131μs<br>(**136.5x faster**) | - |
| Other Mappers | 2.558μs<br>(**4.9x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **6.8x faster** than DataMapper for simple mapping
- Other mapper libraries are **6.1x faster** than DataMapper, but **0.9x slower** than #[UltraFast]
- Plain PHP is **~152x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 25.311μs | 20.120μs |
| SimpleDto #[UltraFast] | 2.217μs<br>(**11.4x faster**) | 2.217μs<br>(**9.1x faster**) |
| Plain PHP | 0.315μs<br>(**80.4x faster**) | 0.315μs<br>(**63.9x faster**) |
| Other Serializer | 68.770μs<br>(**2.7x slower**) | 68.770μs<br>(**3.4x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **31.0x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **10.2x faster** than DataMapper for simple mappings
- DataMapper is **3.0x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.04 μs
- MTIME (auto-validation):    1.99 μs
- HASH (auto-validation):     2.02 μs
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
Normal Dto:                1.69 μs (baseline)
#[UltraFast]:              1.17 μs (30.8% faster)
#[NoCasts]:                1.08 μs (36.3% faster)
#[NoValidation]:           1.72 μs (1.8% slower)
#[NoAttributes]:           1.67 μs (1.1% faster)
#[NoCasts, NoValidation]:  1.07 μs (37.0% faster)
#[NoAttributes, NoCasts]:  1.07 μs (36.5% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              3.30 μs (with type casting)
#[NoCasts]:                1.04 μs (68.5% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.69 ms
#[UltraFast]:              1.17 ms (30.8% faster)
#[NoCasts]:                1.08 ms (36.3% faster)
#[NoAttributes, NoCasts]:  1.07 ms (36.5% faster)

Savings per 1M requests:   ~522ms (0.5s) with #[UltraFast]
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
