---
title: CallbackHelper
description: Unified callback execution and registration helper
---

The `CallbackHelper` provides a unified way to execute and register callbacks throughout the library. It supports multiple callback formats and provides a consistent API for callback management.

## Overview

`CallbackHelper` is the central callback management system used by:
- **DataMapper** - Template expression callbacks
- **SimpleDTO Attributes** - `#[Visible]`, `#[WhenCallback]`
- **Hooks** - DataMapper hook callbacks
- **Filters** - Custom filter callbacks

## Registration

### register()

Register a named callback that can be used in template expressions.

```php
use event4u\DataHelpers\Support\CallbackHelper;

CallbackHelper::register('slugify_example', function($params) {
    return strtolower(str_replace(' ', '-', $params->value));
});

// Use in template
$template = ['slug' => '{{ article.title | callback:slugify_example }}'];
```

### registerOrReplace()

Register a callback or replace an existing one.

```php
CallbackHelper::registerOrReplace('slugify', function($params) {
    return strtolower(preg_replace('/[^a-z0-9]+/', '-', $params->value));
});
```

### get()

Retrieve a registered callback.

```php
$callback = CallbackHelper::get('slugify');

if ($callback !== null) {
    // Callback exists
}
```

### has()

Check if a callback is registered.

```php
if (CallbackHelper::has('slugify')) {
    // Callback exists
}
```

### unregister()

Remove a registered callback.

```php
CallbackHelper::unregister('slugify');
```

### clear()

Remove all registered callbacks.

```php
CallbackHelper::clear();
```

### getRegisteredNames()

Get all registered callback names.

```php
$names = CallbackHelper::getRegisteredNames();
// ['slugify', 'uppercase', 'formatDate', ...]
```

### count()

Get the number of registered callbacks.

```php
$count = CallbackHelper::count();
```

## Execution

### execute()

Execute a callback with unified logic. Supports multiple callback formats.

```php
use event4u\DataHelpers\Support\CallbackHelper;

// Register a callback first
CallbackHelper::register('double', fn($x) => $x * 2);

// Execute registered callback
$result = CallbackHelper::execute('double', 5);
// Result: 10

// Execute closure
$result = CallbackHelper::execute(fn($x) => $x * 2, 5);
// Result: 10
```

## Execution Priority

`CallbackHelper::execute()` resolves callbacks in the following order:

1. **Resolve `static::method`** - If callback is `'static::method'` and first arg is an object, resolve to actual class
2. **Registered callbacks** - Check if callback is registered via `register()`
3. **Static methods** - `'Class::method'` or `[Class::class, 'method']`
4. **Array callables** - `[$instance, 'method']`
5. **Instance methods** - Method name with instance in args (uses reflection for private/protected)
6. **Global functions** - `'function_name'`
7. **Closures** - `fn($x) => $x * 2`
8. **Invokable objects** - Objects with `__invoke()` method

## Callback Formats

### Registered Callbacks

```php
CallbackHelper::register('upper', fn($p) => strtoupper($p->value));

// Use in template
$template = ['name' => '{{ user.name | callback:upper }}'];
```

### Static Methods

```php
class StringHelper
{
    public static function slugify(string $value): string
    {
        return strtolower(str_replace(' ', '-', $value));
    }
}

// String syntax
$result = CallbackHelper::execute('StringHelper::slugify', 'Hello World');

// Array syntax
$result = CallbackHelper::execute([StringHelper::class, 'slugify'], 'Hello World');
```

### Static Method with `static::`

```php
class UserDTO extends SimpleDTO
{
    #[Visible(callback: 'static::canViewEmail')]
    public readonly string $email;

    public static function canViewEmail(mixed $dto, mixed $context): bool
    {
        return $context?->role === 'admin';
    }
}

// CallbackHelper automatically resolves 'static::' to the actual class
```

### Instance Methods

```php
class UserDTO extends SimpleDTO
{
    #[Visible(callback: 'canViewEmail')]
    public readonly string $email;

    private function canViewEmail(mixed $context): bool
    {
        return $context?->role === 'admin';
    }
}

// CallbackHelper uses reflection to call private/protected methods
```

### Array Callables with Instance

```php
class PermissionChecker
{
    public function canView(string $user): bool
    {
        return $user === 'admin';
    }
}

$checker = new PermissionChecker();

// Array syntax with instance
$result = CallbackHelper::execute([$checker, 'canView'], 'admin');
// Result: true
```

### Global Functions

```php
function slugify(string $value): string
{
    return strtolower(str_replace(' ', '-', $value));
}

$result = CallbackHelper::execute('slugify', 'Hello World');
```

### Closures

```php
$callback = fn($value) => strtoupper($value);

$result = CallbackHelper::execute($callback, 'hello');
// 'HELLO'
```

### Invokable Objects

```php
class Slugifier
{
    public function __invoke(string $value): string
    {
        return strtolower(str_replace(' ', '-', $value));
    }
}

$slugifier = new Slugifier();
$result = CallbackHelper::execute($slugifier, 'Hello World');
```

## Examples

### Template Expression Callbacks

```php
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Support\CallbackHelper;

// Register callbacks
CallbackHelper::register('slugify', fn($p) => strtolower(str_replace(' ', '-', $p->value)));
CallbackHelper::register('excerpt', fn($p) => substr($p->value, 0, 100) . '...');

// Use in template
$template = [
    'slug' => '{{ article.title | callback:slugify }}',
    'excerpt' => '{{ article.content | callback:excerpt }}',
];

$result = DataMapper::source($article)
    ->template($template)
    ->map()
    ->getTarget();
```

### Attribute Callbacks

```php
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Visible;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        // Instance method callback
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,

        // Static method callback
        #[Visible(callback: 'static::canViewPhone')]
        public readonly string $phone,

        // Conditional property
        #[WhenCallback('static::isAdult')]
        public readonly ?string $adultContent = null,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        return $context?->role === 'admin';
    }

    public static function canViewPhone(mixed $dto, mixed $context): bool
    {
        return $context?->role === 'admin' || $context?->role === 'manager';
    }

    public static function isAdult(object $dto, mixed $value, array $context): bool
    {
        return ($dto->age ?? 0) >= 18;
    }
}
```

## Error Handling

`CallbackHelper::execute()` throws `InvalidArgumentException` if the callback cannot be resolved:

```php
try {
    $arg = ['value' => 'test'];
    $result = CallbackHelper::execute('nonExistentFunction', $arg);
} catch (InvalidArgumentException $e) {
    // Handle error
}
```

For attribute callbacks (`#[Visible]`, `#[WhenCallback]`), exceptions are caught automatically and the property is hidden or excluded.

## Migration from CallbackRegistry

`CallbackRegistry` has been removed. Use `CallbackHelper` instead:

```php
// ❌ Old (removed)
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
CallbackRegistry::register('upper', fn($p) => strtoupper($p->value));

// ✅ New
use event4u\DataHelpers\Support\CallbackHelper;
CallbackHelper::register('upper', fn($p) => strtoupper($p->value));
```

All methods have the same signature, so migration is straightforward.

