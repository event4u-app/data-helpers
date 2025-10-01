# Laravel Data Helpers

[![PHP](https://img.shields.io/badge/PHP-8.1%E2%80%938.5-777bb3?logo=php&logoColor=white)](#requirements)
[![Laravel](https://img.shields.io/badge/Laravel-8--12-FF2D20?logo=laravel&logoColor=white)](#requirements)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](#license)

High-level, well-tested helpers for structured data access, mutation, and mapping using dot-notation paths with wildcard support. Works with
arrays, DTOs/objects, Laravel Collections, Eloquent Models, JSON, and XML.

Features at a glance:

- Dot-notation paths with deep multi-level wildcards (e.g. `users.*.profile.*.city`)
- Access values consistently across arrays, objects, Collections, Models, JSON, XML
- Mutate data: set/merge/unset deeply into arrays, DTOs, Collections, and Models
- Map between heterogeneous structures (simple pairs, structured mappings)
- Template-driven mapping (read and write) with alias support
- AutoMap snake_case to camelCase for DTO/Model targets
- Replace options: case-insensitive and trimming
- Hooks system with typed contexts for mapping lifecycle

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
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
- [API Overview](#api-overview)
- [Contributing](#contributing)
- [License](#license)

**📖 Full documentation with extensive examples:**

- [Data Accessor](docs/data-accessor.md) – Read nested data with wildcards, Collections, and Models
- [Data Mutator](docs/data-mutator.md) – Write, merge, and unset nested values with wildcards
- [Data Mapper](docs/data-mapper.md) – Map between structures with templates, transforms, and hooks
- [Dot-Path Syntax](docs/dot-path.md) – Path notation reference and best practices

💡 **Tip:** The docs contain many real-world examples including deep wildcards, JSON templates, autoMap (snake_case → camelCase), value
replacement, hooks, and common patterns for each helper.

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

## Contributing

- Issues and PRs welcome.
- Please follow coding standards and add tests for changes.

## License

MIT License. See the [LICENSE](LICENSE) file for details.

