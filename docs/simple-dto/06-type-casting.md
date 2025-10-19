# Type Casting

Learn how SimpleDTO automatically converts data types using built-in and custom casts.

---

## 🎯 What is Type Casting?

Type casting automatically converts input data to the correct PHP type. For example:

```php
// Input: string "30"
// Output: int 30

// Input: string "2024-01-15"
// Output: Carbon instance
```

---

## 🔧 Built-in Casts

SimpleDTO provides 20+ built-in casts for common data types.

### Primitive Types

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,      // String cast
        public readonly int $age,          // Integer cast
        public readonly float $price,      // Float cast
        public readonly bool $active,      // Boolean cast
        public readonly array $tags,       // Array cast
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 123,              // → "123"
    'age' => "30",              // → 30
    'price' => "19.99",         // → 19.99
    'active' => "1",            // → true
    'tags' => "tag1,tag2",      // → ["tag1,tag2"]
]);
```

---

## 📅 Date and Time Casts

### DateTimeCast

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

class EventDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $startDate,
        
        #[Cast(DateTimeCast::class, format: 'Y-m-d')]
        public readonly Carbon $endDate,
    ) {}
}

$dto = EventDTO::fromArray([
    'title' => 'Conference',
    'startDate' => '2024-01-15 10:00:00',
    'endDate' => '2024-01-15',
]);

echo $dto->startDate->format('F j, Y'); // January 15, 2024
```

### DateCast

```php
use event4u\DataHelpers\SimpleDTO\Casts\DateCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Cast(DateCast::class)]
        public readonly Carbon $birthDate,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'birthDate' => '1990-05-15',
]);
```

---

## 🔢 Enum Casts

### EnumCast

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\EnumCast;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Cast(EnumCast::class)]
        public readonly Status $status,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'status' => 'active',  // String → Status::ACTIVE
]);

echo $dto->status->value; // active
```

### BackedEnumCast

```php
enum Priority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

class TaskDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[Cast(EnumCast::class)]
        public readonly Priority $priority,
    ) {}
}

$dto = TaskDTO::fromArray([
    'title' => 'Important Task',
    'priority' => 3,  // Int → Priority::HIGH
]);
```

---

## 📦 Collection Casts

### ArrayCast

```php
use event4u\DataHelpers\SimpleDTO\Casts\ArrayCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Cast(ArrayCast::class)]
        public readonly array $tags,
        
        #[Cast(ArrayCast::class, itemType: 'int')]
        public readonly array $scores,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'tags' => 'tag1,tag2,tag3',  // String → Array
    'scores' => ['10', '20', '30'],  // String[] → Int[]
]);
```

### CollectionCast

```php
use event4u\DataHelpers\SimpleDTO\Casts\CollectionCast;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        
        #[Cast(CollectionCast::class, itemType: OrderItemDTO::class)]
        public readonly array $items,
    ) {}
}

$dto = OrderDTO::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2],
        ['product' => 'Gadget', 'quantity' => 1],
    ],
]);
```

---

## 🎨 Object Casts

### ObjectCast

```php
use event4u\DataHelpers\SimpleDTO\Casts\ObjectCast;

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Cast(ObjectCast::class, type: AddressDTO::class)]
        public readonly AddressDTO $address,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
    ],
]);
```

---

## 🔐 Encrypted Casts

### EncryptedCast

```php
use event4u\DataHelpers\SimpleDTO\Casts\EncryptedCast;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Cast(EncryptedCast::class)]
        public readonly string $ssn,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'ssn' => '123-45-6789',  // Encrypted when stored
]);

// Automatically decrypted when accessed
echo $dto->ssn; // 123-45-6789
```

---

## 🎯 Custom Casts

### Creating a Custom Cast

```php
use event4u\DataHelpers\SimpleDTO\Contracts\Cast;

class UpperCaseCast implements Cast
{
    public function cast(mixed $value): mixed
    {
        return strtoupper($value);
    }
    
