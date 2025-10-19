# Nested DTOs

Learn how to work with nested and complex DTO structures.

---

## 🎯 What are Nested DTOs?

Nested DTOs are DTOs that contain other DTOs as properties, allowing you to model complex data structures:

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
        public readonly AddressDTO $address,  // Nested DTO
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);

echo $dto->address->city;  // New York
```

---

## 🚀 Basic Nested DTOs

### Single Nested DTO

```php
class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $bio,
        public readonly string $website,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ProfileDTO $profile,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'profile' => [
        'bio' => 'Software Developer',
        'website' => 'https://johndoe.com',
    ],
]);
```

### Multiple Nested DTOs

```php
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class ContactDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $phone,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly AddressDTO $address,
        public readonly ContactDTO $contact,
    ) {}
}
```

### Optional Nested DTOs

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?AddressDTO $address = null,
        public readonly ?ProfileDTO $profile = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    // address and profile are optional
]);
```

---

## 📦 Array of Nested DTOs

### Using Array Type

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
        /** @var OrderItemDTO[] */
        public readonly array $items,
    ) {}
}

$dto = OrderDTO::fromArray([
    'orderId' => 123,
    'items' => [
        ['product' => 'Widget', 'quantity' => 2, 'price' => 9.99],
        ['product' => 'Gadget', 'quantity' => 1, 'price' => 19.99],
    ],
]);
```

### Using Cast Attribute

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Casts\CollectionCast;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        
        #[Cast(CollectionCast::class, itemType: OrderItemDTO::class)]
        public readonly array $items,
    ) {}
}
```

---

## 🎯 Deep Nesting

### Three Levels Deep

```php
class CityDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $country,
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

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => [
            'name' => 'New York',
            'country' => 'USA',
        ],
    ],
]);

echo $dto->address->city->name;  // New York
```

---

## 🔄 Serialization

### Automatic Nested Serialization

```php
$array = $dto->toArray();
// [
//     'name' => 'John Doe',
//     'address' => [
//         'street' => '123 Main St',
//         'city' => [
//             'name' => 'New York',
//             'country' => 'USA',
//         ],
//     ],
// ]
```

### JSON Serialization

```php
$json = $dto->toJson();
// {
//     "name": "John Doe",
//     "address": {
//         "street": "123 Main St",
//         "city": {
//             "name": "New York",
//             "country": "USA"
//         }
//     }
// }
```

---

## 🎨 Real-World Examples

### Example 1: E-commerce Order

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
        public readonly float $subtotal,
    ) {}
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

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly CustomerDTO $customer,
        public readonly ShippingAddressDTO $shippingAddress,
        /** @var OrderItemDTO[] */
        public readonly array $items,
        public readonly float $total,
    ) {}
}
```

### Example 2: Blog Post with Author and Comments

```php
class AuthorDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $avatar,
    ) {}
}

class CommentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly Carbon $createdAt,
    ) {}
}

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        public readonly AuthorDTO $author,
        /** @var CommentDTO[] */
        public readonly array $comments,
        public readonly Carbon $publishedAt,
    ) {}
}
```

### Example 3: Company Organization

```php
class EmployeeDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $position,
    ) {}
}

class DepartmentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly EmployeeDTO $manager,
        /** @var EmployeeDTO[] */
        public readonly array $employees,
    ) {}
}

class CompanyDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        /** @var DepartmentDTO[] */
        public readonly array $departments,
    ) {}
}
```

---

## 🔄 Mapping Nested Properties

### MapFrom with Nested Paths

```php
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[MapFrom('profile.bio')]
        public readonly string $bio,
        
        #[MapFrom('address.city.name')]
        public readonly string $cityName,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'profile' => [
        'bio' => 'Software Developer',
    ],
    'address' => [
        'city' => [
            'name' => 'New York',
        ],
    ],
]);
```

---

## 🎯 Conditional Nested Properties

### Conditional Nested DTOs

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?ProfileDTO $profile = null,
        
        #[WhenCan('view-admin')]
        public readonly ?AdminDataDTO $adminData = null,
    ) {}
}
```

---

## 🔄 Lazy Nested DTOs

### Lazy Loading Relationships

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,
        
        #[Lazy]
        public readonly ?array $comments = null,
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            posts: fn() => PostDTO::collection($user->posts),
            comments: fn() => CommentDTO::collection($user->comments),
        );
    }
}
```

---

## 🎨 Circular References

### Handling Circular References

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        /** @var UserDTO[] */
        public readonly ?array $friends = null,
    ) {}
    
    public static function fromModel(User $user, int $depth = 0): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            friends: $depth < 2 
                ? $user->friends->map(fn($friend) => 
                    self::fromModel($friend, $depth + 1)
                  )->toArray()
                : null,
        );
    }
}
```

---

## 🔍 Accessing Nested Properties

### Direct Access

```php
echo $dto->address->city->name;
```

### Safe Access with Null Coalescing

```php
echo $dto->address?->city?->name ?? 'Unknown';
```

### Using Helper Methods

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?AddressDTO $address = null,
    ) {}
    
    public function getCityName(): ?string
    {
        return $this->address?->city?->name;
    }
}
```

---

## 💡 Best Practices

### 1. Keep Nesting Shallow

```php
// ✅ Good - 2-3 levels
$dto->address->city

// ❌ Bad - too deep
$dto->company->department->team->manager->address->city
```

### 2. Use Optional for Nullable Nested DTOs

```php
// ✅ Good - nullable nested DTO
public readonly ?AddressDTO $address = null

// ❌ Bad - required nested DTO that might not exist
public readonly AddressDTO $address
```

### 3. Document Nested Arrays

```php
// ✅ Good - documented array type
/** @var OrderItemDTO[] */
public readonly array $items

// ❌ Bad - undocumented array
public readonly array $items
```

### 4. Use Factory Methods for Complex Nesting

```php
// ✅ Good - factory method
public static function fromModel(Order $order): self
{
    return new self(
        orderId: $order->id,
        customer: CustomerDTO::fromModel($order->customer),
        items: OrderItemDTO::collection($order->items),
    );
}
```

---

## 🎯 Performance Considerations

### Lazy Load Nested Collections

```php
// ✅ Good - lazy loading
#[Lazy]
public readonly ?array $posts = null

// ❌ Bad - eager loading large collections
public readonly array $posts
```

### Use Pagination for Large Nested Collections

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $recentPosts,  // Only recent
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            recentPosts: PostDTO::collection(
                $user->posts()->latest()->take(10)->get()
            ),
        );
    }
}
```

---

## 📚 Next Steps

1. [Collections](15-collections.md) - Working with collections
2. [Lazy Properties](13-lazy-properties.md) - Lazy loading
3. [Type Casting](06-type-casting.md) - Type conversion
4. [Property Mapping](08-property-mapping.md) - Map nested properties

---

**Previous:** [Collections](15-collections.md)  
**Next:** [Laravel Integration](17-laravel-integration.md)

