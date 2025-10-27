---
title: Reverse Mapping
description: Bidirectional data transformation using a single mapping definition with Fluent API
---

Reverse mapping enables bidirectional data transformation using a single template definition with the Fluent API.

## Introduction

Reverse mapping provides bidirectional data transformation:

- **Forward mapping**: Transform data from source to target using `->map()`
- **Reverse mapping**: Transform data from target back to source using `->reverseMap()`

This is particularly useful when:
- Converting between Dtos and domain models
- Synchronizing data between different formats
- Implementing undo/redo functionality
- Building bidirectional API transformations

## Basic Concepts

### How Reverse Mapping Works

The Fluent API provides `->reverseMap()` method that reverses the template direction:

```php
use event4u\DataHelpers\DataMapper;

$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];

// Forward: user -> profile
$userData = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$result = DataMapper::source($userData)
    ->template($template)
    ->map()
    ->getTarget();
// Result: ['profile' => ['name' => 'John', 'email' => 'john@example.com']]

// Reverse: profile -> user (using the SAME template!)
$profileData = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$result = DataMapper::source($profileData)
    ->template($template)
    ->reverseMap()
    ->getTarget();
// Result: ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
```

### Template Reversal

Templates are reversed automatically when using `->reverseMap()`:

```php
$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];

// Forward: user -> profile
$userData = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$profile = DataMapper::source($userData)
    ->template($template)
    ->map()
    ->getTarget();
// Result: ['profile' => ['name' => 'John', 'email' => 'john@example.com']]

// Reverse: profile -> user
$profileData = ['profile' => ['name' => 'Jane', 'email' => 'jane@example.com']];
$user = DataMapper::source($profileData)
    ->template($template)
    ->reverseMap()
    ->getTarget();
// Result: ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']]
```

## API Reference

### reverseMap()

Execute reverse mapping using the configured template.

```php
$source = ['profile' => ['name' => 'John']];
$template = ['name' => '{{ profile.name }}'];
$result = DataMapper::source($source)
    ->template($template)
    ->reverseMap();
```

**Returns:** `DataMapperResult` with reversed mapping

**Methods on Result:**
- `getTarget()` - Get the reversed target data
- `getSource()` - Get the original source data
- `getTemplate()` - Get the template used

### Configuration Options

All standard DataMapper configuration options work with reverse mapping:

```php
$source = ['profile' => ['name' => 'John']];
$template = ['name' => '{{ profile.name }}'];
$result = DataMapper::source($source)
    ->template($template)
    ->skipNull(true)           // Skip null values
    ->reindexWildcard(false)   // Don't reindex arrays
    ->reverseMap();
```

## Use Cases

### Dto to Model Conversion

<!-- skip-test: Requires Dto/Model classes -->
```php
$template = [
    'id' => '{{ user.id }}',
    'name' => '{{ user.name }}',
    'email' => '{{ user.email }}',
];

// Forward: Model -> Dto
$userModel = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];
$userDto = DataMapper::source(['user' => $userModel])
    ->target(UserDto::class)
    ->template($template)
    ->map()
    ->getTarget();

// Reverse: Dto -> Model
$userModel = DataMapper::source(['user' => $userDto])
    ->target(User::class)
    ->template($template)
    ->reverseMap()
    ->getTarget();
```

### API Request/Response

```php
$template = [
    'user' => [
        'firstName' => '{{ request.first_name }}',
        'lastName' => '{{ request.last_name }}',
        'email' => '{{ request.email }}',
    ],
];

// Forward: API request -> Internal format
$apiRequest = ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com'];
$internal = DataMapper::source(['request' => $apiRequest])
    ->template($template)
    ->map()
    ->getTarget();

// Reverse: Internal format -> API response
$apiResponse = DataMapper::source($internal)
    ->template($template)
    ->reverseMap()
    ->getTarget();
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
$internalData = ['userId' => 123, 'userName' => 'john_doe'];
$externalData = DataMapper::source(['internal' => $internalData])
    ->template($template)
    ->map()
    ->getTarget();

// Sync back from external system
$internalData = DataMapper::source($externalData)
    ->template($template)
    ->reverseMap()
    ->getTarget();
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
$userData = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com', 'phone' => '123-456'];
$profile = DataMapper::source(['user' => $userData])
    ->template($template)
    ->map()
    ->getTarget();

// Reverse
$userData = DataMapper::source($profile)
    ->template($template)
    ->reverseMap()
    ->getTarget();
```

