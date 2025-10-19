# Security & Visibility

Learn how to control property visibility and secure sensitive data in DTOs.

---

## 🎯 Overview

SimpleDTO provides powerful security features to control what data is exposed:

- ✅ **Hidden Properties** - Never serialize sensitive data
- ✅ **Conditional Visibility** - Show/hide based on conditions
- ✅ **Encrypted Properties** - Automatically encrypt/decrypt
- ✅ **Hashed Properties** - One-way hashing for passwords
- ✅ **Role-Based Visibility** - Show based on user roles
- ✅ **Permission-Based Visibility** - Show based on permissions

---

## 🔒 Hidden Properties

### #[Hidden] Attribute

Properties marked as hidden are **never** included in serialization:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        
        #[Hidden]
        public readonly string $password,
        
        #[Hidden]
        public readonly ?string $apiToken = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'apiToken' => 'token123',
]);

$array = $dto->toArray();
// [
//     'id' => 1,
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
// ]
// password and apiToken are NOT included
```

### Common Use Cases

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[Hidden] // Never expose passwords
        public readonly string $password,
        
        #[Hidden] // Never expose tokens
        public readonly ?string $rememberToken = null,
        
        #[Hidden] // Never expose internal IDs
        public readonly ?string $internalId = null,
        
        #[Hidden] // Never expose sensitive data
        public readonly ?string $ssn = null,
    ) {}
}
```

---

## 🎨 Conditional Visibility

### Show Based on Authentication

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth] // Only when authenticated
        public readonly ?string $email = null,
        
        #[WhenAuth] // Only when authenticated
        public readonly ?string $phone = null,
    ) {}
}

// When not authenticated
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe']

// When authenticated
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+1234567890']
```

### Show Based on Permissions

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        
        #[WhenCan('edit')] // Only if user can edit
        public readonly ?string $editUrl = null,
        
        #[WhenCan('delete')] // Only if user can delete
        public readonly ?string $deleteUrl = null,
        
        #[WhenCan('view-analytics')] // Only if user can view analytics
        public readonly ?array $analytics = null,
    ) {}
}
```

### Show Based on Roles

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenRole;

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly array $stats,
        
        #[WhenRole('admin')] // Only for admins
        public readonly ?array $adminPanel = null,
        
        #[WhenRole(['admin', 'moderator'])] // For admins and moderators
        public readonly ?array $moderationTools = null,
    ) {}
}
```

### Show Based on Context

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenContext('include_email')] // Only when context includes email
        public readonly ?string $email = null,
        
        #[WhenContext('include_profile')] // Only when context includes profile
        public readonly ?array $profile = null,
    ) {}
}

// Without context
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe']

// With context
$dto->withContext(['include_email' => true])->toArray();
// ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
```

---

## 🔐 Encrypted Properties

### Automatic Encryption/Decryption

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[Cast(EncryptedCast::class)]
        public readonly string $ssn,
        
        #[Cast(EncryptedCast::class)]
        public readonly ?string $creditCard = null,
    ) {}
}

// Data is automatically encrypted when stored
$dto = UserDTO::fromArray([
    'id' => 1,
    'name' => 'John Doe',
    'ssn' => '123-45-6789',
]);

// Access decrypted value
echo $dto->ssn; // 123-45-6789

// Stored encrypted in database
$array = $dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'ssn' => 'eyJpdiI6...encrypted...']
```

---

## 🔑 Hashed Properties

### One-Way Hashing

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\HashCast;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        
        #[Cast(HashCast::class)]
        public readonly string $password,
    ) {}
}

$dto = CreateUserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
]);

// Password is automatically hashed
$array = $dto->toArray();
// [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'password' => '$2y$10$...',
// ]
```

---

## 🎯 Real-World Examples

### Example 1: User Profile API

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        
        #[WhenAuth] // Only when authenticated
        public readonly ?string $email = null,
        
        #[WhenAuth] // Only when authenticated
        public readonly ?string $phone = null,
        
        #[WhenRole('admin')] // Only for admins
        public readonly ?string $ipAddress = null,
        
        #[WhenRole('admin')] // Only for admins
        public readonly ?Carbon $lastLoginAt = null,
        
        #[Hidden] // Never expose
        public readonly ?string $password = null,
        
        #[Hidden] // Never expose
        public readonly ?string $apiToken = null,
    ) {}
}

