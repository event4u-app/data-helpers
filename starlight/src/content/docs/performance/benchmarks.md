---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers provides powerful features with acceptable performance overhead:

<!-- BENCHMARK_INTRODUCTION_START -->

- **Type safety and validation** - With reasonable performance cost
- **4.0x faster** than Symfony Serializer for complex mappings
- Other mapper libraries are **6.8x faster**, but DataMapper provides better features
- **Low memory footprint** - ~1.2 KB per instance
<!-- BENCHMARK_INTRODUCTION_END -->

## Performance Trade-offs

Data Helpers prioritizes **developer experience, type safety, and maintainability** over raw speed:

<!-- BENCHMARK_TRADEOFFS_START -->

```
SimpleDto vs Plain PHP:
- SimpleDto:  ~14-17μs per operation
- Plain PHP:  ~0.4μs per operation
- Trade-off:  ~37x slower, but with type safety, validation, and immutability

DataMapper vs Plain PHP:
- DataMapper: ~18-21μs per operation
- Plain PHP:  ~0.2-0.5μs per operation
- Trade-off:  ~57x slower, but with template syntax and automatic mapping

DataMapper vs Symfony Serializer:
- DataMapper: ~31-37μs per operation
- Symfony:    ~121-148μs per operation
- Benefit:    4.0x faster with better developer experience
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
| Simple Get | 0.388μs | Get value from flat array |
| Nested Get | 0.506μs | Get value from nested path |
| Wildcard Get | 8.898μs | Get values using single wildcard |
| Deep Wildcard Get | 79.966μs | Get values using multiple wildcards |
| Typed Get String | 0.413μs | Get typed string value |
| Typed Get Int | 0.412μs | Get typed int value |
| Create Accessor | 0.082μs | Instantiate DataAccessor |

<!-- BENCHMARK_DATA_ACCESSOR_END -->

## DataMutator Performance

<!-- BENCHMARK_DATA_MUTATOR_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 1.049μs | Set value in flat array |
| Nested Set | 1.516μs | Set value in nested path |
| Deep Set | 1.757μs | Set value creating new nested structure |
| Multiple Set | 2.358μs | Set multiple values at once |
| Merge | 1.532μs | Deep merge arrays |
| Unset | 1.238μs | Remove single value |
| Multiple Unset | 2.045μs | Remove multiple values |

<!-- BENCHMARK_DATA_MUTATOR_END -->

## DataMapper Performance

<!-- BENCHMARK_DATA_MAPPER_START -->

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 16.813μs | Map flat structure |
| Nested Mapping | 19.306μs | Map nested structure |
| Auto Map | 15.861μs | Automatic field mapping |
| Map From Template | 18.958μs | Map using template expressions |

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
| From Array | 14.119μs<br>(we are) | 0.214μs<br>(**65.9x slower**) | 0.291μs<br>(**48.5x slower**) | Our SimpleDto implementation |
| To Array | 18.957μs<br>(we are) | - | 0.322μs<br>(**58.9x slower**) | Our SimpleDto toArray() |
| Complex Data | 12.629μs<br>(we are) | - | 0.415μs<br>(**30.4x slower**) | Our SimpleDto with complex data |

<!-- BENCHMARK_DTO_COMPARISON_END -->

<!-- BENCHMARK_DTO_INSIGHTS_START -->

**Key Insights:**
- SimpleDto provides **type safety, validation, and immutability** with reasonable overhead
- Plain PHP is **~75x faster** but lacks type safety and validation features
- Other DTO libraries have **similar performance** (~44x faster than SimpleDto)
- The overhead is acceptable for the added safety and developer experience
<!-- BENCHMARK_DTO_INSIGHTS_END -->

## Mapper Performance Comparison

Comparison of our DataMapper with other mapper libraries and plain PHP:

<!-- BENCHMARK_MAPPER_COMPARISON_START -->

| Method | DataMapper | Plain PHP | Other Mappers | Description |
|--------|------------|-----------|---------------|-------------|
| Simple Mapping | 14.875μs<br>(we are) | 0.094μs<br>(**158.6x slower**) | 4.225μs<br>(**3.5x slower**) | Our DataMapper implementation |
| Nested Mapping | 24.884μs<br>(we are) | 0.247μs<br>(**100.9x slower**) | - | Our DataMapper with nested data |
| Template Mapping | 18.803μs<br>(we are) | - | - | Our DataMapper with template syntax |

<!-- BENCHMARK_MAPPER_COMPARISON_END -->

<!-- BENCHMARK_MAPPER_INSIGHTS_START -->

**Key Insights:**
- Other mapper libraries are **6.8x faster** than DataMapper, but lack template syntax and advanced features
- Plain PHP is **~115x faster** but requires manual mapping code for each use case
- DataMapper provides the best balance of features, readability, and maintainability
- The overhead is acceptable for complex mapping scenarios with better developer experience
<!-- BENCHMARK_MAPPER_INSIGHTS_END -->

## Serialization Performance

Comparison with Symfony Serializer for nested JSON to DTO mapping:

<!-- BENCHMARK_SERIALIZATION_START -->

| Method | DataMapper | Plain PHP | Symfony Serializer | Description |
|--------|------------|-----------|-------------------|-------------|
| Template Syntax | 38.196μs<br>(we are) | 0.478μs<br>(**80.0x slower**) | 134.687μs<br>(**3.5x faster**) | DataMapper with template syntax |
| Simple Paths | 29.654μs<br>(we are) | 0.478μs<br>(**62.1x slower**) | 134.687μs<br>(**4.5x faster**) | DataMapper with simple paths |

<!-- BENCHMARK_SERIALIZATION_END -->

<!-- BENCHMARK_SERIALIZATION_INSIGHTS_START -->

**Key Insights:**
- DataMapper is **4.0x faster** than Symfony Serializer
- Zero reflection overhead for template-based mapping
- Optimized for nested data structures
<!-- BENCHMARK_SERIALIZATION_INSIGHTS_END -->

## See Also

- [Running Benchmarks](/data-helpers/performance/running-benchmarks/) - How to run
- [Optimization](/data-helpers/performance/optimization/) - Optimization guide
