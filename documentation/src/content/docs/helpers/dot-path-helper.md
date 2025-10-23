---
title: Dot Path Syntax
description: Learn how to use dot-path notation and wildcards to access nested data
---

Learn how to use dot-path notation and wildcards to access nested data.

## Overview

All helpers (DataAccessor, DataMutator, DataMapper) use the same dot-path syntax and wildcard semantics.

Dot-path notation allows you to access deeply nested data structures using a simple string syntax:

```php
// Access nested data
$accessor->get('user.profile.name');

// Access array elements
$accessor->get('users.0.email');

// Use wildcards
$accessor->get('users.*.email');
```

## Segments

### Basic Syntax

Segments are separated by `.` (dot):

```php
// Simple path
'user.profile.name' // Accesses $data['user']['profile']['name']

// Numeric index
'users.0.email' // Accesses $data['users'][0]['email']

// Multiple levels
'company.departments.0.employees.5.name'
```

### Rules

- Segments are separated by `.` (dot)
- Numeric segments index arrays/collections
- Empty segments are not allowed
- Invalid syntax throws `InvalidArgumentException`:
  - Leading dot: `.a`
  - Trailing dot: `a.`
  - Double dots: `a..b`
- Empty path `""` is allowed and yields no segments

## Wildcards

### Single Wildcard

`*` matches any single segment at that position:

```php
// Match all emails in users array
'users.*.email'

// Returns:
[
  'users.0.email' => 'alice@example.com',
  'users.1.email' => 'bob@example.com',
  'users.2.email' => 'charlie@example.com',
]
```

### Deep Wildcards

Multiple `*` can appear in one path:

```php
// Match all SKUs in all items across all orders
'orders.*.items.*.sku'

// Returns:
[
  'orders.0.items.0.sku' => 'WIDGET-A',
  'orders.0.items.1.sku' => 'GADGET-B',
  'orders.1.items.0.sku' => 'TOOL-C',
]
```

### Wildcard Validation

```php
// Check if path contains wildcard
$accessor->containsWildcard('users.*.email'); // true
$accessor->containsWildcard('users.0.email'); // false

// Throws on invalid syntax
$accessor->containsWildcard('users..*.email'); // InvalidArgumentException
```

## DataAccessor with Wildcards

### Return Format

DataAccessor returns an associative array keyed by the resolved dot-path for each match:

```php
$data = [
    'users' => [
        ['email' => 'a@example.com'],
        ['email' => null],
        ['email' => 'b@example.com'],
    ],
];

$accessor = new DataAccessor($data);
$result = $accessor->get('users.*.email');

// Returns:
[
  'users.0.email' => 'a@example.com',
  'users.1.email' => null,
  'users.2.email' => 'b@example.com',
]
```

## DataMapper Wildcard Expansion

### Basic Expansion

When mapping from `users.*.email` to `emails.*`:

```php
$mapper = new DataMapper($data);
$mapper->map('users.*.email', 'emails.*');

// Each matched value is placed into emails.{i}
```

### Skip Null Values

```php
$mapper->map('users.*.email', 'emails.*', skipNull: true);

// Nulls are skipped
```

### Index Handling

#### Default (preserve gaps)

```php
$mapper->map('users.*.email', 'emails.*', reindexWildcard: false);

// Preserves numeric gaps (e.g., keep 0 and 2)
```

#### Reindex (compact)

```php
$mapper->map('users.*.email', 'emails.*', reindexWildcard: true);

// Compacts indices to sequential array [0..n-1]
```

## Root Level Numeric Indices

Paths like `0.name` are valid and target the root-level array index:

```php
$data = [
    ['name' => 'Alice'],
    ['name' => 'Bob'],
];

$accessor = new DataAccessor($data);
$name = $accessor->get('0.name'); // 'Alice'
```

## Missing Keys

### DataAccessor

Returns `null` if a path does not exist:

```php
$accessor->get('user.nonexistent.key'); // null
```

### DataMapper

With `skipNull=true`, values that resolve to `null` are skipped:

```php
$mapper->map('user.email', 'contact.email', skipNull: true);
```

## Examples

### Simple Path

```php
'user.profile.name' // Accesses $data['user']['profile']['name']
```

### Numeric Index

```php
'users.0.email' // Accesses $data['users'][0]['email']
```

### Single Wildcard

```php
'users.*.email' // Matches all emails in users array
// Returns: ['users.0.email' => 'a@x', 'users.1.email' => 'b@x']
```

### Deep Wildcards

```php
'orders.*.items.*.sku' // Matches all SKUs in all items across all orders
// Returns: ['orders.0.items.0.sku' => 'A', 'orders.0.items.1.sku' => 'B']
```

### Root-Level Index

```php
'0.name' // Accesses $data[0]['name'] when $data is a numeric array
```

## Edge Cases

### Empty Path

An empty path `""` is allowed and yields no segments. DataAccessor returns the entire data structure:

```php
$accessor = new DataAccessor(['a' => 1]);
$result = $accessor->get(''); // ['a' => 1]
```

### Invalid Paths

These paths throw `InvalidArgumentException`:

```php
'.user.name'    // Leading dot
'user.name.'    // Trailing dot
'user..name'    // Double dots
```

## Best Practices

### Use Wildcards for Collections

```php
// ✅ Good - use wildcard
$accessor->get('users.*.email');

// ❌ Bad - manual loop
foreach ($data['users'] as $i => $user) {
    $accessor->get("users.{$i}.email");
}
```

### Validate Paths

```php
// ✅ Good - validate before use
if ($accessor->containsWildcard($path)) {
    // Handle wildcard path
}

// ❌ Bad - assume path format
$accessor->get($path);
```

## See Also

- [DataAccessor](/main-classes/data-accessor/) - Read nested data
- [DataMutator](/main-classes/data-mutator/) - Modify nested data
- [DataMapper](/main-classes/data-mapper/) - Transform data
