# DataFilter

The **DataFilter** provides a fluent API for filtering and transforming existing data collections. It works with Arrays, DTOs, Eloquent Models, Collections, and any iterable data.

## Key Differences: DataFilter vs QueryBuilder

| Feature | DataFilter | QueryBuilder |
|---------|-----------|--------------|
| **Purpose** | Filter existing data | Build templates with wildcard operators |
| **Input** | Arrays, DTOs, Models, Collections | Template structure |
| **Field Paths** | Simple strings (\`'price'\`, \`'user.name'\`) | Template expressions (\`'{{ products.*.price }}'\`) |
| **Use Case** | Post-mapping filtering | Pre-mapping query building |
| **Example** | \`DataFilter::query($products)->where('price', '>', 100)->get()\` | \`DataMapper::query()->source('products', $products)->where('price', '>', 100)->get()\` |

## Features

- ✅ **WHERE Conditions** - Filter with comparison operators (=, !=, >, <, >=, <=)
- ✅ **AND/OR Logic** - Complex nested conditions
- ✅ **LIKE Patterns** - SQL-style pattern matching with % and _
- ✅ **BETWEEN/NOT BETWEEN** - Range filtering
- ✅ **WHERE IN/NOT IN** - Array membership checks
- ✅ **WHERE NULL/NOT NULL** - Null value filtering
- ✅ **ORDER BY** - Single and multi-field sorting
- ✅ **LIMIT/OFFSET** - Pagination support
- ✅ **DISTINCT** - Remove duplicates
- ✅ **Custom Operators** - Extensible via OperatorRegistry

## Example 1: E-Commerce Product Filtering

```php
use event4u\DataHelpers\DataFilter;

// Sample product data
$products = [
    ['id' => 1, 'name' => 'Laptop Pro 15"', 'price' => 1299.99, 'category' => 'Electronics', 'stock' => 5, 'rating' => 4.5],
    ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 29.99, 'category' => 'Electronics', 'stock' => 150, 'rating' => 4.2],
    ['id' => 3, 'name' => 'Office Desk', 'price' => 349.99, 'category' => 'Furniture', 'stock' => 12, 'rating' => 4.7],
    ['id' => 4, 'name' => 'Gaming Chair', 'price' => 299.99, 'category' => 'Furniture', 'stock' => 8, 'rating' => 4.3],
    ['id' => 5, 'name' => 'USB-C Cable', 'price' => 12.99, 'category' => 'Electronics', 'stock' => 200, 'rating' => 4.0],
    ['id' => 6, 'name' => 'Monitor 27"', 'price' => 449.99, 'category' => 'Electronics', 'stock' => 0, 'rating' => 4.6],
];

// Filter electronics with price between $100-$500, in stock, sorted by rating
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->between('price', [100, 500])
    ->where('stock', '>', 0)
    ->orderBy('rating', 'DESC')
    ->get();

// Result: [Monitor 27", Laptop Pro 15"]
print_r($result);

// Get the best-rated product in a category
$bestChair = DataFilter::query($products)
    ->where('category', '=', 'Furniture')
    ->orderBy('rating', 'DESC')
    ->first();

echo $bestChair['name']; // "Office Desk" (rating: 4.7)

// Count products in stock by category
$electronicsCount = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('stock', '>', 0)
    ->count();

echo $electronicsCount; // 3
```

## Example 2: User Management with Complex Filters

```php
use event4u\DataHelpers\DataFilter;

// Sample user data
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
print_r($activeStaff);

// Find users without email addresses
$usersWithoutEmail = DataFilter::query($users)
    ->whereNull('email')
    ->get();

// Result: [Charlie Brown]
print_r($usersWithoutEmail);

// Search users by name pattern
$usersWithA = DataFilter::query($users)
    ->like('name', 'A%')  // Names starting with 'A'
    ->get();

// Result: [Alice Johnson, Eve Anderson]
print_r($usersWithA);

// Pagination: Get page 2 with 2 users per page
$page2 = DataFilter::query($users)
    ->where('status', '=', 'active')
    ->orderBy('last_login', 'DESC')
    ->offset(2)  // Skip first 2
    ->limit(2)   // Take next 2
    ->get();

// Result: [Bob Smith, Eve Anderson]
print_r($page2);

// Get unique roles
$roles = DataFilter::query($users)
    ->distinct('role')
    ->get();

// Result: ['admin', 'user', 'moderator']
print_r($roles);
```

## API Reference

### Query Methods

#### `where(string $field, string $operator, mixed $value): self`
Filter by field with comparison operator.

**Operators:** `=`, `!=`, `>`, `<`, `>=`, `<=`

```php
DataFilter::query($data)->where('price', '>', 100)->get();
```

#### `whereIn(string $field, array $values): self`
Filter by field matching any value in array.

```php
DataFilter::query($data)->whereIn('category', ['Electronics', 'Furniture'])->get();
```

#### `whereNull(string $field): self`
Filter by null values.

```php
DataFilter::query($data)->whereNull('email')->get();
```

#### `whereNotNull(string $field): self`
Filter by non-null values.

```php
DataFilter::query($data)->whereNotNull('email')->get();
```

#### `like(string $field, string $pattern): self`
Filter by SQL-style pattern matching.

**Wildcards:** `%` (any characters), `_` (single character)

```php
DataFilter::query($data)->like('name', 'John%')->get();  // Starts with "John"
DataFilter::query($data)->like('email', '%@gmail.com')->get();  // Ends with "@gmail.com"
```

#### `between(string $field, array $range): self`
Filter by value range (inclusive).

```php
DataFilter::query($data)->between('price', [100, 500])->get();
```

#### `orderBy(string $field, string $direction = 'ASC'): self`
Sort results by field.

**Directions:** `ASC`, `DESC`

```php
DataFilter::query($data)->orderBy('price', 'DESC')->get();
```

#### `limit(int $limit): self`
Limit number of results.

```php
DataFilter::query($data)->limit(10)->get();
```

#### `offset(int $offset): self`
Skip first N results.

```php
DataFilter::query($data)->offset(20)->limit(10)->get();  // Page 3 (20-30)
```

#### `distinct(string $field): self`
Get unique values for a field.

```php
DataFilter::query($data)->distinct('category')->get();
```

### Result Methods

#### `get(): array`
Execute query and return all results.

```php
$results = DataFilter::query($data)->where('status', '=', 'active')->get();
```

#### `first(): ?array`
Execute query and return first result or null.

```php
$user = DataFilter::query($data)->where('id', '=', 1)->first();
```

#### `count(): int`
Execute query and return count of results.

```php
$total = DataFilter::query($data)->where('status', '=', 'active')->count();
```

### Custom Operators

#### `addOperator(string $name, mixed $config): self`
Add a custom operator to the query.

```php
DataFilter::query($data)
    ->addOperator('MY_CUSTOM_OP', ['field' => 'price', 'value' => 100])
    ->get();
```

See [Custom Operators Example](../examples/21-custom-operators.php) for details.

## Next Steps

- See [Custom Operators](../examples/21-custom-operators.php) for creating custom operators
- See [Query Builder](query-builder.md) for template-based queries
- See [Wildcard Operators](wildcard-operators.md) for advanced wildcard usage
