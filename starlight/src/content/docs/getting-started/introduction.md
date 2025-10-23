---
title: Introduction
description: Learn about Data Helpers - a powerful, framework-agnostic PHP library for data manipulation
---

<div align="center" style="margin-bottom: 2rem;">
  <a href="https://event4u.app">
    <img alt="Data Helpers - Framework-agnostic PHP library for data manipulation, transformation, and validation" src="/data-helpers/banner.png" style="max-width: 100%; height: auto; border-radius: 8px;" />
  </a>
</div>

Data Helpers is a powerful, framework-agnostic PHP library for accessing, transforming, and mapping complex nested data structures with ease. It provides dot notation access, wildcard support, data mapping with templates, caching, and 40+ built-in filters.

## What is Data Helpers?

Data Helpers is a comprehensive toolkit for working with data in PHP applications. It provides five main components:

- **DataAccessor** - Read nested data with dot notation and wildcards
- **DataMutator** - Modify nested data structures safely
- **DataMapper** - Transform data structures with templates and pipelines
- **DataFilter** - Query and filter data with SQL-like API
- **SimpleDTO** - Immutable Data Transfer Objects with validation and casting

## Quick Example

```php
// From this messy API response...
$apiResponse = [
    'data' => [
        'departments' => [
            ['users' => [['email' => 'alice@example.com'], ['email' => 'bob@example.com']]],
            ['users' => [['email' => 'charlie@example.com']]],
        ],
    ],
];

// ...to this clean result in one line
$accessor = new DataAccessor($apiResponse);
$emails = $accessor->get('data.departments.*.users.*.email');
// Result: ['alice@example.com', 'bob@example.com', 'charlie@example.com']
```

## Why Use Data Helpers?

### Stop Writing Nested Loops

Without Data Helpers, you need to write verbose code with multiple loops and null checks:

```php
// Without Data Helpers
$emails = [];
foreach ($data['departments'] ?? [] as $dept) {
    foreach ($dept['users'] ?? [] as $user) {
        if (isset($user['email'])) {
            $emails[] = $user['email'];
        }
    }
}

// With Data Helpers
$emails = $accessor->get('departments.*.users.*.email');
```

### Transform Data Structures with Ease

Map between different data formats, APIs, or database schemas without writing repetitive transformation code:

```php
$mapper = new DataMapper();
$result = $mapper->map($source, [
    'user_name' => '{{ profile.name }}',
    'user_email' => '{{ profile.contact.email }}',
    'total_orders' => '{{ orders.*.amount | sum }}',
]);
```

### Type-Safe and Well-Tested

- PHPStan Level 9 compliant
- 2900+ tests with comprehensive coverage
- Works reliably with arrays, objects, Collections, Models, JSON, and XML

### Framework-Agnostic with Smart Detection

Use it anywhere - Laravel, Symfony, Doctrine, or plain PHP. Framework support is automatically detected at runtime:

- Laravel 9+ - Collections, Eloquent Models
- Symfony 6+ - Collections, Entities
- Doctrine 2+ - Collections, Entities
- Plain PHP - Works out of the box

### Blazing Fast Performance

DataMapper is significantly faster than traditional serializers for DTO mapping:

- Up to 3.7x faster than Symfony Serializer
- Optimized for nested data structures
- Zero reflection overhead for template-based mapping
- See [Performance Benchmarks](/performance/benchmarks) for detailed comparison

## Key Features

### DataAccessor
- Dot notation path access
- Wildcard support for nested arrays
- Type-safe getters
- Collections support (Laravel/Doctrine)
- JSON and XML support

### DataMutator
- Safe nested value setting
- Merge operations
- Unset operations
- Wildcard mutations

### DataMapper
- Template-based mapping
- Pipeline filters (40+ built-in)
- Reverse mapping
- Query builder
- GROUP BY and aggregations
- Custom operators

### DataFilter
- SQL-like query API
- WHERE, ORDER BY, LIMIT, OFFSET
- Custom operators
- Chainable methods

### SimpleDTO
- Immutable DTOs
- 20+ built-in casts
- Validation system (22+ attributes)
- Property mapping
- Conditional properties (18 attributes)
- Lazy properties
- Computed properties
- Collections support
- TypeScript generation
- Framework integration

## Requirements

- PHP 8.2 or higher
- No required dependencies
- Optional framework integrations:
  - Laravel 9+
  - Symfony 6+
  - Doctrine 2+

## Installation

Install via Composer:

```bash
composer require event4u/data-helpers
```

See [Installation](/getting-started/installation) for detailed setup instructions.

## Next Steps

- [Installation](/getting-started/installation) - Install and configure Data Helpers
- [Quick Start](/getting-started/quick-start) - Get started in 5 minutes
- [Core Concepts](/core-concepts/dot-notation) - Learn the fundamentals
- [Examples](/examples) - Browse 90+ code examples

## Comparison with Similar Libraries

Data Helpers offers several advantages over comparable projects:

- **Framework-agnostic** - Works with Laravel, Symfony, or plain PHP
- **Zero dependencies** - No required dependencies, optional framework integrations
- **Comprehensive** - 5 main components covering all data manipulation needs
- **Well-tested** - 2900+ tests with PHPStan Level 9 compliance
- **High performance** - Up to 3.7x faster than traditional serializers
- **Rich feature set** - 40+ filters, 20+ casts, 22+ validation attributes, 18 conditional attributes

See [Performance Comparison](/performance/comparison) for detailed benchmarks.

## Support

- [GitHub Issues](https://github.com/event4u-app/data-helpers/issues) - Report bugs or request features
- [GitHub Discussions](https://github.com/event4u-app/data-helpers/discussions) - Ask questions and share ideas
- [Examples](/examples) - Browse 90+ code examples
- [API Reference](/api) - Complete API documentation

## License

Data Helpers is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

