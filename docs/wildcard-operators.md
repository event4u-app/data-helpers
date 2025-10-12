# Wildcard Operators

Wildcard operators allow you to filter, sort, limit, group, and transform arrays in template mappings. They work with wildcard paths (e.g., `{{ products.* }}`) to process multiple items at once.

**Namespace:** `event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry`

## Table of Contents

- [Overview](#overview)
- [Built-in Operators](#built-in-operators)
  - [WHERE - Filter Items](#where---filter-items)
  - [ORDER BY - Sort Items](#order-by---sort-items)
  - [LIMIT - Limit Results](#limit---limit-results)
  - [OFFSET - Skip Items](#offset---skip-items)
  - [DISTINCT - Remove Duplicates](#distinct---remove-duplicates)
  - [LIKE - Pattern Matching](#like---pattern-matching)
  - [GROUP BY - Group and Aggregate](#group-by---group-and-aggregate)
- [Combining Operators](#combining-operators)
- [Custom Operators](#custom-operators)
- [Execution Order](#execution-order)

## Overview

Wildcard operators are applied to wildcard arrays before mapping individual items. They enable SQL-like operations on arrays:

```php
$template = [
    'filtered_products' => [
        'WHERE' => [
            '{{ products.*.category }}' => 'Electronics',
            '{{ products.*.price }}' => ['>', 100],
        ],
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        'LIMIT' => 10,
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];
```

**Key Features:**
- ✅ SQL-like syntax for array operations
- ✅ Chainable operators (WHERE → ORDER BY → LIMIT)
- ✅ Works with template expressions
- ✅ Extensible with custom operators
- ✅ Type-safe and well-tested

## Built-in Operators

### WHERE - Filter Items

Filter items based on conditions with AND/OR logic.

**Syntax:**
```php
'WHERE' => [
    '{{ path.*.field }}' => value,                    // Equality
    '{{ path.*.field }}' => ['operator', value],      // Comparison
    'OR' => [                                          // OR logic
        '{{ path.*.field1 }}' => value1,
        '{{ path.*.field2 }}' => value2,
    ],
]
```

**Supported Operators:**
- `=`, `!=`, `<>` - Equality/inequality
- `>`, `>=`, `<`, `<=` - Comparison
- `IN`, `NOT IN` - Array membership
- `LIKE`, `NOT LIKE` - Pattern matching (SQL-style with `%`)

**Examples:**
```php
// Simple equality
'WHERE' => [
    '{{ products.*.category }}' => 'Electronics',
]

// Comparison operators
'WHERE' => [
    '{{ products.*.price }}' => ['>', 100],
    '{{ products.*.stock }}' => ['>=', 10],
]

// IN operator
'WHERE' => [
    '{{ products.*.category }}' => ['IN', ['Electronics', 'Computers']],
]

// OR logic
'WHERE' => [
    'OR' => [
        '{{ products.*.category }}' => 'Electronics',
        '{{ products.*.price }}' => ['<', 50],
    ],
]

// Combined AND/OR
'WHERE' => [
    '{{ products.*.stock }}' => ['>', 0],  // AND
    'OR' => [
        '{{ products.*.category }}' => 'Electronics',
        '{{ products.*.featured }}' => true,
    ],
]
```

**See:** [Example 13](../examples/13-wildcard-where-clause.php)

### ORDER BY - Sort Items

Sort items by one or more fields.

**Syntax:**
```php
'ORDER BY' => [
    '{{ path.*.field }}' => 'ASC|DESC',
]

// Shorthand (single field)
'ORDER BY' => '{{ path.*.field }}'  // ASC by default

// Alternative key
'ORDER' => [...]  // Same as ORDER BY
```

**Examples:**
```php
// Single field ascending
'ORDER BY' => '{{ products.*.name }}'

// Single field descending
'ORDER BY' => [
    '{{ products.*.price }}' => 'DESC',
]

// Multiple fields
'ORDER BY' => [
    '{{ products.*.category }}' => 'ASC',
    '{{ products.*.price }}' => 'DESC',
]
```

**Features:**
- ✅ Numeric sorting (2 < 10 < 100)
- ✅ String sorting (case-insensitive)
- ✅ Null handling (nulls first in ASC, last in DESC)
- ✅ Multi-field sorting

**See:** [Example 13](../examples/13-wildcard-where-clause.php)

### LIMIT - Limit Results

Limit the number of items returned.

**Syntax:**
```php
'LIMIT' => 10  // Return max 10 items
```

**Example:**
```php
'top_products' => [
    'ORDER BY' => [
        '{{ products.*.sales }}' => 'DESC',
    ],
    'LIMIT' => 5,  // Top 5 products
    '*' => [
        'name' => '{{ products.*.name }}',
    ],
]
```

**See:** [Example 13](../examples/13-wildcard-where-clause.php)

### OFFSET - Skip Items

Skip the first N items.

**Syntax:**
```php
'OFFSET' => 10  // Skip first 10 items
```

**Example:**
```php
// Pagination: Skip first 20, take next 10
'products_page_3' => [
    'OFFSET' => 20,
    'LIMIT' => 10,
    '*' => [
        'name' => '{{ products.*.name }}',
    ],
]
```

**See:** [Example 13](../examples/13-wildcard-where-clause.php)

### DISTINCT - Remove Duplicates

Remove duplicate items based on a field.

**Syntax:**
```php
'DISTINCT' => '{{ path.*.field }}'
```

**Example:**
```php
'unique_categories' => [
    'DISTINCT' => '{{ products.*.category }}',
    '*' => [
        'category' => '{{ products.*.category }}',
    ],
]
```

**See:** [Example 16](../examples/16-distinct-like-operators.php)

### LIKE - Pattern Matching

Filter items using SQL-style pattern matching with `%` wildcards.

**Syntax:**
```php
'LIKE' => [
    '{{ path.*.field }}' => 'pattern%',
]
```

**Patterns:**
- `%` - Matches any sequence of characters
- `_` - Not supported (use regex for single character matching)

**Examples:**
```php
// Starts with
'LIKE' => [
    '{{ products.*.name }}' => 'iPhone%',
]

// Ends with
'LIKE' => [
    '{{ products.*.email }}' => '%@gmail.com',
]

// Contains
'LIKE' => [
    '{{ products.*.description }}' => '%wireless%',
]

// Multiple patterns (AND logic)
'LIKE' => [
    '{{ products.*.name }}' => 'iPhone%',
    '{{ products.*.category }}' => '%Electronics%',
]
```

**See:** [Example 16](../examples/16-distinct-like-operators.php)

### GROUP BY - Group and Aggregate

Group items by one or more fields and calculate aggregations.

**Syntax:**
```php
'GROUP BY' => [
    'field' => '{{ path.*.field }}',           // Single field (string or array)
    // OR
    'fields' => ['{{ path.*.field1 }}', ...],  // Multiple fields (string or array)
    
    'aggregations' => [
        'result_name' => ['FUNCTION', '{{ path.*.field }}', ...args],
    ],
    
    'HAVING' => [
        'aggregation_name' => ['operator', value],
    ],
]
```

**Aggregation Functions:**
- `COUNT` - Count items in group
- `SUM` - Sum numeric values
- `AVG` / `AVERAGE` - Average of values
- `MIN` - Minimum value
- `MAX` - Maximum value
- `FIRST` - First value in group
- `LAST` - Last value in group
- `COLLECT` - Collect all values into array
- `CONCAT` - Concatenate values with separator

**Example:**
```php
'sales_by_category' => [
    'GROUP BY' => [
        'field' => '{{ sales.*.category }}',
        'aggregations' => [
            'total_revenue' => ['SUM', '{{ sales.*.amount }}'],
            'order_count' => ['COUNT'],
            'avg_order' => ['AVG', '{{ sales.*.amount }}'],
        ],
        'HAVING' => [
            'total_revenue' => ['>', 1000],
        ],
    ],
    '*' => [
        'category' => '{{ sales.*.category }}',
        'revenue' => '{{ sales.*.total_revenue }}',
        'orders' => '{{ sales.*.order_count }}',
    ],
]
```

**See:** 
- [GROUP BY Operator Documentation](./group-by-operator.md) - Complete guide
- [Example 17](../examples/17-group-by-aggregations.php) - 10 comprehensive examples

## Combining Operators

Operators are executed in a specific order and can be combined:

```php
'result' => [
    'WHERE' => [
        '{{ products.*.stock }}' => ['>', 0],
    ],
    'ORDER BY' => [
        '{{ products.*.price }}' => 'DESC',
    ],
    'OFFSET' => 10,
    'LIMIT' => 5,
    '*' => [
        'name' => '{{ products.*.name }}',
        'price' => '{{ products.*.price }}',
    ],
]
```

**Execution Order:**
1. WHERE - Filter items
2. DISTINCT - Remove duplicates
3. LIKE - Pattern matching
4. GROUP BY - Group and aggregate
5. ORDER BY - Sort items
6. OFFSET - Skip items
7. LIMIT - Limit results

## Custom Operators

Register your own operators to extend functionality.

**Example:**
```php
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;

WildcardOperatorRegistry::register('REVERSE', function(array $items, mixed $config): array {
    return array_reverse($items, true);
});

// Use it
$template = [
    'reversed' => [
        'REVERSE' => true,
        '*' => [
            'name' => '{{ products.*.name }}',
        ],
    ],
];
```

**Handler Signature:**
```php
function(
    array $items,      // Wildcard array to process
    mixed $config,     // Operator configuration from template
    mixed $sources,    // Source data (optional)
    array $aliases     // Resolved aliases (optional)
): array
```

**See:** [Example 14](../examples/14-custom-wildcard-operators.php)

## Execution Order

Operators are executed in this order:

1. **WHERE** - Filter items first
2. **DISTINCT** - Remove duplicates
3. **LIKE** - Pattern matching
4. **GROUP BY** - Group and aggregate
5. **ORDER BY** - Sort remaining items
6. **OFFSET** - Skip items
7. **LIMIT** - Limit final results

This order ensures optimal performance and predictable results.

## See Also

- **[GROUP BY Operator](./group-by-operator.md)** - Detailed GROUP BY documentation
- **[Template Expressions](./template-expressions.md)** - Template syntax and filters
- **[Data Mapper](./data-mapper.md)** - Main mapping documentation
- **[Examples](../examples/)** - Runnable code examples

