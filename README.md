<div align="center">
  <a href="https://event4u.app">
    <img alt="event4u Data Helpers" src=".github/assets/banner.png">
  </a>
</div>

# 🚀 Data Helpers

[![Packagist Version](https://img.shields.io/packagist/v/event4u/data-helpers.svg?style=flat-square&label=packagist)](https://packagist.org/packages/event4u/data-helpers)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb3?logo=php&logoColor=white&style=flat-square)](#installation)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](#license)
[![GitHub Code Quality Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/code-quality.yml?branch=main&label=code%20quality&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/code-quality.yml)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/phpstan.yml)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/run-tests.yml)

**A powerful, framework-agnostic PHP library for accessing, transforming, and mapping complex nested data structures with ease. Features dot
notation access, wildcard support, data mapping with templates, caching, and 40+ built-in filters.**

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
// ['alice@example.com', 'bob@example.com', 'charlie@example.com']
```

**Framework-agnostic** • Works with **Laravel**, **Symfony/Doctrine**, or **standalone PHP** • Zero required dependencies

If you like this package, please support us in building packages like this and [event4u](https://event4u.app) - a free event app that makes
events even
better. [Support us and our project ❤️](#support-the-development)

---

## 💡 Why use this?

### 🎯 **Stop writing nested loops and array checks**

```php
// ❌ Without Data Helpers
$emails = [];
foreach ($data['departments'] ?? [] as $dept) {
    foreach ($dept['users'] ?? [] as $user) {
        if (isset($user['email'])) {
            $emails[] = $user['email'];
        }
    }
}

// ✅ With Data Helpers
$emails = $accessor->get('departments.*.users.*.email');
```

### 🔄 **Transform data structures with ease**

Map between different data formats, APIs, or database schemas without writing repetitive transformation code.

### 🛡️ **Type-safe and well-tested**

PHPStan Level 9 compliant with 1200+ tests. Works reliably with arrays, objects, Collections, Models, JSON, and XML.

### ⚡ **Framework-agnostic with smart detection**

Use it anywhere - Laravel, Symfony, Doctrine, or plain PHP. Framework support is automatically detected at runtime.

<!-- PERFORMANCE_COMPARISON_START -->

### 🚀 **Blazing fast performance**

DataMapper is significantly faster than traditional serializers for DTO mapping:

- Up to **3.7x faster** than Symfony Serializer
- Optimized for nested data structures
- Zero reflection overhead for template-based mapping
- See [benchmarks](#-performance) for detailed performance comparison

<!-- PERFORMANCE_COMPARISON_END -->

---

## 📦 Installation

```bash
composer require event4u/data-helpers
```

**Requirements:** PHP 8.2+

**Framework support** (all optional):

- 🔴 **Laravel** 9+ - Collections, Eloquent Models
- ⚫ **Symfony/Doctrine** - Collections, Entities
- 🔧 **Standalone PHP** - Works out of the box

### Configuration (Optional)

The package works out of the box with sensible defaults. Configuration is **optional** but allows you to customize caching behavior.

#### Laravel

Publish the configuration file:

```bash
php artisan vendor:publish --tag=data-helpers-config
```

This creates `config/data-helpers.php`. Customize performance mode:

```php
return [
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),
];
```

#### Symfony

**With Symfony Flex (automatic):**

```bash
composer require event4u/data-helpers
# Configuration files are automatically copied to config/packages/ and config/services/
```

**Manual installation:**

```bash
cp vendor/event4u/data-helpers/recipe/config/packages/data_helpers.yaml config/packages/
cp vendor/event4u/data-helpers/recipe/config/services/data_helpers.yaml config/services/
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    event4u\DataHelpers\Symfony\DataHelpersBundle::class => ['all' => true],
];
```

📖 **For Symfony Flex recipe details, see:** [docs/symfony-recipe.md](docs/symfony-recipe.md)

#### Plain PHP

Create a config file and load it in your bootstrap:

```php
// config/data-helpers.php
return [
    'performance_mode' => 'fast',
];

