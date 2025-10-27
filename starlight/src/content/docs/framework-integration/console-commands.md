---
title: Symfony Console Commands
description: Complete reference of all Symfony Console commands for SimpleDto
---

Complete reference of all Symfony Console commands for SimpleDto.

## Introduction

SimpleDto provides several console commands for Symfony:

- **make:dto** - Create new Dtos
- **dto:typescript** - Generate TypeScript types
- **dto:list** - List all Dtos
- **dto:validate** - Validate Dto structure
- **dto:cache** - Cache validation rules
- **dto:clear** - Clear validation cache

## make:dto

Create a new Dto class.

### Basic Usage

```bash
bin/console make:dto UserDto
```

Creates `src/Dto/UserDto.php`:

```php
<?php

namespace App\Dto;

use event4u\DataHelpers\SimpleDto;

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

### Options

#### --validation

Create a Dto with validation attributes:

```bash
bin/console make:dto CreateUserDto --validation
```

Creates:

```php
<?php

namespace App\Dto;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Email;

class CreateUserDto extends SimpleDto
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

Create a resource Dto for API responses:

```bash
bin/console make:dto UserResourceDto --resource
```

#### --entity

Create Dto from existing Doctrine entity:

```bash
bin/console make:dto UserDto --entity=User
```

#### --force

Overwrite existing Dto:

```bash
bin/console make:dto UserDto --force
```

## dto:typescript

Generate TypeScript types from Dtos.

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

List all Dtos in the project.

### Basic Usage

```bash
bin/console dto:list
```

Output:
```
+------------------+------------------+------------+
| Dto              | Namespace        | Properties |
+------------------+------------------+------------+
| UserDto          | App\Dto          | 5          |
| CreateUserDto    | App\Dto\Requests | 3          |
| UserResourceDto  | App\Dto\Resources| 7          |
+------------------+------------------+------------+
```

### Options

#### --namespace

Filter by namespace:

```bash
bin/console dto:list --namespace=App\\Dto\\Api
```

## dto:validate

Validate Dto structure and configuration.

### Basic Usage

```bash
bin/console dto:validate UserDto
```

Checks:
- Class exists
- Extends SimpleDto
- Properties are readonly
- Validation attributes are valid
- Type casts are valid
- No circular dependencies

### Options

#### --all

Validate all Dtos:

```bash
bin/console dto:validate --all
```

#### --fix

Attempt to fix common issues:

```bash
bin/console dto:validate UserDto --fix
```

## dto:cache

Cache validation rules for better performance.

### Basic Usage

```bash
bin/console dto:cache
```

Caches validation rules for all Dtos, improving validation performance by up to 198x.

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

### Create API Dtos

```bash
# Create request Dto
bin/console make:dto CreateUserDto --validation

# Create response Dto
bin/console make:dto UserResourceDto --resource

# Generate TypeScript
bin/console dto:typescript
```

### Development Workflow

```bash
# Create Dto from entity
bin/console make:dto OrderDto --entity=Order

# Validate structure
bin/console dto:validate OrderDto

# Generate TypeScript with watch
bin/console dto:typescript --watch
```

### CI/CD Pipeline

```bash
# Validate all Dtos
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
bin/console make:dto UserDto && bin/console dto:validate UserDto
```

### Generate TypeScript and Watch

```bash
bin/console dto:typescript --watch &
```

## Best Practices

### 1. Use Validation in Development

```bash
# Always validate after creating Dtos
bin/console make:dto UserDto
bin/console dto:validate UserDto
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

