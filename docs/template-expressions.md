# Template Expressions

🚀 **Powerful template expression engine** for declarative data transformations - inspired by Twig, but designed specifically for data
mapping.

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
    - [DataMapper::mapFromTemplate()](#datamappermapfromtemplate)
    - [ExpressionParser](#expressionparser)
    - [FilterEngine](#filterengine)
    - [FilterRegistry](#filterregistry)
    - [ExpressionEvaluator](#expressionevaluator)
    - [TemplateExpressionProcessor](#templateexpressionprocessor)
- [Usage Across Mapping Methods](#usage-across-mapping-methods)
- [Wildcard WHERE and ORDER BY Clauses](#wildcard-where-and-order-by-clauses)
    - [WHERE Clauses](#where-clauses)
    - [ORDER BY Clauses](#order-by-clauses)
    - [Custom Wildcard Operators](#custom-wildcard-operators)

## Overview

The Template Expression Engine provides a powerful expression syntax that works across **all mapping methods** (`map()`, `mapFromFile()`, `mapFromTemplate()`):

- ✅ **Transform values** using filter syntax (e.g., `| lower`, `| trim`)
- ✅ **Provide defaults** for null/missing values (e.g., `?? 'Unknown'`)
- ✅ **Chain multiple filters** (e.g., `| trim | lower | ucfirst`)
- ✅ **Reference source fields** (e.g., `{{ user.name }}`)
- ✅ **Reference target fields** using aliases (e.g., `{{ @fieldName }}`)
- ✅ **Use static values** (e.g., `'admin'` without `{{ }}`)
- ✅ **Wildcard support** - Apply filters to array elements (e.g., `{{ users.*.name | upper }}`)

**Key Features:**

- 🎯 **Declarative syntax** - Define transformations in the template
- 🔄 **Unified across all methods** - Same syntax in `map()`, `mapFromFile()`, and `mapFromTemplate()`
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

### Filters (Filter Syntax)

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

**Execution order:**

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

All built-in filters can be used in template expressions with filter syntax (`| alias`).

### String Filters

| Alias                | Description                            | Example                         |
|----------------------|----------------------------------------|---------------------------------|
| `lower`, `lowercase` | Convert to lowercase                   | `'ALICE' → 'alice'`             |
| `upper`, `uppercase` | Convert to uppercase                   | `'alice' → 'ALICE'`             |
| `trim`               | Remove whitespace                      | `'  text  ' → 'text'`           |
| `ucfirst`            | Uppercase first character              | `'alice' → 'Alice'`             |
| `ucwords`            | Uppercase first character of each word | `'alice smith' → 'Alice Smith'` |

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

| Alias     | Description       | Example                 |
|-----------|-------------------|-------------------------|
| `count`   | Count elements    | `[1, 2, 3] → 3`         |
| `first`   | Get first element | `[1, 2, 3] → 1`         |
| `last`    | Get last element  | `[1, 2, 3] → 3`         |
| `keys`    | Get array keys    | `['a' => 1] → ['a']`    |
| `values`  | Get array values  | `['a' => 1] → [1]`      |
| `reverse` | Reverse array     | `[1, 2, 3] → [3, 2, 1]` |
| `sort`    | Sort array        | `[3, 1, 2] → [1, 2, 3]` |
| `unique`  | Remove duplicates | `[1, 2, 1] → [1, 2]`    |
| `join`    | Join to string    | `['a', 'b'] → 'a, b'`   |

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

| Alias     | Description                          | Example                      |
|-----------|--------------------------------------|------------------------------|
| `json`    | JSON encode                          | `['a' => 1] → '{"a":1}'`     |
| `default` | Return empty string if null          | `null → ''`                  |
| `between` | Check if value is in range (boolean) | `50 \| between:0:100 → true` |
| `clamp`   | Limit value to range                 | `150 \| clamp:0:100 → 100.0` |

**Examples:**

```php
$template = [
    'metadata' => '{{ post.meta | json }}',
    'fallback' => '{{ user.name | default }}',

    // Range validation
    'isValidAge' => '{{ user.age | between:18:65 }}',
    'isInRange' => '{{ score | between:0:100 }}',

    // Value clamping
    'normalizedAge' => '{{ user.age | clamp:18:65 }}',
    'percentage' => '{{ score | clamp:0:100 }}',
];
```

**Between vs Clamp:**

- `between` returns a **boolean** (true/false) - useful for validation
- `clamp` returns a **modified value** - useful for normalization

```php
// Between (validation)
{{ 150 | between:0:100 }}  // → false (out of range)
{{ 50 | between:0:100 }}   // → true (in range)

// Clamp (normalization)
{{ 150 | clamp:0:100 }}    // → 100.0 (limited to max)
{{ 50 | clamp:0:100 }}     // → 50.0 (unchanged)
```

**Strict Mode (Between only):**

```php
// Inclusive (default): >= and <=
{{ 3 | between:3:5 }}        // → true (3 is included)

// Strict mode: > and <
{{ 3 | between:3:5:strict }} // → false (3 is excluded)
{{ 4 | between:3:5:strict }} // → true (only 4 is in range)
```

## Custom Filters

Create reusable filter classes that can be used both in pipelines and template expressions (filter syntax):

```php
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;

final class EncryptFilter implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return encrypt($value);
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['encrypt', 'enc'];
    }
}

// Register the filter
FilterRegistry::register(EncryptFilter::class);

// Use in template expressions
$template = [
    'token' => '{{ user.token | encrypt }}',
];

// Or use in pipeline
DataMapper::pipe([EncryptFilter::class])->map($source, [], $mapping);
```

**Benefits:**

- ✅ Reusable in both pipelines and template expressions
- ✅ Type-safe with PHPStan
- ✅ Testable
- ✅ Multiple aliases supported
- ✅ Access to full HookContext

**More examples:**

```php
// Date formatting filter
final class DateFormat implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getHook(): string { return 'preTransform'; }
    public function getFilter(): ?string { return null; }

    /** @return array<int, string> */
    public function getAliases(): array { return ['date']; }
}

// Currency formatting filter
final class CurrencyFormat implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return number_format($value, 2) . ' EUR';
    }

    public function getHook(): string { return 'preTransform'; }
    public function getFilter(): ?string { return null; }

    /** @return array<int, string> */
    public function getAliases(): array { return ['currency']; }
}

// Register filters
FilterRegistry::register(DateFormat::class);
FilterRegistry::register(CurrencyFormat::class);

// Use in templates
$template = [
    'createdAt' => '{{ post.created_at | date }}',
    'price' => '{{ product.price | currency }}',
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

| Use Case            | Syntax     | Example                               |
|---------------------|------------|---------------------------------------|
| Simple mapping      | Classic    | `'user.name'`                         |
| With wildcards      | Classic    | `'users.*.email'`                     |
| With transformation | Expression | `'{{ user.name \| upper }}'`          |
| With default value  | Expression | `'{{ user.age ?? 18 }}'`              |
| Multiple filters    | Expression | `'{{ user.email \| trim \| lower }}'` |

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
        "tags": [
            "laravel",
            "php",
            "symfony"
        ],
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

$template = [
    'product' => [
        'title' => '{{ product.name | ucwords }}',
        'shortDescription' => '{{ product.description | trim }}',
        'price' => '{{ product.price }}',
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

### FilterRegistry::register()

```php
public static function register(string $filterClass): void
```

Register a filter to make it available in template expressions.

**Example:**

```php
FilterRegistry::register(MyCustomFilter::class);

// Now use it in templates
$template = ['name' => '{{ user.name | my_custom }}'];
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

### TemplateExpressionProcessor

**File:** `src/DataMapper/Support/TemplateExpressionProcessor.php`

Central processor for template expressions. This class unifies template expression handling across all mapping methods (`map()`, `mapFromFile()`, `mapFromTemplate()`).

#### TemplateExpressionProcessor::isExpression()

```php
public static function isExpression(mixed $value): bool
```

Check if a value is a template expression.

**Example:**

```php
use event4u\DataHelpers\DataMapper\Support\TemplateExpressionProcessor;

TemplateExpressionProcessor::isExpression('{{ user.name }}'); // true
TemplateExpressionProcessor::isExpression('user.name'); // false
TemplateExpressionProcessor::isExpression('{{ user.name | upper }}'); // true
```

#### TemplateExpressionProcessor::parse()

```php
public static function parse(string $expression): array
```

Parse a template expression into components.

**Returns:**

```php
[
    'path' => 'user.name',           // Data path
    'filters' => ['upper', 'trim'],  // Filter names
    'default' => 'Unknown',          // Default value (from ??)
    'hasFilters' => true,            // Whether filters are present
]
```

**Example:**

```php
$parsed = TemplateExpressionProcessor::parse('{{ user.name | upper | trim ?? "Unknown" }}');

// Result:
// [
//     'path' => 'user.name',
//     'filters' => ['upper', 'trim'],
//     'default' => 'Unknown',
//     'hasFilters' => true,
// ]
```

#### TemplateExpressionProcessor::evaluate()

```php
public static function evaluate(
    string $expression,
    mixed $source = null,
    array $sources = []
): mixed
```

Evaluate a template expression against a data source.

**Parameters:**

- `$expression` - Template expression (e.g., `'{{ user.name | upper }}'`)
- `$source` - Single data source (for `map()`, `mapFromFile()`)
- `$sources` - Named data sources (for `mapFromTemplate()`)

**Example:**

```php
// For map() / mapFromFile()
$source = ['user' => ['name' => 'alice']];
$result = TemplateExpressionProcessor::evaluate('{{ user.name | upper }}', $source);
// 'ALICE'

// For mapFromTemplate()
$sources = ['user' => ['name' => 'alice']];
$result = TemplateExpressionProcessor::evaluate('{{ user.name | upper }}', null, $sources);
// 'ALICE'
```

#### TemplateExpressionProcessor::extractPathAndFilters()

```php
public static function extractPathAndFilters(string $expression): array
```

Extract path, filters, and default value from a template expression.

**Returns:**

```php
[
    'path' => 'user.name',
    'filters' => ['upper', 'trim'],
    'default' => 'Unknown',
]
```

**Example:**

```php
$extracted = TemplateExpressionProcessor::extractPathAndFilters('{{ user.name | upper ?? "Unknown" }}');

// Result:
// [
//     'path' => 'user.name',
//     'filters' => ['upper'],
//     'default' => 'Unknown',
// ]
```

#### TemplateExpressionProcessor::applyFilters()

```php
public static function applyFilters(mixed $value, array $filters): mixed
```

Apply filters to a value. This is a convenience wrapper around `FilterEngine::apply()`.

**Example:**

```php
$value = 'alice';
$filters = ['upper', 'trim'];

$result = TemplateExpressionProcessor::applyFilters($value, $filters);
// 'ALICE'

// Works with null values (important for 'default' filter)
$value = null;
$filters = ['default:"N/A"', 'upper'];

$result = TemplateExpressionProcessor::applyFilters($value, $filters);
// 'N/A'
```

#### TemplateExpressionProcessor::hasFilters()

```php
public static function hasFilters(string $expression): bool
```

Check if an expression contains filters.

**Example:**

```php
TemplateExpressionProcessor::hasFilters('{{ user.name }}'); // false
TemplateExpressionProcessor::hasFilters('{{ user.name | upper }}'); // true
```

#### TemplateExpressionProcessor::extractPath()

```php
public static function extractPath(string $expression): string
```

Extract the path from a template expression (without filters).

**Example:**

```php
$path = TemplateExpressionProcessor::extractPath('{{ user.name | upper }}');
// 'user.name'
```

---

## Usage Across Mapping Methods

The `TemplateExpressionProcessor` enables consistent template expression support across all mapping methods:

### In `map()` and `mapFromFile()`

```php
use event4u\DataHelpers\DataMapper;

$source = [
    'users' => [
        ['name' => 'alice', 'email' => null],
        ['name' => 'bob', 'email' => 'bob@example.com'],
    ],
];

$mapping = [
    // Simple filter
    'names' => '{{ users.*.name | upper }}',

    // Filter with default value
    'emails' => '{{ users.*.email | default:"no-email@example.com" }}',

    // Filter chain
    'formatted' => '{{ users.*.name | trim | ucfirst }}',
];

$result = DataMapper::map($source, [], $mapping);

// Result:
// [
//     'names' => ['ALICE', 'BOB'],
//     'emails' => ['no-email@example.com', 'bob@example.com'],
//     'formatted' => ['Alice', 'Bob'],
// ]
```

### In `mapFromTemplate()`

```php
$template = [
    'users' => [
        '*' => [
            'name' => '{{ users.*.name | upper }}',
            'email' => '{{ users.*.email | default:"no-email@example.com" }}',
        ],
    ],
];

$sources = [
    'users' => [
        ['name' => 'alice', 'email' => null],
        ['name' => 'bob', 'email' => 'bob@example.com'],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources);
```

---

## Comparison: Classic vs Expression Syntax

| Feature          | Classic             | Expression                           |
|------------------|---------------------|--------------------------------------|
| Simple mapping   | `'user.name'`       | `'{{ user.name }}'`                  |
| Default value    | ❌ Not supported     | `'{{ user.name ?? "Unknown" }}'`     |
| Transformation   | ❌ Not supported     | `'{{ user.name \| upper }}'`         |
| Multiple filters | ❌ Not supported     | `'{{ user.name \| trim \| upper }}'` |
| Wildcards        | ✅ `'users.*.email'` | ❌ Not supported in expressions       |
| Performance      | ⚡ Faster            | 🔄 Slightly slower (parsing)         |
| Readability      | ✅ Simple            | ✅ Declarative                        |

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

## Wildcard WHERE and ORDER BY Clauses

Filter and sort wildcard arrays using Laravel Query Builder-style WHERE and ORDER BY clauses.

### WHERE Clauses

#### Basic WHERE Clause

Filter items before mapping:

```php
$template = [
    'project' => [
        'number' => '{{ ConstructionSite.nr_lv }}',
    ],
    'positions' => [
        'WHERE' => [
            '{{ ConstructionSite.Positions.Position.*.project_number }}' => '{{ project.number }}',
        ],
        '*' => [
            'number' => '{{ ConstructionSite.Positions.Position.*.pos_number }}',
            'type' => '{{ ConstructionSite.Positions.Position.*.type }}',
        ],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources, true, true);
// Only positions matching the project number are included
```

#### AND Conditions

Multiple conditions (implicit AND):

```php
'WHERE' => [
    '{{ positions.*.project_number }}' => 'P-001',
    '{{ positions.*.type }}' => 'gravel',
],
```

Explicit AND:

```php
'WHERE' => [
    'AND' => [
        '{{ positions.*.project_number }}' => 'P-001',
        '{{ positions.*.type }}' => 'gravel',
    ],
],
```

#### OR Conditions

```php
'WHERE' => [
    'OR' => [
        '{{ positions.*.type }}' => 'gravel',
        '{{ positions.*.type }}' => 'sand',
    ],
],
```

#### Nested AND/OR

Complex filtering with nested conditions:

```php
'WHERE' => [
    'AND' => [
        '{{ positions.*.project_number }}' => 'P-001',
        'OR' => [
            '{{ positions.*.type }}' => 'gravel',
            '{{ positions.*.quantity }}' => 100,
        ],
    ],
],
```

Multiple OR groups:

```php
'WHERE' => [
    'OR' => [
        [
            'AND' => [
                '{{ positions.*.type }}' => 'gravel',
                '{{ positions.*.quantity }}' => 100,
            ],
        ],
        [
            'AND' => [
                '{{ positions.*.type }}' => 'sand',
                '{{ positions.*.quantity }}' => 80,
            ],
        ],
    ],
],
```

#### Case-Insensitive Keywords

Both `AND`/`OR` and `and`/`or` work:

```php
'WHERE' => [
    'and' => [  // lowercase works too
        '{{ positions.*.project_number }}' => 'P-001',
        'or' => [  // lowercase works too
            '{{ positions.*.type }}' => 'gravel',
            '{{ positions.*.type }}' => 'sand',
        ],
    ],
],
```

### ORDER BY Clauses

Sort wildcard arrays by one or more fields.

#### Single Field Sorting

Sort by a single field in ascending or descending order:

```php
$template = [
    'sorted_positions' => [
        'ORDER BY' => [
            '{{ positions.*.pos_number }}' => 'ASC',
        ],
        '*' => [
            'number' => '{{ positions.*.pos_number }}',
            'type' => '{{ positions.*.type }}',
        ],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources, true, true);
// Positions sorted by pos_number in ascending order
```

#### Multiple Field Sorting

Sort by multiple fields (first field has priority):

```php
'ORDER BY' => [
    '{{ positions.*.priority }}' => 'ASC',
    '{{ positions.*.quantity }}' => 'DESC',
],
```

#### Descending Order

Use `DESC` for descending order:

```php
'ORDER BY' => [
    '{{ positions.*.quantity }}' => 'DESC',
],
```

#### Case-Insensitive Direction

Both `ASC`/`DESC` and `asc`/`desc` work:

```php
'ORDER BY' => [
    '{{ positions.*.pos_number }}' => 'asc',  // lowercase works too
],
```

#### Combining WHERE and ORDER BY

Filter first, then sort:

```php
$template = [
    'filtered_sorted_positions' => [
        'WHERE' => [
            '{{ positions.*.type }}' => 'gravel',
        ],
        'ORDER BY' => [
            '{{ positions.*.quantity }}' => 'DESC',
        ],
        '*' => [
            'number' => '{{ positions.*.pos_number }}',
            'quantity' => '{{ positions.*.quantity }}',
        ],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources, true, true);
// Only gravel positions, sorted by quantity descending
```

### Features

- ✅ **WHERE clauses** - Laravel-style AND/OR logic for filtering
- ✅ **ORDER BY clauses** - Sort by multiple fields with ASC/DESC
- ✅ **Nested conditions** - Unlimited nesting depth for WHERE
- ✅ **Case-insensitive** - `AND`/`and`, `OR`/`or`, `ASC`/`asc`, `DESC`/`desc` all work
- ✅ **Template expressions** - Use `{{ }}` in conditions and sort fields
- ✅ **Alias references** - Reference other template fields
- ✅ **Numeric sorting** - Proper numeric comparison (2 < 10 < 100)
- ✅ **Null handling** - Nulls come first in ASC, last in DESC
- ✅ **Reindexing** - Filtered/sorted results are automatically reindexed

### Custom Wildcard Operators

Register your own operators to extend wildcard functionality.

#### Registering an Operator

```php
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;

WildcardOperatorRegistry::register('LIMIT', function(array $items, mixed $config): array {
    if (!is_int($config) || $config < 0) {
        return $items;
    }

    $result = [];
    $count = 0;

    foreach ($items as $index => $item) {
        if ($count >= $config) {
            break;
        }
        $result[$index] = $item;
        $count++;
    }

    return $result;
});
```

#### Using Custom Operators

```php
$template = [
    'top_products' => [
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        'LIMIT' => 3,
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result = DataMapper::mapFromTemplate($template, $sources, true, true);
```

#### Operator Handler Signature

```php
function(
    array $items,      // Wildcard array to process
    mixed $config,     // Operator configuration from template
    mixed $sources,    // Source data (optional)
    array $aliases     // Resolved aliases (optional)
): array
```

#### Built-in Operators

- `WHERE` / `where` - Filter items with AND/OR logic
- `ORDER BY` / `ORDER_BY` / `order by` / `order_by` / `order` - Sort items

#### Example: GROUP BY Operator

```php
WildcardOperatorRegistry::register('GROUP BY', function(array $items, mixed $config, mixed $sources): array {
    if (!is_string($config)) {
        return $items;
    }

    $grouped = [];

    foreach ($items as $index => $item) {
        $fieldPath = str_replace('*', (string)$index, $config);

        if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
            $path = trim(substr($fieldPath, 2, -2));
            $accessor = new \event4u\DataHelpers\DataAccessor($sources);
            $groupKey = $accessor->get($path);
        } else {
            $groupKey = $item[$config] ?? 'default';
        }

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [];
        }
        $grouped[$groupKey][] = ['index' => $index, 'item' => $item];
    }

    // Return first item of each group
    $result = [];
    foreach ($grouped as $group) {
        $first = $group[0];
        $result[$first['index']] = $first['item'];
    }

    return $result;
});
```

#### Managing Operators

```php
// Check if operator exists
WildcardOperatorRegistry::has('LIMIT'); // true

// Get all registered operators
$operators = WildcardOperatorRegistry::all(); // ['WHERE', 'ORDERBY', 'ORDER', 'LIMIT', ...]

// Unregister an operator
WildcardOperatorRegistry::unregister('LIMIT');
```

---

**See also:**

- [DataMapper Documentation](data-mapper.md)
- [Template Mapping Guide](data-mapper.md#mapping-templates)
- [Example: 08-template-expressions.php](../examples/08-template-expressions.php)
- [Example: 13-wildcard-where-clause.php](../examples/13-wildcard-where-clause.php)
