---
title: DataMutator API
description: Complete API reference for DataMutator
---

Complete API reference for DataMutator.

## Constructor

### `__construct(array &$data)`

Create a new DataMutator instance.

```php
$mutator = new DataMutator($data);
```

## Static Methods

### `make(array &$data): self`

Create a new instance (fluent).

```php
$mutator = DataMutator::make($data);
```

## Set Methods

### `set(string $path, mixed $value): self`

Set value at path.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->set('user.name', 'John Doe');
```

### `setMultiple(array $values): self`

Set multiple values.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->setMultiple([
    'user.name' => 'John',
    'user.email' => 'john@example.com',
]);
```

## Unset Methods

### `unset(string $path): self`

Remove value at path.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->unset('user.password');
```

### `unsetMultiple(array $paths): self`

Remove multiple paths.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->unsetMultiple(['user.password', 'user.token']);
```

## Merge Methods

### `merge(string $path, array $value): self`

Merge array at path.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->merge('user.settings', ['theme' => 'dark']);
```

### `mergeRecursive(string $path, array $value): self`

Recursively merge array.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->mergeRecursive('config', $newConfig);
```

## Array Methods

### `push(string $path, mixed $value): self`

Push value to array.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->push('user.tags', 'admin');
```

### `pull(string $path, mixed $default = null): mixed`

Remove and return value.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$value = $mutator->pull('user.temp_data');
```

## Transform Methods

### `transform(string $path, callable $callback): self`

Transform value at path.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$mutator->transform('user.name', fn($v) => strtoupper($v));
```

## Utility Methods

### `toArray(): array`

Get modified array.

```php
$data = $mutator->toArray();
```

### `getReference(): array`

Get reference to array.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = new DataMutator($data);
$ref = &$mutator->getReference();
```

## See Also

- [DataMutator Guide](/main-classes/data-mutator/) - Complete guide
- [Dot-Notation](/core-concepts/dot-notation/) - Path syntax