### With Wildcards

```php
$template = [
    'products' => [
        '*' => [
            'name' => '{{ items.*.title }}',
            'price' => '{{ items.*.cost }}',
        ],
    ],
];

// Forward
$itemsData = [
    ['title' => 'Product A', 'cost' => 10.99],
    ['title' => 'Product B', 'cost' => 20.99],
];
$products = DataMapper::source(['items' => $itemsData])
    ->template($template)
    ->map()
    ->getTarget();

// Reverse
$items = DataMapper::source($products)
    ->template($template)
    ->reverseMap()
    ->getTarget();
```

### With Pipeline Filters

<!-- skip-test: Import conflict with filter classes -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

$template = [
    'user' => [
        'name' => '{{ person.name }}',
        'email' => '{{ person.email }}',
    ],
];

// Forward: Applies filters
$data = ['name' => '  john  ', 'email' => 'john@example.com'];
$user = DataMapper::source(['person' => $data])
    ->template($template)
    ->pipeline([new TrimStrings(), new UppercaseStrings()])
    ->map()
    ->getTarget();

// Reverse: Filters are NOT reversed (data flows as-is)
$person = DataMapper::source($user)
    ->template($template)
    ->reverseMap()
    ->getTarget();
```

## Limitations

### Non-Reversible Transformations

Some transformations cannot be reversed due to information loss:

```php
// ❌ Cannot reverse - Information loss
$template = [
    'initials' => '{{ user.firstName }}{{ user.lastName }}',
];

// Forward: 'John' + 'Doe' -> 'JohnDoe'
// Reverse: 'JohnDoe' -> Cannot split back to 'John' and 'Doe'
```

### Pipeline Filters

Pipeline filters are NOT automatically reversed:

```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

$template = [
    'name' => '{{ user.name }}',
];

// Forward: 'john' -> 'JOHN' (with UppercaseStrings filter)
$result = DataMapper::source(['user' => ['name' => 'john']])
    ->template($template)
    ->pipeline([new UppercaseStrings()])
    ->map()
    ->getTarget();

// Reverse: 'JOHN' -> 'JOHN' (filter NOT reversed)
$reverse = DataMapper::source($result)
    ->template($template)
    ->reverseMap()
    ->getTarget();
```

## Best Practices

### 1. Use Simple 1:1 Mappings

```php
// ✅ Good - Reversible
$template = [
    'target' => [
        'name' => '{{ source.name }}',
        'email' => '{{ source.email }}',
    ],
];

// ❌ Bad - Not reversible (concatenation)
$template = [
    'target' => [
        'fullName' => '{{ source.firstName }} {{ source.lastName }}',
    ],
];
```

### 2. Test Both Directions

```php
$template = [
    'profile' => [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ],
];

$originalData = ['user' => ['name' => 'John', 'email' => 'john@example.com']];

// Test forward
$forward = DataMapper::source($originalData)
    ->template($template)
    ->map()
    ->getTarget();

// Test reverse
$reverse = DataMapper::source($forward)
    ->template($template)
    ->reverseMap()
    ->getTarget();

// Verify
assert($originalData === $reverse);
```

### 3. Document Non-Reversible Mappings

```php
// ⚠️ WARNING: This mapping is not reversible
// Forward only - use for read operations
$template = [
    'display' => [
        'fullName' => '{{ user.firstName }} {{ user.lastName }}',
    ],
];
```

## See Also

- [DataMapper](/main-classes/data-mapper/) - DataMapper guide
- [Template Expressions](/advanced/template-expressions/) - Template syntax
- [Pipelines](/advanced/pipelines/) - Pipeline processing

