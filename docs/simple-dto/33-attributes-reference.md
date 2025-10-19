# Attributes Reference

Complete reference of all attributes available in SimpleDTO.

---

## 🎯 Overview

SimpleDTO provides 50+ attributes organized into categories:

- ✅ **Validation Attributes** (30+) - Data validation
- ✅ **Conditional Attributes** (18) - Visibility control
- ✅ **Cast Attributes** (1) - Type casting
- ✅ **Mapping Attributes** (2) - Property mapping
- ✅ **Computed Attributes** (1) - Calculated properties
- ✅ **Lazy Attributes** (1) - Deferred evaluation
- ✅ **Hidden Attributes** (1) - Always hidden

---

## ✅ Validation Attributes

See [Validation Attributes Reference](20-validation-attributes.md) for complete list of 30+ validation attributes.

**Quick Reference:**
- `#[Required]` - Property must be present
- `#[Email]` - Valid email address
- `#[Min(int $value)]` - Minimum value/length
- `#[Max(int $value)]` - Maximum value/length
- `#[Between(int $min, int $max)]` - Value between range
- `#[In(array $values)]` - Value in array
- `#[Unique(string $table, string $column)]` - Unique in database
- `#[Exists(string $table, string $column)]` - Exists in database
- And 22+ more...

---

## 🎨 Conditional Attributes

### Core Conditional Attributes (9)

#### #[WhenCallback(callable $callback)]
Show property when callback returns true.

```php
#[WhenCallback(fn() => auth()->check())]
public readonly ?string $email = null;
```

#### #[WhenValue(string $property, mixed $value)]
Show property when another property equals value.

```php
#[WhenValue('status', 'active')]
public readonly ?string $activeData = null;
```

#### #[WhenNull(string $property)]
Show property when another property is null.

```php
#[WhenNull('deletedAt')]
public readonly ?string $activeStatus = null;
```

#### #[WhenNotNull(string $property)]
Show property when another property is not null.

```php
#[WhenNotNull('verifiedAt')]
public readonly ?string $verifiedBadge = null;
```

#### #[WhenTrue(string $property)]
Show property when another property is true.

```php
#[WhenTrue('isPublished')]
public readonly ?string $publishedUrl = null;
```

#### #[WhenFalse(string $property)]
Show property when another property is false.

```php
#[WhenFalse('isActive')]
public readonly ?string $inactiveReason = null;
```

#### #[WhenEquals(string $property, mixed $value)]
Show property when another property equals value (alias for WhenValue).

```php
#[WhenEquals('role', 'admin')]
public readonly ?array $adminPanel = null;
```

#### #[WhenIn(string $property, array $values)]
Show property when another property is in array.

```php
#[WhenIn('status', ['active', 'pending'])]
public readonly ?string $actionUrl = null;
```

#### #[WhenInstanceOf(string $property, string $class)]
Show property when another property is instance of class.

```php
#[WhenInstanceOf('user', Admin::class)]
public readonly ?array $adminData = null;
```

### Context-Based Attributes (4)

#### #[WhenContext(string $key)]
Show property when context key exists.

```php
#[WhenContext('include_email')]
public readonly ?string $email = null;
```

#### #[WhenContextEquals(string $key, mixed $value)]
Show property when context key equals value.

```php
#[WhenContextEquals('view', 'detailed')]
public readonly ?array $details = null;
```

#### #[WhenContextIn(string $key, array $values)]
Show property when context key is in array.

```php
#[WhenContextIn('format', ['full', 'detailed'])]
public readonly ?array $extraData = null;
```

#### #[WhenContextNotNull(string $key)]
Show property when context key is not null.

```php
#[WhenContextNotNull('user_id')]
public readonly ?array $userData = null;
```

### Laravel-Specific Attributes (4)

#### #[WhenAuth]
Show property when user is authenticated.

```php
#[WhenAuth]
public readonly ?string $email = null;
```

#### #[WhenGuest]
Show property when user is not authenticated.

```php
#[WhenGuest]
public readonly ?string $loginPrompt = null;
```

#### #[WhenCan(string $ability, ?string $subjectKey = null)]
Show property when user has permission.

```php
#[WhenCan('edit')]
public readonly ?string $editUrl = null;

#[WhenCan('delete', 'subject')]
public readonly ?string $deleteUrl = null;
```

#### #[WhenRole(string|array $roles)]
Show property when user has role(s).

