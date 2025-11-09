---
title: DataAccessor
description: Read values from nested data structures using dot-notation paths with wildcard support
---

DataAccessor provides a uniform way to read values from nested data structures including arrays, objects, Laravel Collections and Eloquent Models. It supports dot-notation paths, numeric indices and powerful wildcard operations.

## Quick Example

```php
use event4u\DataHelpers\DataAccessor;

$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 25],
        ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 35],
    ],
];

$accessor = DataAccessor::make($data);

// Simple path
$name = $accessor->get('users.0.name');
// $name = 'Alice'

// Wildcard - extract all emails
$emails = $accessor->get('users.*.email');
// $emails = ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com', 'users.2.email' => 'charlie@example.com']

// Default value
$country = $accessor->get('users.0.country', 'Unknown');
// $country = 'Unknown'
```

## Introduction

DataAccessor works with multiple data types:

- **Arrays** - Nested arrays with any depth
- **Objects** - Plain PHP objects with public properties
- **Dtos** - Data Transfer Objects
- **Laravel Collections** - `Illuminate\Support\Collection`
- **Eloquent Models** - Including relationships
- **Arrayable** - Any object implementing `Arrayable`
- **JsonSerializable** - Any object implementing `JsonSerializable`
- **JSON strings** - Automatically parsed
- **XML strings** - Automatically parsed

## Basic Usage

### Creating an Accessor

```php
use event4u\DataHelpers\DataAccessor;

// From array
$array = ['user' => ['name' => 'Alice']];
$accessor = DataAccessor::make($array);

// From object
$object = (object)['user' => (object)['name' => 'Bob']];
$accessor = DataAccessor::make($object);

// From JSON string
$accessor = DataAccessor::make('{"user":{"name":"Charlie"}}');

// From XML string
$accessor = DataAccessor::make('<user><name>Alice</name></user>');
```

### Reading Values

```php
$data = [
    'user' => [
        'profile' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
    ],
];

$accessor = DataAccessor::make($data);

// Dot-notation path
$name = $accessor->get('user.profile.name');
// $name = 'John Doe'

// Non-existent path returns null
$phone = $accessor->get('user.profile.phone');
// $phone = null
```

### Default Values

```php
$data = [
    'user' => [
        'profile' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
    ],
];

$accessor = DataAccessor::make($data);

// Provide default value as second parameter
$phone = $accessor->get('user.profile.phone', 'N/A');
// $phone = 'N/A'

$name = $accessor->get('user.profile.name', 'Anonymous');
// $name = 'John Doe'
```

## Type-Safe Getters

DataAccessor provides strict type-safe getter methods that automatically convert values to the expected type or throw a `TypeMismatchException` if conversion fails.

### Single Value Getters

These methods return a single value with strict type conversion. They return `null` if the path doesn't exist or the value is `null`.

```php
$data = [
    'user' => [
        'name' => 'John Doe',
        'age' => '30',  // string
        'score' => '95.5',  // string
        'active' => 1,  // int
        'tags' => ['php', 'laravel'],
    ],
];

$accessor = DataAccessor::make($data);

// getString() - converts to string or returns null
$name = $accessor->getString('user.name');  // 'John Doe'
$age = $accessor->getString('user.age');    // '30'
$missing = $accessor->getString('user.phone');  // null
$withDefault = $accessor->getString('user.phone', 'N/A');  // 'N/A'

// getInt() - converts to int or returns null
$age = $accessor->getInt('user.age');  // 30 (string → int)
$active = $accessor->getInt('user.active');  // 1
$missing = $accessor->getInt('user.salary');  // null
$withDefault = $accessor->getInt('user.salary', 0);  // 0

// getFloat() - converts to float or returns null
$score = $accessor->getFloat('user.score');  // 95.5 (string → float)
$age = $accessor->getFloat('user.age');  // 30.0 (string → float)

// getBool() - converts to bool or returns null
$active = $accessor->getBool('user.active');  // true (1 → true)
$inactive = $accessor->getBool('user.inactive');  // null

// getArray() - returns array or null
$tags = $accessor->getArray('user.tags');  // ['php', 'laravel']
$missing = $accessor->getArray('user.categories');  // null
```

