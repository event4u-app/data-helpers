# Data Mapper

Map values between structures using dot-paths and wildcards. Supports simple maps, structured maps, bulk mapping, template-based mapping, and **modern pipeline API** with reusable filters.

Namespace: `event4u\DataHelpers\DataMapper`

## Overview

DataMapper supports three mapping styles plus the new **Pipeline API**:

1) **Nested mapping (recommended)** - Define target structure with source paths:

```php
// Nested structure: target => source
$result = DataMapper::map($source, [], [
  'profile' => [
    'fullname' => '{{ user.name }}',
    'contact' => [
      'email' => '{{ user.email }}',
    ],
  ],
]);
```

- Intuitive: Define the target structure you want
- Readable: See the output structure at a glance
- **Template Syntax:** Source paths must be wrapped in `{{ }}` (e.g., `'{{ user.name }}'`)
- **Static Values:** Values without `{{ }}` are treated as static strings (e.g., `'admin'`)
- Wildcards: `'emails' => ['*' => '{{ users.*.email }}']`
- Null handling: by default `skipNull=true` (null values are skipped)
- Wildcard results are expanded into the target; see `reindexWildcard` option

2) **Simple mapping (legacy)** - Flat associative array:

```php
// Flat format: target => source (still supported)
$result = DataMapper::map($source, [], [
  'profile.fullname'       => '{{ user.name }}',
  'profile.contact.email'  => '{{ user.email }}',
]);
```

**Note:** Source paths must be wrapped in `{{ }}`. Values without `{{ }}` are treated as static strings.

See API: [map](#map)

3) Structured mapping entries:

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
// Associative (source => target in structured mappings - no {{ }} needed here)
['mapping' => [ 'name' => 'profile.fullname', 'email' => 'profile.contact.email' ]]

// List of pairs
['mapping' => [ ['name','profile.fullname'], ['email','profile.contact.email'] ]]
```

**Note:** In structured mappings, the format is `source => target` (reversed from simple mappings), and `{{ }}` is not required.

4) Template-based mapping from named sources:

```php
$sources = [ 'user' => $userModel, 'addr' => ['street' => 'Main 1'] ];
$template = [
  'profile' => [
    'fullname' => '{{ user.name }}',
    'email'    => '{{ user.email }}',
    'street'   => '{{ addr.street }}',
  ],
];
$result = DataMapper::mapFromTemplate($template, $sources, skipNull: true, reindexWildcard: false);
```

See API: [mapFromTemplate](#mapfromtemplate)

- Template may be a JSON string or an array
- **Template Syntax:**
  - **Source paths:** `'{{ user.name }}'` - Fetches value from source
  - **Target aliases:** `'{{ @fieldName }}'` - Copies value from already resolved target field
  - **Static values:** `'admin'` - Used as literal string (no `{{ }}`)
- Unknown source aliases return `null` (skipped if `skipNull=true`)
- Wildcards allowed (e.g., `'{{ src.users.*.email }}'`)
- **NEW:** Supports template expressions with filters and defaults (see below)

**Template Expressions:**

Templates now support powerful expression syntax:

```php
$template = [
  'profile' => [
    'name' => '{{ user.firstName | ucfirst }}',           // Source with filter
    'email' => '{{ user.email | trim | lower }}',         // Multiple filters
    'age' => '{{ user.age ?? 18 }}',                      // With default
    'copyName' => '{{ @name }}',                          // Copy from target 'name'
    'role' => 'admin',                                    // Static string
  ],
];
```

**Example with target aliases:**

```php
$template = [
  'firstName' => '{{ user.firstName }}',      // From source
  'lastName' => '{{ user.lastName }}',        // From source
  'fullName' => '{{ @firstName }}',           // Copy 'firstName' from target
  'displayName' => '{{ @fullName }}',         // Copy 'fullName' from target
  'role' => 'user',                           // Static value
];

$sources = ['user' => ['firstName' => 'Alice', 'lastName' => 'Smith']];
$result = DataMapper::mapFromTemplate($template, $sources);

