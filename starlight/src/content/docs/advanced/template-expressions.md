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
- **Conditional expressions** with ternary operator (e.g., `{{ status == "active" ? 1 : 0 }}`)
- **Membership checks** with `IN` / `NOT IN` (e.g., `{{ status IN ["a","b"] ? 1 : 0 }}`)
- **Filtered conditions** with parentheses (e.g., `{{ (status | lower) == "active" ? 1 : 0 }}`)
- **Reference source fields** (e.g., `{{ user.name }}`)
- **Reference target fields** using aliases (e.g., `{{ @fieldName }}`)
- **Use static values** (e.g., `'admin'` without `{{ }}`)
- **Wildcard support** - Apply filters to array elements (e.g., `{{ users.*.name | upper }}`)

**Key Features:**

- 🎯 **Declarative syntax** - Define transformations in the template
- 🔄 **Unified across all methods** - Same syntax in `map()`, `mapFromFile()` and `mapFromTemplate()`
- 🔄 **Composable filters** - Chain multiple transformations
- 📦 **35+ built-in filters** - Common transformations out of the box
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

### Type Casting Filters

Cast values to specific types:

```php
// int / integer - Convert to integer
'{{ value | int }}' // '42' -> 42
'{{ value | integer }}' // '2075436601850' -> 2075436601850

// float - Convert to float
'{{ value | float }}' // '3.14' -> 3.14
'{{ price | float }}' // '19.99' -> 19.99

// bool / boolean - Convert to boolean
'{{ value | bool }}' // '1' -> true
'{{ active | boolean }}' // 'yes' -> true

// string - Convert to string
'{{ value | string }}' // 42 -> '42'
'{{ id | string }}' // 123 -> '123'

// array - Convert to array
'{{ value | array }}' // 'test' -> ['test']
'{{ items | array }}' // Wraps scalars, converts objects

// decimal - Format as decimal with precision
'{{ price | decimal }}' // 123.456 -> '123.46' (default: 2 decimals)
'{{ amount | decimal:4 }}' // 123.456 -> '123.4560' (4 decimals)

// json - Convert to JSON string
'{{ data | json }}' // ['a' => 1] -> '{"a":1}'
'{{ items | json }}' // Encodes arrays/objects to JSON
```

**Type Casting Details:**

- **int/integer**: Casts numeric values to integers, skips null and non-numeric values
- **bool/boolean**: Converts `'1'`, `'true'`, `'yes'`, `'on'`, `1`, `true` → `true`; `'0'`, `'false'`, `'no'`, `'off'`, `''`, `0`, `false` → `false`
- **float**: Casts numeric values to floats, skips null and non-numeric values
- **string**: Casts scalar values to strings, skips null and non-scalar values
- **array**: Wraps scalars in array, converts objects to arrays, keeps arrays unchanged
- **decimal**: Formats numbers with specified precision (default: 2), useful for prices and amounts
- **json**: Encodes arrays/objects to JSON strings, skips existing strings to avoid double-encoding

### Arithmetic Filters

Apply a single arithmetic operation to a numeric value. The second operand can be a
**fixed literal** or a **source path** that is resolved from the data before the filter runs.

```php
// multiply - Multiply by a factor (e.g. hours -> minutes)
'{{ duration.hours | multiply:60 }}'   // 2 -> 120

// divide - Divide by a divisor (e.g. minutes -> hours)
'{{ duration.minutes | divide:60 }}'   // 90 -> 1.5

// add - Add a number
'{{ price.net | add:1 }}'              // 41 -> 42

// subtract - Subtract a number
'{{ price.gross | subtract:19 }}'      // 119 -> 100

// The operand can also come from the source data:
'{{ price.net | multiply:order.taxFactor }}'  // factor read from order.taxFactor
'{{ total.amount | divide:order.installments }}'
```

**Arithmetic Details:**

- Operate on numeric values only; non-numeric values are returned unchanged.
- A missing or non-numeric operand returns the value unchanged.
- `divide` returns the value unchanged on division by zero.
- The operand resolves as a source path when it is **not** numeric and **not** a
  `true` / `false` / `null` keyword; if the path does not resolve, the literal is kept.