// bootstrap.php
$config = require __DIR__ . '/config/data-helpers.php';
event4u\DataHelpers\DataHelpersConfig::initialize($config);
```

📖 **For detailed configuration options, see:** [docs/configuration.md](docs/configuration.md)

👉 [See detailed framework setup guide](#-framework-support)

---

## ⚡ Quick Start

### 1️⃣ **DataAccessor** - Read nested data

```php
use event4u\DataHelpers\DataAccessor;

$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ],
];

$accessor = new DataAccessor($data);

// Get all emails with wildcard
$emails = $accessor->get('users.*.email');
// ['alice@example.com', 'bob@example.com']

// Works with JSON too
$accessor = new DataAccessor('{"users":[{"name":"Alice"}]}');
$name = $accessor->get('users.0.name'); // 'Alice'
```

### 2️⃣ **DataMutator** - Modify nested data

```php
use event4u\DataHelpers\DataMutator;

$data = [];

// Set deeply nested values
$data = DataMutator::set($data, 'user.profile.name', 'Alice');
// ['user' => ['profile' => ['name' => 'Alice']]]

// Merge arrays deeply
$data = DataMutator::merge($data, 'user.profile', ['age' => 30]);
// ['user' => ['profile' => ['name' => 'Alice', 'age' => 30]]]

// Unset multiple paths
$data = DataMutator::unset($data, ['user.profile.age', 'user.unknown']);
```

### 3️⃣ **DataMapper** - Transform data structures

```php
use event4u\DataHelpers\DataMapper;

$source = [
    'firstName' => 'Alice',
    'lastName' => 'Smith',
    'contact' => ['email' => 'alice@example.com'],
];

$mapping = [
    'profile' => [
        'name' => 'firstName',
        'surname' => 'lastName',
    ],
    'email' => 'contact.email',
];

$result = DataMapper::map($source, [], $mapping);
// [
//     'profile' => ['name' => 'Alice', 'surname' => 'Smith'],
//     'email' => 'alice@example.com'
// ]
```

### 4️⃣ **DataFilter** - Filter and query data

```php
use event4u\DataHelpers\DataFilter;

$products = [
    ['id' => 1, 'name' => 'Laptop', 'price' => 1200, 'category' => 'Electronics'],
    ['id' => 2, 'name' => 'Mouse', 'price' => 25, 'category' => 'Electronics'],
    ['id' => 3, 'name' => 'Desk', 'price' => 300, 'category' => 'Furniture'],
];

// Filter with fluent API
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(5)
    ->get();
// [['id' => 1, 'name' => 'Laptop', 'price' => 1200, ...]]

// Get first result or null
$laptop = DataFilter::query($products)
    ->where('name', 'LIKE', 'Lap%')
    ->first();

// Count results
$count = DataFilter::query($products)
    ->where('price', 'BETWEEN', [100, 500])
    ->count(); // 1
```

#### 🔥 **Complex Nested Mapping with Automatic Relations**

Map complex nested structures directly to Eloquent Models or Doctrine Entities with **automatic relation detection**:

```php
use event4u\DataHelpers\DataMapper;

// Source: JSON from API or file
$jsonData = [
    'company' => [
        'name' => 'TechCorp Solutions',
        'email' => 'info@techcorp.example',
        'founded_year' => 2015,
        'departments' => [
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 5000000.00,
                'employee_count' => 120,
            ],
            [
                'name' => 'Sales',
                'code' => 'SAL',
                'budget' => 3000000.00,
                'employee_count' => 80,
            ],
        ],
        'projects' => [
            [
                'name' => 'Cloud Migration',
                'code' => 'PROJ-001',
                'budget' => 2500000.00,
                'status' => 'active',
            ],
        ],
    ],
];

// Target: Eloquent Model or Doctrine Entity
$company = new Company();

// Mapping: Nested structure with wildcards
$mapping = [
    'name' => '{{ company.name }}',
    'email' => '{{ company.email }}',
    'founded_year' => '{{ company.founded_year }}',
    // Automatic relation mapping - DataMapper detects HasMany/OneToMany relations!
    'departments' => [
        '*' => [
            'name' => '{{ company.departments.*.name }}',
            'code' => '{{ company.departments.*.code }}',
            'budget' => '{{ company.departments.*.budget }}',
            'employee_count' => '{{ company.departments.*.employee_count }}',
        ],
    ],
    'projects' => [
        '*' => [
            'name' => '{{ company.projects.*.name }}',
            'code' => '{{ company.projects.*.code }}',
            'budget' => '{{ company.projects.*.budget }}',
            'status' => '{{ company.projects.*.status }}',
        ],
    ],
];

