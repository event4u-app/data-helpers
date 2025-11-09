---
title: DataCollection
description: Framework-independent type-safe collection class for working with arrays of data
sidebar:
  order: 3
---

The `DataCollection` class is a powerful, framework-independent utility for working with arrays of data. It provides a fluent, chainable API similar to Laravel's Collection, but works in any PHP environment.

**Architecture:** DataCollection acts as a container and delegates all data access and transformation operations to [DataAccessor](/data-helpers/main-classes/data-accessor/). This enables dot-notation access and all DataAccessor features within collections.

## Overview

`DataCollection` is a generic, type-safe collection class that:
- Works in any PHP environment (no framework dependencies)
- Uses DataAccessor internally for all data operations
- Supports dot-notation access within collections
- Provides a fluent, chainable API
- Supports lazy evaluation with generators for memory efficiency
- Implements standard PHP interfaces (IteratorAggregate, ArrayAccess, Countable, JsonSerializable)
- Offers full PHPStan Level 9 type safety with generics

## Basic Usage

### Creating Collections

```php
use event4u\DataHelpers\DataCollection;

// Create from array
$collection = DataCollection::make([1, 2, 3, 4, 5]);

// Create empty collection
$empty = DataCollection::make();

// Wrap existing collection or array
$wrapped = DataCollection::wrap([1, 2, 3]);
$same = DataCollection::wrap($wrapped); // Returns same instance
```

### Accessing Items

```php
$collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);

// Get item by key (checks direct key first, then uses dot-notation)
$value = $collection->get('a'); // 1
$value = $collection->get('missing', 'default'); // 'default'

// Dot-notation access (powered by DataAccessor)
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30]],
    ['user' => ['name' => 'Bob', 'age' => 25]],
]);
$name = $collection->get('0.user.name'); // 'Alice'
$age = $collection->get('1.user.age'); // 25

// Array access
$value = $collection['a']; // 1

// Check if key exists
if ($collection->has('a')) {
    // Key exists
}

// Get first/last item (delegates to DataAccessor)
$first = $collection->first(); // 1
$last = $collection->last(); // 3

// With callback
$firstEven = $collection->first(fn($n) => $n % 2 === 0);
```

## Transformation Methods

All transformation methods delegate to [DataAccessor](/data-helpers/main-classes/data-accessor/) for consistent behavior across the library.

### Filter

Remove items that don't match a condition:

```php
$collection = DataCollection::make([1, 2, 3, 4, 5]);

// Filter with callback (delegates to DataAccessor)
$filtered = $collection->filter(fn($item) => $item > 2);
// Result: [2 => 3, 3 => 4, 4 => 5]

// Filter without callback (removes falsy values)
$collection = DataCollection::make([0, 1, false, 2, null, 3]);
$filtered = $collection->filter();
// Result: [1 => 1, 3 => 2, 5 => 3]

// Filter with key and value
$collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);
$filtered = $collection->filter(fn($value, $key) => $key !== 'b');
// Result: ['a' => 1, 'c' => 3]
```

### Map

Transform each item in the collection:

```php
$collection = DataCollection::make([1, 2, 3]);

// Map values
$mapped = $collection->map(fn($item) => $item * 2);
// Result: [2, 4, 6]

// Map with key and value
$collection = DataCollection::make(['a' => 1, 'b' => 2]);
$mapped = $collection->map(fn($value, $key) => $key . ':' . $value);
// Result: ['a' => 'a:1', 'b' => 'b:2']
```

### Reduce

Reduce the collection to a single value:

```php
$collection = DataCollection::make([1, 2, 3, 4]);

// Sum all values
$sum = $collection->reduce(fn($carry, $item) => $carry + $item, 0);
// Result: 10

// Concatenate with keys
$collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);
$result = $collection->reduce(fn($carry, $value, $key) => $carry . $key, '');
// Result: 'abc'
```

## Manipulation Methods

### Push and Prepend

Add items to the collection:

```php
$collection = DataCollection::make([2, 3]);

// Push items to the end
$collection->push(4, 5, 6);
// Result: [2, 3, 4, 5, 6]

// Prepend item to the beginning
$collection->prepend(1);
// Result: [1, 2, 3, 4, 5, 6]
```

### Keys and Values

```php
$collection = DataCollection::make(['a' => 1, 'b' => 2, 'c' => 3]);

// Get all keys
$keys = $collection->keys();
// Result: Collection(['a', 'b', 'c'])

// Get all values (reindexed)
$values = $collection->values();
// Result: Collection([1, 2, 3])
```

## Lazy Evaluation

For memory-efficient processing of large collections, use lazy evaluation with generators:

```php
$collection = DataCollection::make(range(1, 10000));

// Lazy iteration (doesn't load all items into memory)
foreach ($collection->lazy() as $item) {
    // Process one item at a time
}

// Lazy filter
foreach ($collection->lazyFilter(fn($item) => $item > 5000) as $item) {
    // Only matching items are processed
}

// Lazy map
foreach ($collection->lazyMap(fn($item) => $item * 2) as $item) {
    // Transform items on-the-fly
}
```