### Type Conversion Rules

The type-safe getters follow these conversion rules:

**getString():**
- Converts numbers and booleans to strings
- Throws `TypeMismatchException` for arrays or objects without `__toString()`

**getInt():**
- Converts numeric strings and floats to integers
- Converts booleans to 0/1
- Throws `TypeMismatchException` for non-numeric strings or arrays

**getFloat():**
- Converts numeric strings and integers to floats
- Converts booleans to 0.0/1.0
- Throws `TypeMismatchException` for non-numeric strings or arrays

**getBool():**
- Converts any value to boolean using PHP's truthiness rules
- Throws `TypeMismatchException` for arrays

**getArray():**
- Only accepts arrays
- Throws `TypeMismatchException` for non-array values

### Collection Getters

Collection getters are designed for wildcard paths and return typed arrays. They throw a `TypeMismatchException` if the path doesn't contain a wildcard or if any value cannot be converted to the expected type.

```php
$data = [
    'users' => [
        ['name' => 'Alice', 'age' => '30', 'score' => '95.5', 'active' => true],
        ['name' => 'Bob', 'age' => '25', 'score' => '87.3', 'active' => false],
        ['name' => 'Charlie', 'age' => '35', 'score' => '92.1', 'active' => true],
    ],
];

$accessor = DataAccessor::make($data);

// getIntCollection() - returns array of integers
$ages = $accessor->getIntCollection('users.*.age');
// ['users.0.age' => 30, 'users.1.age' => 25, 'users.2.age' => 35]

// getStringCollection() - returns array of strings
$names = $accessor->getStringCollection('users.*.name');
// ['users.0.name' => 'Alice', 'users.1.name' => 'Bob', 'users.2.name' => 'Charlie']

// getFloatCollection() - returns array of floats
$scores = $accessor->getFloatCollection('users.*.score');
// ['users.0.score' => 95.5, 'users.1.score' => 87.3, 'users.2.score' => 92.1]

// getBoolCollection() - returns array of booleans
$activeFlags = $accessor->getBoolCollection('users.*.active');
// ['users.0.active' => true, 'users.1.active' => false, 'users.2.active' => true]

// getArrayCollection() - returns array of arrays
$data = ['orders' => [
    ['items' => ['A', 'B']],
    ['items' => ['C', 'D']],
]];
$accessor = DataAccessor::make($data);
$items = $accessor->getArrayCollection('orders.*.items');
// ['orders.0.items' => ['A', 'B'], 'orders.1.items' => ['C', 'D']]
```

### Exception Handling

Type-safe getters throw `TypeMismatchException` when:

1. **Single value getters** receive an array (use collection getters instead)
2. **Collection getters** are used without wildcards in the path
3. **Any getter** receives a value that cannot be converted to the expected type

```php
use event4u\DataHelpers\Exceptions\TypeMismatchException;

$data = [
    'user' => ['name' => 'John', 'age' => 'invalid'],
    'users' => [
        ['name' => 'Alice', 'age' => 30],
        ['name' => 'Bob', 'age' => 25],
    ],
];

$accessor = DataAccessor::make($data);

// ❌ Throws TypeMismatchException - array returned for single value getter
try {
    $ages = $accessor->getInt('users.*.age');
} catch (TypeMismatchException $e) {
    // Use collection getter instead
    $ages = $accessor->getIntCollection('users.*.age');
}

// ❌ Throws TypeMismatchException - no wildcard in path
try {
    $ages = $accessor->getIntCollection('users.0.age');
} catch (TypeMismatchException $e) {
    // Handle error
}

// ✅ Use single value getter instead
$age = $accessor->getInt('users.0.age');

// ❌ Throws TypeMismatchException - cannot convert to int
try {
    $age = $accessor->getInt('user.age');  // 'invalid' → int fails
} catch (TypeMismatchException $e) {
    // Handle error
}
```

### When to Use Type-Safe Getters

**Use single value getters when:**
- You need strict type safety
- You want automatic type conversion
- You're accessing a single value (not using wildcards)
- You want to catch type errors early

