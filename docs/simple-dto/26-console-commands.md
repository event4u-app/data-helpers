# Console Commands

Complete reference of all Symfony Console commands for SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides several console commands for Symfony:

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
bin/console make:dto UserDTO
```

Creates `src/DTO/UserDTO.php`:

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
bin/console make:dto User/ProfileDTO --path=Api
```

Creates `src/DTO/Api/User/ProfileDTO.php`

#### --request

Create a request DTO with validation:

```bash
bin/console make:dto CreateUserDTO --request
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
bin/console make:dto UserResourceDTO --resource
```

Creates:

```php
<?php

namespace App\DTO;

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted;

class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenGranted('ROLE_USER')]
        public readonly ?string $email = null,
    ) {}
}
```

#### --entity

Create DTO from existing Doctrine entity:

```bash
bin/console make:dto UserDTO --entity=User
```

Analyzes the User entity and creates a DTO with matching properties.

#### --force

Overwrite existing DTO:

```bash
bin/console make:dto UserDTO --force
```

---

## 📝 dto:typescript

Generate TypeScript types from DTOs.

### Basic Usage

```bash
bin/console dto:typescript
```

Generates TypeScript files in `assets/types/`

### Options

#### --output

Specify output directory:

```bash
bin/console dto:typescript --output=assets/types
```

#### --namespace

Generate types for specific namespace:

```bash
bin/console dto:typescript --namespace=App\\DTO\\Api
```

#### --watch

Watch for changes and regenerate automatically:

```bash
bin/console dto:typescript --watch
```

#### --check

Check if types are up to date (useful for CI/CD):

```bash
bin/console dto:typescript --check
```

Returns exit code 1 if types are outdated.

#### --format

Specify output format:

```bash
bin/console dto:typescript --format=interface  # Default
bin/console dto:typescript --format=type
bin/console dto:typescript --format=class
```

---

## 📋 dto:list

List all DTOs in the project.

### Basic Usage

```bash
bin/console dto:list
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
bin/console dto:list --namespace=App\\DTO\\Api
```

#### --json

Output as JSON:

```bash
bin/console dto:list --json
```

Output:

```json
[
  {
    "name": "UserDTO",
    "namespace": "App\\DTO",
    "properties": 5,
    "path": "src/DTO/UserDTO.php"
  }
]
```

---

## ✅ dto:validate

Validate DTO structure and configuration.

### Basic Usage

```bash
bin/console dto:validate UserDTO
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
bin/console dto:validate --all
```

#### --fix

Attempt to fix common issues:

```bash
bin/console dto:validate UserDTO --fix
```

---

## 💾 dto:cache

Cache validation rules for better performance.

### Basic Usage

```bash
bin/console dto:cache
```

Caches validation rules for all DTOs, improving validation performance by up to 198x.

### Options

#### --clear

Clear cache before caching:

```bash
bin/console dto:cache --clear
```

---

## 🗑️ dto:clear

Clear validation rule cache.

### Basic Usage

```bash
bin/console dto:clear
```

Clears all cached validation rules.

---

## 🎯 Real-World Examples

### Example 1: Create API DTOs

```bash
# Create request DTO
bin/console make:dto CreateUserDTO --request --path=Api/Requests

# Create response DTO
bin/console make:dto UserResourceDTO --resource --path=Api/Resources

# Generate TypeScript
bin/console dto:typescript --namespace=App\\DTO\\Api
```

### Example 2: Development Workflow

```bash
# Create DTO
bin/console make:dto OrderDTO --entity=Order

# Validate structure
bin/console dto:validate OrderDTO

# Generate TypeScript
bin/console dto:typescript --watch
```

### Example 3: CI/CD Pipeline

```bash
# Validate all DTOs
bin/console dto:validate --all

# Check TypeScript is up to date
bin/console dto:typescript --check

# Cache validation rules
bin/console dto:cache
```

### Example 4: Deployment

```bash
# Clear old cache
bin/console dto:clear

# Cache validation rules
bin/console dto:cache

# Generate TypeScript
bin/console dto:typescript
```

