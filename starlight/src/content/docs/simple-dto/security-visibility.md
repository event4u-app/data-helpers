---
title: Security & Visibility
description: Learn how to control property visibility and secure sensitive data in DTOs
---

Learn how to control property visibility and secure sensitive data in DTOs.

## Introduction

SimpleDTO provides powerful security features to control what data is exposed:

- **Hidden Properties** - Never serialize sensitive data
- **Conditional Visibility** - Show/hide based on conditions
- **Encrypted Properties** - Automatically encrypt/decrypt
- **Hashed Properties** - One-way hashing for passwords
- **Role-Based Visibility** - Show based on user roles
- **Permission-Based Visibility** - Show based on permissions

## Hidden Properties

### #[Hidden] Attribute

Properties marked as hidden are **never** included in serialization:

```php
use Event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[Hidden]
        public readonly string $password,

        #[Hidden]
        public readonly string $apiToken,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'apiToken' => 'token123',
]);

$array = $dto->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com']
// password and apiToken are excluded
```

## Conditional Visibility

### Based on Authentication

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenAuth]
        public readonly ?string $phone = null,
    ) {}
}

// Only includes email and phone when user is authenticated
```

### Based on Permissions

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,

        #[WhenCan('edit-posts')]
        public readonly ?string $editUrl = null,

        #[WhenCan('delete-posts')]
        public readonly ?string $deleteUrl = null,
    ) {}
}
```

### Based on Roles

```php
class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenRole('admin')]
        public readonly ?array $adminPanel = null,

        #[WhenRole(['admin', 'moderator'])]
        public readonly ?array $moderationPanel = null,
    ) {}
}
```

## Encrypted Properties

### Automatic Encryption

```php
use Event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Cast(EncryptedCast::class)]
        public readonly string $ssn,

        #[Cast(EncryptedCast::class)]
        public readonly string $creditCard,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'ssn' => '123-45-6789',
    'creditCard' => '4111-1111-1111-1111',
]);

// Automatically encrypted when stored
$encrypted = $dto->toArray();

// Automatically decrypted when accessed
echo $dto->ssn; // 123-45-6789
```

## Hashed Properties

### One-Way Hashing

```php
use Event4u\DataHelpers\SimpleDTO\Casts\HashCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[Cast(HashCast::class)]
        public readonly string $password,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'password' => 'secret123',
]);

// Password is hashed
$array = $dto->toArray();
// ['name' => 'John Doe', 'password' => '$2y$10$...']
```


## Real-World Examples

### User Profile with Privacy

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenAuth]
        public readonly ?string $phone = null,

        #[Hidden]
        public readonly string $password,

        #[WhenRole('admin')]
        public readonly ?string $ipAddress = null,
    ) {}
}
```

### Payment Information

```php
class PaymentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $customerName,

        #[Cast(EncryptedCast::class)]
        public readonly string $creditCardNumber,

        #[Cast(EncryptedCast::class)]
        public readonly string $cvv,

        #[Hidden]
        public readonly string $billingAddress,
    ) {}
}
```

### Admin Dashboard

```php
class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly array $stats,

        #[WhenRole('admin')]
        public readonly ?array $userManagement = null,

        #[WhenCan('view-logs')]
        public readonly ?array $systemLogs = null,

        #[WhenCan('manage-settings')]
        public readonly ?array $settings = null,
    ) {}
}
```

## Best Practices

### Always Hide Sensitive Data

```php
// ✅ Good - hide sensitive data
#[Hidden]
public readonly string $password;

#[Hidden]
public readonly string $apiToken;

// ❌ Bad - expose sensitive data
public readonly string $password;
```

### Use Encryption for PII

```php
// ✅ Good - encrypt PII
#[Cast(EncryptedCast::class)]
public readonly string $ssn;

// ❌ Bad - store PII in plain text
public readonly string $ssn;
```

### Use Conditional Visibility

```php
// ✅ Good - conditional visibility
#[WhenAuth]
public readonly ?string $email;

// ❌ Bad - always expose
public readonly string $email;
```

### Hash Passwords

```php
// ✅ Good - hash passwords
#[Cast(HashCast::class)]
public readonly string $password;

// ❌ Bad - store plain text passwords
public readonly string $password;
```

## Security Checklist

- [ ] All passwords are hashed
- [ ] All PII is encrypted
- [ ] Sensitive data is hidden
- [ ] Email/phone only visible when authenticated
- [ ] Admin data only visible to admins
- [ ] API tokens are hidden
- [ ] Credit card numbers are encrypted
- [ ] SSN/Tax IDs are encrypted

## See Also

- [Conditional Properties](/simple-dto/conditional-properties/) - Dynamic visibility
- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
- [Serialization](/simple-dto/serialization/) - Convert to different formats
