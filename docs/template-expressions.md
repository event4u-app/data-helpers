# Template Expressions

🚀 **Powerful template expression engine** for declarative data transformations - inspired by Twig, but designed specifically for data mapping.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Expression Syntax](#expression-syntax)
  - [Simple Variables](#simple-variables)
  - [Default Values](#default-values)
  - [Filters](#filters)
  - [Alias References](#alias-references)
- [Built-in Filters](#built-in-filters)
- [Custom Filters](#custom-filters)
- [Combining with Classic References](#combining-with-classic-references)
- [Advanced Examples](#advanced-examples)
- [API Reference](#api-reference)

## Overview

The Template Expression Engine extends `mapFromTemplate()` with a powerful expression syntax that allows you to:

- ✅ **Transform values** using filters (e.g., `| lower`, `| trim`)
- ✅ **Provide defaults** for null/missing values (e.g., `?? 'Unknown'`)
- ✅ **Chain multiple filters** (e.g., `| trim | lower | ucfirst`)
- ✅ **Reference source fields** (e.g., `{{ user.name }}`)
- ✅ **Reference target fields** using aliases (e.g., `{{ @fieldName }}`)
- ✅ **Use static values** (e.g., `'admin'` without `{{ }}`)

**Key Features:**

- 🎯 **Declarative syntax** - Define transformations in the template
- 🔄 **Composable filters** - Chain multiple transformations
- 📦 **15+ built-in filters** - Common transformations out of the box
- 🔧 **Extensible** - Register custom filters
- ⚡ **Fast** - Optimized expression parsing and evaluation
- 🔒 **Type-safe** - Full PHPStan Level 9 compliance

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

$result = DataMapper::mapFromTemplate($template, $sources);

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
    'email' => '{{ user.email }}',
    'city' => '{{ address.city }}',
];
```

**Equivalent to classic reference:**
```php
$template = [
    'name' => 'user.name',
    'email' => 'user.email',
    'city' => 'address.city',
];
```

### Default Values

Provide fallback values for null or missing data using `??`:

```php
$template = [
    'name' => '{{ user.name ?? "Unknown" }}',
    'age' => '{{ user.age ?? 18 }}',
    'active' => '{{ user.active ?? true }}',
    'data' => '{{ user.data ?? null }}',
];
```

**Supported default types:**
- Strings: `'text'` or `"text"`
- Numbers: `123`, `12.5`
- Booleans: `true`, `false`
- Null: `null`

### Filters

Transform values using filters with the pipe operator `|`:

```php
$template = [
    // Single filter
    'email' => '{{ user.email | lower }}',

    // Multiple filters (executed left to right)
    'name' => '{{ user.name | trim | ucfirst }}',

    // Filters with default value
    'email' => '{{ user.email ?? "no-email" | lower }}',
];
```

**Filter execution order:**
1. Resolve variable (`user.email`)
2. Apply default if null (`?? "default"`)
3. Apply filters left to right (`| filter1 | filter2`)

### Alias References

Reference already resolved target fields using `{{ @fieldName }}`:

```php
$template = [
    'fullName' => '{{ user.firstName | ucfirst }}',
    'displayName' => '{{ @fullName }}',  // Copies value from 'fullName'
    'greeting' => '{{ @fullName }}',     // Can reference multiple times
];

$sources = [
    'user' => ['firstName' => 'alice'],
];

$result = DataMapper::mapFromTemplate($template, $sources);
// [
//     'fullName' => 'Alice',
//     'displayName' => 'Alice',    // Copied from 'fullName'
//     'greeting' => 'Alice',       // Copied from 'fullName'
// ]
```

**Important:**
- Use `{{ @fieldName }}` to reference target fields (already resolved)
- Use `{{ source.field }}` to reference source fields
- Without `{{ }}`, values are treated as static strings

**Example with all three types:**

```php
$template = [
    'name' => '{{ user.name }}',        // Source reference
    'copyName' => '{{ @name }}',        // Target alias reference
    'role' => 'admin',                  // Static value
];

$sources = ['user' => ['name' => 'Alice']];
$result = DataMapper::mapFromTemplate($template, $sources);
// [
//     'name' => 'Alice',       // From source
//     'copyName' => 'Alice',   // Copied from target 'name'
//     'role' => 'admin',       // Static value
// ]
```

**Note:** Alias references work within the same nesting level.

## Built-in Filters

### String Filters

| Filter | Description | Example |
|--------|-------------|---------|
| `lower`, `lowercase` | Convert to lowercase | `'ALICE' → 'alice'` |
| `upper`, `uppercase` | Convert to uppercase | `'alice' → 'ALICE'` |
| `trim` | Remove whitespace | `'  text  ' → 'text'` |
| `ucfirst` | Uppercase first character | `'alice' → 'Alice'` |
| `ucwords` | Uppercase first character of each word | `'alice smith' → 'Alice Smith'` |

**Examples:**
```php
$template = [
    'email' => '{{ user.email | lower }}',
    'name' => '{{ user.name | ucfirst }}',
    'title' => '{{ user.title | ucwords }}',
    'clean' => '{{ user.input | trim }}',
];
```

### Array Filters

| Filter | Description | Example |
|--------|-------------|---------|
| `count` | Count elements | `[1, 2, 3] → 3` |
| `first` | Get first element | `[1, 2, 3] → 1` |
| `last` | Get last element | `[1, 2, 3] → 3` |
| `keys` | Get array keys | `['a' => 1] → ['a']` |
| `values` | Get array values | `['a' => 1] → [1]` |
| `reverse` | Reverse array | `[1, 2, 3] → [3, 2, 1]` |
| `sort` | Sort array | `[3, 1, 2] → [1, 2, 3]` |
| `unique` | Remove duplicates | `[1, 2, 1] → [1, 2]` |
| `join` | Join to string | `['a', 'b'] → 'a, b'` |

**Examples:**
```php
$template = [
    'tagCount' => '{{ post.tags | count }}',
    'firstTag' => '{{ post.tags | first }}',
    'sortedTags' => '{{ post.tags | sort }}',
    'uniqueTags' => '{{ post.tags | unique }}',
    'tagString' => '{{ post.tags | join }}',
];
```

### Utility Filters

| Filter | Description | Example |
|--------|-------------|---------|
| `json` | JSON encode | `['a' => 1] → '{"a":1}'` |
| `default` | Return empty string if null | `null → ''` |

**Examples:**
```php
$template = [
    'metadata' => '{{ post.meta | json }}',
    'fallback' => '{{ user.name | default }}',
];
```

## Custom Filters

Register your own filters using `FilterEngine::registerFilter()`:

```php
use event4u\DataHelpers\DataMapper\Template\FilterEngine;

// Register a custom filter
FilterEngine::registerFilter('encrypt', function($value) {
    return encrypt($value);
});

FilterEngine::registerFilter('hash', function($value) {
    return hash('sha256', $value);
});

FilterEngine::registerFilter('truncate', function($value) {
    return strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
});

// Use in template
$template = [
    'password' => '{{ user.password | hash }}',
    'token' => '{{ user.token | encrypt }}',
    'preview' => '{{ post.content | truncate }}',
];
```

**Custom filter examples:**

```php
// Date formatting
FilterEngine::registerFilter('date', function($value) {
    return date('Y-m-d', strtotime($value));
});

// Currency formatting
FilterEngine::registerFilter('currency', function($value) {
    return number_format($value, 2) . ' EUR';
});

// Slug generation
FilterEngine::registerFilter('slug', function($value) {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $value));
});

$template = [
    'createdAt' => '{{ post.created_at | date }}',
    'price' => '{{ product.price | currency }}',
    'slug' => '{{ post.title | slug }}',
];
```

## Combining with Classic References

You can mix template expressions with classic references in the same template:

```php
$template = [
    'profile' => [
        // Classic reference (no transformation)
        'id' => 'user.id',
        'rawEmail' => 'user.email',

        // Template expression (with transformation)
        'name' => '{{ user.firstName | ucfirst }}',
        'email' => '{{ user.email | trim | lower }}',
        'age' => '{{ user.age ?? 18 }}',

        // Classic reference with wildcards
        'tags' => 'user.tags.*',

        // Template expression with filter
        'tagCount' => '{{ user.tags | count }}',
    ],
];
```

**When to use which:**

| Use Case | Syntax | Example |
|----------|--------|---------|
| Simple mapping | Classic | `'user.name'` |
| With wildcards | Classic | `'users.*.email'` |
| With transformation | Expression | `'{{ user.name \| upper }}'` |
| With default value | Expression | `'{{ user.age ?? 18 }}'` |
| Multiple filters | Expression | `'{{ user.email \| trim \| lower }}'` |

## Advanced Examples

### Example 1: API Response Transformation

```php
$sources = [
    'response' => [
        'user' => [
            'id' => 123,
            'first_name' => '  alice  ',
            'last_name' => '  SMITH  ',
            'email' => '  ALICE@EXAMPLE.COM  ',
            'status' => null,
            'role' => 'admin',
            'created_at' => '2024-01-01 10:30:00',
        ],
        'tags' => ['php', 'laravel', 'symfony', 'php'],
    ],
];

$template = [
    'user' => [
        'id' => 'response.user.id',
        'fullName' => '{{ response.user.first_name | trim | ucfirst }}',
        'lastName' => '{{ response.user.last_name | trim | ucfirst }}',
        'email' => '{{ response.user.email | trim | lower }}',
        'status' => '{{ response.user.status ?? "active" }}',
        'role' => '{{ response.user.role | upper }}',
        'createdAt' => 'response.user.created_at',
    ],
    'metadata' => [
        'tags' => '{{ response.tags | unique | sort }}',
        'tagCount' => '{{ response.tags | unique | count }}',
        'firstTag' => '{{ response.tags | first | upper }}',
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

**Result:**
```json
{
    "user": {
        "id": 123,
        "fullName": "Alice",
        "lastName": "Smith",
        "email": "alice@example.com",
        "status": "active",
        "role": "ADMIN",
        "createdAt": "2024-01-01 10:30:00"
    },
    "metadata": {
        "tags": ["laravel", "php", "symfony"],
        "tagCount": 3,
        "firstTag": "PHP"
    }
}
```

### Example 2: Form Data Normalization

```php
$sources = [
    'form' => [
        'firstName' => '  John  ',
        'lastName' => '  DOE  ',
        'email' => '  JOHN.DOE@EXAMPLE.COM  ',
        'phone' => '',
        'age' => null,
        'newsletter' => null,
    ],
];

$template = [
    'user' => [
        'firstName' => '{{ form.firstName | trim | ucfirst }}',
        'lastName' => '{{ form.lastName | trim | ucfirst }}',
        'email' => '{{ form.email | trim | lower }}',
        'phone' => '{{ form.phone ?? "N/A" }}',
        'age' => '{{ form.age ?? 18 }}',
        'newsletter' => '{{ form.newsletter ?? false }}',
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

### Example 3: E-Commerce Product Mapping

```php
$sources = [
    'product' => [
        'name' => 'wireless headphones',
        'description' => '  High quality wireless headphones with noise cancellation  ',
        'price' => 99.99,
        'stock' => 0,
        'categories' => ['electronics', 'audio', 'headphones'],
        'tags' => ['wireless', 'bluetooth', 'noise-cancelling', 'wireless'],
    ],
];

// Register custom filters
FilterEngine::registerFilter('currency', fn($v) => number_format($v, 2) . ' EUR');
FilterEngine::registerFilter('truncate', fn($v) => strlen($v) > 50 ? substr($v, 0, 50) . '...' : $v);

$template = [
    'product' => [
        'title' => '{{ product.name | ucwords }}',
        'shortDescription' => '{{ product.description | trim | truncate }}',
        'price' => '{{ product.price | currency }}',
        'inStock' => '{{ product.stock ?? 0 }}',
        'categoryCount' => '{{ product.categories | count }}',
        'primaryCategory' => '{{ product.categories | first | ucfirst }}',
        'tags' => '{{ product.tags | unique | sort }}',
        'tagString' => '{{ product.tags | unique | join }}',
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

### Example 4: User Profile with Alias References

```php
$sources = [
    'user' => [
        'firstName' => 'alice',
        'lastName' => 'smith',
        'email' => 'alice@example.com',
    ],
];

$template = [
    'firstName' => '{{ user.firstName | ucfirst }}',
    'lastName' => '{{ user.lastName | ucfirst }}',
    'email' => '{{ user.email | lower }}',

    // Alias references
    'displayName' => '@firstName',
    'greeting' => '@firstName',
    'contactEmail' => '@email',
];

$result = DataMapper::mapFromTemplate($template, $sources);
// [
//     'firstName' => 'Alice',
//     'lastName' => 'Smith',
//     'email' => 'alice@example.com',
//     'displayName' => 'Alice',
//     'greeting' => 'Alice',
//     'contactEmail' => 'alice@example.com',
// ]
```

### Example 5: JSON Template

Templates can be provided as JSON strings:

```php
$templateJson = '{
    "profile": {
        "name": "{{ user.firstName | ucfirst }}",
        "email": "{{ user.email | lower }}",
        "age": "{{ user.age ?? 18 }}"
    }
}';

$sources = [
    'user' => ['firstName' => 'alice', 'email' => 'ALICE@EXAMPLE.COM'],
];

$result = DataMapper::mapFromTemplate($templateJson, $sources);
```

## API Reference

### DataMapper::mapFromTemplate()

```php
public static function mapFromTemplate(
    array|string $template,
    array $sources,
    bool $skipNull = true,
    bool $reindexWildcard = false,
): array
```

Build a new array from a template with expression support.

**Parameters:**
- `$template` - Template array or JSON string with expressions
- `$sources` - Map of source name => source data
- `$skipNull` - Skip null values (default: true)
- `$reindexWildcard` - Reindex wildcard results (default: false)

**Returns:** Mapped array

### ExpressionParser::parse()

```php
public static function parse(string $value): ?array
```

Parse a template expression.

**Returns:**
```php
[
    'type' => 'expression' | 'alias',
    'path' => 'user.name',
    'default' => mixed,
    'filters' => ['lower', 'trim'],
]
```

### ExpressionParser::hasExpression()

```php
public static function hasExpression(string $value): bool
```

Check if a string contains a template expression.

### FilterEngine::apply()

```php
public static function apply(mixed $value, array $filters): mixed
```

Apply multiple filters to a value.

### FilterEngine::registerFilter()

```php
public static function registerFilter(string $name, callable $callback): void
```

Register a custom filter.

**Example:**
```php
FilterEngine::registerFilter('myfilter', function($value) {
    return strtoupper($value);
});
```

### ExpressionEvaluator::evaluate()

```php
public static function evaluate(
    string $value,
    array $sources,
    array $aliases = []
): mixed
```

Evaluate a template expression.

---

## Comparison: Classic vs Expression Syntax

| Feature | Classic | Expression |
|---------|---------|------------|
| Simple mapping | `'user.name'` | `'{{ user.name }}'` |
| Default value | ❌ Not supported | `'{{ user.name ?? "Unknown" }}'` |
| Transformation | ❌ Not supported | `'{{ user.name \| upper }}'` |
| Multiple filters | ❌ Not supported | `'{{ user.name \| trim \| upper }}'` |
| Wildcards | ✅ `'users.*.email'` | ❌ Not supported in expressions |
| Performance | ⚡ Faster | 🔄 Slightly slower (parsing) |
| Readability | ✅ Simple | ✅ Declarative |

**Best Practice:** Use classic references for simple mappings and wildcards, use expressions for transformations and defaults.

---

## Performance Considerations

1. **Expression Parsing:** Expressions are parsed once per template value
2. **Filter Execution:** Filters are executed in order for each value
3. **Caching:** Consider caching compiled templates for repeated use
4. **Classic References:** Use classic references when no transformation is needed (faster)

**Optimization tips:**
- Use classic references for simple mappings
- Minimize filter chains (combine filters when possible)
- Register custom filters for complex transformations (avoid inline logic)
- Cache templates when using the same template multiple times

---

## Migration Guide

### From Classic References

**Before:**
```php
$template = [
    'name' => 'user.name',
    'email' => 'user.email',
];

// Transformation in code
$result = DataMapper::mapFromTemplate($template, $sources);
$result['name'] = ucfirst($result['name']);
$result['email'] = strtolower($result['email']);
```

**After:**
```php
$template = [
    'name' => '{{ user.name | ucfirst }}',
    'email' => '{{ user.email | lower }}',
];

$result = DataMapper::mapFromTemplate($template, $sources);
// Transformations applied automatically
```

### From Hooks

**Before:**
```php
$hooks = [
    'postTransform' => function($value, $context) {
        if ($context->tgtPath() === 'email') {
            return strtolower($value);
        }
        return $value;
    },
];

$result = DataMapper::map($source, [], $mapping, hooks: $hooks);
```

**After:**
```php
$template = [
    'email' => '{{ user.email | lower }}',
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

---

**See also:**
- [DataMapper Documentation](data-mapper.md)
- [Template Mapping Guide](data-mapper.md#mapping-templates)
- [Example: 08-template-expressions.php](../examples/08-template-expressions.php)
