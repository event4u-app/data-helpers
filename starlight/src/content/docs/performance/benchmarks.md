---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.6x faster** than Symfony Serializer for complex mappings
- Other mapper libraries are **4.6x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto #[UltraFast]:  ~3.6μs per operation
- Plain PHP:               ~0.31μs per operation
- Trade-off:               ~12x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~4.5μs per operation
- Plain PHP:  ~0.31μs per operation
- Trade-off:  ~15x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~15μs per operation (depending on casting needs)
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~48-50x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~15-18μs per operation
- Plain PHP:  ~0.2-0.5μs per operation
- Trade-off:  ~54x slower, but with template syntax and automatic mapping

DataMapper vs Symfony Serializer:
- DataMapper: ~26-31μs per operation
- Symfony:    ~91-111μs per operation
- Benefit:    3.6x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~4μs   (15x slower than Plain PHP)
- SimpleDto (with AutoCast): ~15μs   (50x slower than Plain PHP)
- AutoCast overhead:         ~243%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~15μs   (48x slower than Plain PHP)
- Casting overhead:          ~-5% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~243% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~-5% overhead** on top of the AutoCast overhead
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

**Consider Plain PHP when:**
- You're in performance-critical tight loops
- You process millions of operations per second
- You don't need validation or type safety
- You're willing to write and maintain manual mapping code

## DataAccessor Performance

<!-- BENCHMARK_DATA_ACCESSOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Get | 0.287μs | Get value from flat array |
| Nested Get | 0.406μs | Get value from nested path |
| Wildcard Get | 7.017μs | Get values using single wildcard |
| Deep Wildcard Get | 69.394μs | Get values using multiple wildcards |
| Typed Get String | 0.344μs | Get typed string value |
| Typed Get Int | 0.336μs | Get typed int value |
| Create Accessor | 0.068μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.844μs | Set value in flat array |
| Nested Set | 1.191μs | Set value in nested path |
| Deep Set | 1.403μs | Set value creating new nested structure |
| Multiple Set | 1.988μs | Set multiple values at once |
| Merge | 1.245μs | Deep merge arrays |
| Unset | 1.317μs | Remove single value |
| Multiple Unset | 1.806μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.664μs | Map flat structure |
| Nested Mapping | 13.207μs | Map nested structure |
| Auto Map | 11.367μs | Automatic field mapping |
| Map From Template | 14.650μs | Map using template expressions |

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

| Method | SimpleDto Normal | SimpleDto #[UltraFast] | Plain PHP | Other Dtos | Description |
|--------|------------------|------------------------|-----------|------------|-------------|
| From Array | 16.708μs<br>&nbsp; | 2.622μs<br>(**6.4x faster**) | 0.162μs<br>(**103.3x faster**) | 0.408μs<br>(**41.0x faster**) | Our SimpleDto implementation |
| To Array | 23.270μs<br>&nbsp; | 5.347μs<br>(**4.4x faster**) | - | 0.401μs<br>(**58.0x faster**) | Our SimpleDto toArray() |
| Complex Data | 17.685μs<br>&nbsp; | 2.826μs<br>(**6.3x faster**) | - | 0.397μs<br>(**44.5x faster**) | Our SimpleDto with complex data |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **5.3x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~24x slower** than Plain PHP (vs ~126x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~9x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Method | DataMapper | SimpleDto #[UltraFast] | Plain PHP | Other Mappers | Description |
|--------|------------|------------------------|-----------|---------------|-------------|
| Simple Mapping | 12.892μs<br>&nbsp; | 2.622μs<br>(**4.9x faster**) | 0.090μs<br>(**143.9x faster**) | 3.647μs<br>(**3.5x faster**) | Our DataMapper implementation |
| Nested Mapping | 20.697μs<br>&nbsp; | 6.994μs<br>(**3.0x faster**) | 0.212μs<br>(**97.6x faster**) | - | Our DataMapper with nested data |
| Template Mapping | 15.528μs<br>&nbsp; | - | - | - | Our DataMapper with template syntax |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **6.2x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.6x faster** than DataMapper, but **0.7x slower** than #[UltraFast]
- Plain PHP is **~109x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with Symfony Serializer for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Method | DataMapper | SimpleDto #[UltraFast] | Plain PHP | Symfony Serializer | Description |
|--------|------------|------------------------|-----------|-------------------|-------------|
| Template Syntax | 31.853μs<br>&nbsp; | 2.622μs<br>(**12.2x faster**) | 0.414μs<br>(**76.9x faster**) | 100.674μs<br>(**3.2x slower**) | DataMapper with template syntax |
| Simple Paths | 24.860μs<br>&nbsp; | 2.622μs<br>(**9.5x faster**) | 0.414μs<br>(**60.0x faster**) | 100.674μs<br>(**4.0x slower**) | DataMapper with simple paths |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **38.4x faster** than Symfony Serializer!
- **SimpleDto #[UltraFast]** is **10.8x faster** than DataMapper for simple mappings
- DataMapper is **3.6x faster** than Symfony Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     3.82 μs
- MTIME (auto-validation):    2.76 μs
- HASH (auto-validation):     2.87 μs
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
Normal Dto:                2.66 μs (baseline)
#[UltraFast]:              1.68 μs (36.8% faster)
#[NoCasts]:                1.48 μs (44.2% faster)
#[NoValidation]:           2.19 μs (17.7% faster)
#[NoAttributes]:           2.17 μs (18.4% faster)
#[NoCasts, NoValidation]:  1.43 μs (46.3% faster)
#[NoAttributes, NoCasts]:  1.46 μs (45.1% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              4.55 μs (with type casting)
#[NoCasts]:                1.54 μs (66.1% faster!)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 2.66 ms
#[UltraFast]:              1.68 ms (36.8% faster)
#[NoCasts]:                1.48 ms (44.2% faster)
#[NoAttributes, NoCasts]:  1.46 ms (45.1% faster)

Savings per 1M requests:   ~980ms (1.0s) with #[UltraFast]
```
<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_END -->

:::tip[Better Performance for SimpleDto]
Use `#[NoAttributes]`, `#[NoCasts]`, and `#[NoValidation]` attributes to skip unnecessary operations and achieve **34-63% faster** DTO instantiation!

See [Performance Attributes](/data-helpers/attributes/performance/#performance-attributes) for details.
:::

:::caution[UltraFast SimpleDto]{icon="seti:favicon"}
Use `#[UltraFast]` attribute to achieve **~8x faster** performance than normal SimpleDto mode. UltraFast bypasses all overhead (validation, casts, pipeline) for maximum speed while keeping type safety and immutability. Perfect for high-throughput scenarios where you need SimpleDto's API but with near-Plain-PHP performance.

See [Performance Modes](/data-helpers/simple-dto/performance-modes/#performance-modes-overview) for details.
:::

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
- [SimpleDto Caching](/data-helpers/simple-dto/caching/) - Cache invalidation strategies
- [Cache Generation Guide](/data-helpers/performance/cache-generation/) - Manual cache generation
