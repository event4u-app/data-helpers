# Installation

This guide will help you install and configure SimpleDTO in your project.

---

## 📋 Requirements

- **PHP:** 8.2 or higher
- **Composer:** 2.0 or higher
- **Framework:** Optional (Laravel 10+, Symfony 6+)

---

## 🚀 Installation

### Via Composer

```bash
composer require event4u/data-helpers
```

That's it! SimpleDTO is now installed and ready to use.

---

## ⚙️ Configuration

### Framework-Agnostic (Plain PHP)

No configuration needed! Just start using SimpleDTO:

```php
<?php

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

---

### Laravel Configuration

#### 1. Service Provider (Auto-Discovery)

Laravel will automatically discover the service provider. No manual registration needed!

#### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=simple-dto-config
```

This creates `config/simple-dto.php`:

```php
<?php

return [
    // Enable validation caching for better performance
    'validation' => [
        'cache_rules' => true,
        'cache_ttl' => 3600, // 1 hour
    ],
    
    // Enable cast instance caching
    'casts' => [
        'cache_instances' => true,
    ],
    
    // TypeScript generation settings
    'typescript' => [
        'output_path' => resource_path('js/types'),
        'namespace' => 'App\\DTOs',
    ],
];
```

#### 3. Artisan Commands

SimpleDTO provides several Artisan commands:

```bash
# Create a new DTO
php artisan make:dto UserDTO

# Generate TypeScript types
php artisan dto:typescript

# List all DTOs
php artisan dto:list

# Validate DTO structure
php artisan dto:validate UserDTO
```

#### 4. Eloquent Integration

Add the trait to your models:

```php
use event4u\DataHelpers\SimpleDTO\Traits\SimpleDTOEloquentTrait;

class User extends Model
{
    use SimpleDTOEloquentTrait;
    
    // ...
}

// Now you can use:
$dto = UserDTO::fromModel($user);
$user = $dto->toModel(User::class);
```

---

### Symfony Configuration

#### 1. Register Bundle (Auto-Configuration)

Symfony will automatically configure the bundle. No manual registration needed!

#### 2. Configuration (Optional)

Create `config/packages/simple_dto.yaml`:

```yaml
simple_dto:
  validation:
    cache_rules: true
    cache_ttl: 3600
  
  casts:
    cache_instances: true
  
  typescript:
    output_path: '%kernel.project_dir%/assets/types'
    namespace: 'App\DTO'
```

#### 3. Console Commands

SimpleDTO provides several console commands:

```bash
# Create a new DTO
bin/console make:dto UserDTO

# Generate TypeScript types
bin/console dto:typescript

# List all DTOs
bin/console dto:list

# Validate DTO structure
bin/console dto:validate UserDTO
```

#### 4. Doctrine Integration

Add the trait to your entities:

```php
use event4u\DataHelpers\SimpleDTO\Traits\SimpleDTODoctrineTrait;

#[Entity]
class User
{
    use SimpleDTODoctrineTrait;
    
    // ...
}

// Now you can use:
$dto = UserDTO::fromEntity($user);
$user = $dto->toEntity(User::class);
```

---

## 🔧 Optional Dependencies

SimpleDTO has zero required dependencies, but you can install optional packages for additional features:

### For Laravel

```bash
# Eloquent integration
composer require illuminate/database

# Validation
composer require illuminate/validation

# HTTP integration
composer require illuminate/http
```

### For Symfony

```bash
# Doctrine integration
composer require doctrine/orm

# Validation
composer require symfony/validator

# Security integration
composer require symfony/security-core
```

### For TypeScript Generation

```bash
# TypeScript generator
composer require event4u/typescript-generator
```

### For Advanced Serialization

```bash
# XML support
composer require ext-simplexml

# YAML support
composer require symfony/yaml

# CSV support (built-in, no package needed)
```

---

## ✅ Verify Installation

### Test Basic Functionality

Create a test file `test-dto.php`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

class TestDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

$dto = TestDTO::fromArray([
    'name' => 'John Doe',
    'age' => 30,
]);

echo "Name: {$dto->name}\n";
echo "Age: {$dto->age}\n";
echo "JSON: " . $dto->toJson() . "\n";

echo "\n✅ SimpleDTO is working!\n";
```

Run it:

```bash
php test-dto.php
```

Expected output:

```
Name: John Doe
Age: 30
JSON: {"name":"John Doe","age":30}

✅ SimpleDTO is working!
```

---

## 🎯 IDE Support

### PHPStorm

1. Install the **PHP Annotations** plugin
2. Enable **PHP 8.2** language level
3. Mark `vendor/event4u/data-helpers/src` as source root

### VS Code

1. Install **PHP Intelephense** extension
2. Add to `settings.json`:

```json
{
  "intelephense.stubs": [
    "Core",
    "standard",
    "pcre",
    "json"
  ],
  "php.suggest.basic": false
}
```

---

## 🐛 Troubleshooting

### Issue: "Class SimpleDTO not found"

**Solution:** Make sure you've run `composer install` and the autoloader is included:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### Issue: "Trait SimpleDTOTrait not found"

**Solution:** Check your namespace:

```php
use event4u\DataHelpers\SimpleDTO;  // Correct
// not: use SimpleDTO;  // Wrong
```

### Issue: "Call to undefined method fromArray()"

**Solution:** Make sure your DTO extends `SimpleDTO`:

```php
class UserDTO extends SimpleDTO  // Correct
{
    // ...
}
```

### Issue: Laravel commands not available

**Solution:** Clear the cache:

```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

### Issue: Symfony commands not available

**Solution:** Clear the cache:

```bash
bin/console cache:clear
composer dump-autoload
```

---

## 📚 Next Steps

Now that SimpleDTO is installed, let's create your first DTO:

1. [Quick Start](03-quick-start.md) - Create your first DTO in 5 minutes
2. [Basic Usage](04-basic-usage.md) - Learn the core concepts
3. [Creating DTOs](05-creating-dtos.md) - Different ways to create DTOs

### Framework-Specific Guides

- **Laravel:** [Laravel Integration](17-laravel-integration.md)
- **Symfony:** [Symfony Integration](18-symfony-integration.md)
- **Plain PHP:** [Plain PHP Usage](19-plain-php.md)

---

**Previous:** [Introduction](01-introduction.md)  
**Next:** [Quick Start](03-quick-start.md)