// Map in one call - relations are automatically created and linked!
$result = DataMapper::map($jsonData, $company, $mapping);

// Result: Fully populated Company with related Departments and Projects
$result->getName();                           // 'TechCorp Solutions'
$result->getDepartments()->count();           // 2
$result->getDepartments()[0]->getName();      // 'Engineering'
$result->getDepartments()[0]->getBudget();    // 5000000.00 (auto-casted to float)
$result->getProjects()->count();              // 1
$result->getProjects()[0]->getName();         // 'Cloud Migration'

// Works with both Eloquent Models and Doctrine Entities!
// - Eloquent: Uses setRelation() for HasMany/BelongsTo
// - Doctrine: Uses Collection methods for OneToMany/ManyToOne
```

**Key Features:**

- ✅ **Automatic Relation Detection** - Detects Eloquent/Doctrine relations without configuration
- ✅ **Type Casting** - Automatically casts values (string → int/float/bool) based on setter types
- ✅ **Snake_case → camelCase** - Converts `employee_count` → `setEmployeeCount()`
- ✅ **Nested Wildcards** - Map arrays of objects with `*` notation
- ✅ **Framework Agnostic** - Works with Laravel, Symfony, or standalone PHP

---

## 🎯 Core Features

### Dot-Notation Paths with Wildcards

Access deeply nested data without writing loops:

```php
$data = [
    'company' => [
        'departments' => [
            ['name' => 'Engineering', 'employees' => [['name' => 'Alice'], ['name' => 'Bob']]],
            ['name' => 'Sales', 'employees' => [['name' => 'Charlie']]],
        ],
    ],
];

$accessor = new DataAccessor($data);

// Single wildcard
$deptNames = $accessor->get('company.departments.*.name');
// ['Engineering', 'Sales']

// Multi-level wildcards
$allEmployees = $accessor->get('company.departments.*.employees.*.name');
// ['Alice', 'Bob', 'Charlie']
```

### Works with Multiple Data Types

```php
// Arrays
$accessor = new DataAccessor(['user' => ['name' => 'Alice']]);

// Objects
$accessor = new DataAccessor((object)['user' => (object)['name' => 'Alice']]);

// JSON strings
$accessor = new DataAccessor('{"user":{"name":"Alice"}}');

// XML strings
$accessor = new DataAccessor('<root><user><name>Alice</name></user></root>');

// Laravel Collections (if illuminate/support is installed)
$accessor = new DataAccessor(collect(['user' => ['name' => 'Alice']]));

// Doctrine Collections (if doctrine/collections is installed)
$accessor = new DataAccessor(new ArrayCollection(['user' => ['name' => 'Alice']]));
```

### Type-Safe Getters

```php
$accessor = new DataAccessor(['age' => '25', 'active' => 'true']);

$age = $accessor->getInt('age');        // 25 (int)
$active = $accessor->getBool('active'); // true (bool)
$name = $accessor->getString('name', 'Unknown'); // 'Unknown' (default)
```

## 🚀 Advanced Features

### Pipeline API - Compose Filters

Build reusable data transformation pipelines:

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\{TrimStrings, LowercaseEmails, SkipEmptyValues};

$source = [
    'user' => [
        'name' => '  Alice  ',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'phone' => '',
    ],
];

$mapping = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'phone' => '{{ user.phone }}',
    ],
];

$result = DataMapper::pipe([
    new TrimStrings(),
    new LowercaseEmails(),
    new SkipEmptyValues(),
])->map($source, [], $mapping);

// Result: {
//     "profile": {
//         "name": "Alice",
//         "email": "alice@example.com"
//         // phone is skipped (empty)
//     }
// }
```

**Built-in filters:** `TrimStrings`, `DecodeHtmlEntities`, `LowercaseEmails`, `SkipEmptyValues`, `UppercaseStrings`, `ConvertToNull`

