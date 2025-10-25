---
title: Hooks & Events
description: Use hooks and events to customize DataMapper behavior
---

Use hooks and events to customize DataMapper behavior.

## Introduction

Hooks allow you to intercept and modify data during mapping:

- ✅ **BeforeAll** - Before mapping starts
- ✅ **BeforeTransform** - Before transforming value
- ✅ **AfterTransform** - After transforming value
- ✅ **BeforeWrite** - Before writing to target
- ✅ **AfterAll** - After mapping completes

## Basic Usage

### Creating Hooks

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Hooks;
use event4u\DataHelpers\Enums\DataMapperHook;

$src = ['user' => ['name' => '  Alice  ']];
$template = ['name' => '{{ user.name }}'];

$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, fn($value) => trim($value))
    ->toArray();

$result = DataMapper::source($src)
    ->template($template)
    ->hooks($hooks)
    ->map()
    ->getTarget();
```

## Available Hooks

### BeforeAll

Runs before mapping starts:

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeAll, function($context) {
        // Initialize shared state
        $context->set('start_time', microtime(true));
    })
    ->toArray();
```

### BeforeTransform

Runs before transforming each value:

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value, $context) {
        // Trim strings
        return is_string($value) ? trim($value) : $value;
    })
    ->toArray();
```

### AfterTransform

Runs after transforming each value:

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::AfterTransform, function($value, $context) {
        // Log transformed value
        Log::debug('Transformed', ['value' => $value]);

        return $value;
    })
    ->toArray();
```

### BeforeWrite

Runs before writing to target:

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeWrite, function($value, $context) {
        // Skip null values
        if ($value === null) {
            return '__skip__';
        }

        return $value;
    })
    ->toArray();
```

### AfterAll

Runs after mapping completes:

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::AfterAll, function($context) {
        // Log completion time
        $duration = microtime(true) - $context->get('start_time');
        Log::info('Mapping completed', ['duration' => $duration]);
    })
    ->toArray();
```

## Real-World Examples

### Data Sanitization

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value) {
        if (is_string($value)) {
            // Trim whitespace
            $value = trim($value);

            // Remove HTML tags
            $value = strip_tags($value);

            // Sanitize
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    })
    ->toArray();
```

### Audit Trail

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeAll, function($context) {
        $context->set('changes', []);
    })
    ->on(DataMapperHook::AfterTransform, function($value, $context) {
        $changes = $context->get('changes');
        $changes[] = [
            'path' => $context->getPath(),
            'value' => $value,
            'timestamp' => now(),
        ];
        $context->set('changes', $changes);

        return $value;
    })
    ->on(DataMapperHook::AfterAll, function($context) {
        AuditLog::create([
            'changes' => $context->get('changes'),
            'user_id' => auth()->id(),
        ]);
    })
    ->toArray();
```

### Data Validation

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value, $context) {
        $path = $context->getPath();

        // Validate email
        if ($path === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email');
        }

        // Validate age
        if ($path === 'age' && ($value < 0 || $value > 150)) {
            throw new ValidationException('Invalid age');
        }

        return $value;
    })
    ->toArray();
```

### Default Values

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeWrite, function($value, $context) {
        $path = $context->getPath();

        // Set default values
        if ($value === null) {
            return match($path) {
                'status' => 'active',
                'role' => 'user',
                'created_at' => now(),
                default => $value,
            };
        }

        return $value;
    })
    ->toArray();
```

### Data Transformation

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::AfterTransform, function($value, $context) {
        $path = $context->getPath();

        // Transform specific fields
        return match($path) {
            'email' => strtolower($value),
            'name' => ucwords($value),
            'phone' => preg_replace('/[^0-9]/', '', $value),
            default => $value,
        };
    })
    ->toArray();
```

## Conditional Hooks

### For Specific Paths

```php
$hooks = Hooks::make()
    ->onForSrc(DataMapperHook::BeforeTransform, 'user.name', function($value) {
        return ucwords($value);
    })
    ->onForTgt(DataMapperHook::BeforeWrite, 'profile.email', function($value) {
        return strtolower($value);
    })
    ->toArray();
```

### For Specific Modes

```php
$hooks = Hooks::make()
    ->onForMode(DataMapperHook::BeforeAll, 'simple', function($context) {
        // Only for simple mode
    })
    ->toArray();
```

## Multiple Hooks

### Chaining Hooks

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, fn($v) => trim($v))
    ->on(DataMapperHook::AfterTransform, fn($v) => strtolower($v))
    ->on(DataMapperHook::BeforeWrite, fn($v) => $v ?: null)
    ->toArray();
```

### Merging Hooks

```php
$baseHooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, fn($v) => trim($v))
    ->toArray();

$customHooks = Hooks::make()
    ->on(DataMapperHook::AfterTransform, fn($v) => strtolower($v))
    ->toArray();

$allHooks = array_merge($baseHooks, $customHooks);
```

## Hook Context

### Reading Context

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value, $context) {
        $path = $context->getPath();
        $source = $context->getSource();
        $target = $context->getTarget();

        return $value;
    })
    ->toArray();
```

### Setting Context

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeAll, function($context) {
        $context->set('user_id', auth()->id());
        $context->set('timestamp', now());
    })
    ->on(DataMapperHook::AfterTransform, function($value, $context) {
        $userId = $context->get('user_id');
        // Use user_id

        return $value;
    })
    ->toArray();
```

## Skipping Values

### Skip Null Values

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeWrite, function($value) {
        return $value === null ? '__skip__' : $value;
    })
    ->toArray();
```

### Skip Empty Strings

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeWrite, function($value) {
        return $value === '' ? '__skip__' : $value;
    })
    ->toArray();
```

### Conditional Skip

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeWrite, function($value, $context) {
        $path = $context->getPath();

        // Skip specific fields
        if (in_array($path, ['password', 'secret'])) {
            return '__skip__';
        }

        return $value;
    })
    ->toArray();
```

## Error Handling

### Try-Catch

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value) {
        try {
            return transform($value);
        } catch (Exception $e) {
            Log::error('Transform failed', ['error' => $e->getMessage()]);
            return $value;
        }
    })
    ->toArray();
```

### Validation Errors

```php
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($value, $context) {
        if (!validate($value)) {
            throw new ValidationException("Invalid value at {$context->getPath()}");
        }

        return $value;
    })
    ->toArray();
```

## Best Practices

### Keep Hooks Simple

```php
// ✅ Good - simple hook
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, fn($v) => trim($v))
    ->toArray();

// ❌ Bad - complex hook
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($v) {
        // 100 lines of code
    })
    ->toArray();
```

### Use Specific Hooks

```php
// ✅ Good - specific hook
$hooks = Hooks::make()
    ->onForSrc(DataMapperHook::BeforeTransform, 'user.name', fn($v) => ucwords($v))
    ->toArray();

// ❌ Bad - generic hook with conditions
$hooks = Hooks::make()
    ->on(DataMapperHook::BeforeTransform, function($v, $ctx) {
        if ($ctx->getPath() === 'user.name') {
            return ucwords($v);
        }
        return $v;
    })
    ->toArray();
```

## See Also

- [DataMapper](/main-classes/data-mapper/) - DataMapper guide
- [Pipelines](/advanced/pipelines/) - Pipeline processing
- [Custom Casts](/advanced/custom-casts/) - Custom type casts

