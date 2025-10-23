---
title: Symfony Console Commands
description: Complete reference of all Symfony Console commands for SimpleDTO
---

Complete reference of all Symfony Console commands for SimpleDTO.

## Introduction

SimpleDTO provides several console commands for Symfony:

- **make:dto** - Create new DTOs
- **dto:typescript** - Generate TypeScript types
- **dto:list** - List all DTOs
- **dto:validate** - Validate DTO structure
- **dto:cache** - Cache validation rules
- **dto:clear** - Clear validation cache

## make:dto

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

#### --validation

Create a DTO with validation attributes:

```bash
bin/console make:dto CreateUserDTO --validation
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

#### --entity

Create DTO from existing Doctrine entity:

```bash
bin/console make:dto UserDTO --entity=User
```

#### --force

Overwrite existing DTO:

```bash
bin/console make:dto UserDTO --force
```

## dto:typescript

Generate TypeScript types from DTOs.

### Basic Usage

```bash
bin/console dto:typescript
```

Generates TypeScript interfaces in `assets/types/dtos.ts`.

### Options

#### --output

Specify output file:

```bash
bin/console dto:typescript --output=assets/types/api.ts
```

#### --watch

Watch for changes and regenerate automatically:

```bash
bin/console dto:typescript --watch
```

#### --export

Specify export type:

```bash
bin/console dto:typescript --export=export  # export interface
bin/console dto:typescript --export=declare # declare interface
bin/console dto:typescript --export=        # no export
```

## dto:list

List all DTOs in the project.

### Basic Usage

```bash
bin/console dto:list
```

Output:
```
+------------------+------------------+------------+
| DTO              | Namespace        | Properties |
+------------------+------------------+------------+
| UserDTO          | App\DTO          | 5          |
| CreateUserDTO    | App\DTO\Requests | 3          |
| UserResourceDTO  | App\DTO\Resources| 7          |
+------------------+------------------+------------+
```

### Options

#### --namespace

Filter by namespace:

```bash
bin/console dto:list --namespace=App\\DTO\\Api
```

## dto:validate

Validate DTO structure and configuration.

### Basic Usage

```bash
bin/console dto:validate UserDTO
```

Checks:
- Class exists
- Extends SimpleDTO
- Properties are readonly
- Validation attributes are valid
- Type casts are valid
- No circular dependencies

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

## dto:cache

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

## dto:clear

Clear validation rule cache.

### Basic Usage

```bash
bin/console dto:clear
```

Clears all cached validation rules.

## Real-World Examples

### Create API DTOs

```bash
# Create request DTO
bin/console make:dto CreateUserDTO --validation

# Create response DTO
bin/console make:dto UserResourceDTO --resource

# Generate TypeScript
bin/console dto:typescript
```

### Development Workflow

```bash
# Create DTO from entity
bin/console make:dto OrderDTO --entity=Order

# Validate structure
bin/console dto:validate OrderDTO

# Generate TypeScript with watch
bin/console dto:typescript --watch
```

### CI/CD Pipeline

```bash
# Validate all DTOs
bin/console dto:validate --all

# Check TypeScript is up to date
bin/console dto:typescript --check

# Cache validation rules
bin/console dto:cache
```

### Deployment

```bash
# Clear old cache
bin/console dto:clear

# Cache validation rules
bin/console dto:cache

# Generate TypeScript
bin/console dto:typescript
```

## Combining Commands

### Create and Validate

```bash
bin/console make:dto UserDTO && bin/console dto:validate UserDTO
```

### Generate TypeScript and Watch

```bash
bin/console dto:typescript --watch &
```

## Best Practices

### 1. Use Validation in Development

```bash
# Always validate after creating DTOs
bin/console make:dto UserDTO
bin/console dto:validate UserDTO
```

### 2. Cache in Production

```bash
# Add to deployment script
bin/console dto:cache
```

### 3. Generate TypeScript Automatically

```bash
# Add to composer.json scripts
"scripts": {
    "post-install-cmd": [
        "@php bin/console dto:typescript"
    ]
}
```

### 4. Validate in CI/CD

```bash
# Add to CI/CD pipeline
bin/console dto:validate --all
bin/console dto:typescript --check
```

## See Also

- [Symfony Integration](/framework-integration/symfony/) - Symfony integration guide
- [TypeScript Generation](/simple-dto/typescript-generation/) - TypeScript generation details
- [Validation](/simple-dto/validation/) - Validation guide

