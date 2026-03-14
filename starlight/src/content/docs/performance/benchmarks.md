---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.5x faster** than Other Serializer for complex mappings
- Other mapper libraries are up to **4.9x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~8.1μs per operation
- Plain PHP:  ~0.31μs per operation
- Trade-off:  ~26x slower, but with type safety, immutability and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~15.1μs per operation
- Plain PHP:  ~0.31μs per operation
- Trade-off:  ~49x slower, but with type safety, validation and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~15-16μs per operation (depending on casting needs)
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~48-51x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~10-13μs per operation
- Plain PHP:  ~0.1-0.2μs per operation
- Trade-off:  ~74x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~15-18μs per operation
- OtherSerializer:    ~53-65μs per operation
- Benefit:    3.5x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~15μs   (49x slower than Plain PHP)
- SimpleDto (with AutoCast): ~15μs   (48x slower than Plain PHP)
- AutoCast overhead:         ~0%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~16μs   (51x slower than Plain PHP)
- Casting overhead:          ~6% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~0% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~6% overhead** on top of the AutoCast overhead
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
| Simple Get | 0.047μs | Get value from flat array |
| Nested Get | 0.368μs | Get value from nested path |
| Wildcard Get | 0.704μs | Get values using single wildcard |
| Deep Wildcard Get | 31.185μs | Get values using multiple wildcards |
| Typed Get String | 0.061μs | Get typed string value |
| Typed Get Int | 0.060μs | Get typed int value |
| Create Accessor | 0.045μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.893μs | Set value in flat array |
| Nested Set | 1.114μs | Set value in nested path |
| Deep Set | 1.210μs | Set value creating new nested structure |
| Multiple Set | 1.515μs | Set multiple values at once |
| Merge | 1.086μs | Deep merge arrays |
| Unset | 1.066μs | Remove single value |
| Multiple Unset | 1.430μs | Remove multiple values |
| Wildcard Set | 1.497μs |  |
| Deep Wildcard Set | 4.006μs |  |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 9.977μs | Map flat structure |
| Nested Mapping | 10.399μs | Map nested structure |
| Auto Map | 8.082μs | Automatic field mapping |
| Map From Template | 11.133μs | Map using template expressions |

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
| SimpleDto Normal | 5.663μs | 31.683μs | 5.711μs |
| SimpleDto #[UltraFast] | 4.667μs<br>(**1.2x faster**) | 30.157μs | 4.699μs<br>(**1.2x faster**) |
| LiteDto | 3.676μs<br>(**1.5x faster**) | 6.572μs<br>(**4.8x faster**) | 3.716μs<br>(**1.5x faster**) |
| LiteDto #[UltraFast] | 2.351μs<br>(**2.4x faster**) | 4.363μs<br>(**7.3x faster**) | 2.355μs<br>(**2.4x faster**) |
| Plain PHP | 0.080μs<br>(**70.8x faster**) | - | - |
| Other Dtos | 2.686μs<br>(**2.1x faster**) | 3.281μs<br>(**9.7x faster**) | 2.685μs<br>(**2.1x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **1.8x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~110x slower** than Plain PHP (vs ~195x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~3x slower)
- SimpleDto provides **type safety, validation and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 10.138μs | 12.329μs | 12.300μs |
| SimpleDto #[UltraFast] | 4.667μs<br>(**2.2x faster**) | 8.820μs<br>(**1.4x faster**) | - |
| Plain PHP | 0.054μs<br>(**188.9x faster**) | 0.103μs<br>(**119.3x faster**) | - |
| Other Mappers | 2.060μs<br>(**4.9x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **2.2x faster** than DataMapper for simple mapping
- Other mapper libraries are up to **4.9x faster** than DataMapper, and **2.3x faster** than #[UltraFast]
- Plain PHP is **~189x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability and maintainability for complex mappings

<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 19.953μs | 13.464μs |
| SimpleDto #[UltraFast] | 4.667μs<br>(**4.3x faster**) | 4.667μs<br>(**2.9x faster**) |
| Plain PHP | 0.252μs<br>(**79.2x faster**) | 0.252μs<br>(**53.4x faster**) |
| Other Serializer | 58.741μs<br>(**2.9x slower**) | 58.741μs<br>(**4.4x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **12.6x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **3.6x faster** than DataMapper for simple mappings
- DataMapper is **3.5x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.71 μs
- MTIME (auto-validation):    2.70 μs
- HASH (auto-validation):     2.70 μs
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
Normal Dto:                1.54 μs (baseline)
#[UltraFast]:              1.25 μs (18.7% faster)
#[NoCasts]:                1.08 μs (30.3% faster)
#[NoValidation]:           1.61 μs (4.6% slower)
#[NoAttributes]:           1.62 μs (4.7% slower)
#[NoCasts, NoValidation]:  1.10 μs (29.0% faster)
#[NoAttributes, NoCasts]:  1.62 μs (5.1% slower)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              1.70 μs (with type casting)
#[NoCasts]:                1.10 μs (35.4% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.54 ms
#[UltraFast]:              1.25 ms (18.7% faster)
#[NoCasts]:                1.08 ms (30.3% faster)
#[NoAttributes, NoCasts]:  1.62 ms (5.1% slower)

Savings per 1M requests:   ~289ms (0.3s) with #[UltraFast]
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
