---
title: DataMapper
description: Transform data structures with the powerful Fluent API
---

DataMapper provides a modern, fluent API for transforming data between different structures. It supports template-based mapping, queries with SQL-like operators, property-specific filters, and much more.

## Quick Example

```php
use event4u\DataHelpers\DataMapper;

$source = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'orders' => [
        ['id' => 1, 'total' => 100, 'status' => 'shipped'],
        ['id' => 2, 'total' => 200, 'status' => 'pending'],
        ['id' => 3, 'total' => 150, 'status' => 'shipped'],
    ],
];

// Approach 1: Fluent API with query builder
$result = DataMapper::source($source)
    ->query('orders.*')
        ->where('status', '=', 'shipped')
        ->orderBy('total', 'DESC')
        ->end()
    ->template([
        'customer_name' => '{{ user.name }}',
        'customer_email' => '{{ user.email }}',
        'shipped_orders' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();

// Approach 2: Template-based with WHERE/ORDER BY operators (recommended)
$template = [
    'customer_name' => '{{ user.name }}',
    'customer_email' => '{{ user.email }}',
    'shipped_orders' => [
        'WHERE' => [
            '{{ orders.*.status }}' => 'shipped',
        ],
        'ORDER BY' => [
            '{{ orders.*.total }}' => 'DESC',
        ],
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
        ],
    ],
];

$result = DataMapper::source($source)
    ->template($template)
    ->map()
    ->getTarget();

// Both approaches produce the same result:
// [
//     'customer_name' => 'John Doe',
//     'customer_email' => 'john@example.com',
//     'shipped_orders' => [
//         ['id' => 3, 'total' => 150],
//         ['id' => 1, 'total' => 100],
//     ],
// ]
```

### Why Use Template-Based Approach?

The template-based approach (Approach 2) has a significant advantage: **templates can be stored in a database and created with a drag-and-drop editor**, enabling **no-code data mapping**:

```php
// Store templates in database
$source = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'orders' => [
        ['id' => 1, 'total' => 100, 'status' => 'shipped'],
        ['id' => 2, 'total' => 200, 'status' => 'pending'],
        ['id' => 3, 'total' => 150, 'status' => 'shipped'],
    ],
];

// Load template from database (created with drag-and-drop editor)
$template = Mappings::find(3)->template;

$result = DataMapper::source($source)
    ->template($template)
    ->map()
    ->getTarget();
```

**This makes it possible to map import files, API responses, etc. without any programming.**

Use cases:
- **Import Wizards** - Let users map CSV/Excel columns to your data structure
- **API Integration** - Store API response mappings in database
- **Multi-Tenant Systems** - Each tenant can have custom mappings
- **Dynamic ETL** - Build data transformation pipelines without code
- **Form Builders** - Map form submissions to different data structures

## Fluent API Overview

The DataMapper uses a fluent, chainable API:

<!-- skip-test: API overview with placeholders -->
```php
DataMapper::source($source)           // Start with source data
    ->target($target)               // Optional: Set target object/array
    ->template($template)           // Define mapping template
    ->query($path)                  // Start query builder
        ->where($field, $op, $val)  // Add WHERE condition
        ->orderBy($field, $dir)     // Add ORDER BY
        ->limit($n)                 // Add LIMIT
        ->end()                     // End query builder
    ->property($name)               // Access property API
        ->setFilter($filter)        // Set property filter
        ->end()                     // End property API
    ->pipeline($filters)            // Set global filters
    ->skipNull()                    // Skip null values
    ->map()                         // Execute mapping
    ->getTarget();                  // Get result
```

## Basic Usage

### Simple Template Mapping

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com', 'profile' => ['age' => 30]]];
$result = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'age' => '{{ user.profile.age }}',
    ])
    ->map()
    ->getTarget();
