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

PHPStan Level 9 compliant with 400+ tests. Works reliably with arrays, objects, Collections, Models, JSON, and XML.

### ⚡ **Framework-agnostic with smart detection**

Use it anywhere - Laravel, Symfony, Doctrine, or plain PHP. Framework support is automatically detected at runtime.

---

## 📦 Installation

```bash
composer require event4u/data-helpers
```

**Requirements:** PHP 8.2+

**Framework support** (all optional):

- 🔴 **Laravel** 8+ - Collections, Eloquent Models
- ⚫ **Symfony/Doctrine** - Collections, Entities
- 🔧 **Standalone PHP** - Works out of the box

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

### Pipeline API - Compose Transformers

Build reusable data transformation pipelines:

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\{TrimStrings, LowercaseEmails, SkipEmptyValues};

$source = [
    'user' => [
        'name' => '  Alice  ',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'phone' => '',
    ],
];

$mapping = [
    'profile' => [
        'name' => 'user.name',
        'email' => 'user.email',
        'phone' => 'user.phone',
    ],
];

$result = DataMapper::pipe([
    TrimStrings::class,
    LowercaseEmails::class,
    SkipEmptyValues::class,
])->map($source, [], $mapping);

// Result: {
//     "profile": {
//         "name": "Alice",
//         "email": "alice@example.com"
//         // phone is skipped (empty)
//     }
// }
```

**Built-in transformers:** `TrimStrings`, `LowercaseEmails`, `SkipEmptyValues`, `UppercaseStrings`, `ConvertToNull`

👉 [Create custom transformers](docs/data-mapper-pipeline.md#creating-custom-transformers)

### Template Expressions - Powerful Mapping

Use Twig-like expressions in your templates:

```php
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
// {
//     "user": {
//         "id": 123,
//         "name": "Alice Smith",
//         "email": "alice@example.com",
//         "role": "USER",
//         "tags": ["php", "laravel"],
//         "tagCount": 2
//     }
// }
```

**15 built-in filters:** `lower`, `upper`, `trim`, `ucfirst`, `ucwords`, `count`, `first`, `last`, `keys`, `values`, `reverse`, `sort`,
`unique`, `join`, `json`, `default`

👉 [See all filters and create custom ones](docs/template-expressions.md#custom-filters)

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

---

## 🔧 Framework Support

This package works with **any PHP 8.2+ project**. Framework support is **optional** and **automatically detected**.

### Standalone PHP (No dependencies)

✅ Arrays, Objects, JSON, XML

### Laravel 8+ (Optional)

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
- **[Data Mapper](docs/data-mapper.md)** - Map between structures with templates, transforms, and hooks
- **[Template Expressions](docs/template-expressions.md)** - Powerful expression engine with filters and defaults
- **[Dot-Path Syntax](docs/dot-path.md)** - Path notation reference and best practices
- **[Optional Dependencies](docs/optional-dependencies.md)** - Framework integration guide

### Runnable Examples

- [01-data-accessor.php](examples/01-data-accessor.php) - Basic array access with wildcards
- [02-data-mutator.php](examples/02-data-mutator.php) - Mutating arrays
- [03-data-mapper-simple.php](examples/03-data-mapper-simple.php) - Simple mapping
- [04-data-mapper-with-hooks.php](examples/04-data-mapper-with-hooks.php) - Advanced mapping with hooks
- [05-data-mapper-pipeline.php](examples/05-data-mapper-pipeline.php) - Pipeline API with transformers
- [06-laravel.php](examples/06-laravel.php) - Laravel Collections, Eloquent Models
- [07-symfony-doctrine.php](examples/07-symfony-doctrine.php) - Doctrine Collections and Entities
- [08-template-expressions.php](examples/08-template-expressions.php) - Template expressions with filters

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
    TrimStrings::class,
    LowercaseEmails::class,
    SkipEmptyValues::class,
])->map($formData, [], $mapping);
```

---

## 🧪 Testing & Quality

- ✅ **400+ tests** with 1500+ assertions
- ✅ **PHPStan Level 9** - Highest static analysis level
- ✅ **100% type coverage** - All methods fully typed
- ✅ **Pest** - Modern testing framework
- ✅ **Continuous Integration** - Automated testing

---

## ⚡ Performance

All operations are highly optimized and run in microseconds:

<!-- BENCHMARK_RESULTS_START -->
### DataAccessor

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Get | 0.324μs | Get value from flat array |
| Nested Get | 0.426μs | Get value from nested path |
| Wildcard Get | 5.275μs | Get values using single wildcard |
| Deep Wildcard Get | 89.961μs | Get values using multiple wildcards (10 depts × 20 employees) |
| Typed Get String | 0.365μs | Get typed string value |
| Typed Get Int | 0.361μs | Get typed int value |
| Create Accessor | 0.083μs | Instantiate DataAccessor |

### DataMutator

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Set | 0.588μs | Set value in flat array |
| Nested Set | 0.942μs | Set value in nested path |
| Deep Set | 1.139μs | Set value creating new nested structure |
| Multiple Set | 1.706μs | Set multiple values at once |
| Merge | 0.977μs | Deep merge arrays |
| Unset | 0.896μs | Remove single value |
| Multiple Unset | 1.495μs | Remove multiple values |

### DataMapper

| Operation | Time | Description |
|-----------|------|-------------|
| Simple Mapping | 6.252μs | Map flat structure |
| Nested Mapping | 7.153μs | Map nested structure |
| Auto Map | 6.793μs | Automatic field mapping |
| Map From Template | 5.007μs | Map using template expressions |
<!-- BENCHMARK_RESULTS_END -->

**Key Insights:**
- Simple and nested access is extremely fast (~0.3-0.4μs)
- Wildcards add minimal overhead (~5μs for single level)
- All mutation operations are sub-microsecond
- Mapping operations are in the 5-7μs range

Run benchmarks yourself: `composer bench`

---

## 📋 Requirements

- **PHP 8.2+**
- **Optional:** Laravel 8+, Symfony 5+, Doctrine Collections 1.6+, Doctrine ORM 2.10+

---

## 🤝 Contributing

Contributions are welcome! Please see [docs/contributing.md](docs/contributing.md) for details.

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🌟 Show Your Support

If this package helps you, please consider giving it a ⭐ on GitHub!
