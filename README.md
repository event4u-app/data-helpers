# Data Helpers (Framework-Agnostic)

[![PHP](https://img.shields.io/badge/PHP-8.2%E2%80%938.3-777bb3?logo=php&logoColor=white)](#requirements)
[![Laravel](https://img.shields.io/badge/Laravel-8--12-FF2D20?logo=laravel&logoColor=white)](#requirements)
[![Symfony](https://img.shields.io/badge/Symfony-Compatible-000000?logo=symfony&logoColor=white)](#requirements)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](#license)

High-level, well-tested helpers for structured data access, mutation, and mapping using dot-notation paths with wildcard support. Works with
arrays, DTOs/objects, Laravel Collections, Eloquent Models, Doctrine Collections, Doctrine Entities, JSON, and XML.

**Framework-agnostic**: Works with Laravel, Symfony/Doctrine, or standalone PHP. Optional dependencies are automatically detected.

Features at a glance:

- **Framework-agnostic**: Works with Laravel, Symfony/Doctrine, or standalone PHP
- Dot-notation paths with deep multi-level wildcards (e.g. `users.*.profile.*.city`)
- Access values consistently across arrays, objects, Collections (Laravel/Doctrine), Models/Entities, JSON, XML
- Mutate data: set/merge/unset deeply into arrays, DTOs, Collections, Models, and Entities
- Map between heterogeneous structures (simple pairs, structured mappings)
- Template-driven mapping (read and write) with alias support
- AutoMap snake_case to camelCase for DTO/Model/Entity targets
- Replace options: case-insensitive and trimming
- Hooks system with typed contexts for mapping lifecycle
- **Optional dependencies**: Laravel and Doctrine packages are optional, polyfills provided

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Framework Support](#framework-support)
- [Quick Start](#quick-start)
    - [DataAccessor](#dataaccessor)
    - [DataMutator](#datamutator)
    - [DataMapper](#datamapper)
- [Mapping Templates](#mapping-templates)
    - [mapFromTemplate](#mapfromtemplate)
    - [mapToTargetsFromTemplate](#maptotargetsfromtemplate)
- [AutoMap](#automap)
- [Replace Options](#replace-options)
- [Hooks](#hooks)
- [Options & Behavior](#options--behavior)
- [Package Structure](#package-structure)
- [Contributing](#contributing)
- [License](#license)

**📖 Full documentation with extensive examples:**

- [Data Accessor](docs/data-accessor.md) – Read nested data with wildcards, Collections, and Models
- [Data Mutator](docs/data-mutator.md) – Write, merge, and unset nested values with wildcards
- [Data Mapper](docs/data-mapper.md) – Map between structures with templates, transforms, and hooks
- [Dot-Path Syntax](docs/dot-path.md) – Path notation reference and best practices

💡 **Tip:** The docs contain many real-world examples including deep wildcards, JSON templates, autoMap (snake_case → camelCase), value
replacement, hooks, and common patterns for each helper.


**💻 Code Examples:**

- [examples/01-data-accessor.php](examples/01-data-accessor.php) – Basic array access with wildcards
- [examples/02-data-mutator.php](examples/02-data-mutator.php) – Mutating arrays
- [examples/03-data-mapper-simple.php](examples/03-data-mapper-simple.php) – Simple mapping
- [examples/04-data-mapper-with-hooks.php](examples/04-data-mapper-with-hooks.php) – Advanced mapping with hooks
- [examples/05-laravel.php](examples/05-laravel.php) – Laravel Collections, Eloquent Models, Arrayable
- [examples/06-symfony-doctrine.php](examples/06-symfony-doctrine.php) – Doctrine Collections and Entities
## Installation

Use as a local path repository during development.

composer.json (of your host application):

```json
{
    "require": {
        "event4u/laravel-data-helpers": "dev-main"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../galawork-packages/event4u/laravel-data-helpers"
        }
    ]
}
```

Then install:

```bash
composer update event4u/laravel-data-helpers -o
```

PSR-4 namespace: `event4u\DataHelpers`

## Framework Support

This package works with **any PHP 8.2+ project** and provides optional support for popular frameworks:

### Standalone PHP
```bash
composer require event4u/laravel-data-helpers
```
Works out of the box with arrays, objects, JSON, and XML. Polyfills are automatically loaded for Collection and Model types.

### Laravel Projects
```bash
composer require event4u/laravel-data-helpers
composer require illuminate/support      # Usually already installed
composer require illuminate/database     # For Eloquent Model support
```
Full support for Laravel Collections, Eloquent Models, and Arrayable interface.

### Symfony/Doctrine Projects
```bash
composer require event4u/laravel-data-helpers
composer require doctrine/collections    # For Doctrine Collections
composer require doctrine/orm            # For Doctrine Entities
```
Full support for Doctrine Collections and Entities with automatic detection.

**See [OPTIONAL_DEPENDENCIES.md](OPTIONAL_DEPENDENCIES.md) for detailed framework integration guide.**

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

Automatically map snake_case source keys to camelCase DTO/Model targets.

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

## Package Structure

For developers working on this package, here's the internal organization:

```
src/
├── DataAccessor.php          # Read nested data with wildcards
├── DataMutator.php           # Write/merge/unset nested values
├── DataMapper.php            # Main mapping facade
├── DotPathHelper.php         # Dot-notation path utilities
├── DataMapper/
│   ├── AutoMapper.php        # Automatic source → target mapping
│   ├── TemplateMapper.php    # Template-based mapping operations
│   ├── Hooks.php             # Fluent hooks builder
│   ├── Context/              # Typed context objects for hooks
│   │   ├── AllContext.php
│   │   ├── EntryContext.php
│   │   ├── HookContext.php
│   │   ├── PairContext.php
│   │   └── WriteContext.php
│   └── Support/              # Internal helper classes
│       ├── HookInvoker.php
│       ├── MappingEngine.php
│       ├── ValueTransformer.php
│       └── WildcardHandler.php
└── Enums/
    ├── DataMapperHook.php    # Hook name enum
    └── Mode.php              # Mapping mode enum

tests/
└── Unit/
    ├── DataAccessor/
    │   └── DataAccessorTest.php
    ├── DataMapper/
    │   ├── DataMapperTest.php
    │   ├── DataMapperAutoMapTest.php
    │   ├── DataMapperHooksTest.php
    │   ├── DataMapperReplaceTest.php
    │   ├── DataMapperDeepFixturesTest.php
    │   └── HooksBuilderTest.php
    └── DataMutator/
        └── DataMutatorTest.php
```

## Contributing

- Issues and PRs welcome.
- Please follow coding standards and add tests for changes.
- Run tests with `./vendor/bin/pest`

## License

MIT License. See the [LICENSE](LICENSE) file for details.

