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

**Framework-agnostic PHP library with deep framework integration – get the power of framework-specific solutions without the lock-in.**

Transform complex data structures, create type-safe DTOs and simplify data operations with expressive syntax. Works standalone in **Pure PHP** or with **deep integration** for Laravel and Symfony. Includes DataMapper, SimpleDto/LiteDto, DataAccessor/Mutator/Filter and utility helpers (MathHelper, EnvHelper, etc.).

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

**🎯 Framework-Agnostic + Deep Integration** • Pure PHP with **zero dependencies** • Optional **Laravel** & **Symfony** integration • No framework lock-in

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

- **🎯 Framework-Agnostic + Deep Integration** - Pure PHP with zero dependencies, optional deep Laravel/Symfony integration
- **Type-Safe** - PHPStan Level 9 compliant with 4200+ tests
<!-- BENCHMARK_README_FAST_START -->

- **Fast** - SimpleDto with #[UltraFast] is up to 14.7x faster than Other Serializer
<!-- BENCHMARK_README_FAST_END -->
- **Zero Dependencies** - No required dependencies, optional framework integrations
- **No Framework Lock-In** - Use framework features without being tied to a framework
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

## 🔌 Framework Integration

**The best of both worlds:** Use Data Helpers as a **standalone library** in pure PHP, or leverage **deep framework integration** for Laravel and Symfony – without framework lock-in.

### 🎯 Framework-Agnostic Core

**Zero dependencies required.** Works out of the box with:
- ✅ **Pure PHP** - Arrays, objects, JSON, XML
- ✅ **Any Framework** - No framework-specific code required
- ✅ **Portable** - Move between frameworks without code changes

```php
// Works everywhere - no framework needed
$dto = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$json = json_encode($dto);
```

### 🚀 Optional Deep Integration

**When you need it:** Add framework-specific features without changing your core code.

#### Laravel Integration (Optional)

```php
// 1. Controller Injection - Automatic validation & filling
class UserController extends Controller
{
    public function store(UserDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled from request
        $user = User::create($dto->toArray());
        return response()->json($user, 201);
    }
}

// 2. Eloquent Model Integration
$user = User::find(1);
$dto = UserDto::fromModel($user);  // From Model
$dto->toModel($user);              // To Model

// 3. Laravel-Specific Attributes
class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]  // Only when authenticated
        public readonly ?string $email = null,

        #[WhenCan('edit-posts')]  // Only with permission
        public readonly ?string $editUrl = null,

        #[WhenRole('admin')]  // Only for admins
        public readonly ?array $adminPanel = null,
    ) {}
}

// 4. Artisan Commands
php artisan make:dto UserDto
php artisan dto:typescript
php artisan dto:migrate-spatie
```

#### Symfony Integration (Optional)

```php
// 1. Controller Injection - Automatic validation & filling
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function create(UserDto $dto): JsonResponse
    {
        // $dto is automatically validated and filled from request
        $user = new User();
        $dto->toEntity($user);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->json($user, 201);
    }
}

// 2. Doctrine Entity Integration
$user = $this->entityManager->find(User::class, 1);
$dto = UserDto::fromEntity($user);  // From Entity
$dto->toEntity($user);              // To Entity

// 3. Symfony-Specific Attributes
class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenGranted('ROLE_ADMIN')]  // Only with permission
        public readonly ?string $email = null,

        #[WhenSymfonyRole('ROLE_MODERATOR')]  // Only for moderators
        public readonly ?array $moderationPanel = null,
    ) {}
}

// 4. Console Commands
php bin/console make:dto UserDto
php bin/console dto:typescript
```

### 💡 Key Integration Features

| Feature | Pure PHP | Laravel | Symfony |
|---------|----------|---------|---------|
| **DTOs & Validation** | ✅ | ✅ | ✅ |
| **Controller Injection** | ❌ | ✅ Auto | ✅ Auto |
| **Request Validation** | ✅ Manual | ✅ Auto | ✅ Auto |
| **Model/Entity Mapping** | ❌ | ✅ Eloquent | ✅ Doctrine |
| **Framework Attributes** | ❌ | ✅ Auth/Can/Role | ✅ Granted/Role |
| **Code Generation** | ❌ | ✅ Artisan | ✅ Console |
| **TypeScript Export** | ❌ | ✅ | ✅ |

**The Power:** Get framework-specific features when you need them, without framework dependencies in your core code.

📖 **[Laravel Integration Guide](https://event4u-app.github.io/data-helpers/framework-integration/laravel/)** • [Symfony Integration Guide](https://event4u-app.github.io/data-helpers/framework-integration/symfony/)

---

## ⚡ Core Components

**The heart of this library:** Data mapping and DTOs for transforming and structuring data, plus powerful data manipulation tools.

### 1️⃣ DataAccessor - Read & Transform Data

Access deeply nested data with dot notation, wildcards, and powerful transformation methods:

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'age' => '30', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'age' => '25', 'email' => 'bob@example.com'],
    ],
];

$accessor = new DataAccessor($data);

// Generic get() - returns mixed
$emails = $accessor->get('users.*.email');
// ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com']

// Type-safe getters - strict type conversion with nullable return
$name = $accessor->getString('users.0.name');  // 'Alice'
$age = $accessor->getInt('users.0.age');       // 30 (string → int)
$missing = $accessor->getString('users.0.phone');  // null

