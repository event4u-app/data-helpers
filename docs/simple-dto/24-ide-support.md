# IDE Support

Learn how to configure your IDE for the best SimpleDTO development experience.

---

## 🎯 Overview

SimpleDTO provides excellent IDE support with:

- ✅ **Full Autocompletion** - All properties and methods
- ✅ **Type Inference** - Automatic type detection
- ✅ **PHPDoc Support** - Rich documentation
- ✅ **Attribute Support** - PHP 8 attributes
- ✅ **Navigation** - Jump to definition
- ✅ **Refactoring** - Safe renaming and moving

---

## 🚀 PhpStorm / IntelliJ IDEA

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

---

## 💻 VS Code

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
    "apache",
    "bcmath",
    "Core",
    "ctype",
    "curl",
    "date",
    "dom",
    "fileinfo",
    "filter",
    "ftp",
    "gd",
    "hash",
    "iconv",
    "json",
    "libxml",
    "mbstring",
    "mysqli",
    "mysqlnd",
    "openssl",
    "pcre",
    "PDO",
    "pdo_mysql",
    "Phar",
    "SimpleXML",
    "sockets",
    "sodium",
    "SPL",
    "standard",
    "tokenizer",
    "xml",
    "xmlreader",
    "xmlwriter",
    "zip",
    "zlib"
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

---

## 🎨 PHPDoc for Better IDE Support

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
     * 
     * @return string
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    /**
     * Get user's orders
     * 
     * @return OrderDTO[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }
}
```

### Document Complex Types

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        /**
         * Order items with product details
         * @var array<int, array{product: ProductDTO, quantity: int, price: float}>
         */
        public readonly array $items,
    ) {}
}
```

---

## 🔧 IDE Helper Files

### Laravel IDE Helper

Install Laravel IDE Helper for better autocompletion:

```bash
composer require --dev barryvdh/laravel-ide-helper
```

Generate helper files:

```bash
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

### Custom IDE Helper

Create `_ide_helper_dtos.php`:

```php
<?php

namespace PHPSTORM_META {
    
    override(\event4u\DataHelpers\SimpleDTO::fromArray(0), map([
        '' => '@',
    ]));
    
    override(\event4u\DataHelpers\SimpleDTO::fromJson(0), map([
        '' => '@',
    ]));
    
    override(\event4u\DataHelpers\SimpleDTO::fromModel(0), map([
        '' => '@',
    ]));
}
```

---

## 🎯 Code Snippets

### PhpStorm Live Templates

Create live templates for common DTO patterns:

1. Go to `Settings` → `Editor` → `Live Templates`
2. Click `+` to add new template

#### DTO Class Template

Abbreviation: `dto`

```php
class $NAME$DTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

#### DTO Property Template

Abbreviation: `dtoprop`

```php
public readonly $TYPE$ $$$NAME$,
```

### VS Code Snippets

Create `.vscode/php.code-snippets`:

```json
{
  "DTO Class": {
    "prefix": "dto",
    "body": [
      "class ${1:Name}DTO extends SimpleDTO",
      "{",
      "    public function __construct(",
      "        public readonly int \\$id,",
      "        public readonly string \\$name,",
      "    ) {}",
      "}"
    ]
  },
  "DTO Property": {
    "prefix": "dtoprop",
    "body": [
      "public readonly ${1:string} \\$${2:name},"
    ]
  }
}
```

---

## 🎨 Code Style

### PHP CS Fixer Configuration

Create `.php-cs-fixer.php`:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src/DTO')
    ->name('*DTO.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
    ])
    ->setFinder($finder);
```

### PHP_CodeSniffer Configuration

Create `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="DTO Coding Standard">
    <file>src/DTO</file>
    
    <rule ref="PSR12"/>
    
    <rule ref="Generic.Arrays.DisallowLongArraySyntax"/>
    <rule ref="Generic.PHP.RequireStrictTypes"/>
</ruleset>
```

---

## 🔍 Static Analysis

### PHPStan Configuration

Create `phpstan.neon`:

```neon
parameters:
    level: 8
    paths:
        - src/DTO
    
    ignoreErrors:
        # Ignore readonly property initialization
        - '#Property .* is never written, only read#'
```

Run PHPStan:

```bash
vendor/bin/phpstan analyse
```

### Psalm Configuration

Create `psalm.xml`:

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="3"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src/DTO" />
    </projectFiles>
</psalm>
```

Run Psalm:

```bash
vendor/bin/psalm
```

---

## 💡 Best Practices

### 1. Use Type Hints

```php
// ✅ Good - type hinted
public readonly string $name

// ❌ Bad - no type hint
public readonly $name
```

### 2. Document Array Types

```php
// ✅ Good - documented array type
/** @var UserDTO[] */
public readonly array $users

// ❌ Bad - undocumented array
public readonly array $users
```

### 3. Use PHPDoc for Complex Types

```php
// ✅ Good - documented complex type
/**
 * @var array<string, array{id: int, name: string}>
 */
public readonly array $data

// ❌ Bad - undocumented complex type
public readonly array $data
```

### 4. Enable Strict Types

```php
// ✅ Good - strict types enabled
<?php

declare(strict_types=1);

namespace App\DTO;

// ❌ Bad - no strict types
<?php

namespace App\DTO;
```

### 5. Use IDE Helper Files

```bash
# Generate IDE helper files regularly
php artisan ide-helper:generate
php artisan ide-helper:models
```

---

## 🎯 Keyboard Shortcuts

### PhpStorm

- `Ctrl+Space` - Basic code completion
- `Ctrl+Shift+Space` - Smart code completion
- `Ctrl+P` - Parameter info
- `Ctrl+Q` - Quick documentation
- `Ctrl+B` - Go to declaration
- `Ctrl+Alt+B` - Go to implementation
- `Shift+F6` - Rename
- `F2` - Next highlighted error

### VS Code

- `Ctrl+Space` - Trigger suggest
- `Ctrl+Shift+Space` - Trigger parameter hints
- `F12` - Go to definition
- `Alt+F12` - Peek definition
- `Shift+F12` - Go to references
- `F2` - Rename symbol
- `F8` - Go to next error

---

## 📚 Next Steps

1. [Artisan Commands](25-artisan-commands.md) - Laravel commands
2. [Console Commands](26-console-commands.md) - Symfony commands
3. [TypeScript Generation](23-typescript-generation.md) - Generate TypeScript types
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [TypeScript Generation](23-typescript-generation.md)  
**Next:** [Artisan Commands](25-artisan-commands.md)

