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
- Other mapper libraries are **4.8x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~2.3μs per operation
- Plain PHP:  ~0.33μs per operation
- Trade-off:  ~7x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~4.3μs per operation
- Plain PHP:  ~0.33μs per operation
- Trade-off:  ~13x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~15-16μs per operation (depending on casting needs)
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~44-47x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~16-20μs per operation
- Plain PHP:  ~0.2-0.5μs per operation
- Trade-off:  ~51x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~27-33μs per operation
- OtherSerializer:    ~97-118μs per operation
- Benefit:    3.6x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~4μs   (13x slower than Plain PHP)
- SimpleDto (with AutoCast): ~15μs   (44x slower than Plain PHP)
- AutoCast overhead:         ~235%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~16μs   (47x slower than Plain PHP)
- Casting overhead:          ~8% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~235% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~8% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~3.4x faster** and closer to Plain PHP performance

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
| Simple Get | 0.290μs | Get value from flat array |
| Nested Get | 0.389μs | Get value from nested path |
| Wildcard Get | 7.634μs | Get values using single wildcard |
| Deep Wildcard Get | 69.231μs | Get values using multiple wildcards |
| Typed Get String | 0.395μs | Get typed string value |
| Typed Get Int | 0.480μs | Get typed int value |
| Create Accessor | 0.085μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.755μs | Set value in flat array |
| Nested Set | 1.290μs | Set value in nested path |
| Deep Set | 1.497μs | Set value creating new nested structure |
| Multiple Set | 2.218μs | Set multiple values at once |
| Merge | 1.205μs | Deep merge arrays |
| Unset | 1.245μs | Remove single value |
| Multiple Unset | 1.784μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 14.695μs | Map flat structure |
| Nested Mapping | 15.103μs | Map nested structure |
| Auto Map | 12.652μs | Automatic field mapping |
| Map From Template | 16.871μs | Map using template expressions |

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
| SimpleDto Normal | 22.498μs | 27.324μs | 19.719μs |
| SimpleDto #[UltraFast] | 2.901μs<br>(**7.8x faster**) | 5.189μs<br>(**5.3x faster**) | 2.896μs<br>(**6.8x faster**) |
| LiteDto | 3.629μs<br>(**6.2x faster**) | 6.116μs<br>(**4.5x faster**) | 3.604μs<br>(**5.5x faster**) |
| LiteDto #[UltraFast] | 0.847μs<br>(**26.6x faster**) | 1.061μs<br>(**25.8x faster**) | 0.859μs<br>(**23.0x faster**) |
| Plain PHP | 0.194μs<br>(**116.1x faster**) | - | - |
| Other Dtos | 4.870μs<br>(**4.6x faster**) | 6.155μs<br>(**4.4x faster**) | 5.076μs<br>(**3.9x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **10.1x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~12x slower** than Plain PHP (vs ~125x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~0x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 14.811μs | 22.110μs | 16.831μs |
| SimpleDto #[UltraFast] | 2.901μs<br>(**5.1x faster**) | 6.347μs<br>(**3.5x faster**) | - |
| Plain PHP | 0.095μs<br>(**156.2x faster**) | 0.255μs<br>(**86.7x faster**) | - |
| Other Mappers | 4.299μs<br>(**3.4x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **6.2x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.8x faster** than DataMapper, but **0.8x slower** than #[UltraFast]
- Plain PHP is **~102x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 34.611μs | 25.796μs |
| SimpleDto #[UltraFast] | 2.901μs<br>(**11.9x faster**) | 2.901μs<br>(**8.9x faster**) |
| Plain PHP | 0.441μs<br>(**78.5x faster**) | 0.441μs<br>(**58.5x faster**) |
| Other Serializer | 107.539μs<br>(**3.1x slower**) | 107.539μs<br>(**4.2x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **37.1x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **10.4x faster** than DataMapper for simple mappings
- DataMapper is **3.6x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     3.81 μs
- MTIME (auto-validation):    3.67 μs
- HASH (auto-validation):     3.57 μs
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
Normal Dto:                2.59 μs (baseline)
#[UltraFast]:              1.94 μs (24.9% faster)
#[NoCasts]:                1.72 μs (33.3% faster)
#[NoValidation]:           2.86 μs (10.6% slower)
#[NoAttributes]:           3.11 μs (20.3% slower)
#[NoCasts, NoValidation]:  1.93 μs (25.5% faster)
#[NoAttributes, NoCasts]:  1.92 μs (25.6% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              6.24 μs (with type casting)
#[NoCasts]:                2.03 μs (67.4% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 2.59 ms
#[UltraFast]:              1.94 ms (24.9% faster)
#[NoCasts]:                1.72 ms (33.3% faster)
#[NoAttributes, NoCasts]:  1.92 ms (25.6% faster)

Savings per 1M requests:   ~644ms (0.6s) with #[UltraFast]
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