// Result:
// [
//   'firstName' => 'Alice',
//   'lastName' => 'Smith',
//   'fullName' => 'Alice',      // Copied from 'firstName'
//   'displayName' => 'Alice',   // Copied from 'fullName'
//   'role' => 'user',           // Static value
// ]
```

**📖 See [Template Expressions Documentation](template-expressions.md) for complete guide.**

5) **Pipeline API (NEW)** - Modern, fluent API with reusable filters:

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\SkipEmptyValues;

$result = DataMapper::pipe([
    new TrimStrings(),
    new LowercaseEmails(),
    new SkipEmptyValues(),
])->map($source, [], $mapping);
```

**📖 See [Pipeline API Documentation](data-mapper-pipeline.md) for detailed guide and examples.**

---

## Template Syntax

**Important:** Values in mappings must be wrapped in `{{ }}` to be treated as dynamic paths.

### Three Types of Values

```php
// 1. Source Reference: Fetches value from source
'name' => '{{ user.name }}'        // Gets $source['user']['name']
'email' => '{{ user.email }}'      // Gets $source['user']['email']
'count' => '{{ 0 }}'               // Gets $source[0]

// 2. Target Alias: Copies value from already resolved target field
'copyName' => '{{ @name }}'        // Copies value from $result['name']
'backup' => '{{ @email }}'         // Copies value from $result['email']

// 3. Static Value: Used as literal string value (no {{ }})
'role' => 'admin'                  // Sets 'admin' as static value
'status' => 'active'               // Sets 'active' as static value
'message' => 'Hello World'         // Sets 'Hello World' as static value
```

### Examples

#### Example 1: Mix Source, Target Alias, and Static Values

```php
$source = [
    'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
    'role' => 'user',
];

$result = DataMapper::map($source, [], [
    'profile' => [
        'name' => '{{ user.name }}',      // Source: 'Alice'
        'email' => '{{ user.email }}',    // Source: 'alice@example.com'
        'copyName' => '{{ @profile.name }}', // Target alias: 'Alice' (copied from profile.name)
        'role' => 'admin',                // Static: 'admin' (overrides source)
        'status' => 'active',             // Static: 'active'
    ],
]);

// Result:
// [
//   'profile' => [
//     'name' => 'Alice',
//     'email' => 'alice@example.com',
//     'copyName' => 'Alice',        // Copied from 'profile.name'
//     'role' => 'admin',
//     'status' => 'active',
//   ]
// ]
```

#### Example 2: Template Mapping with Target Aliases

```php
$template = [
    'firstName' => '{{ user.firstName }}',  // From source
    'lastName' => '{{ user.lastName }}',    // From source
    'fullName' => '{{ @firstName }}',       // Copy from target 'firstName'
    'displayName' => '{{ @fullName }}',     // Copy from target 'fullName'
    'role' => 'user',                       // Static value
];

$sources = ['user' => ['firstName' => 'Alice', 'lastName' => 'Smith']];
$result = DataMapper::mapFromTemplate($template, $sources);

// Result:
// [
//   'firstName' => 'Alice',
//   'lastName' => 'Smith',
//   'fullName' => 'Alice',      // Copied from 'firstName'
//   'displayName' => 'Alice',   // Copied from 'fullName'
//   'role' => 'user',           // Static value
// ]
```

### Special Cases

**Structured Mappings:** In structured mappings (with `mapping` key), the format is `source => target` and `{{ }}` is **not** required:

```php
DataMapper::map(null, [], [[
    'source' => $source,
    'target' => [],
    'mapping' => [
        'user.name' => 'profile.fullname',    // No {{ }} needed here
        'user.email' => 'profile.email',      // No {{ }} needed here
    ],
]]);
```

**AutoMapper:** AutoMapper automatically maps fields without requiring `{{ }}` syntax.

---

6) Inverse template-based mapping to named targets:

