---
title: Framework Detection
description: Automatic framework detection and integration
---

Data Helpers automatically detects which frameworks are available and enables appropriate integrations.

## How It Works

Framework detection happens at runtime by checking for framework-specific classes:

```php
// Laravel Detection
if (class_exists(\Illuminate\Support\Collection::class)) {
    // Enable Laravel Collection support
}

// Doctrine Detection
if (class_exists(\Doctrine\Common\Collections\Collection::class)) {
    // Enable Doctrine Collection support
}

// Symfony Detection
if (class_exists(\Symfony\Component\HttpFoundation\Request::class)) {
    // Enable Symfony Request support
}
```

## Supported Frameworks

### Laravel 9+

Automatically detected features:
- Collections (`Illuminate\Support\Collection`)
- Eloquent Models (`Illuminate\Database\Eloquent\Model`)
- Service Provider auto-registration
- Controller dependency injection

### Symfony 6+

Automatically detected features:
- Collections (`Doctrine\Common\Collections\ArrayCollection`)
- Entities with Doctrine ORM
- Value Resolver for controllers
- Request data extraction

### Doctrine 2+

Automatically detected features:
- Collections (`Doctrine\Common\Collections\Collection`)
- Entities with relationships
- Lazy loading support

### Plain PHP

Works out of the box with:
- Arrays
- Objects
- JSON strings
- XML strings

## Zero Configuration

No configuration files or environment variables needed. Framework support is enabled automatically when the framework is present.

## Benefits

- **No Setup Required** - Just install and use
- **Framework Agnostic** - Same API across all frameworks
- **Optimal Performance** - Only loads framework-specific code when needed
- **Easy Testing** - Works in any environment

## See Also

- [Framework Integration](/framework-integration/overview/)
- [Laravel Integration](/framework-integration/laravel/)
- [Symfony Integration](/framework-integration/symfony/)
- [Doctrine Integration](/framework-integration/doctrine/)