**Use collection getters when:**
- You're using wildcards to extract multiple values
- You need all values to be of the same type
- You want to ensure type consistency across collections

**Use generic `get()` when:**
- You need maximum flexibility
- You're handling mixed types
- You'll handle type conversion yourself
- You don't need strict type safety

## Wildcards

Wildcards allow you to extract values from multiple items at once.

### Basic Wildcards

```php
$data = [
    'users' => [
        ['email' => 'alice@example.com'],
        ['email' => 'bob@example.com'],
        ['email' => 'charlie@example.com'],
    ],
];

$accessor = DataAccessor::make($data);
$emails = $accessor->get('users.*.email');
// $emails = ['users.0.email' => 'alice@example.com', 'users.1.email' => 'bob@example.com', 'users.2.email' => 'charlie@example.com']
```

### Why Full Path Keys?

The full path keys are intentional and provide:

1. **Stability** - Keys remain consistent across operations
2. **Traceability** - You know exactly where each value came from
3. **Integration** - DataMutator and DataMapper consume this format
4. **Uniqueness** - No key collisions in complex structures

### Null Values in Wildcards

```php
$data = [
    'users' => [
        ['email' => 'alice@example.com'],
        ['email' => null],
        ['email' => 'bob@example.com'],
    ],
];

$accessor = DataAccessor::make($data);
$emails = $accessor->get('users.*.email');
// $emails = ['users.0.email' => 'alice@example.com', 'users.1.email' => null, 'users.2.email' => 'bob@example.com']

// Filter out nulls if needed
$validEmails = array_filter($emails, fn($v) => $v !== null);
// $validEmails = ['users.0.email' => 'alice@example.com', 'users.2.email' => 'bob@example.com']
```


## Deep Wildcards

Multiple wildcards in one path create a flat associative array with full dot-path keys.

### Multiple Wildcards

```php
$data = [
    'users' => [
        [
            'name' => 'Alice',
            'addresses' => [
                'home' => ['city' => 'Berlin'],
                'work' => ['city' => 'Hamburg'],
            ],
        ],
        [
            'name' => 'Bob',
            'addresses' => [
                'home' => ['city' => 'Munich'],
            ],
        ],
    ],
];

$accessor = DataAccessor::make($data);
$cities = $accessor->get('users.*.addresses.*.city');
// $cities = // [
//   'users.0.addresses.home.city' => 'Berlin',
//   'users.0.addresses.work.city' => 'Hamburg',
//   'users.1.addresses.home.city' => 'Munich',
// ]
```

### Three-Level Wildcards

```php
$data = [
    'departments' => [
        [
            'users' => [
                ['posts' => [['title' => 'Post 1'], ['title' => 'Post 2']]],
                ['posts' => [['title' => 'Post 3']]],
            ],
        ],
        [
            'users' => [
                ['posts' => [['title' => 'Post 4']]],
            ],
        ],
    ],
];

$accessor = DataAccessor::make($data);
$titles = $accessor->get('departments.*.users.*.posts.*.title');
// $titles = // [
//   'departments.0.users.0.posts.0.title' => 'Post 1',
//   'departments.0.users.0.posts.1.title' => 'Post 2',
//   'departments.0.users.1.posts.0.title' => 'Post 3',
//   'departments.1.users.0.posts.0.title' => 'Post 4',
// ]
```

## Working with Collections

DataAccessor seamlessly handles Laravel Collections.

### Collection Input

```php
use Illuminate\Support\Collection;

$data = [
    'users' => collect([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]),
];

$accessor = DataAccessor::make($data);
$emails = $accessor->get('users.*.email');
// $emails = // [
//   'users.0.email' => 'alice@example.com',
//   'users.1.email' => 'bob@example.com',
// ]
```

### Nested Collections

```php
$data = [
    'orders' => collect([
        [
            'items' => collect([
                ['sku' => 'A', 'price' => 10],
                ['sku' => 'B', 'price' => 20],
            ]),
        ],
        [
            'items' => collect([
                ['sku' => 'C', 'price' => 30],
            ]),
        ],
    ]),
];

$accessor = DataAccessor::make($data);
$skus = $accessor->get('orders.*.items.*.sku');
// $skus = // [
//   'orders.0.items.0.sku' => 'A',
//   'orders.0.items.1.sku' => 'B',
//   'orders.1.items.0.sku' => 'C',
// ]
```

