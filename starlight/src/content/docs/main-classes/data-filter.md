---
title: DataFilter
description: Fluent API for filtering and transforming data collections with SQL-like operators
---

DataFilter provides a fluent API for filtering and transforming existing data collections. It works with Arrays, Dtos, Eloquent Models, Collections, and any iterable data using SQL-like operators.

## Quick Example

```php
use event4u\DataHelpers\DataFilter;

$products = [
    ['id' => 1, 'name' => 'Laptop', 'price' => 1299, 'category' => 'Electronics', 'stock' => 5],
    ['id' => 2, 'name' => 'Mouse', 'price' => 29, 'category' => 'Electronics', 'stock' => 150],
    ['id' => 3, 'name' => 'Desk', 'price' => 349, 'category' => 'Furniture', 'stock' => 12],
    ['id' => 4, 'name' => 'Chair', 'price' => 299, 'category' => 'Furniture', 'stock' => 8],
    ['id' => 5, 'name' => 'Cable', 'price' => 12, 'category' => 'Electronics', 'stock' => 200],
    ['id' => 6, 'name' => 'Monitor', 'price' => 449, 'category' => 'Electronics', 'stock' => 0],
];

// Filter electronics with price between $100-$500, in stock, sorted by price
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->between('price', 100, 500)
    ->where('stock', '>', 0)
    ->orderBy('price', 'DESC')
    ->get();

// Result: [Monitor ($449), Laptop ($1299)]

// Get first result
$bestProduct = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->orderBy('price', 'DESC')
    ->first();

// Count results
$count = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('stock', '>', 0)
    ->count();
```

## Introduction

DataFilter provides SQL-like filtering for in-memory data collections.

### Key Features

- **WHERE Conditions** - Filter with comparison operators (=, !=, >, <, >=, <=)
- **AND/OR Logic** - Complex nested conditions
- **LIKE Patterns** - SQL-style pattern matching with % and _
- **BETWEEN/NOT BETWEEN** - Range filtering
- **WHERE IN/NOT IN** - Array membership checks
- **WHERE NULL/NOT NULL** - Null value filtering
- **ORDER BY** - Single and multi-field sorting
- **LIMIT/OFFSET** - Pagination support
- **DISTINCT** - Remove duplicates
- **Custom Operators** - Extensible via OperatorRegistry

### DataFilter vs DataMapper Query Builder

| Feature | DataFilter | DataMapper Query Builder |
|---------|-----------|--------------------------|
| **Purpose** | Filter existing data | Build templates with wildcard operators |
| **Input** | Arrays, Dtos, Models, Collections | Template structure |
| **Field Paths** | Simple strings (`'price'`, `'user.name'`) | Template expressions (`'{{ products.*.price }}'`) |
| **Use Case** | Post-mapping filtering | Pre-mapping query building |
| **Example** | `DataFilter::query($products)->where('price', '>', 100)->get()` | `DataMapper::query('products.*')->where('price', '>', 100)->end()` |

## Basic Usage

### Creating a Query

```php
use event4u\DataHelpers\DataFilter;

// Start with data
$data = [
    ['id' => 1, 'name' => 'Alice', 'age' => 30],
    ['id' => 2, 'name' => 'Bob', 'age' => 25],
    ['id' => 3, 'name' => 'Charlie', 'age' => 35],
];

// Create query
$query = DataFilter::query($data);
```

### Simple Filtering

```php
// Filter by single condition
$result = DataFilter::query($data)
    ->where('age', '>', 25)
    ->get();

// $result = [Alice (30), Charlie (35)]
```

### Multiple Conditions

```php
// Multiple WHERE conditions (AND logic)
$result = DataFilter::query($data)
    ->where('age', '>', 25)
    ->where('age', '<', 35)
    ->get();

// $result = [Alice (30)]
```

### Getting Results

```php
// Get all results
$results = DataFilter::query($data)->where('age', '>', 25)->get();

// Get first result
$first = DataFilter::query($data)->where('age', '>', 25)->first();

// Count results
$count = DataFilter::query($data)->where('age', '>', 25)->count();
```


## WHERE Conditions

Filter data using comparison operators.

### Comparison Operators

