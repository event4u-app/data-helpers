# Laravel Polyfills

This directory contains polyfill implementations for Laravel classes that are used by `laravel-data-helpers` but are not required as hard dependencies.

## Purpose

The polyfills allow `laravel-data-helpers` to work without requiring `illuminate/support` or `illuminate/database` as mandatory dependencies. This makes the package more flexible and usable in non-Laravel projects.

## Included Polyfills

### 1. `Illuminate\Support\Collection`
- **File**: `Collection.php`
- **Purpose**: Provides basic Collection functionality when `illuminate/support` is not installed
- **Features**:
  - `all()` - Get all items as array
  - `has($key)` - Check if key exists
  - `get($key, $default)` - Get item by key
  - `toArray()` - Convert to array
  - Implements `ArrayAccess`, `Countable`, `IteratorAggregate`

### 2. `Illuminate\Contracts\Support\Arrayable`
- **File**: `Arrayable.php`
- **Purpose**: Provides the Arrayable interface when `illuminate/support` is not installed
- **Features**:
  - `toArray()` - Convert instance to array

### 3. `Illuminate\Database\Eloquent\Model`
- **File**: `Model.php`
- **Purpose**: Provides minimal Model stub when `illuminate/database` is not installed
- **Features**:
  - `getAttributes()` - Get all attributes
  - `getAttribute($key)` - Get single attribute
  - `setAttribute($key, $value)` - Set attribute
  - `toArray()` - Convert to array
  - Implements `ArrayAccess`
- **Note**: This is a minimal stub for type checking. For full Eloquent functionality, install `illuminate/database`.

## How It Works

The `bootstrap.php` file is automatically loaded via Composer's `files` autoload mechanism. It checks if the Laravel classes exist and loads the polyfills only if they're missing.

```php
// In bootstrap.php
if (!class_exists(\Illuminate\Support\Collection::class)) {
    require_once __DIR__ . '/Collection.php';
}
```

## Installation

### With Laravel (Recommended)

```bash
composer require event4u/laravel-data-helpers
```

Laravel packages are automatically available as dev dependencies for testing.

### Without Laravel (Standalone)

```bash
composer require event4u/laravel-data-helpers
```

The polyfills will be automatically loaded, providing basic functionality.

### With Full Laravel Support

For full Laravel Collection and Eloquent Model support:

```bash
composer require event4u/laravel-data-helpers
composer require illuminate/support
composer require illuminate/database
```

## Limitations

The polyfills provide **minimal functionality** required by `laravel-data-helpers`. They are not full replacements for Laravel packages:

- **Collection polyfill**: Only implements methods used by this package (all, has, get, toArray)
- **Model polyfill**: Only provides basic attribute access, no database functionality
- **Arrayable polyfill**: Just the interface definition

For production use with Laravel features, install the real Laravel packages.

## Testing

The package tests run with full Laravel support (via `require-dev`), ensuring compatibility with real Laravel classes.

## Compatibility

- PHP 8.2+
- Works with or without Laravel
- Compatible with Laravel 8, 9, 10, 11, 12

## See Also

- [Main README](../../README.md)
- [Composer Documentation](https://getcomposer.org/doc/04-schema.md#suggest)

