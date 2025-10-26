---
title: Wildcards
description: Use wildcards to access and manipulate multiple items at once
---

Wildcards allow you to match multiple items in arrays and collections using the `*` symbol. They work across all Data Helpers classes and enable powerful bulk operations.

## Basic Wildcard Usage

Use `*` to match any single segment at that position:

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');
// $emails = ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com']
```

## Deep Wildcards

Multiple `*` can appear in one path:

```php
$data = ['orders' => [['items' => [['sku' => 'ABC'], ['sku' => 'DEF']]]]];
$accessor = new DataAccessor($data);
$skus = $accessor->get('orders.*.items.*.sku');
// $skus = ['orders.0.items.0.sku' => 'ABC', 'orders.0.items.1.sku' => 'DEF']
```

## Wildcard Operators

Use SQL-like operators with wildcards:

- **WHERE** - Filter items
- **ORDER BY** - Sort items
- **LIMIT/OFFSET** - Pagination
- **GROUP BY** - Aggregations
- **DISTINCT** - Remove duplicates

See [DataMapper](/main-classes/data-mapper/) for detailed examples.

## See Also

- [Dot-Notation Paths](/core-concepts/dot-notation/)
- [DataMapper](/main-classes/data-mapper/)
