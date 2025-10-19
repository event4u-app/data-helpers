# Artisan Commands

Complete reference of all Laravel Artisan commands for SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides several Artisan commands for Laravel:

- ✅ **make:dto** - Create new DTOs
- ✅ **dto:typescript** - Generate TypeScript types
- ✅ **dto:list** - List all DTOs
- ✅ **dto:validate** - Validate DTO structure
- ✅ **dto:cache** - Cache validation rules
- ✅ **dto:clear** - Clear validation cache

---

## 🚀 make:dto

Create a new DTO class.

### Basic Usage

```bash
php artisan make:dto UserDTO
```

Creates `app/DTO/UserDTO.php`:

```php
<?php

namespace App\DTO;

use event4u\DataHelpers\SimpleDTO;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

### Options

#### --path

Create DTO in a subdirectory:

```bash
php artisan make:dto User/ProfileDTO --path=Api
```

Creates `app/DTO/Api/User/ProfileDTO.php`

#### --request

Create a request DTO with validation:

```bash
php artisan make:dto CreateUserDTO --request
```

Creates:

```php
<?php

namespace App\DTO;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
    ) {}
}
```

#### --resource

Create a resource DTO for API responses:

```bash
php artisan make:dto UserResourceDTO --resource
```

Creates:

```php
<?php

namespace App\DTO;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;

class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}
```

#### --model

Create DTO from existing Eloquent model:

```bash
php artisan make:dto UserDTO --model=User
```

Analyzes the User model and creates a DTO with matching properties.

#### --force

Overwrite existing DTO:

```bash
php artisan make:dto UserDTO --force
```

---

## 📝 dto:typescript

Generate TypeScript types from DTOs.

### Basic Usage

```bash
php artisan dto:typescript
```

Generates TypeScript files in `resources/js/types/`

### Options

#### --output

Specify output directory:

```bash
php artisan dto:typescript --output=resources/js/types
```

#### --namespace

Generate types for specific namespace:

```bash
php artisan dto:typescript --namespace=App\\DTO\\Api
```

#### --watch

Watch for changes and regenerate automatically:

```bash
php artisan dto:typescript --watch
```

#### --check

Check if types are up to date (useful for CI/CD):

```bash
php artisan dto:typescript --check
```

Returns exit code 1 if types are outdated.

#### --format

Specify output format:

```bash
php artisan dto:typescript --format=interface  # Default
php artisan dto:typescript --format=type
php artisan dto:typescript --format=class
```

---

## 📋 dto:list

List all DTOs in the project.

### Basic Usage

```bash
php artisan dto:list
```

Output:

```
+---------------------------+------------------+------------+
| DTO                       | Namespace        | Properties |
+---------------------------+------------------+------------+
| UserDTO                   | App\DTO          | 5          |
| CreateUserDTO             | App\DTO          | 3          |
| UserResourceDTO           | App\DTO          | 4          |
| PostDTO                   | App\DTO          | 6          |
| OrderDTO                  | App\DTO\Api      | 8          |
+---------------------------+------------------+------------+
```

### Options

#### --namespace

Filter by namespace:

```bash
php artisan dto:list --namespace=App\\DTO\\Api
```

#### --json

Output as JSON:

```bash
php artisan dto:list --json
```

Output:

```json
[
  {
    "name": "UserDTO",
    "namespace": "App\\DTO",
    "properties": 5,
    "path": "app/DTO/UserDTO.php"
  }
]
```

---

## ✅ dto:validate

Validate DTO structure and configuration.

### Basic Usage

```bash
php artisan dto:validate UserDTO
```

Checks:
- ✅ Class exists
- ✅ Extends SimpleDTO
- ✅ Properties are readonly
- ✅ Validation attributes are valid
- ✅ Type casts are valid
- ✅ No circular dependencies

### Options

#### --all

Validate all DTOs:

```bash
php artisan dto:validate --all
```

#### --fix

Attempt to fix common issues:

```bash
php artisan dto:validate UserDTO --fix
```

---

## 💾 dto:cache

Cache validation rules for better performance.

### Basic Usage

```bash
php artisan dto:cache
```

Caches validation rules for all DTOs, improving validation performance by up to 198x.

### Options

#### --clear

Clear cache before caching:

```bash
php artisan dto:cache --clear
```

---

## 🗑️ dto:clear

Clear validation rule cache.

### Basic Usage

```bash
php artisan dto:clear
```

Clears all cached validation rules.

---

## 🎯 Real-World Examples

### Example 1: Create API DTOs

```bash
# Create request DTO
php artisan make:dto CreateUserDTO --request --path=Api/Requests

