# Data Mapper

Map values between structures using dot-paths and wildcards. Supports simple maps, structured maps, bulk mapping, and template-based mapping from named sources.

Namespace: `event4u\DataHelpers\DataMapper`

## Overview

DataMapper supports three mapping styles:

1) Simple mapping (associative):

```php
// ['source.path' => 'target.path']
$result = DataMapper::map($source, [], [
  'user.name'  => 'profile.fullname',
  'user.email' => 'profile.contact.email',
]);
```

- Wildcards: `'users.*.email' => 'emails.*'`
- Null handling: by default `skipNull=true` (null values are skipped)
- Wildcard results are expanded into the target; see `reindexWildcard` option

See API: [map](#map)


2) Structured mapping entries:

```php
$result = DataMapper::map(null, null, [[
  'source' => $userModel,
  'target' => $dto,
  'sourceMapping' => ['name','email'],
  'targetMapping' => ['profile.fullname','profile.contact.email'],
  // optional per-entry overrides
  'skipNull' => true,
  'reindexWildcard' => false,
]]);
```

Alternative: single `mapping` key instead of separate arrays

```php
// Associative
['mapping' => [ 'name' => 'profile.fullname', 'email' => 'profile.contact.email' ]]


See API: [map](#map), [mapMany](#mapmany)

// List of pairs
['mapping' => [ ['name','profile.fullname'], ['email','profile.contact.email'] ]]
```

3) Template-based mapping from named sources:

```php
$sources = [ 'user' => $userModel, 'addr' => ['street' => 'Main 1'] ];
$template = [
  'profile' => [
    'fullname' => 'user.name',
    'email'    => 'user.email',
    'street'   => 'addr.street',
  ],
];
$result = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: false);

See API: [mapFromTemplate](#mapfromtemplate)

```

- Template may be a JSON string or an array
- Strings that look like `alias.path` are resolved against the given sources
- Unknown aliases are treated as literals
- Wildcards allowed (e.g. `src.users.*.email`)

4) Inverse template-based mapping to named targets:

```php
$targets = [ 'user' => $userDto, 'addr' => [] ];
$template = [
  'profile' => [
    'fullname' => 'user.name',
    'email'    => 'user.email',
    'street'   => 'addr.street',
  ],
];
$data = [ 'profile' => ['fullname' => 'Alice', 'email' => 'a@example.com', 'street' => 'Main 1'] ];
$updatedTargets = DataMapper::mapToTargetsFromTemplate($data, $template, $targets);
// $updatedTargets['user']->name === 'Alice'
// $updatedTargets['addr']        === ['street' => 'Main 1']
```

See API: [mapToTargetsFromTemplate](#maptotargetsfromtemplate)

- Uses the same template shape, but writes into provided targets using alias paths

5) Auto-map by matching field names (optional deep):

```php
// Shallow (top-level) name matching
$result = DataMapper::autoMap($jsonOrArray, $modelOrArray);
// Deep mode flattens source and maps nested fields and lists
$result = DataMapper::autoMap($source, $target, deep: true);
```

