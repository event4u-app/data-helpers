---
title: GROUP BY Operator
description: Group items by fields and apply aggregation functions
---

The GROUP BY operator groups items by one or more fields and applies aggregation functions to calculate statistics for each group.

## Introduction

The GROUP BY operator enables you to:

- **Group items** by one or more fields
- **Calculate aggregations** (COUNT, SUM, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT)
- **Filter groups** using HAVING conditions
- **Access aggregated values** in template expressions

**Key Features:**
- 9 built-in aggregation functions
- Single or multiple field grouping
- HAVING clause with all comparison operators
- Aggregated values available in template expressions
- Works with all other wildcard operators

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
    'field' => '{{ source.*.field }}',              // Single field (string)
    // OR
    'field' => [                                     // Multiple fields (array)
        '{{ source.*.field1 }}',
        '{{ source.*.field2 }}',
    ],
    // OR
    'fields' => '{{ source.*.field }}',             // Single field (string)
    // OR
    'fields' => [                                    // Multiple fields (array)
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

**Note:** Both `field` and `fields` are supported and can accept either a single string or an array of strings.

## Aggregation Functions

### COUNT

Counts the number of items in each group.

```php
'aggregations' => [
    'total_count' => ['COUNT'],
]
```

### SUM

Sums numeric values.

```php
'aggregations' => [
    'total_amount' => ['SUM', '{{ orders.*.amount }}'],
]
```

### AVG

Calculates average of numeric values.

```php
'aggregations' => [
    'avg_price' => ['AVG', '{{ products.*.price }}'],
]
```

### MIN

Finds minimum value.

```php
'aggregations' => [
    'min_price' => ['MIN', '{{ products.*.price }}'],
]
```

### MAX

Finds maximum value.

```php
'aggregations' => [
    'max_price' => ['MAX', '{{ products.*.price }}'],
]
```

### FIRST

Gets first value in group.

```php
'aggregations' => [
    'first_order' => ['FIRST', '{{ orders.*.date }}'],
]
```

### LAST

Gets last value in group.

```php
'aggregations' => [
    'last_order' => ['LAST', '{{ orders.*.date }}'],
]
```

### COLLECT

Collects all values into an array.

```php
'aggregations' => [
    'all_names' => ['COLLECT', '{{ items.*.name }}'],
]
```

### CONCAT

Concatenates values with separator.

```php
'aggregations' => [
    'names_list' => ['CONCAT', '{{ items.*.name }}', ', '],
]
```

## HAVING Clause

Filter groups after aggregation:

```php
'GROUP BY' => [
    'field' => '{{ orders.*.customerId }}',
    'aggregations' => [
        'total_orders' => ['COUNT'],
        'total_spent' => ['SUM', '{{ orders.*.amount }}'],
    ],
    'HAVING' => [
        'total_orders' => ['>', 5],
        'total_spent' => ['>=', 1000],
    ],
]
```

### Supported Operators

- `=` - Equal
- `!=`, `<>` - Not equal
- `>` - Greater than
- `<` - Less than
- `>=` - Greater than or equal
- `<=` - Less than or equal

## Multi-Field Grouping

Group by multiple fields:

```php
'GROUP BY' => [
    'fields' => [
        '{{ orders.*.customerId }}',
        '{{ orders.*.status }}',
    ],
    'aggregations' => [
        'count' => ['COUNT'],
    ],
]
```

## Complete Examples

### Group Orders by Customer

```php
$sources = [
    'orders' => [
        ['customerId' => 1, 'amount' => 100],
        ['customerId' => 1, 'amount' => 200],
        ['customerId' => 2, 'amount' => 150],
    ],
];

$template = [
    'result' => [
        'GROUP BY' => [
            'field' => '{{ orders.*.customerId }}',
            'aggregations' => [
                'total_orders' => ['COUNT'],
                'total_spent' => ['SUM', '{{ orders.*.amount }}'],
                'avg_order' => ['AVG', '{{ orders.*.amount }}'],
            ],
        ],
        '*' => [
            'customerId' => '{{ orders.*.customerId }}',
            'totalOrders' => '{{ orders.*.total_orders }}',
            'totalSpent' => '{{ orders.*.total_spent }}',
            'avgOrder' => '{{ orders.*.avg_order }}',
        ],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);

// Result:
// [
//     'result' => [
//         ['customerId' => 1, 'totalOrders' => 2, 'totalSpent' => 300, 'avgOrder' => 150],
//         ['customerId' => 2, 'totalOrders' => 1, 'totalSpent' => 150, 'avgOrder' => 150],
//     ]
// ]
```

### Group Products by Category

```php
$sources = [
    'products' => [
        ['category' => 'Electronics', 'price' => 1299, 'name' => 'Laptop'],
        ['category' => 'Electronics', 'price' => 29, 'name' => 'Mouse'],
        ['category' => 'Furniture', 'price' => 299, 'name' => 'Desk'],
    ],
];

$template = [
    'categories' => [
        'GROUP BY' => [
            'field' => '{{ products.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
                'min_price' => ['MIN', '{{ products.*.price }}'],
                'max_price' => ['MAX', '{{ products.*.price }}'],
                'avg_price' => ['AVG', '{{ products.*.price }}'],
                'product_names' => ['CONCAT', '{{ products.*.name }}', ', '],
            ],
        ],
        '*' => [
            'category' => '{{ products.*.category }}',
            'productCount' => '{{ products.*.count }}',
            'priceRange' => '{{ products.*.min_price }} - {{ products.*.max_price }}',
            'avgPrice' => '{{ products.*.avg_price }}',
            'products' => '{{ products.*.product_names }}',
        ],
    ],
];
```

### With HAVING Filter

```php
$template = [
    'highValueCustomers' => [
        'GROUP BY' => [
            'field' => '{{ orders.*.customerId }}',
            'aggregations' => [
                'total_orders' => ['COUNT'],
                'total_spent' => ['SUM', '{{ orders.*.amount }}'],
            ],
            'HAVING' => [
                'total_orders' => ['>', 5],
                'total_spent' => ['>=', 1000],
            ],
        ],
        '*' => [
            'customerId' => '{{ orders.*.customerId }}',
            'totalOrders' => '{{ orders.*.total_orders }}',
            'totalSpent' => '{{ orders.*.total_spent }}',
        ],
    ],
];
```

## Best Practices

### 1. Use Descriptive Aggregation Names

```php
// ✅ Good
'aggregations' => [
    'total_orders' => ['COUNT'],
    'total_revenue' => ['SUM', '{{ orders.*.amount }}'],
]

// ❌ Bad
'aggregations' => [
    'cnt' => ['COUNT'],
    'sum' => ['SUM', '{{ orders.*.amount }}'],
]
```

### 2. Filter Before Grouping

```php
// ✅ Good - Filter first, then group
$template = [
    'result' => [
        'WHERE' => [
            '{{ orders.*.status }}' => ['=', 'completed'],
        ],
        'GROUP BY' => [
            'field' => '{{ orders.*.customerId }}',
            // ...
        ],
    ],
];
```

### 3. Use HAVING for Aggregation Filters

```php
// ✅ Good - Filter aggregated values with HAVING
'HAVING' => [
    'total_orders' => ['>', 5],
]

// ❌ Bad - Can't filter aggregated values with WHERE
'WHERE' => [
    '{{ orders.*.total_orders }}' => ['>', 5], // Won't work
]
```

## See Also

- [Query Builder](/advanced/query-builder/) - Query builder
- [Template Expressions](/advanced/template-expressions/) - Template syntax
- [DataMapper](/main-classes/data-mapper/) - DataMapper guide

