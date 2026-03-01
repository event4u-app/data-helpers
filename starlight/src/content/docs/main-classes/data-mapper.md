---
title: DataMapper
description: Transform data structures with the powerful Fluent API
---

DataMapper provides a modern, fluent API for transforming data between different structures. It supports template-based mapping, queries with SQL-like operators, property-specific filters and much more.

## Quick Example

```php
use event4u\DataHelpers\DataMapper;

$source = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'orders' => [
        ['id' => 1, 'total' => 100, 'status' => 'shipped'],
        ['id' => 2, 'total' => 200, 'status' => 'pending'],
        ['id' => 3, 'total' => 150, 'status' => 'shipped'],
    ],
];

// Approach 1: Fluent API with query builder
$result = DataMapper::source($source)
    ->query('orders.*')
        ->where('status', '=', 'shipped')
        ->orderBy('total', 'DESC')
        ->end()
    ->template([
        'customer_name' => '{{ user.name }}',
        'customer_email' => '{{ user.email }}',
        'shipped_orders' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();

// Approach 2: Template-based with WHERE/ORDER BY operators (recommended)
$template = [
    'customer_name' => '{{ user.name }}',
    'customer_email' => '{{ user.email }}',
    'shipped_orders' => [
        'WHERE' => [
            '{{ orders.*.status }}' => 'shipped',
        ],
        'ORDER BY' => [
            '{{ orders.*.total }}' => 'DESC',
        ],
        '*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
        ],
    ],
];

$result = DataMapper::source($source)
    ->template($template)
    ->map()
    ->getTarget();

// Both approaches produce the same result:
// [
//     'customer_name' => 'John Doe',
//     'customer_email' => 'john@example.com',
//     'shipped_orders' => [
//         ['id' => 3, 'total' => 150],
//         ['id' => 1, 'total' => 100],
//     ],
// ]
```

### Why Use Template-Based Approach?

The template-based approach (Approach 2) has a significant advantage: **templates can be stored in a database and created with a drag-and-drop editor**, enabling **no-code data mapping**:

```php
// Store templates in database
$source = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ],
    'orders' => [
        ['id' => 1, 'total' => 100, 'status' => 'shipped'],
        ['id' => 2, 'total' => 200, 'status' => 'pending'],
        ['id' => 3, 'total' => 150, 'status' => 'shipped'],
    ],
];

// Load template from database (created with drag-and-drop editor)
$template = Mappings::find(3)->template;

$result = DataMapper::source($source)
    ->template($template)
    ->map()
    ->getTarget();
```

**This makes it possible to map import files, API responses, etc. without any programming.**

Use cases:
- **Import Wizards** - Let users map CSV/Excel columns to your data structure
- **API Integration** - Store API response mappings in database
- **Multi-Tenant Systems** - Each tenant can have custom mappings
- **Dynamic ETL** - Build data transformation pipelines without code
- **Form Builders** - Map form submissions to different data structures

## Fluent API Overview

The DataMapper uses a fluent, chainable API.
You can start with `DataMapper::from($source)` (alias: `DataMapper::source($source)`).

<!-- skip-test: API overview with placeholders -->
```php
DataMapper::from($source)            // Start with source data (alias: DataMapper::source())
    ->target($target)               // Optional: Set target object/array
    ->template($template)           // Define mapping template
    ->query($path)                  // Start query builder
        ->where($field, $op, $val)  // Add WHERE condition
        ->orderBy($field, $dir)     // Add ORDER BY
        ->limit($n)                 // Add LIMIT
        ->end()                     // End query builder
    ->property($name)               // Access property API
        ->setFilter($filter)        // Set property filter
        ->end()                     // End property API
    ->pipeline($filters)            // Set global filters
    ->skipNull()                    // Skip null values
    ->map()                         // Execute mapping
    ->getTarget();                  // Get result
```

## Basic Usage

### Simple Template Mapping

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com', 'profile' => ['age' => 30]]];
$result = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'age' => '{{ user.profile.age }}',
    ])
    ->map()
    ->getTarget();
