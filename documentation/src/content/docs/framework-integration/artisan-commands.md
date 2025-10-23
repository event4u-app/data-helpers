---
title: Laravel Artisan Commands
description: Complete reference of all Laravel Artisan commands for SimpleDTO
---

Complete reference of all Laravel Artisan commands for SimpleDTO.

## Overview

SimpleDTO provides several Artisan commands for Laravel:

- **make:dto** - Create new DTOs
- **dto:typescript** - Generate TypeScript types
- **dto:migrate-spatie** - Migrate Spatie Laravel Data to SimpleDTO
- **dto:list** - List all DTOs
- **dto:validate** - Validate DTO structure
- **dto:cache** - Cache validation rules
- **dto:clear** - Clear validation cache

## make:dto

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

#### --validation

Create a DTO with validation attributes:

```bash
php artisan make:dto CreateUserDTO --validation
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

#### --force

Overwrite existing DTO:

```bash
php artisan make:dto UserDTO --force
```

## dto:typescript

Generate TypeScript types from DTOs.

### Basic Usage

```bash
php artisan dto:typescript
```

Generates TypeScript interfaces in `resources/js/types/dtos.ts`.

### Options

#### --output

Specify output file:

```bash
php artisan dto:typescript --output=resources/js/types/api.ts
```

#### --watch

Watch for changes and regenerate automatically:

```bash
php artisan dto:typescript --watch
```

#### --export

Specify export type:

```bash
php artisan dto:typescript --export=export  # export interface
php artisan dto:typescript --export=declare # declare interface
php artisan dto:typescript --export=        # no export
```

## dto:migrate-spatie

Migrate Spatie Laravel Data classes to SimpleDTO.

### Basic Usage

```bash
php artisan dto:migrate-spatie
```

Migrates all Spatie Data classes in `app/Data` directory.

### Options

#### --path

Specify directory to scan:

```bash
php artisan dto:migrate-spatie --path=app/Data/Api
```

#### --dry-run

Preview changes without modifying files:

```bash
php artisan dto:migrate-spatie --dry-run
```

#### --backup

Create backup files before migration:

```bash
php artisan dto:migrate-spatie --backup
```

This creates `.backup` files for each migrated file.

#### --force

Skip confirmation prompt:

```bash
php artisan dto:migrate-spatie --force
```

### What it does

The command automatically:

1. Finds all Spatie Data classes
2. Replaces `Data` with `SimpleDTO` base class
3. Updates namespace imports
4. Adds `readonly` to properties
5. Updates attribute namespaces
6. Replaces `WithCast` with `Cast` attribute

### Example

**Before migration:**
```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,
        public string $email,
    ) {}
}
```

**After migration:**
```php
use event4u\DataHelpers\SimpleDTO\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;

class UserData extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

**See also:** [Migration from Spatie](/guides/migration-from-spatie/) - Complete migration guide

## dto:list

List all DTOs in the project.

### Basic Usage

```bash
php artisan dto:list
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
php artisan dto:list --namespace=App\\DTO\\Api
```

## dto:validate

Validate DTO structure and configuration.

### Basic Usage

```bash
php artisan dto:validate UserDTO
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
php artisan dto:validate --all
```

#### --fix

Attempt to fix common issues:

```bash
php artisan dto:validate UserDTO --fix
```

## dto:cache

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

## dto:clear

Clear validation rule cache.

### Basic Usage

```bash
php artisan dto:clear
```

Clears all cached validation rules.

## Real-World Examples

### Create API DTOs

```bash
# Create request DTO
php artisan make:dto CreateUserDTO --validation

# Create response DTO
php artisan make:dto UserResourceDTO --resource

# Generate TypeScript
php artisan dto:typescript
```

### Development Workflow

```bash
# Create DTO
php artisan make:dto OrderDTO

# Validate structure
php artisan dto:validate OrderDTO

# Generate TypeScript with watch
php artisan dto:typescript --watch
```

### CI/CD Pipeline

```bash
# Validate all DTOs
php artisan dto:validate --all

# Check TypeScript is up to date
php artisan dto:typescript --check

# Cache validation rules
php artisan dto:cache
```

### Deployment

```bash
# Clear old cache
php artisan dto:clear

# Cache validation rules
php artisan dto:cache

# Generate TypeScript
php artisan dto:typescript
```

## Combining Commands

### Create and Validate

```bash
php artisan make:dto UserDTO && php artisan dto:validate UserDTO
```

### Generate TypeScript and Watch

```bash
php artisan dto:typescript --watch &
```

## Best Practices

### 1. Use Validation in Development

```bash
# Always validate after creating DTOs
php artisan make:dto UserDTO
php artisan dto:validate UserDTO
```

### 2. Cache in Production

```bash
# Add to deployment script
php artisan dto:cache
```

### 3. Generate TypeScript Automatically

```bash
# Add to composer.json scripts
"scripts": {
    "post-autoload-dump": [
        "@php artisan dto:typescript"
    ]
}
```

### 4. Validate in CI/CD

```bash
# Add to CI/CD pipeline
php artisan dto:validate --all
php artisan dto:typescript --check
```

## See Also

- [Laravel Integration](/framework-integration/laravel/) - Laravel integration guide
- [TypeScript Generation](/simple-dto/typescript-generation/) - TypeScript generation details
- [Validation](/simple-dto/validation/) - Validation guide

