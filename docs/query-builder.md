# Query Builder

The **DataMapperQuery** provides a Laravel-inspired fluent interface for building complex data mapping queries. It combines the power of the DataMapper with an intuitive, chainable API.

## Table of Contents

- [Quick Start](#quick-start)
- [Features](#features)
- [Basic Usage](#basic-usage)
- [Pipeline Integration](#pipeline-integration)
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
- ✅ **Pipeline Integration** - Combine with DataMapper pipelines for data transformation
- ✅ **WHERE with Comparison Operators** - `=`, `!=`, `<>`, `>`, `<`, `>=`, `<=`
- ✅ **Advanced WHERE Conditions** - `between()`, `whereIn()`, `whereNull()`, `exists()`, `like()`
- ✅ **Nested WHERE Conditions** - Use closures for complex AND/OR logic
- ✅ **OR WHERE Conditions** - Combine conditions with OR logic
- ✅ **ORDER BY, LIMIT, OFFSET** - Sort and paginate results
- ✅ **GROUP BY with Aggregations** - COUNT, SUM, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT
- ✅ **HAVING Clause** - Filter grouped results
- ✅ **DISTINCT** - Remove duplicates
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

## Pipeline Integration

You can combine the Query Builder with DataMapper pipelines for data transformation:

### Using pipe() Method

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

### Using pipeQuery() Factory

```php
// Create a query with pipeline in one step
$result = DataMapper::pipeQuery([
        new TrimStrings(),
        new LowercaseStrings(),
    ])
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->get();
```

The pipeline filters are applied during the mapping process, allowing you to transform data as it's being queried.

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

### BETWEEN

Check if a value is between two values (inclusive):

```php
// Price between 50 and 150
$query->between('price', 50, 150);

// Age between 18 and 65
$query->between('age', 18, 65);
```

### NOT BETWEEN

Check if a value is NOT between two values:

```php
// Price NOT between 50 and 150
$query->notBetween('price', 50, 150);
```

### WHERE IN

Check if a value is in an array of values:

```php
// Category is 'Electronics' OR 'Furniture'
$query->whereIn('category', ['Electronics', 'Furniture']);

// Status is 'active', 'pending', or 'approved'
$query->whereIn('status', ['active', 'pending', 'approved']);
```

### WHERE NOT IN

Check if a value is NOT in an array of values:

```php
// Category is NOT 'Electronics' OR 'Furniture'
$query->whereNotIn('category', ['Electronics', 'Furniture']);
```

### WHERE NULL / WHERE NOT NULL

Check if a field is null or not null:

```php
// Email is null
$query->whereNull('email');

// Email is NOT null
$query->whereNotNull('email');
```

### EXISTS / NOT EXISTS

Check if a field exists (alias for whereNotNull/whereNull):

```php
// Email field exists (not null)
$query->exists('email');

// Email field does NOT exist (is null)
$query->notExists('email');
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

### between(string $field, mixed $min, mixed $max): self

Check if a value is between two values (inclusive).

```php
$query->between('price', 50, 150);
```

### notBetween(string $field, mixed $min, mixed $max): self

Check if a value is NOT between two values.

```php
$query->notBetween('price', 50, 150);
```

### whereIn(string $field, array $values): self

Check if a value is in an array of values.

```php
$query->whereIn('category', ['Electronics', 'Furniture']);
```

### whereNotIn(string $field, array $values): self

Check if a value is NOT in an array of values.

```php
$query->whereNotIn('category', ['Electronics', 'Furniture']);
```

### whereNull(string $field): self

Check if a field is null.

```php
$query->whereNull('email');
```

### whereNotNull(string $field): self

Check if a field is NOT null.

```php
$query->whereNotNull('email');
```

### exists(string $field): self

Check if a field exists (alias for whereNotNull).

```php
$query->exists('email');
```

### notExists(string $field): self

Check if a field does NOT exist (alias for whereNull).

```php
$query->notExists('email');
```

### pipe(array $filters): self

Set pipeline filters for data transformation.

```php
$query->pipe([
    new TrimStrings(),
    new LowercaseStrings(),
]);
```

### get(): array

Execute the query and return results.

```php
$result = $query->get();
```

## Factory Methods

### DataMapper::query(): DataMapperQuery

Create a new query builder instance.

```php
$query = DataMapper::query();
```

### DataMapper::pipeQuery(array $filters): DataMapperQuery

Create a new query builder with pipeline filters.

```php
$query = DataMapper::pipeQuery([
    new TrimStrings(),
    new LowercaseStrings(),
]);
```

## See Also

- [Data Mapper](data-mapper.md) - Core mapping functionality
- [Data Mapper Pipeline](data-mapper-pipeline.md) - Pipeline documentation
- [Wildcard Operators](wildcard-operators.md) - All available operators
- [GROUP BY Operator](group-by-operator.md) - Detailed GROUP BY documentation
- [Template Expressions](template-expressions.md) - Expression syntax
- [Examples](../examples/18-query-builder.php) - Runnable code examples

