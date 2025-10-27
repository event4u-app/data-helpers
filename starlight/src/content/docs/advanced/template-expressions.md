---
title: Template Expressions
description: Powerful template expression engine for declarative data transformations
---

Powerful template expression engine for declarative data transformations - inspired by Twig, but designed specifically for data mapping.

## Introduction

The Template Expression Engine provides a powerful expression syntax that works across **all mapping methods**:

- **Transform values** using filter syntax (e.g., `| lower`, `| trim`)
- **Provide defaults** for null/missing values (e.g., `?? 'Unknown'`)
- **Chain multiple filters** (e.g., `| trim | lower | ucfirst`)
- **Reference source fields** (e.g., `{{ user.name }}`)
- **Reference target fields** using aliases (e.g., `{{ @fieldName }}`)
- **Use static values** (e.g., `'admin'` without `{{ }}`)
- **Wildcard support** - Apply filters to array elements (e.g., `{{ users.*.name | upper }}`)

**Key Features:**

- 🎯 **Declarative syntax** - Define transformations in the template
- 🔄 **Unified across all methods** - Same syntax in `map()`, `mapFromFile()`, and `mapFromTemplate()`
- 🔄 **Composable filters** - Chain multiple transformations
- 📦 **30+ built-in filters** - Common transformations out of the box
- 🔧 **Extensible** - Register custom filters
- ⚡ **Fast** - Optimized expression parsing and evaluation

## Quick Start

```php
use event4u\DataHelpers\DataMapper;

$sources = [
    'user' => [
        'firstName' => 'alice',
        'email' => '  ALICE@EXAMPLE.COM  ',
        'age' => null,
    ],
];

$template = [
    'profile' => [
        // Simple expression
        'name' => '{{ user.firstName | ucfirst }}',

        // Expression with default value
        'age' => '{{ user.age ?? 18 }}',

        // Multiple filters
        'email' => '{{ user.email | trim | lower }}',
    ],
];

$result = DataMapper::source($sources)
    ->template($template)
    ->map()
    ->getTarget();

// Result:
// [
//     'profile' => [
//         'name' => 'Alice',
//         'age' => 18,
//         'email' => 'alice@example.com',
//     ]
// ]
```

## Expression Syntax

### Simple Variables

Access source data using dot-notation paths wrapped in `{{ }}`:

```php
$template = [
    'name' => '{{ user.name }}',
    'email' => '{{ user.contact.email }}',
];
```

### Default Values

Provide fallback values for null/missing data using `??`:

```php
$template = [
    'name' => '{{ user.name ?? "Unknown" }}',
    'age' => '{{ user.age ?? 18 }}',
    'role' => '{{ user.role ?? "guest" }}',
];
```

### Filters

Transform values using the pipe `|` operator:

```php
$template = [
    'name' => '{{ user.name | upper }}',
    'email' => '{{ user.email | lower }}',
    'title' => '{{ post.title | trim }}',
];
```

### Chaining Filters

Chain multiple filters together:

```php
$template = [
    'name' => '{{ user.name | trim | lower | ucfirst }}',
    'slug' => '{{ post.title | trim | lower | replace:" ":"-" }}',
];
```

### Alias References

Reference target fields using `@` prefix:

```php
$template = [
    'firstName' => '{{ user.firstName }}',
    'lastName' => '{{ user.lastName }}',
    'fullName' => '{{ @firstName }} {{ @lastName }}',
];
```

## Built-in Filters

### String Filters

```php
// upper - Convert to uppercase
'{{ name | upper }}' // 'john' -> 'JOHN'

// lower - Convert to lowercase
'{{ name | lower }}' // 'JOHN' -> 'john'

// ucfirst - Uppercase first character
'{{ name | ucfirst }}' // 'john' -> 'John'

// trim - Remove whitespace
'{{ name | trim }}' // '  john  ' -> 'john'

// replace - Replace text
'{{ name | replace:"a":"b" }}' // 'apple' -> 'bpple'
```

### Array Filters

```php
// first - Get first element
'{{ items | first }}' // [1, 2, 3] -> 1

// last - Get last element
'{{ items | last }}' // [1, 2, 3] -> 3

// count - Count elements
'{{ items | count }}' // [1, 2, 3] -> 3

// join - Join array elements
'{{ items | join:", " }}' // [1, 2, 3] -> '1, 2, 3'
```

### Type Filters

```php
// int - Convert to integer
'{{ value | int }}' // '42' -> 42

// float - Convert to float
'{{ value | float }}' // '3.14' -> 3.14

// bool - Convert to boolean
'{{ value | bool }}' // '1' -> true

// string - Convert to string
'{{ value | string }}' // 42 -> '42'
```

### Date Filters

