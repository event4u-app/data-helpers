---
title: Architecture
description: Internal architecture and design patterns of Data Helpers
---

This document describes the internal architecture of Data Helpers with a focus on the DataMapper implementation. The public API remains stable while the implementation is split into small, focused components.

## Introduction

DataMapper acts as a facade and delegates work to specialized components that each follow a single responsibility:

- **MappingEngine** - Core mapping logic for simple and structured mappings, value flow, hook invocation, and writes
- **WildcardHandler** - Normalization of wildcard results and safe iteration (skip nulls, optional reindex)
- **HookInvoker** - Hook normalization, prefix filtering (src:/tgt:/mode:), and legacy array-context compatibility
- **TemplateMapper** - Template-based mapping from named sources and inverse writes to named targets
- **AutoMapper** - Automatic mapping (source → target) with optional deep mode
- **ValueFilter** - Utilities for replacements, case conversion, property checks

## High-Level Flow

The `DataMapper::source()->template()->map()` method follows this flow:

1. Normalize hooks (enums → strings, arrays of callables allowed)
2. For simple mappings: iterate associative pairs (src → tgt)
3. For each pair: Access source via DataAccessor
4. Apply hooks: beforePair → beforeTransform
5. If source path contains wildcard: normalize wildcard arrays and iterate items
6. For each value: afterTransform → beforeWrite (may return '__skip__') → write via DataMutator → afterWrite
7. afterPair / afterAll hooks close the cycle

## Core Components

### DataAccessor

Reads values from nested data structures using dot-notation paths.

**Features:**
- Supports arrays, objects, Laravel Collections, Eloquent Models
- Wildcard support (`users.*.email`)
- Numeric indices (`users.0.name`)
- Safe access with default values

**Example:**
```php
$accessor = new DataAccessor(['users' => [['name' => 'Alice']]]);
$name = $accessor->get('users.0.name'); // 'Alice'
$emails = $accessor->get('users.*.email'); // ['users.0.email' => 'alice@example.com']
```

### DataMutator

Writes values to nested data structures using dot-notation paths.

**Features:**
- Creates nested structures automatically
- Supports wildcards for batch writes
- Handles arrays and objects
- Immutable operations (returns new structure)

**Example:**
```php
$mutator = new DataMutator([]);
$result = $mutator->set('user.name', 'Alice');
// Result: ['user' => ['name' => 'Alice']]
```

### WildcardHandler

Handles wildcard operations and normalization.

**Features:**
- Normalizes wildcard results to simple arrays
- Skips null values (optional)
- Reindexes arrays (optional)
- Preserves deterministic order

**Wildcard Results:**

DataAccessor returns wildcard reads as arrays keyed by dot-paths:
```php
['users.0.email' => 'a@x', 'users.2.email' => 'b@x']
```

WildcardHandler normalizes to simple list:
```php
['a@x', 'b@x']
```

**Example:**
```php
$items = ['users.0.email' => 'a@x', 'users.2.email' => 'b@x'];
$normalized = WildcardHandler::normalizeWildcardArray($items);
// Result: ['a@x', 'b@x']

WildcardHandler::iterateWildcardItems(
    $normalized,
    skipNull: true,
    reindexWildcard: true,
    onSkip: null,
    onItem: fn($i, $v) => echo "$i: $v\n"
);
```

### HookInvoker

Manages hook execution and filtering.

**Features:**
- Typed contexts (AllContext, EntryContext, PairContext, WriteContext)
- Prefix filtering: `src:<prefix>`, `tgt:<prefix>`, `mode:<mode>`
- Legacy array-context compatibility
- Predictable hook order

**Hook Order per Pair:**
```
beforePair → beforeTransform → (wildcard? iterate items) →
afterTransform → beforeWrite → write → afterWrite → afterPair
```

**Example:**
```php
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\PairContext;

$hooks = [
    DataMapperHook::BeforePair->value => [
        'src:users.*.email' => function (PairContext $ctx) {
            // Cancel this pair if user index is odd
            return ((int)($ctx->wildcardIndex ?? -1)) % 2 === 1 ? false : null;
        },
    ],
    DataMapperHook::BeforeTransform->value => fn($v) => is_string($v) ? trim($v) : $v,
];
```

### TemplateMapper

Resolves template expressions with named sources.

**Features:**
- Resolves `alias.path` against named sources
- Supports wildcards in templates
- Inverse mapping (write back to targets)
- Null filtering and reindexing

**Example:**
```php
$sources = [
    'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
    'order' => ['id' => 123, 'total' => 99.99],
];

$template = 'user.name'; // Resolves to 'Alice'
$template = 'order.total'; // Resolves to 99.99
```

### AutoMapper

Automatic mapping between source and target structures.

**Modes:**
- **Shallow** - Maps top-level keys only
- **Deep** - Flattens nested structures, converts numeric indices to wildcards

**Example:**
```php
$source = ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']];
$target = new UserDto();

// Shallow mode
$result = AutoMapper::autoMap($source, $target, deep: false);

// Deep mode (flattens nested structures)
$result = AutoMapper::autoMap($source, $target, deep: true);
```

