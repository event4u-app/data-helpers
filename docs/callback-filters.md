# Callback Filters

Callback filters allow you to define custom transformation logic using closures. They provide complete access to the mapping context, making them perfect for complex, context-aware transformations.

## Table of Contents

- [Overview](#overview)
- [CallbackParameters DTO](#callbackparameters-dto)
- [Pipeline Usage](#pipeline-usage)
- [Template Expression Usage](#template-expression-usage)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)
- [Examples](#examples)

---

## Overview

Callback filters come in two flavors:

1. **CallbackFilter** - For use in pipelines with inline closures
2. **CallbackRegistry** - For named callbacks in template expressions

Both receive a `CallbackParameters` DTO with complete context about the current transformation.

---

## CallbackParameters DTO

Every callback receives a `CallbackParameters` object with the following properties:

```php
readonly class CallbackParameters {
    public mixed $source;      // Complete source data
    public array $mapping;     // Complete mapping array
    public mixed $target;      // Complete target data
    public string $key;        // Final individual key (e.g., 'email')
    public string $keyPath;    // Full dot notation path (e.g., 'user.profile.email')
    public mixed $value;       // Current value being transformed
}
```

### Property Details

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `source` | `mixed` | Complete source data structure | `['user' => ['name' => 'John']]` |
| `mapping` | `array` | Complete mapping array | `['profile.name' => '{{ user.name }}']` |
| `target` | `mixed` | Complete target data structure | `['profile' => []]` |
| `key` | `string` | Final key being written | `'name'` |
| `keyPath` | `string` | Full dot-notation path | `'profile.name'` |
| `value` | `mixed` | Current value to transform | `'John'` |

---

## Pipeline Usage

Use `CallbackFilter` to apply custom transformations in pipelines.

### Basic Example

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CallbackFilter;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;

$source = [
    'user' => [
        'email' => 'JOHN@EXAMPLE.COM',
    ],
];

$mapping = [
    'profile.email' => '{{ user.email }}',
];

$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Lowercase all emails
        if ($params->key === 'email' && is_string($params->value)) {
            return strtolower($params->value);
        }
        return $params->value;
    }),
])->map($source, [], $mapping);

// Result: ['profile' => ['email' => 'john@example.com']]
```

### Context-Aware Transformations

Access the full context to make intelligent decisions:

```php
$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Apply discount from source data
        if ($params->key === 'price' && is_numeric($params->value)) {
            $discount = $params->source['order']['discount'] ?? 0;
            return $params->value * (1 - $discount / 100);
        }
        return $params->value;
    }),
])->map($source, [], $mapping);
```

### Conditional Skipping

Return `'__skip__'` to skip values:

```php
$result = DataMapper::pipe([
    new CallbackFilter(function(CallbackParameters $params) {
        // Skip empty values
        if ('' === $params->value || [] === $params->value) {
            return '__skip__';
        }
        return $params->value;
    }),
])->map($source, [], $mapping);
```

---

## Template Expression Usage

Use `CallbackRegistry` to register named callbacks for template expressions.

### Registering Callbacks

```php
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;

// Register a callback
CallbackRegistry::register('slugify', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }
    return strtolower(str_replace(' ', '-', $params->value));
});
```

### Using in Templates

```php
$template = [
    'post' => [
        'slug' => '{{ article.title | callback:slugify }}',
    ],
];

$result = DataMapper::mapFromTemplate($template, [
    'article' => ['title' => 'Hello World'],
]);

// Result: ['post' => ['slug' => 'hello-world']]
```

### Registry Methods

```php
// Register (throws exception if already exists)
CallbackRegistry::register('name', $callback);

// Register or replace
CallbackRegistry::registerOrReplace('name', $callback);

// Get callback
$callback = CallbackRegistry::get('name');

// Check if exists
if (CallbackRegistry::has('name')) { ... }

// Unregister
CallbackRegistry::unregister('name');

