---
title: DataMapper API
description: Complete API reference for DataMapper
---

Complete API reference for DataMapper.

## Factory Methods

### `DataMapper::source(mixed $source): FluentDataMapper`

Create mapper with source data.

```php
use event4u\DataHelpers\DataMapper;

$source = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = DataMapper::source($source);
```

### `DataMapper::template(array $template): FluentDataMapper`

Create mapper with template.

```php
use event4u\DataHelpers\DataMapper;

$mapper = DataMapper::template([
    'name' => '{{ user.name }}',
    'email' => '{{ user.email }}',
]);
```

## Configuration Methods

### `source(mixed $source): self`

Set source data.

```php
use event4u\DataHelpers\DataMapper;

$source = ['user' => ['name' => 'John']];
$mapper = DataMapper::source($source);
```

### `target(mixed $target): self`

Set target data.

```php
use event4u\DataHelpers\DataMapper;

$source = ['id' => 1];
$target = ['id' => null];
$mapper = DataMapper::source($source)->target($target);
```

### `template(array $template): self`

Set mapping template.

```php
use event4u\DataHelpers\DataMapper;

$mapper = DataMapper::source([])
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ]);
```

### `skipNull(bool $skip = true): self`

Skip null values in mapping.

```php
use event4u\DataHelpers\DataMapper;

$mapper = DataMapper::source(['name' => null])
    ->template(['name' => '{{ name }}'])
    ->skipNull(true);
```

### `reindexWildcard(bool $reindex = true): self`

Reindex wildcard results.

```php
use event4u\DataHelpers\DataMapper;

$mapper = DataMapper::source([])
    ->reindexWildcard(false);
```

## Execution Methods

### `map(bool $withQuery = true): DataMapperResult`

Execute mapping and return result.

```php
use event4u\DataHelpers\DataMapper;

$source = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$result = DataMapper::source($source)
    ->template(['name' => '{{ user.name }}'])
    ->map();

$target = $result->getTarget();
```

### `autoMap(?bool $deep = null): DataMapperResult`

Automatically map matching fields.

```php
use event4u\DataHelpers\DataMapper;

$source = ['user_name' => 'John', 'user_email' => 'john@example.com'];
$result = DataMapper::source($source)
    ->autoMap();
```

### `reverseMap(): DataMapperResult`

Execute reverse mapping (target → source).

```php
use event4u\DataHelpers\DataMapper;

$source = ['name' => 'John'];
$target = ['full_name' => 'John Doe'];
$result = DataMapper::source($source)
    ->target($target)
    ->template(['full_name' => '{{ name }}'])
    ->reverseMap();
```

## Advanced Methods

### `query(string $wildcardPath): MapperQuery`

Create query on wildcard path.

```php
use event4u\DataHelpers\DataMapper;

$source = ['users' => [
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
]];

$result = DataMapper::source($source)
    ->template(['users' => ['*' => ['name' => '{{ users.*.name }}']]])
    ->query('users.*')
        ->where('age', '>', 18)
        ->orderBy('name', 'ASC')
        ->limit(10)
        ->end()
    ->map();
```

### `property(string $property): DataMapperProperty`

Access property for filters.

<!-- skip-test: Requires filter classes -->
```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

$result = DataMapper::source(['name' => '  John  '])
    ->template(['name' => '{{ name }}'])
    ->property('name')
        ->setFilter(new TrimStrings())
        ->end()
    ->map();
```

## Template Syntax

### Simple Path

<!-- skip-test: Template syntax example -->
```php
'{{ source.path }}'
```

### With Default

<!-- skip-test: Template syntax example -->
```php
'{{ source.path | default:"N/A" }}'
```

### With Filters

<!-- skip-test: Template syntax example -->
```php
'{{ source.path | upper | trim }}'
```

### Wildcards

<!-- skip-test: Template syntax example -->
```php
'{{ users.*.name }}'
```

## See Also

- [DataMapper Guide](/data-helpers/main-classes/data-mapper/) - Complete guide
- [Hooks & Events](/data-helpers/advanced/hooks-events/) - Hooks guide

