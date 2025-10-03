# Optional Framework Dependencies

## Overview

The package works standalone with PHP 8.2+ and provides polyfills for framework-specific classes when they're not available.

**Supported Frameworks:**
- **Laravel** (Collections, Eloquent Models)
- **Symfony/Doctrine** (Collections, Entities)
- **Standalone PHP** (Arrays, Objects, JSON, XML)

## Installation Options

### Option 1: Standalone (No Framework)

```bash
composer require event4u/laravel-data-helpers
```

**What you get:**

- ✅ Full DataAccessor functionality with arrays, objects, JSON, XML
- ✅ Full DataMutator functionality with arrays and objects
- ✅ Full DataMapper functionality
- ✅ Basic Collection support (via polyfill)
- ✅ Basic Arrayable support (via polyfill)
- ⚠️ Limited Model/Entity support (via polyfill stub)

**Limitations:**

- Collection polyfill only provides basic methods (all, has, get, toArray)
- Model/Entity polyfill is a minimal stub for type checking only
- No database functionality

### Option 2: With Laravel Support (Recommended for Laravel Projects)

```bash
composer require event4u/laravel-data-helpers
composer require illuminate/support
composer require illuminate/database
```

**What you get:**

- ✅ Everything from Option 1
- ✅ Full Laravel Collection support with all methods
- ✅ Full Eloquent Model support with database functionality
- ✅ Full Arrayable interface support

### Option 3: With Symfony/Doctrine Support (Recommended for Symfony Projects)

```bash
composer require event4u/laravel-data-helpers
composer require doctrine/collections
composer require doctrine/orm
```

**What you get:**

- ✅ Everything from Option 1
- ✅ Full Doctrine Collection support with all methods
- ✅ Full Doctrine Entity support with ORM functionality
- ✅ Automatic entity detection via attributes/annotations

### Option 4: Development/Testing (Automatic)

When you install the package for development:

```bash
git clone <repo>
composer install
```

Laravel and Doctrine packages are automatically installed as `require-dev` dependencies, ensuring all tests run with full framework support.

## Polyfills

The package includes polyfills in `src/Polyfills/` that are automatically loaded when framework classes are not available:

**Laravel Polyfills:**
- **`Illuminate\Support\Collection`** - Basic collection functionality
- **`Illuminate\Contracts\Support\Arrayable`** - Arrayable interface
- **`Illuminate\Database\Eloquent\Model`** - Minimal model stub

**Doctrine Polyfills:**
- **`Doctrine\Common\Collections\Collection`** - Collection interface
- **`Doctrine\Common\Collections\ArrayCollection`** - Basic collection implementation

See [src/Polyfills/README.md](src/Polyfills/README.md) for details.

### Checking Which Implementation Is Used

```php
use Illuminate\Support\Collection;

// Check if real Laravel Collection is available
if (class_exists(Collection::class)) {
    $reflection = new ReflectionClass(Collection::class);
    if (str_contains($reflection->getFileName(), 'vendor/illuminate')) {
        echo "Using real Laravel Collection";
    } else {
        echo "Using polyfill Collection";
    }
}
```

## Use Cases

### Use Case 1: Laravel Project

```php
// composer.json already has illuminate/support
use event4u\DataHelpers\DataAccessor;
use Illuminate\Support\Collection;

$collection = new Collection(['name' => 'John', 'age' => 30]);
$accessor = new DataAccessor($collection);
$name = $accessor->get('name'); // "John"
```

**Result:** Uses real Laravel Collection with full functionality.

### Use Case 2: Standalone PHP Project

```php
// No illuminate/support installed
use event4u\DataHelpers\DataAccessor;
use Illuminate\Support\Collection; // Polyfill is loaded automatically

$collection = new Collection(['name' => 'John', 'age' => 30]);
$accessor = new DataAccessor($collection);
$name = $accessor->get('name'); // "John"
```

**Result:** Uses polyfill Collection with basic functionality.

### Use Case 3: Symfony Project

```php
// No Laravel dependencies
use event4u\DataHelpers\DataMapper;

$source = ['first_name' => 'John', 'last_name' => 'Doe'];
$target = [];

$result = DataMapper::map($source, $target, [
    'first_name' => 'firstName',
    'last_name' => 'lastName',
]);

// $result = ['firstName' => 'John', 'lastName' => 'Doe']
```

**Result:** Works perfectly without any Laravel dependencies.

## Testing

The package tests always run with full Laravel support (via `require-dev`), ensuring compatibility with real Laravel classes.

```bash
composer test
```

## Performance

**Polyfills have minimal performance impact:**

- Polyfills are only loaded if Laravel classes don't exist (one-time check)
- Polyfill implementations are lightweight and optimized
- No runtime overhead when using real Laravel classes

## Compatibility Matrix

| Feature                  | Standalone | With illuminate/support | With illuminate/database | With doctrine/collections | With doctrine/orm |
|--------------------------|------------|-------------------------|--------------------------|---------------------------|-------------------|
| Arrays                   | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Objects                  | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| JSON/XML                 | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Laravel Collections      | ⚠️ Basic   | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Doctrine Collections     | ⚠️ Basic   | ⚠️ Basic                | ⚠️ Basic                 | ✅ Full                    | ✅ Full            |
| Arrayable Interface      | ⚠️ Basic   | ✅ Full                  | ✅ Full                   | ⚠️ Basic                  | ⚠️ Basic          |
| Eloquent Models          | ⚠️ Stub    | ⚠️ Stub                 | ✅ Full                   | ⚠️ Stub                   | ⚠️ Stub           |
| Doctrine Entities        | ❌ None    | ❌ None                 | ❌ None                  | ⚠️ Basic                  | ✅ Full            |
| DataAccessor             | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| DataMutator              | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| DataMapper               | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Wildcards (*.path)       | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Deep Wildcards (*.*.*)   | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| AutoMap                  | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Template Mapping         | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |
| Hooks System             | ✅ Full     | ✅ Full                  | ✅ Full                   | ✅ Full                    | ✅ Full            |

**Legend:**
- ✅ **Full** - Complete functionality with all features
- ⚠️ **Basic** - Limited functionality via polyfill (sufficient for basic use cases)
- ⚠️ **Stub** - Minimal type checking only, no real functionality
- ❌ **None** - Not available without the dependency

## FAQ

### Q: Should I install illuminate/support?

**A:** If you're using Laravel, it's already installed. If you're not using Laravel and only need basic functionality, the polyfills are
sufficient.

### Q: Can I use Eloquent Models without illuminate/database?

**A:** The polyfill provides a minimal stub for type checking, but for real database functionality, you need `illuminate/database`.

### Q: What about performance?

**A:** Polyfills have minimal performance impact. The class existence check happens once at autoload time.

### Q: Can I contribute polyfill improvements?

**A:** Yes! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## See Also

- [Main README](README.md)
- [Polyfills Documentation](src/Polyfills/README.md)
- [Changelog](CHANGELOG.md)

