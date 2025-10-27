---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **3.5x faster** than Symfony Serializer for complex mappings
- Other mapper libraries are **5.9x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto vs Plain PHP:
- SimpleDto:  ~9-11μs per operation
- Plain PHP:  ~0.3μs per operation
- Trade-off:  ~35x slower, but with type safety, validation, and immutability

DataMapper vs Plain PHP:
- DataMapper: ~11-13μs per operation
- Plain PHP:  ~0.1-0.4μs per operation
- Trade-off:  ~46x slower, but with template syntax and automatic mapping

DataMapper vs Symfony Serializer:
- DataMapper: ~20-24μs per operation
- Symfony:    ~69-84μs per operation
- Benefit:    3.5x faster with better developer experience
```
<!-- BENCHMARK_TRADEOFFS_END -->

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
| Simple Get | 0.240μs | Get value from flat array |
| Nested Get | 0.311μs | Get value from nested path |
| Wildcard Get | 5.224μs | Get values using single wildcard |
| Deep Wildcard Get | 52.852μs | Get values using multiple wildcards |
| Typed Get String | 0.265μs | Get typed string value |
| Typed Get Int | 0.267μs | Get typed int value |
| Create Accessor | 0.062μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.638μs | Set value in flat array |
| Nested Set | 0.936μs | Set value in nested path |
| Deep Set | 1.090μs | Set value creating new nested structure |
| Multiple Set | 1.480μs | Set multiple values at once |
| Merge | 0.889μs | Deep merge arrays |
| Unset | 0.868μs | Remove single value |
| Multiple Unset | 1.323μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 9.731μs | Map flat structure |
| Nested Mapping | 10.294μs | Map nested structure |
| Auto Map | 8.343μs | Automatic field mapping |
| Map From Template | 9.886μs | Map using template expressions |

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
| From Array | 8.307μs<br>(we are) | 0.154μs<br>(**54.1x slower**) | 0.198μs<br>(**42.0x slower**) | Our SimpleDto implementation |
| To Array | 11.990μs<br>(we are) | - | 0.247μs<br>(**48.5x slower**) | Our SimpleDto toArray() |
| Complex Data | 8.379μs<br>(we are) | - | 0.273μs<br>(**30.7x slower**) | Our SimpleDto with complex data |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- Plain PHP is **~69x faster** but lacks type safety and validation features
- Other DTO libraries have **similar performance** (~40x faster than SimpleDto)
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Method | DataMapper | Plain PHP | Other Mappers | Description |
|--------|------------|-----------|---------------|-------------|
| Simple Mapping | 9.612μs<br>(we are) | 0.082μs<br>(**117.2x slower**) | 3.020μs<br>(**3.2x slower**) | Our DataMapper implementation |
| Nested Mapping | 15.099μs<br>(we are) | 0.182μs<br>(**82.9x slower**) | - | Our DataMapper with nested data |
| Template Mapping | 11.368μs<br>(we are) | - | - | Our DataMapper with template syntax |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- Other mapper libraries are **5.9x faster** than DataMapper, but lack template syntax and advanced features
- Plain PHP is **~91x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with Symfony Serializer for nested JSON to DTO mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Method | DataMapper | Plain PHP | Symfony Serializer | Description |
|--------|------------|-----------|-------------------|-------------|
| Template Syntax | 25.431μs<br>(we are) | 0.335μs<br>(**76.0x slower**) | 76.240μs<br>(**3.0x faster**) | DataMapper with template syntax |
| Simple Paths | 18.291μs<br>(we are) | 0.335μs<br>(**54.7x slower**) | 76.240μs<br>(**4.2x faster**) | DataMapper with simple paths |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- DataMapper is **3.5x faster** than Symfony Serializer
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
