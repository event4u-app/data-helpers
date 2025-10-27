---
title: Nested Dtos
description: Learn how to work with complex nested Dto structures
---

Learn how to work with complex nested Dto structures.

## What are Nested Dtos?

Nested Dtos allow you to compose complex data structures from simpler Dtos:

```php
class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}

$user = UserDto::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);
```

## Basic Nesting

### Single Nested Dto

```php
class ProfileDto extends SimpleDto
{
    public function __construct(
        public readonly string $bio,
        public readonly string $avatar,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ProfileDto $profile,
    ) {}
}
```

### Optional Nested Dto

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?AddressDto $address = null,
    ) {}
}
```

## Multiple Levels

### Three-Level Nesting

```php
class CityDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $zipCode,
    ) {}
}

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly CityDto $city,
    ) {}
}

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDto $address,
    ) {}
}
```

## Collections of Nested Dtos

### Array of Dtos

```php
class OrderItemDto extends SimpleDto
{
    public function __construct(
        public readonly string $product,
        public readonly int $quantity,
        public readonly float $price,
    ) {}
}

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $orderId,
        public readonly array $items,  // Array of OrderItemDto
    ) {}
}

$order = OrderDto::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2, 'price' => 10.00],
        ['product' => 'Gadget', 'quantity' => 1, 'price' => 20.00],
    ],
]);
```

### Using DataCollection

```php
use event4u\DataHelpers\SimpleDto\DataCollection;

class OrderDto extends SimpleDto
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
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
    ) {}
}

class OrderItemDto extends SimpleDto
{
    public function __construct(
        public readonly ProductDto $product,
        public readonly int $quantity,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->product->price * $this->quantity;
    }
}

class ShippingAddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $orderId,
        public readonly DataCollection $items,
        public readonly ShippingAddressDto $shippingAddress,
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
public readonly AddressDto $address;

// ❌ Bad - no type hint
public readonly $address;
```

### Keep Nesting Shallow

```php
// ✅ Good - 2-3 levels
UserDto -> AddressDto -> CityDto

// ❌ Bad - too deep
UserDto -> ProfileDto -> SettingsDto -> PreferencesDto -> ThemeDto
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
- [Creating Dtos](/simple-dto/creating-dtos/) - Creation methods
