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
- Other mapper libraries are **9.6x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~1.6μs per operation
- Plain PHP:  ~0.22μs per operation
- Trade-off:  ~7x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~3.0μs per operation
- Plain PHP:  ~0.22μs per operation
- Trade-off:  ~13x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~10μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~44x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~10-12μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~48x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~18-21μs per operation
- OtherSerializer:    ~63-77μs per operation
- Benefit:    3.6x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~3μs   (13x slower than Plain PHP)
- SimpleDto (with AutoCast): ~10μs   (44x slower than Plain PHP)
- AutoCast overhead:         ~226%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~10μs   (44x slower than Plain PHP)
- Casting overhead:          ~1% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~226% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~1% overhead** on top of the AutoCast overhead
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
| Simple Get | 0.227μs | Get value from flat array |
| Nested Get | 0.282μs | Get value from nested path |
| Wildcard Get | 4.652μs | Get values using single wildcard |
| Deep Wildcard Get | 48.923μs | Get values using multiple wildcards |
| Typed Get String | 0.249μs | Get typed string value |
| Typed Get Int | 0.250μs | Get typed int value |
| Create Accessor | 0.064μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.573μs | Set value in flat array |
| Nested Set | 0.848μs | Set value in nested path |
| Deep Set | 0.965μs | Set value creating new nested structure |
| Multiple Set | 1.360μs | Set multiple values at once |
| Merge | 0.833μs | Deep merge arrays |
| Unset | 0.792μs | Remove single value |
| Multiple Unset | 1.206μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 8.884μs | Map flat structure |
| Nested Mapping | 9.297μs | Map nested structure |
| Auto Map | 7.810μs | Automatic field mapping |
| Map From Template | 9.093μs | Map using template expressions |

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
| SimpleDto Normal | 11.712μs | 15.336μs | 11.683μs |
| SimpleDto #[UltraFast] | 1.949μs<br>(**6.0x faster**) | 3.650μs<br>(**4.2x faster**) | 1.901μs<br>(**6.1x faster**) |
| LiteDto | 2.399μs<br>(**4.9x faster**) | 3.809μs<br>(**4.0x faster**) | 2.403μs<br>(**4.9x faster**) |
| LiteDto #[UltraFast] | 0.554μs<br>(**21.2x faster**) | 0.701μs<br>(**21.9x faster**) | 0.555μs<br>(**21.1x faster**) |
| Plain PHP | 0.144μs<br>(**81.6x faster**) | - | - |
| Other Dtos | 0.407μs<br>(**28.8x faster**) | 0.416μs<br>(**36.8x faster**) | 0.411μs<br>(**28.5x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **8.3x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~12x slower** than Plain PHP (vs ~98x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~4x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 8.981μs | 14.208μs | 10.775μs |
| SimpleDto #[UltraFast] | 1.949μs<br>(**4.6x faster**) | 4.600μs<br>(**3.1x faster**) | - |
| Plain PHP | 0.072μs<br>(**124.4x faster**) | 0.164μs<br>(**86.5x faster**) | - |
| Other Mappers | N/A | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **5.8x faster** than DataMapper for simple mapping
- Other mapper libraries are **9.6x faster** than DataMapper, but **1.7x slower** than #[UltraFast]
- Plain PHP is **~96x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 22.088μs | 16.801μs |
| SimpleDto #[UltraFast] | 1.949μs<br>(**11.3x faster**) | 1.949μs<br>(**8.6x faster**) |
| Plain PHP | 0.315μs<br>(**70.2x faster**) | 0.315μs<br>(**53.4x faster**) |
| Other Serializer | 69.790μs<br>(**3.2x slower**) | 69.790μs<br>(**4.2x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **35.8x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **10.0x faster** than DataMapper for simple mappings
- DataMapper is **3.6x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.09 μs
- MTIME (auto-validation):    2.11 μs
- HASH (auto-validation):     2.11 μs
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
Normal Dto:                1.76 μs (baseline)
#[UltraFast]:              1.17 μs (33.3% faster)
#[NoCasts]:                1.17 μs (33.6% faster)
#[NoValidation]:           1.74 μs (1.0% faster)
#[NoAttributes]:           1.72 μs (2.1% faster)
#[NoCasts, NoValidation]:  1.13 μs (35.6% faster)
#[NoAttributes, NoCasts]:  1.13 μs (35.6% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              3.43 μs (with type casting)
#[NoCasts]:                1.13 μs (67.2% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.76 ms
#[UltraFast]:              1.17 ms (33.3% faster)
#[NoCasts]:                1.17 ms (33.6% faster)
#[NoAttributes, NoCasts]:  1.13 ms (35.6% faster)

Savings per 1M requests:   ~584ms (0.6s) with #[UltraFast]
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
