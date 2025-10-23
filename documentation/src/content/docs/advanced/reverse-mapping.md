---
title: Reverse Mapping
description: Bidirectional data transformation using a single mapping definition
---

The `ReverseDataMapper` class provides bidirectional mapping capabilities by reversing the direction of mappings and templates.

## Introduction

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

### ReverseDataMapper::map()

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
- `$caseInsensitiveReplace`: Case-insensitive template replacement

### ReverseDataMapper::mapFromTemplate()

Map from template structure back to sources.

```php
public static function mapFromTemplate(
    array $template,
    mixed $data,
    bool $skipNull = true,
    bool $reindexWildcard = false,
    array $hooks = [],
    bool $trimValues = true,
): array
```

## Use Cases

### DTO to Model Conversion

```php
// Forward: Model -> DTO
$userDTO = DataMapper::map($user, [], [
    'id' => 'id',
    'name' => 'name',
    'email' => 'email',
], UserDTO::class);

// Reverse: DTO -> Model
$user = ReverseDataMapper::map($userDTO, new User(), [
    'id' => 'id',
    'name' => 'name',
    'email' => 'email',
]);
```

### API Request/Response

```php
$mapping = [
    'user.firstName' => '{{ request.first_name }}',
    'user.lastName' => '{{ request.last_name }}',
    'user.email' => '{{ request.email }}',
];

// Forward: API request -> Internal format
$internal = DataMapper::map($apiRequest, [], $mapping);

// Reverse: Internal format -> API response
$apiResponse = ReverseDataMapper::map($internal, [], $mapping);
```

### Data Synchronization

```php
$template = [
    'external' => [
        'user_id' => '{{ internal.userId }}',
        'user_name' => '{{ internal.userName }}',
    ],
];

// Sync to external system
$externalData = DataMapper::mapFromTemplate($template, $internalData);

// Sync back from external system
$internalData = ReverseDataMapper::mapFromTemplate($template, $externalData);
```

## Advanced Examples

### With Nested Data

```php
$template = [
    'profile' => [
        'personal' => [
            'name' => '{{ user.name }}',
            'age' => '{{ user.age }}',
        ],
        'contact' => [
            'email' => '{{ user.email }}',
            'phone' => '{{ user.phone }}',
        ],
    ],
];

// Forward
$profile = DataMapper::mapFromTemplate($template, ['user' => $userData]);

// Reverse
$userData = ReverseDataMapper::mapFromTemplate($template, $profile);
```

### With Wildcards

```php
$mapping = [
    'products.*.name' => '{{ items.*.title }}',
    'products.*.price' => '{{ items.*.cost }}',
];

// Forward
$products = DataMapper::map($items, [], $mapping);

// Reverse
$items = ReverseDataMapper::map($products, [], $mapping);
```

### With Filters

```php
$template = [
    'user' => [
        'name' => '{{ person.name | upper }}',
        'email' => '{{ person.email | lower }}',
    ],
];

// Forward: Applies filters
$user = DataMapper::mapFromTemplate($template, ['person' => $data]);

// Reverse: Reverses filters automatically
$person = ReverseDataMapper::mapFromTemplate($template, $user);
```

## Limitations

### Non-Reversible Transformations

Some transformations cannot be reversed:

```php
// ❌ Cannot reverse - Information loss
$template = [
    'initials' => '{{ user.firstName | first }}{{ user.lastName | first }}',
];

// Forward: 'John Doe' -> 'JD'
// Reverse: 'JD' -> Cannot reconstruct 'John Doe'
```

### Complex Filters

Complex filters may not reverse correctly:

```php
// ⚠️ May not reverse correctly
$template = [
    'slug' => '{{ title | slugify }}',
];

// Forward: 'Hello World' -> 'hello-world'
// Reverse: 'hello-world' -> 'hello-world' (not 'Hello World')
```

## Best Practices

### 1. Use Simple Mappings

```php
// ✅ Good - Reversible
$mapping = [
    'target.name' => '{{ source.name }}',
    'target.email' => '{{ source.email }}',
];

// ❌ Bad - Not reversible
$mapping = [
    'target.fullName' => '{{ source.firstName }} {{ source.lastName }}',
];
```

### 2. Test Both Directions

```php
// Test forward
$forward = DataMapper::map($source, [], $mapping);

// Test reverse
$reverse = ReverseDataMapper::map($forward, [], $mapping);

// Verify
assert($source === $reverse);
```

### 3. Document Non-Reversible Mappings

```php
// ⚠️ WARNING: This mapping is not reversible
// Forward only - use for read operations
$mapping = [
    'display' => '{{ user.firstName }} {{ user.lastName }}',
];
```

## See Also

- [DataMapper](/main-classes/data-mapper/) - DataMapper guide
- [Template Expressions](/advanced/template-expressions/) - Template syntax
- [Pipelines](/advanced/pipelines/) - Pipeline processing