### Accessing by Index

```php
$data = [
    'users' => collect([
        ['name' => 'Alice'],
        ['name' => 'Bob'],
    ]),
];

$accessor = DataAccessor::make($data);

// Access specific index
$firstUser = $accessor->get('users.0.name');
// $firstUser = 'Alice'

// Wildcard still works
$allNames = $accessor->get('users.*.name');
// $allNames = ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']
```

## Working with Eloquent Models

DataAccessor works with Eloquent Models and their relationships.

### Basic Model Access

<!-- skip-test: Requires Eloquent Model -->
```php
$user = User::find(1);
$accessor = DataAccessor::make($user);

$name = $accessor->get('name');
$email = $accessor->get('email');
```

### Accessing Relationships

<!-- skip-test: Requires Eloquent Model with relationships -->
```php
$user = User::with('posts.comments')->first();
$accessor = DataAccessor::make($user);

// Access relationship
$postTitles = $accessor->get('posts.*.title');

// Deep relationship access
$commentTexts = $accessor->get('posts.*.comments.*.text');
```

### Model Collections

<!-- skip-test: Requires Eloquent Model collection -->
```php
$users = User::with('posts')->get();
$accessor = DataAccessor::make(['users' => $users]);

// Extract all post titles from all users
$allPostTitles = $accessor->get('users.*.posts.*.title');
```


## JSON and XML Input

DataAccessor automatically parses JSON and XML strings.

### JSON Strings

```php
$json = '{"users":[{"name":"Alice","age":30},{"name":"Bob","age":25}]}';
$accessor = DataAccessor::make($json);

$names = $accessor->get('users.*.name');
// $names = ['users.0.name' => 'Alice', 'users.1.name' => 'Bob']

$firstAge = $accessor->get('users.0.age');
// $firstAge = 30
```

### XML Strings

```php
$xml = '<users><user><name>Alice</name></user><user><name>Bob</name></user></users>';
$accessor = DataAccessor::make($xml);

$names = $accessor->get('users.user.*.name');
// Returns parsed XML as array structure
```

## Common Patterns

### Extract All Values from Nested Structure

```php
$data = [
    'departments' => [
        ['users' => [['email' => 'a@x.com'], ['email' => 'b@x.com']]],
        ['users' => [['email' => 'c@x.com']]],
    ],
];

$accessor = DataAccessor::make($data);
$emails = $accessor->get('departments.*.users.*.email');
// $emails = // [
//   'departments.0.users.0.email' => 'a@x.com',
//   'departments.0.users.1.email' => 'b@x.com',
//   'departments.1.users.0.email' => 'c@x.com',
// ]
```

### Safe Access with Default

```php
$config = ['app' => ['settings' => ['theme' => 'dark', 'timeout' => 60]]];
$accessor = DataAccessor::make($config);

// Always provide sensible defaults
$theme = $accessor->get('app.settings.theme', 'default');
$timeout = $accessor->get('app.settings.timeout', 30);
$debug = $accessor->get('app.settings.debug', false);
```

### Combining with Array Functions

```php
$data = ['products' => [
    ['name' => 'Product A', 'price' => 10.50],
    ['name' => 'Product B', 'price' => 25.00],
    ['name' => 'Product C', 'price' => 15.75],
]];
$accessor = DataAccessor::make($data);
$prices = $accessor->get('products.*.price');

// Calculate total
$totalPrice = array_sum($prices);

// Calculate average
$avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : 0;

// Find max/min
$maxPrice = max($prices);
$minPrice = min($prices);
```

### Root-Level Numeric Indices

```php
$data = [
    ['name' => 'Alice', 'age' => 30],
    ['name' => 'Bob', 'age' => 25],
];

$accessor = DataAccessor::make($data);

// Access specific index
$firstUser = $accessor->get('0.name');
// $firstUser = 'Alice'

// Use wildcard at root level
$allNames = $accessor->get('*.name');
// $allNames = ['0.name' => 'Alice', '1.name' => 'Bob']
```