```

### Template Syntax

Templates use `{{ }}` for dynamic values:

- **Dynamic values:** `'{{ user.name }}'` - Fetches value from source
- **Static values:** `'admin'` - Used as literal string (no `{{ }}`)
- **Dot-notation:** `'{{ user.profile.address.street }}'` - Nested access
- **Wildcards:** `'{{ users.*.email }}'` - Array operations
- **Conditional expressions:** `'{{ condition ? trueValue : falseValue }}'` - Transform values based on conditions
- **Null coalescing:** `'{{ user.email ?? "default@example.com" }}'` - Default value for null
- **Elvis operator:** `'{{ user.name ?: "Anonymous" }}'` - Default value for falsy values

### Conditional Expressions (Transformations)

Conditional expressions allow you to transform values based on conditions. DataMapper supports three types of conditional operators:

#### 1. Ternary Operator (`? :`)

Full conditional expression with explicit condition:

<!-- skip-test: Syntax example only -->
```php
'{{ condition ? trueValue : falseValue }}'
```

**Supported Operators:**
- **Equality:** `==`, `!=`
- **Comparison:** `>`, `<`, `>=`, `<=`

#### 2. Null Coalescing Operator (`??`)

Returns the left value if it's **not null**, otherwise returns the right value:

<!-- skip-test: Syntax example only -->
```php
'{{ user.email ?? "default@example.com" }}'
```

**Behavior:**
- ✅ Triggers only on `null`
- ❌ Does NOT trigger on `false`, `0`, `""`, or `[]`

#### 3. Elvis Operator (`?:`)

Returns the left value if it's **truthy**, otherwise returns the right value:

<!-- skip-test: Syntax example only -->
```php
'{{ user.name ?: "Anonymous" }}'
```

**Behavior:**
- ✅ Triggers on any falsy value: `null`, `false`, `0`, `""`, `[]`
- ❌ Does NOT trigger on truthy values like `1`, `"text"`, `[1, 2]`

#### Comparison Table

| Operator | Triggers on | Example | When right value? |
|----------|-------------|---------|-------------------|
| `??` | `null` only | `{{ email ?? "default" }}` | Only when `null` |
| `?:` | Any falsy value | `{{ name ?: "Anonymous" }}` | When `null`, `false`, `0`, `""`, `[]` |
| `? :` | Custom condition | `{{ age > 18 ? "adult" : "minor" }}` | When condition is false |

#### Examples

**Ternary Operator - Transform status to 0 or 1:**

```php
$source = [
    'users' => [
        ['name' => 'Alice', 'status' => 'active'],
        ['name' => 'Bob', 'status' => 'inactive'],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name }}',
            'active' => '{{ users.*.status == "active" ? 1 : 0 }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'users' => [