// Public view (not authenticated)
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'username' => 'johndoe']

// Authenticated user view
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'username' => 'johndoe', 'email' => 'john@example.com', 'phone' => '+1234567890']

// Admin view
$dto->toArray();
// ['id' => 1, 'name' => 'John Doe', 'username' => 'johndoe', 'email' => 'john@example.com', 'phone' => '+1234567890', 'ipAddress' => '192.168.1.1', 'lastLoginAt' => '2024-01-15 10:30:00']
```

### Example 2: Payment Information

```php
class PaymentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly float $amount,
        public readonly string $status,
        
        #[Cast(EncryptedCast::class)]
        public readonly string $cardNumber,
        
        #[Cast(EncryptedCast::class)]
        public readonly string $cvv,
        
        #[WhenRole('admin')]
        public readonly ?string $fullCardNumber = null,
        
        #[Hidden]
        public readonly ?string $processorToken = null,
    ) {}
}
```

### Example 3: Medical Records

```php
class PatientDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenCan('view-medical-records')]
        #[Cast(EncryptedCast::class)]
        public readonly ?string $medicalHistory = null,
        
        #[WhenCan('view-medical-records')]
        #[Cast(EncryptedCast::class)]
        public readonly ?string $medications = null,
        
        #[WhenRole('doctor')]
        public readonly ?string $diagnosis = null,
        
        #[Hidden]
        public readonly ?string $ssn = null,
    ) {}
}
```

### Example 4: Financial Data

```php
class AccountDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $accountNumber,
        
        #[WhenAuth]
        public readonly ?float $balance = null,
        
        #[WhenContext('include_transactions')]
        public readonly ?array $transactions = null,
        
        #[WhenRole('accountant')]
        public readonly ?float $totalRevenue = null,
        
        #[WhenRole('accountant')]
        public readonly ?float $totalExpenses = null,
        
        #[Hidden]
        #[Cast(EncryptedCast::class)]
        public readonly ?string $routingNumber = null,
    ) {}
}
```

---

## 🔄 Combining Security Features

### Multiple Conditions

```php
class SensitiveDataDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[WhenAuth, WhenRole('admin')] // Must be authenticated AND admin
        public readonly ?string $sensitiveData = null,
        
        #[WhenCan('view-analytics'), WhenContext('include_analytics')] // Must have permission AND context
        public readonly ?array $analytics = null,
    ) {}
}
```

### Encryption + Conditional Visibility

```php
class SecureDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[Cast(EncryptedCast::class), WhenRole('admin')] // Encrypted AND only for admins
        public readonly ?string $secretKey = null,
    ) {}
}
```

---

## 💡 Best Practices

### 1. Always Hide Sensitive Data

```php
// ✅ Good - sensitive data hidden
#[Hidden]
public readonly string $password

// ❌ Bad - sensitive data exposed
public readonly string $password
```

### 2. Use Encryption for PII

```php
// ✅ Good - PII encrypted
#[Cast(EncryptedCast::class)]
public readonly string $ssn

// ❌ Bad - PII not encrypted
public readonly string $ssn
```

### 3. Use Role-Based Visibility

```php
// ✅ Good - role-based visibility
#[WhenRole('admin')]
public readonly ?array $adminData = null

// ❌ Bad - always visible
public readonly array $adminData
```

### 4. Combine Multiple Security Layers

```php
// ✅ Good - multiple layers
#[Cast(EncryptedCast::class), WhenRole('admin'), WhenCan('view-sensitive')]
public readonly ?string $secretData = null

// ❌ Bad - single layer
#[WhenAuth]
public readonly ?string $secretData = null
```

### 5. Never Log Sensitive Data

```php
// ✅ Good - sensitive data hidden from logs
#[Hidden]
public readonly string $password

// ❌ Bad - sensitive data in logs
public readonly string $password
```

---

## 📚 Next Steps

1. [Conditional Properties](10-conditional-properties.md) - All conditional attributes
2. [Type Casting](06-type-casting.md) - Encryption and hashing casts
3. [Laravel Integration](17-laravel-integration.md) - Laravel security features
4. [Symfony Integration](18-symfony-integration.md) - Symfony security features

---

**Previous:** [Custom Validation](21-custom-validation.md)  
**Next:** [TypeScript Generation](23-typescript-generation.md)