👉 [Create custom filters](docs/data-mapper-pipeline.md#creating-custom-filters)

### DataFilter - Query and Filter Data

Filter arrays with a fluent, SQL-like API supporting both direct mode and wildcard mode:

```php
use event4u\DataHelpers\DataFilter;

$products = [
    ['id' => 1, 'name' => 'Laptop Pro', 'price' => 1200, 'category' => 'Electronics', 'stock' => 5],
    ['id' => 2, 'name' => 'Mouse', 'price' => 25, 'category' => 'Electronics', 'stock' => 50],
    ['id' => 3, 'name' => 'Desk', 'price' => 300, 'category' => 'Furniture', 'stock' => 10],
];

// Direct Mode - Filter existing data
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(5)
    ->get();

// Wildcard Mode - Build templates with QueryBuilder
use event4u\DataHelpers\DataMapper;

$data = ['products' => $products];
$result = DataMapper::query()
    ->source('products', $data)
    ->where('{{ products.*.category }}', '=', 'Electronics')
    ->where('{{ products.*.price }}', '>', 100)
    ->orderBy('{{ products.*.price }}', 'DESC')
    ->get();
```

**Supported operators:** `WHERE`, `WHERE IN`, `WHERE NULL`, `WHERE NOT NULL`, `LIKE`, `BETWEEN`, `ORDER BY`, `LIMIT`, `OFFSET`, `DISTINCT`

**Fluent API methods:** `where()`, `whereIn()`, `whereNull()`, `whereNotNull()`, `like()`, `between()`, `orderBy()`, `limit()`, `offset()`,
`distinct()`, `first()`, `count()`

**Custom operators:** Add your own operators with `addOperator()`

👉 [See full DataFilter documentation](docs/data-filter.md)

### Template Expressions - Powerful Mapping

Use Twig-like expressions **in all mapping methods** (`map()`, `mapFromFile()`, `mapFromTemplate()`):

```php
// Works in mapFromTemplate()
$template = [
    'user' => [
        'id' => '{{ user.id }}',
        'name' => '{{ user.firstName | ucfirst }} {{ user.lastName | ucfirst }}',
        'email' => '{{ user.email | lower | trim }}',
        'role' => '{{ user.role | upper ?? "USER" }}',
        'tags' => '{{ user.tags }}',
        'tagCount' => '{{ user.tags | count }}',
    ],
];

$sources = [
    'user' => [
        'id' => 123,
        'firstName' => 'alice',
        'lastName' => 'smith',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'role' => null,
        'tags' => ['php', 'laravel'],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);

// Also works in map() and mapFromFile()!
$source = ['name' => 'alice', 'email' => null];
$mapping = [
    'name' => '{{ name | ucfirst }}',
    'email' => '{{ email | default:"no-email@example.com" }}',
];
$result = DataMapper::map($source, [], $mapping);
// ['name' => 'Alice', 'email' => 'no-email@example.com']
```

**18 built-in filters:** `lower`, `upper`, `trim`, `decode_html`, `ucfirst`, `ucwords`, `count`, `first`, `last`, `keys`, `values`,
`reverse`, `sort`, `replace`,
`unique`, `join`, `json`, `default`, `between`, `strip_tags`

👉 [See all filters and create custom ones](docs/filters.md#creating-custom-filters)

### AutoMap - Automatic Property Mapping

Automatically map properties with matching names:

```php
$source = [
    'id' => 1,
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'extra' => 'ignored',
];

$target = [
    'id' => null,
    'name' => null,
    'email' => null,
];

$result = DataMapper::autoMap($source, $target);
// ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']
```

### Reverse Mapping - Bidirectional Data Transformation

Use the same mapping definition to transform data in both directions:

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\ReverseDataMapper;

// Define mapping once
$mapping = [
    'profile.name' => '{{ user.name }}',
    'profile.email' => '{{ user.email }}',
];

// Forward: user -> profile
$user = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$profile = DataMapper::map($user, [], $mapping);
// ['profile' => ['name' => 'John', 'email' => 'john@example.com']]

// Reverse: profile -> user (using the SAME mapping!)
$profile = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$user = ReverseDataMapper::map($profile, [], $mapping);
// ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
```

**Perfect for:**

- DTO ↔ Domain Model conversion
- API Request/Response transformation
- Form data binding
- Bidirectional synchronization

📖 **[Full Reverse Mapping Documentation](docs/reverse-mapping.md)**

---

### MappedDataModel - Laravel-Style Request Binding

Automatically map and transform request data with type safety and validation.

```php
use event4u\DataHelpers\MappedDataModel;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToInteger;

class ProductModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'product_id' => '{{ request.id }}',
            'name' => '{{ request.name }}',
            'price' => '{{ request.price }}',
        ];
    }

    protected function pipes(): array
    {
        return [new TrimStrings(), new CastToInteger()];
    }
}