```php
$data = [
    ['id' => 1, 'price' => 100],
    ['id' => 2, 'price' => 200],
    ['id' => 3, 'price' => 300],
];

// Equal
$result = DataFilter::query($data)->where('price', '=', 200)->get();
// Result: [['id' => 2, 'price' => 200]]

// Not equal
$result = DataFilter::query($data)->where('price', '!=', 200)->get();
// Result: [['id' => 1, 'price' => 100], ['id' => 3, 'price' => 300]]

// Greater than
$result = DataFilter::query($data)->where('price', '>', 150)->get();
// Result: [['id' => 2, 'price' => 200], ['id' => 3, 'price' => 300]]

// Less than
$result = DataFilter::query($data)->where('price', '<', 250)->get();
// Result: [['id' => 1, 'price' => 100], ['id' => 2, 'price' => 200]]

// Greater than or equal
$result = DataFilter::query($data)->where('price', '>=', 200)->get();
// Result: [['id' => 2, 'price' => 200], ['id' => 3, 'price' => 300]]

// Less than or equal
$result = DataFilter::query($data)->where('price', '<=', 200)->get();
// Result: [['id' => 1, 'price' => 100], ['id' => 2, 'price' => 200]]
```

### Multiple Conditions (AND Logic)

```php
$products = [
    ['name' => 'Laptop', 'price' => 1299, 'category' => 'Electronics', 'stock' => 5],
    ['name' => 'Mouse', 'price' => 29, 'category' => 'Electronics', 'stock' => 150],
    ['name' => 'Desk', 'price' => 349, 'category' => 'Furniture', 'stock' => 12],
];

// Multiple conditions (AND)
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('price', '>', 100)
    ->where('stock', '>', 0)
    ->get();

// Result: [Laptop]
```

### Nested Field Access

```php
$users = [
    ['name' => 'Alice', 'profile' => ['age' => 30, 'city' => 'Berlin']],
    ['name' => 'Bob', 'profile' => ['age' => 25, 'city' => 'Munich']],
];

// Access nested fields with dot-notation
$result = DataFilter::query($users)
    ->where('profile.age', '>', 25)
    ->get();

// Result: [Alice]
```

## WHERE IN / NOT IN

Filter by array membership.

### WHERE IN

```php
$users = [
    ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
    ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
    ['id' => 3, 'name' => 'Charlie', 'role' => 'moderator'],
    ['id' => 4, 'name' => 'Diana', 'role' => 'user'],
];

// Filter by multiple values
$result = DataFilter::query($users)
    ->whereIn('role', ['admin', 'moderator'])
    ->get();

// Result: [Alice, Charlie]
```

### WHERE NOT IN

```php
// Exclude specific values
$result = DataFilter::query($users)
    ->whereNotIn('role', ['user'])
    ->get();

// $result = [Alice, Charlie]
```

## WHERE NULL / NOT NULL

Filter by null values.

### WHERE NULL

```php
$users = [
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['id' => 2, 'name' => 'Bob', 'email' => null],
    ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
];

// Find users without email
$result = DataFilter::query($users)
    ->whereNull('email')
    ->get();

// Result: [Bob]
```

### WHERE NOT NULL

```php
// Find users with email
$result = DataFilter::query($users)
    ->whereNotNull('email')
    ->get();

// $result = [Alice, Charlie]
```

## LIKE Pattern Matching

SQL-style pattern matching with wildcards.

### Pattern Wildcards

- `%` - Matches any number of characters (including zero)
- `_` - Matches exactly one character

### Basic Patterns

```php
$users = [
    ['id' => 1, 'name' => 'Alice Johnson'],
    ['id' => 2, 'name' => 'Bob Smith'],
    ['id' => 3, 'name' => 'Alice Brown'],
    ['id' => 4, 'name' => 'Charlie Anderson'],
];

// Starts with "Alice"
$result = DataFilter::query($users)
    ->like('name', 'Alice%')
    ->get();
// Result: [Alice Johnson, Alice Brown]

// Ends with "son"
$result = DataFilter::query($users)
    ->like('name', '%son')
    ->get();
// Result: [Alice Johnson, Charlie Anderson]

// Contains "Smith"
$result = DataFilter::query($users)
    ->like('name', '%Smith%')
    ->get();
// Result: [Bob Smith]
```

### Email Pattern Matching

```php
$users = [
    ['name' => 'Alice', 'email' => 'alice@gmail.com'],
    ['name' => 'Bob', 'email' => 'bob@company.com'],
    ['name' => 'Charlie', 'email' => 'charlie@gmail.com'],
];

// Find Gmail users
$result = DataFilter::query($users)
    ->like('email', '%@gmail.com')
    ->get();

// Result: [Alice, Charlie]
```


## BETWEEN / NOT BETWEEN

Filter by value ranges.

### BETWEEN (Inclusive)