```php
$targets = [ 'user' => $userDto, 'addr' => [] ];
$template = [
  'profile' => [
    'fullname' => '{{ user.name }}',
    'email'    => '{{ user.email }}',
    'street'   => '{{ addr.street }}',
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

- `$context->mode()` returns string; `$context->modeEnum()` returns enum `App\Helpers\DataMapper\Mode` (`Simple`, `Structured`,
  `StructuredAssoc`, `StructuredPairs`)
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

Use the enum `App\\Enums\\DataMapperHook` to avoid typos in hook names. Arrays require string keys, so use `->value` when defining the hooks
array.

### Hook builder utility

Convenience methods help reduce boilerplate for common filters:

- onForSrc(name, 'user.*', callable)
- onForTgt(name, 'profile.*', callable)
- onForMode(name, 'simple'|'structured', callable)
- onForModeEnum(name, App\\Helpers\\DataMapper\\Mode::..., callable)
- onForPrefix(name, '<prefix>', callable) // matches either srcPath or tgtPath once

Example:

```php
$hooks = Hooks::make()
  ->onForMode(DataMapperHook::BeforeAll, 'simple', fn(App\Helpers\DataMapper\AllContext $ctx) => null)
  ->onForSrc(DataMapperHook::BeforePair, 'user.name', fn(App\Helpers\DataMapper\PairContext $ctx) => false)
  ->onForTgt(DataMapperHook::BeforeWrite, 'profile.', fn($v, App\Helpers\DataMapper\WriteContext $ctx) => '__skip__')
  ->toArray();
```

```php
use App\Enums\DataMapperHook;
use App\Helpers\DataMapper\Mode;

$hooks = Hooks::make()
  ->onForModeEnum(DataMapperHook::BeforeAll, Mode::Simple, fn(App\Helpers\DataMapper\AllContext $ctx) => null)
  ->toArray();
```

```php
// Matches either srcPath or tgtPath with a single predicate (no double-calls)
$hooks = Hooks::make()
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
  bool|MappingOptions $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false
): mixed
```

**Modern API (recommended):**

```php
use event4u\DataHelpers\DataMapper\MappingOptions;

// Using default options
$result = DataMapper::map($source, $target, $mapping, MappingOptions::default());

// Using factory methods
$result = DataMapper::map($source, $target, $mapping, MappingOptions::includeNull());
$result = DataMapper::map($source, $target, $mapping, MappingOptions::reindexed());

// Using fluent API
$result = DataMapper::map($source, $target, $mapping,
    MappingOptions::default()
        ->withSkipNull(false)
        ->withTrimValues(false)
        ->withHook(DataMapperHook::beforeAll, fn($ctx) => /* ... */)
);
```

**Legacy API (still supported):**

```php
// Old 8-parameter syntax still works for backward compatibility
$result = DataMapper::map($source, $target, $mapping, true, false, [], true, false);
```

**Parameters:**

- `$skipNull`: (bool|MappingOptions) Skip null values when reading source values. Can be a boolean (legacy) or a `MappingOptions` instance (recommended).
- `$reindexWildcard`: (bool) If true, wildcard expansions compact indices (0..n-1). If false, original numeric keys are preserved (e.g. `[0 => 'a', 2 => 'b']`). Ignored when using `MappingOptions`.
- `$hooks`: (array) Typed hook callbacks as documented above. Ignored when using `MappingOptions`.
- `$trimValues`: (bool, default `true`) Trims string values before replacement matching. Ignored when using `MappingOptions`.
- `$caseInsensitiveReplace`: (bool, default `false`) Enables case-insensitive matching of string replacement keys. Ignored when using `MappingOptions`.

**MappingOptions:**

The `MappingOptions` DTO provides a cleaner, more maintainable API:

```php
// Factory methods
MappingOptions::default()        // skipNull=true, reindexWildcard=false, trimValues=true
MappingOptions::includeNull()    // skipNull=false
MappingOptions::reindexed()      // reindexWildcard=true

// Fluent API
$options = MappingOptions::default()
    ->withSkipNull(false)
    ->withReindexWildcard(true)
    ->withHooks([...])
    ->withHook(DataMapperHook::beforeAll, fn($ctx) => /* ... */)
    ->withTrimValues(false)
    ->withCaseInsensitiveReplace(true);
```

**Notes:**

- Structured entries can still override `skipNull`/`reindexWildcard`; see [Replace](#replace) for declaring `replaces` per entry.
- When using `MappingOptions`, the individual parameters (`$reindexWildcard`, `$hooks`, `$trimValues`, `$caseInsensitiveReplace`) are ignored.

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
  bool|MappingOptions $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false
): array
```

**Modern API:**

```php
use event4u\DataHelpers\DataMapper\MappingOptions;

$results = DataMapper::mapMany($mappings, MappingOptions::default());
```

**Parameters:**

- Accepts an array of structured mapping entries
- Each entry can override `skipNull` and `reindexWildcard`
- Global `hooks`, `trimValues`, `caseInsensitiveReplace` apply to all entries (or use `MappingOptions`)
- Returns an array of updated targets

