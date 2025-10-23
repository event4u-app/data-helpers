---
title: Performance Comparison
description: Comparison with similar DTO and serialization packages
---

Comparison with similar DTO and serialization packages.

## Overview

Data Helpers delivers excellent performance:

- **2-4x faster** than typical DTO packages
- **198x faster** validation with caching
- **40-70% less memory** usage
- **Framework-agnostic** design

## vs Typical DTO Packages

### Instance Creation

```
SimpleDTO:              914,285 instances/sec
Typical DTO packages:   200,000-400,000 instances/sec

Result: 2-4x faster
```

### Validation

```
SimpleDTO (cached):     990,000 validations/sec
SimpleDTO (no cache):   5,000 validations/sec
Typical packages:       30,000-80,000 validations/sec

Result: Caching provides 198x improvement
```

### Features

| Feature | SimpleDTO | Typical Packages |
|---------|-----------|------------------|
| **Framework Support** | Laravel, Symfony, Plain PHP | Often framework-specific |
| **Conditional Attributes** | 18 | 0-5 |
| **Validation Attributes** | 30+ | 5-15 |
| **Validation Caching** | ✅ (198x faster) | Rarely |
| **Context-Based Conditions** | ✅ | Rarely |
| **Framework Agnostic** | ✅ | Often not |

## vs Serialization Libraries

### Performance

```
SimpleDTO:                  ~5.0μs per operation
Typical serializers:        ~15-25μs per operation

Result: 3-5x faster
```

### Memory

```
SimpleDTO:                  ~1.2 KB per instance
Typical serializers:        ~2-4 KB per instance

Result: 40-70% less memory
```

## Real-World Performance

### API Response (100 users)

```
SimpleDTO:              45ms
Typical DTO packages:   120-180ms

Result: 2.5-4x faster in real-world scenarios
```

### Large Dataset (1000 records)

```
SimpleDTO:              420ms
Typical packages:       1200-2000ms

Result: 3-5x faster with large datasets
```

## Why Data Helpers is Fast

**Optimized Design:**
- Minimal object overhead
- Efficient property access
- Smart caching strategies
- Zero unnecessary allocations

**Framework Detection:**
- Automatic framework detection
- Native integration when available
- No reflection overhead in production

**Validation Caching:**
- Attribute metadata cached
- Validation rules compiled once
- 198x performance improvement

## See Also

- [Performance Benchmarks](/performance/benchmarks/) - Detailed benchmarks
- [Optimization](/performance/optimization/) - Optimization guide