// Use in controller
$product = new ProductModel(['id' => '12345', 'name' => '  Mouse  ']);
```

📖 **[Full MappedDataModel Documentation](docs/mapped-data-model.md)**

📖 **[All Filters](docs/filters.md)**

---

### SimpleDTO - Immutable Data Transfer Objects

Create type-safe, immutable DTOs with automatic JSON serialization:

```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

// Create from array
$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Access properties
echo $user->name; // 'John Doe'

// Convert to array or JSON
$array = $user->toArray();
$json = json_encode($user);

// Works seamlessly with DataMapper
$mappedData = DataMapper::source($apiResponse)
    ->template(['name' => '{{ user.full_name }}', 'email' => '{{ user.email }}'])
    ->map()
    ->toArray();
$dto = UserDTO::fromArray($mappedData);
```

**Features:**
- ✅ Immutable by design with readonly properties
- ✅ Type-safe with full PHP type hinting
- ✅ JSON serializable out of the box
- ✅ Supports nested DTOs
- ✅ PHPStan Level 9 compliant

📖 **[Full SimpleDTO Documentation](docs/simple-dto.md)** • [Examples](examples/23-simple-dto.php)

---

### Query Builder - Laravel-Style Fluent Interface

Build complex data queries with an intuitive, chainable API:

```php
use event4u\DataHelpers\DataMapper;

$products = [
    ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'Electronics', 'price' => 1299, 'stock' => 5],
    ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 29, 'stock' => 50],
    ['id' => 3, 'name' => 'Standing Desk', 'category' => 'Furniture', 'price' => 299, 'stock' => 8],
    ['id' => 4, 'name' => 'Ergonomic Chair', 'category' => 'Furniture', 'price' => 249, 'stock' => 12],
];

// Simple query with WHERE, ORDER BY, and LIMIT
$result = DataMapper::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();
// [['id' => 1, 'name' => 'Laptop Pro', ...]]

// Complex query with GROUP BY and aggregations
$stats = DataMapper::query()
    ->source('products', $products)
    ->groupBy('category', [
        'total_products' => ['COUNT'],
        'avg_price' => ['AVG', 'price'],
        'total_stock' => ['SUM', 'stock'],
    ])
    ->get();
// [
//     ['category' => 'Electronics', 'total_products' => 2, 'avg_price' => 664, 'total_stock' => 55],
//     ['category' => 'Furniture', 'total_products' => 2, 'avg_price' => 274, 'total_stock' => 20]
// ]

// Combine with pipeline for data transformation
$result = DataMapper::pipeQuery([
        new TrimStrings(),
    ])
    ->source('products', $products)
    ->where('stock', '>', 0)
    ->orderBy('price', 'ASC')
    ->get();
