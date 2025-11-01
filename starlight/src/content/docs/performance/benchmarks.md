---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.4x faster** than Other Serializer for complex mappings
- Other mapper libraries are **4.5x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~6.5μs per operation
- Plain PHP:  ~0.21μs per operation
- Trade-off:  ~30x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~4.0μs per operation
- Plain PHP:  ~0.21μs per operation
- Trade-off:  ~19x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~6μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~27-29x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~13-16μs per operation
- Plain PHP:  ~0.1-0.3μs per operation
- Trade-off:  ~75x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~20-24μs per operation
- OtherSerializer:    ~67-82μs per operation
- Benefit:    3.4x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~4μs   (19x slower than Plain PHP)
- SimpleDto (with AutoCast): ~6μs   (27x slower than Plain PHP)
- AutoCast overhead:         ~44%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~6μs   (29x slower than Plain PHP)
- Casting overhead:          ~5% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~44% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~5% overhead** on top of the AutoCast overhead
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
| Simple Get | 0.270μs | Get value from flat array |
| Nested Get | 0.330μs | Get value from nested path |
| Wildcard Get | 9.299μs | Get values using single wildcard |
| Deep Wildcard Get | 49.217μs | Get values using multiple wildcards |
| Typed Get String | 0.286μs | Get typed string value |
| Typed Get Int | 0.285μs | Get typed int value |
| Create Accessor | 0.063μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.893μs | Set value in flat array |
| Nested Set | 1.173μs | Set value in nested path |
| Deep Set | 1.270μs | Set value creating new nested structure |
| Multiple Set | 1.627μs | Set multiple values at once |
| Merge | 1.145μs | Deep merge arrays |
| Unset | 1.122μs | Remove single value |
| Multiple Unset | 1.503μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.497μs | Map flat structure |
| Nested Mapping | 12.960μs | Map nested structure |
| Auto Map | 11.066μs | Automatic field mapping |
| Map From Template | 12.992μs | Map using template expressions |

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
| SimpleDto Normal | 4.800μs | 26.217μs | 4.746μs |
| SimpleDto #[UltraFast] | 4.541μs | 25.868μs | 4.606μs |
| LiteDto | 2.680μs<br>(**1.8x faster**) | 3.776μs<br>(**6.9x faster**) | 2.571μs<br>(**1.8x faster**) |
| LiteDto #[UltraFast] | 1.192μs<br>(**4.0x faster**) | 1.390μs<br>(**18.9x faster**) | 1.232μs<br>(**3.9x faster**) |
| Plain PHP | 0.106μs<br>(**45.3x faster**) | - | - |
| Other Dtos | 3.116μs<br>(**1.5x faster**) | 3.782μs<br>(**6.9x faster**) | 3.067μs<br>(**1.5x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **1.8x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~66x slower** than Plain PHP (vs ~121x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~2x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 13.189μs | 17.191μs | 13.580μs |
| SimpleDto #[UltraFast] | 4.541μs<br>(**2.9x faster**) | 8.007μs<br>(**2.1x faster**) | - |
| Plain PHP | 0.066μs<br>(**200.4x faster**) | 0.129μs<br>(**133.5x faster**) | - |
| Other Mappers | 2.448μs<br>(**5.4x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **3.2x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.5x faster** than DataMapper, but **1.4x slower** than #[UltraFast]
- Plain PHP is **~151x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 24.662μs | 19.549μs |
| SimpleDto #[UltraFast] | 4.541μs<br>(**5.4x faster**) | 4.541μs<br>(**4.3x faster**) |
| Plain PHP | 0.308μs<br>(**80.0x faster**) | 0.308μs<br>(**63.4x faster**) |
| Other Serializer | 74.495μs<br>(**3.0x slower**) | 74.495μs<br>(**3.8x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **16.4x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **4.9x faster** than DataMapper for simple mappings
- DataMapper is **3.4x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.22 μs
- MTIME (auto-validation):    2.27 μs
- HASH (auto-validation):     2.18 μs
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
Normal Dto:                1.35 μs (baseline)
#[UltraFast]:              1.33 μs (1.6% faster)
#[NoCasts]:                0.93 μs (31.1% faster)
#[NoValidation]:           1.39 μs (3.1% slower)
#[NoAttributes]:           1.45 μs (7.6% slower)
#[NoCasts, NoValidation]:  0.96 μs (29.1% faster)
#[NoAttributes, NoCasts]:  1.41 μs (4.7% slower)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              2.42 μs (with type casting)
#[NoCasts]:                1.07 μs (55.7% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.35 ms
#[UltraFast]:              1.33 ms (1.6% faster)
#[NoCasts]:                0.93 ms (31.1% faster)
#[NoAttributes, NoCasts]:  1.41 ms (4.7% slower)

Savings per 1M requests:   ~22ms (0.0s) with #[UltraFast]
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