### autoMap

```php
autoMap(
  mixed $source,
  mixed $target,
  bool|MappingOptions $skipNull = true,
  bool $reindexWildcard = false,
  array $hooks = [],
  bool $trimValues = true,
  bool $caseInsensitiveReplace = false,
  bool $deep = false
): mixed
```

**Modern API:**

```php
use event4u\DataHelpers\DataMapper\MappingOptions;

$result = DataMapper::autoMap($source, $target, MappingOptions::default(), deep: true);
```

**Parameters:**

- Shallow mode (default): matches top-level field names between source and target
- Object targets: automatically maps matching properties (supports snake_case to camelCase conversion)
- Deep mode (`deep=true`): flattens nested structures to dot-paths and maps nested fields and wildcard lists (numeric indices become `*`)
- Respects `skipNull`, `reindexWildcard`, `hooks`, `trimValues`, `caseInsensitiveReplace` (or use `MappingOptions`)
- Source can be a JSON string or array/DTO/Model/Collection

### mapFromTemplate

```php
mapFromTemplate(array|string $template, array $sources, bool $skipNull = true, bool $reindexWildcard = false): array
```

- Builds a structure by resolving `alias.path` strings against named sources
- `skipNull`: remove keys that resolve to null (including filtering nulls inside wildcard arrays)
- `reindexWildcard`: when true, wildcard arrays are sorted by numeric key and reindexed sequentially

## Wildcards

- In simple/structured mappings, wildcard results from the source (e.g. `users.*.email`) are expanded into the target path's wildcard (e.g.
  `emails.*`).
- By default, if a matched value is null and `skipNull=true`, the index is skipped and (unless `reindexWildcard=true`) the numeric position
  is preserved in the target (e.g. index 1 missing).

## Examples


### Complex nested mapping with automatic relation detection

Map complex nested JSON/XML structures directly to Eloquent Models or Doctrine Entities with automatic relation detection:

```php
use event4u\DataHelpers\DataMapper;

// Source: Complex nested JSON from API
$jsonData = [
    'company' => [
        'name' => 'TechCorp Solutions',
        'registration_number' => 'REG-2024-001',
        'email' => 'info@techcorp.example',
        'phone' => '+1-555-0123',
        'founded_year' => 2015,
        'employee_count' => 250,
        'annual_revenue' => 15750000.50,
        'is_active' => true,
        'departments' => [
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'budget' => 5000000.00,
                'employee_count' => 120,
                'manager_name' => 'Alice Johnson',
            ],
            [
                'name' => 'Sales',
                'code' => 'SAL',
                'budget' => 3000000.00,
                'employee_count' => 80,
                'manager_name' => 'Bob Smith',
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'budget' => 1500000.00,
                'employee_count' => 50,
                'manager_name' => 'Carol Williams',
            ],
        ],
        'projects' => [
            [
                'name' => 'Cloud Migration',
                'code' => 'PROJ-001',
                'budget' => 2500000.00,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'active',
            ],
            [
                'name' => 'Mobile App Development',
                'code' => 'PROJ-002',
                'budget' => 1800000.00,
                'start_date' => '2024-03-01',
                'end_date' => '2024-09-30',
                'status' => 'active',
            ],
        ],
    ],
];

// Target: Eloquent Model or Doctrine Entity
$company = new Company();

// Mapping: Nested structure with wildcards
$mapping = [
    'name' => '{{ company.name }}',
    'registration_number' => '{{ company.registration_number }}',
    'email' => '{{ company.email }}',
    'phone' => '{{ company.phone }}',
    'founded_year' => '{{ company.founded_year }}',
    'employee_count' => '{{ company.employee_count }}',
    'annual_revenue' => '{{ company.annual_revenue }}',
    'is_active' => '{{ company.is_active }}',

    // Automatic relation mapping - DataMapper detects HasMany/OneToMany relations!
    // The wildcard '*' maps each array element to a separate related entity
    'departments' => [
        '*' => [
            'name' => '{{ company.departments.*.name }}',
            'code' => '{{ company.departments.*.code }}',
            'budget' => '{{ company.departments.*.budget }}',
            'employee_count' => '{{ company.departments.*.employee_count }}',
            'manager_name' => '{{ company.departments.*.manager_name }}',
        ],
    ],
    'projects' => [
        '*' => [
            'name' => '{{ company.projects.*.name }}',
            'code' => '{{ company.projects.*.code }}',
            'budget' => '{{ company.projects.*.budget }}',
            'start_date' => '{{ company.projects.*.start_date }}',
            'end_date' => '{{ company.projects.*.end_date }}',
            'status' => '{{ company.projects.*.status }}',
        ],
    ],
];

// Map in one call - relations are automatically created and linked!
$result = DataMapper::map($jsonData, $company, $mapping);

// Result: Fully populated Company with related Departments and Projects
echo $result->getName();                           // 'TechCorp Solutions'
echo $result->getFoundedYear();                    // 2015 (auto-casted from string to int)
echo $result->getAnnualRevenue();                  // 15750000.50 (auto-casted to float)
echo $result->getIsActive();                       // true (auto-casted to bool)

// Access relations (Eloquent Collection or Doctrine ArrayCollection)
echo $result->getDepartments()->count();           // 3
echo $result->getDepartments()[0]->getName();      // 'Engineering'
echo $result->getDepartments()[0]->getCode();      // 'ENG'
echo $result->getDepartments()[0]->getBudget();    // 5000000.00 (auto-casted to float)
echo $result->getDepartments()[0]->getEmployeeCount(); // 120 (auto-casted to int)

echo $result->getProjects()->count();              // 2
echo $result->getProjects()[0]->getName();         // 'Cloud Migration'
echo $result->getProjects()[0]->getCode();         // 'PROJ-001'
echo $result->getProjects()[0]->getBudget();       // 2500000.00 (auto-casted to float)
echo $result->getProjects()[0]->getStatus();       // 'active'

// Works with both Eloquent Models and Doctrine Entities!
// - Eloquent: Uses setRelation() for HasMany/BelongsTo/BelongsToMany
// - Doctrine: Uses Collection methods for OneToMany/ManyToOne/ManyToMany
```