## Design Patterns

### Single Responsibility

Each component has a single, well-defined responsibility:
- DataAccessor: Read values
- DataMutator: Write values
- WildcardHandler: Handle wildcards
- HookInvoker: Execute hooks
- TemplateMapper: Resolve templates
- AutoMapper: Automatic mapping

### Immutability

All operations return new structures instead of modifying existing ones:

```php
$mutator = new DataMutator(['name' => 'Alice']);
$result = $mutator->set('email', 'alice@example.com');
// Original: ['name' => 'Alice']
// Result: ['name' => 'Alice', 'email' => 'alice@example.com']
```

### Typed Contexts

Hook contexts provide type safety and IDE support:

```php
use event4u\DataHelpers\DataMapper\PairContext;

$hook = function (PairContext $ctx) {
    // IDE knows about $ctx->sourcePath, $ctx->targetPath, etc.
    return $ctx->value;
};
```

## Extensibility

### 1. Custom Hook Behavior

Use prefix filters to scope callbacks:

```php
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\PairContext;

$hooks = [
    DataMapperHook::BeforePair->value => [
        'src:users.*.email' => function (PairContext $ctx) {
            // Only for users.*.email paths
            return $ctx->value;
        },
    ],
    DataMapperHook::BeforeTransform->value => fn($v) => is_string($v) ? trim($v) : $v,
];
```

### 2. Custom HookInvoker

Extend HookInvoker for new filter patterns:

```php
use event4u\DataHelpers\DataMapper\HookInvoker;

class CustomHookInvoker extends HookInvoker
{
    protected static function matchPrefixPattern(string $value, string $pattern): bool
    {
        if (str_starts_with($pattern, 'regex:')) {
            return (bool) preg_match(substr($pattern, 6), $value);
        }
        return parent::matchPrefixPattern($value, $pattern);
    }
}
```

### 3. Custom Wildcard Iteration

Wrap WildcardHandler for custom behavior:

```php
use event4u\DataHelpers\DataMapper\WildcardHandler;

function iterateEmails(array $items, callable $onItem): void
{
    $items = WildcardHandler::normalizeWildcardArray($items);
    WildcardHandler::iterateWildcardItems(
        $items,
        skipNull: true,
        reindexWildcard: true,
        onSkip: null,
        onItem: function (int $i, $v) use ($onItem) {
            if (!is_string($v)) {
                return true;
            }
            return $onItem($i, strtolower($v));
        }
    );
}
```

### 4. Custom Helper Components

Create focused, side-effect-free helpers:

```php
namespace App\Support;

final class PhoneNormalizer
{
    public static function e164(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $digits = preg_replace('/[^0-9+]/', '', $raw);
        return $digits ?: null;
    }
}
```

Use with property filters:

```php
$result = DataMapper::source(['user' => ['phone' => ' (030) 123 45 ']])
    ->template(['dto.phone' => '{{ user.phone }}'])
    ->property('dto.phone')
        ->setFilter([App\Support\PhoneNormalizer::class, 'e164'])
        ->end()
    ->map()
    ->getTarget();
```

## Performance Considerations

### Best Practices

- ✅ Prefer simple mappings over templates when possible
- ✅ Combine related transforms into a single callable
- ✅ Avoid heavy work in `beforePair`; prefer `beforeTransform`/`beforeWrite`
- ✅ Batch operations when possible
- ✅ Minimize DataAccessor calls

### Optimization Tips

```php
// ❌ Slow: Multiple accessor calls
foreach ($paths as $path) {
    $value = $accessor->get($path);
    // Process value
}

// ✅ Fast: Single wildcard call
$values = $accessor->get('users.*.email');
foreach ($values as $email) {
    // Process email
}
```

## Testing Extensions

Use Pest for fast feedback:

```bash
# All tests
task test:run

# Single file
task test:unit -- tests/Unit/Helpers/DataMapperTest.php

# Filter by name
task test:unit -- --filter="wildcards"
```

### Example Test

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\PairContext;

it('skips odd indices via hook', function () {
    $src = ['users' => [
        ['email' => 'a@x'],
        ['email' => 'b@x'],
        ['email' => 'c@x'],
    ]];

    $hooks = [
        DataMapperHook::BeforePair->value => [
            'src:users.*.email' => function (PairContext $ctx) {
                return ((int)($ctx->wildcardIndex ?? -1)) % 2 === 1 ? false : null;
            },
        ],
    ];

    $out = DataMapper::source($src)
        ->template(['emails.*' => '{{ users.*.email }}'])
        ->skipNull(true)
        ->reindexWildcard(true)
        ->hooks($hooks)
        ->map()
        ->getTarget();

    expect($out)->toEqual(['emails' => ['a@x', 'c@x']]);
});
```

## Next Steps

- [Development Setup](/guides/development-setup/) - Setup your environment
- [Contributing Guide](/guides/contributing/) - Learn how to contribute
- [DataMapper Documentation](/main-classes/data-mapper/) - Learn about DataMapper
- [Advanced Features](/advanced/hooks-events/) - Explore advanced features

