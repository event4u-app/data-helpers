---
title: Conditional Properties
description: Learn how to use SimpleDTO's 18 conditional attributes to dynamically include or exclude properties
---

Learn how to use SimpleDTO's 18 conditional attributes to dynamically include or exclude properties.

## What are Conditional Properties?

Conditional properties are properties that are only included in serialization when certain conditions are met:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]  // Only when authenticated
        public readonly ?string $email = null,

        #[WhenCan('view-admin')]  // Only with permission
        public readonly ?array $adminData = null,
    ) {}
}
```

**SimpleDTO provides 18 conditional attributes** - 9x more than Spatie Data!

## Core Conditional Attributes

### WhenCallback

Execute custom logic to determine inclusion:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenCallback(fn($value, $dto) => strlen($value) > 0)]
        public readonly ?string $bio = null,

        #[WhenCallback(fn($value, $dto) => $dto->age >= 18)]
        public readonly ?string $driversLicense = null,
    ) {}
}
```

### WhenValue

Include when property has a specific value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,

        #[WhenValue('status', 'published')]
        public readonly ?string $publishedAt = null,
    ) {}
}
```

### WhenNull / WhenNotNull

Include based on null check:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenNotNull]
        public readonly ?string $phone = null,

        #[WhenNull('deletedAt')]
        public readonly ?string $activeStatus = null,
    ) {}
}
```

### WhenTrue / WhenFalse

Include based on boolean value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenTrue;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenFalse;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $isActive,

        #[WhenTrue('isActive')]
        public readonly ?string $activeMessage = null,

        #[WhenFalse('isActive')]
        public readonly ?string $inactiveReason = null,
    ) {}
}
```

### WhenEquals / WhenNotEquals

Include when property equals/not equals a value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotEquals;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,

        #[WhenEquals('status', 'shipped')]
        public readonly ?string $trackingNumber = null,

        #[WhenNotEquals('status', 'cancelled')]
        public readonly ?string $estimatedDelivery = null,
    ) {}
}
```

### WhenIn / WhenNotIn

Include when property is in/not in a list:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotIn;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $role,

        #[WhenIn('role', ['admin', 'moderator'])]
        public readonly ?array $moderationTools = null,

        #[WhenNotIn('role', ['guest', 'banned'])]
        public readonly ?array $premiumFeatures = null,
    ) {}
}
```


## Laravel-Specific Attributes

### WhenAuth / WhenGuest

Include based on authentication status:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGuest;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth]
        public readonly ?string $email = null,

        #[WhenGuest]
        public readonly ?string $registerPrompt = null,
    ) {}
}
```

### WhenCan

Include based on user permissions:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenCan('edit-posts')]
        public readonly ?string $editUrl = null,

        #[WhenCan('delete-posts')]
        public readonly ?string $deleteUrl = null,
    ) {}
}
```

### WhenRole

Include based on user role:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenRole;

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

## Symfony-Specific Attributes

### WhenGranted

Include based on Symfony security:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenGranted('ROLE_ADMIN')]
        public readonly ?array $adminData = null,
    ) {}
}
```

### WhenSymfonyRole

Include based on Symfony role:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenSymfonyRole;

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?array $adminPanel = null,
    ) {}
}
```

## Context-Based Conditions

### WhenContext

Include based on context value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        public readonly array $data,

        #[WhenContext('debug')]
        public readonly ?array $debugInfo = null,
    ) {}
}

// Use with context
$dto = ApiResponseDTO::fromArray($data)->withContext(['debug' => true]);
```

### WhenContextEquals

Include when context equals a value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenContextEquals('environment', 'development')]
        public readonly ?array $debugData = null,
    ) {}
}
```

### WhenContextIn

Include when context is in a list:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn;

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,

        #[WhenContextIn('environment', ['development', 'staging'])]
        public readonly ?array $debugInfo = null,
    ) {}
}
```

## All 18 Conditional Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `WhenCallback` | Custom logic | `#[WhenCallback(fn($v) => $v > 0)]` |
| `WhenValue` | Property has value | `#[WhenValue('status', 'active')]` |
| `WhenNull` | Property is null | `#[WhenNull('deletedAt')]` |
| `WhenNotNull` | Property is not null | `#[WhenNotNull]` |
| `WhenTrue` | Property is true | `#[WhenTrue('isActive')]` |
| `WhenFalse` | Property is false | `#[WhenFalse('isActive')]` |
| `WhenEquals` | Property equals value | `#[WhenEquals('status', 'done')]` |
| `WhenNotEquals` | Property not equals | `#[WhenNotEquals('status', 'draft')]` |
| `WhenIn` | Property in list | `#[WhenIn('role', ['admin'])]` |
| `WhenNotIn` | Property not in list | `#[WhenNotIn('role', ['guest'])]` |
| `WhenAuth` | User authenticated (Laravel) | `#[WhenAuth]` |
| `WhenGuest` | User is guest (Laravel) | `#[WhenGuest]` |
| `WhenCan` | User has permission (Laravel) | `#[WhenCan('edit')]` |
| `WhenRole` | User has role (Laravel) | `#[WhenRole('admin')]` |
| `WhenGranted` | User granted (Symfony) | `#[WhenGranted('ROLE_ADMIN')]` |
| `WhenSymfonyRole` | User has role (Symfony) | `#[WhenSymfonyRole('ROLE_ADMIN')]` |
| `WhenContext` | Context is truthy | `#[WhenContext('debug')]` |
| `WhenContextEquals` | Context equals value | `#[WhenContextEquals('env', 'dev')]` |
| `WhenContextIn` | Context in list | `#[WhenContextIn('env', ['dev'])]` |

## Best Practices

### Use Specific Attributes

```php
// ✅ Good - specific attribute
#[WhenAuth]
public readonly ?string $email;

// ❌ Bad - generic callback
#[WhenCallback(fn() => auth()->check())]
public readonly ?string $email;
```

### Combine with Other Features

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenAuth, Lazy]
        public readonly ?array $posts = null,
    ) {}
}
```


## Code Examples

The following working examples demonstrate this feature:

- [**Basic Conditional**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/basic-conditional.php) - Simple conditional properties
- [**WhenCallback with Parameters**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/whencallback-with-parameters.php) - Callbacks with parameters
- [**With Method**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/with-method.php) - Using with() method
- [**Context-Based Conditions**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/context-based-conditions.php) - Context-aware conditions
- [**Custom Conditions**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/custom-conditions.php) - Creating custom conditions
- [**Laravel Attributes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/laravel-conditional-attributes.php) - Laravel-specific attributes
- [**Symfony Attributes**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/conditional-properties/symfony-conditional-attributes.php) - Symfony-specific attributes

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [ConditionalPropertiesTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/ConditionalPropertiesTest.php) - Conditional property tests
- [ContextTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDTO/ContextTest.php) - Context tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Conditional
```

## See Also

- [Lazy Properties](/simple-dto/lazy-properties/) - Defer expensive operations
- [Computed Properties](/simple-dto/computed-properties/) - Calculate values
- [Security & Visibility](/simple-dto/security-visibility/) - Control data exposure
