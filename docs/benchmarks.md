# Benchmarks

Performance benchmarks for the Data Helpers library using PHPBench.

## Running Benchmarks

```bash
# Run all benchmarks
composer benchmark

# Create a baseline for comparison
composer benchmark:baseline

# Compare against baseline
composer benchmark:compare
```

## Benchmark Results

### DataAccessor

| Operation       | Time (μs) | Description                                                   |
|-----------------|-----------|---------------------------------------------------------------|
| Simple Get      | ~0.3      | Get value from flat array                                     |
| Nested Get      | ~0.4      | Get value from nested path                                    |
| Wildcard Get    | ~5.0      | Get values using single wildcard                              |
| Deep Wildcard   | ~91.0     | Get values using multiple wildcards (10 depts × 20 employees) |
| Typed Get       | ~0.4      | Get typed value (string/int)                                  |
| Create Accessor | ~0.1      | Instantiate DataAccessor                                      |

**Key Insights:**

- Simple and nested access is very fast (~0.3-0.4μs)
- Wildcards add overhead but are still performant (~5μs for single level)
- Deep wildcards scale linearly with data size
- Creating an accessor is extremely lightweight

### DataMutator

| Operation      | Time (μs) | Description                             |
|----------------|-----------|-----------------------------------------|
| Simple Set     | ~0.6      | Set value in flat array                 |
| Nested Set     | ~0.9      | Set value in nested path                |
| Deep Set       | ~1.1      | Set value creating new nested structure |
| Multiple Set   | ~1.7      | Set multiple values at once             |
| Merge          | ~1.0      | Deep merge arrays                       |
| Unset          | ~0.9      | Remove single value                     |
| Multiple Unset | ~1.5      | Remove multiple values                  |

**Key Insights:**

- All mutation operations are sub-microsecond
- Multiple operations in batch are more efficient than individual calls
- Deep nesting adds minimal overhead
- Merge is as fast as simple set

### DataMapper

| Operation        | Time (μs) | Description                    |
|------------------|-----------|--------------------------------|
| Simple Mapping   | ~6.3      | Map flat structure             |
| Nested Mapping   | ~7.1      | Map nested structure           |
| AutoMap          | ~6.7      | Automatic field mapping        |
| Template Mapping | ~5.0      | Map using template expressions |

**Key Insights:**

- All mapping operations are in the 5-7μs range
- Template mapping is slightly faster due to optimized path
- Nested mapping adds minimal overhead
- AutoMap performance is comparable to explicit mapping

## Benchmark Configuration

Benchmarks are configured in `phpbench.json`:

- **Revs**: 1000 (operations per iteration)
- **Iterations**: 5 (statistical samples)
- **Progress**: Dots display
- **Report**: Table format with mode and rstdev

## Adding New Benchmarks

Create a new benchmark class in `benchmarks/`:

```php
<?php

namespace event4u\DataHelpers\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class MyBench
{
    public function setUp(): void
    {
        // Setup code
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMyOperation(): void
    {
        // Code to benchmark
    }
}
```

## Performance Tips

Based on benchmark results:

1. **Use simple paths when possible** - Flat access is ~3x faster than wildcards
2. **Batch operations** - Multiple sets/unsets in one call is more efficient
3. **Cache accessors** - Creating an accessor is cheap, but reusing is better
4. **Wildcards are fine** - Even deep wildcards are fast enough for most use cases
5. **Template mapping** - Slightly faster than explicit mapping for simple cases

## System Information

Benchmarks run on:

- PHP 8.3.25
- No Xdebug
- No OPcache (for accurate measurements)

For production use, enable OPcache for better performance.