See API: [autoMap](#automap)

## Transforms

Structured mappings can apply per-field transformations before writing to the target.

- Provide a `transforms` array on each structured entry
- For `sourceMapping`/`targetMapping`: use a list aligned by index
- For `mapping`:
  - Associative mapping: `transforms` can be associative keyed by source path, or a list aligned by iteration order
  - List-of-pairs: `transforms` is a list aligned by pair index
- Transforms are callables (e.g. `'strtoupper'`, `['Class', 'method']`, or closures)
- `skipNull` applies before and after transform: null inputs are skipped, and if a transform returns null it is skipped when `skipNull=true`

Examples:

```php
// Index-aligned transforms with source/target arrays
DataMapper::map(null, [], [[
  'source' => ['name' => 'Alice', 'email' => 'ALICE@EXAMPLE.COM'],
  'target' => [],
  'sourceMapping' => ['name', 'email'],
  'targetMapping' => ['out.nameUpper', 'out.emailLower'],
  'transforms' => ['strtoupper', 'strtolower'],
]]);
// => ['out' => ['nameUpper' => 'ALICE', 'emailLower' => 'alice@example.com']]

// Associative mapping with transforms keyed by source path
DataMapper::map(null, null, [[
  'source' => ['user' => ['name' => 'Alice', 'email' => 'ALICE@EXAMPLE.COM']],
  'target' => [],
  'mapping' => [
    'user.name' => 'profile.fullname',
    'user.email' => 'profile.email',
  ],
  'transforms' => [
    'user.name' => 'strtoupper',
    'user.email' => 'strtolower',
  ],
]]);

// Wildcard: transform is applied to each element
DataMapper::map(null, [], [[
  'source' => ['users' => [ ['email' => 'a@x'], ['email' => null], ['email' => 'b@x'] ]],
  'target' => [],
  'sourceMapping' => ['users.*.email'],
  'targetMapping' => ['out.*'],
  'skipNull' => true,
  'reindexWildcard' => true,
  'transforms' => [static fn($v) => is_string($v) ? strtoupper($v) : $v],
]]);
// => ['out' => ['A@X', 'B@X']]
```

## Replace

Structured mappings can perform value replacements after transforms and optional trims, before post-transform hooks.

- Declare `replaces` on the structured entry
- Works with both `sourceMapping`/`targetMapping` arrays and associative/list `mapping`
- Matching rules:
  - Exact match for `string|int` keys
  - Case-insensitive string matching when `caseInsensitiveReplace=true` (map-level parameter)
  - Arrays/objects are not replaced
- Processing order per value: transform → trim (if `trimValues=true`) → replace → postTransform

Example (associative mapping with case-insensitive replace):

```php
DataMapper::map(null, [], [[
  'source' => ['order' => ['status' => 'bezahlt' ]],
  'target' => [],
  'mapping' => [ 'order.status' => 'dto.paymentStatus' ],
  'replaces' => [ 'order.status' => [ 'BEZAHLT' => 'PAID' ] ],
]], caseInsensitiveReplace: true);
// => ['dto' => ['paymentStatus' => 'PAID']]
```


- Wildcards in targets supported (e.g. `people.*.name`) with `skipNull`/`reindexWildcard`

## Hooks

Hooks let you observe and customize mapping at various stages. You can pass hooks to `map()`/`mapMany()` via the `$hooks` parameter.

Typed hook contexts are passed to callbacks. Use the typed context classes to access hook data and paths.

Supported hook names and preferred signatures:

- beforeAll: `function(App\Helpers\DataMapper\AllContext $context): void|bool`
- afterAll: `function(App\Helpers\DataMapper\AllContext $context): void`
- beforeEntry (structured only): `function(App\Helpers\DataMapper\EntryContext $context): void|bool`
- afterEntry (structured only): `function(App\Helpers\DataMapper\EntryContext $context): void`
- beforePair: `function(App\Helpers\DataMapper\PairContext $context): void|bool`
- afterPair: `function(App\Helpers\DataMapper\PairContext $context): void`
- preTransform: `function(mixed $value, App\Helpers\DataMapper\PairContext $context): mixed`
- postTransform: `function(mixed $value, App\Helpers\DataMapper\PairContext $context): mixed`
- beforeWrite: `function(mixed $value, App\Helpers\DataMapper\WriteContext $context): mixed` (return `'__skip__'` to skip)
- afterWrite: `function(array|object $target, App\Helpers\DataMapper\WriteContext $context, mixed $writtenValue): array|object`

Context helpers and properties:
- `$context->mode()` returns string; `$context->modeEnum()` returns enum `App\Helpers\DataMapper\Mode` (`Simple`, `Structured`, `StructuredAssoc`, `StructuredPairs`)
- `PairContext`: `pairIndex`, `srcPath()`, `tgtPath()`, `wildcardIndex`, `source`, `target`
- `WriteContext`: adds `resolvedTargetPath`
- `AllContext`: `mapping`, `source`, `target`
- `EntryContext`: `entry`, `source`, `target`
- Access members directly via typed properties/methods, e.g. `$ctx->srcPath()` or `$ctx->mode()`

Path-prefix filtering for hooks:
- Provide hooks as an associative array with optional filters as keys:
  - `src:<prefix>` applies only if `srcPath` matches the prefix (supports trailing `*`)
  - `tgt:<prefix>` applies only if `tgtPath` matches the prefix (supports trailing `*`)
  - `mode:<simple|structured|structured-assoc|structured-pairs>` restricts to a mapping mode

Example with typed contexts:

```php
use App\Enums\DataMapperHook;
use App\Helpers\DataMapper\WriteContext;

$hooks = [
  DataMapperHook::BeforePair->value => [
    'src:user.*'   => function(App\Helpers\DataMapper\PairContext $ctx) { /* ... */ },
    'mode:simple'  => function(App\Helpers\DataMapper\PairContext $ctx) { /* ... */ },
  ],
  DataMapperHook::PreTransform->value => fn($v) => is_string($v) ? trim($v) : $v,
  DataMapperHook::BeforeWrite->value  => function($v, WriteContext $ctx) {
      return $v === '' ? '__skip__' : $v;
  },
  DataMapperHook::AfterWrite->value   => function (array|object $t, WriteContext $ctx, mixed $written) {
      if (is_string($written)) {
          return App\Helpers\DataMutator::set($t, (string)($ctx['resolvedTargetPath'] ?? ''), strtoupper($written));
      }
      return $t;
  },
];
```

### Safer hook names via enum

Use the enum `App\\Enums\\DataMapperHook` to avoid typos in hook names. Arrays require string keys, so use `->value` when defining the hooks array.

### Hook builder utility

Convenience methods help reduce boilerplate for common filters:

- onForSrc(name, 'user.*', callable)
- onForTgt(name, 'profile.*', callable)
- onForMode(name, 'simple'|'structured', callable)
- onForModeEnum(name, App\\Helpers\\DataMapper\\Mode::..., callable)
- onForPrefix(name, '<prefix>', callable) // matches either srcPath or tgtPath once


Example:

```php
$hooks = DataMapperHooks::make()
  ->onForMode(DataMapperHook::BeforeAll, 'simple', fn(App\Helpers\DataMapper\AllContext $ctx) => null)
  ->onForSrc(DataMapperHook::BeforePair, 'user.name', fn(App\Helpers\DataMapper\PairContext $ctx) => false)
  ->onForTgt(DataMapperHook::BeforeWrite, 'profile.', fn($v, App\Helpers\DataMapper\WriteContext $ctx) => '__skip__')
  ->toArray();
```

```php
use App\Enums\DataMapperHook;
use App\Helpers\DataMapper\Mode;

$hooks = DataMapperHooks::make()
  ->onForModeEnum(DataMapperHook::BeforeAll, Mode::Simple, fn(App\Helpers\DataMapper\AllContext $ctx) => null)
  ->toArray();
```

```php
// Matches either srcPath or tgtPath with a single predicate (no double-calls)
$hooks = DataMapperHooks::make()
  ->onForPrefix(DataMapperHook::PreTransform, 'users.*.email', fn($v) => is_string($v) ? strtoupper($v) : $v)
  ->onForPrefix(DataMapperHook::BeforeWrite, 'profile.', fn($v) => '__skip__')
  ->toArray();
```


You can also build with enum keys using the helper and merge sets; later definitions override earlier ones.

Notes:
- Returning `false` from `beforeAll`, `beforeEntry`, or `beforePair` cancels the respective scope.
- `beforeWrite` can return `'__skip__'` to skip writing the current value.
- Hooks declared both globally and per-entry are merged; per-entry hooks run after globals.
- Legacy array context remains supported for backward compatibility. Prefer typed contexts going forward.


## API

### map

```php
map(
  mixed $source,
  mixed $target,
  array $mapping,
  bool $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false
): mixed
```

- `skipNull`: skip null values when reading source values
- `reindexWildcard`: if true, wildcard expansions compact indices (0..n-1). If false, original numeric keys are preserved (e.g. `[0 => 'a', 2 => 'b']`).
- `hooks`: typed hook callbacks as documented above
- `trimValues` (default `true`): trims string values before replacement matching
- `caseInsensitiveReplace` (default `false`): enables case-insensitive matching of string replacement keys
- Structured entries can still override `skipNull`/`reindexWildcard`; see [Replace](#replace) for declaring `replaces` per entry.


### mapToTargetsFromTemplate

```php
mapToTargetsFromTemplate(array|string $data, array|string $template, array $targets, bool $skipNull = true, bool $reindexWildcard = false): array
```

- Applies values from `$data` onto the provided `$targets` according to alias dot-paths in `$template`
- `$data` and `$template` accept arrays or JSON strings
- `skipNull`: skip writing nulls (including nulls within wildcard arrays)
- `reindexWildcard`: compact indices when writing wildcard arrays (0..n-1) instead of preserving gaps

Example with wildcards:

```php
$targets = ['people' => []];
$template = ['names' => 'people.*.name'];
$data     = ['names' => ['Alice', null, 'Bob']];

// preserve gaps
$res = DataMapper::mapToTargetsFromTemplate($data, $template, $targets, skipNull: true, reindexWildcard: false);
// ['people' => [ 0 => ['name' => 'Alice'], 2 => ['name' => 'Bob'] ]]

// reindex
$res = DataMapper::mapToTargetsFromTemplate($data, $template, $targets, skipNull: true, reindexWildcard: true);
// ['people' => [ ['name' => 'Alice'], ['name' => 'Bob'] ]]
```

### mapMany

```php
mapMany(
  array $mappings,
  bool $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false
): array
```

- Accepts an array of structured mapping entries
- Each entry can override `skipNull` and `reindexWildcard`
- Global `hooks`, `trimValues`, `caseInsensitiveReplace` apply to all entries
- Returns an array of updated targets


### autoMap

```php
autoMap(
  mixed $source,
  mixed $target,
  bool $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false,
  bool $deep = false
): mixed
```

- Shallow mode (default): matches top-level field names between source and target
- Object targets: snake_case source keys map to camelCase top-level properties when available
- Deep mode (`deep=true`): flattens nested structures to dot-paths and maps nested fields and wildcard lists (numeric indices become `*`)
- Respects `skipNull`, `reindexWildcard`, `hooks`, `trimValues`, `caseInsensitiveReplace`
- Source can be a JSON string or array/DTO/Model/Collection

### mapFromTemplate

```php
mapFromTemplate(array|string $template, array $sources, bool $skipNull = true, bool $reindexWildcard = false): array
```

- Builds a structure by resolving `alias.path` strings against named sources
- `skipNull`: remove keys that resolve to null (including filtering nulls inside wildcard arrays)
- `reindexWildcard`: when true, wildcard arrays are sorted by numeric key and reindexed sequentially

## Wildcards

- In simple/structured mappings, wildcard results from the source (e.g. `users.*.email`) are expanded into the target path's wildcard (e.g. `emails.*`).
- By default, if a matched value is null and `skipNull=true`, the index is skipped and (unless `reindexWildcard=true`) the numeric position is preserved in the target (e.g. index 1 missing).

## Examples

### Simple mapping with wildcards

```php
DataMapper::map([
  'users' => [
    ['email' => 'a@example.com'],
    ['email' => null],
    ['email' => 'b@example.com'],
  ],
], [], [ 'users.*.email' => 'emails.*' ], skipNull: true, reindexWildcard: false);
// ['emails' => [0 => 'a@example.com', 2 => 'b@example.com']]
```

### Structured mapping per-entry overrides

```php
DataMapper::map(null, [], [[
  'source' => $source,
  'target' => [],
  'sourceMapping' => ['users.*.email'],
  'targetMapping' => ['emails.*'],
  'skipNull' => true,
  'reindexWildcard' => true, // compact indices
]], true, false);
// ['emails' => ['a@example.com', 'b@example.com']]
```

### Template JSON with reindexing

```php
$sources = ['src' => ['users' => [ ['email' => 'a@example.com'], ['email' => null], ['email' => 'b@example.com'] ]]];
$json = '{"emails":"src.users.*.email"}';
DataMapper::mapFromTemplate($json, $sources, skipNull: true, reindexWildcard: true);
// ['emails' => ['a@example.com', 'b@example.com']]
```


## Recipes

### 1) DTO → Model via autoMap (deep)

```php
class UserDto { public string $name = 'Alice'; public array $address = ['street' => 'Main 1']; }
$user = new App\Models\User();
$dto  = new UserDto();

// Maps top-level by name; deep=true maps nested structures (lists use `*`)
$user = DataMapper::autoMap($dto, $user, deep: true);
```

### 2) Case-insensitive status mapping with trim

```php
$src = ['order' => ['status' => ' bezahlt ']];
$mapping = [[
  'source' => $src,
  'target' => [],
  'mapping' => ['order.status' => 'dto.paymentStatus'],
  'replaces' => ['order.status' => ['BEZAHLT' => 'PAID']],
]];
$res = DataMapper::map(null, [], $mapping, caseInsensitiveReplace: true, trimValues: true);
// ['dto' => ['paymentStatus' => 'PAID']]
```

### 3) Wildcards: preserve gaps vs reindex

```php
$src = ['users' => [ ['email' => 'a@x'], ['email' => null], ['email' => 'b@x'] ]];
$map = ['users.*.email' => 'emails.*'];

$preserve = DataMapper::map($src, [], $map, skipNull: true, reindexWildcard: false);
// ['emails' => [0 => 'a@x', 2 => 'b@x']]

$reindex  = DataMapper::map($src, [], $map, skipNull: true, reindexWildcard: true);
// ['emails' => ['a@x', 'b@x']]
```

### 4) Template from named sources with wildcard + reindex

```php
$sources  = ['src' => ['users' => [['name' => 'A'], ['name' => null], ['name' => 'B']]]];
$template = ['names' => 'src.users.*.name'];

$out = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: true);
// ['names' => ['A', 'B']]
```

## Best practices

- **Start simple**: Begin with simple associative mappings before moving to structured or template-based approaches.
- **Use templates for complex transformations**: When mapping from multiple sources or building complex nested structures, templates are clearer than manual mappings.
- **Leverage hooks**: Use hooks for validation, logging, or custom transformations instead of post-processing.
- **Prefer typed contexts**: When writing hooks, use the typed context classes (PairContext, WriteContext, etc.) for better IDE support.
- **AutoMap for DTOs**: When mapping API responses to DTOs, autoMap with `deep: true` saves time and reduces boilerplate.
- **Test edge cases**: Always test with null values, empty arrays, and missing paths to ensure your mappings handle edge cases gracefully.

## Performance notes

- Wildcards traverse all matching elements, so performance scales with the number of matches.
- Deep wildcards can be expensive on large nested structures.
- Hooks add overhead; use them judiciously for performance-critical code.
- For very large datasets, consider batching operations or using direct array manipulation.

## Common patterns

### Map API response to DTO

```php
$apiResponse = ['user_name' => 'Alice', 'user_email' => 'alice@example.com'];
$dto = new #[\AllowDynamicProperties] class {
  public string $name = '';
  public string $email = '';
};

$dto = DataMapper::autoMap($apiResponse, $dto, deep: false);
// $dto->name === 'Alice', $dto->email === 'alice@example.com'
```

### Transform and validate with hooks

```php
use event4u\DataHelpers\Enums\DataMapperHook;

$hooks = [
  DataMapperHook::PreTransform->value => fn($v) => is_string($v) ? trim($v) : $v,
  DataMapperHook::AfterWrite->value => function($ctx) {
    if ($ctx->targetPath === 'email' && !filter_var($ctx->value, FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException("Invalid email: {$ctx->value}");
    }
  },
];

$result = DataMapper::map($source, [], [['user.email' => 'email']], hooks: $hooks);
```

### Build from multiple sources

```php
$sources = [
  'user' => User::first(),
  'config' => ['currency' => 'EUR', 'timezone' => 'Europe/Berlin'],
  'stats' => ['orders' => 42],
];

$template = [
  'invoice' => [
    'customer_name' => 'user.name',
    'currency' => 'config.currency',
    'total_orders' => 'stats.orders',
  ],
];

$invoice = DataMapper::mapFromTemplate($template, $sources);
```

### Reindex wildcards for clean arrays

```php
$source = ['users' => [['email' => 'a@x'], ['email' => null], ['email' => 'b@x']]];
$result = DataMapper::map($source, [], [['users.*.email' => 'emails.*']], skipNull: true, reindexWildcard: true);
// ['emails' => ['a@x', 'b@x']] // Clean sequential array without gaps
```

## Troubleshooting

### Null values not skipped

**Problem**: Null values appear in the result even though `skipNull: true` is set.

**Solution**: Ensure `skipNull: true` is passed to the mapping method. For structured mappings, check per-entry overrides.

### Wildcard indices not reindexed

**Problem**: Wildcard results have gaps (e.g., `[0 => 'a', 2 => 'b']`).

**Solution**: Set `reindexWildcard: true` to get sequential indices (`[0 => 'a', 1 => 'b']`).

### Hooks not firing

**Problem**: Hooks are not being called during mapping.

**Solution**: Verify hook keys match enum values (e.g., `DataMapperHook::PreTransform->value`). Use `DataMapperHooks::make()` builder for type safety.

### Template aliases not resolved

**Problem**: Template values appear as literal strings instead of resolved values.

**Solution**: Ensure the alias prefix matches a key in the `$sources` array. Unknown aliases are treated as literals.

## Additional examples

### Chaining multiple mappings

```php
$step1 = DataMapper::map($source, [], [['a.b' => 'x.y']]);
$step2 = DataMapper::map($step1, [], [['x.y' => 'final.value']]);
```

### Using hooks for logging

```php
$hooks = [
  DataMapperHook::BeforeWrite->value => function($ctx) {
    error_log("Writing {$ctx->value} to {$ctx->targetPath}");
  },
];

$result = DataMapper::map($source, [], [['a' => 'b']], hooks: $hooks);
```

### Complex template with nested wildcards

```php
$sources = [
  'orders' => [
    'data' => [
      ['id' => 1, 'items' => [['sku' => 'A', 'qty' => 2], ['sku' => 'B', 'qty' => 1]]],
      ['id' => 2, 'items' => [['sku' => 'C', 'qty' => 3]]],
    ],
  ],
];

$template = [
  'order_items' => 'orders.data.*.items.*.sku',
];

$result = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: true);
// ['order_items' => ['A', 'B', 'C']]
```

### Conditional mapping with beforePair hook

```php
$hooks = [
  DataMapperHook::BeforePair->value => function($ctx) {
    // Skip mapping if source value is empty string
    if ($ctx->sourceValue === '') {
      return false; // Skip this pair
    }
  },
];

$result = DataMapper::map(['name' => '', 'email' => 'a@x'], [], [['name' => 'userName'], ['email' => 'userEmail']], hooks: $hooks);
// ['userEmail' => 'a@x'] // 'name' was skipped
```

## Notes

- Accessor wildcard results are internally normalized when used in templates (numeric index extraction).
- For deterministic behavior with numeric arrays, Mutator uses index-based replace logic during merges.
- Mapper favors immutability of inputs and returns the updated target.