```php
$products = [
    ['id' => 1, 'price' => 50],
    ['id' => 2, 'price' => 150],
    ['id' => 3, 'price' => 250],
    ['id' => 4, 'price' => 350],
];

// Price between 100 and 300 (inclusive)
$result = DataFilter::query($products)
    ->between('price', 100, 300)
    ->get();

// Result: [['id' => 2, 'price' => 150], ['id' => 3, 'price' => 250]]
```

### NOT BETWEEN

```php
// Price NOT between 100 and 300
$result = DataFilter::query($products)
    ->notBetween('price', [100, 300])
    ->get();

// $result = [['id' => 1, 'price' => 50], ['id' => 4, 'price' => 350]]
```

### Date Ranges

```php
$orders = [
    ['id' => 1, 'date' => '2024-01-05'],
    ['id' => 2, 'date' => '2024-01-15'],
    ['id' => 3, 'date' => '2024-01-25'],
];

// Orders in January 2024
$result = DataFilter::query($orders)
    ->between('date', '2024-01-01', '2024-01-31')
    ->get();
```

## ORDER BY

Sort results by one or multiple fields.

### Single Field Sorting

```php
$products = [
    ['id' => 1, 'name' => 'Laptop', 'price' => 1299],
    ['id' => 2, 'name' => 'Mouse', 'price' => 29],
    ['id' => 3, 'name' => 'Keyboard', 'price' => 89],
];

// Sort by price ascending
$result = DataFilter::query($products)
    ->orderBy('price', 'ASC')
    ->get();
// Result: [Mouse ($29), Keyboard ($89), Laptop ($1299)]

// Sort by price descending
$result = DataFilter::query($products)
    ->orderBy('price', 'DESC')
    ->get();
// Result: [Laptop ($1299), Keyboard ($89), Mouse ($29)]
```

### Multiple Field Sorting

```php
$products = [
    ['category' => 'Electronics', 'name' => 'Laptop', 'price' => 1299],
    ['category' => 'Electronics', 'name' => 'Mouse', 'price' => 29],
    ['category' => 'Furniture', 'name' => 'Desk', 'price' => 349],
    ['category' => 'Furniture', 'name' => 'Chair', 'price' => 299],
];

// Sort by category, then by price
$result = DataFilter::query($products)
    ->orderBy('category', 'ASC')
    ->orderBy('price', 'DESC')
    ->get();

// Result:
// Electronics: Laptop ($1299), Mouse ($29)
// Furniture: Desk ($349), Chair ($299)
```

### Nested Field Sorting

```php
$users = [
    ['name' => 'Alice', 'profile' => ['age' => 30]],
    ['name' => 'Bob', 'profile' => ['age' => 25]],
    ['name' => 'Charlie', 'profile' => ['age' => 35]],
];

// Sort by nested field
$result = DataFilter::query($users)
    ->orderBy('profile.age', 'DESC')
    ->get();

// Result: [Charlie (35), Alice (30), Bob (25)]
```

## LIMIT and OFFSET

Pagination support.

### LIMIT

```php
$products = [
    ['id' => 1, 'name' => 'Product 1'],
    ['id' => 2, 'name' => 'Product 2'],
    ['id' => 3, 'name' => 'Product 3'],
    ['id' => 4, 'name' => 'Product 4'],
    ['id' => 5, 'name' => 'Product 5'],
];

// Get first 3 products
$result = DataFilter::query($products)
    ->limit(3)
    ->get();

// Result: [Product 1, Product 2, Product 3]
```

### OFFSET

```php
// Skip first 2 products
$result = DataFilter::query($products)
    ->offset(2)
    ->get();

// $result = [Product 3, Product 4, Product 5]
```

### Pagination

```php
// Page 1 (items 1-3)
$page1 = DataFilter::query($products)
    ->offset(0)
    ->limit(3)
    ->get();

// Page 2 (items 4-6)
$page2 = DataFilter::query($products)
    ->offset(3)
    ->limit(3)
    ->get();

// Helper function for pagination
function paginate($data, $page, $perPage) {
    return DataFilter::query($data)
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->get();
}

$page1 = paginate($products, 1, 3); // Items 1-3
$page2 = paginate($products, 2, 3); // Items 4-6
```

## DISTINCT

Get unique values for a field.

### Basic DISTINCT

```php
$users = [
    ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
    ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
    ['id' => 3, 'name' => 'Charlie', 'role' => 'admin'],
    ['id' => 4, 'name' => 'Diana', 'role' => 'moderator'],
];

// Get unique roles
$roles = DataFilter::query($users)
    ->distinct('role')
    ->get();

// Result: ['admin', 'user', 'moderator']
```

