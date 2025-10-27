---
title: Data Types Support
description: Supported data types and automatic type detection
---

Data Helpers works with various data types and automatically detects the appropriate handling strategy.

## Supported Types

### Arrays

Standard PHP arrays are fully supported:

```php
$data = ['user' => ['name' => 'John']];
$accessor = new DataAccessor($data);
```

### Objects

Plain PHP objects with public properties:

```php
$user = new stdClass();
$user->name = 'John';
$accessor = new DataAccessor($user);
$name = $accessor->get('name');
```

### Laravel Collections

Laravel Collections are automatically detected:

```php
$collection = collect([
    ['name' => 'Alice'],
    ['name' => 'Bob'],
]);
$accessor = new DataAccessor($collection);
```

### Eloquent Models

Laravel Eloquent Models with relationships:

<!-- skip-test: Requires Laravel Eloquent -->
```php
$user = User::with('profile')->first();
$accessor = new DataAccessor($user);
$email = $accessor->get('profile.email');
```

### Doctrine Collections

Doctrine Collections and Entities:

<!-- skip-test: Requires Doctrine repository -->
```php
$users = $repository->findAll();
$accessor = new DataAccessor($users);
```

### JSON Strings

JSON strings are automatically decoded:

```php
$json = '{"user":{"name":"John"}}';
$accessor = new DataAccessor($json);
$name = $accessor->get('user.name');
```

### XML Strings

XML strings are automatically converted:

```php
$xml = '<user><name>John</name></user>';
$accessor = new DataAccessor($xml);
$name = $accessor->get('user.name');
```

## Type Detection

Data Helpers automatically detects the data type and uses the appropriate strategy:

1. **JSON Detection** - Checks for JSON string
2. **XML Detection** - Checks for XML string
3. **Collection Detection** - Checks for Laravel/Doctrine Collections
4. **Model Detection** - Checks for Eloquent/Doctrine Models
5. **Object Detection** - Handles plain objects
6. **Array Fallback** - Default handling

## Framework Detection

Framework support is automatically detected at runtime:

- **Laravel** - Detected via `class_exists(Collection::class)`
- **Doctrine** - Detected via `class_exists(DoctrineCollection::class)`
- **Symfony** - Detected via `class_exists(ArrayCollection::class)`

No configuration needed!

## See Also

- [Framework Detection](/data-helpers/core-concepts/framework-detection/)
- [Framework Integration](/data-helpers/framework-integration/overview/)
