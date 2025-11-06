<div align="center">
  <a href="https://event4u.app">
    <img alt="event4u Data Helpers" src=".github/assets/banner.png">
  </a>
</div>

# Data Helpers

[![Packagist Version](https://img.shields.io/packagist/v/event4u/data-helpers.svg?style=flat-square&label=packagist)](https://packagist.org/packages/event4u/data-helpers)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb3?logo=php&logoColor=white&style=flat-square)](#installation)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](#license)
[![GitHub Code Quality Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/code-quality.yml?branch=main&label=code%20quality&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/code-quality.yml)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/phpstan.yml)
[![GitHub Test Matrix Action Status](https://img.shields.io/github/actions/workflow/status/event4u-app/data-helpers/test-matrix.yml?branch=main&label=test%20matrix&style=flat-square)](https://github.com/event4u-app/data-helpers/actions/workflows/test-matrix.yml)

**A powerful, framework-agnostic PHP library for data mapping, DTOs and data manipulation utilities.**

Transform complex data structures, create type-safe DTOs and simplify data operations with expressive syntax. Includes DataMapper, SimpleDto/LiteDto, DataAccessor/Mutator/Filter and utility helpers (MathHelper, EnvHelper, etc.).

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

// ...to this clean result in a few lines
$accessor = new DataAccessor($apiResponse);
$emails = $accessor->get('data.departments.*.users.*.email');
// ['alice@example.com', 'bob@example.com', 'charlie@example.com']
```

**Framework-agnostic** • Works with **Laravel**, **Symfony/Doctrine** or **standalone PHP** • Zero required dependencies

📖 **[Full Documentation](https://event4u-app.github.io/data-helpers/)** • [Getting Started](https://event4u-app.github.io/data-helpers/getting-started/quick-start/) • [API Reference](https://event4u-app.github.io/data-helpers/api/)

💖 **[Support the Development](#-sponsoring)** - Help us build better tools for the PHP community

---

## 💡 Why Data Helpers?

### 🎯 Stop Writing Nested Loops

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

### 🚀 Key Benefits

- **Type-Safe** - PHPStan Level 9 compliant with 3500+ tests
<!-- BENCHMARK_README_FAST_START -->

- **Fast** - SimpleDto with #[UltraFast] is up to 14.7x faster than Other Serializer
<!-- BENCHMARK_README_FAST_END -->
- **Framework-Agnostic** - Works with Laravel, Symfony, Doctrine or plain PHP
- **Zero Dependencies** - No required dependencies, optional framework integrations
- **No-Code Mapping** - Store templates in database, create with drag-and-drop editors

---

## 📦 Installation

```bash
composer require event4u/data-helpers
```

**Requirements:** PHP 8.2+

**Framework support** (all optional):
- 🔴 **Laravel** 9+ - Collections, Eloquent Models
- ⚫ **Symfony/Doctrine** 6+ - Collections, Entities
- 🔧 **Standalone PHP** - Works out of the box

📖 **[Installation Guide](https://event4u-app.github.io/data-helpers/getting-started/installation/)** • [Configuration](https://event4u-app.github.io/data-helpers/getting-started/configuration/)

---

## ⚡ Core Components

**The heart of this library:** Data mapping and DTOs for transforming and structuring data, plus powerful data manipulation tools.

### 1️⃣ DataAccessor - Read Nested Data

Access deeply nested data with dot notation and wildcards:

```php
$data = [
    'users' => [
        ['email' => 'alice@example.com'],
        ['email' => 'bob@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$emails = $accessor->get('users.*.email');
// $emails = ['alice@example.com', 'bob@example.com']
```

📖 **[DataAccessor Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-accessor/)**

### 2️⃣ DataMutator - Modify Nested Data

Safely modify nested structures:

```php
$data = ['user' => ['profile' => []]];
DataMutator::make($data)
    ->set('user.profile.name', 'Alice')
    ->merge('user.profile', ['age' => 30]);
// $data is now modified: ['user' => ['profile' => ['name' => 'Alice', 'age' => 30]]]
```

📖 **[DataMutator Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-mutator/)**

### 4️⃣ DataFilter - Query Data

Filter and query data with SQL-like API:

```php
$products = [
    ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1200],
    ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25],
    ['id' => 3, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400],
];

$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->get();
// Result: [Laptop ($1200), Monitor ($400)]
```

📖 **[DataFilter Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-filter/)**

### 5️⃣ SimpleDto - Immutable Dtos

Create type-safe, immutable Data Transfer Objects with automatic type casting by default:

<!-- skip-test: property declaration only -->
```php skip-test
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;

// Default: Automatic type casting enabled
class ReadmeUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
        public readonly AddressDto $address,  // Nested DTO (auto-cast by default)
    ) {}
}

// Automatic type conversion by default
$user = ReadmeUserDto::fromArray([
    'name' => 'John',
    'email' => 'john@example.com',
    'age' => '30',  // String "30" → int 30 (automatic)
    'address' => ['city' => 'Berlin'],  // Array → AddressDto (automatic)
]);

// Disable automatic casting for better performance
#[NoCasts]
class StrictUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,  // Must be int, no conversion
        public readonly AddressDto $address,  // Must be AddressDto instance, no conversion
    ) {}
}
```

📖 **[SimpleDto Documentation](https://event4u-app.github.io/data-helpers/simple-dto/introduction/)**

### 6️⃣ LiteDto - Ultra-Fast Dtos

Create ultra-fast, minimalistic DTOs with essential features:

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;

class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,

        #[MapFrom('email_address')]
        public readonly string $email,

        #[Hidden]
        public readonly string $password,
    ) {}
}

$user = UserDto::from([
    'name' => 'John',
    'email_address' => 'john@example.com',
    'password' => 'secret',
]);

$array = $user->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
// password is hidden
```

**Performance**: LiteDto is **7.6x faster** than SimpleDto Normal (~2.3μs vs ~18.5μs)

📖 **[LiteDto Documentation](https://event4u-app.github.io/data-helpers/lite-dto/introduction/)**

### 3️⃣ DataMapper - Transform Data

Map between different data structures with templates:

```php
$source = [
    'user' => ['name' => 'John Doe', 'email' => 'john@example.com'],
    'orders' => [
        ['id' => 1, 'status' => 'shipped', 'total' => 100],
        ['id' => 2, 'status' => 'pending', 'total' => 50],
        ['id' => 3, 'status' => 'shipped', 'total' => 200],
    ],
];

$result = DataMapper::from($source)
    ->template([
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
    ])
    ->map()
    ->getTarget();
```

**💡 No-Code Data Mapping:** Templates can be stored in a database and created with a drag-and-drop editor - perfect for import wizards, API integrations and ETL pipelines without writing code!

📖 **[DataMapper Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/)**

### 7️⃣ Utility Helpers - Common Data Operations

Simplify common data operations with specialized helper classes:

```php
use event4u\DataHelpers\Helpers\MathHelper;
use event4u\DataHelpers\Helpers\EnvHelper;
use event4u\DataHelpers\Helpers\StringHelper;

// Math operations with precision
$result = MathHelper::add('10.5', '20.3', 2);  // '30.80'
$percentage = MathHelper::percentage(75, 300);  // 25.0

// Environment variable access with type casting
$debug = EnvHelper::getBool('APP_DEBUG', false);
$port = EnvHelper::getInt('APP_PORT', 8080);

// String manipulation
$slug = StringHelper::slug('Hello World!');  // 'hello-world'
$truncated = StringHelper::truncate('Long text...', 10);  // 'Long te...'
```

**Available Helpers:**
- **MathHelper** - Precision math operations (add, subtract, multiply, divide, percentage, round)
- **EnvHelper** - Type-safe environment variable access (getString, getInt, getBool, getFloat, getArray)
- **StringHelper** - String manipulation (slug, truncate, camelCase, snakeCase, studlyCase)
- **ArrayHelper** - Array operations (flatten, pluck, only, except, dot notation)
- **ConfigHelper** - Configuration management with framework detection

📖 **[Helpers Documentation](https://event4u-app.github.io/data-helpers/helpers/overview/)**

---

## 🎯 Advanced Features

### No-Code Data Mapping

**Store templates in database and create mappings without programming:**

```php
// Load template from database (created with drag-and-drop editor)
$template = Mappings::find(3)->template;

$result = DataMapper::from($source)
    ->template($template)
    ->map()
    ->getTarget();
```

**Perfect for:**
- 📥 **Import Wizards** - Let users map CSV/Excel columns to your data model
- 🔌 **API Integration** - Configure API mappings without code changes
- 🏢 **Multi-Tenant Systems** - Each tenant can have custom data mappings
- 🔄 **Dynamic ETL** - Build data transformation pipelines visually
- 📝 **Form Builders** - Map form submissions to different data structures

📖 **[Template-Based Mapping Guide](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/)**

### Complex Nested Mapping

Map complex nested structures to Eloquent Models or Doctrine Entities:

```php
// Automatic relation detection for Eloquent/Doctrine
$company = new Company();
$result = DataMapper::from($jsonData)
    ->target($company)
    ->template([
        'name' => '{{ company.name }}',
        'departments' => [
            '*' => [
                'name' => '{{ company.departments.*.name }}',
                'budget' => '{{ company.departments.*.budget }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

- ✅ Automatic Relation Detection
- ✅ Type Casting (string → int/float/bool)
- ✅ Snake_case → camelCase conversion
- ✅ Nested Wildcards

📖 **[Advanced Mapping Guide](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/)**

### Pipeline API

Transform data with composable filters:

```php
use Tests\Utils\Docu\TrimStrings;
use Tests\Utils\Docu\LowercaseEmails;
use Tests\Utils\Docu\SkipEmptyValues;

$source = ['name' => '  John  ', 'email' => 'JOHN@EXAMPLE.COM'];
$mapping = ['name' => '{{ name }}', 'email' => '{{ email }}'];

$result = DataMapper::from($source)
    ->template($mapping)
    ->pipeline([
        new TrimStrings(),
        new LowercaseEmails(),
        new SkipEmptyValues(),
    ])
    ->map()
    ->getTarget();

// $result = ['name' => 'John', 'email' => 'john@example.com']
```

📖 **[Pipeline Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/pipelines/)**

### Template Expressions

Use Twig-like expressions with 18+ built-in filters:

```php
$mapping = [
    'name' => '{{ user.firstName | ucfirst }} {{ user.lastName | ucfirst }}',
    'email' => '{{ user.email | lower | trim }}',
    'role' => '{{ user.role | upper ?? "USER" }}',
];
```

📖 **[Template Expressions](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/template-expressions/)**

### Query Builder

Laravel-style fluent interface for building queries:

```php
$result = DataMapper::query()
    ->source('products', $data)
    ->where('category', 'Electronics')
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->groupBy('category', ['total' => ['COUNT']])
    ->get();
```

📖 **[Query Builder Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/query-builder/)**

---

## 📚 Documentation

**Comprehensive documentation with guides, examples and API reference is available at:**

🔗 **[event4u-app.github.io/data-helpers](https://event4u-app.github.io/data-helpers/)**

The documentation includes:
- 📖 **Getting Started Guides** - Installation, configuration and quick start tutorials
- 🔧 **Main Classes** - Detailed guides for DataAccessor, DataMutator, DataMapper and DataFilter
- 🎯 **SimpleDto** - Type-safe Dtos with validation, casting and collections
- ⚡ **LiteDto** - Ultra-fast, minimalistic Dtos (7.6x faster than SimpleDto)
- 🚀 **Advanced Features** - Template expressions, query builder, pipelines and reverse mapping
- 🔌 **Framework Integration** - Laravel, Symfony and Doctrine integration guides
- 💡 **90+ Code Examples** - Runnable examples for every feature
- 📊 **Performance Benchmarks** - Optimization tips and benchmark results
- 🔍 **Complete API Reference** - Full API documentation for all classes and methods

---

## 🧪 Testing & Quality

- ✅ **3500+ tests** with comprehensive coverage
- ✅ **PHPStan Level 9** - Highest static analysis level
- ✅ **100% type coverage** - All methods fully typed
- ✅ **Continuous Integration** - Automated testing across PHP 8.2, 8.3, 8.4

📖 **[Contributing Guide](https://event4u-app.github.io/data-helpers/guides/contributing/)** • [Development Setup](https://event4u-app.github.io/data-helpers/guides/development-setup/)

---

## ⚡ Performance

All operations are highly optimized:

<!-- BENCHMARK_README_PERFORMANCE_START -->

- Simple access: ~0.3μs
- Nested access: ~0.3μs
- Wildcards: ~11μs
- **SimpleDto #[UltraFast] is up to 14.7x faster** than Other Serializer
<!-- BENCHMARK_README_PERFORMANCE_END -->

📖 **[Performance Benchmarks](https://event4u-app.github.io/data-helpers/performance/benchmarks/)** • [Optimization Tips](https://event4u-app.github.io/data-helpers/performance/optimization/)

---

## 🤝 Contributing

Contributions are welcome! Please see the [Contributing Guide](https://event4u-app.github.io/data-helpers/guides/contributing/) for details.

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run quality checks
composer quality
```

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
