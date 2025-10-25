---
title: DataMapper API
description: Complete API reference for DataMapper
---

Complete API reference for DataMapper.

## Static Methods

### `source(array $source): self`

Set source data.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper = DataMapper::source($sourceData);
```

### `target(array $target): self`

Set target data.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper = DataMapper::source($src)->target($tgt);
```

## Mapping Methods

### `template(array $template): self`

Set mapping template.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper->template([
    'name' => '{{ user.full_name }}',
    'email' => '{{ user.email }}',
]);
```

### `map(): self`

Execute mapping.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper->map();
```

### `getTarget(): array`

Get mapped target.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$result = $mapper->getTarget();
```

## Configuration Methods

### `skipNull(bool $skip = true): self`

Skip null values.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper->skipNull(true);
```

### `reindexWildcard(bool $reindex = true): self`

Reindex wildcard results.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper->reindexWildcard(false);
```

### `hooks(array $hooks): self`

Set hooks.

```php
use event4u\DataHelpers\DataMapper;

$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = new DataMapper($data);
$mapper->hooks($hooksArray);
```

## Template Syntax

### Simple Path

```php
'{{ source.path }}'
```

### With Default

```php
'{{ source.path | default:"N/A" }}'
```

### With Filters

```php
'{{ source.path | upper | trim }}'
```

### Wildcards

```php
'{{ users.*.name }}'
```

## See Also

- [DataMapper Guide](/main-classes/data-mapper/) - Complete guide
- [Hooks & Events](/advanced/hooks-events/) - Hooks guide

