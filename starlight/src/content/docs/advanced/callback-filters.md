---
title: Callback Filters
description: Custom transformation logic using closures with full context access
---

Callback filters allow you to define custom transformation logic using closures with complete access to the mapping context.

## Introduction

Callback filters come in two flavors:

1. **CallbackFilter** - For use in pipelines with inline closures
2. **CallbackHelper** - For named callbacks in template expressions

Both receive a `CallbackParameters` DTO with complete context about the current transformation.

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

$result = DataMapper::source($source)
    ->template($mapping)
    ->pipeline([
        new CallbackFilter(function(CallbackParameters $params) {
            // Lowercase all emails
            if ($params->key === 'email' && is_string($params->value)) {
                return strtolower($params->value);
            }
            return $params->value;
        }),
    ])
    ->map()
    ->getTarget();

// Result: ['profile' => ['email' => 'john@example.com']]
```

### Context-Aware Transformations

Access the full context to make intelligent decisions:

<!-- skip-test: Requires CallbackFilter class -->
```php
$source = ['order' => ['discount' => 10]];
$mapping = ['price' => '{{ order.price }}'];
$result = DataMapper::source($source)
    ->template($mapping)
    ->pipeline([
        new CallbackFilter(function(CallbackParameters $params) {
            // Apply discount from source data
            if ($params->key === 'price' && is_numeric($params->value)) {
                $discount = $params->source['order']['discount'] ?? 0;
                return $params->value * (1 - $discount / 100);
            }
            return $params->value;
        }),
    ])
    ->map()
    ->getTarget();
```

### Conditional Skipping

Return `'__skip__'` to skip values:

<!-- skip-test: Requires CallbackFilter class -->
```php
$source = ['name' => '', 'email' => 'test@example.com'];
$mapping = ['name' => '{{ name }}', 'email' => '{{ email }}'];
$result = DataMapper::source($source)
    ->template($mapping)
    ->pipeline([
        new CallbackFilter(function(CallbackParameters $params) {
            // Skip empty values
            if ('' === $params->value || [] === $params->value) {
                return '__skip__';
            }
            return $params->value;
        }),
    ])
    ->map()
    ->getTarget();
```

## Template Expression Usage

Use `CallbackHelper` to register named callbacks for template expressions.

### Registering Callbacks

```php
use event4u\DataHelpers\Support\CallbackHelper;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;

// Register a callback
CallbackHelper::register('slugify', function(CallbackParameters $params) {
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

$sources = [
    'article' => [
        'title' => 'Hello World',
    ],
];

$result = DataMapper::source($sources)
    ->template($template)
    ->map()
    ->getTarget();
// Result: ['post' => ['slug' => 'hello-world']]
```

### Multiple Callbacks

```php
CallbackHelper::register('uppercase', fn($p) => strtoupper($p->value));
CallbackHelper::register('reverse', fn($p) => strrev($p->value));

$template = [
    'text' => '{{ input | callback:uppercase | callback:reverse }}',
];
```

## Advanced Features

### Access Full Context

```php
CallbackHelper::register('applyDiscount', function(CallbackParameters $params) {
    // Access source data
    $discount = $params->source['order']['discount'] ?? 0;

    // Access target data
    $existingTotal = $params->target['order']['total'] ?? 0;

    // Apply transformation
    return $params->value * (1 - $discount / 100);
});
```

### Conditional Logic

```php
CallbackHelper::register('formatPrice', function(CallbackParameters $params) {
    // Check key path
    if (str_contains($params->keyPath, 'price')) {
        return '$' . number_format($params->value, 2);
    }
    return $params->value;
});
```

### Error Handling

```php
CallbackHelper::register('safeJson', function(CallbackParameters $params) {
    try {
        return json_decode($params->value, true);
    } catch (\Exception $e) {
        return null;
    }
});
```

## Best Practices

### 1. Keep Callbacks Simple

```php
// ✅ Good - Single responsibility
CallbackHelper::register('trim', fn($p) => trim($p->value));

// ❌ Bad - Too complex
CallbackHelper::register('processEverything', function($p) {
    // 50 lines of complex logic
});
```

### 2. Use Type Checks

```php
// ✅ Good
CallbackHelper::register('uppercase', function($p) {
    if (!is_string($p->value)) {
        return $p->value;
    }
    return strtoupper($p->value);
});
```

### 3. Return Original Value When Not Applicable

```php
// ✅ Good
CallbackHelper::register('formatDate', function($p) {
    if (!$p->value instanceof DateTime) {
        return $p->value; // Return unchanged
    }
    return $p->value->format('Y-m-d');
});
```

### 4. Use Descriptive Names

```php
// ✅ Good
CallbackHelper::register('formatCurrency', ...);
CallbackHelper::register('slugifyTitle', ...);

// ❌ Bad
CallbackHelper::register('fn1', ...);
CallbackHelper::register('process', ...);
```

## Real-World Examples

### Format Currency

```php
CallbackHelper::register('formatCurrency', function($p) {
    if (!is_numeric($p->value)) {
        return $p->value;
    }
    return '$' . number_format($p->value, 2);
});

$template = [
    'price' => '{{ product.price | callback:formatCurrency }}',
];
```

### Generate Slug

<!-- skip-test: Slugify already registered above -->
```php
CallbackHelper::register('slugify', function($p) {
    if (!is_string($p->value)) {
        return $p->value;
    }
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $p->value));
});
```

### Calculate Age

```php
CallbackHelper::register('calculateAge', function($p) {
    if (!$p->value instanceof DateTime) {
        return null;
    }
    return $p->value->diff(new DateTime())->y;
});
```

## See Also

- [Pipelines](/advanced/pipelines/) - Pipeline processing
- [Template Expressions](/advanced/template-expressions/) - Template syntax
- [DataMapper](/main-classes/data-mapper/) - DataMapper guide

