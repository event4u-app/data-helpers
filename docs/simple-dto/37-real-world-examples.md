# Real-World Examples

Practical examples of using SimpleDTO in real-world applications.

---

## 🎯 Overview

This guide shows real-world use cases:

- ✅ **E-Commerce** - Product catalog, orders, payments
- ✅ **Blog Platform** - Posts, comments, authors
- ✅ **User Management** - Registration, profiles, permissions
- ✅ **API Integration** - External APIs, webhooks
- ✅ **Multi-Tenant SaaS** - Organizations, subscriptions

---

## 🛒 E-Commerce Platform

### Product Catalog

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly float $price,
        public readonly ?float $salePrice,
        public readonly string $description,
        public readonly CategoryDTO $category,
        /** @var string[] */
        public readonly array $images,
        /** @var string[] */
        public readonly array $tags,
        public readonly int $stock,
        public readonly bool $inStock,
        
        #[WhenAuth]
        public readonly ?float $cost = null,
        
        #[WhenRole('admin')]
        public readonly ?array $analytics = null,
    ) {}
    
    #[Computed]
    public function discount(): ?float
    {
        if (!$this->salePrice) {
            return null;
        }
        
        return round((($this->price - $this->salePrice) / $this->price) * 100, 2);
    }
}
```

### Shopping Cart

```php
class CartDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,
        /** @var CartItemDTO[] */
        public readonly array $items,
        public readonly ?string $couponCode,
    ) {}
    
    #[Computed]
    public function subtotal(): float
    {
        return array_sum(array_map(
            fn($item) => $item->total(),
            $this->items
        ));
    }
    
    #[Computed]
    public function discount(): float
    {
        if (!$this->couponCode) {
            return 0;
        }
        
        // Calculate discount based on coupon
        return $this->subtotal() * 0.1; // 10% example
    }
    
    #[Computed]
    public function total(): float
    {
        return $this->subtotal() - $this->discount();
    }
}

class CartItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly ProductDTO $product,
        public readonly int $quantity,
    ) {}
    
    #[Computed]
    public function total(): float
    {
        $price = $this->product->salePrice ?? $this->product->price;
        return $price * $this->quantity;
    }
}
```

### Order Processing

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly CustomerDTO $customer,
        /** @var OrderItemDTO[] */
        public readonly array $items,
        public readonly AddressDTO $shippingAddress,
        public readonly AddressDTO $billingAddress,
        public readonly string $status,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $shipping,
        public readonly float $total,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[WhenAuth]
        public readonly ?PaymentDTO $payment = null,
        
        #[WhenRole('admin')]
        public readonly ?array $internalNotes = null,
    ) {}
}
```

---

## 📝 Blog Platform

### Blog Post

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $excerpt,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly CategoryDTO $category,
        /** @var string[] */
        public readonly array $tags,
        public readonly string $status,
        public readonly int $views,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $publishedAt,
        
        #[Lazy]
        public readonly ?array $comments = null,
        
        #[WhenAuth]
        public readonly ?string $editUrl = null,
        
        #[WhenCan('edit')]
        public readonly ?string $deleteUrl = null,
    ) {}
    
    #[Computed]
    public function readingTime(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return (int) ceil($words / 200); // 200 words per minute
    }
}
```

### Comment System

```php
class CommentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        public readonly UserDTO $author,
        public readonly ?int $parentId,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[Lazy]
        public readonly ?array $replies = null,
        
        #[WhenAuth]
        public readonly ?bool $canEdit = null,
        
        #[WhenAuth]
        public readonly ?bool $canDelete = null,
    ) {}
}
```

---

## 👥 User Management

### User Registration

```php
class RegisterUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3), Max(50)]
        public readonly string $name,
        
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Min(8), Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
        public readonly string $password,
        
        #[Required, Same('password')]
        public readonly string $passwordConfirmation,
        
        #[Required, Accepted]
        public readonly bool $termsAccepted,
    ) {}
}
```

### User Profile

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly ?string $avatar,
        public readonly ?string $bio,
        public readonly ?string $location,
        public readonly ?string $website,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $joinedAt,
        
        #[WhenAuth]
        public readonly ?string $email = null,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
        
        #[WhenRole('admin')]
        public readonly ?array $permissions = null,
    ) {}
}
```

---

## 🔌 API Integration

### External API Response

```php
class WeatherDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('location.city')]
        public readonly string $city,
        
        #[MapFrom('location.country')]
        public readonly string $country,
        
        #[MapFrom('current.temp_c')]
        public readonly float $temperature,
        
        #[MapFrom('current.condition.text')]
        public readonly string $condition,
        
        #[MapFrom('current.humidity')]
        public readonly int $humidity,
        
        #[MapFrom('current.wind_kph')]
        public readonly float $windSpeed,
    ) {}
}
```

### Webhook Payload

```php
class WebhookPayloadDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $event,
        public readonly array $data,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $timestamp,
        
        public readonly string $signature,
    ) {}
    
    public function verify(string $secret): bool
    {
        $payload = json_encode([
            'event' => $this->event,
            'data' => $this->data,
            'timestamp' => $this->timestamp->toIso8601String(),
        ]);
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $this->signature);
    }
}
```

---

## 🏢 Multi-Tenant SaaS

### Organization

```php
class OrganizationDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $plan,
        public readonly int $memberCount,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[WhenRole('owner')]
        public readonly ?SubscriptionDTO $subscription = null,
        
        #[WhenRole('owner')]
        public readonly ?BillingDTO $billing = null,
        
        #[Lazy]
        public readonly ?array $members = null,
    ) {}
}
```

### Subscription

```php
class SubscriptionDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $plan,
        public readonly string $status,
        public readonly float $price,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $currentPeriodStart,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $currentPeriodEnd,
        
        #[WhenRole('owner')]
        public readonly ?string $cancelUrl = null,
        
        #[WhenRole('owner')]
        public readonly ?string $upgradeUrl = null,
    ) {}
}
```

---

## 📚 Next Steps

1. [API Resources](38-api-resources.md) - REST API examples
2. [Form Requests](39-form-requests.md) - Form handling
3. [Testing DTOs](40-testing-dtos.md) - Testing strategies

---

**Previous:** [Interfaces Reference](36-interfaces-reference.md)  
**Next:** [API Resources](38-api-resources.md)

