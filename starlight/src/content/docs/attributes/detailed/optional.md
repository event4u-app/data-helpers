---
title: "#[Optional] - Optional Properties"
description: "Mark properties as optional to distinguish between null and missing values"
---

The `#[Optional]` attribute allows you to mark properties as optional, wrapping them in an `Optional<T>` container. This is particularly useful for **partial updates** where you need to distinguish between:

- A value that was **explicitly set to null**
- A value that was **not provided at all** (missing)

## Basic Usage

```php
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\Support\Optional;

class UpdateUserDto extends LiteDto
{
    public function __construct(
        #[OptionalAttribute]
        public readonly Optional|string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,
        #[OptionalAttribute]
        public readonly Optional|string $phone,
    ) {}
}

// Only update email
$updates = UpdateUserDto::from(['email' => 'new@example.com']);

$updates->name->isEmpty();   // true - not provided
$updates->email->isPresent(); // true - provided
$updates->email->get();       // 'new@example.com'
$updates->phone->isEmpty();   // true - not provided
```

## Distinguishing null vs. missing

```php
class UserDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[OptionalAttribute]
        public readonly Optional|string $email,      // Can be missing
        public readonly ?string $phone,              // Can be null
        #[OptionalAttribute]
        public readonly Optional|string|null $bio,   // Can be missing OR null
    ) {}
}

// Missing email, explicit null phone
$dto = UserDto::from([
    'name' => 'John',
    'phone' => null,
]);

$dto->email->isEmpty();  // true - missing
$dto->phone;             // null - explicitly set

// Explicit null bio
$dto2 = UserDto::from([
    'name' => 'John',
    'phone' => '123',
    'bio' => null,
]);

$dto2->bio->isPresent(); // true - provided
$dto2->bio->get();       // null - explicitly set to null
```

## Optional API

The `Optional<T>` wrapper provides a functional API:

### Checking Presence

```php
$dto = UpdateUserDto::from(['email' => 'john@example.com']);

$dto->email->isPresent(); // true
$dto->email->isEmpty();   // false

$dto->name->isPresent();  // false
$dto->name->isEmpty();    // true
```

### Getting Values

```php
// Get value (throws if empty)
$email = $dto->email->get(); // 'john@example.com'

// Get with default value
$name = $dto->name->orElse('Unknown'); // 'Unknown'

// Get or null
$phone = $dto->phone->orNull(); // null
```

### Transforming Values

```php
// Map transforms the value if present
$uppercase = $dto->email->map(fn($email) => strtoupper($email));
$uppercase->get(); // 'JOHN@EXAMPLE.COM'

// Empty optionals are not transformed
$dto->name->map(fn($name) => strtoupper($name))->isEmpty(); // true

// Filter keeps value only if predicate is true
$longEmail = $dto->email->filter(fn($email) => strlen($email) > 10);
```

### Conditional Execution

```php
// Execute callback if present
$dto->email->ifPresent(function($email) {
    echo "Email: $email";
});

// Execute callback if empty
$dto->name->ifEmpty(function() {
    echo "Name not provided";
});
```

## Serialization

Optional properties are automatically handled in `toArray()` and `toJson()`:

```php
$dto = UpdateUserDto::from([
    'name' => 'John',
    'phone' => '123-456-7890',
]);

// Empty optionals are excluded
$dto->toArray();
// ['name' => 'John', 'phone' => '123-456-7890']

$dto->toJson();
// {"name":"John","phone":"123-456-7890"}

// Present optionals are unwrapped
$dto2 = UpdateUserDto::from([
    'name' => 'John',
    'email' => null,  // explicitly null
]);

$dto2->toArray();
// ['name' => 'John', 'email' => null]
```

## Partial Updates Example

A common use case is updating only specific fields:

```php
class UpdateProductDto extends LiteDto
{
    public function __construct(
        #[OptionalAttribute]
        public readonly Optional|string $name,
        #[OptionalAttribute]
        public readonly Optional|float $price,
        #[OptionalAttribute]
        public readonly Optional|string|null $description,
    ) {}
}

// Update only price
$updates = UpdateProductDto::from(['price' => 29.99]);

// Apply updates to existing product
function applyUpdates(Product $product, UpdateProductDto $updates): void
{
    if ($updates->name->isPresent()) {
        $product->name = $updates->name->get();
    }
    
    if ($updates->price->isPresent()) {
        $product->price = $updates->price->get();
    }
    
    if ($updates->description->isPresent()) {
        // Can be null - that's intentional (clear description)
        $product->description = $updates->description->get();
    }
}
```

## Performance

`#[Optional]` is an **opt-in feature** with **zero performance overhead** when not used:

- Properties **without** `#[Optional]` are not wrapped → no overhead
- Properties **with** `#[Optional]` are wrapped in `Optional<T>` → minimal overhead
- Feature-flag system ensures fast-path optimization

## Compatibility

`#[Optional]` works seamlessly with other LiteDto features:

```php
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\CastWith;

class UpdateUserDto extends LiteDto
{
    public function __construct(
        #[OptionalAttribute]
        #[MapFrom('user_name')]
        public readonly Optional|string $name,
        
        #[OptionalAttribute]
        #[CastWith(EmailCaster::class)]
        public readonly Optional|Email $email,
    ) {}
}
```

## Comparison: #[Optional] vs. #[Sometimes] vs. nullable

| Feature | `#[Optional]` | `#[Sometimes]` | `?type` |
|---------|--------------|----------------|---------|
| **Distinguishes null vs. missing** | ✅ Yes | ❌ No | ❌ No |
| **Partial Updates** | ✅ Optimal | ⚠️ With nullable | ⚠️ Can't distinguish |
| **Validation** | ✅ Only if present | ✅ Only if present | ✅ Always |
| **Wrapper** | ✅ Optional<T> | ❌ No wrapper | ❌ No wrapper |
| **Performance** | ⚠️ Minimal overhead | ✅ No overhead | ✅ No overhead |

### When to use what?

- **Use `#[Optional]`** when you need to distinguish between `null` and `missing` (e.g., partial updates)
- **Use `#[Sometimes]`** when you only want validation if the value is provided
- **Use `?type`** when `null` is a valid value but you don't need to distinguish it from missing

## See Also

- [#[Sometimes]](/litedto/validation/sometimes) - Conditional validation
- [#[Nullable]](/litedto/validation/nullable) - Allow null values
- [Partial Updates Pattern](/patterns/partial-updates) - Best practices for partial updates

