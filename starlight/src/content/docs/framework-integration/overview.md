---
title: Framework Integration Overview
description: Overview of framework integrations for Laravel, Symfony, Doctrine, and Plain PHP
---

Overview of framework integrations for Laravel, Symfony, Doctrine, and Plain PHP.

## Introduction

Data Helpers works with **any PHP 8.2+ project** and provides optional framework integrations:

- **Laravel 9+** - Automatic service provider, controller injection, Eloquent integration
- **Symfony 6+** - Bundle registration, value resolvers, Doctrine integration
- **Doctrine 2+** - Entity mapping, collection support
- **Plain PHP** - Works out of the box with arrays, objects, JSON

## Zero Configuration

All framework integrations are **automatically detected** at runtime:

```php
// Laravel Detection
if (class_exists(\Illuminate\Support\Collection::class)) {
    // Enable Laravel Collection support
}

// Symfony Detection
if (class_exists(\Symfony\Component\HttpFoundation\Request::class)) {
    // Enable Symfony Request support
}
```

## Installation

```bash
composer require event4u/data-helpers
```

That's it! Data Helpers automatically detects your framework.

## Framework Support

### Laravel 9+

**Features:**
- ✅ Service Provider auto-registration
- ✅ Controller dependency injection
- ✅ Eloquent Model integration
- ✅ Artisan commands (make:dto, dto:typescript)
- ✅ Laravel-specific attributes (WhenAuth, WhenCan, WhenRole)

### Symfony 6+

**Features:**
- ✅ Bundle auto-registration (with Flex)
- ✅ Value Resolver for controllers
- ✅ Doctrine Entity integration
- ✅ Console commands (make:dto, dto:typescript)
- ✅ Symfony-specific attributes (WhenGranted, WhenSymfonyRole)

### Doctrine 2+

**Features:**
- ✅ Entity mapping (fromEntity, toEntity)
- ✅ Collection support
- ✅ Lazy loading

### Plain PHP

**Features:**
- ✅ Arrays, Objects, JSON, XML
- ✅ No dependencies required

## Quick Start

### Laravel

```php
class UserDto extends SimpleDto
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
    ) {}
}

// In controller
public function store(UserDto $dto) {
    // Automatic validation & injection
}
```

### Symfony

<!-- skip-test: controller method -->
```php
#[Route('/users', methods: ['POST'])]
public function create(UserDto $dto): JsonResponse {
    // Automatic validation & injection
}
```

### Plain PHP

```php
$data = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
$dto = UserDto::validateAndCreate($data);
```

## Next Steps

- [Laravel Integration](/data-helpers/framework-integration/laravel/) - Detailed Laravel guide
- [Symfony Integration](/data-helpers/framework-integration/symfony/) - Detailed Symfony guide
- [Doctrine Integration](/data-helpers/framework-integration/doctrine/) - Doctrine entity mapping
- [Plain PHP Usage](/data-helpers/framework-integration/plain-php/) - Standalone usage