### Filter Null Values

```php
$data = ['users' => [
    ['email' => 'alice@x.com'],
    ['email' => null],
    ['email' => 'bob@x.com'],
]];

$accessor = DataAccessor::make($data);
$emails = $accessor->get('users.*.email');

// Filter out nulls
$validEmails = array_filter($emails, fn($v) => $v !== null);
// $validEmails = ['users.0.email' => 'alice@x.com', 'users.2.email' => 'bob@x.com']

// Get only values (remove keys)
$emailList = array_values($validEmails);
// $emailList = ['alice@x.com', 'bob@x.com']
```

## Best Practices

### Use Wildcards for Bulk Reads

When you need to extract the same field from multiple items, wildcards are more efficient than looping:

```php
// ❌ Inefficient
$emails = [];
foreach ($data['users'] as $user) {
    $emails[] = $user['email'];
}

// ✅ Efficient
$accessor = DataAccessor::make($data);
$emails = $accessor->get('users.*.email');
```

### Combine with DataMutator

Use DataAccessor to read values and DataMutator to write them into a new structure:

```php
$sourceData = ['users' => [
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
]];
$accessor = DataAccessor::make($sourceData);
$emails = $accessor->get('users.*.email');

$target = [];
DataMutator::make($target)->set('contacts', array_map(fn($email) => ['email' => $email], $emails));
```

### Always Provide Defaults

Avoid null checks by providing sensible defaults:

```php
// ❌ Requires null check
$theme = $accessor->get('settings.theme');
if ($theme === null) {
    $theme = 'default';
}

// ✅ Clean and safe
$theme = $accessor->get('settings.theme', 'default');
```

### Leverage Collections

DataAccessor works seamlessly with Laravel Collections:

```php
$accessor = DataAccessor::make($data);
$prices = $accessor->get('products.*.price');

// Convert to Collection for chaining
$collection = collect($prices);
$filtered = $collection->filter(fn($p) => $p > 100)->values();
```

## Structure Introspection

DataAccessor provides methods to analyze the structure of your data with type information.

### Get Structure (Flat)

The `getStructure()` method returns a flat array with dot-notation paths and type information:

```php
use event4u\DataHelpers\DataAccessor;

$data = [
    'name' => 'John Doe',
    'age' => 30,
    'emails' => [
        ['email' => 'john@work.com', 'type' => 'work', 'verified' => true],
        ['email' => 'john@home.com', 'type' => 'home', 'verified' => false],
    ],
];

$accessor = DataAccessor::make($data);
$structure = $accessor->getStructure();
// $structure = // [
//   'name' => 'string',
//   'age' => 'int',
//   'emails' => 'array',
//   'emails.*' => 'array',
//   'emails.*.email' => 'string',
//   'emails.*.type' => 'string',
//   'emails.*.verified' => 'bool',
// ]
```

### Get Structure (Multidimensional)

The `getStructureMultidimensional()` method returns a nested array structure:

```php
$data = ['name' => 'John', 'age' => 30, 'emails' => [['email' => 'john@example.com']]];
$accessor = DataAccessor::make($data);
$structure = $accessor->getStructureMultidimensional();
// $structure = // [
//   'name' => 'string',
//   'age' => 'int',
//   'emails' => [
//     '*' => [
//       'email' => 'string',
//       'type' => 'string',
//       'verified' => 'bool',
//     ],
//   ],
// ]
```

### Wildcards in Structure

Arrays use wildcards (`*`) to represent the structure of all elements:

```php
$data = [
    'departments' => [
        [
            'name' => 'Engineering',
            'employees' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ],
        ],
        [
            'name' => 'Sales',
            'employees' => [
                ['name' => 'Charlie', 'age' => 35],
            ],
        ],
    ],
];

$accessor = DataAccessor::make($data);
$structure = $accessor->getStructure();
// $structure = // [
//   'departments' => 'array',
//   'departments.*' => 'array',
//   'departments.*.name' => 'string',
//   'departments.*.employees' => 'array',
//   'departments.*.employees.*' => 'array',
//   'departments.*.employees.*.name' => 'string',
//   'departments.*.employees.*.age' => 'int',
// ]
```