```

### Template Syntax

Templates use `{{ }}` for dynamic values:

- **Dynamic values:** `'{{ user.name }}'` - Fetches value from source
- **Static values:** `'admin'` - Used as literal string (no `{{ }}`)
- **Dot-notation:** `'{{ user.profile.address.street }}'` - Nested access
- **Wildcards:** `'{{ users.*.email }}'` - Array operations

### Mapping to Objects

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public string $name;
    public string $email;
}

$result = DataMapper::source($source)
    ->target(UserDto::class)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ])
    ->map()
    ->getTarget(); // Returns UserDto instance
```

### Nested Structures

```php
$source = [
    'user' => ['name' => 'John', 'email' => 'john@example.com', 'phone' => '555-1234'],
    'orders' => [['id' => 1, 'total' => 100], ['id' => 2, 'total' => 200]],
];
$result = DataMapper::source($source)
    ->template([
        'customer' => [
            'name' => '{{ user.name }}',
            'contact' => [
                'email' => '{{ user.email }}',
                'phone' => '{{ user.phone }}',
            ],
        ],
        'orders' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

## Query Builder

The query builder provides SQL-like operators for filtering and transforming data during mapping.

:::tip[Template-Based Alternative]
Instead of using the fluent query API, you can use **WHERE/ORDER BY operators directly in templates**. This approach is recommended when templates need to be stored in a database or created with a visual editor. See the [Quick Example](#quick-example) above for details.
:::

### Basic Queries

```php
// Fluent API approach
$result = DataMapper::source($source)
    ->query('orders.*')
        ->where('total', '>', 100)
        ->orderBy('total', 'DESC')
        ->limit(5)
        ->end()
    ->template([
        'items' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();

// Template-based approach (same result)
$result = DataMapper::source($source)
    ->template([
        'items' => [
            'WHERE' => [
                '{{ orders.*.total }}' => ['>', 100],
            ],
            'ORDER BY' => [
                '{{ orders.*.total }}' => 'DESC',
            ],
            'LIMIT' => 5,
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

### WHERE Conditions

```php
// Simple comparison
->where('status', '=', 'active')
->where('price', '>', 100)
->where('stock', '<=', 10)

// Multiple conditions (AND logic)
->where('status', '=', 'active')
->where('price', '>', 100)

// BETWEEN
->where('price', 'BETWEEN', [50, 150])

// IN
->where('status', 'IN', ['active', 'pending'])

// LIKE (pattern matching)
->where('name', 'LIKE', 'John%')

// NULL checks
->where('deleted_at', 'IS NULL')
->where('email', 'IS NOT NULL')
```

### ORDER BY

```php
// Single field
->orderBy('price', 'DESC')

// Multiple fields
->orderBy('category', 'ASC')
->orderBy('price', 'DESC')
```

### LIMIT and OFFSET

```php
// Limit results
->limit(10)

// Skip items
->offset(20)

// Pagination
->offset(20)
->limit(10)
```

### DISTINCT

```php
// Remove duplicates
->distinct('email')
```

### GROUP BY

```php
// Group and aggregate
->groupBy('category', [
    'total' => 'SUM(price)',
    'count' => 'COUNT(*)',
    'avg_price' => 'AVG(price)',
])
```

## Pipeline Filters

Apply filters to all mapped values globally.

### Global Filters

<!-- skip-test: Import conflict with other examples -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

$result = DataMapper::source($source)
    ->pipeline([
        new TrimStrings(),
        new UppercaseStrings(),
    ])
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ])
    ->map()
    ->getTarget();

// All string values are trimmed and uppercased
```

### Adding Filters

<!-- skip-test: Import conflict with other examples -->
```php
$mapper = DataMapper::source($source)
    ->template($template);

// Add single filter
$mapper->addPipelineFilter(new TrimStrings());

// Add multiple filters
$mapper->pipeline([
    new TrimStrings(),
    new UppercaseStrings(),
]);
```

### Built-in Filters

Data Helpers includes 40+ built-in filters:

- **String Filters:** TrimStrings, UppercaseStrings, LowercaseStrings, etc.
- **Number Filters:** RoundNumbers, FormatCurrency, etc.
- **Date Filters:** FormatDate, ParseDate, etc.
- **Array Filters:** FlattenArray, UniqueValues, etc.
- **Validation Filters:** ValidateEmail, ValidateUrl, etc.

See [Filters Documentation](/advanced-features/filters) for complete list.

## Property-Specific Filters

Apply filters to specific properties only.

### Using setFilter()

<!-- skip-test: Import conflict with other examples -->
```php
$result = DataMapper::source($source)
    ->setFilter('name', new TrimStrings(), new UppercaseStrings())
    ->setFilter('email', new TrimStrings(), new LowercaseStrings())
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'bio' => '{{ user.bio }}',
    ])
    ->map()
    ->getTarget();

// Only 'name' and 'email' are filtered, 'bio' is not
```

### Using Property API

<!-- skip-test: Import conflict with other examples -->
```php
$result = DataMapper::source($source)
    ->property('name')
        ->setFilter(new TrimStrings(), new UppercaseStrings())
        ->end()
    ->property('email')
        ->setFilter(new TrimStrings(), new LowercaseStrings())
        ->end()
    ->template($template)
    ->map()
    ->getTarget();
```

### Nested Properties

<!-- skip-test: Code snippet example -->
```php
// Works with dot-notation
->setFilter('user.profile.bio', new TrimStrings())

// Works with wildcards
->setFilter('items.*.name', new TrimStrings())
```

## Property API

The Property API provides focused access to individual properties.

### Get Property Target

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ]);

// Get mapping target for property
$target = $mapper->property('name')->getTarget();
// $target = '{{ user.name }}'
```

### Get Property Filters

<!-- skip-test: requires mapper instance -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

$mapper->setFilter('name', new TrimStrings());

$filters = $mapper->property('name')->getFilter();
// $filters = [TrimStrings]
```

### Get Mapped Value

```php
// Execute mapping and get value for specific property
$value = $mapper->property('name')->getMappedValue();
// $value = 'John Doe' (after applying filters)
```

### Reset Property Filters

<!-- skip-test: Import conflict with other examples -->
```php
$mapper->property('name')
    ->setFilter(new TrimStrings())
    ->resetFilter()  // Remove all filters
    ->setFilter(new UppercaseStrings())  // Set new filter
    ->end();
```

## Discriminator (Polymorphic Mapping)

Automatically select target class based on a discriminator field (Liskov Substitution Principle).

### Basic Usage

<!-- skip-test: to abstract. results in error. -->
```php
abstract class Animal
{
    public string $name;
    public int $age;
}

class Dog extends Animal
{
    public string $breed;
}

class Cat extends Animal
{
    public int $lives;
}

$source = [
    'type' => 'dog',
    'name' => 'Rex',
    'age' => 5,
    'breed' => 'Golden Retriever',
];

$result = DataMapper::source($source)
    ->target(Animal::class)
    ->discriminator('type', [
        'dog' => Dog::class,
        'cat' => Cat::class,
    ])
    ->template([
        'name' => '{{ name }}',
        'age' => '{{ age }}',
        'breed' => '{{ breed }}',
    ])
    ->map()
    ->getTarget();

// Returns Dog instance (because type='dog')
```

### Nested Discriminator

```php
// Discriminator field can be nested
->discriminator('meta.classification.type', [
    'premium' => PremiumUser::class,
    'basic' => BasicUser::class,
])
```

### Fallback Behavior

```php
// If discriminator value not found, falls back to original target
$result = DataMapper::source(['type' => 'unknown'])
    ->target(Animal::class)
    ->discriminator('type', [
        'dog' => Dog::class,
        'cat' => Cat::class,
    ])
    ->template($template)
    ->map()
    ->getTarget();

// Returns Animal instance (fallback)
```

## Copy and Extend

Create independent copies of mapper configurations.

### Copy Configuration

```php
$baseMapper = DataMapper::source($source)
    ->target(User::class)
    ->template([
        'name' => '{{ name }}',
    ]);

// Create independent copy
$extendedMapper = $baseMapper->copy()
    ->extendTemplate([
        'email' => '{{ email }}',
    ])
    ->addPipelineFilter(new TrimStrings());

// $baseMapper is unchanged
// $extendedMapper has extended config
```

### Extend Template

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com', 'phone' => '555-1234']];
$mapper = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
    ]);

// Extend with additional fields
$mapper->extendTemplate([
    'email' => '{{ user.email }}',
    'phone' => '{{ user.phone }}',
]);

// Template now has all three fields
```

## Reset and Delete

Manage template operators dynamically.

### Reset to Original

```php
$source = ['products' => [['id' => 1, 'status' => 'active', 'price' => 100], ['id' => 2, 'status' => 'inactive', 'price' => 50]]];
$mapper = DataMapper::source($source)
    ->template([
        'items' => [
            'WHERE' => ['{{ products.*.status }}' => 'active'],
            'ORDER BY' => ['{{ products.*.price }}' => 'DESC'],
            '*' => ['id' => '{{ products.*.id }}'],
        ],
    ]);

// Modify with query
$mapper->query('products.*')
    ->where('price', '>', 75)
    ->orderBy('price', 'ASC')
    ->end();

// Reset WHERE to original template value
$mapper->reset()->where();

// Reset entire template
$mapper->reset()->all();
```

### Delete Operators

```php
// Delete specific operator
$mapper->delete()->where();

// Delete all operators
$mapper->delete()->all();
```

### Chainable

```php
// Chain multiple operations
$mapper->reset()->where()->orderBy();
$mapper->delete()->limit()->offset();
```

## Performance

DataMapper is optimized for performance:

- **3.7x faster** than Symfony Serializer for Dto mapping
- **Zero reflection overhead** for template-based mapping
- **Efficient caching** for path resolution and reflection
- **Minimal overhead** (7.1%) for Fluent API wrapper

See [Performance Benchmarks](/performance/benchmarks) for detailed comparison.

## Code Examples

The following working examples demonstrate DataMapper in action:

- [**Simple Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/simple-mapping.php) - Basic template-based mapping
- [**Template-Based Queries**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/template-based-queries.php) - WHERE/ORDER BY in templates (recommended for database-stored templates)
- [**With Hooks**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/with-hooks.php) - Using hooks for custom logic
- [**Pipeline**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/pipeline.php) - Filter pipelines and transformations
- [**Mapped Data Model**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/mapped-data-model.php) - Using MappedDataModel class
- [**Template Expressions**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/template-expressions.php) - Advanced template syntax
- [**Reverse Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/reverse-mapping.php) - Bidirectional mapping
- [**Dto Integration**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/dto-integration.php) - Integration with SimpleDto

All examples are fully tested and can be run directly:

```bash
php examples/main-classes/data-mapper/simple-mapping.php
php examples/main-classes/data-mapper/template-based-queries.php
php examples/main-classes/data-mapper/with-hooks.php
```

## Related Tests

The functionality is thoroughly tested. Key test files:

- [DataMapperTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/DataMapperTest.php) - Core functionality tests
- [DataMapperHooksTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/DataMapperHooksTest.php) - Hook system tests
- [DataMapperPipelineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/Pipeline/DataMapperPipelineTest.php) - Pipeline tests
- [MapperQueryTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MapperQueryTest.php) - Query integration tests
- [MultiSourceFluentTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MultiSourceFluentTest.php) - Multi-source mapping tests
- [MultiTargetMappingTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MultiTargetMappingTest.php) - Multi-target mapping tests
- [DataMapperIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Integration/DataMapperIntegrationTest.php) - End-to-end scenarios

Run the tests:

```bash
# Run all DataMapper tests
task test:unit -- --filter=DataMapper

# Run specific test file
vendor/bin/pest tests/Unit/DataMapper/DataMapperTest.php
```
## See Also

- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataMutator](/main-classes/data-mutator/) - Modify nested data
- [DataFilter](/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Wildcards](/core-concepts/wildcards/) - Wildcard operators
- [Examples](/examples/) - 90+ code examples
