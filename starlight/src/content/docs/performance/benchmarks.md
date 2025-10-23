---
title: Performance Benchmarks
description: Detailed performance benchmarks for Data Helpers
---

Detailed performance benchmarks for Data Helpers.

## Introduction

Data Helpers is optimized for high performance:

- **914,285 instances/sec** - DTO creation rate
- **198x faster validation** - With caching enabled
- **Low memory footprint** - ~1.2 KB per instance
- **Microsecond operations** - Most operations under 1μs

## DTO Creation

```
SimpleDTO:     914,285 instances/sec
Plain Array:   1,200,000 instances/sec

SimpleDTO adds type safety with minimal overhead
```

## Validation

### Without Cache

```
5,000 validations/sec
0.2ms per validation
```

### With Cache

```
990,000 validations/sec
0.001ms per validation

Improvement: 198x faster
```

## Type Casting

```
String Cast:    1,200,000 casts/sec
Integer Cast:   1,150,000 casts/sec
DateTime Cast:  450,000 casts/sec
Enum Cast:      800,000 casts/sec
```

## Serialization

```
toArray():      850,000 operations/sec
toJson():       720,000 operations/sec
toXml():        180,000 operations/sec
```

## DataAccessor

```
Simple Get:      ~0.3μs
Nested Get:      ~0.4μs
Wildcard Get:    ~5.0μs
```

## DataMutator

```
Simple Set:      ~0.6μs
Nested Set:      ~0.9μs
Deep Set:        ~1.1μs
```

## DataMapper

```
Simple Map:      ~5.0μs
Nested Map:      ~6.5μs
Template Map:    ~5.5μs
```

## Memory Usage

```
DTO Instance:    ~1.2 KB
With Validation: ~1.5 KB
With Caching:    ~0.8 KB
```

## Comparison with Similar Packages

Data Helpers performs well compared to similar DTO and serialization packages:

### Instance Creation

```
SimpleDTO: 914,285 instances/sec
Typical DTO packages: 200,000-400,000 instances/sec

Result: 2-4x faster than typical alternatives
```

### Validation Performance

```
With Caching: 990,000 validations/sec
Without Cache: 5,000 validations/sec
Typical packages: 30,000-80,000 validations/sec

Result: Caching provides 198x improvement
```

### Memory Efficiency

```
SimpleDTO: ~1.2 KB per instance
Typical packages: 2-4 KB per instance

Result: 40-70% less memory usage
```

### Framework Support

```
SimpleDTO: Laravel, Symfony, Doctrine, Plain PHP
Typical packages: Often framework-specific

Result: True framework-agnostic design
```

## See Also

- [Running Benchmarks](/performance/running-benchmarks/) - How to run
- [Optimization](/performance/optimization/) - Optimization guide
