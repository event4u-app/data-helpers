---
title: IDE Support
description: Configure your IDE for the best SimpleDTO development experience
---

Learn how to configure your IDE for the best SimpleDTO development experience.

## Overview

SimpleDTO provides excellent IDE support with:

- **Full Autocompletion** - All properties and methods
- **Type Inference** - Automatic type detection
- **PHPDoc Support** - Rich documentation
- **Attribute Support** - PHP 8 attributes
- **Navigation** - Jump to definition
- **Refactoring** - Safe renaming and moving

## PhpStorm / IntelliJ IDEA

### Installation

PhpStorm has built-in support for PHP 8 attributes and readonly properties.

### Configuration

1. **Enable PHP 8.2+ Support**
   - Go to `Settings` → `PHP`
   - Set `PHP language level` to `8.2` or higher

2. **Install Laravel Idea Plugin** (for Laravel projects)
   - Go to `Settings` → `Plugins`
   - Search for "Laravel Idea"
   - Install and restart

3. **Install Symfony Plugin** (for Symfony projects)
   - Go to `Settings` → `Plugins`
   - Search for "Symfony Support"
   - Install and restart

### Features

#### Autocompletion

```php
$dto = UserDTO::fromArray([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// PhpStorm provides autocompletion for:
$dto->name // ✅ Autocompletes
$dto->email // ✅ Autocompletes
$dto->toArray() // ✅ Autocompletes
$dto->toJson() // ✅ Autocompletes
```

#### Type Inference

```php
// PhpStorm knows the return type
$dto = UserDTO::fromArray($data); // UserDTO
$array = $dto->toArray(); // array
$json = $dto->toJson(); // string
```

#### Navigation

- `Ctrl+Click` (or `Cmd+Click` on Mac) on property to jump to definition
- `Ctrl+B` to go to declaration
- `Ctrl+Alt+B` to go to implementation

#### Refactoring

- Right-click property → `Refactor` → `Rename` to safely rename
- Right-click class → `Refactor` → `Move` to move to different namespace

## VS Code

### Installation

Install the following extensions:

1. **PHP Intelephense**
   ```bash
   ext install bmewburn.vscode-intelephense-client
   ```

2. **PHP DocBlocker**
   ```bash
   ext install neilbrayfield.php-docblocker
   ```

3. **Laravel Extra Intellisense** (for Laravel)
   ```bash
   ext install amiralizadeh9480.laravel-extra-intellisense
   ```

### Configuration

Create `.vscode/settings.json`:

```json
{
  "php.suggest.basic": false,
  "intelephense.stubs": [
    "Core",
    "PDO",
    "SPL",
    "standard"
  ],
  "intelephense.environment.phpVersion": "8.2.0",
  "intelephense.completion.triggerParameterHints": true,
  "intelephense.completion.insertUseDeclaration": true,
  "intelephense.format.enable": true
}
```

### Features

#### Autocompletion

Works the same as PhpStorm with Intelephense.

#### Type Hints

Hover over any property or method to see type information.

#### Go to Definition

- `F12` to go to definition
- `Ctrl+Click` to jump to definition

## PHPDoc for Better IDE Support

### Document Array Types

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        /** @var string[] */
        public readonly array $tags,
        
        /** @var OrderDTO[] */
        public readonly array $orders,
        
        /** @var array<string, mixed> */
        public readonly array $metadata,
    ) {}
}
```

### Document Return Types

```php
class UserDTO extends SimpleDTO
{
    // ...
    
    /**
     * Get user's full name
     * @return string
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### Document Complex Types

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        /** @var array<int, ProductDTO> */
        public readonly array $products,
        
        /** @var array{total: float, tax: float, shipping: float} */
        public readonly array $pricing,
    ) {}
}
```

## PHPStan Integration

### Installation

```bash
composer require --dev phpstan/phpstan
```

### Configuration

Create `phpstan.neon`:

```neon
parameters:
    level: 9
    paths:
        - app
        - src
    
    # Enable strict rules
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
```

### Run PHPStan

```bash
vendor/bin/phpstan analyse
```

PHPStan will validate:
- All properties are typed
- All methods have return types
- No undefined properties
- No type mismatches

## Best Practices

### 1. Always Use Type Hints

```php
// ✅ Good
public readonly string $name;

// ❌ Bad
public readonly $name;
```

### 2. Document Array Types

```php
// ✅ Good
/** @var string[] */
public readonly array $tags;

// ❌ Bad
public readonly array $tags;
```

### 3. Use Readonly Properties

```php
// ✅ Good
public readonly string $name;

// ❌ Bad
public string $name;
```

### 4. Use PHP 8.2+ Features

```php
// ✅ Good - PHP 8.2+
public readonly string $name;

// ❌ Bad - Old style
/** @var string */
private $name;
```

## Troubleshooting

### IDE Not Recognizing Properties

**Problem:**
```php
$dto->name // IDE shows "Property not found"
```

**Solution:**
1. Clear IDE cache (PhpStorm: `File` → `Invalidate Caches`)
2. Rebuild project index
3. Check PHP language level is 8.2+

### Autocompletion Not Working

**Problem:**
Autocompletion doesn't show DTO properties.

**Solution:**
1. Make sure all properties are `public readonly`
2. Check IDE has indexed the project
3. Verify PHP version in IDE settings

### Type Hints Not Showing

**Problem:**
Hover doesn't show type information.

**Solution:**
1. Install PHP Intelephense (VS Code)
2. Enable type hints in IDE settings
3. Add PHPDoc comments for complex types

## See Also

- [Creating DTOs](/simple-dto/creating-dtos/) - DTO creation guide
- [TypeScript Generation](/simple-dto/typescript-generation/) - Generate TypeScript types
- [Validation](/simple-dto/validation/) - Validation guide

