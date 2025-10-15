# Scripts

This directory contains utility scripts for the Data Helpers package.

## update-benchmark-results.php

Automatically updates the benchmark results in README.md.

### Usage

```bash
composer benchmark:readme
```

### What it does

1. Runs PHPBench with table output
2. Parses the benchmark results
3. Updates the section between `<!-- BENCHMARK_RESULTS_START -->` and `<!-- BENCHMARK_RESULTS_END -->` in README.md
4. Preserves all other content in the README

### When to use

- After making performance improvements
- Before releasing a new version
- When benchmark results are outdated

### Output

The script generates markdown tables with:

- Operation name (formatted from benchmark method names)
- Time in microseconds (μs)
- Description of what the operation does

Example output:

```markdown
### DataAccessor

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Get | 0.319μs | Get value from flat array |
| Nested Get | 0.417μs | Get value from nested path |

...
```
