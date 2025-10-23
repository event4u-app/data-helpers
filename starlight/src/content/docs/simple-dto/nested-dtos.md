---
title: Nested DTOs
description: Learn how to work with complex nested DTO structures
---

Learn how to work with complex nested DTO structures.

## What are Nested DTOs?

Nested DTOs allow you to compose complex data structures from simpler DTOs:

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}

$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);
```

## Basic Nesting

### Single Nested DTO

```php
class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $bio,
        public readonly string $avatar,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ProfileDTO $profile,
    ) {}
}
```

### Optional Nested DTO

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?AddressDTO $address = null,
    ) {}
}
```

## Multiple Levels

### Three-Level Nesting

```php
class CityDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $zipCode,
    ) {}
}

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly CityDTO $city,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
    ) {}
}
```

## Collections of Nested DTOs

### Array of DTOs

```php
class OrderItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $product,
        public readonly int $quantity,
        public readonly float $price,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly array $items,  // Array of OrderItemDTO
    ) {}
}

$order = OrderDTO::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2, 'price' => 10.00],
        ['product' => 'Gadget', 'quantity' => 1, 'price' => 20.00],
    ],
]);
```

### Using DataCollection

```php
use Event4u\DataHelpers\SimpleDTO\DataCollection;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly DataCollection $items,
    ) {}
}
```

## Real-World Example

### E-Commerce Order

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

class OrderItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly ProductDTO $product,
        public readonly int $quantity,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->product->price * $this->quantity;
    }
}

class ShippingAddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly DataCollection $items,
        public readonly ShippingAddressDTO $shippingAddress,
        public readonly Carbon $orderDate,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->items->sum(fn($item) => $item->total());
    }
}
```

## Best Practices

### Use Type Hints

```php
// ✅ Good - with type hint
public readonly AddressDTO $address;

// ❌ Bad - no type hint
public readonly $address;
```

### Keep Nesting Shallow

```php
// ✅ Good - 2-3 levels
UserDTO -> AddressDTO -> CityDTO

// ❌ Bad - too deep
UserDTO -> ProfileDTO -> SettingsDTO -> PreferencesDTO -> ThemeDTO
```

### Use Collections for Arrays

```php
// ✅ Good - use DataCollection
public readonly DataCollection $items;

// ❌ Bad - plain array
public readonly array $items;
```

## See Also

- [Collections](/simple-dto/collections/) - Work with collections
- [Type Casting](/simple-dto/type-casting/) - Automatic type conversion
- [Creating DTOs](/simple-dto/creating-dtos/) - Creation methods