//         ['name' => 'Alice', 'active' => 1],
//         ['name' => 'Bob', 'active' => 0],
//     ]
// ]
```

**Age category (adult/minor):**

```php
$source = [
    'people' => [
        ['name' => 'Alice', 'age' => 25],
        ['name' => 'Bob', 'age' => 17],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'people.*' => [
            'name' => '{{ people.*.name }}',
            'category' => '{{ people.*.age >= 18 ? "adult" : "minor" }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'people' => [
//         ['name' => 'Alice', 'category' => 'adult'],
//         ['name' => 'Bob', 'category' => 'minor'],
//     ]
// ]
```

**Price category with boolean flags:**

```php
$source = [
    'products' => [
        ['name' => 'Laptop', 'price' => 1200],
        ['name' => 'Mouse', 'price' => 25],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'products.*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
            'expensive' => '{{ products.*.price > 100 ? true : false }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'products' => [
//         ['name' => 'Laptop', 'price' => 1200, 'expensive' => true],
//         ['name' => 'Mouse', 'price' => 25, 'expensive' => false],
//     ]
// ]
```

**Multiple conditions in same template:**

```php
$source = [
    'orders' => [
        ['id' => 1, 'total' => 150, 'status' => 'completed'],
        ['id' => 2, 'total' => 50, 'status' => 'pending'],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'orders.*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
            'is_completed' => '{{ orders.*.status == "completed" ? true : false }}',
            'is_large_order' => '{{ orders.*.total >= 100 ? true : false }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'orders' => [
//         ['id' => 1, 'total' => 150, 'is_completed' => true, 'is_large_order' => true],
//         ['id' => 2, 'total' => 50, 'is_completed' => false, 'is_large_order' => false],
//     ]
// ]
```

#### Value Types

Conditional expressions support all common value types:

- **Integers:** `{{ quantity < 10 ? 1 : 0 }}`
- **Floats:** `{{ price >= 99.99 ? 1 : 0 }}`
- **Strings:** `{{ status == "active" ? "Yes" : "No" }}`
- **Booleans:** `{{ age >= 18 ? true : false }}`
- **Null:** `{{ email != null ? 1 : 0 }}`

#### String Literals

You can use both single and double quotes for string literals:

```php
// Double quotes
'status_text' => '{{ user.status == "active" ? "Yes" : "No" }}'

// Single quotes
'status_text' => "{{ user.status == 'active' ? 'Yes' : 'No' }}"
```

#### Nested Properties

Conditional expressions work with nested property access:

```php
$source = [
    'user' => [
        'profile' => [
            'age' => 25,
        ],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'adult' => '{{ user.profile.age >= 18 ? 1 : 0 }}',
    ])
    ->map()
    ->getTarget();

// Result: ['adult' => 1]
```

**Null Coalescing - Default email addresses:**

```php
$source = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => null],
        ['name' => 'Charlie'], // email missing
    ],
];

$result = DataMapper::source($source)
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email ?? "no-email@example.com" }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'users' => [
//         ['name' => 'Alice', 'email' => 'alice@example.com'],
//         ['name' => 'Bob', 'email' => 'no-email@example.com'],
//         ['name' => 'Charlie', 'email' => 'no-email@example.com'],
//     ]
// ]
```

**Elvis Operator - Anonymous names:**

```php
$source = [
    'users' => [
        ['name' => 'Alice'],
        ['name' => ''], // empty string
        ['name' => null], // null
    ],
];

$result = DataMapper::source($source)
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name ?: "Anonymous" }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'users' => [
//         ['name' => 'Alice'],
//         ['name' => 'Anonymous'],
//         ['name' => 'Anonymous'],
//     ]
// ]
```

**Difference between ?? and ?::**

```php
$source = [
    'user' => [
        'email' => '',      // empty string
        'quantity' => 0,    // zero
        'active' => false,  // false
    ],
];

$result = DataMapper::source($source)
    ->template([
        // ?? only triggers on null (NOT on empty string, 0, or false)
        'email_coalescing' => '{{ user.email ?? "default@example.com" }}',
        'quantity_coalescing' => '{{ user.quantity ?? 10 }}',
        'active_coalescing' => '{{ user.active ?? true }}',

        // ?: triggers on ANY falsy value (empty string, 0, false, null)
        'email_elvis' => '{{ user.email ?: "default@example.com" }}',
        'quantity_elvis' => '{{ user.quantity ?: 10 }}',
        'active_elvis' => '{{ user.active ?: true }}',
    ])
    ->skipNull(false)
    ->map()
    ->getTarget();

// Result:
// [
//     'email_coalescing' => '',              // NOT replaced (not null)
//     'quantity_coalescing' => 0,            // NOT replaced (not null)
//     'active_coalescing' => false,          // NOT replaced (not null)
//     'email_elvis' => 'default@example.com', // Replaced (empty string is falsy)
//     'quantity_elvis' => 10,                // Replaced (0 is falsy)
//     'active_elvis' => true,                // Replaced (false is falsy)
// ]
```

#### Working with Wildcards

Conditional expressions integrate seamlessly with wildcard operators:

```php
$source = [
    'items' => [
        ['name' => 'Item A', 'quantity' => 10],
        ['name' => 'Item B', 'quantity' => 5],
        ['name' => 'Item C', 'quantity' => 15],
    ],
];

$result = DataMapper::source($source)
    ->template([
        'items.*' => [
            'name' => '{{ items.*.name }}',
            'quantity' => '{{ items.*.quantity }}',
            'low_stock' => '{{ items.*.quantity < 10 ? 1 : 0 }}',
            'high_stock' => '{{ items.*.quantity >= 15 ? 1 : 0 }}',
        ],
    ])
    ->map()
    ->getTarget();

// Result:
// [
//     'items' => [
//         ['name' => 'Item A', 'quantity' => 10, 'low_stock' => 0, 'high_stock' => 0],
//         ['name' => 'Item B', 'quantity' => 5, 'low_stock' => 1, 'high_stock' => 0],
//         ['name' => 'Item C', 'quantity' => 15, 'low_stock' => 0, 'high_stock' => 1],
//     ]
// ]
```

:::tip[Use Cases]
Conditional expressions are perfect for:
- **Ternary (`? :`)** - Data type conversion, categorization, business logic
- **Null Coalescing (`??`)** - Default values for optional fields, API responses with missing data
- **Elvis (`?:`)** - Default values for empty strings, anonymization, fallback for zero values
- **Common scenarios:**
  - Convert strings to integers/booleans
  - Classify values into categories (adult/minor, expensive/cheap)
  - Apply rules during mapping (low stock alerts, premium users)
  - Create boolean flags based on conditions
  - Transform status codes to readable values
:::

:::note[Performance]
Conditional expressions are evaluated during the mapping process and have minimal performance overhead. They are optimized for use with wildcards and large datasets.
:::

💡 **See the complete examples:**
- Run `php examples/datamapper-conditional-expressions.php` for ternary operator examples
- Run `php examples/datamapper-null-coalescing-elvis.php` for `??` and `?:` examples

### Loading Data from Files

DataMapper can load data directly from JSON and XML files using `sourceFile()`:

<!-- skip-test: Do net test file import here -->
```php
// Load from JSON file
$result = DataMapper::sourceFile('/path/to/data.json')
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ])
    ->map()
    ->getTarget();

// Load from XML file
$result = DataMapper::sourceFile('/path/to/data.xml')
    ->template([
        'name' => '{{ company.name }}',
        'email' => '{{ company.email }}',
    ])
    ->map()
    ->getTarget();
```

#### ⚠️ Important: XML Root Element Preservation

When loading XML files, **the root element name is always preserved** and must be included in your mapping paths:

```php
// XML file content:
// <?xml version="1.0"?>
// <company>
//     <name>TechCorp</name>
//     <email>info@techcorp.com</email>
// </company>

// ✅ Correct: Include root element in path
$mapping = [
    'company_name' => '{{ company.name }}',
    'company_email' => '{{ company.email }}',
];

// ❌ Wrong: Missing root element (will return null)
$mapping = [
    'company_name' => '{{ name }}',
    'company_email' => '{{ email }}',
];
```

**Different root elements require different paths:**

```php
// For <VitaCost>...</VitaCost>
'number' => '{{ VitaCost.ConstructionSite.nr_lv }}'

// For <Datafields>...</Datafields>
'salutation' => '{{ Datafields.contact_persons.contact_person.salutation }}'

// For <company>...</company>
'name' => '{{ company.name }}'
```

**Example with nested XML arrays:**

```php
// XML: <company><departments><department>...</department></departments></company>
$mapping = [
    'company_name' => '{{ company.name }}',
    'departments' => [
        '*' => [
            'name' => '{{ company.departments.department.*.name }}',
            'code' => '{{ company.departments.department.*.code }}',
        ],
    ],
];
```

#### XML Files with Multiple Root Elements

In some cases, you may need to work with XML files that have multiple root elements (technically invalid XML, but sometimes necessary):

```php
// XML file with multiple roots:
// <LVDATA><LV>...</LV></LVDATA>
// <POSDATA><POS>...</POS></POSDATA>

// Both root elements are preserved and accessible
$mapping = [
    'lv_id' => '{{ LVDATA.LV.ID_LV }}',
    'lv_number' => '{{ LVDATA.LV.NR_LV }}',
    'positions' => [
        '*' => [
            'position_id' => '{{ POSDATA.POS.*.ID_POSITION }}',
            'lv_id' => '{{ POSDATA.POS.*.ID_LV }}',
        ],
    ],
];

$result = DataMapper::sourceFile('/path/to/multi-root.xml')
    ->template($mapping)
    ->map()
    ->getTarget();
```

The FileLoader automatically detects and handles multiple root elements by wrapping them in a temporary container during parsing.

💡 **See the complete example:** Run `php examples/data-mapper/xml-file-mapping.php` for a comprehensive demonstration of XML file loading with different root elements.

### Mapping to Objects

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public string $name;
    public string $email;
}

$result = DataMapper::source($source)
    ->target(UserDto::class)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ])
    ->map()
    ->getTarget(); // Returns UserDto instance
```

### Working with Readonly Properties

PHP 8.1+ introduced readonly properties that can only be initialized once. The DataMapper provides the `modifyReadOnly()` method to handle these properties intelligently.

#### Default Behavior (modifyReadOnly disabled)

By default, readonly properties that are already initialized will be skipped:

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

// Create instance with initialized readonly properties
$dto = new UserDto(999, 'Original');

$source = ['id' => 123, 'name' => 'John'];

// Map to existing object - readonly properties are skipped
$result = DataMapper::source($source)
    ->target($dto)
    ->template([
        'id' => '{{ id }}',
        'name' => '{{ name }}',
    ])
    ->map()
    ->getTarget();

// Result: id=999, name='Original' (unchanged)
```

#### Enabling Readonly Modification

When `modifyReadOnly(true)` is enabled, the mapper will create a new instance to allow setting readonly properties:

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

$dto = new UserDto(999, 'Original');
$source = ['id' => 123, 'name' => 'John'];

// Enable readonly modification
$result = DataMapper::source($source)
    ->target($dto)
    ->modifyReadOnly(true)  // Creates new instance
    ->template([
        'id' => '{{ id }}',
        'name' => '{{ name }}',
    ])
    ->map()
    ->getTarget();

// Result: id=123, name='John' (new instance created)
// Original $dto remains unchanged: id=999, name='Original'
```

#### Mapping to Class Names

When the target is a class name (string) instead of an object instance, readonly properties can always be set:

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

$source = ['id' => 123, 'name' => 'John'];

// Pass class name as target
$result = DataMapper::source($source)
    ->target(UserDto::class)  // Class name, not instance
    ->modifyReadOnly(true)
    ->template([
        'id' => '{{ id }}',
        'name' => '{{ name }}',
    ])
    ->map()
    ->getTarget();

// Result: id=123, name='John' (new instance created via constructor)
```

#### Performance Optimization

The mapper only creates a new instance when necessary:

- **Class name target**: Uses constructor when possible (best performance)
- **Object target + no readonly properties to modify**: Reuses existing object (preserves reference)
- **Object target + readonly properties to modify**: Creates new instance via reflection

<!-- skip-test: declares UserDto class -->
```php
class UserDto
{
    public int $id = 0;      // Mutable property
    public string $name = ''; // Mutable property
}

$dto = new UserDto();
$source = ['id' => 123, 'name' => 'John'];

// No readonly properties - reuses existing object
$result = DataMapper::source($source)
    ->target($dto)
    ->modifyReadOnly(false)
    ->template([
        'id' => '{{ id }}',
        'name' => '{{ name }}',
    ])
    ->map()
    ->getTarget();

// $result === $dto (same object reference)
```

:::tip[Best Practice]
- Use `modifyReadOnly(false)` (default) for safe, predictable behavior
- Use `modifyReadOnly(true)` only when you explicitly need to modify readonly properties
- Prefer passing class names as targets when working with readonly properties
:::

### Nested Structures

```php
$source = [
    'user' => ['name' => 'John', 'email' => 'john@example.com', 'phone' => '555-1234'],
    'orders' => [['id' => 1, 'total' => 100], ['id' => 2, 'total' => 200]],
];
$result = DataMapper::source($source)
    ->template([
        'customer' => [
            'name' => '{{ user.name }}',
            'contact' => [
                'email' => '{{ user.email }}',
                'phone' => '{{ user.phone }}',
            ],
        ],
        'orders' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

## Query Builder

The query builder provides SQL-like operators for filtering and transforming data during mapping.

:::tip[Template-Based Alternative]
Instead of using the fluent query API, you can use **WHERE/ORDER BY operators directly in templates**. This approach is recommended when templates need to be stored in a database or created with a visual editor. See the [Quick Example](#quick-example) above for details.
:::

### Basic Queries

```php
// Fluent API approach
$result = DataMapper::source($source)
    ->query('orders.*')
        ->where('total', '>', 100)
        ->orderBy('total', 'DESC')
        ->limit(5)
        ->end()
    ->template([
        'items' => [
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();

// Template-based approach (same result)
$result = DataMapper::source($source)
    ->template([
        'items' => [
            'WHERE' => [
                '{{ orders.*.total }}' => ['>', 100],
            ],
            'ORDER BY' => [
                '{{ orders.*.total }}' => 'DESC',
            ],
            'LIMIT' => 5,
            '*' => [
                'id' => '{{ orders.*.id }}',
                'total' => '{{ orders.*.total }}',
            ],
        ],
    ])
    ->map()
    ->getTarget();
```

### WHERE Conditions

```php
// Simple comparison
->where('status', '=', 'active')
->where('price', '>', 100)
->where('stock', '<=', 10)

// Multiple conditions (AND logic)
->where('status', '=', 'active')
->where('price', '>', 100)

// BETWEEN
->where('price', 'BETWEEN', [50, 150])

// IN
->where('status', 'IN', ['active', 'pending'])

// LIKE (pattern matching)
->where('name', 'LIKE', 'John%')

// NULL checks
->where('deleted_at', 'IS NULL')
->where('email', 'IS NOT NULL')
```

### ORDER BY

```php
// Single field
->orderBy('price', 'DESC')

// Multiple fields
->orderBy('category', 'ASC')
->orderBy('price', 'DESC')
```

### LIMIT and OFFSET

```php
// Limit results
->limit(10)

// Skip items
->offset(20)

// Pagination
->offset(20)
->limit(10)
```

### DISTINCT

```php
// Remove duplicates
->distinct('email')
```

### GROUP BY

```php
// Group and aggregate
->groupBy('category', [
    'total' => 'SUM(price)',
    'count' => 'COUNT(*)',
    'avg_price' => 'AVG(price)',
])
```

## Pipeline Filters

Apply filters to all mapped values globally.

### Global Filters

<!-- skip-test: Import conflict with other examples -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

$result = DataMapper::source($source)
    ->pipeline([
        new TrimStrings(),
        new UppercaseStrings(),
    ])
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ])
    ->map()
    ->getTarget();

// All string values are trimmed and uppercased
```

### Adding Filters

<!-- skip-test: Import conflict with other examples -->
```php
$mapper = DataMapper::source($source)
    ->template($template);

// Add single filter
$mapper->addPipelineFilter(new TrimStrings());

// Add multiple filters
$mapper->pipeline([
    new TrimStrings(),
    new UppercaseStrings(),
]);
```

### Built-in Filters

Data Helpers includes 40+ built-in filters:

- **String Filters:** TrimStrings, UppercaseStrings, LowercaseStrings, etc.
- **Number Filters:** RoundNumbers, FormatCurrency, etc.
- **Date Filters:** FormatDate, ParseDate, etc.
- **Array Filters:** FlattenArray, UniqueValues, etc.
- **Validation Filters:** ValidateEmail, ValidateUrl, etc.

See [Filters Documentation](/data-helpers/advanced-features/filters/) for complete list.

## Property-Specific Filters

Apply filters to specific properties only.

### Using setFilter()

<!-- skip-test: Import conflict with other examples -->
```php
$result = DataMapper::source($source)
    ->setFilter('name', new TrimStrings(), new UppercaseStrings())
    ->setFilter('email', new TrimStrings(), new LowercaseStrings())
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'bio' => '{{ user.bio }}',
    ])
    ->map()
    ->getTarget();

// Only 'name' and 'email' are filtered, 'bio' is not
```

### Using Property API

<!-- skip-test: Import conflict with other examples -->
```php
$result = DataMapper::source($source)
    ->property('name')
        ->setFilter(new TrimStrings(), new UppercaseStrings())
        ->end()
    ->property('email')
        ->setFilter(new TrimStrings(), new LowercaseStrings())
        ->end()
    ->template($template)
    ->map()
    ->getTarget();
```

### Nested Properties

<!-- skip-test: Code snippet example -->
```php
// Works with dot-notation
->setFilter('user.profile.bio', new TrimStrings())

// Works with wildcards
->setFilter('items.*.name', new TrimStrings())
```

## Property API

The Property API provides focused access to individual properties.

### Get Property Target

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
$mapper = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
    ]);

// Get mapping target for property
$target = $mapper->property('name')->getTarget();
// $target = '{{ user.name }}'
```

### Get Property Filters

<!-- skip-test: requires mapper instance -->
```php
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;

$mapper->setFilter('name', new TrimStrings());

$filters = $mapper->property('name')->getFilter();
// $filters = [TrimStrings]
```

### Get Mapped Value

```php
// Execute mapping and get value for specific property
$value = $mapper->property('name')->getMappedValue();
// $value = 'John Doe' (after applying filters)
```

### Reset Property Filters

<!-- skip-test: Import conflict with other examples -->
```php
$mapper->property('name')
    ->setFilter(new TrimStrings())
    ->resetFilter()  // Remove all filters
    ->setFilter(new UppercaseStrings())  // Set new filter
    ->end();
```

## Discriminator (Polymorphic Mapping)

Automatically select target class based on a discriminator field (Liskov Substitution Principle).

### Basic Usage

<!-- skip-test: to abstract. results in error. -->
```php
abstract class Animal
{
    public string $name;
    public int $age;
}

class Dog extends Animal
{
    public string $breed;
}

class Cat extends Animal
{
    public int $lives;
}

$source = [
    'type' => 'dog',
    'name' => 'Rex',
    'age' => 5,
    'breed' => 'Golden Retriever',
];

$result = DataMapper::source($source)
    ->target(Animal::class)
    ->discriminator('type', [
        'dog' => Dog::class,
        'cat' => Cat::class,
    ])
    ->template([
        'name' => '{{ name }}',
        'age' => '{{ age }}',
        'breed' => '{{ breed }}',
    ])
    ->map()
    ->getTarget();

// Returns Dog instance (because type='dog')
```

### Nested Discriminator

```php
// Discriminator field can be nested
->discriminator('meta.classification.type', [
    'premium' => PremiumUser::class,
    'basic' => BasicUser::class,
])
```

### Fallback Behavior

```php
// If discriminator value not found, falls back to original target
$result = DataMapper::source(['type' => 'unknown'])
    ->target(Animal::class)
    ->discriminator('type', [
        'dog' => Dog::class,
        'cat' => Cat::class,
    ])
    ->template($template)
    ->map()
    ->getTarget();

// Returns Animal instance (fallback)
```

## Copy and Extend

Create independent copies of mapper configurations.

### Copy Configuration

```php
$baseMapper = DataMapper::source($source)
    ->target(User::class)
    ->template([
        'name' => '{{ name }}',
    ]);

// Create independent copy
$extendedMapper = $baseMapper->copy()
    ->extendTemplate([
        'email' => '{{ email }}',
    ])
    ->addPipelineFilter(new TrimStrings());

// $baseMapper is unchanged
// $extendedMapper has extended config
```

### Extend Template

```php
$source = ['user' => ['name' => 'John', 'email' => 'john@example.com', 'phone' => '555-1234']];
$mapper = DataMapper::source($source)
    ->template([
        'name' => '{{ user.name }}',
    ]);

// Extend with additional fields
$mapper->extendTemplate([
    'email' => '{{ user.email }}',
    'phone' => '{{ user.phone }}',
]);

// Template now has all three fields
```

## Reset and Delete

Manage template operators dynamically.

### Reset to Original

```php
$source = ['products' => [['id' => 1, 'status' => 'active', 'price' => 100], ['id' => 2, 'status' => 'inactive', 'price' => 50]]];
$mapper = DataMapper::source($source)
    ->template([
        'items' => [
            'WHERE' => ['{{ products.*.status }}' => 'active'],
            'ORDER BY' => ['{{ products.*.price }}' => 'DESC'],
            '*' => ['id' => '{{ products.*.id }}'],
        ],
    ]);

// Modify with query
$mapper->query('products.*')
    ->where('price', '>', 75)
    ->orderBy('price', 'ASC')
    ->end();

// Reset WHERE to original template value
$mapper->reset()->where();

// Reset entire template
$mapper->reset()->all();
```

### Delete Operators

```php
// Delete specific operator
$mapper->delete()->where();

// Delete all operators
$mapper->delete()->all();
```

### Chainable

```php
// Chain multiple operations
$mapper->reset()->where()->orderBy();
$mapper->delete()->limit()->offset();
```

## Performance

DataMapper is optimized for performance:

- **3.7x faster** than Symfony Serializer for Dto mapping
- **Zero reflection overhead** for template-based mapping
- **Efficient caching** for path resolution and reflection
- **Minimal overhead** (7.1%) for Fluent API wrapper

See [Performance Benchmarks](/data-helpers/performance/benchmarks/) for detailed comparison.

## Code Examples

The following working examples demonstrate DataMapper in action:

- [**Simple Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/simple-mapping.php) - Basic template-based mapping
- [**Template-Based Queries**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/template-based-queries.php) - WHERE/ORDER BY in templates (recommended for database-stored templates)
- [**Conditional Expressions**](https://github.com/event4u-app/data-helpers/blob/main/examples/datamapper-conditional-expressions.php) - Transform values with ternary operators (`? :`)
- [**Null Coalescing & Elvis**](https://github.com/event4u-app/data-helpers/blob/main/examples/datamapper-null-coalescing-elvis.php) - Default values with `??` and `?:` operators
- [**With Hooks**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/with-hooks.php) - Using hooks for custom logic
- [**Pipeline**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/pipeline.php) - Filter pipelines and transformations
- [**Mapped Data Model**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/mapped-data-model.php) - Using MappedDataModel class
- [**Template Expressions**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/template-expressions.php) - Advanced template syntax
- [**Reverse Mapping**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/reverse-mapping.php) - Bidirectional mapping
- [**Dto Integration**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-mapper/dto-integration.php) - Integration with SimpleDto

All examples are fully tested and can be run directly:

```bash
php examples/main-classes/data-mapper/simple-mapping.php
php examples/main-classes/data-mapper/template-based-queries.php
php examples/datamapper-conditional-expressions.php
php examples/datamapper-null-coalescing-elvis.php
php examples/main-classes/data-mapper/with-hooks.php
```

## Related Tests

The functionality is thoroughly tested. Key test files:

- [DataMapperTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/DataMapperTest.php) - Core functionality tests
- [ConditionalExpressionsTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/ConditionalExpressionsTest.php) - Ternary operator tests (`? :`)
- [NullCoalescingAndElvisTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/NullCoalescingAndElvisTest.php) - Null coalescing (`??`) and Elvis (`?:`) tests
- [DataMapperHooksTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/DataMapperHooksTest.php) - Hook system tests
- [DataMapperPipelineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/Pipeline/DataMapperPipelineTest.php) - Pipeline tests
- [MapperQueryTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MapperQueryTest.php) - Query integration tests
- [MultiSourceFluentTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MultiSourceFluentTest.php) - Multi-source mapping tests
- [MultiTargetMappingTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataMapper/MultiTargetMappingTest.php) - Multi-target mapping tests
- [DataMapperIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Integration/DataMapperIntegrationTest.php) - End-to-end scenarios

Run the tests:

```bash
# Run all DataMapper tests
task test:unit -- --filter=DataMapper

# Run specific test file
vendor/bin/pest tests/Unit/DataMapper/DataMapperTest.php
```
## See Also

- [DataAccessor](/data-helpers/main-classes/data-accessor/) - Read nested data
- [DataMutator](/data-helpers/main-classes/data-mutator/) - Modify nested data
- [DataFilter](/data-helpers/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard operators
- [Examples](/data-helpers/examples/) - 90+ code examples