### DISTINCT with Filtering

```php
$products = [
    ['name' => 'Laptop', 'category' => 'Electronics', 'price' => 1299],
    ['name' => 'Mouse', 'category' => 'Electronics', 'price' => 29],
    ['name' => 'Desk', 'category' => 'Furniture', 'price' => 349],
    ['name' => 'Monitor', 'category' => 'Electronics', 'price' => 449],
];

// Get unique categories for products over $100
$categories = DataFilter::query($products)
    ->where('price', '>', 100)
    ->distinct('category')
    ->get();

// Result: ['Electronics', 'Furniture']
```


## Result Methods

Execute queries and retrieve results.

### get() - Get All Results

```php
// Get all matching results
$results = DataFilter::query($data)
    ->where('status', '=', 'active')
    ->get();
// $results = array of matching items
```

### first() - Get First Result

```php
// Get first matching result
$first = DataFilter::query($data)
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->first();
// $first = single item or null if no match
```

### count() - Count Results

```php
// Count matching results
$count = DataFilter::query($data)
    ->where('status', '=', 'active')
    ->count();
// $count = integer count
```

## Real-World Examples

### E-Commerce Product Filtering

```php
$products = [
    ['id' => 1, 'name' => 'Laptop Pro 15"', 'price' => 1299, 'category' => 'Electronics', 'stock' => 5, 'rating' => 4.5],
    ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 29, 'category' => 'Electronics', 'stock' => 150, 'rating' => 4.2],
    ['id' => 3, 'name' => 'Office Desk', 'price' => 349, 'category' => 'Furniture', 'stock' => 12, 'rating' => 4.7],
    ['id' => 4, 'name' => 'Gaming Chair', 'price' => 299, 'category' => 'Furniture', 'stock' => 8, 'rating' => 4.3],
    ['id' => 5, 'name' => 'USB-C Cable', 'price' => 12, 'category' => 'Electronics', 'stock' => 200, 'rating' => 4.0],
    ['id' => 6, 'name' => 'Monitor 27"', 'price' => 449, 'category' => 'Electronics', 'stock' => 0, 'rating' => 4.6],
];

// Filter electronics with price between $100-$500, in stock, sorted by rating
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->between('price', 100, 500)
    ->where('stock', '>', 0)
    ->orderBy('rating', 'DESC')
    ->get();

// Result: [Monitor 27" (4.6), Laptop Pro 15" (4.5)]

// Get the best-rated product in a category
$bestChair = DataFilter::query($products)
    ->where('category', '=', 'Furniture')
    ->orderBy('rating', 'DESC')
    ->first();

// Result: Office Desk (rating: 4.7)

// Count products in stock by category
$electronicsCount = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('stock', '>', 0)
    ->count();

// Result: 3
```

### User Management

```php
$users = [
    ['id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'admin', 'status' => 'active', 'last_login' => '2024-01-15'],
    ['id' => 2, 'name' => 'Bob Smith', 'email' => 'bob@example.com', 'role' => 'user', 'status' => 'active', 'last_login' => '2024-01-10'],
    ['id' => 3, 'name' => 'Charlie Brown', 'email' => null, 'role' => 'user', 'status' => 'inactive', 'last_login' => '2023-12-01'],
    ['id' => 4, 'name' => 'Diana Prince', 'email' => 'diana@example.com', 'role' => 'moderator', 'status' => 'active', 'last_login' => '2024-01-14'],
    ['id' => 5, 'name' => 'Eve Anderson', 'email' => 'eve@example.com', 'role' => 'user', 'status' => 'active', 'last_login' => null],
];

// Find active users with specific roles
$activeStaff = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->whereIn('role', ['admin', 'moderator'])
    ->orderBy('name', 'ASC')
    ->get();

// Result: [Alice Johnson, Diana Prince]

// Find users without email addresses
$usersWithoutEmail = DataFilter::query($users)
    ->whereNull('email')
    ->get();

// Result: [Charlie Brown]

// Search users by name pattern
$usersWithA = DataFilter::query($users)
    ->like('name', 'A%')  // Names starting with 'A'
    ->get();

// Result: [Alice Johnson, Eve Anderson]

// Pagination: Get page 2 with 2 users per page
$page2 = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->orderBy('last_login', 'DESC')
    ->offset(2)  // Skip first 2
    ->limit(2)   // Take next 2
    ->get();

// Result: [Bob Smith, Eve Anderson]

// Get unique roles
$roles = DataFilter::query($users)
    ->distinct('role')
    ->get();

// Result: ['admin', 'user', 'moderator']
```

