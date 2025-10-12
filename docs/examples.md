# Examples

Run individual examples with:

```bash
php examples/01-data-accessor.php
php examples/02-data-mutator.php
php examples/03-data-mapper-simple.php
php examples/04-data-mapper-with-hooks.php
php examples/05-data-mapper-pipeline.php
php examples/06-laravel.php
php examples/07-symfony-doctrine.php
php examples/08-mapped-data-model.php
php examples/08-template-expressions.php
php examples/09-performance-caching.php
php examples/13-wildcard-where-clause.php
php examples/14-custom-wildcard-operators.php
php examples/16-distinct-like-operators.php
```

## Example Files

### Basic Examples (Framework-agnostic)
- **01-data-accessor.php** - Reading nested data with wildcards (arrays)
- **02-data-mutator.php** - Writing, merging, and unsetting values (arrays)
- **03-data-mapper-simple.php** - Simple mapping between structures (arrays)
- **04-data-mapper-with-hooks.php** - Advanced mapping with hooks (arrays)
- **05-data-mapper-pipeline.php** - Pipeline API with filters
- **08-mapped-data-model.php** - MappedDataModel for working with mapped data
- **08-template-expressions.php** - Template-based mapping with filters
- **09-performance-caching.php** - Performance optimization and caching features
- **13-wildcard-where-clause.php** - Wildcard operators (WHERE, ORDER BY, LIMIT, OFFSET)
- **14-custom-wildcard-operators.php** - Custom wildcard operators
- **16-distinct-like-operators.php** - DISTINCT and LIKE operators

### Framework-specific Examples
- **06-laravel.php** - Laravel Collections, Eloquent Models, Arrayable
- **07-symfony-doctrine.php** - Symfony/Doctrine Collections and Entities

## Running Examples

All examples work out of the box. Framework-specific examples (06, 07) require the respective framework packages to be installed.

**Note:** Examples 06 and 07 require framework packages:

```bash
# For Laravel examples (06)
composer require illuminate/support:^8 illuminate/database:^8
php examples/06-laravel.php

# For Doctrine examples (07)
composer require doctrine/collections:^1.6 doctrine/orm:^2.10
php examples/07-symfony-doctrine.php
```

## Example Highlights

### Performance & Caching (09)

The performance example demonstrates:
- **Template expression caching** - Parsed expressions are cached for reuse
- **Template mapping cache** - Mapping arrays are cached with LRU eviction
- **File content caching** - Loaded files are cached to avoid repeated I/O
- **Filter instance caching** - Filter instances are reused
- **Performance mode switching** - Fast vs. Safe mode for different use cases
- **Cache management** - Statistics, clearing, and monitoring
- **LRU eviction** - Automatic eviction of least recently used entries
- **Memory usage estimation** - Understanding cache memory footprint

Run the example:
```bash
php examples/09-performance-caching.php
```

Key features demonstrated:
- Configuration for optimal performance
- Cache statistics and monitoring
- Performance mode switching (fast/safe)
- LRU eviction behavior
- Cache management and clearing
- Memory usage estimation
- Performance best practices
