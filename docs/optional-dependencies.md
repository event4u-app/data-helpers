# Optional Framework Dependencies

## Overview

The package works standalone with PHP 8.2+ for arrays, objects, JSON, and XML. Framework-specific features require the respective framework
packages to be installed.

**Supported Frameworks:**

- **Laravel** (Collections, Eloquent Models)
- **Symfony/Doctrine** (Collections, Entities)
- **Standalone PHP** (Arrays, Objects, JSON, XML)

## Installation Options

### Option 1: Standalone (No Framework)

```bash
composer require event4u/data-helpers
```

**What you get:**

- ✅ Full DataAccessor functionality with arrays, objects, JSON, XML
- ✅ Full DataMutator functionality with arrays and objects
- ✅ Full DataMapper functionality
- ❌ No Laravel Collection support (requires illuminate/support)
- ❌ No Eloquent Model support (requires illuminate/database)
- ❌ No Doctrine Collection support (requires doctrine/collections)
- ❌ No Doctrine Entity support (requires doctrine/orm)

**Use this option when:**

- You only work with arrays, objects, JSON, or XML
- You don't need Laravel or Doctrine features

### Option 2: With Laravel Support (Recommended for Laravel Projects)

```bash
composer require event4u/data-helpers
composer require illuminate/support:^8
composer require illuminate/database:^8
```

**What you get:**

- ✅ Everything from Option 1
- ✅ Full Laravel Collection support with all methods (Laravel 9+)
- ✅ Full Eloquent Model support with database functionality (Laravel 9+)
- ✅ Full Arrayable interface support

**Use this option when:**

- You're working in a Laravel project (Laravel 9 or higher)
- You need to work with Laravel Collections or Eloquent Models

### Option 3: With Symfony/Doctrine Support (Recommended for Symfony Projects)

```bash
composer require event4u/data-helpers
composer require doctrine/collections:^1.6
composer require doctrine/orm:^2.10
```

**What you get:**

- ✅ Everything from Option 1
- ✅ Full Doctrine Collection support with all methods (Collections 1.6+)
- ✅ Full Doctrine Entity support with ORM functionality (ORM 2.10+)
- ✅ Automatic entity detection via attributes/annotations

**Use this option when:**

- You're working in a Symfony project
- You need to work with Doctrine Collections or Entities

### Option 4: Development/Testing (Automatic)

When you install the package for development:

```bash
git clone <repo>
composer install
```

Laravel and Doctrine packages are automatically installed as `require-dev` dependencies, ensuring all tests run with full framework support.

## Use Cases

### Use Case 1: Laravel Project

```php
// composer.json has illuminate/support installed
use event4u\DataHelpers\DataAccessor;
use Illuminate\Support\Collection;

$collection = new Collection(['name' => 'John', 'age' => 30]);
$accessor = new DataAccessor($collection);
$name = $accessor->get('name'); // "John"
```

**Result:** Works with Laravel Collection with full functionality.

### Use Case 2: Standalone PHP Project

```php
// No illuminate/support installed - use arrays instead
use event4u\DataHelpers\DataAccessor;

$data = ['name' => 'John', 'age' => 30];
$accessor = new DataAccessor($data);
$name = $accessor->get('name'); // "John"
```

**Result:** Works with plain arrays - no Laravel dependencies needed.

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
task test:run
```

## Performance

**Framework detection has minimal performance impact:**

- Class existence checks happen once per request
- No overhead when framework classes are not used
- Optimal performance with native framework implementations

## Compatibility Matrix

| Feature              | Standalone | With illuminate / support | With illuminate / database | With doctrine / collections | With doctrine / orm |
|----------------------|------------|---------------------------|----------------------------|-----------------------------|---------------------|
| Arrays               | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Objects              | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| JSON/XML             | ✅ Full     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Laravel Collections  | ❌ None     | ✅ Full                    | ✅ Full                     | ✅ Full                      | ✅ Full              |
| Doctrine Collections | ❌ None     | ❌ None                    | ❌ None                     | ✅ Full                      | ✅ Full              |
| Arrayable Interface  | ❌ None     | ✅ Full                    | ✅ Full                     | ❌ None                      | ❌ None              |
| Eloquent Models      | ❌ None     | ❌ None                    | ✅ Full                     | ❌ None                      | ❌ None              |
| Doctrine Entities    | ❌ None     | ❌ None                    | ❌ None                     | ⚠️ Basic                    | ✅ Full              |

**Legend:**

- ✅ **Full** - Complete functionality with all features
- ⚠️ **Basic** - Limited functionality (e.g., entities without full ORM support)
- ❌ **None** - Not available without the dependency

## FAQ

### Q: Should I install illuminate/support?

**A:** Only if you need Laravel Collection support. If you're using Laravel, it's already installed. If you're working with plain arrays,
you don't need it.

### Q: Can I use Eloquent Models without illuminate/database?

**A:** No. Eloquent Model support requires `illuminate/database` to be installed. Without it, you can only work with arrays and plain
objects.

### Q: Can I use Doctrine Collections without doctrine/collections?

**A:** No. Doctrine Collection support requires `doctrine/collections` to be installed. Without it, you can only work with arrays and plain
objects.

### Q: What about performance?

**A:** Framework detection has minimal performance impact. Class existence checks happen once per request.

## See Also

- [Main README](../README.md)
- [Changelog](../CHANGELOG.md)

