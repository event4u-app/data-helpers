---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.8x faster** than Symfony Serializer for complex mappings
- Other mapper libraries are **6.2x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~3μs per operation
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~13x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~10μs per operation (depending on casting needs)
- Plain PHP:  ~0.2μs per operation
- Trade-off:  ~44-45x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~11-13μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~43x slower, but with template syntax and automatic mapping

DataMapper vs Symfony Serializer:
- DataMapper: ~19-23μs per operation
- Symfony:    ~71-87μs per operation
- Benefit:    3.8x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~3μs   (13x slower than Plain PHP)
- SimpleDto (with AutoCast): ~10μs   (44x slower than Plain PHP)
- AutoCast overhead:         ~239%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~10μs   (45x slower than Plain PHP)
- Casting overhead:          ~2% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~239% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~2% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~3.4x faster** and closer to Plain PHP performance

**When to use #[AutoCast]:**
- ✅ CSV imports (all values are strings)
- ✅ XML parsing (all values are strings)
- ✅ HTTP requests (query params and form data are strings)
- ✅ Legacy APIs with inconsistent types
- ❌ Internal DTOs with correct types
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
| Simple Get | 0.238μs | Get value from flat array |
| Nested Get | 0.302μs | Get value from nested path |
| Wildcard Get | 5.068μs | Get values using single wildcard |
| Deep Wildcard Get | 51.743μs | Get values using multiple wildcards |
| Typed Get String | 0.272μs | Get typed string value |
| Typed Get Int | 0.258μs | Get typed int value |
| Create Accessor | 0.063μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.601μs | Set value in flat array |
| Nested Set | 0.885μs | Set value in nested path |
| Deep Set | 1.019μs | Set value creating new nested structure |
| Multiple Set | 1.422μs | Set multiple values at once |
| Merge | 0.864μs | Deep merge arrays |
| Unset | 0.829μs | Remove single value |
| Multiple Unset | 1.261μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 9.388μs | Map flat structure |
| Nested Mapping | 10.311μs | Map nested structure |
| Auto Map | 8.069μs | Automatic field mapping |
| Map From Template | 9.912μs | Map using template expressions |

<!-- BENCHMARK_DATA_MAPPER_END -->

## Memory Usage

```
Dto Instance:    ~1.2 KB
With Validation: ~1.5 KB
With Caching:    ~0.8 KB
```

## DTO Performance Comparison

Comparison of our SimpleDto implementation with other DTO libraries and plain PHP:

<!-- BENCHMARK_DTO_COMPARISON_START -->

| Method | SimpleDto | Plain PHP | Other DTOs | Description |
|--------|-----------|-----------|------------|-------------|
| From Array | 16.983μs<br>&nbsp; | 0.152μs<br>(**111.4x faster**) | 0.195μs<br>(**87.2x faster**) | Our SimpleDto implementation |
| To Array | 18.978μs<br>&nbsp; | - | 0.252μs<br>(**75.2x faster**) | Our SimpleDto toArray() |
| Complex Data | 14.498μs<br>&nbsp; | - | 0.270μs<br>(**53.6x faster**) | Our SimpleDto with complex data |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- Plain PHP is **~123x faster** but lacks type safety and validation features
- Other DTO libraries have **similar performance** (~70x faster than SimpleDto)
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Method | DataMapper | Plain PHP | Other Mappers | Description |
|--------|------------|-----------|---------------|-------------|
| Simple Mapping | 9.410μs<br>&nbsp; | 0.086μs<br>(**109.2x faster**) | 2.813μs<br>(**3.3x faster**) | Our DataMapper implementation |
| Nested Mapping | 15.010μs<br>&nbsp; | 0.187μs<br>(**80.1x faster**) | - | Our DataMapper with nested data |
| Template Mapping | 11.042μs<br>&nbsp; | - | - | Our DataMapper with template syntax |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- Other mapper libraries are **6.2x faster** than DataMapper, but lack template syntax and advanced features
- Plain PHP is **~86x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with Symfony Serializer for nested JSON to DTO mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Method | DataMapper | Plain PHP | Symfony Serializer | Description |
|--------|------------|-----------|-------------------|-------------|
| Template Syntax | 23.896μs<br>&nbsp; | 0.416μs<br>(**57.5x faster**) | 79.336μs<br>(**3.3x slower**) | DataMapper with template syntax |
| Simple Paths | 17.503μs<br>&nbsp; | 0.416μs<br>(**42.1x faster**) | 79.336μs<br>(**4.5x slower**) | DataMapper with simple paths |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- DataMapper is **3.8x faster** than Symfony Serializer
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## Cache Invalidation Performance

Data Helpers supports different cache invalidation strategies with varying performance characteristics:

<!-- BENCHMARK_CACHE_INVALIDATION_START -->

```
Cache Invalidation Modes (50,000 iterations, warm cache):
- MANUAL (no validation):     1.96 μs
- MTIME (auto-validation):    1.98 μs
- HASH (auto-validation):     1.96 μs
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

### Basic DTO (10,000 iterations)

```
Normal DTO:                1.58 μs (baseline)
#[NoCasts]:                1.08 μs (31.9% faster)
#[NoValidation]:           1.61 μs (-2.2% faster)
#[NoAttributes]:           1.58 μs (0.2% faster)
#[NoCasts, NoValidation]:  1.07 μs (32.3% faster)
#[NoAttributes, NoCasts]:  1.07 μs (32.2% faster)
```

### With AutoCast (10,000 iterations)

```
AutoCast DTO:              3.37 μs (with type casting)
#[NoCasts]:                1.15 μs (66.0% faster!)
```

### Real-World API (1,000 DTOs)

```
SimpleDTO:                 1.58 ms
#[NoCasts]:                1.08 ms (31.9% faster)
#[NoAttributes, NoCasts]:  1.07 ms (32.2% faster)

Savings per 1M requests:   ~504ms (0.5s)
```
<!-- BENCHMARK_PERFORMANCE_ATTRIBUTES_END -->

:::tip[Maximum Performance for SimpleDto]
Use `#[NoAttributes]`, `#[NoCasts]`, and `#[NoValidation]` attributes to skip unnecessary operations and achieve **34-63% faster** DTO instantiation!

See [Performance Attributes](/data-helpers/attributes/performance/#performance-attributes) for details.
:::

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
- [SimpleDto Caching](/data-helpers/simple-dto/caching/) - Cache invalidation strategies
- [Cache Generation Guide](/data-helpers/performance/cache-generation/) - Manual cache generation
