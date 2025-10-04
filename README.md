# Data Helpers (Framework-Agnostic)

[![PHP](https://img.shields.io/badge/PHP-8.2%E2%80%938.3-777bb3?logo=php&logoColor=white)](#requirements)
[![Standalone](https://img.shields.io/badge/Standalone-PHP-8892BF?logo=php&logoColor=white)](#framework-support)
[![Supports](https://img.shields.io/badge/Supports-Laravel-FF2D20?logo=laravel&logoColor=white)](#framework-support)
[![Supports](https://img.shields.io/badge/Supports-Symfony-000000?logo=symfony&logoColor=white)](#framework-support)
[![Supports](https://img.shields.io/badge/Supports-Doctrine-FC6A31?logo=doctrine&logoColor=white)](#framework-support)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](#license)

High-level, well-tested helpers for structured data access, mutation, and mapping using dot-notation paths with wildcard support. Works with
arrays, DTOs/objects, Laravel Collections, Eloquent Models, Doctrine Collections, Doctrine Entities, JSON, and XML.

**🚀 Framework-agnostic**: Works seamlessly with **Laravel**, **Symfony/Doctrine**, or **standalone PHP**. All framework dependencies are
optional and automatically detected.

## ✨ Features at a Glance

### 🎯 Framework Support

- **🔴 Laravel**: Collections, Eloquent Models, Arrayable interface
- **⚫ Symfony/Doctrine**: Collections, Entities (with automatic detection)
- **🔧 Standalone PHP**: Arrays, Objects, JSON, XML
- **🔄 Mixed Environments**: Use Laravel and Doctrine together
- **📦 Zero Required Dependencies**: All framework packages are optional

### 🚀 Core Features

- **Dot-notation paths** with deep multi-level wildcards (e.g. `users.*.profile.*.city`)
- **Consistent API** across arrays, objects, Collections (Laravel/Doctrine), Models/Entities, JSON, XML
- **Mutate data**: set/merge/unset deeply into arrays, DTOs, Collections, Models, and Entities
- **Map between structures**: simple pairs, structured mappings, template-driven
- **AutoMap**: automatic mapping from source to target properties
- **Replace options**: case-insensitive and trimming
- **Hooks system**: typed contexts for mapping lifecycle

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Framework Support](#framework-support)
    - [Compatibility Matrix](#-compatibility-matrix)
- [Quick Start](#quick-start)
    - [DataAccessor](#dataaccessor)
    - [DataMutator](#datamutator)
    - [DataMapper](#datamapper)
    - [Pipeline API (NEW)](#pipeline-api-new)
- [Mapping Templates](#mapping-templates)
    - [mapFromTemplate](#mapfromtemplate)
    - [mapToTargetsFromTemplate](#maptotargetsfromtemplate)
- [AutoMap](#automap)
- [Replace Options](#replace-options)
- [Hooks](#hooks)
- [Options & Behavior](#options--behavior)
- [Contributing](#contributing)
- [License](#license)

**📖 Full documentation with extensive examples:**

- [Data Accessor](docs/data-accessor.md) – Read nested data with wildcards, Collections, and Models
- [Data Mutator](docs/data-mutator.md) – Write, merge, and unset nested values with wildcards
- [Data Mapper](docs/data-mapper.md) – Map between structures with templates, transforms, and hooks
- [Dot-Path Syntax](docs/dot-path.md) – Path notation reference and best practices

💡 **Tip:** The docs contain many real-world examples including deep wildcards, JSON templates, autoMap (source → target), value
replacement, hooks, and common patterns for each helper.

**💻 Code Examples:**

- [examples/01-data-accessor.php](examples/01-data-accessor.php) – Basic array access with wildcards
- [examples/02-data-mutator.php](examples/02-data-mutator.php) – Mutating arrays
- [examples/03-data-mapper-simple.php](examples/03-data-mapper-simple.php) – Simple mapping
- [examples/04-data-mapper-with-hooks.php](examples/04-data-mapper-with-hooks.php) – Advanced mapping with hooks
- [examples/05-data-mapper-pipeline.php](examples/05-data-mapper-pipeline.php) – **NEW:** Pipeline API with transformers
- [examples/06-laravel.php](examples/06-laravel.php) – Laravel Collections, Eloquent Models, Arrayable
- [examples/07-symfony-doctrine.php](examples/07-symfony-doctrine.php) – Doctrine Collections and Entities

## Installation

Use as a local path repository during development.

composer.json (of your host application):

```json
{
    "require": {
        "event4u/data-helpers": "dev-main"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../event4u/data-helpers"
        }
    ]
}
```

Then install:

```bash
composer update event4u/data-helpers -o
```

PSR-4 namespace: `event4u\DataHelpers`

## Framework Support

This package works with **any PHP 8.2+ project** and provides optional support for popular frameworks. All framework dependencies are *
*optional** and **automatically detected** at runtime.

### 🔧 Standalone PHP

```bash
composer require event4u/data-helpers
```

**Works out of the box** with arrays, objects, JSON, and XML. No framework dependencies required.

**Supported types:**

- ✅ Arrays
- ✅ Objects (stdClass, DTOs)
- ✅ JSON strings
- ✅ XML strings
- ✅ JsonSerializable objects

### 🔴 Laravel Projects

```bash
composer require event4u/data-helpers
composer require illuminate/support:^8      # Usually already installed
composer require illuminate/database:^8     # For Eloquent Model support
```

**Full support** for Laravel Collections, Eloquent Models, and Arrayable interface (Laravel 8+).

**Supported types:**

- ✅ `Illuminate\Support\Collection`
- ✅ `Illuminate\Database\Eloquent\Model`
- ✅ `Illuminate\Contracts\Support\Arrayable`
- ✅ All standalone PHP types

**Example:**

```php
$collection = collect(['users' => [['name' => 'John'], ['name' => 'Jane']]]);
$accessor = new DataAccessor($collection);
$names = $accessor->get('users.*.name');  // ['John', 'Jane']
```

### ⚫ Symfony/Doctrine Projects

```bash
composer require event4u/data-helpers
composer require doctrine/collections:^1.6    # For Doctrine Collections
composer require doctrine/orm:^2.10           # For Doctrine Entities
```

**Full support** for Doctrine Collections and Entities with automatic detection (Doctrine Collections 1.6+, ORM 2.10+).

**Supported types:**

- ✅ `Doctrine\Common\Collections\Collection`
- ✅ `Doctrine\Common\Collections\ArrayCollection`
- ✅ Doctrine Entities (any class with Doctrine attributes)
- ✅ All standalone PHP types

**Example:**

```php
$collection = new ArrayCollection(['users' => [['name' => 'John'], ['name' => 'Jane']]]);
$accessor = new DataAccessor($collection);
$names = $accessor->get('users.*.name');  // ['John', 'Jane']
```

### 🔄 Mixed Environments

You can use Laravel and Doctrine types **together** in the same project. The package automatically detects and handles both:

```php
// Works with both Laravel and Doctrine Collections
$laravelCollection = collect([...]);
$doctrineCollection = new ArrayCollection([...]);

$accessor1 = new DataAccessor($laravelCollection);  // Uses Laravel methods
$accessor2 = new DataAccessor($doctrineCollection); // Uses Doctrine methods
```

**📖 See [OPTIONAL_DEPENDENCIES.md](OPTIONAL_DEPENDENCIES.md) for detailed framework integration guide.**

### 📊 Compatibility Matrix

| Feature              | Standalone | With illuminate / support | With illuminate / database | With doctrine / collections | With doctrine / orm |
|----------------------|------------|---------------------------|----------------------------|-----------------------------|---------------------|
| Arrays               | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Objects              | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| JSON/XML             | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Laravel Collections  | ❌ None     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Doctrine Collections | ❌ None     | ❌ None                    | ❌ None                     | ✅ Full                      | ✅ Full              |
| Arrayable Interface  | ❌ None     | ✅ Full                    | ✅ Full                     | ❌ None                      | ❌ None              |
| Eloquent Models      | ❌ None     | ❌ None                    | ✅ Full                     | ❌ None                      | ❌ None              |
| Doctrine Entities    | ❌ None     | ❌ None                    | ❌ None                     | ⚠️ Basic                    | ✅ Full              |

**Legend:**

- ✅ **Full** - Complete functionality with all features
- ⚠️ **Basic** - Limited functionality (e.g., entities without full ORM support)
- ❌ **None** - Not available without the dependency

## Quick Start

### DataAccessor

Read values from various inputs using dot paths and wildcards.

```php
use event4u\DataHelpers\DataAccessor;

$input = [
  'company' => [
    'departments' => [
      [ 'users' => [ ['email' => 'a@example.com'], ['email' => null] ] ],
      [ 'users' => [ ['email' => 'b@example.com'] ] ],
    ],
  ],
];

$acc = new DataAccessor($input);
$emails = $acc->get('company.departments.*.users.*.email');
// [ 'a@example.com', null, 'b@example.com' ]
```

Works with Collections, Eloquent Models, JSON, and XML too:

```php
$acc = new DataAccessor('{"users":[{"name":"Alice"},{"name":"Bob"}]}');
$names = $acc->get('users.*.name'); // ['Alice','Bob']
```

### DataMutator

Set, merge or unset deeply into arrays, DTOs/objects, Collections, and Models.

```php
use event4u\DataHelpers\DataMutator;

$data = [];
$data = DataMutator::set($data, 'user.profile.name', 'Alice');
// ['user' => ['profile' => ['name' => 'Alice']]]

$data = DataMutator::merge($data, 'user.profile', ['tags' => ['a']]);
// merges arrays deeply

$data = DataMutator::unset($data, ['user.profile.tags', 'user.unknown']);
```

Wildcards are supported for batch updates/unsets in arrays and Collections.

```php
$dto = new #[\AllowDynamicProperties] class {};
DataMutator::set($dto, 'dynamicProperty', 'value');
```

### DataMapper

Map values between heterogeneous structures using dot-paths.

Simple mapping (list of pairs):

```php
use event4u\DataHelpers\DataMapper;

$source = ['a' => ['b' => 'value']];
$target = [];
$mapping = [
  ['a.b', 'x.y'],
  ['a.b', 'flat'],
];

$result = DataMapper::map($source, $target, $mapping);
// ['x' => ['y' => 'value'], 'flat' => 'value']
```

Structured mapping (source/target entries):

```php
$mapping = [
  [
    'source'  => ['a.b', 'a.c'],
    'target'  => ['x.y', 'x.z'],
    'skipNull' => true,
    'reindexWildcard' => false,
  ],
];

$result = DataMapper::map($source, [], $mapping);
```

Batch mappings:

```php
$targets = DataMapper::mapMany([
  [ 'source' => $source, 'target' => [], 'mapping' => [['a.b','x.y']] ],
  [ 'source' => $source, 'target' => [], 'mapping' => [['a.b','flat']] ],
]);
```

### Pipeline API (NEW)

🚀 **Modern, fluent API** for composing reusable data transformers - inspired by Laravel's pipeline pattern.

**Quick Example:**

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;

$source = [
    'user' => [
        'name' => '  Alice  ',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'phone' => '',
    ],
];

$mapping = [
    'user.name' => 'profile.name',
    'user.email' => 'profile.email',
    'user.phone' => 'profile.phone',
];

// Apply transformation pipeline
$result = DataMapper::pipe([
    TrimStrings::class,           // Trim whitespace
    LowercaseEmails::class,       // Lowercase email addresses
    SkipEmptyValues::class,       // Skip empty values
])->map($source, [], $mapping);

// Result:
// {
//     "profile": {
//         "name": "Alice",
//         "email": "alice@example.com"
//         // phone is skipped (empty)
//     }
// }
```

**Built-in Transformers:**

- `TrimStrings` - Trims whitespace from all string values
- `LowercaseEmails` - Converts email addresses to lowercase (detects 'email' in path)
- `SkipEmptyValues` - Skips empty strings and empty arrays from being written
- `UppercaseStrings` - Converts all strings to uppercase
- `ConvertToNull` - Converts specific values to null (e.g., 'N/A', 'null', empty strings)

**Create Custom Transformers:**

```php
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
use event4u\DataHelpers\DataMapper\Context\HookContext;

class ValidateEmail implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: $value");
        }
        return $value;
    }

    public function getHook(): string
    {
        return 'preTransform'; // Hook to attach to
    }

    public function getFilter(): ?string
    {
        return null; // No filtering (apply to all values)
    }
}

// Use your custom transformer
$result = DataMapper::pipe([
    TrimStrings::class,
    ValidateEmail::class,
])->map($source, [], $mapping);
```

**Combine with Additional Hooks:**

```php
$result = DataMapper::pipe([
    TrimStrings::class,
    LowercaseEmails::class,
])
->withHooks([
    'afterAll' => fn($ctx) => logger()->info('Mapping completed'),
])
->map($source, [], $mapping);
```

**Reuse Pipelines:**

```php
// Define once, use multiple times
$cleanupPipeline = DataMapper::pipe([
    TrimStrings::class,
    ConvertToNull::class,
    SkipEmptyValues::class,
]);

$users = $cleanupPipeline->map($userSource, [], $userMapping);
$products = $cleanupPipeline->map($productSource, [], $productMapping);
```

**💡 Note:** The Pipeline API is **fully compatible** with the classic `DataMapper::map()` API. Both can be used interchangeably:

```php
// Classic API (still works!)
$result = DataMapper::map($source, [], $mapping, hooks: [
    'preTransform' => fn($v) => is_string($v) ? trim($v) : $v,
]);

// Pipeline API (new, modern)
$result = DataMapper::pipe([TrimStrings::class])->map($source, [], $mapping);
```

## Mapping Templates

Templates describe the output (or input) structure using path aliases.

### mapFromTemplate

Build a new array using a template that references values by alias.

```php
$template = [
  'emails' => 'src.company.departments.*.users.*.email',
  'first'  => 'src.company.departments.0.users.0.email',
];

$sources = [
  'src' => $source,
];

$out = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: true);
// ['emails' => ['a@example.com','b@example.com'], 'first' => 'a@example.com']
```

JSON templates supported as well (string input).

### mapToTargetsFromTemplate

Apply values (matching the template shape) back into real targets.

```php
$data = [
  'emails' => ['a@example.com','b@example.com'],
];
$template = [
  'emails' => 'dto.users.*.email',
];

$targets = [ 'dto' => new #[\AllowDynamicProperties] class { public array $users = [ (object)[], (object)[] ]; } ];

$updated = DataMapper::mapToTargetsFromTemplate($data, $template, $targets, reindexWildcard: true);
// writes emails back into $targets['dto']->users[*]->email
```

## AutoMap

Automatically map source properties to target properties by matching field names.

```php
$src = ['first_name' => 'Alice', 'last_name' => 'Smith'];
$dto = new #[\AllowDynamicProperties] class { public string $firstName; public string $lastName; };

$result = DataMapper::autoMap($src, $dto);
```

## Replace Options

Enable trimming and case-insensitive replaces for string mapping values.

```php
$result = DataMapper::map(
  ['name' => '  alice  '],
  [],
  [['name', 'userName']],
  skipNull: true,
  reindexWildcard: false,
  hooks: [],
  trimValues: true,
  caseInsensitiveReplace: true,
);
```

## Hooks

Intercept mapping lifecycle events using enums and typed contexts.

```php
use event4u\DataHelpers\Hooks;
use event4u\DataHelpers\Enums\DataMapperHook;

$hooks = Hooks::make()
  ->on(DataMapperHook::BeforeAll, function ($ctx) {
      // $ctx is AllContext
  })
  ->on(DataMapperHook::BeforeWrite, function ($ctx) {
      // $ctx is WriteContext; modify $ctx->target, $ctx->value
  })
  ->toArray();

$result = DataMapper::map($source, $target, [['a.b','x.y']], hooks: $hooks);
```

Alternatively, pass a simple associative array keyed by enum name.

## Options & Behavior

- skipNull (default true): omit keys when resolved value is null
- reindexWildcard (default false): preserve numeric keys unless enabled
- trimValues (default true): trim strings prior to replace logic
- caseInsensitiveReplace (default false): use case-insensitive search for replacements

**🆕 New in this version:**

- `Support/` - Helper classes for framework-agnostic Collection and Entity handling
- Framework detection with automatic runtime checks

## Contributing

- Issues and PRs welcome.
- Please follow coding standards and add tests for changes.
- Run tests with `./vendor/bin/pest`

## License

MIT License. See the [LICENSE](LICENSE) file for details.

