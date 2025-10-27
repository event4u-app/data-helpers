# Dto Performance Benchmarks

This directory contains performance benchmarks comparing Traditional Mutable Dtos with SimpleDto Immutable Dtos.

## Running the Benchmarks

```bash
# Run the comparison benchmark
docker exec data-helpers-php84 php benchmarks/dto-comparison-benchmark.php

# Run the detailed benchmark
docker exec data-helpers-php84 php benchmarks/dto-detailed-benchmark.php
```

## Benchmark Results Summary

### 1. Dto Creation with DataMapper

**Winner: SimpleDto (41.8% faster)**

- **Traditional Mutable Dto**: 247.38 μs per operation
- **SimpleDto Immutable**: 144.04 μs per operation

SimpleDto is significantly faster when used with DataMapper because it maps to an array first, then creates the Dto in one go using named arguments. Traditional Dtos require the DataMapper to set each property individually.

### 2. Simple Dto Creation (no DataMapper)

**Winner: Traditional (98.6% faster)**

- **Traditional**: 0.11 μs per operation (8.8M ops/sec)
- **SimpleDto**: 0.23 μs per operation (4.4M ops/sec)

For simple Dto creation without DataMapper, traditional Dtos are faster because they use direct property assignment. SimpleDto uses `fromArray()` with the spread operator and named arguments, which has overhead.

### 3. Property Access (read)

**Winner: Traditional (7.8% faster)**

- **Traditional**: 0.02 μs per operation (46.2M ops/sec)
- **SimpleDto**: 0.02 μs per operation (42.9M ops/sec)

Property access is nearly identical, with traditional Dtos being slightly faster. The difference is negligible in real-world applications.

### 4. toArray() Conversion

**Winner: Traditional (110.9% faster)**

- **Traditional (manual)**: 0.05 μs per operation (21M ops/sec)
- **SimpleDto (automatic)**: 0.10 μs per operation (9.9M ops/sec)

Manual array creation is faster than `get_object_vars()` used by SimpleDto's `toArray()`. However, SimpleDto provides this functionality automatically without boilerplate code.

### 5. JSON Serialization

**Winner: Traditional (231.8% faster)**

- **Traditional (manual)**: 0.18 μs per operation (5.5M ops/sec)
- **SimpleDto (automatic)**: 0.61 μs per operation (1.6M ops/sec)

Manual JSON encoding is faster than using the `JsonSerializable` interface. However, SimpleDto provides automatic serialization without any boilerplate code.

### 6. Batch Creation (100 Dtos)

**Winner: Traditional (128.0% faster)**

- **Traditional**: 9.03 μs per batch (110K batches/sec)
- **SimpleDto**: 20.59 μs per batch (48K batches/sec)

For batch operations, traditional Dtos are faster due to simpler construction. However, SimpleDto provides immutability and type safety benefits.

## Performance Analysis

### When SimpleDto is Faster

1. **DataMapper Integration** (41.8% faster)
   - Mapping from external sources (JSON, XML, arrays)
   - Complex nested structures
   - Bulk data transformations

### When Traditional Dto is Faster

1. **Simple Creation** (98.6% faster)
   - Direct instantiation without DataMapper
   - Hot paths with millions of operations

2. **Array Conversion** (110.9% faster)
   - Manual array creation
   - Performance-critical serialization

3. **JSON Serialization** (231.8% faster)
   - Manual JSON encoding
   - High-frequency API responses

4. **Batch Operations** (128.0% faster)
   - Creating many Dtos at once
   - Bulk data processing

## Real-World Performance Impact

### Negligible Impact Scenarios

For most applications, the performance difference is **negligible**:

- **1,000 Dto creations**: ~0.12 ms difference
- **10,000 JSON serializations**: ~4.3 ms difference
- **100,000 property reads**: ~1.7 ms difference

These differences are typically **insignificant** compared to:
- Database queries (10-100ms)
- HTTP requests (50-500ms)
- File I/O operations (1-100ms)

### Significant Impact Scenarios

Performance differences become **significant** in:

1. **High-frequency APIs** (>10,000 requests/sec)
2. **Real-time systems** (sub-millisecond requirements)
3. **Batch processing** (millions of records)
4. **Memory-constrained environments**

## Recommendations

### Use SimpleDto When

✅ **Developer Experience > Raw Performance**
- Automatic `toArray()` and JSON serialization
- Immutability guarantees
- Type safety with readonly properties
- Less boilerplate code

✅ **Working with DataMapper**
- 41.8% faster than traditional Dtos
- Cleaner integration with mapping operations

✅ **API Development**
- Frequent array/JSON conversions
- Data validation and type safety
- Immutable request/response objects

✅ **Domain Models**
- Value objects
- Event sourcing
- CQRS patterns

### Use Traditional Dto When

✅ **Performance is Critical**
- High-frequency operations (>1M ops/sec)
- Performance-critical hot paths
- Real-time systems

✅ **Mutability is Required**
- Data needs to be modified after creation
- Incremental updates
- Legacy code integration

✅ **Simple Use Cases**
- Minimal array/JSON conversions
- Direct property access only
- No complex transformations

## Optimization Tips

### For SimpleDto

1. **Cache Dto instances** when possible
2. **Use batch operations** with `array_map()`
3. **Avoid unnecessary conversions** (toArray/JSON)
4. **Profile your specific use case**

### For Traditional Dto

1. **Add helper methods** for common operations
2. **Consider traits** for shared functionality
3. **Document mutability** clearly
4. **Use type hints** for safety

## Conclusion

**SimpleDto provides excellent developer experience with acceptable performance for most use cases.**

The performance overhead is typically **negligible** in real-world applications where:
- Database queries dominate execution time
- Network I/O is the bottleneck
- Developer productivity matters

**Choose based on your priorities:**
- **Developer Experience + Type Safety** → SimpleDto
- **Raw Performance** → Traditional Dto
- **DataMapper Integration** → SimpleDto (faster!)

## Benchmark Environment

- **PHP Version**: 8.4
- **Hardware**: Docker container on macOS
- **Iterations**: 1,000 - 1,000,000 depending on operation
- **Warmup**: 10-100 iterations before measurement
- **Timing**: `hrtime(true)` for nanosecond precision