**How the nesting resolves:**

1. **Top-level fields** (`name`, `email`, etc.) are mapped directly to the Company entity
2. **Nested arrays with wildcards** (`departments.*`, `projects.*`) are detected as relations:
   - DataMapper checks if `departments` is a relation method on the Company entity
   - For each array element, a new Department/Project entity is created
   - Each nested field (`name`, `code`, `budget`, etc.) is mapped to the related entity
   - The wildcard `*` in the source path (`company.departments.*.name`) matches each array index
   - The wildcard `*` in the nested mapping tells DataMapper to create multiple entities
3. **Type casting** happens automatically:
   - `founded_year: "2015"` (string) → `setFoundedYear(2015)` (int)
   - `budget: "5000000.00"` (string) → `setBudget(5000000.00)` (float)
   - `is_active: "true"` (string) → `setIsActive(true)` (bool)
   - `employee_count: "120"` (string) → `setEmployeeCount(120)` (int)
4. **Snake_case to camelCase** conversion:
   - `employee_count` → `setEmployeeCount()`
   - `annual_revenue` → `setAnnualRevenue()`
   - `manager_name` → `setManagerName()`
5. **Relations are automatically linked**:
   - Eloquent: `$company->setRelation('departments', $departmentsCollection)`
   - Doctrine: `$company->addDepartment($department)` for each department

**Key Features:**
- ✅ **Automatic Relation Detection** - No configuration needed, detects Eloquent/Doctrine relations
- ✅ **Type Casting** - Automatically casts values based on setter parameter types
- ✅ **Snake_case → camelCase** - Converts field names to match PHP naming conventions
- ✅ **Nested Wildcards** - Map arrays of objects with `*` notation
- ✅ **Framework Agnostic** - Works with Laravel, Symfony, or standalone PHP
- ✅ **Deep Nesting** - Supports multiple levels of nested relations


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
- **Use templates for complex transformations**: When mapping from multiple sources or building complex nested structures, templates are
  clearer than manual mappings.
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
    'customer_name' => '{{ user.name }}',
    'currency' => '{{ config.currency }}',
    'total_orders' => '{{ stats.orders }}',
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

**Solution**: Verify hook keys match enum values (e.g., `DataMapperHook::PreTransform->value`). Use `Hooks::make()` builder for type safety.

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