### Union Types

When array elements have different types, union types are returned:

```php
$data = [
    'values' => [
        'string value',
        42,
        null,
        true,
    ],
];

$accessor = DataAccessor::make($data);
$structure = $accessor->getStructure();
// $structure = // [
//   'values' => 'array',
//   'values.*' => 'bool|int|null|string',
// ]
```

### Object Types

Objects are returned with their full namespace:

```php
use event4u\DataHelpers\SimpleDto;

class EmailDto extends SimpleDto
{
    public function __construct(
        public readonly string $email,
        public readonly bool $verified,
    ) {}
}

$data = [
    'contact' => new EmailDto('john@example.com', true),
];

$accessor = DataAccessor::make($data);
$structure = $accessor->getStructure();
// $structure = // [
//   'contact' => '\EmailDto',
//   'contact.email' => 'string',
//   'contact.verified' => 'bool',
// ]
```

### Use Cases

Structure introspection is useful for:

- **API Documentation** - Generate API schemas automatically
- **Validation** - Verify data structure matches expectations
- **Type Checking** - Ensure data types are correct
- **Debugging** - Understand complex data structures
- **Code Generation** - Generate TypeScript interfaces or PHP classes
- **Testing** - Verify data structure in tests

```php
// Example: Validate API response structure
$accessor = DataAccessor::make($apiResponse);
$structure = $accessor->getStructure();

$expectedStructure = [
    'status' => 'string',
    'data' => 'array',
    'data.users' => 'array',
    'data.users.*' => 'array',
    'data.users.*.id' => 'int',
    'data.users.*.name' => 'string',
    'data.users.*.email' => 'string',
];

foreach ($expectedStructure as $path => $expectedType) {
    if (!isset($structure[$path]) || $structure[$path] !== $expectedType) {
        throw new Exception("Invalid structure at path: $path");
    }
}
```

## Performance Notes

### Wildcard Performance

- Wildcards traverse all matching elements
- Performance scales with the number of matches
- For large datasets, consider filtering data first

```php
// ❌ Slow on large datasets
$accessor = DataAccessor::make($hugeDataset);
$allEmails = $accessor->get('users.*.email');

// ✅ Filter first
$activeUsers = array_filter($hugeDataset['users'], fn($u) => $u['active']);
$accessor = DataAccessor::make(['users' => $activeUsers]);
$emails = $accessor->get('users.*.email');
```

### Deep Wildcards

Multiple wildcards can be expensive on large nested structures:

<!-- skip-test: Example only, no executable code -->
```php
// Can be slow on large datasets
$accessor->get('departments.*.teams.*.users.*.email');

// Consider limiting depth or filtering
```

### Caching

DataAccessor uses internal caching for path resolution, so repeated calls with the same path are fast:

```php
$data = ['user' => ['profile' => ['name' => 'Alice']]];
$accessor = DataAccessor::make($data);

// First call parses the path
$value1 = $accessor->get('user.profile.name');

// Subsequent calls with cached path (fast)
$value2 = $accessor->get('user.profile.name');
```

## Code Examples

The following working examples demonstrate DataAccessor in action:

