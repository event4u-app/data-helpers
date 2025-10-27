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
- Other mapper libraries are **6.6x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto vs Plain PHP (without #[AutoCast]):
- SimpleDto:  ~4μs per operation
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~13x slower, but with type safety, validation, and immutability

SimpleDto vs Plain PHP (with #[AutoCast]):
- SimpleDto:  ~11-13μs per operation (depending on casting needs)
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~37-44x slower, but with automatic type conversion
- Note:       Only use #[AutoCast] when you need automatic type conversion
              (e.g., CSV, XML, HTTP requests with string values)

DataMapper vs Plain PHP:
- DataMapper: ~14-18μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~56x slower, but with template syntax and automatic mapping

DataMapper vs Symfony Serializer:
- DataMapper: ~24-30μs per operation
- Symfony:    ~87-107μs per operation
- Benefit:    3.6x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

## AutoCast Performance Impact

The `#[AutoCast]` attribute provides automatic type conversion but comes with a performance cost:

<!-- BENCHMARK_AUTOCAST_PERFORMANCE_START -->

```
Scenario 1: Correct types (no casting needed)
- SimpleDto (no AutoCast):   ~4μs   (13x slower than Plain PHP)
- SimpleDto (with AutoCast): ~11μs   (37x slower than Plain PHP)
- AutoCast overhead:         ~189%

Scenario 2: String types (casting needed)
- SimpleDto (with AutoCast): ~13μs   (44x slower than Plain PHP)
- Casting overhead:          ~18% (compared to correct types)
```

**Key Insights:**
- **#[AutoCast] adds ~189% overhead** even when no casting is needed (due to reflection)
- **Actual casting adds only ~18% overhead** on top of the AutoCast overhead
- **Without #[AutoCast], SimpleDto is ~2.9x faster** and closer to Plain PHP performance

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
| Simple Get | 0.256μs | Get value from flat array |
| Nested Get | 0.353μs | Get value from nested path |
| Wildcard Get | 6.467μs | Get values using single wildcard |
| Deep Wildcard Get | 65.359μs | Get values using multiple wildcards |
| Typed Get String | 0.356μs | Get typed string value |
| Typed Get Int | 0.343μs | Get typed int value |
| Create Accessor | 0.066μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.739μs | Set value in flat array |
| Nested Set | 1.059μs | Set value in nested path |
| Deep Set | 1.176μs | Set value creating new nested structure |
| Multiple Set | 1.771μs | Set multiple values at once |
| Merge | 1.080μs | Deep merge arrays |
| Unset | 1.078μs | Remove single value |
| Multiple Unset | 1.574μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 12.138μs | Map flat structure |
| Nested Mapping | 13.133μs | Map nested structure |
| Auto Map | 10.556μs | Automatic field mapping |
| Map From Template | 13.185μs | Map using template expressions |

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
| From Array | 5.689μs<br>&nbsp; | 0.167μs<br>(**34.1x faster**) | 0.221μs<br>(**25.7x faster**) | Our SimpleDto implementation |
| To Array | 11.341μs<br>&nbsp; | - | 0.293μs<br>(**38.7x faster**) | Our SimpleDto toArray() |
| Complex Data | 5.172μs<br>&nbsp; | - | 0.307μs<br>(**16.9x faster**) | Our SimpleDto with complex data |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- Plain PHP is **~49x faster** but lacks type safety and validation features
- Other DTO libraries have **similar performance** (~27x faster than SimpleDto)
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Method | DataMapper | Plain PHP | Other Mappers | Description |
|--------|------------|-----------|---------------|-------------|
| Simple Mapping | 12.104μs<br>&nbsp; | 0.093μs<br>(**130.4x faster**) | 3.611μs<br>(**3.4x faster**) | Our DataMapper implementation |
| Nested Mapping | 21.384μs<br>&nbsp; | 0.193μs<br>(**110.6x faster**) | - | Our DataMapper with nested data |
| Template Mapping | 14.544μs<br>&nbsp; | - | - | Our DataMapper with template syntax |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- Other mapper libraries are **6.6x faster** than DataMapper, but lack template syntax and advanced features
- Plain PHP is **~112x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with Symfony Serializer for nested JSON to DTO mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Method | DataMapper | Plain PHP | Symfony Serializer | Description |
|--------|------------|-----------|-------------------|-------------|
| Template Syntax | 29.752μs<br>&nbsp; | 0.407μs<br>(**73.1x faster**) | 97.115μs<br>(**3.3x slower**) | DataMapper with template syntax |
| Simple Paths | 24.062μs<br>&nbsp; | 0.407μs<br>(**59.1x faster**) | 97.115μs<br>(**4.0x slower**) | DataMapper with simple paths |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- DataMapper is **3.6x faster** than Symfony Serializer
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