// Clear all
CallbackRegistry::clear();

// Get all names
$names = CallbackRegistry::getRegisteredNames();

// Count
$count = CallbackRegistry::count();
```

---

## Advanced Features

### Chaining Multiple Callbacks

```php
CallbackRegistry::register('sanitize', fn($p) => trim(strip_tags($p->value)));
CallbackRegistry::register('truncate', fn($p) => substr($p->value, 0, 20) . '...');

$template = [
    'preview' => '{{ post.content | callback:sanitize | callback:truncate }}',
];
```

### Path-Based Logic

```php
new CallbackFilter(function(CallbackParameters $params) {
    // Different logic based on path
    if (str_starts_with($params->keyPath, 'user.')) {
        // User-specific transformation
    } elseif (str_starts_with($params->keyPath, 'admin.')) {
        // Admin-specific transformation
    }
    return $params->value;
})
```

### Complex Transformations

```php
CallbackRegistry::register('initials', function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }
    
    $parts = explode(' ', $params->value);
    $initials = array_map(fn($p) => $p[0] ?? '', $parts);
    return strtoupper(implode('', $initials));
});

// 'John Doe' => 'JD'
```

---

## Error Handling

Exceptions in callbacks are caught and handled via `MapperExceptions`:

```php
use event4u\DataHelpers\DataMapper\MapperExceptions;

MapperExceptions::setCollectExceptionsEnabled(true);

CallbackRegistry::register('divide', function(CallbackParameters $params) {
    if (!is_numeric($params->value)) {
        throw new RuntimeException('Value must be numeric');
    }
    return $params->value / 2;
});

try {
    $result = DataMapper::mapFromTemplate($template, $sources);
} catch (Throwable $e) {
    // Exception contains context about where it failed
    echo $e->getMessage();
}
```

**Behavior:**
- Exceptions are collected during mapping
- Original value is returned when exception occurs
- All exceptions are thrown at the end of mapping
- Exception message includes context (path, callback name)

---

## Best Practices

### 1. Type Safety

Always check types before transforming:

```php
new CallbackFilter(function(CallbackParameters $params) {
    if (!is_string($params->value)) {
        return $params->value;
    }
    return strtoupper($params->value);
})
```

### 2. Descriptive Names

Use clear, descriptive names for registered callbacks:

```php
// ✅ Good
CallbackRegistry::register('convertToSlug', ...);
CallbackRegistry::register('extractInitials', ...);

// ❌ Bad
CallbackRegistry::register('cb1', ...);
CallbackRegistry::register('transform', ...);
```

### 3. Single Responsibility

Keep callbacks focused on one transformation:

```php
// ✅ Good - separate concerns
CallbackRegistry::register('trim', fn($p) => trim($p->value));
CallbackRegistry::register('lowercase', fn($p) => strtolower($p->value));

// ❌ Bad - doing too much
CallbackRegistry::register('process', function($p) {
    return strtolower(trim(strip_tags($p->value)));
});
```

### 4. Reusability

Register callbacks that can be reused across different mappings:

```php
// Reusable callbacks
CallbackRegistry::register('formatCurrency', fn($p) => '$' . number_format($p->value, 2));
CallbackRegistry::register('formatDate', fn($p) => date('Y-m-d', strtotime($p->value)));
```

---

## Examples

See [examples/19-callback-filters.php](../examples/19-callback-filters.php) for complete working examples including:

1. CallbackFilter in Pipeline
2. CallbackRegistry with Template Expressions
3. Access to Full Context
4. Conditional Skipping with `__skip__`
5. Multiple Callbacks in Chain
6. Error Handling

---

## See Also

- [Data Mapper Pipeline](data-mapper-pipeline.md) - Pipeline API overview
- [Template Expressions](template-expressions.md) - Template syntax and filters
- [Filters](filters.md) - Built-in filters
- [Exception Handling](exception-handling.md) - Error handling strategies

