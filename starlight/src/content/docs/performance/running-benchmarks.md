---
title: Running Benchmarks
description: How to run performance benchmarks
---

How to run performance benchmarks.

## Introduction

Data Helpers uses PHPBench for performance benchmarking.

## Running Benchmarks

### Run All Benchmarks

```bash
composer benchmark
```

### Run Specific Benchmark

```bash
vendor/bin/phpbench run benchmarks/DataAccessorBench.php
```

### Create Baseline

```bash
composer benchmark:baseline
```

### Compare Against Baseline

```bash
composer benchmark:compare
```

## Available Benchmarks

- **DataAccessor** - Get operations
- **DataMutator** - Set/merge operations
- **DataMapper** - Mapping operations
- **SimpleDto** - Dto creation, validation, serialization

## Creating Custom Benchmarks

<!-- skip-test: Full file example -->
```php
<?php

namespace event4u\DataHelpers\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[BeforeMethods('setUp')]
class MyBench
{
    private array $data;

    public function setUp(): void
    {
        $this->data = ['key' => 'value'];
    }

    #[Revs(1000)]
    #[Iterations(5)]
    public function benchMyOperation(): void
    {
        // Code to benchmark
    }
}
```

## Interpreting Results

### Mode

Average time per operation:

```
mode: 0.5μs  // 0.5 microseconds
```

### RStdev

Relative standard deviation:

```
rstdev: 2.5%  // 2.5% variation
```

Lower is better.

## Best Practices

### Disable Xdebug

```bash
php -d xdebug.mode=off vendor/bin/phpbench run
```

### Use Baseline

```bash
composer benchmark:baseline
# Make changes
composer benchmark:compare
```

## See Also

- [Performance Benchmarks](/performance/benchmarks/) - Results
- [Optimization](/performance/optimization/) - Optimization guide
