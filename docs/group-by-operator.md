# GROUP BY Operator

The GROUP BY operator groups items by one or more fields and applies aggregation functions to calculate statistics for each group. It works similarly to SQL's GROUP BY clause and supports HAVING filters.

**Namespace:** `event4u\DataHelpers\DataMapper\Support\WildcardOperators\GroupByOperator`

## Table of Contents

- [Overview](#overview)
- [Basic Syntax](#basic-syntax)
- [Aggregation Functions](#aggregation-functions)
- [HAVING Clause](#having-clause)
- [Multi-Field Grouping](#multi-field-grouping)
- [Complete Examples](#complete-examples)
- [How It Works](#how-it-works)
- [Best Practices](#best-practices)

## Overview

The GROUP BY operator enables you to:

- **Group items** by one or more fields
- **Calculate aggregations** (COUNT, SUM, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT)
- **Filter groups** using HAVING conditions
- **Access aggregated values** in template expressions

**Key Features:**
- ✅ 9 built-in aggregation functions
- ✅ Single or multiple field grouping
- ✅ HAVING clause with all comparison operators
- ✅ Aggregated values available in template expressions
- ✅ Works with all other wildcard operators

## Basic Syntax

### Single Field Grouping

```php
$template = [
    'result' => [
        'GROUP BY' => [
            'field' => '{{ source.*.grouping_field }}',
            'aggregations' => [
                'agg_name' => ['FUNCTION', '{{ source.*.field_to_aggregate }}'],
            ],
        ],
        '*' => [
            'group_field' => '{{ source.*.grouping_field }}',
            'aggregated_value' => '{{ source.*.agg_name }}',
        ],
    ],
];
```

### Configuration Structure

```php
'GROUP BY' => [
    // Required: Field(s) to group by
    'field' => '{{ source.*.field }}',              // Single field
    // OR
    'fields' => [                                    // Multiple fields
        '{{ source.*.field1 }}',
        '{{ source.*.field2 }}',
    ],

    // Optional: Aggregations to calculate
    'aggregations' => [
        'result_name' => ['FUNCTION', '{{ source.*.field }}', ...args],
    ],

    // Optional: Filter groups after aggregation
    'HAVING' => [
        'aggregation_name' => ['operator', value],
    ],
]
```

## Aggregation Functions

### COUNT

Counts the number of items in each group.

```php
'aggregations' => [
    'total_count' => ['COUNT'],
]
```

**Parameters:** None
**Returns:** `int` - Number of items in the group

**Example:**
```php
// Input: 5 Electronics, 3 Furniture
// Output: ['Electronics' => 5, 'Furniture' => 3]
```

### SUM

Sums numeric values across all items in the group.

```php
'aggregations' => [
    'total_price' => ['SUM', '{{ sales.*.price }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to sum

**Returns:** `int|float` - Sum of all values

**Example:**
```php
// Input: prices [100, 200, 300]
// Output: 600
```

### AVG

Calculates the average of numeric values.

```php
'aggregations' => [
    'avg_price' => ['AVG', '{{ sales.*.price }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to average

**Returns:** `float` - Average value

**Example:**
```php
// Input: prices [100, 200, 300]
// Output: 200.0
```

### MIN

Finds the minimum value in the group.

```php
'aggregations' => [
    'min_price' => ['MIN', '{{ sales.*.price }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to find minimum

**Returns:** `mixed` - Minimum value

**Example:**
```php
// Input: prices [100, 200, 300]
// Output: 100
```

### MAX

Finds the maximum value in the group.

```php
'aggregations' => [
    'max_price' => ['MAX', '{{ sales.*.price }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to find maximum

**Returns:** `mixed` - Maximum value

**Example:**
```php
// Input: prices [100, 200, 300]
// Output: 300
```

### FIRST

Returns the first value in the group.

```php
'aggregations' => [
    'first_product' => ['FIRST', '{{ sales.*.product }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to get first value

**Returns:** `mixed` - First value in the group

**Example:**
```php
// Input: products ['Laptop', 'Mouse', 'Keyboard']
// Output: 'Laptop'
```

### LAST

Returns the last value in the group.

```php
'aggregations' => [
    'last_product' => ['LAST', '{{ sales.*.product }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to get last value

**Returns:** `mixed` - Last value in the group

**Example:**
```php
// Input: products ['Laptop', 'Mouse', 'Keyboard']
// Output: 'Keyboard'
```

### COLLECT

Collects all values into an array.

```php
'aggregations' => [
    'all_products' => ['COLLECT', '{{ sales.*.product }}'],
]
```

**Parameters:**
- `field` - Template expression for the field to collect

**Returns:** `array` - Array of all values

**Example:**
```php
// Input: products ['Laptop', 'Mouse', 'Keyboard']
// Output: ['Laptop', 'Mouse', 'Keyboard']
```

### CONCAT

Concatenates all values into a string with a separator.

```php
'aggregations' => [
    'product_list' => ['CONCAT', '{{ sales.*.product }}', ', '],
]
```

**Parameters:**
- `field` - Template expression for the field to concatenate
- `separator` (optional) - String separator (default: `', '`)

**Returns:** `string` - Concatenated string

**Example:**
```php
// Input: products ['Laptop', 'Mouse', 'Keyboard']
// Output: 'Laptop, Mouse, Keyboard'
```

## HAVING Clause

Filter groups after aggregation using HAVING conditions. All conditions must match (AND logic).

### Supported Operators

- `=` - Equal
- `!=` or `<>` - Not equal
- `>` - Greater than
- `>=` - Greater than or equal
- `<` - Less than
- `<=` - Less than or equal

### Syntax

```php
'HAVING' => [
    'aggregation_name' => ['operator', value],
    'another_agg' => ['operator', value],  // AND logic
]
```

### Examples

**Single Condition:**
```php
'HAVING' => [
    'product_count' => ['>', 3],  // Only groups with more than 3 products
]
```

**Multiple Conditions (AND):**
```php
'HAVING' => [
    'product_count' => ['>=', 3],      // At least 3 products
    'total_revenue' => ['>', 1000],    // AND revenue > 1000
]
```

**Shorthand (equality):**
```php
'HAVING' => [
    'category' => 'Electronics',  // Same as ['=', 'Electronics']
]
```

## Multi-Field Grouping

Group by multiple fields to create nested groupings.

```php
'GROUP BY' => [
    'fields' => [
        '{{ sales.*.category }}',
        '{{ sales.*.region }}',
    ],
    'aggregations' => [
        'count' => ['COUNT'],
        'revenue' => ['SUM', '{{ sales.*.price }}'],
    ],
]
```

**Result:** Each unique combination of category and region becomes a separate group.

**Example:**
```php
// Input:
// - Electronics, North: 2 items
// - Electronics, South: 1 item
// - Furniture, North: 1 item

// Output: 3 groups
[
    ['category' => 'Electronics', 'region' => 'North', 'count' => 2],
    ['category' => 'Electronics', 'region' => 'South', 'count' => 1],
    ['category' => 'Furniture', 'region' => 'North', 'count' => 1],
]
```

## Complete Examples

See [`examples/17-group-by-aggregations.php`](../examples/17-group-by-aggregations.php) for 10 comprehensive examples.

### Example 1: Basic COUNT

Count products by category.

```php
$sources = [
    'sales' => [
        ['product' => 'Laptop', 'category' => 'Electronics', 'price' => 1200],
        ['product' => 'Mouse', 'category' => 'Electronics', 'price' => 25],
        ['product' => 'Desk', 'category' => 'Furniture', 'price' => 300],
        ['product' => 'Chair', 'category' => 'Furniture', 'price' => 800],
    ],
];

$template = [
    'categories' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_count' => ['COUNT'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'count' => '{{ sales.*.product_count }}',
        ],
    ],
];

$result = $mapper->mapFromTemplate($template, $sources);
```

**Output:**
```php
[
    'categories' => [
        ['category' => 'Electronics', 'count' => 2],
        ['category' => 'Furniture', 'count' => 2],
    ],
]
```

### Example 2: SUM and AVG

Calculate total revenue and average price per category.

```php
$template = [
    'category_stats' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
                'avg_price' => ['AVG', '{{ sales.*.price }}'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'revenue' => '{{ sales.*.total_revenue }}',
            'average' => '{{ sales.*.avg_price }}',
        ],
    ],
];
```

**Output:**
```php
[
    'category_stats' => [
        ['category' => 'Electronics', 'revenue' => 1225, 'average' => 612.5],
        ['category' => 'Furniture', 'revenue' => 1100, 'average' => 550.0],
    ],
]
```

### Example 3: HAVING Filter

Only show categories with more than 3 products and revenue > 1000.

```php
$template = [
    'high_value_categories' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_count' => ['COUNT'],
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
            ],
            'HAVING' => [
                'product_count' => ['>', 3],
                'total_revenue' => ['>', 1000],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'count' => '{{ sales.*.product_count }}',
            'revenue' => '{{ sales.*.total_revenue }}',
        ],
    ],
];
```

**Output:** Only categories matching both conditions.

### Example 4: COLLECT and CONCAT

Collect all product names and concatenate them.

```php
$template = [
    'category_products' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'all_products' => ['COLLECT', '{{ sales.*.product }}'],
                'product_list' => ['CONCAT', '{{ sales.*.product }}', ', '],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'products_array' => '{{ sales.*.all_products }}',
            'products_string' => '{{ sales.*.product_list }}',
        ],
    ],
];
```

**Output:**
```php
[
    'category_products' => [
        [
            'category' => 'Electronics',
            'products_array' => ['Laptop', 'Mouse'],
            'products_string' => 'Laptop, Mouse',
        ],
        [
            'category' => 'Furniture',
            'products_array' => ['Desk', 'Chair'],
            'products_string' => 'Desk, Chair',
        ],
    ],
]
```

### Example 5: Multi-Field Grouping

Group by category AND region.

```php
$sources = [
    'sales' => [
        ['category' => 'Electronics', 'region' => 'North', 'price' => 1200],
        ['category' => 'Electronics', 'region' => 'South', 'price' => 25],
        ['category' => 'Electronics', 'region' => 'North', 'price' => 400],
        ['category' => 'Furniture', 'region' => 'East', 'price' => 300],
    ],
];

$template = [
    'stats' => [
        'GROUP BY' => [
            'fields' => [
                '{{ sales.*.category }}',
                '{{ sales.*.region }}',
            ],
            'aggregations' => [
                'count' => ['COUNT'],
                'revenue' => ['SUM', '{{ sales.*.price }}'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'region' => '{{ sales.*.region }}',
            'count' => '{{ sales.*.count }}',
            'revenue' => '{{ sales.*.revenue }}',
        ],
    ],
];
```

**Output:**
```php
[
    'stats' => [
        ['category' => 'Electronics', 'region' => 'North', 'count' => 2, 'revenue' => 1600],
        ['category' => 'Electronics', 'region' => 'South', 'count' => 1, 'revenue' => 25],
        ['category' => 'Furniture', 'region' => 'East', 'count' => 1, 'revenue' => 300],
    ],
]
```

## How It Works

### Execution Flow

1. **Grouping Phase**
   - Items are grouped by the specified field(s)
   - Each unique value (or combination) creates a separate group
   - Original item data is preserved in each group

2. **Aggregation Phase**
   - For each group, aggregation functions are calculated
   - Results are stored as new fields in the first item of each group
   - Aggregated values become available for template expressions

3. **HAVING Phase** (if specified)
   - Groups are filtered based on aggregated values
   - Only groups matching all HAVING conditions are kept

4. **Template Mapping Phase**
   - Each group's first item (with aggregations) is mapped through the template
   - Aggregated values are accessible via `{{ source.*.aggregation_name }}`

### Data Flow Example

**Input:**
```php
[
    ['category' => 'A', 'price' => 100],
    ['category' => 'A', 'price' => 200],
    ['category' => 'B', 'price' => 300],
]
```

**After Grouping:**
```php
[
    'A' => [
        ['category' => 'A', 'price' => 100],
        ['category' => 'A', 'price' => 200],
    ],
    'B' => [
        ['category' => 'B', 'price' => 300],
    ],
]
```

**After Aggregation (SUM price):**
```php
[
    ['category' => 'A', 'price' => 100, 'total_price' => 300],
    ['category' => 'B', 'price' => 300, 'total_price' => 300],
]
```

**After Template Mapping:**
```php
[
    ['category' => 'A', 'total' => 300],
    ['category' => 'B', 'total' => 300],
]
```

## Best Practices

### 1. Choose Meaningful Aggregation Names

Use descriptive names that clearly indicate what the aggregation represents.

**Good:**
```php
'aggregations' => [
    'total_revenue' => ['SUM', '{{ sales.*.price }}'],
    'avg_order_value' => ['AVG', '{{ sales.*.price }}'],
    'product_count' => ['COUNT'],
]
```

**Avoid:**
```php
'aggregations' => [
    'sum1' => ['SUM', '{{ sales.*.price }}'],
    'avg1' => ['AVG', '{{ sales.*.price }}'],
    'cnt' => ['COUNT'],
]
```

### 2. Use HAVING for Post-Aggregation Filtering

Use WHERE for filtering before grouping, HAVING for filtering after aggregation.

```php
// Filter items BEFORE grouping (use WHERE operator)
'WHERE' => [
    'price' => ['>', 100],  // Only expensive items
],
'GROUP BY' => [
    'field' => '{{ sales.*.category }}',
    'aggregations' => [
        'count' => ['COUNT'],
    ],
    // Filter groups AFTER aggregation
    'HAVING' => [
        'count' => ['>', 5],  // Only categories with > 5 expensive items
    ],
],
```

### 3. Combine Multiple Aggregations

Calculate multiple statistics in one pass for efficiency.

```php
'aggregations' => [
    'count' => ['COUNT'],
    'total' => ['SUM', '{{ sales.*.price }}'],
    'average' => ['AVG', '{{ sales.*.price }}'],
    'min' => ['MIN', '{{ sales.*.price }}'],
    'max' => ['MAX', '{{ sales.*.price }}'],
]
```

### 4. Use COLLECT for Detailed Analysis

When you need to preserve all values for further processing.

```php
'aggregations' => [
    'all_prices' => ['COLLECT', '{{ sales.*.price }}'],
    'all_products' => ['COLLECT', '{{ sales.*.product }}'],
]
```

### 5. Multi-Field Grouping for Nested Analysis

Group by multiple dimensions for detailed breakdowns.

```php
'GROUP BY' => [
    'fields' => [
        '{{ sales.*.category }}',
        '{{ sales.*.region }}',
        '{{ sales.*.quarter }}',
    ],
]
```

### 6. Performance Considerations

- **Large Datasets:** GROUP BY processes all items in memory. For very large datasets (>100k items), consider pre-aggregating data.
- **Multiple Aggregations:** Calculating multiple aggregations in one GROUP BY is more efficient than multiple separate GROUP BY operations.
- **HAVING vs WHERE:** Use WHERE to reduce items before grouping for better performance.

### 7. Error Handling

Always validate that aggregation fields exist in your data.

```php
// Good: Check if field exists
if (isset($item['price'])) {
    'aggregations' => [
        'total' => ['SUM', '{{ sales.*.price }}'],
    ],
}
```

## Common Use Cases

### Sales Analytics

```php
'GROUP BY' => [
    'field' => '{{ sales.*.product_category }}',
    'aggregations' => [
        'total_sales' => ['SUM', '{{ sales.*.amount }}'],
        'order_count' => ['COUNT'],
        'avg_order_value' => ['AVG', '{{ sales.*.amount }}'],
    ],
    'HAVING' => [
        'total_sales' => ['>', 10000],
    ],
]
```

### User Activity Reports

```php
'GROUP BY' => [
    'field' => '{{ events.*.user_id }}',
    'aggregations' => [
        'event_count' => ['COUNT'],
        'first_event' => ['FIRST', '{{ events.*.event_type }}'],
        'last_event' => ['LAST', '{{ events.*.event_type }}'],
        'all_events' => ['COLLECT', '{{ events.*.event_type }}'],
    ],
]
```

### Inventory Management

```php
'GROUP BY' => [
    'fields' => [
        '{{ products.*.warehouse }}',
        '{{ products.*.category }}',
    ],
    'aggregations' => [
        'total_quantity' => ['SUM', '{{ products.*.quantity }}'],
        'product_count' => ['COUNT'],
        'min_stock' => ['MIN', '{{ products.*.quantity }}'],
    ],
    'HAVING' => [
        'min_stock' => ['<', 10],  // Low stock alert
    ],
]
```

## See Also

- [Wildcard Operators](./data-mapper.md#wildcard-operators) - Overview of all wildcard operators
- [WHERE Operator](./data-mapper.md#where-operator) - Filter items before grouping
- [ORDER BY Operator](./data-mapper.md#order-by-operator) - Sort grouped results
- [Template Expressions](./template-expressions.md) - Template syntax reference
- [Examples](../examples/17-group-by-aggregations.php) - 10 comprehensive examples

