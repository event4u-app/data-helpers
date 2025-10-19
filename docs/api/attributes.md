# Attributes API Reference

Complete API reference for all SimpleDTO attributes.

---

## 📋 Table of Contents

- [Validation Attributes](#validation-attributes) (30+)
- [Conditional Attributes](#conditional-attributes) (18)
- [Cast Attributes](#cast-attributes) (1)
- [Mapping Attributes](#mapping-attributes) (2)
- [Computed Attributes](#computed-attributes) (1)
- [Lazy Attributes](#lazy-attributes) (1)
- [Hidden Attributes](#hidden-attributes) (1)

---

## Validation Attributes

### Required

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Required`

**Description:** Property must be present and not null.

**Usage:**
```php
#[Required]
public readonly string $name;
```

**Laravel Rule:** `required`

---

### Nullable

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Nullable`

**Description:** Property can be null.

**Usage:**
```php
#[Nullable]
public readonly ?string $middleName = null;
```

**Laravel Rule:** `nullable`

---

### Email

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Email`

**Description:** Property must be a valid email address.

**Usage:**
```php
#[Email]
public readonly string $email;
```

**Laravel Rule:** `email`

---

### URL

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\URL`

**Description:** Property must be a valid URL.

**Usage:**
```php
#[URL]
public readonly string $website;
```

**Laravel Rule:** `url`

---

### Min

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Min`

**Description:** Minimum value or length.

**Constructor:**
```php
public function __construct(public int|float $value)
```

**Usage:**
```php
#[Min(3)]
public readonly string $name;

#[Min(18)]
public readonly int $age;
```

**Laravel Rule:** `min:{value}`

---

### Max

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Max`

**Description:** Maximum value or length.

**Constructor:**
```php
public function __construct(public int|float $value)
```

**Usage:**
```php
#[Max(100)]
public readonly string $name;

#[Max(120)]
public readonly int $age;
```

**Laravel Rule:** `max:{value}`

---

### Between

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Between`

**Description:** Value must be between min and max.

**Constructor:**
```php
public function __construct(
    public int|float $min,
    public int|float $max
)
```

**Usage:**
```php
#[Between(18, 65)]
public readonly int $age;

#[Between(0, 100)]
public readonly float $percentage;
```

**Laravel Rule:** `between:{min},{max}`

---

### In

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\In`

**Description:** Value must be in the given array.

**Constructor:**
```php
public function __construct(public array $values)
```

**Usage:**
```php
#[In(['draft', 'published', 'archived'])]
public readonly string $status;
```

**Laravel Rule:** `in:{values}`

---

### NotIn

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\NotIn`

**Description:** Value must not be in the given array.

**Constructor:**
```php
public function __construct(public array $values)
```

**Usage:**
```php
#[NotIn(['admin', 'root'])]
public readonly string $username;
```

**Laravel Rule:** `not_in:{values}`

---

### Regex

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Regex`

**Description:** Value must match the regular expression.

**Constructor:**
```php
public function __construct(public string $pattern)
```

**Usage:**
```php
#[Regex('/^[A-Z][a-z]+$/')]
public readonly string $name;

#[Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
public readonly string $password;
```

**Laravel Rule:** `regex:{pattern}`

---

### Unique

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Unique`

**Description:** Value must be unique in database table.

**Constructor:**
```php
public function __construct(
    public string $table,
    public string $column,
    public ?int $ignoreId = null
)
```

**Usage:**
```php
#[Unique('users', 'email')]
public readonly string $email;

#[Unique('users', 'email', ignoreId: 1)]
public readonly string $email;
```

**Laravel Rule:** `unique:{table},{column},{ignoreId}`

---

### Exists

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Exists`

**Description:** Value must exist in database table.

**Constructor:**
```php
public function __construct(
    public string $table,
    public string $column = 'id'
)
```

**Usage:**
```php
#[Exists('categories', 'id')]
public readonly int $categoryId;
```

**Laravel Rule:** `exists:{table},{column}`

---

### Accepted

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Accepted`

**Description:** Value must be accepted (true, 1, "yes", "on").

**Usage:**
```php
#[Accepted]
public readonly bool $termsAccepted;
```

**Laravel Rule:** `accepted`

---

### Same

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Same`

**Description:** Value must match another field.

**Constructor:**
```php
public function __construct(public string $field)
```

**Usage:**
```php
#[Same('password')]
public readonly string $passwordConfirmation;
```

**Laravel Rule:** `same:{field}`

---

### Different

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Different`

**Description:** Value must be different from another field.

**Constructor:**
```php
public function __construct(public string $field)
```

**Usage:**
```php
#[Different('oldPassword')]
public readonly string $newPassword;
```

**Laravel Rule:** `different:{field}`

---

### StringType

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\StringType`

**Description:** Value must be a string.

**Usage:**
```php
#[StringType]
public readonly string $name;
```

**Laravel Rule:** `string`

---

### Integer

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Integer`

**Description:** Value must be an integer.

**Usage:**
```php
#[Integer]
public readonly int $age;
```

**Laravel Rule:** `integer`

---

### Boolean

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Boolean`

**Description:** Value must be a boolean.

**Usage:**
```php
#[Boolean]
public readonly bool $active;
```

**Laravel Rule:** `boolean`

---

### ArrayType

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\ArrayType`

**Description:** Value must be an array.

**Usage:**
```php
#[ArrayType]
public readonly array $tags;
```

**Laravel Rule:** `array`

---

### Numeric

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Numeric`

**Description:** Value must be numeric.

**Usage:**
```php
#[Numeric]
public readonly float $price;
```

**Laravel Rule:** `numeric`

---

## Conditional Attributes

### Core Conditional Attributes (9)

#### WhenCallback

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback`

**Description:** Show property when callback returns true.

**Constructor:**
```php
public function __construct(public \Closure $callback)
```

**Usage:**
```php
#[WhenCallback(fn() => auth()->check())]
public readonly ?string $email = null;
```

---

#### WhenValue

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenValue`

**Description:** Show property when another property equals value.

**Constructor:**
```php
public function __construct(
    public string $property,
    public mixed $value
)
```

**Usage:**
```php
#[WhenValue('status', 'active')]
public readonly ?string $activeData = null;
```

---

#### WhenNull

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenNull`

**Description:** Show property when another property is null.

**Constructor:**
```php
public function __construct(public string $property)
```

**Usage:**
```php
#[WhenNull('deletedAt')]
public readonly ?string $activeStatus = null;
```

---

#### WhenNotNull

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull`

**Description:** Show property when another property is not null.

**Constructor:**
```php
public function __construct(public string $property)
```

**Usage:**
```php
#[WhenNotNull('verifiedAt')]
public readonly ?string $verifiedBadge = null;
```

---

#### WhenTrue

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenTrue`

**Description:** Show property when another property is true.

**Constructor:**
```php
public function __construct(public string $property)
```

**Usage:**
```php
#[WhenTrue('isPublished')]
public readonly ?string $publishedUrl = null;
```

---

#### WhenFalse

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenFalse`

**Description:** Show property when another property is false.

**Constructor:**
```php
public function __construct(public string $property)
```

**Usage:**
```php
#[WhenFalse('isActive')]
public readonly ?string $inactiveReason = null;
```

---

#### WhenEquals

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals`

**Description:** Show property when another property equals value (alias for WhenValue).

**Constructor:**
```php
public function __construct(
    public string $property,
    public mixed $value
)
```

**Usage:**
```php
#[WhenEquals('role', 'admin')]
public readonly ?array $adminPanel = null;
```

---

#### WhenIn

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenIn`

**Description:** Show property when another property is in array.

**Constructor:**
```php
public function __construct(
    public string $property,
    public array $values
)
```

**Usage:**
```php
#[WhenIn('status', ['active', 'pending'])]
public readonly ?string $actionUrl = null;
```

---

#### WhenInstanceOf

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenInstanceOf`

**Description:** Show property when another property is instance of class.

**Constructor:**
```php
public function __construct(
    public string $property,
    public string $class
)
```

**Usage:**
```php
#[WhenInstanceOf('user', Admin::class)]
public readonly ?array $adminData = null;
```

---

### Context-Based Attributes (4)

#### WhenContext

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenContext`

**Description:** Show property when context key exists.

**Constructor:**
```php
public function __construct(public string $key)
```

**Usage:**
```php
#[WhenContext('include_email')]
public readonly ?string $email = null;
```

---

#### WhenContextEquals

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals`

**Description:** Show property when context key equals value.

**Constructor:**
```php
public function __construct(
    public string $key,
    public mixed $value
)
```

**Usage:**
```php
#[WhenContextEquals('view', 'detailed')]
public readonly ?array $details = null;
```

---

#### WhenContextIn

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn`

**Description:** Show property when context key is in array.

**Constructor:**
```php
public function __construct(
    public string $key,
    public array $values
)
```

**Usage:**
```php
#[WhenContextIn('format', ['full', 'detailed'])]
public readonly ?array $extraData = null;
```

---

#### WhenContextNotNull

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenContextNotNull`

**Description:** Show property when context key is not null.

**Constructor:**
```php
public function __construct(public string $key)
```

**Usage:**
```php
#[WhenContextNotNull('user_id')]
public readonly ?array $userData = null;
```

---

### Laravel-Specific Attributes (4)

#### WhenAuth

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth`

**Description:** Show property when user is authenticated.

**Usage:**
```php
#[WhenAuth]
public readonly ?string $email = null;
```

---

#### WhenGuest

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenGuest`

**Description:** Show property when user is not authenticated.

**Usage:**
```php
#[WhenGuest]
public readonly ?string $loginPrompt = null;
```

---

#### WhenCan

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenCan`

**Description:** Show property when user has permission.

**Constructor:**
```php
public function __construct(
    public string $ability,
    public ?string $subjectKey = null
)
```

**Usage:**
```php
#[WhenCan('edit')]
public readonly ?string $editUrl = null;

#[WhenCan('delete', 'subject')]
public readonly ?string $deleteUrl = null;
```

---

#### WhenRole

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenRole`

**Description:** Show property when user has role(s).

**Constructor:**
```php
public function __construct(public string|array $roles)
```

**Usage:**
```php
#[WhenRole('admin')]
public readonly ?array $adminPanel = null;

#[WhenRole(['admin', 'moderator'])]
public readonly ?array $moderationTools = null;
```

---

### Symfony-Specific Attributes (2)

#### WhenGranted

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenGranted`

**Description:** Show property when security is granted.

**Constructor:**
```php
public function __construct(
    public string $attribute,
    public ?string $subjectKey = null
)
```

**Usage:**
```php
#[WhenGranted('ROLE_ADMIN')]
public readonly ?array $adminData = null;

#[WhenGranted('EDIT', 'subject')]
public readonly ?string $editUrl = null;
```

---

#### WhenSymfonyRole

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\WhenSymfonyRole`

**Description:** Show property when user has Symfony role(s).

**Constructor:**
```php
public function __construct(public string|array $roles)
```

**Usage:**
```php
#[WhenSymfonyRole('ROLE_ADMIN')]
public readonly ?array $adminPanel = null;
```

---

## Cast Attributes

### Cast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Cast`

**Description:** Apply type cast to property.

**Constructor:**
```php
public function __construct(
    public string $castClass,
    public array $options = []
)
```

**Usage:**
```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(DateTimeCast::class, ['format' => 'Y-m-d'])]
public readonly Carbon $date;
```

---

## Mapping Attributes

### MapFrom

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\MapFrom`

**Description:** Map from different input key.

**Constructor:**
```php
public function __construct(public string $key)
```

**Usage:**
```php
#[MapFrom('user_name')]
public readonly string $name;

#[MapFrom('profile.bio')]
public readonly string $bio;
```

---

### MapTo

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\MapTo`

**Description:** Map to different output key.

**Constructor:**
```php
public function __construct(public string $key)
```

**Usage:**
```php
#[MapTo('user_name')]
public readonly string $name;
```

---

## Computed Attributes

### Computed

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Computed`

**Description:** Mark method as computed property.

**Usage:**
```php
#[Computed]
public function fullName(): string
{
    return $this->firstName . ' ' . $this->lastName;
}
```

---

## Lazy Attributes

### Lazy

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Lazy`

**Description:** Mark property as lazy (only evaluated when accessed).

**Usage:**
```php
#[Lazy]
public readonly ?array $posts = null;
```

---

## Hidden Attributes

### Hidden

**Namespace:** `event4u\DataHelpers\SimpleDTO\Attributes\Hidden`

**Description:** Always hide property from serialization.

**Usage:**
```php
#[Hidden]
public readonly string $password;
```

---

**See Also:**
- [Casts API](casts.md)
- [Methods API](methods.md)
- [User Guide](../simple-dto/README.md)

