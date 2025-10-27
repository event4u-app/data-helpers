---
title: Computed Properties
description: Learn how to calculate values on-the-fly using computed properties
---

Learn how to calculate values on-the-fly using computed properties.

## What are Computed Properties?

Computed properties are methods that calculate values based on other properties and are automatically included in serialization:

```php
class UserDto extends SimpleDto
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

$dto = new UserDto(firstName: 'John', lastName: 'Doe');
echo $dto->fullName();  // John Doe

$array = $dto->toArray();
// ['firstName' => 'John', 'lastName' => 'Doe', 'fullName' => 'John Doe']
```

## Basic Usage

### Using #[Computed] Attribute

```php
use event4u\DataHelpers\SimpleDto\Attributes\Computed;

class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly float $price,
        public readonly float $taxRate,
    ) {}

    #[Computed]
    public function priceWithTax(): float
    {
        return $this->price * (1 + $this->taxRate);
    }

    #[Computed]
    public function taxAmount(): float
    {
        return $this->price * $this->taxRate;
    }
}

$dto = new ProductDto(price: 100, taxRate: 0.19);
echo $dto->priceWithTax();  // 119.0
echo $dto->taxAmount();     // 19.0
```

## Real-World Examples

### User Profile

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly Carbon $birthDate,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    #[Computed]
    public function age(): int
    {
        return $this->birthDate->age;
    }

    #[Computed]
    public function initials(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }
}
```

### Order Calculations

```php
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $taxRate,
        public readonly float $shippingCost,
        public readonly float $discount,
    ) {}

    #[Computed]
    public function taxAmount(): float
    {
        return $this->subtotal * $this->taxRate;
    }

    #[Computed]
    public function total(): float
    {
        return $this->subtotal + $this->taxAmount() + $this->shippingCost - $this->discount;
    }

    #[Computed]
    public function savings(): float
    {
        return $this->discount;
    }
}
```

## Combining with Other Features

### Computed + Conditional

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    #[Computed]
    #[WhenAuth]
    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### Computed + Lazy

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $userId,
    ) {}

    #[Computed]
    #[Lazy]
    public function statistics(): array
    {
        return [
            'posts' => Post::where('user_id', $this->userId)->count(),
            'comments' => Comment::where('user_id', $this->userId)->count(),
        ];
    }
}
```

### Computed + Hidden

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    #[Computed]
    #[Hidden]
    public function internalId(): string
    {
        return md5($this->firstName . $this->lastName);
    }
}

$array = $dto->toArray();
// ['firstName' => 'John', 'lastName' => 'Doe']
// internalId is excluded
```

## Best Practices

### Keep Computations Simple

```php
// ✅ Good - simple calculation
#[Computed]
public function fullName(): string
{
    return $this->firstName . ' ' . $this->lastName;
}

// ❌ Bad - complex logic
#[Computed]
public function complexCalculation(): array
{
    // 100 lines of complex logic
}
```

### Use Type Hints

```php
// ✅ Good - with type hint
#[Computed]
public function total(): float
{
    return $this->price * $this->quantity;
}

// ❌ Bad - no type hint
#[Computed]
public function total()
{
    return $this->price * $this->quantity;
}
```

### Cache Expensive Computations

```php
class UserDto extends SimpleDto
{
    private ?array $cachedStats = null;

    #[Computed]
    #[Lazy]
    public function statistics(): array
    {
        if ($this->cachedStats === null) {
            $this->cachedStats = [
                'posts' => Post::where('user_id', $this->userId)->count(),
                'comments' => Comment::where('user_id', $this->userId)->count(),
            ];
        }

        return $this->cachedStats;
    }
}
```

## Code Examples

The following working examples demonstrate this feature:

- [**Basic Computed**](https://github.com/event4u-app/data-helpers/blob/main/examples/simple-dto/computed-properties/basic-computed.php) - Simple computed properties

All examples are fully tested and can be run directly.

## Related Tests

The functionality is thoroughly tested. Key test files:

- [ComputedPropertiesTest.php](https://github.com/event4u-app/data-helpers/blob/main/tests/Unit/SimpleDto/ComputedPropertiesTest.php) - Computed property tests

Run the tests:

```bash
# Run tests
task test:unit -- --filter=Computed
```
## See Also

- [Lazy Properties](/data-helpers/simple-dto/lazy-properties/) - Defer expensive operations
- [Conditional Properties](/data-helpers/simple-dto/conditional-properties/) - Dynamic visibility
- [Type Casting](/data-helpers/simple-dto/type-casting/) - Automatic type conversion
