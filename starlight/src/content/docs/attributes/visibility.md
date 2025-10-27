---
title: Visibility Attributes
description: Reference for visibility control attributes
---

Reference for visibility control attributes.

## Introduction

SimpleDto provides visibility attributes:

- **#[Hidden]** - Always hide property
- **#[Visible(callback)]** - Conditionally visible
- **18 Conditional Attributes** - See [Conditional Attributes](/data-helpers/attributes/conditional/)

## Hidden Attribute

Properties marked as hidden are **never** included in serialization:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;

#[Hidden]
public readonly string $password;

#[Hidden]
public readonly string $apiToken;
```

## Visible Attribute

Conditionally visible based on callback:

<!-- skip-test: property declaration only -->
```php
use event4u\DataHelpers\SimpleDto\Attributes\Visible;

#[Visible(callback: 'canViewEmail')]
public readonly string $email;

private function canViewEmail(mixed $context): bool
{
    return $context?->role === 'admin';
}
```

### Static Callback

<!-- skip-test: property declaration only -->
```php
#[Visible(callback: [PermissionChecker::class, 'canViewEmail'])]
public readonly string $email;
```

### Laravel Gate

<!-- skip-test: property declaration only -->
```php
#[Visible(gate: 'view-email')]
public readonly string $email;
```

### Symfony Voter

<!-- skip-test: property declaration only -->
```php
#[Visible(voter: 'view', attribute: 'email')]
public readonly string $email;
```

## Conditional Attributes

See [Conditional Attributes](/data-helpers/attributes/conditional/) for 18 conditional attributes:

<!-- skip-test: property declarations only -->
```php
#[WhenAuth]                    // Show when authenticated
#[WhenRole('admin')]           // Show when user has role
#[WhenCan('view-email')]       // Show when user has permission
#[WhenValue('status', 'active')] // Show when property equals value
```

## Real-World Example

```php
class UserProfileDto extends SimpleDto
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

- [Security & Visibility](/data-helpers/simple-dto/security-visibility/) - Security guide
- [Conditional Attributes](/data-helpers/attributes/conditional/) - Conditional visibility
