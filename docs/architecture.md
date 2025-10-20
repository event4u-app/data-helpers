# Architecture

This document describes the internal architecture of the Laravel Data Helpers package with a focus on the DataMapper refactor. The public
API remains stable while the implementation is split into small, focused components.

## Overview

DataMapper acts as a facade and delegates work to specialized components that each follow a single responsibility:

- MappingEngine – Core mapping logic for simple and structured mappings, value flow, hook invocation, and writes
- WildcardHandler – Normalization of wildcard results and safe iteration (skip nulls, optional reindex)
- HookInvoker – Hook normalization, prefix filtering (src:/tgt:/mode:), and legacy array-context compatibility
- TemplateMapper – Template-based mapping from named sources and inverse writes to named targets
- AutoMapper – Automatic mapping (source → target) with optional deep mode
- ValueFilter – Utilities for replacements, case conversion, property checks

High-level flow for `DataMapper::map()`:

1) Normalize hooks (enums → strings, arrays of callables allowed)
2) For simple mappings: iterate associative pairs (src → tgt)
3) For each pair: Access source via DataAccessor
4) Apply hooks: beforePair → beforeTransform
5) If source path contains wildcard: normalize wildcard arrays and iterate items
6) For each value: afterTransform → beforeWrite (may return '__skip__') → write via DataMutator → afterWrite
7) afterPair / afterAll hooks close the cycle

## Wildcards

- DataAccessor returns wildcard reads as arrays keyed by dot-paths (e.g., `['users.0.email' => 'a@x', 'users.2.email' => 'b@x']`).
- WildcardHandler::normalizeWildcardArray() flattens such arrays to a simple list preserving order (e.g., `['a@x','b@x']`). This avoids
  collisions with deep wildcards while keeping deterministic order.
- iterateWildcardItems($items, $skipNull, $reindex, $onSkip, $onItem) controls skipping nulls and optional reindexing (0..n-1). When
  reindex=false, original numeric keys are preserved.

## Hooks

- Typed contexts (AllContext, EntryContext, PairContext, WriteContext) are passed to callbacks by default.
- Prefix filtering: use `src:<prefix>`, `tgt:<prefix>`, or `mode:<simple|structured|structured-assoc|structured-pairs>` keys to scope
  individual callbacks.

Hook order per pair (simplified):

- beforePair → beforeTransform → (wildcard? iterate items) → afterTransform → beforeWrite → write → afterWrite → afterPair

## Template mapping

- TemplateMapper resolves strings like `alias.path` against a map of named sources.
- For wildcard results inside templates, TemplateMapper normalizes arrays, optionally filters nulls (skipNull), and can reindex
  sequentially.
- Inverse mapping (`mapToTargetsFromTemplate`) writes values back into alias targets and supports wildcards in target paths.

## AutoMap

- Shallow mode maps top-level keys; object targets prefer camelCase when the property exists.
- Deep mode flattens nested structures; numeric indices in the source become `*`, enabling wildcard writes to targets.

## Backward compatibility

- Typed context classes provide better IDE support and static analysis.

## Extensibility guidelines

- Prefer DataMapper as the entry point; use component classes directly only for advanced scenarios.
- Keep new helpers small and focused; follow existing naming and behavior patterns (immutability, typed contexts, docblocks).
- When adding hooks, ensure predictable order and prefix-filter handling; extend HookInvoker when necessary.
- For performance-sensitive cases, consider batching operations and minimizing DataAccessor calls.

## How to extend

This section shows practical patterns to extend DataMapper without breaking the public API.

### 1) Add custom hook behavior

Prefer composing hooks via arrays. Use prefix filters (`src:`, `tgt:`, `mode:`) to scope callbacks.

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

### 3) Extend HookInvoker for new filters

If you need a new filter key (e.g. `srcRegex:`), extend HookInvoker and override filtering.

```php
class CustomHookInvoker extends \event4u\DataHelpers\DataMapper\HookInvoker {
  protected static function matchPrefixPattern(string $value, string $pattern): bool {
    if (str_starts_with($pattern, 'regex:')) {
      return (bool) preg_match(substr($pattern, 6), $value);
    }
    return parent::matchPrefixPattern($value, $pattern);
  }
}
```

Use your invoker in an advanced flow if needed.

### 4) Custom wildcard iteration behavior

Wrap `WildcardHandler::iterateWildcardItems()` to add metrics, logging, or special skip rules.

```php
function iterateEmails(array $items, callable $onItem): void {
  $items = \event4u\DataHelpers\DataMapper\WildcardHandler::normalizeWildcardArray($items);
  \event4u\DataHelpers\DataMapper\WildcardHandler::iterateWildcardItems(
    $items, skipNull: true, reindexWildcard: true, onSkip: null,
    onItem: function (int $i, $v) use ($onItem) {
      if (!is_string($v)) { return true; }
      return $onItem($i, strtolower($v));
    }
  );
}
```

### 5) Add a focused helper component

Keep helpers small and side‑effect free. Follow naming, docblocks, and immutability conventions.

```php
namespace App\Support; // example package-local helper

final class PhoneNormalizer {
  public static function e164(?string $raw): ?string {
    if ($raw === null || $raw === '') return null;
    // minimal demo; replace with real formatting
    $digits = preg_replace('/[^0-9+]/', '', $raw);
    return $digits ?: null;
  }
}
```

Use with transforms or hooks:

```php
$entry = [
  'source' => ['user' => ['phone' => ' (030) 123 45 ']],
  'target' => [],
  'mapping' => ['user.phone' => 'dto.phone'],
  'transforms' => [ 'user.phone' => [App\Support\PhoneNormalizer::class, 'e164'] ],
];
```

### 6) Performance considerations

- Prefer simple mappings over templates when possible.
- Combine related transforms into a single callable to reduce overhead.
- Avoid heavy work in `beforePair`; prefer `beforeTransform`/`beforeWrite` for value-specific logic.

## How to test extensions

Use Pest for fast feedback. Run the full suite or a single test/file.

- All tests: `task test:run`
- Single file: `task test:unit -- tests/Unit/Helpers/DataMapperTest.php`
- Filter by name: `task test:unit -- tests/Unit/Helpers/DataMapperTest.php --filter="wildcards"`

Example: test a custom hook that skips odd indices for `users.*.email`.

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\PairContext;

it('skips odd indices via hook', function () {
    $src = ['users' => [ ['email' => 'a@x'], ['email' => 'b@x'], ['email' => 'c@x'] ]];
    $hooks = [
        DataMapperHook::BeforePair->value => [
            'src:users.*.email' => function (PairContext $ctx) {
                return ((int)($ctx->wildcardIndex ?? -1)) % 2 === 1 ? false : null;
            },
        ],
    ];

    $out = DataMapper::map($src, [], ['users.*.email' => 'emails.*'], skipNull: true, reindexWildcard: true, hooks: $hooks);
    expect($out)->toEqual(['emails' => ['a@x', 'c@x']]);
});
```
