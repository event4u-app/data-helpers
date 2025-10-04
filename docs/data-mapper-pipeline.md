# DataMapper Pipeline API

🚀 **Modern, fluent API** for composing reusable data transformers - inspired by Laravel's pipeline pattern.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Built-in Transformers](#built-in-transformers)
- [Creating Custom Transformers](#creating-custom-transformers)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Examples](#examples)

## Overview

The Pipeline API provides a clean, reusable way to apply data transformations during mapping operations. Instead of writing inline hooks for common transformations, you can compose pipelines from reusable transformer classes.

**Benefits:**

- ✅ **Reusable** - Define transformers once, use them in multiple mappings
- ✅ **Composable** - Chain multiple transformers together
- ✅ **Testable** - Each transformer is a separate, testable class
- ✅ **Type-safe** - Full PHPStan Level 9 compliance
- ✅ **Compatible** - Works alongside the classic hooks API

## Quick Start

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;

$source = [
    'user' => [
        'name' => '  Alice  ',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'phone' => '',
    ],
];

$mapping = [
    'user.name' => 'profile.name',
    'user.email' => 'profile.email',
    'user.phone' => 'profile.phone',
];

// Apply transformation pipeline
$result = DataMapper::pipe([
    TrimStrings::class,
    LowercaseEmails::class,
    SkipEmptyValues::class,
])->map($source, [], $mapping);

// Result:
// [
//     'profile' => [
//         'name' => 'Alice',
//         'email' => 'alice@example.com',
//         // phone is skipped (empty)
//     ]
// ]
```

## Built-in Transformers

### TrimStrings

Trims whitespace from all string values.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;

$result = DataMapper::pipe([TrimStrings::class])
    ->map($source, [], $mapping);
```

**Hook:** `preTransform`

**Example:**
- Input: `'  Alice  '`
- Output: `'Alice'`

### LowercaseEmails

Converts email addresses to lowercase. Automatically detects fields containing 'email' in the path.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;

$result = DataMapper::pipe([LowercaseEmails::class])
    ->map($source, [], $mapping);
```

**Hook:** `preTransform`

**Example:**
- Input: `'ALICE@EXAMPLE.COM'` (path contains 'email')
- Output: `'alice@example.com'`

### SkipEmptyValues

Skips empty strings and empty arrays from being written to the target.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\SkipEmptyValues;

$result = DataMapper::pipe([SkipEmptyValues::class])
    ->map($source, [], $mapping);
```

**Hook:** `beforeWrite`

**Example:**
- Input: `''` or `[]`
- Output: Value is not written to target

### UppercaseStrings

Converts all string values to uppercase.

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\UppercaseStrings;

$result = DataMapper::pipe([UppercaseStrings::class])
    ->map($source, [], $mapping);
```

**Hook:** `preTransform`

**Example:**
- Input: `'alice'`
- Output: `'ALICE'`

### ConvertToNull

Converts specific values to null (e.g., 'N/A', 'null', empty strings).

```php
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\ConvertToNull;

// Use default values ('', 'N/A', 'null', 'NULL')
$result = DataMapper::pipe([ConvertToNull::class])
    ->map($source, [], $mapping);

// Or specify custom values
$result = DataMapper::pipe([
    new ConvertToNull(['N/A', 'n/a', 'null', 'NULL', '-']),
])->map($source, [], $mapping);
```

**Hook:** `preTransform`

**Example:**
- Input: `'N/A'`
- Output: `null`

## Creating Custom Transformers

Implement the `TransformerInterface` to create your own transformers:

```php
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
use event4u\DataHelpers\DataMapper\Context\HookContext;

class ValidateEmail implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: $value");
        }
        return $value;
    }

    public function getHook(): string
    {
        return 'preTransform'; // Hook to attach to
    }

    public function getFilter(): ?string
    {
        return null; // No filtering (apply to all values)
    }
}
```

### Available Hooks

- `beforeAll` - Before entire mapping starts
- `afterAll` - After entire mapping completes
- `beforeEntry` - Before each structured entry
- `afterEntry` - After each structured entry
- `beforePair` - Before each source→target pair
- `afterPair` - After each source→target pair
- `preTransform` - Before value transformation
- `postTransform` - After value transformation
- `beforeWrite` - Before writing to target
- `afterWrite` - After writing to target

### Filtered Transformers

Use `getFilter()` to apply transformers conditionally:

```php
class LowercaseEmailsFiltered implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        // Only apply to paths containing 'email'
        return 'src:*email*';
    }
}
```

**Filter formats:**
- `src:user.email` - Only for source path `user.email`
- `tgt:profile.*` - Only for target paths starting with `profile.`
- `mode:simple` - Only for simple mapping mode

## Advanced Usage

### Reuse Pipelines

```php
// Define once
$cleanupPipeline = DataMapper::pipe([
    TrimStrings::class,
    ConvertToNull::class,
    SkipEmptyValues::class,
]);