```

**Features:**

- ✅ WHERE with comparison operators (`=`, `!=`, `<>`, `>`, `<`, `>=`, `<=`)
- ✅ Advanced WHERE conditions:
    - `between(field, min, max)` - Value between min and max
    - `notBetween(field, min, max)` - Value NOT between min and max
    - `whereIn(field, values)` - Value in array
    - `whereNotIn(field, values)` - Value NOT in array
    - `whereNull(field)` / `whereNotNull(field)` - NULL checks
    - `exists(field)` / `notExists(field)` - Field existence checks
    - `like(field, pattern)` - Pattern matching with wildcards
- ✅ Nested WHERE with closures for complex AND/OR logic
- ✅ ORDER BY, LIMIT, OFFSET for sorting and pagination
- ✅ GROUP BY with aggregations (COUNT, SUM, AVG, MIN, MAX, etc.)
- ✅ HAVING clause for filtering grouped results
- ✅ DISTINCT operator for unique values
- ✅ Pipeline integration for data transformation
- ✅ Method chaining in any order

📖 **[Full Query Builder Documentation](docs/query-builder.md)** • [Examples](examples/18-query-builder.php)

## 🔧 Framework Support

This package works with **any PHP 8.2+ project**. Framework support is **optional** and **automatically detected**.

### Standalone PHP (No dependencies)

✅ Arrays, Objects, JSON, XML

### Laravel 9+ (Optional)

```bash
composer require illuminate/support illuminate/database
```

✅ Collections, Eloquent Models, Arrayable interface

### Symfony/Doctrine (Optional)

```bash
composer require doctrine/collections doctrine/orm
```

✅ Doctrine Collections, Entities

### Mixed Environments

Use Laravel and Doctrine together - automatic detection handles both!

📖 **[Full framework integration guide](docs/optional-dependencies.md)** with compatibility matrix and examples

---

## 📖 Documentation

### Comprehensive Guides

- **[Data Accessor](docs/data-accessor.md)** - Read nested data with wildcards, Collections, and Models
- **[Data Mutator](docs/data-mutator.md)** - Write, merge, and unset nested values with wildcards
- **[Data Filter](docs/data-filter.md)** - Filter and query data with a fluent API (direct mode and wildcard mode)
- **[Data Mapper](docs/data-mapper.md)** - Map between structures with templates, transforms, and hooks
    - **[Query Builder](docs/query-builder.md)** - Laravel-style fluent interface for building queries (WHERE, ORDER BY, LIMIT, GROUP BY,
      etc.)
        - **[Wildcard Operators](docs/wildcard-operators.md)** - Filter, sort, limit, group, and transform arrays (WHERE, ORDER BY, LIMIT,
          OFFSET, DISTINCT, LIKE, GROUP BY)
        - **[GROUP BY Operator](docs/group-by-operator.md)** - Group data with aggregations (COUNT, SUM, AVG, MIN, MAX, etc.) and HAVING
          filters
- **[Data Mapper Pipeline](docs/data-mapper-pipeline.md)** - Compose filters for data transformation
- **[Reverse Mapping](docs/reverse-mapping.md)** - Bidirectional data transformation using the same mapping definition
- **[Template Expressions](docs/template-expressions.md)** - Powerful expression engine with filters and defaults
- **[Filters](docs/filters.md)** - All built-in filters and how to create custom ones
    - **[Callback Filters](docs/callback-filters.md)** - Custom transformations using closures with full context access
- **[Mapped Data Model](docs/mapped-data-model.md)** - Laravel-style request binding with type safety
- **[SimpleDTO](docs/simple-dto.md)** - Immutable Data Transfer Objects with JSON serialization
- **[Exception Handling](docs/exception-handling.md)** - Error handling, collection, and debugging
- **[Dot-Path Syntax](docs/dot-path.md)** - Path notation reference and best practices
- **[Optional Dependencies](docs/optional-dependencies.md)** - Framework integration guide
- **[Framework Integration](docs/framework-integration.md)** - Deep dive into Laravel, Symfony, and Doctrine support
- **[Configuration](docs/configuration.md)** - Performance mode and environment configuration
- **[Architecture](docs/architecture.md)** - Internal design and extension points
- **[Types](docs/types.md)** - Type system and casting behavior
- **[Support Helpers](docs/support.md)** - Framework abstraction layers (CollectionHelper, ModelHelper, etc.)
- **[Enum Support](docs/enum-support.md)** - Working with PHP 8.1+ enums

### Helpers

- **[EnvHelper](docs/envhelper.md)** - Framework-agnostic environment variable access with type casting
- **[MathHelper](docs/mathhelper.md)** - High-precision mathematical operations using BCMath
- **[ConfigHelper](docs/config-helper.md)** - Configuration management utilities

### Development & Contributing

- **[Contributing](docs/contributing.md)** - How to contribute to this project
- **[Benchmarks](docs/benchmarks.md)** - Performance benchmarks and optimization tips
- **[Scripts](docs/scripts.md)** - Development scripts and automation
- **[Test with Versions](docs/test-with-versions.md)** - Testing across PHP and framework versions
- **[E2E Tests](tests-e2e/README.md)** - End-to-end tests with real framework integrations
- **[Examples](docs/examples.md)** - Additional code examples and use cases

### Runnable Examples

- [01-data-accessor.php](examples/01-data-accessor.php) - Basic array access with wildcards
- [02-data-mutator.php](examples/02-data-mutator.php) - Mutating arrays
- [03-data-mapper-simple.php](examples/03-data-mapper-simple.php) - Simple mapping
- [04-data-mapper-with-hooks.php](examples/04-data-mapper-with-hooks.php) - Advanced mapping with hooks
- [05-data-mapper-pipeline.php](examples/05-data-mapper-pipeline.php) - Pipeline API with filters
- [06-laravel.php](examples/06-laravel.php) - Laravel Collections, Eloquent Models
- [07-symfony-doctrine.php](examples/07-symfony-doctrine.php) - Doctrine Collections and Entities
- [08-mapped-data-model.php](examples/08-mapped-data-model.php) - MappedDataModel with validation and type casting
- [09-template-expressions.php](examples/09-template-expressions.php) - Template expressions with filters
- [10-reverse-mapping.php](examples/10-reverse-mapping.php) - Bidirectional mapping with ReverseDataMapper
- [11-exception-handling.php](examples/11-exception-handling.php) - Exception handling modes and best practices
- [12-wildcard-where-clause.php](examples/12-wildcard-where-clause.php) - Filter, sort, and paginate wildcard arrays
- [13-custom-wildcard-operators.php](examples/13-custom-wildcard-operators.php) - Register custom wildcard operators
- [14-distinct-like-operators.php](examples/14-distinct-like-operators.php) - DISTINCT and LIKE operators
- [15-group-by-aggregations.php](examples/15-group-by-aggregations.php) - GROUP BY with aggregations
- [16-query-builder.php](examples/16-query-builder.php) - Query Builder with fluent interface
- [15-query-builder.php](examples/15-query-builder.php) - Laravel-style Query Builder with WHERE, ORDER BY, LIMIT, GROUP BY, etc.
- [20-data-filter.php](examples/20-data-filter.php) - DataFilter with WHERE, ORDER BY, LIMIT, first(), count()
- [21-custom-operators.php](examples/21-custom-operators.php) - Custom operators for DataFilter and QueryBuilder
- [22-complex-queries.php](examples/22-complex-queries.php) - Complex queries with multiple operators chained
- [23-simple-dto.php](examples/23-simple-dto.php) - Immutable DTOs with JSON serialization and DataMapper integration

---

## 🔍 Common Use Cases

### API Response Transformation

```php
// Transform external API response to your internal format
$apiResponse = $client->get('/users');
$mapping = [
    'users' => [
        '*' => [
            'userId' => 'data.*.id',
            'email' => 'data.*.attributes.email',
            'name' => 'data.*.attributes.profile.name',
        ],
    ],
];
$result = DataMapper::map($apiResponse, [], $mapping);
```

### Database Migration

```php
// Map old database structure to new schema
$oldData = $oldDb->query('SELECT * FROM legacy_users');
$mapping = [
    'profile' => [
        'firstName' => 'first_name',
        'lastName' => 'last_name',
    ],
    'contact' => [
        'email' => 'email_address',
    ],
];
foreach ($oldData as $row) {
    $newData = DataMapper::map($row, [], $mapping);
    $newDb->insert('users', $newData);
}
```

### Form Data Normalization

```php
// Clean and normalize user input
$formData = $_POST;
$result = DataMapper::pipe([
    new TrimStrings(),
    new LowercaseEmails(),
    new SkipEmptyValues(),
])->map($formData, [], $mapping);
```

---

## 🧪 Testing & Quality

- ✅ **1200+ tests** with 3100+ assertions
- ✅ **PHPStan Level 9** - Highest static analysis level
- ✅ **100% type coverage** - All methods fully typed
- ✅ **Pest** - Modern testing framework
- ✅ **Continuous Integration** - Automated testing

---

## ⚡ Performance

All operations are highly optimized and run in microseconds:

<!-- BENCHMARK_RESULTS_START -->

### DataAccessor

| Operation         | Time     | Description                                                   |
|-------------------|----------|---------------------------------------------------------------|
| Simple Get        | 0.211μs  | Get value from flat array                                     |
| Nested Get        | 0.272μs  | Get value from nested path                                    |
| Wildcard Get      | 4.303μs  | Get values using single wildcard                              |
| Deep Wildcard Get | 48.423μs | Get values using multiple wildcards (10 depts × 20 employees) |
| Typed Get String  | 0.235μs  | Get typed string value                                        |
| Typed Get Int     | 0.234μs  | Get typed int value                                           |
| Create Accessor   | 0.053μs  | Instantiate DataAccessor                                      |

### DataMutator

| Operation      | Time    | Description                             |
|----------------|---------|-----------------------------------------|
| Simple Set     | 0.450μs | Set value in flat array                 |
| Nested Set     | 0.716μs | Set value in nested path                |
| Deep Set       | 0.846μs | Set value creating new nested structure |
| Multiple Set   | 1.257μs | Set multiple values at once             |
| Merge          | 0.719μs | Deep merge arrays                       |
| Unset          | 0.694μs | Remove single value                     |
| Multiple Unset | 1.093μs | Remove multiple values                  |

### DataMapper

| Operation         | Time    | Description                    |
|-------------------|---------|--------------------------------|
| Simple Mapping    | 6.466μs | Map flat structure             |
| Nested Mapping    | 7.049μs | Map nested structure           |
| Auto Map          | 7.449μs | Automatic field mapping        |
| Map From Template | 7.137μs | Map using template expressions |

### DTO Serialization Comparison

Comparison of DataMapper vs Symfony Serializer for mapping nested JSON to DTOs:

| Method                   | Time     | vs Symfony        | Description                                 |
|--------------------------|----------|-------------------|---------------------------------------------|
| Manual Mapping           | 0.350μs  | **273.7x faster** | Direct DTO constructor (baseline)           |
| Data Mapper Simple Paths | 16.875μs | **5.7x faster**   | DataMapper with simple path mapping         |
| Data Mapper Template     | 25.585μs | **3.7x faster**   | DataMapper with template syntax ({{ ... }}) |
| Symfony Serializer Array | 95.666μs |                   | Symfony Serializer from array               |
| Symfony Serializer Json  | 90.359μs |                   | Symfony Serializer from JSON                |

<!-- BENCHMARK_RESULTS_END -->

**Key Insights:**

- Simple and nested access is extremely fast (~0.3-0.4μs)
- Wildcards add minimal overhead (~5μs for single level)
- All mutation operations are sub-microsecond
- Mapping operations are in the 5-7μs range

Run benchmarks yourself: `composer benchmark`

---

## 📋 Requirements

- **PHP 8.2+**
- **Optional:** Laravel 9+, Symfony 5+, Doctrine Collections 1.6+, Doctrine ORM 2.10+

---

## 🤝 Contributing

Contributions are welcome! Please see [docs/contributing.md](docs/contributing.md) for details.

---

## 💖 Sponsoring

This package is part of the **event4u** ecosystem - a comprehensive event management platform. Your sponsorship helps us:

- 🚀 **Develop event4u** - The next-generation event management app
- 📦 **Maintain open-source packages** - Like this Data Helpers library
- 🔧 **Build new tools** - More packages and utilities for the PHP community
- 📚 **Improve documentation** - Better guides and examples
- 🐛 **Fix bugs faster** - Dedicated time for maintenance and support

### Support the Development

<p align="left">
  <a href="https://github.com/sponsors/matze4u">
    <img src="https://img.shields.io/badge/Sponsor-@matze4u-ea5027?style=for-the-badge&logo=github-sponsors&logoColor=white" alt="Sponsor @matze4u" />
  </a>
  &nbsp;&nbsp;
  <a href="https://github.com/sponsors/event4u-app">
    <img src="https://img.shields.io/badge/Sponsor-event4u--app-ea5027?style=for-the-badge&logo=github-sponsors&logoColor=white" alt="Sponsor event4u-app" />
  </a>
</p>

Every contribution, no matter how small, makes a difference and is greatly appreciated! 🙏

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🌟 Show Your Support

If this package helps you, please consider:

- ⭐ Giving it a star on GitHub
- 💖 [Sponsoring the development](https://github.com/sponsors/event4u-app)
- 📢 Sharing it with others