- [**Basic Usage**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-accessor/basic-usage.php) - Complete example showing dot-notation, wildcards and default values
- [**Structure Introspection**](https://github.com/event4u-app/data-helpers/blob/main/examples/main-classes/data-accessor/structure-introspection.php) - Examples of analyzing data structure with type information

All examples are fully tested and can be run directly:

```bash
php examples/main-classes/data-accessor/basic-usage.php
php examples/main-classes/data-accessor/structure-introspection.php
```

## Related Tests

The DataAccessor functionality is thoroughly tested. Key test files:

**Unit Tests:**
- [DataAccessorTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataAccessor/DataAccessorTest.php) - Core functionality tests
- [DataAccessorLazyWildcardTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataAccessorLazyWildcardTest.php) - Wildcard behavior tests
- [DataAccessorDoctrineTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataAccessor/DataAccessorDoctrineTest.php) - Doctrine integration tests
- [DataAccessorLaravelTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/DataAccessor/DataAccessorLaravelTest.php) - Laravel integration tests

**Integration Tests:**
- [DataAccessorIntegrationTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Integration/DataAccessorIntegrationTest.php) - End-to-end scenarios

Run the tests:

```bash
# Run all DataAccessor tests
task test:unit -- --filter=DataAccessor

# Run specific test file
vendor/bin/pest tests/Unit/DataAccessor/DataAccessorTest.php
```

## Transformation Methods

DataAccessor provides powerful transformation methods for filtering, mapping, and reducing data:

### first() and last()

Get the first or last item from the data:

```php
$accessor = new DataAccessor([1, 2, 3, 4, 5]);

// Get first item
$first = $accessor->first();  // 1

// Get first item matching condition
$firstEven = $accessor->first(fn($n) => $n % 2 === 0);  // 2

// Get last item
$last = $accessor->last();  // 5

// Get last item matching condition
$lastOdd = $accessor->last(fn($n) => $n % 2 !== 0);  // 5

// With default value
$notFound = $accessor->first(fn($n) => $n > 10, 'default');  // 'default'
```

### filter()

Filter items by a callback:

```php
$accessor = new DataAccessor([1, 2, 3, 4, 5]);

// Filter with callback
$filtered = $accessor->filter(fn($n) => $n > 2);
// [2 => 3, 3 => 4, 4 => 5]

// Filter falsy values (without callback)
$accessor = new DataAccessor([0, 1, false, 2, null, 3, '']);
$filtered = $accessor->filter();
// [1 => 1, 3 => 2, 5 => 3]

// Callback receives value and key
$accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
$filtered = $accessor->filter(fn($value, $key) => $key === 'b');
// ['b' => 2]
```

### map()

Transform each item:

```php
$accessor = new DataAccessor([1, 2, 3]);

// Map values
$mapped = $accessor->map(fn($n) => $n * 2);
// [2, 4, 6]

// Map with keys
$accessor = new DataAccessor(['a' => 1, 'b' => 2]);
$mapped = $accessor->map(fn($value, $key) => $key . ':' . $value);
// ['a' => 'a:1', 'b' => 'b:2']
```

### reduce()

Reduce data to a single value:

```php
$accessor = new DataAccessor([1, 2, 3, 4, 5]);

// Sum all values
$sum = $accessor->reduce(fn($carry, $item) => $carry + $item, 0);
// 15

// Concatenate keys
$accessor = new DataAccessor(['a' => 1, 'b' => 2, 'c' => 3]);
$keys = $accessor->reduce(fn($carry, $item, $key) => $carry . $key, '');
// 'abc'
```

### Lazy Evaluation

For large datasets, use lazy evaluation with Generators:

```php
$accessor = new DataAccessor(range(1, 100000));

// Lazy iteration - processes one item at a time
foreach ($accessor->lazy() as $item) {
    // Memory efficient - doesn't load all items at once
}

// Lazy filter
foreach ($accessor->lazyFilter(fn($n) => $n > 50000) as $item) {
    // Only processes items that match the condition
}

// Lazy map
foreach ($accessor->lazyMap(fn($n) => $n * 2) as $item) {
    // Transforms items on-the-fly
}
```

**Benefits of Lazy Evaluation:**
- **Memory Efficient** - Processes one item at a time
- **Performance** - Stops early if you break out of the loop
- **Large Datasets** - Ideal for 10k+ items

## See Also

- [DataCollection](/data-helpers/main-classes/data-collection/) - Type-safe collections (uses DataAccessor internally)
- [DataMutator](/data-helpers/main-classes/data-mutator/) - Modify nested data
- [DataMapper](/data-helpers/main-classes/data-mapper/) - Transform data structures
- [DataFilter](/data-helpers/main-classes/data-filter/) - Query and filter data
- [Core Concepts: Dot-Notation](/data-helpers/core-concepts/dot-notation/) - Path syntax
- [Core Concepts: Wildcards](/data-helpers/core-concepts/wildcards/) - Wildcard operators
- [Examples](/data-helpers/examples/) - 90+ code examples
