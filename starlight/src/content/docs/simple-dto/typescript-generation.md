---
title: TypeScript Generation
description: Automatically generate TypeScript types from your Dtos
---

Learn how to automatically generate TypeScript types from your Dtos.

## Introduction

SimpleDto can automatically generate TypeScript interfaces and types from your PHP Dtos:

- **Automatic Generation** - Generate TypeScript from PHP
- **Type Mapping** - PHP types → TypeScript types
- **Nested Dtos** - Automatic nested type generation
- **Enums** - PHP enums → TypeScript enums
- **Arrays & Collections** - Proper array typing
- **Optional Properties** - Nullable → optional
- **JSDoc Comments** - Include PHP docblocks

## Quick Start

### Laravel

```bash
php artisan dto:typescript
```

### Symfony

```bash
bin/console dto:typescript
```

### Plain PHP

```php
use event4u\DataHelpers\SimpleDto\TypeScript\Generator;

$generator = new Generator();
$generator->generate(
    dtoPath: __DIR__ . '/src/Dto',
    outputPath: __DIR__ . '/frontend/types'
);
```

## Basic Example

### PHP Dto

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface UserDto {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
}
```

## Type Mapping

### Primitive Types

```php
// PHP
public readonly string $name;
public readonly int $age;
public readonly float $price;
public readonly bool $active;

// TypeScript
name: string;
age: number;
price: number;
active: boolean;
```

### Arrays

```php
// PHP
public readonly array $tags;
/** @var string[] */
public readonly array $categories;

// TypeScript
tags: any[];
categories: string[];
```

### Nullable Types

```php
// PHP
public readonly ?string $middleName = null;
public readonly ?int $age = null;

// TypeScript
middleName?: string | null;
age?: number | null;
```

### Union Types

```php
// PHP
public readonly string|int $id;

// TypeScript
id: string | number;
```

## Nested Dtos

### PHP Dtos

```php
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface AddressDto {
  street: string;
  city: string;
  country: string;
}

export interface UserDto {
  id: number;
  name: string;
  address: AddressDto;
}
```

## Collections

### PHP Dto

```php
class PostDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        /** @var CommentDto[] */
        public readonly array $comments,
    ) {}
}

class CommentDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
    ) {}
}
```

### Generated TypeScript

```typescript
export interface CommentDto {
  id: number;
  content: string;
}

export interface PostDto {
  id: number;
  title: string;
  comments: CommentDto[];
}
```

## Enums

### PHP Enum

```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly UserRole $role,
    ) {}
}
```

### Generated TypeScript

```typescript
export enum UserRole {
  ADMIN = 'admin',
  USER = 'user',
  GUEST = 'guest',
}

export interface UserDto {
  id: number;
  name: string;
  role: UserRole;
}
```

## Configuration

### Laravel

```php
// config/simple-dto.php
return [
    'typescript' => [
        'output' => resource_path('js/types/dtos.ts'),
        'export_type' => 'export', // 'export', 'declare', or ''
        'include_comments' => true,
        'sort_properties' => false,
    ],
];
```

### Symfony

```yaml
# config/packages/simple_dto.yaml
simple_dto:
  typescript:
    output: '%kernel.project_dir%/assets/types/dtos.ts'
    export_type: 'export'
    include_comments: true
    sort_properties: false
```

## Watch Mode

### Laravel

```bash
php artisan dto:typescript --watch
```

### Symfony

```bash
bin/console dto:typescript --watch
```

Automatically regenerates TypeScript when Dtos change.

## CI/CD Integration

### Check if Types are Up-to-Date

```bash
# Laravel
php artisan dto:typescript --check

# Symfony
bin/console dto:typescript --check
```

Returns exit code 1 if types are outdated.

### GitHub Actions Example

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Install dependencies
        run: composer install
      - name: Check TypeScript types
        run: php artisan dto:typescript --check
```

## Best Practices

### 1. Always Use Type Hints

```php
// ✅ Good
public readonly string $name;

// ❌ Bad - generates 'any'
public readonly $name;
```

### 2. Document Array Types

```php
// ✅ Good
/** @var string[] */
public readonly array $tags;

// ❌ Bad - generates 'any[]'
public readonly array $tags;
```

### 3. Use Enums for Constants

```php
// ✅ Good
public readonly UserRole $role;

// ❌ Bad
public readonly string $role;
```

### 4. Generate on Deployment

```bash
# Add to deployment script
php artisan dto:typescript
```


## Code Examples

The following working examples demonstrate this feature:

- [**Basic Generation**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/typescript-generation/basic-generation.php) - Generate TypeScript types
- [**Generator Options**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/typescript-generation/generator-options.php) - Customizing generation

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [TypeScriptGeneratorTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/TypeScriptGeneratorTest.php) - TypeScript generation tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=TypeScript
```

## See Also

- [Artisan Commands](/framework-integration/artisan-commands/) - Laravel commands
- [Console Commands](/framework-integration/console-commands/) - Symfony commands
- [Creating Dtos](/simple-dto/creating-dtos/) - Dto creation guide

