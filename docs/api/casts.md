# Casts API Reference

Complete API reference for all SimpleDTO type casts.

---

## 📋 Table of Contents

- [Primitive Casts](#primitive-casts)
- [Date & Time Casts](#date--time-casts)
- [Enum Casts](#enum-casts)
- [Collection Casts](#collection-casts)
- [Object Casts](#object-casts)
- [Security Casts](#security-casts)
- [Custom Casts](#custom-casts)

---

## Primitive Casts

### StringCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\StringCast`

**Description:** Converts value to string.

**Usage:**
```php
#[Cast(StringCast::class)]
public readonly string $name;
```

**Converts:**
- `123` → `"123"`
- `true` → `"1"`
- `false` → `""`

---

### IntegerCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\IntegerCast`

**Description:** Converts value to integer.

**Usage:**
```php
#[Cast(IntegerCast::class)]
public readonly int $age;
```

**Converts:**
- `"123"` → `123`
- `123.45` → `123`
- `true` → `1`
- `false` → `0`

---

### FloatCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\FloatCast`

**Description:** Converts value to float.

**Usage:**
```php
#[Cast(FloatCast::class)]
public readonly float $price;
```

**Converts:**
- `"123.45"` → `123.45`
- `123` → `123.0`

---

### BooleanCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\BooleanCast`

**Description:** Converts value to boolean.

**Usage:**
```php
#[Cast(BooleanCast::class)]
public readonly bool $active;
```

**Converts:**
- `1`, `"1"`, `"true"`, `"yes"`, `"on"` → `true`
- `0`, `"0"`, `"false"`, `"no"`, `"off"` → `false`

---

## Date & Time Casts

### DateTimeCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast`

**Description:** Converts to Carbon/DateTime instance.

**Options:**
- `format` (string): Input format (default: auto-detect)
- `timezone` (string): Timezone (default: UTC)

**Usage:**
```php
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

#[Cast(DateTimeCast::class, ['format' => 'Y-m-d H:i:s'])]
public readonly Carbon $customFormat;

#[Cast(DateTimeCast::class, ['timezone' => 'America/New_York'])]
public readonly Carbon $localTime;
```

**Converts:**
- `"2024-01-15 10:30:00"` → `Carbon` instance
- `1705315800` → `Carbon` instance (from timestamp)
- `Carbon` instance → `Carbon` instance (unchanged)

---

### DateCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\DateCast`

**Description:** Converts to date only (Y-m-d).

**Usage:**
```php
#[Cast(DateCast::class)]
public readonly Carbon $birthDate;
```

**Converts:**
- `"2024-01-15 10:30:00"` → `Carbon` with time set to 00:00:00
- `"2024-01-15"` → `Carbon` instance

---

### TimeCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\TimeCast`

**Description:** Converts to time only (H:i:s).

**Usage:**
```php
#[Cast(TimeCast::class)]
public readonly Carbon $startTime;
```

**Converts:**
- `"10:30:00"` → `Carbon` with today's date
- `"2024-01-15 10:30:00"` → `Carbon` with today's date and time 10:30:00

---

### TimestampCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\TimestampCast`

**Description:** Converts to Unix timestamp.

**Usage:**
```php
#[Cast(TimestampCast::class)]
public readonly int $timestamp;
```

**Converts:**
- `"2024-01-15 10:30:00"` → `1705315800`
- `Carbon` instance → Unix timestamp

---

## Enum Casts

### EnumCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\EnumCast`

**Description:** Converts to PHP enum.

**Options:**
- `enum` (string): Enum class name (required)

**Usage:**
```php
enum UserRole
{
    case Admin;
    case User;
    case Guest;
}

#[Cast(EnumCast::class, ['enum' => UserRole::class])]
public readonly UserRole $role;
```

**Converts:**
- `"Admin"` → `UserRole::Admin`
- `"User"` → `UserRole::User`

---

### BackedEnumCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\BackedEnumCast`

**Description:** Converts to backed enum.

**Options:**
- `enum` (string): Backed enum class name (required)

**Usage:**
```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

#[Cast(BackedEnumCast::class, ['enum' => Status::class])]
public readonly Status $status;
```

**Converts:**
- `"draft"` → `Status::Draft`
- `"published"` → `Status::Published`

---

## Collection Casts

### ArrayCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\ArrayCast`

**Description:** Converts to array.

**Usage:**
```php
#[Cast(ArrayCast::class)]
public readonly array $tags;
```

**Converts:**
- `"tag1,tag2,tag3"` → `["tag1", "tag2", "tag3"]`
- `'{"key": "value"}'` → `["key" => "value"]`
- `Collection` → `array`

---

### CollectionCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\CollectionCast`

**Description:** Converts to collection of DTOs.

**Options:**
- `itemType` (string): DTO class name (required)

**Usage:**
```php
#[Cast(CollectionCast::class, ['itemType' => UserDTO::class])]
public readonly array $users;
```

**Converts:**
```php
[
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
]
// →
[
    UserDTO(id: 1, name: 'John'),
    UserDTO(id: 2, name: 'Jane'),
]
```

---

## Object Casts

### ObjectCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\ObjectCast`

**Description:** Converts to nested DTO.

**Options:**
- `class` (string): DTO class name (required)

**Usage:**
```php
#[Cast(ObjectCast::class, ['class' => AddressDTO::class])]
public readonly AddressDTO $address;
```

**Converts:**
```php
[
    'street' => '123 Main St',
    'city' => 'New York',
]
// →
AddressDTO(street: '123 Main St', city: 'New York')
```

---

### JsonCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\JsonCast`

**Description:** Converts JSON string to array/object.

**Options:**
- `assoc` (bool): Return associative array (default: true)

**Usage:**
```php
#[Cast(JsonCast::class)]
public readonly array $metadata;

#[Cast(JsonCast::class, ['assoc' => false])]
public readonly object $data;
```

**Converts:**
- `'{"key": "value"}'` → `["key" => "value"]`
- `'[1, 2, 3]'` → `[1, 2, 3]`

---

## Security Casts

### EncryptedCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast`

**Description:** Encrypts/decrypts value.

**Usage:**
```php
#[Cast(EncryptedCast::class)]
public readonly string $ssn;
```

**Behavior:**
- **Input:** Encrypts plain text → encrypted string
- **Output:** Decrypts encrypted string → plain text

**Example:**
```php
$dto = new UserDTO(ssn: '123-45-6789');
// Stored as: "eyJpdiI6IjRmN..."

$decrypted = $dto->ssn; // "123-45-6789"
```

---

### HashedCast

**Namespace:** `event4u\DataHelpers\SimpleDTO\Casts\HashedCast`

**Description:** One-way hashing (for passwords).

**Options:**
- `algorithm` (string): Hash algorithm (default: bcrypt)

**Usage:**
```php
#[Cast(HashedCast::class)]
public readonly string $password;
```

**Behavior:**
- **Input:** Hashes plain text → hashed string
- **Output:** Returns hashed string (cannot be decrypted)

**Example:**
```php
$dto = new UserDTO(password: 'secret123');
// Stored as: "$2y$10$..."

$hashed = $dto->password; // "$2y$10$..."
```

---

## Custom Casts

### Creating Custom Casts

**Interface:** `event4u\DataHelpers\SimpleDTO\Contracts\CastInterface`

**Methods:**
```php
interface CastInterface
{
    public function cast(mixed $value): mixed;
}
```

**Example:**
```php
use event4u\DataHelpers\SimpleDTO\Contracts\CastInterface;

class UpperCaseCast implements CastInterface
{
    public function cast(mixed $value): mixed
    {
        return strtoupper((string) $value);
    }
}
```

**Usage:**
```php
#[Cast(UpperCaseCast::class)]
public readonly string $name;
```

---

### Custom Cast with Options

**Example:**
```php
class PrefixCast implements CastInterface
{
    public function __construct(
        private string $prefix = ''
    ) {}
    
    public function cast(mixed $value): mixed
    {
        return $this->prefix . $value;
    }
}
```

**Usage:**
```php
#[Cast(PrefixCast::class, ['prefix' => 'Mr. '])]
public readonly string $name;
```

---

## Cast Chaining

You can apply multiple casts by creating a custom cast that chains others:

```php
class ChainCast implements CastInterface
{
    public function __construct(
        private array $casts = []
    ) {}
    
    public function cast(mixed $value): mixed
    {
        foreach ($this->casts as $castClass) {
            $cast = new $castClass();
            $value = $cast->cast($value);
        }
        
        return $value;
    }
}
```

**Usage:**
```php
#[Cast(ChainCast::class, ['casts' => [StringCast::class, UpperCaseCast::class]])]
public readonly string $name;
```

---

## Performance

### Cast Caching

Casts are automatically cached for better performance:

```php
// First call: Creates cast instance
$dto1 = UserDTO::fromArray($data);

// Subsequent calls: Reuses cached cast instance
$dto2 = UserDTO::fromArray($data);
```

**Performance Improvement:** ~40% faster, ~60% less memory

---

**See Also:**
- [Attributes API](attributes.md)
- [Methods API](methods.md)
- [Type Casting Guide](../simple-dto/06-type-casting.md)