---

## 🔄 Combining Commands

### Create and Validate

```bash
bin/console make:dto UserDTO && bin/console dto:validate UserDTO
```

### Generate TypeScript and Watch

```bash
bin/console dto:typescript --watch &
```

### List and Generate

```bash
bin/console dto:list --namespace=App\\DTO\\Api --json | jq -r '.[].name' | while read dto; do
  bin/console dto:typescript --namespace=App\\DTO\\Api
done
```

---

## 🛠️ Custom Commands

### Create Custom Command

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DtoStatsCommand extends Command
{
    protected static $defaultName = 'dto:stats';
    protected static $defaultDescription = 'Show DTO statistics';
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $dtos = $this->getDTOs();
        
        $io->success([
            "Total DTOs: " . count($dtos),
            "Total Properties: " . $this->getTotalProperties($dtos),
            "Average Properties: " . $this->getAverageProperties($dtos),
        ]);
        
        return Command::SUCCESS;
    }
    
    private function getDTOs(): array
    {
        // Implementation
    }
}
```

Register in `config/services.yaml`:

```yaml
services:
    App\Command\DtoStatsCommand:
        tags:
            - { name: console.command }
```

---

## 💡 Best Practices

### 1. Use Descriptive Names

```bash
# ✅ Good - descriptive names
bin/console make:dto CreateUserRequestDTO
bin/console make:dto UserResourceDTO

# ❌ Bad - generic names
bin/console make:dto UserDTO
bin/console make:dto User
```

### 2. Organize by Feature

```bash
# ✅ Good - organized by feature
bin/console make:dto User/CreateUserDTO --path=Api
bin/console make:dto User/UpdateUserDTO --path=Api
bin/console make:dto User/UserResourceDTO --path=Api

# ❌ Bad - flat structure
bin/console make:dto CreateUserDTO
bin/console make:dto UpdateUserDTO
bin/console make:dto UserResourceDTO
```

### 3. Generate TypeScript Regularly

```bash
# Add to composer.json scripts
"scripts": {
    "post-install-cmd": [
        "@php bin/console dto:typescript"
    ],
    "post-update-cmd": [
        "@php bin/console dto:typescript"
    ]
}
```

### 4. Cache in Production

```bash
# Add to deployment script
bin/console dto:cache
```

### 5. Validate in CI/CD

```bash
# Add to CI/CD pipeline
bin/console dto:validate --all
bin/console dto:typescript --check
```

---

## 🎨 Aliases

Add aliases to your shell configuration:

```bash
# ~/.bashrc or ~/.zshrc

alias dto:make='bin/console make:dto'
alias dto:ts='bin/console dto:typescript'
alias dto:ls='bin/console dto:list'
alias dto:check='bin/console dto:validate'
```

Usage:

```bash
dto:make UserDTO
dto:ts --watch
dto:ls
dto:check UserDTO
```

---

## 🔧 Makefile Integration

Create a `Makefile`:

```makefile
.PHONY: dto-make dto-ts dto-list dto-validate dto-cache dto-clear

dto-make:
	bin/console make:dto $(name)

dto-ts:
	bin/console dto:typescript

dto-ts-watch:
	bin/console dto:typescript --watch

dto-list:
	bin/console dto:list

dto-validate:
	bin/console dto:validate --all

dto-cache:
	bin/console dto:cache

dto-clear:
	bin/console dto:clear

dto-deploy:
	bin/console dto:clear
	bin/console dto:cache
	bin/console dto:typescript
```

Usage:

```bash
make dto-make name=UserDTO
make dto-ts-watch
make dto-list
make dto-validate
make dto-deploy
```

---

## 📚 Next Steps

1. [Artisan Commands](25-artisan-commands.md) - Laravel commands
2. [TypeScript Generation](23-typescript-generation.md) - TypeScript details
3. [IDE Support](24-ide-support.md) - IDE configuration
4. [Best Practices](29-best-practices.md) - Tips and recommendations

---

**Previous:** [Artisan Commands](25-artisan-commands.md)  
**Next:** [Performance](27-performance.md)

