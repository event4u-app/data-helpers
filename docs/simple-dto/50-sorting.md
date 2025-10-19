# 🔤 Array Sorting

Control the order of keys in your DTO's array and JSON output with flexible sorting options.

---

## 📚 Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Sorting Direction](#sorting-direction)
- [Nested Sorting](#nested-sorting)
- [Custom Sort Callback](#custom-sort-callback)
- [JSON Serialization](#json-serialization)
- [Chaining with Other Methods](#chaining-with-other-methods)
- [Default Behavior](#default-behavior)
- [API Reference](#api-reference)

---

## Overview

By default, DTOs output their properties in the order they are defined. The sorting feature allows you to:

- **Sort keys alphabetically** (ascending or descending)
- **Apply sorting to nested arrays** recursively
- **Use custom sort logic** via callbacks
- **Maintain immutability** - sorting creates a new instance

**Default Settings:**
- `sorting`: `false` (disabled)
- `direction`: `'asc'` (ascending)
- `nestedSort`: `false` (disabled)
- `sortBy`: keys (alphabetically)

---

## Basic Usage

### Enable Sorting

```php
use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $zebra,
        public readonly string $name,
        public readonly string $email,
        public readonly string $alpha,
    ) {}
}

$user = UserDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
]);

// Without sorting (original order)
$user->toArray();
// ['zebra' => 'z', 'name' => 'John', 'email' => 'john@example.com', 'alpha' => 'a']

// With sorting (alphabetical)
$user->sorted()->toArray();
// ['alpha' => 'a', 'email' => 'john@example.com', 'name' => 'John', 'zebra' => 'z']
```

### Disable Sorting

```php
$sorted = $user->sorted();
$unsorted = $sorted->unsorted();

$unsorted->toArray();
// Back to original order
```

---

## Sorting Direction

### Ascending (Default)

```php
$user->sorted()->toArray();
// or explicitly:
$user->sorted('asc')->toArray();
// ['alpha' => 'a', 'email' => 'john@example.com', 'name' => 'John', 'zebra' => 'z']
```

### Descending

```php
$user->sorted('desc')->toArray();
// ['zebra' => 'z', 'name' => 'John', 'email' => 'john@example.com', 'alpha' => 'a']
```

---

## Nested Sorting

By default, only the top-level keys are sorted. Enable nested sorting to sort all levels recursively.

### Top-Level Only (Default)

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $attributes,
    ) {}
}

$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'attributes' => [
        'weight' => '2kg',
        'color' => 'silver',
        'brand' => 'Apple',
    ],
]);

$product->sorted()->toArray();
// [
//     'attributes' => [
//         'weight' => '2kg',    // Not sorted
//         'color' => 'silver',
//         'brand' => 'Apple',
//     ],
//     'name' => 'Laptop',
// ]
```

### With Nested Sorting

```php
$product->sorted()->withNestedSort()->toArray();
// [
//     'attributes' => [
//         'brand' => 'Apple',   // Sorted!
//         'color' => 'silver',
//         'weight' => '2kg',
//     ],
//     'name' => 'Laptop',
// ]
```

### Deeply Nested Arrays

```php
$config = ConfigDTO::fromArray([
    'config' => [
        'zebra' => [
            'nested_z' => 'value_z',
            'nested_a' => 'value_a',
            'nested_m' => [
                'deep_z' => 'deep_z',
                'deep_a' => 'deep_a',
            ],
        ],
        'alpha' => [...],
    ],
]);

$config->sorted()->withNestedSort()->toArray();
// All levels sorted recursively
```

---

## Custom Sort Callback

Use a custom callback for advanced sorting logic.

### Reverse Alphabetical

```php
$sorted = $user->sortedBy(fn($a, $b) => strcmp($b, $a));
$sorted->toArray();
// Keys in reverse alphabetical order
```

### Sort by Key Length

```php
class DataDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $a,
        public readonly string $abc,
        public readonly string $ab,
    ) {}
}

$data = DataDTO::fromArray(['a' => '1', 'abc' => '3', 'ab' => '2']);

