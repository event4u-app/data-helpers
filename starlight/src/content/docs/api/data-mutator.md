---
title: DataMutator API
description: Complete API reference for DataMutator
---

Complete API reference for DataMutator.

## Constructor

### `__construct(array|object &$target)`

Create a new DataMutator instance with a reference to the target.

```php
$data = ['user' => ['name' => 'John']];
$mutator = new DataMutator($data);
$mutator->set('user.age', 25);
// $data is now: ['user' => ['name' => 'John', 'age' => 25]]
```

## Static Methods

### `make(array|object &$target): self`

Create a new instance (fluent factory method).

```php
$data = ['user' => ['name' => 'John']];
DataMutator::make($data)->set('user.age', 25);
// $data is now: ['user' => ['name' => 'John', 'age' => 25]]
```

## Set Methods

### `set(array|string $pathOrValues, mixed $value = null, bool $merge = false): self`

Set value at path or multiple values at once.

```php
use event4u\DataHelpers\DataMutator;

// Single value
$data = ['user' => ['name' => 'John', 'age' => 25]];
DataMutator::make($data)->set('user.name', 'John Doe');
// $data is now: ['user' => ['name' => 'John Doe', 'age' => 25]]

// Multiple values
$data = ['user' => ['name' => 'John']];
DataMutator::make($data)->set([
    'user.name' => 'John Doe',
    'user.email' => 'john@example.com',
]);
// $data is now: ['user' => ['name' => 'John Doe', 'email' => 'john@example.com']]
```

## Unset Methods

### `unset(array|string $paths): self`

Remove value at path or multiple paths.

```php
use event4u\DataHelpers\DataMutator;

// Single path
$data = ['user' => ['name' => 'John', 'age' => 25, 'password' => 'secret']];
DataMutator::make($data)->unset('user.password');
// $data is now: ['user' => ['name' => 'John', 'age' => 25]]

// Multiple paths
$data = ['user' => ['name' => 'John', 'password' => 'secret', 'token' => 'abc']];
DataMutator::make($data)->unset(['user.password', 'user.token']);
// $data is now: ['user' => ['name' => 'John']]
```

## Merge Methods

### `merge(array|string $pathOrValues, ?array $value = null): self`

Merge array at path or multiple paths.

```php
use event4u\DataHelpers\DataMutator;

// Single merge
$data = ['user' => ['name' => 'John', 'age' => 25]];
DataMutator::make($data)->merge('user', ['email' => 'john@example.com']);
// $data is now: ['user' => ['name' => 'John', 'age' => 25, 'email' => 'john@example.com']]

// Multiple merges
$data = ['user' => ['name' => 'John']];
DataMutator::make($data)->merge([
    'user' => ['age' => 25],
    'settings' => ['theme' => 'dark'],
]);
```

### `mergeRecursive(string $path, array $value): self`

Recursively merge array.

```php
use event4u\DataHelpers\DataMutator;

$data = ['config' => ['database' => ['host' => 'localhost']]];
$newConfig = ['database' => ['port' => 3306]];
DataMutator::make($data)->mergeRecursive('config', $newConfig);
// $data is now: ['config' => ['database' => ['host' => 'localhost', 'port' => 3306]]]
```

## Array Methods

### `push(string $path, mixed $value): self`

Push value to array.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['tags' => ['user']]];
DataMutator::make($data)->push('user.tags', 'admin');
// $data is now: ['user' => ['tags' => ['user', 'admin']]]
```

### `pull(string $path, mixed $default = null): mixed`

Remove and return value.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'temp_data' => 'xyz']];
$value = DataMutator::make($data)->pull('user.temp_data');
// $value is 'xyz', $data is now: ['user' => ['name' => 'John']]
```

## Transform Methods

### `transform(string $path, callable $callback): self`

Transform value at path.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'john', 'age' => 25]];
DataMutator::make($data)->transform('user.name', fn($v) => strtoupper($v));
// $data is now: ['user' => ['name' => 'JOHN', 'age' => 25]]
```

## Utility Methods

### `toArray(): array|object`

Get the modified target (array or object).

```php
$data = ['user' => ['name' => 'John']];
$result = DataMutator::make($data)->set('user.age', 25)->toArray();
// $result is: ['user' => ['name' => 'John', 'age' => 25]]
```

### `getReference(): array|object`

Get reference to the target.

```php
use event4u\DataHelpers\DataMutator;

$data = ['user' => ['name' => 'John', 'age' => 25]];
$mutator = DataMutator::make($data);
$ref = &$mutator->getReference();
// $ref points to $data
```

## See Also

- [DataMutator Guide](/main-classes/data-mutator/) - Complete guide
- [Dot-Notation](/core-concepts/dot-notation/) - Path syntax

