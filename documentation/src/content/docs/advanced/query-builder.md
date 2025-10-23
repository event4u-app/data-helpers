---
title: Query Builder
description: Laravel-inspired fluent interface for building complex data mapping queries
---

The **DataMapperQuery** provides a Laravel-inspired fluent interface for building complex data mapping queries.

## Introduction

The Query Builder combines the power of the DataMapper with an intuitive, chainable API.

**Features:**

- **Laravel-style Fluent Interface** - Chainable methods for intuitive query building
- **Method Chaining in Any Order** - Call methods in whatever order makes sense
- **Pipeline Integration** - Combine with DataMapper pipelines for data transformation
- **WHERE with Comparison Operators** - `=`, `!=`, `<>`, `>`, `<`, `>=`, `<=`
- **Advanced WHERE Conditions** - `between()`, `whereIn()`, `whereNull()`, `exists()`, `like()`
- **Nested WHERE Conditions** - Use closures for complex AND/OR logic
- **OR WHERE Conditions** - Combine conditions with OR logic
- **ORDER BY, LIMIT, OFFSET** - Sort and paginate results
- **GROUP BY with Aggregations** - COUNT, SUM, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT
- **HAVING Clause** - Filter grouped results
- **DISTINCT** - Remove duplicates

## Quick Start

```php
use event4u\DataHelpers\DataMapper;

$products = [
    ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1299],
    ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 29],
    ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 299],
];

// Simple query
$result = DataMapper::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();
```

## Basic Usage

### Creating a Query

```php
use event4u\DataHelpers\DataMapper;

// Static factory method
$query = DataMapper::query();

// Or use constructor
$query = new DataMapperQuery();
```

### Adding a Data Source

```php
$query->source('products', $products);
```

### Executing the Query

```php
$result = $query->get();
```

## Pipeline Integration

Combine the Query Builder with DataMapper pipelines:

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;

$result = DataMapper::query()
    ->source('products', $products)
    ->pipe([
        new TrimStrings(),
        new LowercaseStrings(),
    ])
    ->where('category', 'Electronics')
    ->get();
```

## WHERE Conditions

### Basic WHERE

```php
// Equal
$query->where('category', 'Electronics');
$query->where('category', '=', 'Electronics');

// Not equal
$query->where('status', '!=', 'deleted');
$query->where('status', '<>', 'deleted');

// Comparison
$query->where('price', '>', 100);
$query->where('price', '>=', 100);
$query->where('price', '<', 1000);
$query->where('price', '<=', 1000);
```

### Advanced WHERE

```php
// Between
$query->between('price', 100, 500);

// In array
$query->whereIn('category', ['Electronics', 'Furniture']);

// Not in array
$query->whereNotIn('status', ['deleted', 'archived']);

// Null check
$query->whereNull('deletedAt');
$query->whereNotNull('publishedAt');

// Exists check
$query->exists('metadata.tags');

// Like pattern
$query->like('name', '%Laptop%');
```

### Nested Conditions

```php
$query->where(function($q) {
    $q->where('category', 'Electronics')
      ->where('price', '>', 100);
})->orWhere(function($q) {
    $q->where('category', 'Furniture')
      ->where('price', '<', 500);
});
```

### OR Conditions

```php
$query->where('category', 'Electronics')
      ->orWhere('category', 'Furniture');
```

## Sorting

### ORDER BY

```php
// Ascending
$query->orderBy('price');
$query->orderBy('price', 'ASC');

// Descending
$query->orderBy('price', 'DESC');

// Multiple columns
$query->orderBy('category', 'ASC')
      ->orderBy('price', 'DESC');
```

## Limiting Results

### LIMIT and OFFSET

```php
// Limit
$query->limit(10);

// Offset
$query->offset(20);

// Pagination
$query->limit(10)->offset(20); // Page 3
```

## Grouping and Aggregations

### GROUP BY

```php
$query->groupBy('category');

// Multiple fields
$query->groupBy(['category', 'brand']);
```

### Aggregations

```php
$query->groupBy('category')
      ->aggregations([
          'total' => ['COUNT'],
          'avgPrice' => ['AVG', '{{ products.*.price }}'],
          'maxPrice' => ['MAX', '{{ products.*.price }}'],
      ]);
```

### HAVING Clause

```php
$query->groupBy('category')
      ->aggregations([
          'total' => ['COUNT'],
      ])
      ->having('total', '>', 5);
```

## Other Operators

### DISTINCT

```php
$query->distinct();
```

### SELECT Fields

```php
$query->select(['name', 'price']);
```

## Complete Examples

### Filter and Sort Products

```php
$result = DataMapper::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();
```

### Group and Aggregate

```php
$result = DataMapper::query()
    ->source('orders', $orders)
    ->groupBy('customerId')
    ->aggregations([
        'totalOrders' => ['COUNT'],
        'totalSpent' => ['SUM', '{{ orders.*.amount }}'],
        'avgOrder' => ['AVG', '{{ orders.*.amount }}'],
    ])
    ->having('totalOrders', '>', 5)
    ->get();
```

### Complex Filtering

```php
$result = DataMapper::query()
    ->source('users', $users)
    ->where(function($q) {
        $q->where('role', 'admin')
          ->orWhere('role', 'moderator');
    })
    ->where('active', true)
    ->whereNotNull('emailVerifiedAt')
    ->orderBy('createdAt', 'DESC')
    ->limit(50)
    ->get();
```

### With Pipeline

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\RemoveEmpty;

$result = DataMapper::query()
    ->source('products', $products)
    ->pipe([
        new TrimStrings(),
        new RemoveEmpty(),
    ])
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'ASC')
    ->get();
```

## Method Reference

### Query Building

- `source(string $name, mixed $data)` - Add data source
- `where(string|Closure $field, mixed $operator = null, mixed $value = null)` - Add WHERE condition
- `orWhere(string|Closure $field, mixed $operator = null, mixed $value = null)` - Add OR WHERE condition
- `between(string $field, mixed $min, mixed $max)` - Add BETWEEN condition
- `whereIn(string $field, array $values)` - Add IN condition
- `whereNotIn(string $field, array $values)` - Add NOT IN condition
- `whereNull(string $field)` - Add NULL check
- `whereNotNull(string $field)` - Add NOT NULL check
- `exists(string $field)` - Add EXISTS check
- `like(string $field, string $pattern)` - Add LIKE condition

### Sorting and Limiting

- `orderBy(string $field, string $direction = 'ASC')` - Add ORDER BY
- `limit(int $limit)` - Set LIMIT
- `offset(int $offset)` - Set OFFSET

### Grouping

- `groupBy(string|array $fields)` - Add GROUP BY
- `aggregations(array $aggregations)` - Add aggregations
- `having(string $field, string $operator, mixed $value)` - Add HAVING condition

### Other

- `distinct()` - Remove duplicates
- `select(array $fields)` - Select specific fields
- `pipe(array $filters)` - Add pipeline filters
- `get()` - Execute query and get results

## See Also

- [DataMapper](/main-classes/data-mapper/) - DataMapper guide
- [Template Expressions](/advanced/template-expressions/) - Template syntax
- [Pipelines](/advanced/pipelines/) - Pipeline processing