// Collection getters for wildcards - returns DataCollection instances
$ages = $accessor->getIntCollection('users.*.age');  // DataCollection<int>
$names = $accessor->getStringCollection('users.*.name');  // DataCollection<string>

// Transformation methods - filter, map, reduce directly on DataAccessor
$filtered = $accessor->filter(fn($user) => $user['age'] > 25);  // [['name' => 'Alice', ...]]
$mapped = $accessor->map(fn($user) => $user['name']);  // ['Alice', 'Bob']
$sum = $accessor->reduce(fn($carry, $user) => $carry + $user['age'], 0);  // 55

// first() and last() with optional callback
$firstUser = $accessor->first();  // ['name' => 'Alice', ...]
$lastAdult = $accessor->last(fn($user) => $user['age'] >= 18);

// Lazy evaluation for large datasets
foreach ($accessor->lazyFilter(fn($user) => $user['age'] > 25) as $user) {
    // Process items one at a time without loading all into memory
}
```

📖 **[DataAccessor Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-accessor/)**

### 2️⃣ DataCollection - Type-Safe Collections

Framework-independent collection class with fluent API. Uses DataAccessor for reading, DataMutator for writing, and DataFilter for SQL-like querying:

```php
use event4u\DataHelpers\DataCollection;

$collection = DataCollection::make([1, 2, 3, 4, 5]);

// Filter, map, reduce with method chaining (delegates to DataAccessor)
$result = $collection
    ->filter(fn($item) => $item > 2)  // [3, 4, 5]
    ->map(fn($item) => $item * 2)     // [6, 8, 10]
    ->reduce(fn($carry, $item) => $carry + $item, 0);  // 24

// Dot-notation read access (via DataAccessor)
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30]],
    ['user' => ['name' => 'Bob', 'age' => 25]],
]);
$name = $collection->get('0.user.name');  // 'Alice'

// Dot-notation write access (via DataMutator) - modifies in-place
$collection
    ->set('0.user.city', 'Berlin')
    ->merge('1.user', ['city' => 'Munich', 'country' => 'Germany'])
    ->transform('0.user.name', fn($name) => strtoupper($name));

// SQL-like filtering (via DataFilter) - returns new DataCollection
$users = DataCollection::make([
    ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'],
    ['name' => 'Bob', 'age' => 25, 'city' => 'Munich'],
    ['name' => 'Charlie', 'age' => 35, 'city' => 'Berlin'],
]);
$filtered = $users
    ->query()
    ->where('age', '>', 25)
    ->where('city', 'Berlin')
    ->orderBy('age', 'DESC')
    ->get();  // Returns new DataCollection

// Lazy evaluation for large datasets
foreach ($collection->lazyFilter(fn($item) => $item > 2) as $item) {
    // Process items one at a time without loading all into memory
}
```

📖 **[DataCollection Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-collection/)**

### 3️⃣ DataMutator - Modify Nested Data

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

Create type-safe, immutable Data Transfer Objects with automatic type casting and multi-format serialization (JSON, XML, YAML, CSV):

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

// Multi-format serialization
$json = $user->toJson();  // JSON
$xml = $user->toXml();    // XML
$yaml = $user->toYaml();  // YAML
$csv = $user->toCsv();    // CSV
```

📖 **[SimpleDto Documentation](https://event4u-app.github.io/data-helpers/simple-dto/introduction/)**

### 6️⃣ LiteDto - Ultra-Fast Dtos

Create ultra-fast, minimalistic DTOs with essential features:

```php
use event4u\DataHelpers\LiteDto;
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

Map between different data structures with templates. Supports multi-format output (JSON, XML, YAML, CSV):

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

**⚠️ XML Files:** When loading XML files with `sourceFile()`, the root element name is preserved. Always include it in your paths: `'{{ company.name }}'` for `<company><name>...</name></company>`.

📖 **[DataMapper Documentation](https://event4u-app.github.io/data-helpers/main-classes/data-mapper/)**

### 7️⃣ Utility Helpers - Common Data Operations

Simplify common data operations with specialized helper classes:

```php
use event4u\DataHelpers\Helpers\MathHelper;
use event4u\DataHelpers\Helpers\EnvHelper;

// Math operations with precision
$result = MathHelper::add('10.5', '20.3', 2);  // 30.8
$average = MathHelper::average([10, 20, 30]);  // 20.0
$sum = MathHelper::sum([5, 10, 15]);  // 30.0

// Environment variable access with type casting
$debug = EnvHelper::boolean('APP_DEBUG', false);
$port = EnvHelper::integer('APP_PORT', 8080);
$timeout = EnvHelper::float('REQUEST_TIMEOUT', 30.0);
```

**Available Helpers:**
- **MathHelper** - Precision math operations using bcmath (add, subtract, multiply, divide, modulo, powerOf, squareRoot, compare, min, max, sum, average, product, time conversions)
- **EnvHelper** - Type-safe environment variable access with framework detection (get, has, string, integer, float, boolean, array)
- **ConfigHelper** - Singleton configuration manager with framework detection and dot notation (getInstance, get, getBoolean, getInteger, getFloat, getString, getArray, has, set, reset)
- **DotPathHelper** - Dot notation path utilities with wildcard support (segments, buildPrefix, isWildcard, containsWildcard)
- **ObjectHelper** - Deep object cloning with recursion control (copy)

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

- ✅ **4200+ tests** with comprehensive coverage
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