## Utility Methods

### Count and Empty Checks

```php
$collection = DataCollection::make([1, 2, 3]);

// Count items
$count = $collection->count(); // 3
$count = count($collection); // 3 (Countable interface)

// Check if empty
if ($collection->isEmpty()) {
    // Collection is empty
}

if ($collection->isNotEmpty()) {
    // Collection has items
}
```

### Conversion Methods

```php
$collection = DataCollection::make(['a' => 1, 'b' => 2]);

// Convert to array
$array = $collection->toArray();
// Result: ['a' => 1, 'b' => 2]

// Get all items (alias for toArray)
$items = $collection->all();
$items = $collection->items();

// Convert to JSON
$json = $collection->toJson();
// Result: '{"a":1,"b":2}'

// JSON serialization
$json = json_encode($collection);
// Result: '{"a":1,"b":2}'
```

## Method Chaining

All transformation methods return a new Collection instance, allowing for fluent method chaining:

```php
$collection = DataCollection::make([1, 2, 3, 4, 5, 6]);

$result = $collection
    ->filter(fn($item) => $item > 2)  // [3, 4, 5, 6]
    ->map(fn($item) => $item * 2)     // [6, 8, 10, 12]
    ->values()                         // Reindex: [6, 8, 10, 12]
    ->toArray();

// Result: [6, 8, 10, 12]
```

## Type Safety

The Collection class is fully type-safe with PHPStan generics:

```php
/** @var Collection<int> */
$numbers = DataCollection::make([1, 2, 3]);

/** @var Collection<string> */
$strings = DataCollection::make(['a', 'b', 'c']);

/** @var Collection<array<string, mixed>> */
$arrays = DataCollection::make([
    ['name' => 'John'],
    ['name' => 'Jane'],
]);
```

## Integration with Type-Safe Getters

The Collection class is returned by all collection getter methods in DataAccessor, SimpleDto, and LiteDto:

```php
use event4u\DataHelpers\DataAccessor;

$data = [
    'users' => [
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
    ],
];

$accessor = new DataAccessor($data);

// Returns Collection<int>
$ages = $accessor->getIntCollection('users.*.age');

// Chain collection methods
$adults = $ages
    ->filter(fn($age) => $age >= 18)
    ->map(fn($age) => $age + 1)
    ->toArray();
```

## DtoCollection for DTOs

For working with collections of DTOs, use the specialized `DtoCollection` class which extends `DataCollection`:

```php
use event4u\DataHelpers\SimpleDto\DtoCollection;
use event4u\DataHelpers\SimpleDto\SimpleDto;

class UserDto extends SimpleDto
{
    public string $name;
    public int $age;
}

// Create collection of DTOs
$users = DtoCollection::forDto(UserDto::class, [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
]);

// All DataCollection methods work
$adults = $users->filter(fn(UserDto $user) => $user->age >= 18);
```

See the [DtoCollection documentation](/simple-dto/data-collection/) for more details.

## Performance Considerations

- **Lazy Evaluation**: Use `lazy()`, `lazyFilter()`, and `lazyMap()` for large collections to avoid loading all items into memory
- **Immutability**: Most methods return new Collection instances. For in-place modifications, use `push()` and `prepend()`
- **Type Safety**: Generic type annotations help PHPStan catch type errors at analysis time

## API Reference

### Creation Methods
- `make(array $items = []): static` - Create new collection
- `wrap(mixed $value): static` - Wrap value in collection

### Access Methods
- `get(int|string $key, mixed $default = null): mixed` - Get item by key
- `first(?callable $callback = null, mixed $default = null): mixed` - Get first item
- `last(?callable $callback = null, mixed $default = null): mixed` - Get last item
- `has(int|string $key): bool` - Check if key exists

### Transformation Methods
- `filter(?callable $callback = null): static` - Filter items
- `map(callable $callback): static` - Transform items
- `reduce(callable $callback, mixed $initial = null): mixed` - Reduce to single value

### Manipulation Methods
- `push(mixed ...$values): static` - Add items to end
- `prepend(mixed $value): static` - Add item to beginning
- `keys(): static` - Get all keys
- `values(): static` - Get all values (reindexed)

### Lazy Methods
- `lazy(): Generator` - Lazy iteration
- `lazyFilter(callable $callback): Generator` - Lazy filter
- `lazyMap(callable $callback): Generator` - Lazy map

### Utility Methods
- `count(): int` - Count items
- `isEmpty(): bool` - Check if empty
- `isNotEmpty(): bool` - Check if not empty
- `all(): array` - Get all items
- `items(): array` - Get all items (alias)
- `toArray(): array` - Convert to array
- `toJson(int $options = 0): string` - Convert to JSON