```php
// date - Format date
'{{ created | date:"Y-m-d" }}' // DateTime -> '2024-01-15'

// timestamp - Convert to timestamp
'{{ created | timestamp }}' // DateTime -> 1705276800
```

### Data Cleaning Filters

```php
// empty_to_null - Convert empty values to null
'{{ bio | empty_to_null }}' // '' -> null, [] -> null

// empty_to_null with zero conversion
'{{ count | empty_to_null:"zero" }}' // 0 -> null

// empty_to_null with string zero conversion
'{{ value | empty_to_null:"string_zero" }}' // '0' -> null

// empty_to_null with both zero conversions
'{{ amount | empty_to_null:"zero,string_zero" }}' // 0 -> null, '0' -> null

// default - Provide default value
'{{ name | default:"Unknown" }}' // null -> 'Unknown'
```

**ConvertEmptyToNull Options:**
- No options: Converts `""`, `[]`, and `null` to `null`
- `"zero"`: Also converts integer `0` to `null`
- `"string_zero"`: Also converts string `"0"` to `null`
- `"zero,string_zero"`: Converts both zero types to `null`

**Note:** Boolean `false` is **never** converted to `null`.

**See also:** [ConvertEmptyToNull Attribute](/data-helpers/simple-dto/convert-empty-to-null/) for SimpleDto usage.

## Custom Filters

### Register Custom Filter

<!-- skip-test: Requires custom FilterInterface implementation -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;

// Custom filters must implement FilterInterface
// See documentation for creating custom filters
FilterRegistry::register(SlugifyFilter::class);

$template = [
    'slug' => '{{ title | slugify }}',
];
```

### Filter with Parameters

<!-- skip-test: Requires custom FilterInterface implementation -->
```php
// Custom filters must implement FilterInterface
FilterRegistry::register(TruncateFilter::class);

$template = [
    'excerpt' => '{{ content | truncate:100 }}',
];
```

## Wildcard Support

Apply filters to array elements:

```php
$sources = [
    'users' => [
        ['name' => 'john'],
        ['name' => 'jane'],
    ],
];

$template = [
    'names' => '{{ users.*.name | upper }}',
];

// Result: ['names' => ['JOHN', 'JANE']]
```

## WHERE and ORDER BY Clauses

### WHERE Clauses

Filter array elements:

```php
$template = [
    'result' => [
        'WHERE' => [
            '{{ items.*.price }}' => ['>', 100],
        ],
        '*' => [
            'name' => '{{ items.*.name }}',
            'price' => '{{ items.*.price }}',
        ],
    ],
];
```

### ORDER BY Clauses

Sort array elements:

```php
$template = [
    'result' => [
        'ORDER BY' => [
            '{{ items.*.price }}' => 'DESC',
        ],
        '*' => [
            'name' => '{{ items.*.name }}',
            'price' => '{{ items.*.price }}',
        ],
    ],
];
```

## Advanced Examples

### Complex Transformation

```php
$template = [
    'user' => [
        'name' => '{{ person.firstName | trim | ucfirst }} {{ person.lastName | trim | ucfirst }}',
        'email' => '{{ person.email | trim | lower }}',
        'role' => '{{ person.role ?? "guest" | upper }}',
        'created' => '{{ person.createdAt | date:"Y-m-d H:i:s" }}',
    ],
];
```

### Nested Data

```php
$template = [
    'order' => [
        'customer' => [
            'name' => '{{ order.customer.name | ucfirst }}',
            'email' => '{{ order.customer.email | lower }}',
        ],
        'items' => [
            '*' => [
                'name' => '{{ order.items.*.name | trim }}',
                'price' => '{{ order.items.*.price | float }}',
            ],
        ],
    ],
];
```

### With Aliases

```php
$template = [
    'firstName' => '{{ user.firstName | ucfirst }}',
    'lastName' => '{{ user.lastName | ucfirst }}',
    'fullName' => '{{ @firstName }} {{ @lastName }}',
    'greeting' => 'Hello, {{ @fullName }}!',
];
```

## Best Practices

### 1. Use Filters for Transformations

```php
// ✅ Good
'{{ name | trim | ucfirst }}'

// ❌ Bad - Use callback instead
'{{ name }}' // Then transform in PHP
```

### 2. Provide Defaults

```php
// ✅ Good
'{{ user.role ?? "guest" }}'

// ❌ Bad
'{{ user.role }}' // May be null
```

### 3. Chain Filters Logically

```php
// ✅ Good - Logical order
'{{ name | trim | lower | ucfirst }}'

// ❌ Bad - Illogical order
'{{ name | ucfirst | lower | trim }}'
```

## See Also

- [DataMapper](/main-classes/data-mapper/) - DataMapper guide
- [Callback Filters](/advanced/callback-filters/) - Custom callbacks
- [Query Builder](/advanced/query-builder/) - Query builder