### Order Processing

```php
$orders = [
    ['id' => 1, 'customer' => 'Alice', 'total' => 299, 'status' => 'shipped', 'date' => '2024-01-10'],
    ['id' => 2, 'customer' => 'Bob', 'total' => 150, 'status' => 'pending', 'date' => '2024-01-12'],
    ['id' => 3, 'customer' => 'Charlie', 'total' => 450, 'status' => 'shipped', 'date' => '2024-01-11'],
    ['id' => 4, 'customer' => 'Diana', 'total' => 89, 'status' => 'cancelled', 'date' => '2024-01-09'],
    ['id' => 5, 'customer' => 'Eve', 'total' => 320, 'status' => 'shipped', 'date' => '2024-01-13'],
];

// Find high-value shipped orders
$highValueOrders = DataFilter::query($orders)
    ->where('status', '=', 'shipped')
    ->where('total', '>', 250)
    ->orderBy('total', 'DESC')
    ->get();

// Result: [Charlie ($450), Eve ($320), Alice ($299)]

// Calculate total revenue for shipped orders
$shippedOrders = DataFilter::query($orders)
    ->where('status', '=', 'shipped')
    ->get();

$totalRevenue = array_sum(array_column($shippedOrders, 'total'));
// Result: 1069

// Find orders in date range
$januaryOrders = DataFilter::query($orders)
    ->between('date', '2024-01-10', '2024-01-12')
    ->get();

// Result: [Alice, Bob, Charlie]
```

## Best Practices

### Chain Filters for Readability

```php
// ✅ Clear and readable
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('stock', '>', 0)
    ->between('price', 100, 500)
    ->orderBy('rating', 'DESC')
    ->limit(10)
    ->get();
```

### Use first() for Single Results

```php
// ✅ Efficient
$user = DataFilter::query($users)
    ->where('id', '=', 1)
    ->first();

// ❌ Inefficient
$users = DataFilter::query($users)
    ->where('id', '=', 1)
    ->get();
$user = $users[0] ?? null;
```

### Use count() for Counting

```php
// ✅ Efficient
$count = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->count();

// ❌ Inefficient
$users = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->get();
$count = count($users);
```

### Combine with DataMapper

```php
// Filter data, then transform with DataMapper
$activeUsers = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->get();

$result = DataMapper::source(['users' => $activeUsers])
    ->template([
        'users' => [
            '*' => [
                'name' => '{{ users.*.name }}',
                'email' => '{{ users.*.email }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

## Code Examples

The following working examples demonstrate DataFilter in action:

- [**Basic Usage**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/basic-usage.php) - WHERE, ORDER BY, LIMIT operations
- [**Wildcard WHERE**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/wildcard-where.php) - Filtering with wildcards
- [**Custom Wildcard Operators**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/custom-wildcard-operators.php) - Creating custom operators
- [**DISTINCT & LIKE**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/distinct-like-operators.php) - DISTINCT and LIKE operations
- [**GROUP BY & Aggregations**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/group-by-aggregations.php) - Grouping and aggregating data
- [**Query Builder**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/query-builder.php) - Fluent query builder API
- [**Callback Filters**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/callback-filters.php) - Custom filter callbacks
- [**Custom Operators**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/custom-operators.php) - Extending with custom operators
- [**Complex Queries**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-filter/complex-queries.php) - Advanced query scenarios

All examples are fully tested and can be run directly:

```bash
php examples/main-classes/data-filter/basic-usage.php
php examples/main-classes/data-filter/query-builder.php
```

## Related Tests

The functionality is thoroughly tested. Key test files:

- [DataFilterTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataFilterTest.php) - Core functionality tests
- [QueryBuilderTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataFilter/QueryBuilderTest.php) - Query builder tests
- [OperatorTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataFilter/OperatorTest.php) - Operator tests
- [AggregationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataFilter/AggregationTest.php) - Aggregation tests
- [DataFilterIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Integration/DataFilterIntegrationTest.php) - End-to-end scenarios

Run the tests:

```bash
# Run all DataFilter tests
task test:unit -- --filter=DataFilter

# Run specific test file
vendor/bin/pest tests/Unit/DataFilterTest.php
```
## See Also

- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataMutator](/main-classes/data-mutator/) - Modify nested data
- [DataMapper](/main-classes/data-mapper/) - Transform data structures
- [Core Concepts: Dot-Notation](/core-concepts/dot-notation/) - Path syntax
- [Examples](/examples/) - 90+ code examples
