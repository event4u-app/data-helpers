---
title: Conditional Attributes
description: Complete reference of all 18 conditional attributes for dynamic visibility
---

Complete reference of all 18 conditional attributes for dynamic visibility.

## Introduction

SimpleDto provides 18 conditional attributes organized into categories:

- **Core Conditional** (9) - Framework-agnostic conditions
- **Laravel-Specific** (4) - Authentication & authorization
- **Symfony-Specific** (2) - Security & roles
- **Context-Based** (3) - Custom context conditions

## Quick Reference

| Attribute | Description |
|-----------|-------------|
| `#[WhenCallback(callable)]` | Custom logic |
| `#[WhenValue(string, mixed)]` | Property equals value |
| `#[WhenNull(string)]` | Property is null |
| `#[WhenNotNull(string)]` | Property not null |
| `#[WhenTrue(string)]` | Property is true |
| `#[WhenFalse(string)]` | Property is false |
| `#[WhenEquals(string, mixed)]` | Property equals |
| `#[WhenIn(string, array)]` | Property in array |
| `#[WhenNotIn(string, array)]` | Property not in array |
| `#[WhenAuth]` | User authenticated (Laravel) |
| `#[WhenGuest]` | User is guest (Laravel) |
| `#[WhenCan(string)]` | User has permission (Laravel) |
| `#[WhenRole(string)]` | User has role (Laravel) |
| `#[WhenGranted(string)]` | User granted (Symfony) |
| `#[WhenSymfonyRole(string)]` | User has role (Symfony) |
| `#[WhenContext(string)]` | Context is truthy |
| `#[WhenContextEquals(string, mixed)]` | Context equals |
| `#[WhenContextIn(string, array)]` | Context in array |

## Core Conditional Attributes

```php
// Custom logic
#[WhenCallback(fn($value, $dto) => strlen($value) > 0)]
public readonly ?string $bio = null;

// Property equals value
#[WhenValue('status', 'published')]
public readonly ?string $publishedAt = null;

// Property is null
#[WhenNull('deletedAt')]
public readonly ?string $activeStatus = null;

// Property not null
#[WhenNotNull('verifiedAt')]
public readonly ?string $verifiedBadge = null;

// Property is true
#[WhenTrue('isPublished')]
public readonly ?string $publishedUrl = null;

// Property is false
#[WhenFalse('isActive')]
public readonly ?string $inactiveReason = null;

// Property equals (alias for WhenValue)
#[WhenEquals('role', 'admin')]
public readonly ?array $adminPanel = null;

// Property in array
#[WhenIn('role', ['admin', 'moderator'])]
public readonly ?array $moderationTools = null;

// Property not in array
#[WhenNotIn('role', ['guest', 'banned'])]
public readonly ?array $premiumFeatures = null;
```

## Laravel-Specific Attributes

```php
// User authenticated
#[WhenAuth]
public readonly ?string $email = null;

// User is guest
#[WhenGuest]
public readonly ?string $registerPrompt = null;

// User has permission
#[WhenCan('edit-posts')]
public readonly ?string $editUrl = null;

// User has role
#[WhenRole('admin')]
public readonly ?array $adminPanel = null;

// Multiple roles (OR logic)
#[WhenRole(['admin', 'moderator'])]
public readonly ?array $moderationPanel = null;
```

## Symfony-Specific Attributes

```php
// User granted
#[WhenGranted('ROLE_ADMIN')]
public readonly ?array $adminData = null;

// User has role
#[WhenSymfonyRole('ROLE_ADMIN')]
public readonly ?array $adminPanel = null;
```

## Context-Based Attributes

```php
// Context is truthy
#[WhenContext('debug')]
public readonly ?array $debugInfo = null;

// Use with context
$dto->withContext(['debug' => true]);

// Context equals value
#[WhenContextEquals('environment', 'development')]
public readonly ?array $debugData = null;

// Context in array
#[WhenContextIn('environment', ['development', 'staging'])]
public readonly ?array $debugInfo = null;
```

## Real-World Examples

### User Profile

```php
class UserProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenRole('admin')]
        public readonly ?string $ipAddress = null,
    ) {}
}
```

### Admin Dashboard

```php
class DashboardDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[WhenRole('admin')]
        public readonly ?array $userManagement = null,

        #[WhenCan('view-logs')]
        public readonly ?array $systemLogs = null,
    ) {}
}
```

## Best Practices

### Use Specific Attributes

```php
// ✅ Good
#[WhenAuth]
public readonly ?string $email;

// ❌ Bad
#[WhenCallback(fn() => auth()->check())]
public readonly ?string $email;
```

### Combine Conditions

```php
// Multiple conditions (AND logic)
#[WhenAuth, WhenRole('admin')]
public readonly ?array $adminData = null;
```

## See Also

- [Conditional Properties](/data-helpers/simple-dto/conditional-properties/) - Detailed guide
- [Security & Visibility](/data-helpers/simple-dto/security-visibility/) - Security practices
- [Validation Attributes](/data-helpers/attributes/validation/) - Validation reference
