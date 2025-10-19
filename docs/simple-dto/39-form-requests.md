# Form Requests

Complete guide to using SimpleDTO for form handling and validation.

---

## 🎯 Overview

SimpleDTO provides powerful form handling:

- ✅ **Automatic Validation** - Validate on creation
- ✅ **Type Safety** - Guaranteed data types
- ✅ **Controller Injection** - Auto-validation in controllers
- ✅ **Custom Rules** - Extend validation
- ✅ **Error Messages** - Customizable messages

---

## 🚀 Basic Form Request

### User Registration Form

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

### Controller

```php
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Validate and create DTO
        $dto = RegisterUserDTO::validateAndCreate($request->all());
        
        // Create user
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
        
        return response()->json([
            'message' => 'User registered successfully',
            'user' => UserResourceDTO::fromModel($user)->toArray(),
        ], 201);
    }
}
```

---

## 🎨 Controller Injection

### Auto-Validation with Attribute

```php
#[ValidateRequest]
class CreateProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3), Max(100)]
        public readonly string $name,
        
        #[Required, Numeric, Min(0)]
        public readonly float $price,
        
        #[Required, StringType]
        public readonly string $description,
        
        #[Required, In(['draft', 'published'])]
        public readonly string $status,
    ) {}
}
```

### Controller with Injection

```php
class ProductController extends Controller
{
    // DTO is automatically validated before method is called
    public function store(CreateProductDTO $dto): JsonResponse
    {
        $product = Product::create($dto->toArray());
        
        return response()->json([
            'message' => 'Product created successfully',
            'product' => ProductResourceDTO::fromModel($product)->toArray(),
        ], 201);
    }
}
```

---

## ✅ Complex Validation

### Nested Form Data

```php
class CreateOrderDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Exists('customers', 'id')]
        public readonly int $customerId,
        
        #[Required, ArrayType, Min(1)]
        /** @var OrderItemDTO[] */
        public readonly array $items,
        
        #[Required]
        public readonly AddressDTO $shippingAddress,
        
        #[Required]
        public readonly AddressDTO $billingAddress,
        
        #[Nullable, StringType, Max(50)]
        public readonly ?string $couponCode = null,
    ) {}
}

class OrderItemDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Exists('products', 'id')]
        public readonly int $productId,
        
        #[Required, Integer, Min(1), Max(100)]
        public readonly int $quantity,
    ) {}
}

class AddressDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $street,
        
        #[Required, StringType, Max(50)]
        public readonly string $city,
        
        #[Required, StringType, Size(2)]
        public readonly string $state,
        
        #[Required, StringType, Regex('/^\d{5}$/')]
        public readonly string $zipCode,
    ) {}
}
```

### Controller

```php
class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dto = CreateOrderDTO::validateAndCreate($request->all());
        
        // Create order
        $order = Order::create([
            'customer_id' => $dto->customerId,
            'coupon_code' => $dto->couponCode,
        ]);
        
        // Create order items
        foreach ($dto->items as $item) {
            $order->items()->create([
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
            ]);
        }
        
        // Save addresses
        $order->shippingAddress()->create($dto->shippingAddress->toArray());
        $order->billingAddress()->create($dto->billingAddress->toArray());
        
        return response()->json([
            'message' => 'Order created successfully',
            'order' => OrderResourceDTO::fromModel($order)->toArray(),
        ], 201);
    }
}
```

---

## 🔄 Update Forms

### Update User Profile

```php
class UpdateProfileDTO extends SimpleDTO
{
    public function __construct(
        #[Nullable, StringType, Min(3), Max(50)]
        public readonly ?string $name = null,
        
        #[Nullable, StringType, Max(500)]
        public readonly ?string $bio = null,
        
        #[Nullable, URL]
        public readonly ?string $website = null,
        
        #[Nullable, StringType, Max(100)]
        public readonly ?string $location = null,
    ) {}
}
```

### Controller

```php
class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $dto = UpdateProfileDTO::validateAndCreate($request->all());
        
        $user = auth()->user();
        
        // Only update provided fields
        $data = array_filter($dto->toArray(), fn($value) => $value !== null);
        
        $user->update($data);
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => UserResourceDTO::fromModel($user->fresh())->toArray(),
        ]);
    }
}
```

---

## 🎯 File Upload Forms

### Upload Avatar

```php
class UploadAvatarDTO extends SimpleDTO
{
    public function __construct(
        #[Required, File, Image, MaxFileSize(2048), Dimensions(['min_width' => 100, 'min_height' => 100])]
        public readonly UploadedFile $avatar,
    ) {}
}
```

### Controller

```php
class AvatarController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $dto = UploadAvatarDTO::validateAndCreate($request->all());
        
        $user = auth()->user();
        
        // Store avatar
        $path = $dto->avatar->store('avatars', 'public');
        
        // Update user
        $user->update(['avatar' => $path]);
        
        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => Storage::url($path),
        ]);
    }
}
```

---

## 🔐 Conditional Validation

### Admin vs User Forms

```php
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Min(3), Max(50)]
        public readonly string $name,
        
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
        
        // Only admins can set role
        #[WhenRole('admin'), In(['user', 'admin', 'moderator'])]
        public readonly ?string $role = null,
        
        // Only admins can set permissions
        #[WhenRole('admin'), ArrayType]
        public readonly ?array $permissions = null,
    ) {}
}
```

---

## 💡 Best Practices

### 1. Separate Request and Response DTOs

```php
// ✅ Good - separate DTOs
class CreateUserDTO extends SimpleDTO { /* ... */ }
class UserResourceDTO extends SimpleDTO { /* ... */ }

// ❌ Bad - same DTO
class UserDTO extends SimpleDTO { /* ... */ }
```

### 2. Use Specific Validation

```php
// ✅ Good - specific validation
#[Required, Email, Unique('users', 'email')]
public readonly string $email

// ❌ Bad - generic validation
#[Required]
public readonly string $email
```

### 3. Cache Validation Rules

```bash
# Always cache in production
php artisan dto:cache
```

### 4. Handle Validation Errors

```php
try {
    $dto = CreateUserDTO::validateAndCreate($request->all());
} catch (ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors(),
    ], 422);
}
```

---

## 📚 Next Steps

1. [Validation](07-validation.md) - Validation details
2. [API Resources](38-api-resources.md) - API responses
3. [Testing DTOs](40-testing-dtos.md) - Testing strategies

---

**Previous:** [API Resources](38-api-resources.md)  
**Next:** [Testing DTOs](40-testing-dtos.md)

