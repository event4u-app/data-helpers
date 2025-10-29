---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.7x faster** than Other Serializer for complex mappings
- Other mapper libraries are **4.7x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto #[UltraFast] vs Plain PHP:
- SimpleDto:  ~1.9μs per operation
- Plain PHP:  ~0.24μs per operation
- Trade-off:  ~8x slower, but with type safety, immutability, and mapping

SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~3.3μs per operation
- Plain PHP:  ~0.24μs per operation
- Trade-off:  ~14x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~11-12μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~45-50x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~12-15μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~48x slower, but with template syntax and automatic mapping

DataMapper vs Other Serializer:
- DataMapper: ~21-25μs per operation
- OtherSerializer:    ~78-95μs per operation
- Benefit:    3.7x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~3μs   (14x slower than Plain PHP)
- SimpleDto (with AutoCast): ~12μs   (50x slower than Plain PHP)
- AutoCast overhead:         ~254%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~11μs   (45x slower than Plain PHP)
- Casting overhead:          ~-11% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~254% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~-11% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~3.5x faster** and closer to Plain PHP performance

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
| Simple Get | 0.263μs | Get value from flat array |
| Nested Get | 0.343μs | Get value from nested path |
| Wildcard Get | 6.669μs | Get values using single wildcard |
| Deep Wildcard Get | 62.665μs | Get values using multiple wildcards |
| Typed Get String | 0.301μs | Get typed string value |
| Typed Get Int | 0.306μs | Get typed int value |
| Create Accessor | 0.065μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.666μs | Set value in flat array |
| Nested Set | 1.066μs | Set value in nested path |
| Deep Set | 1.184μs | Set value creating new nested structure |
| Multiple Set | 1.630μs | Set multiple values at once |
| Merge | 1.054μs | Deep merge arrays |
| Unset | 1.006μs | Remove single value |
| Multiple Unset | 1.452μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.265μs | Map flat structure |
| Nested Mapping | 12.597μs | Map nested structure |
| Auto Map | 10.110μs | Automatic field mapping |
| Map From Template | 11.650μs | Map using template expressions |

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
| SimpleDto Normal | 16.359μs | 22.669μs | 16.192μs |
| SimpleDto #[UltraFast] | 2.402μs<br>(**6.8x faster**) | 4.737μs<br>(**4.8x faster**) | 2.351μs<br>(**6.9x faster**) |
| LiteDto | 2.779μs<br>(**5.9x faster**) | 4.791μs<br>(**4.7x faster**) | 2.940μs<br>(**5.5x faster**) |
| LiteDto #[UltraFast] | 0.659μs<br>(**24.8x faster**) | 0.825μs<br>(**27.5x faster**) | 0.641μs<br>(**25.2x faster**) |
| Plain PHP | 0.242μs<br>(**67.6x faster**) | - | - |
| Other Dtos | 3.834μs<br>(**4.3x faster**) | 4.455μs<br>(**5.1x faster**) | 3.539μs<br>(**4.6x faster**) |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- **#[UltraFast] mode** provides **9.5x faster** performance than normal SimpleDto
- **#[UltraFast]** is only **~10x slower** than Plain PHP (vs ~97x for normal mode)
- **#[UltraFast]** is competitive with other Dto libraries (~0x slower)
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Implementation | Simple Mapping | Nested Mapping | Template Mapping |
|----------------|----------------|----------------|------------------|
| DataMapper | 10.781μs | 17.015μs | 12.593μs |
| SimpleDto #[UltraFast] | 2.402μs<br>(**4.5x faster**) | 5.015μs<br>(**3.4x faster**) | - |
| Plain PHP | 0.090μs<br>(**119.3x faster**) | 0.190μs<br>(**89.6x faster**) | - |
| Other Mappers | 3.201μs<br>(**3.4x faster**) | N/A | N/A |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **5.6x faster** than DataMapper for simple mapping
- Other mapper libraries are **4.7x faster** than DataMapper, but **0.8x slower** than #[UltraFast]
- Plain PHP is **~96x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability for complex mappings
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with external serializers for nested JSON to Dto mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Implementation | Template Syntax | Simple Paths |
|----------------|-----------------|---------------|
| DataMapper | 25.835μs | 20.451μs |
| SimpleDto #[UltraFast] | 2.402μs<br>(**10.8x faster**) | 2.402μs<br>(**8.5x faster**) |
| Plain PHP | 0.350μs<br>(**73.9x faster**) | 0.350μs<br>(**58.5x faster**) |
| Other Serializer | 86.761μs<br>(**3.4x slower**) | 86.761μs<br>(**4.2x slower**) |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- **SimpleDto #[UltraFast]** is **36.1x faster** than Other Serializer!
- **SimpleDto #[UltraFast]** is **9.6x faster** than DataMapper for simple mappings
- DataMapper is **3.7x faster** than Other Serializer for complex mappings
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     2.44 μs
- MTIME (auto-validation):    2.41 μs
- HASH (auto-validation):     2.35 μs
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
#[UltraFast]:              1.98 μs (12.0% slower)
#[NoCasts]:                1.17 μs (33.4% faster)
#[NoValidation]:           1.76 μs (0.4% faster)
#[NoAttributes]:           1.77 μs (0.5% slower)
#[NoCasts, NoValidation]:  1.22 μs (30.7% faster)
#[NoAttributes, NoCasts]:  1.24 μs (29.5% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast Dto:              3.75 μs (with type casting)
#[NoCasts]:                1.32 μs (64.8% faster)
```

### Real-World API (1,000 Dtos)

```
SimpleDto:                 1.76 ms
#[UltraFast]:              1.98 ms (12.0% slower)
#[NoCasts]:                1.17 ms (33.4% faster)
#[NoAttributes, NoCasts]:  1.24 ms (29.5% faster)

Overhead per 1M requests:  ~212ms (0.2s) with #[UltraFast]
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
