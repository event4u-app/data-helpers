---
title: Laravel Artisan Commands
description: Complete reference of all Laravel Artisan commands for SimpleDto
---

Complete reference of all Laravel Artisan commands for SimpleDto.

## Introduction

SimpleDto provides several Artisan commands for Laravel:

- **make:dto** - Create new Dtos
- **dto:typescript** - Generate TypeScript types
- **dto:migrate-spatie** - Migrate Spatie Laravel Data to SimpleDto
- **dto:list** - List all Dtos
- **dto:validate** - Validate Dto structure
- **dto:cache** - Cache validation rules
- **dto:clear** - Clear validation cache

## make:dto

Create a new Dto class.

### Basic Usage

```bash
php artisan make:dto UserDto
```

Creates `app/Dto/UserDto.php`:

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
php artisan make:dto CreateUserDto --validation
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
php artisan make:dto UserResourceDto --resource
```

#### --force

Overwrite existing Dto:

```bash
php artisan make:dto UserDto --force
```

## dto:typescript

Generate TypeScript types from Dtos.

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

Migrate Spatie Laravel Data classes to SimpleDto.

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
2. Replaces `Data` with `SimpleDto` base class
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
use event4u\DataHelpers\SimpleDto\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

class UserData extends SimpleDto
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

List all Dtos in the project.

### Basic Usage

```bash
php artisan dto:list
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
php artisan dto:list --namespace=App\\Dto\\Api
```

## dto:validate

Validate Dto structure and configuration.

### Basic Usage

```bash
php artisan dto:validate UserDto
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
php artisan dto:validate --all
```

#### --fix

Attempt to fix common issues:

```bash
php artisan dto:validate UserDto --fix
```

## dto:cache

Cache validation rules for better performance.

### Basic Usage

```bash
php artisan dto:cache
```

Caches validation rules for all Dtos, improving validation performance by up to 198x.

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

### Create API Dtos

```bash
# Create request Dto
php artisan make:dto CreateUserDto --validation

# Create response Dto
php artisan make:dto UserResourceDto --resource

# Generate TypeScript
php artisan dto:typescript
```

### Development Workflow

```bash
# Create Dto
php artisan make:dto OrderDto

# Validate structure
php artisan dto:validate OrderDto

# Generate TypeScript with watch
php artisan dto:typescript --watch
```

### CI/CD Pipeline

```bash
# Validate all Dtos
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
php artisan make:dto UserDto && php artisan dto:validate UserDto
```

### Generate TypeScript and Watch

```bash
php artisan dto:typescript --watch &
```

## Best Practices

### 1. Use Validation in Development

```bash
# Always validate after creating Dtos
php artisan make:dto UserDto
php artisan dto:validate UserDto
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

