# Reverse Mapping

The `ReverseDataMapper` class provides bidirectional mapping capabilities by reversing the direction of mappings and templates. This allows you to use the same mapping definition to transform data in both directions.

## Table of Contents

- [Overview](#overview)
- [Basic Concepts](#basic-concepts)
- [API Reference](#api-reference)
- [Examples](#examples)
- [Use Cases](#use-cases)
- [Comparison with DataMapper](#comparison-with-datamapper)

## Overview

Reverse mapping enables bidirectional data transformation using a single mapping definition:

- **Forward mapping**: Transform data from source to target using `DataMapper`
- **Reverse mapping**: Transform data from target back to source using `ReverseDataMapper`

This is particularly useful when:
- Converting between DTOs and domain models
- Synchronizing data between different formats
- Implementing undo/redo functionality
- Building bidirectional API transformations

## Basic Concepts

### How Reverse Mapping Works

The `ReverseDataMapper` internally reverses the mapping definition and delegates to `DataMapper`:

```php
// Original mapping: user -> profile
$mapping = [
    'profile.name' => '{{ user.name }}',
    'profile.email' => '{{ user.email }}',
];

// Forward: user -> profile
$user = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$profile = DataMapper::map($user, [], $mapping);
// Result: ['profile' => ['name' => 'John', 'email' => 'john@example.com']]

// Reverse: profile -> user
$profile = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$user = ReverseDataMapper::map($profile, [], $mapping);
// Result: ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
```

### Template Reversal

Templates are also reversed automatically:

```php
$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];

// Forward: sources -> template structure
$sources = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$result = DataMapper::mapFromTemplate($template, $sources);
// Result: ['profile' => ['name' => 'John', 'email' => 'john@example.com']]

// Reverse: template structure -> sources
$data = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$sources = ReverseDataMapper::mapFromTemplate($template, $data);
// Result: ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
```

## API Reference

### `ReverseDataMapper::map()`

Map values from source to target using reversed mappings.

```php
public static function map(
    mixed $source,
    mixed $target,
    array $mapping,
    bool $skipNull = true,
    bool $reindexWildcard = false,
    array $hooks = [],
    bool $trimValues = true,
    bool $caseInsensitiveReplace = false,
): mixed
```

**Parameters:**
- `$source`: The source data
- `$target`: The target data (array, object, model, DTO, etc.)
- `$mapping`: Mapping definition (will be reversed)
- `$skipNull`: Skip null values
- `$reindexWildcard`: Reindex wildcard results
- `$hooks`: Optional hooks
- `$trimValues`: Trim string values
- `$caseInsensitiveReplace`: Case insensitive replace

**Returns:** The updated target

### `ReverseDataMapper::mapFromTemplate()`

Map from a template with reversed paths.

```php
public static function mapFromTemplate(
    array $template,
    array $sources,
    bool $skipNull = true,
    bool $reindexWildcard = false,
    bool $trimValues = true,
): array
```

**Parameters:**
- `$template`: The template (will be reversed)
- `$sources`: The source data
- `$skipNull`: Skip null values
- `$reindexWildcard`: Reindex wildcard results
- `$trimValues`: Trim string values

**Returns:** The mapped result

### `ReverseDataMapper::mapToTargetsFromTemplate()`

Map data to targets using a template (NOT reversed).

```php
public static function mapToTargetsFromTemplate(
    array $data,
    array $template,
    array $targets,
    bool $skipNull = true,
    bool $reindexWildcard = false,
): array
```

**Parameters:**
- `$data`: Data with template structure
- `$template`: The template (NOT reversed - used as-is)
- `$targets`: Map of target name => target data
- `$skipNull`: Skip null values
- `$reindexWildcard`: Reindex wildcard results

**Returns:** Updated targets

**Note:** This method does NOT reverse the template because `mapToTargetsFromTemplate` already does what we need for reverse mapping: it reads from the template structure and writes to targets using template paths.

### `ReverseDataMapper::autoMap()`

Auto-map by matching field names (symmetric operation).

```php
public static function autoMap(
    mixed $source,
    mixed $target,
    bool $skipNull = true,
    bool $reindexWildcard = false,
    array $hooks = [],
    bool $trimValues = true,
    bool $caseInsensitiveReplace = false,
    bool $deep = false,
): mixed
```

**Note:** Auto-mapping is symmetric, so `ReverseDataMapper::autoMap()` simply delegates to `DataMapper::autoMap()`.

### `ReverseDataMapper::pipe()`

Create a pipeline for reverse mapping with filters.

```php
public static function pipe(array $filters): ReverseDataMapperPipeline
```

**Parameters:**
- `$filters`: Array of filter instances or class names

**Returns:** A `ReverseDataMapperPipeline` instance

## Examples

### Example 1: Simple Reverse Mapping

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\ReverseDataMapper;

$mapping = [
    'profile.name' => '{{ user.name }}',
    'profile.email' => '{{ user.email }}',
];

// Forward: user -> profile
$user = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$profile = DataMapper::map($user, [], $mapping);

// Reverse: profile -> user
$profile = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$user = ReverseDataMapper::map($profile, [], $mapping);
```

### Example 2: Nested Reverse Mapping

```php
$nestedMapping = [
    'dto' => [
        'fullName' => '{{ person.name }}',
        'contact' => [
            'email' => '{{ person.email }}',
            'phone' => '{{ person.phone }}',
        ],
    ],
];

// Forward: person -> dto
$person = [
    'person' => [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'phone' => '+1234567890',
    ],
];
$dto = DataMapper::map($person, [], $nestedMapping);

// Reverse: dto -> person
$dto = [
    'dto' => [
        'fullName' => 'Bob',
        'contact' => [
            'email' => 'bob@example.com',
            'phone' => '+0987654321',
        ],
    ],
];
$person = ReverseDataMapper::map($dto, [], $nestedMapping);
```

### Example 3: Template-Based Reverse Mapping

```php
$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];

// Forward: sources -> template structure
$sources = ['user' => ['name' => 'Charlie', 'email' => 'charlie@example.com']];
$result = DataMapper::mapFromTemplate($template, $sources);

// Reverse: template structure -> sources
$data = ['profile' => ['name' => 'David', 'email' => 'david@example.com']];
$sources = ReverseDataMapper::mapFromTemplate($template, $data);
```

### Example 4: Wildcard Reverse Mapping

```php
$wildcardMapping = [
    'names.*' => '{{ users.*.name }}',
];

// Forward: users -> names
$users = ['users' => [
    ['name' => 'Frank'],
    ['name' => 'Grace'],
]];
$names = DataMapper::map($users, [], $wildcardMapping);

// Reverse: names -> users
$names = ['names' => ['Alice', 'Bob']];
$users = ReverseDataMapper::map($names, [], $wildcardMapping);
```

### Example 5: Pipeline with Filters

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

$mapping = [
    'output.name' => '{{ input.name }}',
];

$pipeline = ReverseDataMapper::pipe([new TrimStrings(), new UppercaseStrings()]);

$input = ['input' => ['name' => '  john  ']];
$result = $pipeline->map($input, [], $mapping);
// Result: ['output' => ['name' => 'JOHN']]
```

## Use Cases

### 1. DTO ↔ Domain Model Conversion

```php
// Define mapping once
$mapping = [
    'dto.userId' => '{{ user.id }}',
    'dto.userName' => '{{ user.name }}',
];

// Convert domain model to DTO
$user = ['user' => ['id' => 1, 'name' => 'John']];
$dto = DataMapper::map($user, [], $mapping);

// Convert DTO back to domain model
$dto = ['dto' => ['userId' => 2, 'userName' => 'Jane']];
$user = ReverseDataMapper::map($dto, [], $mapping);
```

### 2. API Request/Response Transformation

```php
$template = [
    'api' => [
        'user_id' => '{{ internal.userId }}',
        'full_name' => '{{ internal.name }}',
    ],
];

// Transform internal format to API format
$internal = ['internal' => ['userId' => 1, 'name' => 'John']];
$apiRequest = DataMapper::mapFromTemplate($template, $internal);

// Transform API response back to internal format
$apiResponse = ['api' => ['user_id' => 2, 'full_name' => 'Jane']];
$internal = ReverseDataMapper::mapFromTemplate($template, $apiResponse);
```

### 3. Form Data Binding

```php
$formMapping = [
    'form.name' => '{{ model.name }}',
    'form.email' => '{{ model.email }}',
];

// Populate form from model
$model = ['model' => ['name' => 'John', 'email' => 'john@example.com']];
$formData = DataMapper::map($model, [], $formMapping);

// Update model from form submission
$formData = ['form' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$model = ReverseDataMapper::map($formData, [], $formMapping);
```

## Comparison with DataMapper

| Feature | DataMapper | ReverseDataMapper |
|---------|-----------|-------------------|
| Direction | Source → Target | Target → Source |
| Mapping | Uses mapping as-is | Reverses mapping |
| Template | Uses template as-is | Reverses template (except `mapToTargetsFromTemplate`) |
| Auto-map | Symmetric | Delegates to DataMapper |
| Pipeline | Forward filters | Reverse filters |
| Use case | Initial transformation | Reverse transformation |

## See Also

- [DataMapper Documentation](data-mapper.md)
- [Template Mapping](template-mapping.md)
- [Pipeline Filters](pipeline-filters.md)
- [Examples](../examples/10-reverse-mapping.php)