$sorted = $data->sortedBy(fn($a, $b) => strlen($a) <=> strlen($b));
$sorted->toArray();
// ['a' => '1', 'ab' => '2', 'abc' => '3']
```

### Custom Business Logic

```php
$sorted = $dto->sortedBy(function($a, $b) {
    // Priority keys first
    $priority = ['id', 'name', 'email'];
    
    $aIndex = array_search($a, $priority);
    $bIndex = array_search($b, $priority);
    
    if ($aIndex !== false && $bIndex !== false) {
        return $aIndex <=> $bIndex;
    }
    
    if ($aIndex !== false) return -1;
    if ($bIndex !== false) return 1;
    
    return strcmp($a, $b);
});
```

---

## JSON Serialization

Sorting applies to both `toArray()` and `jsonSerialize()`:

```php
$user = UserDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
]);

// Unsorted JSON
json_encode($user);
// {"zebra":"z","name":"John","email":"john@example.com","alpha":"a"}

// Sorted JSON
json_encode($user->sorted());
// {"alpha":"a","email":"john@example.com","name":"John","zebra":"z"}
```

---

## Chaining with Other Methods

Sorting works seamlessly with other DTO methods:

```php
// Sorted + Visibility
$user->sorted()->only(['name', 'email'])->toArray();
// ['email' => 'john@example.com', 'name' => 'John']

$user->sorted()->except(['password'])->toArray();
// All fields except password, sorted

// Sorted + With
$user->sorted()->with(['extra' => 'value'])->toArray();
// All fields + extra, sorted

// Sorted + Include
$user->sorted()->include(['computed'])->toArray();
// All fields + computed property, sorted
```

---

## Default Behavior

Without calling `sorted()`, DTOs maintain their original key order:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $zebra,
        public readonly string $alpha,
        public readonly string $beta,
    ) {}
}

$user = UserDTO::fromArray(['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b']);
$user->toArray();
// ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b']
// Order matches property definition order
```

---

## API Reference

### Methods

#### `sorted(string $direction = 'asc'): static`

Enable sorting with optional direction.

**Parameters:**
- `$direction` - Sort direction: `'asc'` or `'desc'` (default: `'asc'`)

**Returns:** New DTO instance with sorting enabled

**Example:**
```php
$sorted = $dto->sorted();        // Ascending
$sorted = $dto->sorted('desc');  // Descending
```

---

#### `unsorted(): static`

Disable sorting.

**Returns:** New DTO instance with sorting disabled

**Example:**
```php
$unsorted = $dto->sorted()->unsorted();
```

---

#### `withNestedSort(bool $enabled = true): static`

Enable or disable nested sorting.

**Parameters:**
- `$enabled` - Whether to enable nested sorting (default: `true`)

**Returns:** New DTO instance with nested sorting configured

**Example:**
```php
$sorted = $dto->sorted()->withNestedSort();
$sorted = $dto->sorted()->withNestedSort(false);
```

---

#### `sortedBy(callable $callback): static`

Sort using a custom callback.

**Parameters:**
- `$callback` - Sort callback `(string $keyA, string $keyB): int`
  - Return negative if `$keyA` should come before `$keyB`
  - Return zero if keys are equal
  - Return positive if `$keyA` should come after `$keyB`

**Returns:** New DTO instance with custom sorting

**Example:**
```php
$sorted = $dto->sortedBy(fn($a, $b) => strcmp($b, $a));
$sorted = $dto->sortedBy(fn($a, $b) => strlen($a) <=> strlen($b));
```

---

## Best Practices

### 1. Use Sorting for API Responses

```php
// Consistent key order in API responses
return response()->json($user->sorted());
```

### 2. Combine with Visibility

```php
// Sorted public data only
$public = $user->sorted()->except(['password', 'token']);
```

### 3. Enable Nested Sort for Complex Data

```php
// Ensure all levels are sorted
$config->sorted()->withNestedSort()->toArray();
```

### 4. Use Custom Callbacks for Business Logic

```php
// Priority fields first, then alphabetical
$sorted = $dto->sortedBy(function($a, $b) {
    $priority = ['id', 'name'];
    // ... custom logic
});
```

### 5. Remember Immutability

```php
$original = $dto;
$sorted = $dto->sorted();

// $original is unchanged
// $sorted has sorted keys
```

---

## See Also

- [Visibility & Security](30-visibility.md) - Control which properties are visible
- [Computed Properties](31-computed-properties.md) - Add dynamic properties
- [with() Method](42-with-method.md) - Add additional data
- [Wrapping](41-wrapping.md) - Wrap output in a key