```php
#[WhenRole('admin')]
public readonly ?array $adminPanel = null;

#[WhenRole(['admin', 'moderator'])]
public readonly ?array $moderationTools = null;
```

### Symfony-Specific Attributes (2)

#### #[WhenGranted(string $attribute, ?string $subjectKey = null)]
Show property when security is granted.

```php
#[WhenGranted('ROLE_ADMIN')]
public readonly ?array $adminData = null;

#[WhenGranted('EDIT', 'subject')]
public readonly ?string $editUrl = null;
```

#### #[WhenSymfonyRole(string|array $roles)]
Show property when user has Symfony role(s).

```php
#[WhenSymfonyRole('ROLE_ADMIN')]
public readonly ?array $adminPanel = null;

#[WhenSymfonyRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
public readonly ?array $moderationTools = null;
```

---

## 🔄 Cast Attribute

### #[Cast(string $castClass, array $options = [])]
Apply type cast to property.

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(DateTimeCast::class, ['format' => 'Y-m-d'])]
public readonly Carbon $date;
```

---

## 🗺️ Mapping Attributes

### #[MapFrom(string $key)]
Map from different input key.

```php
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

#[MapFrom('user_name')]
public readonly string $name;

#[MapFrom('profile.bio')]
public readonly string $bio;
```

### #[MapTo(string $key)]
Map to different output key.

```php
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;

#[MapTo('user_name')]
public readonly string $name;

#[MapTo('profile.bio')]
public readonly string $bio;
```

---

## 🧮 Computed Attribute

### #[Computed]
Mark property as computed (calculated from other properties).

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
    
    #[Computed]
    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

---

## ⏳ Lazy Attribute

### #[Lazy]
Mark property as lazy (only evaluated when accessed).

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        
        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
}
```

---

## 🔒 Hidden Attribute

### #[Hidden]
Always hide property from serialization.

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Hidden]
        public readonly string $password,
        
        #[Hidden]
        public readonly ?string $apiToken = null,
    ) {}
}
```

---

## 🎯 Combining Attributes

You can combine multiple attributes on a single property:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        // Validation + Mapping
        #[Required, Email, MapFrom('user_email')]
        public readonly string $email,
        
        // Validation + Cast
        #[Required, Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        // Conditional + Cast
        #[WhenAuth, Cast(EncryptedCast::class)]
        public readonly ?string $ssn = null,
        
        // Multiple Conditionals
        #[WhenAuth, WhenRole('admin')]
        public readonly ?array $adminData = null,
    ) {}
}
```

---

## 📋 Attribute Categories Summary

### By Purpose

**Data Validation:**
- 30+ validation attributes
- See [Validation Attributes](20-validation-attributes.md)

**Visibility Control:**
- 18 conditional attributes
- 1 hidden attribute

**Data Transformation:**
- 1 cast attribute (20+ cast classes)
- 2 mapping attributes

**Computed Values:**
- 1 computed attribute
- 1 lazy attribute

### By Framework

**Framework-Agnostic:**
- All validation attributes
- Core conditional attributes (9)
- Cast, mapping, computed, lazy, hidden

**Laravel-Specific:**
- WhenAuth, WhenGuest, WhenCan, WhenRole

**Symfony-Specific:**
- WhenGranted, WhenSymfonyRole

---

## 💡 Best Practices

### 1. Use Specific Attributes

```php
// ✅ Good - specific attribute
#[Email]
public readonly string $email

// ❌ Bad - generic regex
#[Regex('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/')]
public readonly string $email
```

### 2. Combine Related Attributes

```php
// ✅ Good - combined
#[Required, Email, Unique('users', 'email')]
public readonly string $email

// ❌ Bad - separate properties
```

### 3. Order Attributes Logically

```php
// ✅ Good - validation, then transformation, then visibility
#[Required, Email, MapFrom('user_email'), WhenAuth]
public readonly string $email

// ❌ Bad - random order
#[WhenAuth, Required, MapFrom('user_email'), Email]
public readonly string $email
```

---

## 📚 Next Steps

1. [Validation Attributes](20-validation-attributes.md) - All validation attributes
2. [Conditional Properties](10-conditional-properties.md) - Conditional attributes
3. [Type Casting](06-type-casting.md) - Cast classes
4. [Property Mapping](08-property-mapping.md) - Mapping attributes

---

**Previous:** [Troubleshooting](32-troubleshooting.md)  
**Next:** [Casts Reference](34-casts-reference.md)

