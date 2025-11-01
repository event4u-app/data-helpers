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
- Other mapper libraries are **4.4x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~7.7μs per operation
- Plain PHP:  ~0.21μs per operation
- Trade-off:  ~37x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~4.0μs per operation
- Plain PHP:  ~0.21μs per operation
- Trade-off:  ~20x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~6-7μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~28-32x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~13-16μs per operation
- Plain PHP:  ~0.1-0.3μs per operation
- Trade-off:  ~74x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~21-25μs per operation
- OtherSerializer:    ~62-75μs per operation
- Benefit:    3.0x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~4μs   (20x slower than Plain PHP)
- SimpleDto (with AutoCast): ~6μs   (28x slower than Plain PHP)
- AutoCast overhead:         ~44%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~7μs   (32x slower than Plain PHP)
- Casting overhead:          ~13% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~44% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~13% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~1.4x faster** and closer to Plain PHP performance

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
| Simple Get | 0.262μs | Get value from flat array |
| Nested Get | 0.327μs | Get value from nested path |
| Wildcard Get | 9.525μs | Get values using single wildcard |
| Deep Wildcard Get | 50.128μs | Get values using multiple wildcards |
| Typed Get String | 0.281μs | Get typed string value |
| Typed Get Int | 0.286μs | Get typed int value |
| Create Accessor | 0.062μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.909μs | Set value in flat array |
| Nested Set | 1.212μs | Set value in nested path |
| Deep Set | 1.297μs | Set value creating new nested structure |
| Multiple Set | 1.695μs | Set multiple values at once |
| Merge | 1.163μs | Deep merge arrays |
| Unset | 1.142μs | Remove single value |
| Multiple Unset | 1.565μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.750μs | Map flat structure |
| Nested Mapping | 13.026μs | Map nested structure |
| Auto Map | 10.963μs | Automatic field mapping |
| Map From Template | 12.962μs | Map using template expressions |

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
| SimpleDto Normal | 4.996μs | 26.774μs | 5.013μs |
| SimpleDto #[UltraFast] | 4.620μs | 26.467μs | 4.623μs |
| LiteDto | 7.543μs<br>(**1.5x slower**) | 9.116μs<br>(**2.9x faster**) | 7.501μs<br>(**1.5x slower**) |
| LiteDto #[UltraFast] | 3.357μs<br>(**1.5x faster**) | 3.576μs<br>(**7.5x faster**) | 3.368μs<br>(**1.5x faster**) |
| Plain PHP | 0.107μs<br>(**46.7x faster**) | - | - |
| Other Dtos | 3.209μs<br>(**1.6x faster**) | 3.925μs<br>(**6.8x faster**) | 3.200μs<br>(**1.6x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **1.6x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~77x slower** than Plain PHP (vs ~123x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~2x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 12.541μs | 17.522μs | 14.217μs |
| SimpleDto #[UltraFast] | 4.620μs<br>(**2.7x faster**) | 8.211μs<br>(**2.1x faster**) | - |
| Plain PHP | 0.066μs<br>(**188.9x faster**) | 0.132μs<br>(**132.5x faster**) | - |
| Other Mappers | 2.552μs<br>(**4.9x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **3.2x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.4x faster** than DataMapper, but **1.4x slower** than #[UltraFast]
- Plain PHP is **~149x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 25.359μs | 20.210μs |
| SimpleDto #[UltraFast] | 4.620μs<br>(**5.5x faster**) | 4.620μs<br>(**4.4x faster**) |
| Plain PHP | 0.312μs<br>(**81.2x faster**) | 0.312μs<br>(**64.7x faster**) |
| Other Serializer | 68.542μs<br>(**2.7x slower**) | 68.542μs<br>(**3.4x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **14.8x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **4.9x faster** than DataMapper for simple mappings
- DataMapper is **3.0x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.15 μs
- MTIME (auto-validation):    2.13 μs
- HASH (auto-validation):     2.12 μs
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
Normal Dto:                1.44 μs (baseline)
#[UltraFast]:              1.33 μs (7.6% faster)
#[NoCasts]:                1.00 μs (30.5% faster)
#[NoValidation]:           1.42 μs (1.4% faster)
#[NoAttributes]:           1.41 μs (2.0% faster)
#[NoCasts, NoValidation]:  0.99 μs (31.0% faster)
#[NoAttributes, NoCasts]:  1.42 μs (1.6% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              1.92 μs (with type casting)
#[NoCasts]:                1.00 μs (48.2% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.44 ms
#[UltraFast]:              1.33 ms (7.6% faster)
#[NoCasts]:                1.00 ms (30.5% faster)
#[NoAttributes, NoCasts]:  1.42 ms (1.6% faster)

Savings per 1M requests:   ~110ms (0.1s) with #[UltraFast]
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
