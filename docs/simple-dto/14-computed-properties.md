# Computed Properties

Learn how to create calculated properties that are derived from other properties.

---

## 🎯 What are Computed Properties?

Computed properties are properties that are calculated from other properties rather than being set directly:

```php
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

$dto = new UserDTO(firstName: 'John', lastName: 'Doe');
echo $dto->fullName();  // John Doe

$array = $dto->toArray();
// ['firstName' => 'John', 'lastName' => 'Doe', 'fullName' => 'John Doe']
```

---

## 🚀 Basic Usage

### Using #[Computed] Attribute

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;

class ProductDTO extends SimpleDTO
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

$dto = new ProductDTO(price: 100.0, taxRate: 0.2);

echo $dto->priceWithTax();  // 120.0
echo $dto->taxAmount();     // 20.0
```

---

## 🎨 Common Patterns

### String Manipulation

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
    ) {}
    
    #[Computed]
    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    #[Computed]
    public function initials(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }
    
    #[Computed]
    public function emailDomain(): string
    {
        return substr($this->email, strpos($this->email, '@') + 1);
    }
}
```

### Date Calculations

```php
class EventDTO extends SimpleDTO
{
    public function __construct(
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
    ) {}
    
    #[Computed]
    public function duration(): int
    {
        return $this->startDate->diffInDays($this->endDate);
    }
    
    #[Computed]
    public function isUpcoming(): bool
    {
        return $this->startDate->isFuture();
    }
    
    #[Computed]
    public function isPast(): bool
    {
        return $this->endDate->isPast();
    }
    
    #[Computed]
    public function isOngoing(): bool
    {
        return $this->startDate->isPast() && $this->endDate->isFuture();
    }
}
```

### Numeric Calculations

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $shipping,
        public readonly float $discount,
    ) {}
    
    #[Computed]
    public function total(): float
    {
        return $this->subtotal + $this->tax + $this->shipping - $this->discount;
    }
    
    #[Computed]
    public function totalWithoutDiscount(): float
    {
        return $this->subtotal + $this->tax + $this->shipping;
    }
    
    #[Computed]
    public function discountPercentage(): float
    {
        return ($this->discount / $this->totalWithoutDiscount()) * 100;
    }
}
```

### Boolean Logic

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly bool $emailVerified,
        public readonly bool $phoneVerified,
        public readonly bool $twoFactorEnabled,
    ) {}
    
    #[Computed]
    public function isFullyVerified(): bool
    {
        return $this->emailVerified && $this->phoneVerified;
    }
    
    #[Computed]
    public function isSecure(): bool
    {
        return $this->isFullyVerified() && $this->twoFactorEnabled;
    }
}
```

---

## 🎯 Real-World Examples

### Example 1: User Profile

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly Carbon $birthDate,
        public readonly int $postsCount,
        public readonly int $followersCount,
        public readonly int $followingCount,
    ) {}
    
    #[Computed]
    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    #[Computed]
    public function age(): int
    {
        return $this->birthDate->age;
    }
    
    #[Computed]
    public function isAdult(): bool
    {
        return $this->age() >= 18;
    }
    
    #[Computed]
    public function followersRatio(): float
    {
        if ($this->followingCount === 0) {
            return 0;
        }
        return $this->followersCount / $this->followingCount;
    }
    
    #[Computed]
    public function engagementScore(): float
    {
        return ($this->postsCount * 2) + $this->followersCount + ($this->followingCount * 0.5);
    }
}
```

### Example 2: E-commerce Product

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly float $originalPrice,
        public readonly int $stock,
        public readonly float $rating,
        public readonly int $reviewsCount,
    ) {}
    
    #[Computed]
    public function isOnSale(): bool
    {
        return $this->price < $this->originalPrice;
    }
    
    #[Computed]
    public function discountAmount(): float
    {
        return $this->originalPrice - $this->price;
    }
    
    #[Computed]
    public function discountPercentage(): float
    {
        if ($this->originalPrice === 0) {
            return 0;
        }
        return (($this->originalPrice - $this->price) / $this->originalPrice) * 100;
    }
    
    #[Computed]
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
    
    #[Computed]
    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 5;
    }
    
    #[Computed]
    public function averageRating(): string
    {
        return number_format($this->rating, 1);
    }
    
    #[Computed]
    public function hasReviews(): bool
    {
        return $this->reviewsCount > 0;
    }
}
```