    public function uncast(mixed $value): mixed
    {
        return strtolower($value);
    }
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Cast(UpperCaseCast::class)]
        public readonly string $name,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'john doe',  // → "JOHN DOE"
]);
```

### Custom Cast with Parameters

```php
class TruncateCast implements Cast
{
    public function __construct(
        private int $length = 100
    ) {}
    
    public function cast(mixed $value): mixed
    {
        return substr($value, 0, $this->length);
    }
    
    public function uncast(mixed $value): mixed
    {
        return $value;
    }
}

class PostDTO extends SimpleDTO
{
    public function __construct(
        #[Cast(TruncateCast::class, length: 50)]
        public readonly string $title,
    ) {}
}
```

---

## 📋 All Built-in Casts

| Cast | Description | Example |
|------|-------------|---------|
| `StringCast` | Convert to string | `123` → `"123"` |
| `IntegerCast` | Convert to integer | `"30"` → `30` |
| `FloatCast` | Convert to float | `"19.99"` → `19.99` |
| `BooleanCast` | Convert to boolean | `"1"` → `true` |
| `ArrayCast` | Convert to array | `"a,b"` → `["a","b"]` |
| `DateTimeCast` | Convert to Carbon | `"2024-01-15"` → `Carbon` |
| `DateCast` | Convert to Carbon (date only) | `"2024-01-15"` → `Carbon` |
| `EnumCast` | Convert to enum | `"active"` → `Status::ACTIVE` |
| `ObjectCast` | Convert to object | `[...]` → `AddressDTO` |
| `CollectionCast` | Convert to collection | `[...]` → `Collection` |
| `JsonCast` | Parse JSON | `"{...}"` → `array` |
| `EncryptedCast` | Encrypt/decrypt | `"secret"` → encrypted |
| `HashCast` | Hash value | `"password"` → hashed |
| `UrlCast` | Validate URL | `"example.com"` → `"https://example.com"` |
| `EmailCast` | Normalize email | `"JOHN@EXAMPLE.COM"` → `"john@example.com"` |
| `PhoneCast` | Format phone | `"1234567890"` → `"+1 (234) 567-8900"` |
| `MoneyCast` | Format money | `1999` → `"$19.99"` |
| `PercentageCast` | Format percentage | `0.15` → `"15%"` |
| `SlugCast` | Create slug | `"Hello World"` → `"hello-world"` |
| `UuidCast` | Validate UUID | `"..."` → UUID |

---

## 🎨 Cast Options

### Format Option

```php
#[Cast(DateTimeCast::class, format: 'Y-m-d H:i:s')]
public readonly Carbon $createdAt;
```

### Nullable Option

```php
#[Cast(DateTimeCast::class, nullable: true)]
public readonly ?Carbon $deletedAt;
```

### Default Option

```php
#[Cast(IntegerCast::class, default: 0)]
public readonly int $count;
```

---

## 💡 Best Practices

### 1. Use Specific Casts

```php
// ✅ Good - explicit cast
#[Cast(DateTimeCast::class)]
public readonly Carbon $createdAt;

// ❌ Bad - relying on type hint only
public readonly Carbon $createdAt;
```

### 2. Specify Format for Dates

```php
// ✅ Good - explicit format
#[Cast(DateTimeCast::class, format: 'Y-m-d')]
public readonly Carbon $date;

// ❌ Bad - ambiguous format
#[Cast(DateTimeCast::class)]
public readonly Carbon $date;
```

### 3. Use Nullable for Optional Fields

```php
// ✅ Good
#[Cast(DateTimeCast::class, nullable: true)]
public readonly ?Carbon $deletedAt;

// ❌ Bad
#[Cast(DateTimeCast::class)]
public readonly ?Carbon $deletedAt;
```

---

## 📚 Next Steps

1. [Validation](07-validation.md) - Validate your data
2. [Property Mapping](08-property-mapping.md) - Map property names
3. [Nested DTOs](16-nested-dtos.md) - Complex structures
4. [Casts Reference](34-casts-reference.md) - Complete cast list

---

**Previous:** [Creating DTOs](05-creating-dtos.md)  
**Next:** [Validation](07-validation.md)

