---
title: DataMapper
description: Transform and map data structures with templates, pipelines, and powerful query capabilities
---

DataMapper is a powerful tool for transforming data structures. It provides a fluent API for mapping, filtering, and transforming data with templates, pipelines, and SQL-like queries.

## Quick Example

```php
use event4u\DataHelpers\DataMapper;

$source = [
    'profile' => [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'contact' => [
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ],
    ],
    'settings' => [
        'theme' => 'dark',
        'language' => 'en',
    ],
];

// Simple template mapping
$result = DataMapper::source($source)
    ->template([
        'name' => 'profile.firstName',
        'email' => 'profile.contact.email',
        'theme' => 'settings.theme',
    ])
    ->map();

// Result:
// [
//     'name' => 'John',
//     'email' => 'john@example.com',
//     'theme' => 'dark',
// ]
```

## Key Features

### Template Expressions

Map data using simple dot-notation paths or complex expressions:

```php
DataMapper::source($data)
    ->template([
        'fullName' => '{{ profile.firstName }} {{ profile.lastName }}',
        'email' => 'profile.contact.email',
        'isActive' => 'status.active',
    ])
    ->map();
```

### Query Builder

Filter and transform data with SQL-like queries:

```php
DataMapper::source($users)
    ->where('age', '>', 18)
    ->where('status', 'active')
    ->orderBy('name')
    ->limit(10)
    ->map();
```

### Pipelines

Chain multiple transformations:

```php
DataMapper::source($data)
    ->pipeline([
        'uppercase' => fn($value) => strtoupper($value),
        'trim' => fn($value) => trim($value),
    ])
    ->map();
```

### GROUP BY Operations

Group and aggregate data:

```php
DataMapper::source($orders)
    ->groupBy('customer_id')
    ->aggregate([
        'total' => 'SUM(amount)',
        'count' => 'COUNT(*)',
    ])
    ->map();
```

## When to Use DataMapper

Use DataMapper when you need to:

- **Transform API responses** - Map external API data to your internal structure
- **Aggregate data** - Group and summarize data with SQL-like operations
- **Filter collections** - Query data with complex conditions
- **Normalize data** - Convert data from one format to another
- **Build ETL pipelines** - Extract, transform, and load data

## Performance

DataMapper is optimized for performance:

- **Lazy evaluation** - Only processes data when needed
- **Efficient memory usage** - Streams large datasets
- **Cached templates** - Compiled templates for repeated use
- **Minimal overhead** - Direct array access without unnecessary copies

## Learn More

For detailed documentation and advanced features, see:

- [DataMapper Introduction](/data-helpers/main-classes/data-mapper/) - Complete guide
- [Template Expressions](/data-helpers/advanced/template-expressions/) - Advanced templating
- [Query Builder](/data-helpers/advanced/query-builder/) - SQL-like queries
- [GROUP BY Operator](/data-helpers/advanced/group-by/) - Aggregation and grouping
- [Pipelines](/data-helpers/advanced/pipelines/) - Transformation chains
- [Reverse Mapping](/data-helpers/advanced/reverse-mapping/) - Bidirectional mapping

## Next Steps

- [Read the full DataMapper documentation →](/data-helpers/main-classes/data-mapper/)
- [Explore template expressions →](/data-helpers/advanced/template-expressions/)
- [Learn about query builder →](/data-helpers/advanced/query-builder/)