- A custom filter can opt into the same source-path argument resolution by
  implementing `event4u\DataHelpers\DataMapper\Pipeline\ResolvesSourceArguments`.

### Date Filters

```php
// date - Format date (default: 'Y-m-d H:i:s')
'{{ created | date }}'          // DateTime -> '2024-01-15 10:30:00'
'{{ created | date:"Y-m-d" }}'  // DateTime -> '2024-01-15'
'{{ created | date:"d.m.Y" }}'  // DateTime -> '15.01.2024'
'{{ created | date:"c" }}'      // DateTime -> ISO 8601

// date_format - Alias for date
'{{ created | date_format:"Y-m-d" }}' // Same as date

// timestamp - Convert to Unix timestamp (int)
'{{ created | timestamp }}'       // DateTime -> 1705276800
'{{ created | timestamp }}'       // '2024-01-15' -> 1705276800
'{{ created | timestamp }}'       // 1705276800 -> 1705276800 (pass-through)
```

**Supported input types for `date` and `timestamp`:**
- `DateTimeInterface` (DateTime, DateTimeImmutable, Carbon)
- Date strings (any format supported by `strtotime()`)
- Unix timestamps (int) — `date` reformats, `timestamp` passes through

The format parameter uses [PHP date format](https://www.php.net/manual/en/datetime.format.php) characters.

### Validation Filters

```php
// in - Validate value is in allowed list (returns value or null + exception)
'{{ type | in:[VEHICLE,ORDER,PROJECT] }}'              // 'VEHICLE' -> 'VEHICLE'
'{{ type | in:[VEHICLE,ORDER,PROJECT] }}'              // 'UNKNOWN' -> null + exception

// in with optional flag - empty/null values are allowed without error
'{{ type | in:[VEHICLE,ORDER]:optional }}'             // '' -> null (no error)

// in_list - Alias for in
'{{ type | in_list:[ACTIVE,INACTIVE] }}'

// not_in - Validate value is NOT in blocked list
'{{ status | not_in:[DELETED,ARCHIVED] }}'             // 'ACTIVE' -> 'ACTIVE'
'{{ status | not_in:[DELETED,ARCHIVED] }}'             // 'DELETED' -> null + exception

// not_in with optional flag
'{{ status | not_in:[DELETED,ARCHIVED]:optional }}'    // '' -> null (no error)

// not_in_list - Alias for not_in
'{{ status | not_in_list:[DELETED] }}'
```

Combine with other filters for full validation chains:

```php
// Normalize and validate
'{{ ownerType | string | upper | in:[VEHICLE,ORDER,PROJECT,TOOL,EMPLOYEE] }}'
```

When a value fails validation, the exception is handled via `MapperExceptions`:
- **Collect mode** (default): Exception is collected, `null` is returned
- **Throw mode**: Exception is thrown immediately

The `:optional` flag treats empty strings and `null` as "not set" — no error is raised and `null` is returned.

### Data Cleaning Filters

```php
// empty_to_null - Convert empty values to null
'{{ bio | empty_to_null }}' // '' -> null, [] -> null

// empty_to_null with zero conversion
'{{ count | empty_to_null:"zero" }}' // 0 -> null

// empty_to_null with string zero conversion
'{{ value | empty_to_null:"string_zero" }}' // '0' -> null

// empty_to_null with false conversion
'{{ active | empty_to_null:"false" }}' // false -> null

// empty_to_null with multiple conversions
'{{ amount | empty_to_null:"zero,string_zero" }}' // 0 -> null, '0' -> null

// empty_to_null with all conversions
'{{ flexible | empty_to_null:"zero,string_zero,false" }}' // 0, '0', false -> null

// default - Provide default value
'{{ name | default:"Unknown" }}' // null -> 'Unknown'
```

**ConvertEmptyToNull Options:**
- No options: Converts `""`, `[]` and `null` to `null`
- `"zero"`: Also converts integer `0` to `null`
- `"string_zero"`: Also converts string `"0"` to `null`
- `"false"`: Also converts boolean `false` to `null`
- `"zero,string_zero"`: Converts both zero types to `null`
- `"zero,string_zero,false"`: Converts all three types to `null`

**Note:** By default, boolean `false` is **not** converted to `null` unless you use the `"false"` option.

**See also:** [ConvertEmptyToNull Attribute](/data-helpers/simple-dto/convert-empty-to-null/) for SimpleDto usage.

## Conditional Expressions

### Ternary Operator

Transform values based on conditions:

<!-- skip-test: Syntax example only -->
```php
// Equality check
'{{ user.status == "active" ? 1 : 0 }}'

// Comparison operators: ==, !=, >, <, >=, <=
'{{ user.age >= 18 ? "adult" : "minor" }}'
```

### IN / NOT IN Operator

Check if a value is contained in an array literal:

<!-- skip-test: Syntax example only -->
```php
// IN - check membership
'{{ status IN ["active","pending"] ? 1 : 0 }}'

// NOT IN - inverse check
'{{ status NOT IN ["Defekt","Verkauft"] ? 1 : 0 }}'

// With numeric values
'{{ category_id IN [1,3,5,7] ? 1 : 0 }}'

// With null in the array
'{{ status IN [null,"Ok"] ? 1 : 0 }}'
```

### Pipe Filters in Conditions

Use **parentheses** to apply filters before comparing:

<!-- skip-test: Syntax example only -->
```php
// Case-insensitive comparison
'{{ (user.status | lower) == "active" ? 1 : 0 }}'

// Combined with IN
'{{ (equipment.*.status | lower) IN ["verkauft","defekt","verschrottet"] ? 1 : 0 }}'
```

:::caution[Parentheses Required]
Filters in conditions **must** use parentheses `(path | filter)`. Without parentheses, the pipe is interpreted as a filter on the entire expression.
:::

### Null Handling

String filters (`lower`, `upper`, `trim`, `ucfirst`, `ucwords`) pass `null` through unchanged:

<!-- skip-test: Syntax example only -->
```php
// If user.status is null:
'{{ (user.status | lower) == "active" ? 1 : 0 }}'  // → 0 (null != "active")
'{{ (user.status | lower) == null ? 1 : 0 }}'       // → 1 (null == null)
```

For more details and examples, see [DataMapper → Conditional Expressions](/data-helpers/main-classes/data-mapper/#conditional-expressions-transformations).

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

### Type Casting in Templates

```php
$sources = [
    'product' => [
        'id' => '2075436601850',        // String from API
        'price' => '19.99',              // String from API
        'stock' => '42',                 // String from API
        'active' => '1',                 // String from API
        'tags' => 'electronics',         // Single value
        'metadata' => ['color' => 'red'], // Array
    ],
];

$template = [
    'product' => [
        'id' => '{{ product.id | int }}',              // Cast to integer
        'price' => '{{ product.price | decimal:2 }}',  // Format as decimal
        'stock' => '{{ product.stock | integer }}',    // Cast to integer
        'active' => '{{ product.active | bool }}',     // Cast to boolean
        'tags' => '{{ product.tags | array }}',        // Wrap in array
        'metadata' => '{{ product.metadata | json }}', // Encode to JSON
    ],
];

$result = DataMapper::source($sources)
    ->template($template)
    ->map()
    ->getTarget();

// Result:
// [
//     'product' => [
//         'id' => 2075436601850,        // int
//         'price' => '19.99',           // string (formatted)
//         'stock' => 42,                // int
//         'active' => true,             // bool
//         'tags' => ['electronics'],    // array
//         'metadata' => '{"color":"red"}', // JSON string
//     ]
// ]
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

- [DataMapper](/data-helpers/main-classes/data-mapper/) - DataMapper guide
- [Callback Filters](/data-helpers/advanced/callback-filters/) - Custom callbacks
- [Query Builder](/data-helpers/advanced/query-builder/) - Query builder

