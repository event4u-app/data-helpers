---
title: Extending Dtos
description: Extend SimpleDto with custom functionality
---

Extend SimpleDto with custom functionality.

## Introduction

Extend Dtos to add custom behavior:

- ✅ **Custom Methods** - Add business logic
- ✅ **Traits** - Reuse functionality
- ✅ **Inheritance** - Share common properties
- ✅ **Interfaces** - Define contracts

## Custom Methods

### Adding Business Logic

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
    ) {}

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getInitials(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }

    public function isEmailVerified(): bool
    {
        return User::where('email', $this->email)
            ->whereNotNull('email_verified_at')
            ->exists();
    }
}
```

### Validation Methods

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock < 10;
    }

    public function canPurchase(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
```

## Using Traits

### Creating a Trait

```php
trait Timestampable
{
    public readonly Carbon $createdAt;
    public readonly Carbon $updatedAt;

    public function isNew(): bool
    {
        return $this->createdAt->diffInDays(now()) < 7;
    }

    public function wasRecentlyUpdated(): bool
    {
        return $this->updatedAt->diffInHours(now()) < 24;
    }
}
```

### Using the Trait

```php
class PostDto extends SimpleDto
{
    use Timestampable;

    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}
}

$post = PostDto::fromArray($data);
if ($post->isNew()) {
    echo 'New post!';
}
```

## Inheritance

### Base Dto

```php
abstract class BaseDto extends SimpleDto
{
    public readonly int $id;
    public readonly Carbon $createdAt;
    public readonly Carbon $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }
}
```

### Extending Base Dto

```php
class UserDto extends BaseDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}
}

class PostDto extends BaseDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}
}
```

## Implementing Interfaces

### Creating an Interface

```php
interface Searchable
{
    public function getSearchableFields(): array;
    public function getSearchWeight(): int;
}
```

### Implementing the Interface

```php
class ProductDto extends SimpleDto implements Searchable
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $sku,
    ) {}

    public function getSearchableFields(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
        ];
    }

    public function getSearchWeight(): int
    {
        return 10;
    }
}
```

## Real-World Examples

### User Dto with Permissions

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly array $permissions,
    ) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }
}
```

### Order Dto with Calculations

```php
class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly array $items,
        public readonly float $taxRate,
        public readonly float $shippingCost,
    ) {}

    public function getSubtotal(): float
    {
        return array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $this->items
        ));
    }

    public function getTax(): float
    {
        return $this->getSubtotal() * $this->taxRate;
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax() + $this->shippingCost;
    }

    public function getItemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }
}
```

### Product Dto with Formatting

```php
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly string $currency,
    ) {}

    public function getFormattedPrice(): string
    {
        $price = $this->priceInCents / 100;

        return match($this->currency) {
            'USD' => '$' . number_format($price, 2),
            'EUR' => '€' . number_format($price, 2),
            'GBP' => '£' . number_format($price, 2),
            default => $this->currency . ' ' . number_format($price, 2),
        };
    }

    public function getPriceInDollars(): float
    {
        return $this->priceInCents / 100;
    }
}
```

## Advanced Patterns

### Factory Methods

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
    ) {}

    public static function createAdmin(string $name, string $email): self
    {
        return new self($name, $email, 'admin');
    }

    public static function createUser(string $name, string $email): self
    {
        return new self($name, $email, 'user');
    }
}

$admin = UserDto::createAdmin('John Doe', 'john@example.com');
```

### Builder Pattern

```php
class UserDtoBuilder
{
    private array $data = [];

    public function setName(string $name): self
    {
        $this->data['name'] = $name;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->data['email'] = $email;
        return $this;
    }

    public function setRole(string $role): self
    {
        $this->data['role'] = $role;
        return $this;
    }

    public function build(): UserDto
    {
        return UserDto::fromArray($this->data);
    }
}

$user = (new UserDtoBuilder())
    ->setName('John Doe')
    ->setEmail('john@example.com')
    ->setRole('admin')
    ->build();
```

### Immutable Updates

```php
class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    public function withName(string $name): self
    {
        return new self($name, $this->email);
    }

    public function withEmail(string $email): self
    {
        return new self($this->name, $email);
    }
}

$user = UserDto::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$updated = $user->withName('Jane');
```

## Best Practices

### Single Responsibility

```php
// ✅ Good - single responsibility
class UserDto extends SimpleDto
{
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}

// ❌ Bad - multiple responsibilities
class UserDto extends SimpleDto
{
    public function getFullName(): string { }
    public function sendEmail(): void { }
    public function saveToDatabase(): void { }
}
```

### Immutability

```php
// ✅ Good - immutable
public function withName(string $name): self
{
    return new self($name, $this->email);
}

// ❌ Bad - mutable
public function setName(string $name): void
{
    $this->name = $name;
}
```

### Type Hints

```php
// ✅ Good - type hints
public function getFullName(): string
{
    return "{$this->firstName} {$this->lastName}";
}

// ❌ Bad - no type hints
public function getFullName()
{
    return "{$this->firstName} {$this->lastName}";
}
```

## See Also

- [SimpleDto Introduction](/data-helpers/simple-dto/introduction/) - Dto basics
- [Custom Casts](/data-helpers/advanced/custom-casts/) - Custom type casts
- [Custom Validation](/data-helpers/advanced/custom-validation/) - Custom validation rules

