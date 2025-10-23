---
title: Extending DTOs
description: Extend SimpleDTO with custom functionality
---

Extend SimpleDTO with custom functionality.

## Introduction

Extend DTOs to add custom behavior:

- ✅ **Custom Methods** - Add business logic
- ✅ **Traits** - Reuse functionality
- ✅ **Inheritance** - Share common properties
- ✅ **Interfaces** - Define contracts

## Custom Methods

### Adding Business Logic

```php
class UserDTO extends SimpleDTO
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
class ProductDTO extends SimpleDTO
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
class PostDTO extends SimpleDTO
{
    use Timestampable;
    
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}
}

$post = PostDTO::fromArray($data);
if ($post->isNew()) {
    echo 'New post!';
}
```

## Inheritance

### Base DTO

```php
abstract class BaseDTO extends SimpleDTO
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

### Extending Base DTO

```php
class UserDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}
}

class PostDTO extends BaseDTO
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
class ProductDTO extends SimpleDTO implements Searchable
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

### User DTO with Permissions

```php
class UserDTO extends SimpleDTO
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

### Order DTO with Calculations

```php
class OrderDTO extends SimpleDTO
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

### Product DTO with Formatting

```php
class ProductDTO extends SimpleDTO
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
class UserDTO extends SimpleDTO
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

$admin = UserDTO::createAdmin('John Doe', 'john@example.com');
```

### Builder Pattern

```php
class UserDTOBuilder
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
    
    public function build(): UserDTO
    {
        return UserDTO::fromArray($this->data);
    }
}

$user = (new UserDTOBuilder())
    ->setName('John Doe')
    ->setEmail('john@example.com')
    ->setRole('admin')
    ->build();
```

### Immutable Updates

```php
class UserDTO extends SimpleDTO
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

$user = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);
$updated = $user->withName('Jane');
```

## Best Practices

### Single Responsibility

```php
// ✅ Good - single responsibility
class UserDTO extends SimpleDTO
{
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}

// ❌ Bad - multiple responsibilities
class UserDTO extends SimpleDTO
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

- [SimpleDTO Introduction](/simple-dto/introduction/) - DTO basics
- [Custom Casts](/advanced/custom-casts/) - Custom type casts
- [Custom Validation](/advanced/custom-validation/) - Custom validation rules

