---
title: Visibility Attributes
description: Reference for visibility control attributes
---

Reference for visibility control attributes.

## Overview

SimpleDTO provides visibility attributes:

- **#[Hidden]** - Always hide property
- **#[Visible(callback)]** - Conditionally visible
- **18 Conditional Attributes** - See [Conditional Attributes](/attributes/conditional/)

## Hidden Attribute

Properties marked as hidden are **never** included in serialization:

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

#[Hidden]
public readonly string $password;

#[Hidden]
public readonly string $apiToken;
```

## Visible Attribute

Conditionally visible based on callback:

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Visible;

#[Visible(callback: 'canViewEmail')]
public readonly string $email;

private function canViewEmail(mixed $context): bool
{
    return $context?->role === 'admin';
}
```

### Static Callback

```php
#[Visible(callback: [PermissionChecker::class, 'canViewEmail'])]
public readonly string $email;
```

### Laravel Gate

```php
#[Visible(gate: 'view-email')]
public readonly string $email;
```

### Symfony Voter

```php
#[Visible(voter: 'view', attribute: 'email')]
public readonly string $email;
```

## Conditional Attributes

See [Conditional Attributes](/attributes/conditional/) for 18 conditional attributes:

```php
#[WhenAuth]                    // Show when authenticated
#[WhenRole('admin')]           // Show when user has role
#[WhenCan('view-email')]       // Show when user has permission
#[WhenValue('status', 'active')] // Show when property equals value
```

## Real-World Example

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[Hidden]
        public readonly string $password,

        #[WhenRole('admin')]
        public readonly ?string $ipAddress = null,
    ) {}
}
```

## Best Practices

### Always Hide Sensitive Data

```php
// ✅ Good
#[Hidden]
public readonly string $password;

// ❌ Bad
public readonly string $password;
```

### Use Conditional Visibility

```php
// ✅ Good
#[WhenAuth]
public readonly ?string $email;
```

## Security Checklist

- [ ] All passwords are hidden
- [ ] All API tokens are hidden
- [ ] PII is encrypted or hidden
- [ ] Email/phone only visible when authenticated
- [ ] Admin data only visible to admins

## See Also

- [Security & Visibility](/simple-dto/security-visibility/) - Security guide
- [Conditional Attributes](/attributes/conditional/) - Conditional visibility
