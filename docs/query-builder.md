# Query Builder

The **DataMapperQuery** provides a Laravel-inspired fluent interface for building complex data mapping queries. It combines the power of the DataMapper with an intuitive, chainable API.

## Table of Contents

- [Quick Start](#quick-start)
- [Features](#features)
- [Basic Usage](#basic-usage)
- [WHERE Conditions](#where-conditions)
- [Comparison Operators](#comparison-operators)
- [Nested Conditions](#nested-conditions)
- [OR Conditions](#or-conditions)
- [Sorting](#sorting)
- [Limiting Results](#limiting-results)
- [Grouping and Aggregations](#grouping-and-aggregations)
- [Other Operators](#other-operators)
- [Operator Execution Order](#operator-execution-order)
- [Method Reference](#method-reference)

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

## Features

- ✅ **Laravel-style Fluent Interface** - Chainable methods for intuitive query building
- ✅ **Method Chaining in Any Order** - Call methods in whatever order makes sense
- ✅ **WHERE with Comparison Operators** - `=`, `!=`, `<>`, `>`, `<`, `>=`, `<=`
- ✅ **Nested WHERE Conditions** - Use closures for complex AND/OR logic
- ✅ **OR WHERE Conditions** - Combine conditions with OR logic
- ✅ **ORDER BY, LIMIT, OFFSET** - Sort and paginate results
- ✅ **GROUP BY with Aggregations** - COUNT, SUM, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT
- ✅ **HAVING Clause** - Filter grouped results
- ✅ **DISTINCT and LIKE** - Remove duplicates and pattern matching
- ✅ **Operator Execution Order** - Operators are applied in the order they are called

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

## WHERE Conditions

### Simple Equality

```php
// Two-argument form (defaults to =)
$query->where('category', 'Electronics');

// Three-argument form (explicit operator)
$query->where('category', '=', 'Electronics');
```

### Multiple Conditions (AND Logic)

```php
$query->where('category', 'Electronics')
      ->where('price', '>', 100)
      ->where('stock', '>', 0);
```

## Comparison Operators

All standard comparison operators are supported:

```php
// Equality
$query->where('status', '=', 'active');
$query->where('status', 'active');  // Shorthand

// Inequality
$query->where('status', '!=', 'deleted');
$query->where('status', '<>', 'deleted');  // Alternative syntax

// Greater than
$query->where('price', '>', 100);

// Greater than or equal
$query->where('price', '>=', 100);

// Less than
$query->where('price', '<', 1000);

// Less than or equal
$query->where('price', '<=', 1000);
```

## Nested Conditions

Use closures to group conditions with AND logic:

```php
$query->where(function ($q) {
    $q->where('category', 'Electronics')
      ->where('price', '>', 50)
      ->where('rating', '>=', 4.5);
});
```

## OR Conditions

### Simple OR

```php
$query->where('category', 'Furniture')
      ->orWhere('price', '<', 50);
```

### Nested OR with Closure

```php
$query->orWhere(function ($q) {
    $q->where('category', 'Electronics')
      ->where('price', '<', 100);
});
```

## Sorting

### ORDER BY

```php
// Ascending (default)
$query->orderBy('price');

// Descending
$query->orderBy('price', 'DESC');

// Multiple fields
$query->orderBy('category', 'ASC')
      ->orderBy('price', 'DESC');
```

## Limiting Results

### LIMIT

```php
$query->limit(10);
```

### OFFSET

```php
$query->offset(20);
```

### Pagination

```php
// Page 3, 10 items per page
$query->offset(20)
      ->limit(10);
```

## Grouping and Aggregations

### GROUP BY

```php
$query->groupBy('category');
```

### With Aggregations

```php
$query->groupBy('category', [
    'total_products' => ['COUNT'],
    'avg_price' => ['AVG', 'price'],
    'total_stock' => ['SUM', 'stock'],
    'min_price' => ['MIN', 'price'],
    'max_price' => ['MAX', 'price'],
]);
```

### Available Aggregation Functions

- `COUNT` - Count items in group
- `SUM` - Sum of field values
- `AVG` - Average of field values
- `MIN` - Minimum field value
- `MAX` - Maximum field value
- `FIRST` - First field value
- `LAST` - Last field value
- `COLLECT` - Collect all field values into array
- `CONCAT` - Concatenate field values with separator

### HAVING Clause

Filter grouped results:

```php
$query->groupBy('category', [
    'total_products' => ['COUNT'],
    'avg_price' => ['AVG', 'price'],
])
->having('total_products', '>', 5)
->having('avg_price', '>=', 100);
```

## Other Operators

### DISTINCT

Remove duplicate values:

```php
$query->distinct('category');
```

### LIKE

Pattern matching with SQL-style wildcards:

```php
// Starts with 'Lap'
$query->like('name', 'Lap%');

// Ends with 'top'
$query->like('name', '%top');

// Contains 'apt'
$query->like('name', '%apt%');
```

## Operator Execution Order

**Important:** Operators are applied in the order they are called, not in a fixed order!

```php
// Query A: LIMIT first, then WHERE
$resultA = DataMapper::query()
    ->source('products', $products)
    ->limit(4)  // Limits to first 4 products
    ->where('category', 'Electronics')  // Then filters
    ->get();

// Query B: WHERE first, then LIMIT
$resultB = DataMapper::query()
    ->source('products', $products)
    ->where('category', 'Electronics')  // Filters first
    ->limit(4)  // Then limits to 4 results
    ->get();

// Results will be different!
```

This allows you to control the exact execution flow of your query.

## Method Reference

### source(string $name, mixed $data): self

Add a named data source.

```php
$query->source('products', $products);
```

### where(string|Closure $field, mixed $operator = null, mixed $value = null): self

Add a WHERE condition.

```php
$query->where('price', '>', 100);
$query->where('category', 'Electronics');
$query->where(function ($q) { /* nested */ });
```

### orWhere(string|Closure $field, mixed $operator = null, mixed $value = null): self

Add an OR WHERE condition.

```php
$query->orWhere('category', 'Furniture');
```

### orderBy(string $field, string $direction = 'ASC'): self

Add an ORDER BY clause.

```php
$query->orderBy('price', 'DESC');
```

### limit(int $limit): self

Set LIMIT.

```php
$query->limit(10);
```

### offset(int $offset): self

Set OFFSET.

```php
$query->offset(20);
```

### groupBy(string|array $fields, ?array $aggregations = null): self

Set GROUP BY with optional aggregations.

```php
$query->groupBy('category', [
    'count' => ['COUNT'],
    'avg_price' => ['AVG', 'price'],
]);
```

### having(string $field, string $operator, mixed $value): self

Add a HAVING condition.

```php
$query->having('count', '>', 5);
```

### distinct(string $field): self

Set DISTINCT field.

```php
$query->distinct('category');
```

### like(string $field, string $pattern): self

Add a LIKE pattern.

```php
$query->like('name', '%Laptop%');
```

### get(): array

Execute the query and return results.

```php
$result = $query->get();
```

## See Also

- [Data Mapper](data-mapper.md) - Core mapping functionality
- [Wildcard Operators](wildcard-operators.md) - All available operators
- [GROUP BY Operator](group-by-operator.md) - Detailed GROUP BY documentation
- [Template Expressions](template-expressions.md) - Expression syntax
- [Examples](../examples/18-query-builder.php) - Runnable code examples