### Example 3: Financial Report

```php
class FinancialReportDTO extends SimpleDTO
{
    public function __construct(
        public readonly float $revenue,
        public readonly float $costs,
        public readonly float $expenses,
        public readonly float $taxes,
    ) {}
    
    #[Computed]
    public function grossProfit(): float
    {
        return $this->revenue - $this->costs;
    }
    
    #[Computed]
    public function netProfit(): float
    {
        return $this->grossProfit() - $this->expenses - $this->taxes;
    }
    
    #[Computed]
    public function grossMargin(): float
    {
        if ($this->revenue === 0) {
            return 0;
        }
        return ($this->grossProfit() / $this->revenue) * 100;
    }
    
    #[Computed]
    public function netMargin(): float
    {
        if ($this->revenue === 0) {
            return 0;
        }
        return ($this->netProfit() / $this->revenue) * 100;
    }
    
    #[Computed]
    public function isProfitable(): bool
    {
        return $this->netProfit() > 0;
    }
}
```

---

## 🔄 Computed Properties in Serialization

### Automatic Inclusion

```php
$dto = new UserDTO(firstName: 'John', lastName: 'Doe');

$array = $dto->toArray();
// [
//     'firstName' => 'John',
//     'lastName' => 'Doe',
//     'fullName' => 'John Doe',  // Computed property included
// ]
```

### Exclude from Serialization

```php
class UserDTO extends SimpleDTO
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

---

## 🎨 Combining with Other Features

### Computed + Conditional

```php
class UserDTO extends SimpleDTO
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
class UserDTO extends SimpleDTO
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

---

## ⚡ Performance Considerations

### Caching Computed Values

```php
class UserDTO extends SimpleDTO
{
    private ?string $cachedFullName = null;
    
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
    
    #[Computed]
    public function fullName(): string
    {
        if ($this->cachedFullName === null) {
            $this->cachedFullName = $this->firstName . ' ' . $this->lastName;
        }
        return $this->cachedFullName;
    }
}
```

### Avoid Expensive Operations

```php
// ❌ Bad - expensive operation in computed property
#[Computed]
public function allPosts(): array
{
    return Post::where('user_id', $this->id)->get()->toArray();
}

// ✅ Good - use lazy loading instead
#[Lazy]
public readonly ?array $posts = null;
```

---

## 💡 Best Practices

### 1. Keep Computed Properties Simple

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
    // 50 lines of complex logic
}
```

### 2. Use Type Hints

```php
// ✅ Good - type hinted
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

### 3. Document Computed Properties

```php
/**
 * Calculate the total price including tax
 * 
 * @return float Total price with tax
 */
#[Computed]
public function totalWithTax(): float
{
    return $this->price * (1 + $this->taxRate);
}
```

### 4. Avoid Side Effects

```php
// ✅ Good - pure function
#[Computed]
public function total(): float
{
    return $this->price * $this->quantity;
}

// ❌ Bad - has side effects
#[Computed]
public function total(): float
{
    Log::info('Calculating total');  // Side effect!
    return $this->price * $this->quantity;
}
```

---

## 📚 Next Steps

1. [Collections](15-collections.md) - Working with collections
2. [Lazy Properties](13-lazy-properties.md) - Lazy loading
3. [Conditional Properties](10-conditional-properties.md) - Dynamic properties
4. [Performance](27-performance.md) - Optimization tips

---

**Previous:** [Lazy Properties](13-lazy-properties.md)  
**Next:** [Collections](15-collections.md)