# Create response DTO
php artisan make:dto UserResourceDTO --resource --path=Api/Resources

# Generate TypeScript
php artisan dto:typescript --namespace=App\\DTO\\Api
```

### Example 2: Development Workflow

```bash
# Create DTO
php artisan make:dto OrderDTO --model=Order

# Validate structure
php artisan dto:validate OrderDTO

# Generate TypeScript
php artisan dto:typescript --watch
```

### Example 3: CI/CD Pipeline

```bash
# Validate all DTOs
php artisan dto:validate --all

# Check TypeScript is up to date
php artisan dto:typescript --check

# Cache validation rules
php artisan dto:cache
```

### Example 4: Deployment

```bash
# Clear old cache
php artisan dto:clear

# Cache validation rules
php artisan dto:cache

# Generate TypeScript
php artisan dto:typescript
```

---

## 🔄 Combining Commands

### Create and Validate

```bash
php artisan make:dto UserDTO && php artisan dto:validate UserDTO
```

### Generate TypeScript and Watch

```bash
php artisan dto:typescript --watch &
```

### List and Generate

```bash
php artisan dto:list --namespace=App\\DTO\\Api | grep -v "^+" | tail -n +2 | while read dto; do
  php artisan dto:typescript --namespace=App\\DTO\\Api
done
```

---

## 🛠️ Custom Commands

### Create Custom Command

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DtoStatsCommand extends Command
{
    protected $signature = 'dto:stats';
    protected $description = 'Show DTO statistics';
    
    public function handle()
    {
        $dtos = $this->getDTOs();
        
        $this->info("Total DTOs: " . count($dtos));
        $this->info("Total Properties: " . $this->getTotalProperties($dtos));
        $this->info("Average Properties: " . $this->getAverageProperties($dtos));
    }
    
    private function getDTOs(): array
    {
        // Implementation
    }
}
```

---

## 💡 Best Practices

### 1. Use Descriptive Names

```bash
# ✅ Good - descriptive names
php artisan make:dto CreateUserRequestDTO
php artisan make:dto UserResourceDTO

# ❌ Bad - generic names
php artisan make:dto UserDTO
php artisan make:dto User
```

### 2. Organize by Feature

```bash
# ✅ Good - organized by feature
php artisan make:dto User/CreateUserDTO --path=Api
php artisan make:dto User/UpdateUserDTO --path=Api
php artisan make:dto User/UserResourceDTO --path=Api

# ❌ Bad - flat structure
php artisan make:dto CreateUserDTO
php artisan make:dto UpdateUserDTO
php artisan make:dto UserResourceDTO
```

### 3. Generate TypeScript Regularly

```bash
# Add to composer.json scripts
"scripts": {
    "post-autoload-dump": [
        "@php artisan dto:typescript"
    ]
}
```

### 4. Cache in Production

```bash
# Add to deployment script
php artisan dto:cache
```

### 5. Validate in CI/CD

```bash
# Add to CI/CD pipeline
php artisan dto:validate --all
php artisan dto:typescript --check
```

---

## 🎨 Aliases

Add aliases to your shell configuration:

```bash
# ~/.bashrc or ~/.zshrc

alias dto:make='php artisan make:dto'
alias dto:ts='php artisan dto:typescript'
alias dto:ls='php artisan dto:list'
alias dto:check='php artisan dto:validate'
```

Usage:

```bash
dto:make UserDTO
dto:ts --watch
dto:ls
dto:check UserDTO
```

---

## 📚 Next Steps

1. [Console Commands](26-console-commands.md) - Symfony commands
2. [TypeScript Generation](23-typescript-generation.md) - TypeScript details
3. [IDE Support](24-ide-support.md) - IDE configuration
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [IDE Support](24-ide-support.md)  
**Next:** [Console Commands](26-console-commands.md)