// Use multiple times
$users = $cleanupPipeline->map($userSource, [], $userMapping);
$products = $cleanupPipeline->map($productSource, [], $productMapping);
$orders = $cleanupPipeline->map($orderSource, [], $orderMapping);
```

### Combine with Additional Hooks

```php
$result = DataMapper::pipe([
    TrimStrings::class,
    LowercaseEmails::class,
])
->withHooks([
    'afterAll' => fn($ctx) => logger()->info('Mapping completed'),
    'beforeWrite' => [
        'tgt:sensitive.*' => fn($v, $ctx) => encrypt($v),
    ],
])
->map($source, [], $mapping);
```

### Mix Transformer Instances and Classes

```php
$result = DataMapper::pipe([
    TrimStrings::class,                              // Class name
    new ConvertToNull(['N/A', 'null', '-']),        // Instance with custom config
    LowercaseEmails::class,                          // Class name
])->map($source, [], $mapping);
```

### Chain Multiple Transformers

Transformers are executed in order:

```php
$result = DataMapper::pipe([
    TrimStrings::class,        // 1. Trim whitespace
    ConvertToNull::class,      // 2. Convert 'N/A' to null
    SkipEmptyValues::class,    // 3. Skip empty values
])->map($source, [], $mapping);
```

## API Reference

### DataMapper::pipe()

```php
public static function pipe(array $transformers): DataMapperPipeline
```

Creates a new pipeline with the given transformers.

**Parameters:**
- `$transformers` - Array of `TransformerInterface` instances or class names

**Returns:** `DataMapperPipeline` instance

### DataMapperPipeline::through()

```php
public function through(TransformerInterface|string $transformer): self
```

Adds a transformer to the pipeline.

**Parameters:**
- `$transformer` - `TransformerInterface` instance or class name

**Returns:** `$this` for method chaining

### DataMapperPipeline::withHooks()

```php
public function withHooks(array $hooks): self
```

Adds additional hooks to the pipeline.

**Parameters:**
- `$hooks` - Array of hooks (same format as `DataMapper::map()`)

**Returns:** `$this` for method chaining

### DataMapperPipeline::map()

```php
public function map(
    mixed $source,
    mixed $target,
    array $mapping,
    bool $skipNull = true,
    bool $reindexWildcard = false,
    bool $trimValues = true,
    bool $caseInsensitiveReplace = false,
): mixed
```

Executes the mapping with the configured pipeline.

**Parameters:** Same as `DataMapper::map()` (except `hooks` which is built from transformers)

**Returns:** The mapped target

### TransformerInterface

```php
interface TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed;
    public function getHook(): string;
    public function getFilter(): ?string;
}
```

## Examples

### Example 1: Clean User Data

```php
$source = [
    'users' => [
        ['name' => '  Alice  ', 'email' => '  ALICE@EXAMPLE.COM  ', 'status' => 'N/A'],
        ['name' => '  Bob  ', 'email' => '  BOB@EXAMPLE.COM  ', 'status' => 'active'],
    ],
];

$mapping = [
    'users.*.name' => 'users.*.name',
    'users.*.email' => 'users.*.email',
    'users.*.status' => 'users.*.status',
];

$result = DataMapper::pipe([
    TrimStrings::class,
    ConvertToNull::class,
    LowercaseEmails::class,
    SkipEmptyValues::class,
])->map($source, [], $mapping);

// Result:
// [
//     'users' => [
//         ['name' => 'Alice', 'email' => 'alice@example.com'],
//         ['name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active'],
//     ]
// ]
```

### Example 2: Custom Validation Transformer

```php
class ValidateAge implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (is_int($value) && ($value < 0 || $value > 150)) {
            throw new InvalidArgumentException("Invalid age: $value");
        }
        return $value;
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return 'src:*.age'; // Only apply to 'age' fields
    }
}

$result = DataMapper::pipe([
    ValidateAge::class,
])->map($source, [], $mapping);
```

### Example 3: Reusable API Response Cleaner

```php
class ApiResponseCleaner
{
    private DataMapperPipeline $pipeline;

    public function __construct()
    {
        $this->pipeline = DataMapper::pipe([
            TrimStrings::class,
            ConvertToNull::class,
            LowercaseEmails::class,
            SkipEmptyValues::class,
        ]);
    }

    public function clean(array $response, array $mapping): array
    {
        return $this->pipeline->map($response, [], $mapping);
    }
}

// Usage
$cleaner = new ApiResponseCleaner();
$cleanedUsers = $cleaner->clean($userResponse, $userMapping);
$cleanedProducts = $cleaner->clean($productResponse, $productMapping);
```

## Compatibility with Classic API

The Pipeline API is **fully compatible** with the classic `DataMapper::map()` API:

```php
// Classic API (still works!)
$result = DataMapper::map($source, [], $mapping, hooks: [
    'preTransform' => fn($v) => is_string($v) ? trim($v) : $v,
    'beforeWrite' => fn($v) => '' === $v ? '__skip__' : $v,
]);

// Pipeline API (new, modern)
$result = DataMapper::pipe([
    TrimStrings::class,
    SkipEmptyValues::class,
])->map($source, [], $mapping);

// Both produce the same result!
```

**When to use which:**

- **Classic API**: One-off transformations, complex custom logic, existing code
- **Pipeline API**: Reusable transformations, standard operations, new code

---

**See also:**
- [DataMapper Documentation](data-mapper.md)
- [Hooks Documentation](data-mapper.md#hooks)
- [Example: 05-data-mapper-pipeline.php](../examples/05-data-mapper-pipeline.php)
