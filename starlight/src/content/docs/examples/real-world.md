---
title: Real-World Application Examples
description: Complete application examples
---

Complete application examples.

## Introduction

Real-world application examples:

- ✅ **E-Commerce** - Product catalog, orders, payments
- ✅ **Blog Platform** - Posts, comments, authors
- ✅ **SaaS Application** - Organizations, subscriptions
- ✅ **API Backend** - REST API with authentication

## E-Commerce Platform

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
        public readonly array $images,
        public readonly int $stock,
        
        #[WhenAuth]
        public readonly ?bool $inWishlist = null,
        
        #[WhenRole('admin')]
        public readonly ?float $cost = null,
    ) {}
    
    public function getDisplayPrice(): float
    {
        return $this->salePrice ?? $this->price;
    }
    
    public function isOnSale(): bool
    {
        return $this->salePrice !== null;
    }
    
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
}
```

### Shopping Cart

```php
class CartDTO extends SimpleDTO
{
    public function __construct(
        public readonly array $items,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $shipping,
        public readonly float $total,
    ) {}
    
    public function getItemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }
}

class CartItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly ProductDTO $product,
        public readonly int $quantity,
        public readonly float $price,
        public readonly float $total,
    ) {}
}
```

### Order Processing

```php
class CreateOrderDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly array $items,
        
        #[Required]
        public readonly AddressDTO $shippingAddress,
        
        #[Required]
        public readonly AddressDTO $billingAddress,
        
        #[Required, In(['credit_card', 'paypal', 'bank_transfer'])]
        public readonly string $paymentMethod,
    ) {}
}

$dto = CreateOrderDTO::validateAndCreate($_POST);

DB::transaction(function() use ($dto) {
    $order = Order::create([
        'user_id' => auth()->id(),
        'payment_method' => $dto->paymentMethod,
        'shipping_address' => $dto->shippingAddress->toArray(),
        'billing_address' => $dto->billingAddress->toArray(),
    ]);
    
    foreach ($dto->items as $item) {
        $order->items()->create($item);
    }
    
    // Process payment
    Payment::process($order, $dto->paymentMethod);
});
```

## Blog Platform

### Blog Post

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly CategoryDTO $category,
        public readonly array $tags,
        public readonly int $views,
        public readonly Carbon $publishedAt,
        
        #[Lazy]
        public readonly ?array $comments = null,
        
        #[WhenAuth]
        public readonly ?string $editUrl = null,
    ) {}
    
    public function getReadingTime(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return (int)ceil($words / 200);
    }
    
    public function isRecent(): bool
    {
        return $this->publishedAt->isAfter(Carbon::now()->subDays(7));
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
        public readonly Carbon $createdAt,
        public readonly ?int $parentId = null,
        public readonly array $replies = [],
    ) {}
}

class CreateCommentDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Min(10)]
        public readonly string $content,
        
        #[Required, Exists('posts', 'id')]
        public readonly int $postId,
        
        #[Exists('comments', 'id')]
        public readonly ?int $parentId = null,
    ) {}
}
```

## SaaS Application

### Organization

```php
class OrganizationDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly SubscriptionDTO $subscription,
        public readonly array $members,
        
        #[WhenRole('owner')]
        public readonly ?BillingDTO $billing = null,
    ) {}
}
```

### Subscription

```php
class SubscriptionDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $plan,
        public readonly string $status,
        public readonly Carbon $currentPeriodStart,
        public readonly Carbon $currentPeriodEnd,
        public readonly bool $cancelAtPeriodEnd,
    ) {}
    
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    public function daysRemaining(): int
    {
        return $this->currentPeriodEnd->diffInDays(now());
    }
}
```

## API Backend

### Authentication

```php
class LoginDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required]
        public readonly string $password,
    ) {}
}

class AuthTokenDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly UserDTO $user,
    ) {}
}

// Login endpoint
Route::post('/api/login', function(Request $request) {
    $dto = LoginDTO::validateAndCreate($request->all());
    
    if (!auth()->attempt(['email' => $dto->email, 'password' => $dto->password])) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
    
    $user = auth()->user();
    $token = $user->createToken('api')->plainTextToken;
    
    return AuthTokenDTO::fromArray([
        'accessToken' => $token,
        'tokenType' => 'Bearer',
        'expiresIn' => 3600,
        'user' => UserDTO::fromModel($user)->toArray(),
    ])->toJson();
});
```

### REST API

```php
// GET /api/users
Route::get('/api/users', function(Request $request) {
    $users = User::paginate(20);
    
    return response()->json([
        'data' => $users->map(fn($u) => UserDTO::fromModel($u)),
        'meta' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ],
    ]);
});

// POST /api/users
Route::post('/api/users', function(Request $request) {
    $dto = CreateUserDTO::validateAndCreate($request->all());
    
    $user = User::create($dto->toArray());
    
    return response()->json(UserDTO::fromModel($user), 201);
});

// PUT /api/users/{id}
Route::put('/api/users/{id}', function(Request $request, int $id) {
    $dto = UpdateUserDTO::validateAndCreate($request->all());
    
    $user = User::findOrFail($id);
    $user->update(array_filter($dto->toArray()));
    
    return response()->json(UserDTO::fromModel($user));
});

// DELETE /api/users/{id}
Route::delete('/api/users/{id}', function(int $id) {
    User::findOrFail($id)->delete();
    
    return response()->json(null, 204);
});
```

## See Also

- [API Integration](/examples/api-integration/) - API examples
- [Form Processing](/examples/form-processing/) - Form examples
- [Database Operations](/examples/database-operations/) - Database examples

