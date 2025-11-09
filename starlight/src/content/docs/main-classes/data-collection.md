---
title: DataCollection
description: Framework-independent type-safe collection class for working with arrays of data
sidebar:
  order: 3
---

The `DataCollection` class is a powerful, framework-independent utility for working with arrays of data. It provides a fluent, chainable API similar to Laravel's Collection, but works in any PHP environment.

**Architecture:** DataCollection acts as a container and delegates operations to three core classes:
- [DataAccessor](/data-helpers/main-classes/data-accessor/) for **reading** data (get, filter, map, etc.)
- [DataMutator](/data-helpers/main-classes/data-mutator/) for **writing** data (set, merge, forget, etc.)
- [DataFilter](/data-helpers/main-classes/data-filter/) for **SQL-like querying** (where, orderBy, limit, etc.)

This enables full dot-notation support for reading and writing, plus powerful SQL-like filtering.

## Key Features

`DataCollection` is a generic, type-safe collection class that:
- Works in any PHP environment (no framework dependencies)
- Uses DataAccessor internally for reading and transformations
- Uses DataMutator internally for mutations with dot-notation
- Supports full dot-notation for both reading and writing
- Provides a fluent, chainable API similar to Laravel Collections
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
$nestedCollection = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30]],
    ['user' => ['name' => 'Bob', 'age' => 25]],
]);
$name = $nestedCollection->get('0.user.name'); // 'Alice'
$age = $nestedCollection->get('1.user.age'); // 25

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
$firstEven = $collection->first(fn($n) => $n % 2 === 0); // 2
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

## Mutable Methods with Dot-Notation

DataCollection provides powerful mutable methods that use [DataMutator](/data-helpers/main-classes/data-mutator/) internally. These methods modify the collection in-place and return `$this` for chaining.

### set() - Set Values

Set a value at a specific path using dot notation:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice']],
]);

$collection->set('0.user.age', 30);
// Collection: [['user' => ['name' => 'Alice', 'age' => 30]]]

// Chaining
$collection
    ->set('0.user.city', 'Berlin')
    ->set('0.user.country', 'Germany');
```

### merge() - Merge Values

Merge an array into a specific path:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice']],
]);

$collection->merge('0.user', ['age' => 30, 'city' => 'Berlin']);
// Collection: [['user' => ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin']]]

// Merge multiple paths
$collection->merge([
    '0.user.age' => 30,
    '0.user.city' => 'Berlin',
]);
```

### forget() - Remove Values

Remove a value at a specific path:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin']],
]);

$collection->forget('0.user.age');
// Collection: [['user' => ['name' => 'Alice', 'city' => 'Berlin']]]
```

### transform() - Transform Values

Transform a value at a specific path using a callback:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'alice']],
]);

$collection->transform('0.user.name', fn($name) => strtoupper($name));
// Collection: [['user' => ['name' => 'ALICE']]]
```

### pushTo() - Push to Nested Array

Push a value to an array at a specific path:

```php
$collection = DataCollection::make([
    ['user' => ['tags' => ['php']]],
]);

$collection->pushTo('0.user.tags', 'laravel');
// Collection: [['user' => ['tags' => ['php', 'laravel']]]]
```

### pull() - Remove and Return

Remove a value and return it:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30]],
]);

$age = $collection->pull('0.user.age');  // 30
// Collection: [['user' => ['name' => 'Alice']]]

// With default value
$city = $collection->pull('0.user.city', 'Unknown');  // 'Unknown'
```

### Chaining Mutable Methods

All mutable methods return `$this`, enabling fluent chaining:

```php
$collection = DataCollection::make([
    ['user' => ['name' => 'Alice']],
]);

$collection
    ->set('0.user.age', 30)
    ->merge('0.user', ['city' => 'Berlin'])
    ->transform('0.user.name', fn($name) => strtoupper($name))
    ->pushTo('0.user.tags', 'php');

// Collection: [['user' => ['name' => 'ALICE', 'age' => 30, 'city' => 'Berlin', 'tags' => ['php']]]]
```

## SQL-Like Filtering with DataFilter

DataCollection integrates with [DataFilter](/data-helpers/main-classes/data-filter/) to provide powerful SQL-like querying capabilities. The `query()` method returns a wrapper that allows chaining filter operations and returns a new DataCollection.

### Basic Filtering

```php
$users = DataCollection::make([
    ['name' => 'Alice', 'age' => 30, 'city' => 'Berlin'],
    ['name' => 'Bob', 'age' => 25, 'city' => 'Munich'],
    ['name' => 'Charlie', 'age' => 35, 'city' => 'Berlin'],
]);

// Simple where clause
$filtered = $users
    ->query()
    ->where('age', '>', 25)
    ->get();  // Returns new DataCollection

// Multiple conditions
$berliners = $users
    ->query()
    ->where('age', '>', 25)
    ->where('city', 'Berlin')
    ->get();
```

### Advanced Filtering

```php
// BETWEEN
$filtered = $users
    ->query()
    ->between('age', 26, 36)
    ->get();

// WHERE IN
$filtered = $users
    ->query()
    ->whereIn('city', ['Berlin', 'Hamburg'])
    ->get();

// WHERE NULL / NOT NULL
$filtered = $users
    ->query()
    ->whereNull('email')
    ->get();

// LIKE pattern matching
$filtered = $users
    ->query()
    ->like('name', 'Ali%')
    ->get();
```

### Ordering and Limiting

```php
// ORDER BY
$ordered = $users
    ->query()
    ->orderBy('age', 'DESC')
    ->get();

// LIMIT and OFFSET
$paginated = $users
    ->query()
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->offset(20)
    ->get();
```

### Complex Queries

```php
$result = $users
    ->query()
    ->where('city', 'Berlin')
    ->where('active', true)
    ->where('age', '>=', 30)
    ->orderBy('age', 'DESC')
    ->limit(5)
    ->get();  // Returns new DataCollection

// Get first result
$first = $users
    ->query()
    ->where('age', '>', 25)
    ->first();  // Returns single item or null

// Count results
$count = $users
    ->query()
    ->where('city', 'Berlin')
    ->count();  // Returns integer
```

### Working with Nested Data

DataFilter supports dot-notation for nested fields:

```php
$data = DataCollection::make([
    ['user' => ['name' => 'Alice', 'age' => 30]],
    ['user' => ['name' => 'Bob', 'age' => 25]],
]);

$filtered = $data
    ->query()
    ->where('user.age', '>', 25)
    ->get();
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

See the [DtoCollection documentation](/data-helpers/simple-dto/collections/) for more details.

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

