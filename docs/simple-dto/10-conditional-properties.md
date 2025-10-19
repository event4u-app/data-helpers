# Conditional Properties

Learn how to use SimpleDTO's 18 conditional attributes to dynamically include or exclude properties.

---

## 🎯 What are Conditional Properties?

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

---

## 📋 Core Conditional Attributes (9)

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

class SettingsDTO extends SimpleDTO
{
    public function __construct(
        public readonly bool $emailNotifications,
        
        #[WhenTrue('emailNotifications')]
        public readonly ?string $emailFrequency = null,
        
        #[WhenFalse('emailNotifications')]
        public readonly ?string $disabledReason = null,
    ) {}
}
```

### WhenEquals

Include when property equals a specific value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        
        #[WhenEquals('status', 'shipped')]
        public readonly ?string $trackingNumber = null,
        
        #[WhenEquals('status', 'cancelled')]
        public readonly ?string $cancellationReason = null,
    ) {}
}
```

### WhenIn

Include when property is in a list of values:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $role,
        
        #[WhenIn('role', ['admin', 'moderator'])]
        public readonly ?array $moderationTools = null,
    ) {}
}
```

### WhenInstanceOf

Include when property is instance of a class:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenInstanceOf;

class PaymentDTO extends SimpleDTO
{
    public function __construct(
        public readonly object $paymentMethod,
        
        #[WhenInstanceOf('paymentMethod', CreditCard::class)]
        public readonly ?string $cardLastFour = null,
        
        #[WhenInstanceOf('paymentMethod', PayPal::class)]
        public readonly ?string $paypalEmail = null,
    ) {}
}
```

---

## 🌐 Context-Based Attributes (4)

### WhenContext

Include when context key exists:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenContext('include_email')]
        public readonly ?string $email = null,
        
        #[WhenContext('include_stats')]
        public readonly ?array $statistics = null,
    ) {}
}

// Usage
$dto = UserDTO::fromArray($data);
$array = $dto->withContext(['include_email' => true])->toArray();
```

### WhenContextEquals

Include when context value equals specific value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenContextEquals('view', 'detailed')]
        public readonly ?string $description = null,
        
        #[WhenContextEquals('currency', 'USD')]
        public readonly ?float $priceUsd = null,
    ) {}
}

$array = $dto->withContext(['view' => 'detailed'])->toArray();
```

### WhenContextIn

Include when context value is in list:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenContextIn('environment', ['development', 'staging'])]
        public readonly ?array $debugInfo = null,
    ) {}
}

$array = $dto->withContext(['environment' => 'development'])->toArray();
```

### WhenContextNotNull

Include when context key is not null:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextNotNull;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        
        #[WhenContextNotNull('user')]
        public readonly ?string $customerName = null,
    ) {}
}

$array = $dto->withContext(['user' => $user])->toArray();
```

---

## 🔐 Laravel-Specific Attributes (4)

### WhenAuth

Include only when user is authenticated:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
    ) {}
}

// Automatically checks auth()->check()
$array = $dto->toArray();
```

### WhenGuest

Include only when user is NOT authenticated:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGuest;

class PageDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenGuest]
        public readonly ?string $loginPrompt = null,
        
        #[WhenGuest]
        public readonly ?string $registerLink = null,
    ) {}
}
```

### WhenCan

Include only when user has permission:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        
        #[WhenCan('edit')]
        public readonly ?string $editUrl = null,
        
        #[WhenCan('delete')]
        public readonly ?string $deleteUrl = null,
    ) {}
}

// With subject
$array = $dto->withContext(['subject' => $post])->toArray();
```

### WhenRole

Include only when user has role:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenRole;

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenRole('admin')]
        public readonly ?array $adminPanel = null,
        
        #[WhenRole(['admin', 'moderator'])]
        public readonly ?array $moderationTools = null,
    ) {}
}
```

---

## 🛡️ Symfony-Specific Attributes (2)

### WhenGranted

Include only when user is granted permission:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted;

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        
        #[WhenGranted('EDIT')]
        public readonly ?string $editUrl = null,
        
        #[WhenGranted('DELETE', 'subject')]
        public readonly ?string $deleteUrl = null,
    ) {}
}

// With security context
$array = $dto->withContext(['security' => $security])->toArray();
```

### WhenSymfonyRole

Include only when user has Symfony role:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenSymfonyRole;

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenSymfonyRole('ROLE_ADMIN')]
        public readonly ?array $adminPanel = null,
        
        #[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly ?array $moderationTools = null,
    ) {}
}
```

---

## 🎯 Combining Conditions (AND Logic)

Multiple attributes on the same property use AND logic:

```php
class SecretDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenAuth]
        #[WhenRole('admin')]
        #[WhenCan('view-secrets')]
        public readonly ?string $secretContent = null,
    ) {}
}

// All conditions must be true:
// 1. User must be authenticated
// 2. User must have 'admin' role
// 3. User must have 'view-secrets' permission
```

---

## 💡 Real-World Examples

### Example 1: API Resource with Permissions

```php
class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
        
        #[WhenCan('view-admin')]
        public readonly ?array $adminData = null,
        
        #[WhenRole('admin')]
        public readonly ?Carbon $lastLogin = null,
    ) {}
}
```

### Example 2: Environment-Specific Data

```php
class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        public readonly array $data,
        
        #[WhenContextIn('environment', ['development', 'staging'])]
        public readonly ?array $debugInfo = null,
        
        #[WhenContextEquals('environment', 'development')]
        public readonly ?array $sqlQueries = null,
    ) {}
}
```

### Example 3: Feature Flags

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        
        #[WhenContext('feature_new_profile')]
        public readonly ?array $profileV2 = null,
        
        #[WhenContext('feature_social_links')]
        public readonly ?array $socialLinks = null,
    ) {}
}
```

---

## 📚 Next Steps

1. [with() Method](11-with-method.md) - Dynamic properties
2. [Context-Based Conditions](12-context-based-conditions.md) - Advanced context usage
3. [Security & Visibility](22-security-visibility.md) - Hidden properties
4. [Attributes Reference](33-attributes-reference.md) - Complete list

---

**Previous:** [Serialization](09-serialization.md)  
**Next:** [with() Method](11-with-method.md)

