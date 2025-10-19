# Lazy Properties

Learn how to defer expensive operations until they're actually needed using lazy properties.

---

## 🎯 What are Lazy Properties?

Lazy properties are properties that are only evaluated when accessed, not when the DTO is created:

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,  // Only loaded when accessed
    ) {}
}

$dto = UserDTO::fromModel($user);
// Posts are NOT loaded yet

$posts = $dto->posts;
// Posts are loaded NOW
```

---

## 🚀 Basic Usage

### Using #[Lazy] Attribute

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        
        #[Lazy]
        public readonly ?array $posts = null,
        
        #[Lazy]
        public readonly ?array $comments = null,
    ) {}
}

$dto = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'posts' => fn() => Post::where('user_id', 1)->get(),
    'comments' => fn() => Comment::where('user_id', 1)->get(),
]);

// Posts and comments are NOT loaded yet
echo $dto->name;  // Fast

// Posts are loaded NOW
$posts = $dto->posts;  // Slower (database query)
```

---

## 🎨 Lazy Loading Patterns

### Database Relationships

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,
        
        #[Lazy]
        public readonly ?array $followers = null,
        
        #[Lazy]
        public readonly ?array $following = null,
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            posts: fn() => PostDTO::collection($user->posts),
            followers: fn() => UserDTO::collection($user->followers),
            following: fn() => UserDTO::collection($user->following),
        );
    }
}

$dto = UserDTO::fromModel($user);
// No relationships loaded yet

$posts = $dto->posts;  // Loads posts NOW
```

### Expensive Calculations

```php
class StatisticsDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $monthlyStats = null,
        
        #[Lazy]
        public readonly ?array $yearlyStats = null,
        
        #[Lazy]
        public readonly ?float $averageScore = null,
    ) {}
    
    public static function fromUser(User $user): self
    {
        return new self(
            name: $user->name,
            monthlyStats: fn() => $user->calculateMonthlyStats(),
            yearlyStats: fn() => $user->calculateYearlyStats(),
            averageScore: fn() => $user->calculateAverageScore(),
        );
    }
}
```

### External API Calls

```php
class GitHubUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        
        #[Lazy]
        public readonly ?array $repositories = null,
        
        #[Lazy]
        public readonly ?array $followers = null,
        
        #[Lazy]
        public readonly ?array $gists = null,
    ) {}
    
    public static function fromUsername(string $username): self
    {
        return new self(
            username: $username,
            repositories: fn() => Http::get("https://api.github.com/users/{$username}/repos")->json(),
            followers: fn() => Http::get("https://api.github.com/users/{$username}/followers")->json(),
            gists: fn() => Http::get("https://api.github.com/users/{$username}/gists")->json(),
        );
    }
}
```

---

## 🔄 Lazy Serialization

### Exclude from toArray() by Default

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
}

$dto = UserDTO::fromModel($user);

// Lazy properties are NOT included by default
$array = $dto->toArray();
// ['name' => 'John Doe']

// Include lazy properties explicitly
$array = $dto->toArray(includeLazy: true);
// ['name' => 'John Doe', 'posts' => [...]]
```

### Conditional Lazy Loading

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        #[WhenContext('include_posts')]
        public readonly ?array $posts = null,
    ) {}
}

// Posts are only loaded if context includes 'include_posts'
$array = $dto
    ->withContext(['include_posts' => true])
    ->toArray(includeLazy: true);
```

---

## 🎯 Real-World Examples

### Example 1: User Profile with Stats

```php
class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        
        #[Lazy]
        public readonly ?int $postsCount = null,
        
        #[Lazy]
        public readonly ?int $followersCount = null,
        
        #[Lazy]
        public readonly ?array $recentPosts = null,
        
        #[Lazy]
        public readonly ?array $topPosts = null,
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            postsCount: fn() => $user->posts()->count(),
            followersCount: fn() => $user->followers()->count(),
            recentPosts: fn() => PostDTO::collection($user->posts()->latest()->take(5)->get()),
            topPosts: fn() => PostDTO::collection($user->posts()->orderBy('views', 'desc')->take(5)->get()),
        );
    }
}
```

### Example 2: Product with Reviews

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        
        #[Lazy]
        public readonly ?array $reviews = null,
        
        #[Lazy]
        public readonly ?float $averageRating = null,
        
        #[Lazy]
        public readonly ?array $relatedProducts = null,
    ) {}
    
    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            price: $product->price,
            reviews: fn() => ReviewDTO::collection($product->reviews),
            averageRating: fn() => $product->reviews()->avg('rating'),
            relatedProducts: fn() => self::collection($product->relatedProducts()),
        );
    }
}
```

### Example 3: Dashboard with Multiple Data Sources

```php
class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[Lazy]
        public readonly ?array $userStats = null,
        
        #[Lazy]
        public readonly ?array $salesStats = null,
        
        #[Lazy]
        public readonly ?array $recentOrders = null,
        
        #[Lazy]
        public readonly ?array $topProducts = null,
    ) {}
    
    public static function create(): self
    {
        return new self(
            title: 'Dashboard',
            userStats: fn() => [
                'total' => User::count(),
                'active' => User::where('active', true)->count(),
                'new_today' => User::whereDate('created_at', today())->count(),
            ],
            salesStats: fn() => [
                'total' => Order::sum('total'),
                'today' => Order::whereDate('created_at', today())->sum('total'),
                'this_month' => Order::whereMonth('created_at', now()->month)->sum('total'),
            ],
            recentOrders: fn() => OrderDTO::collection(Order::latest()->take(10)->get()),
            topProducts: fn() => ProductDTO::collection(Product::orderBy('sales', 'desc')->take(10)->get()),
        );
    }
}
```

---

## 🔍 Checking Lazy State

### Check if Property is Loaded

```php
$dto = UserDTO::fromModel($user);

// Check if lazy property is loaded
$isLoaded = $dto->isLazyPropertyLoaded('posts');

// Get all loaded lazy properties
$loaded = $dto->getLoadedLazyProperties();

// Get all unloaded lazy properties
$unloaded = $dto->getUnloadedLazyProperties();
```

---

## ⚡ Performance Benefits

### Without Lazy Loading

```php
// ❌ Bad - loads everything immediately
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $posts,  // Always loaded
        public readonly array $comments,  // Always loaded
        public readonly array $followers,  // Always loaded
    ) {}
}

$dto = UserDTO::fromModel($user);
// 3 database queries executed immediately
// Even if we only need the name!
```

### With Lazy Loading

```php
// ✅ Good - loads only when needed
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,
        
        #[Lazy]
        public readonly ?array $comments = null,
        
        #[Lazy]
        public readonly ?array $followers = null,
    ) {}
}

$dto = UserDTO::fromModel($user);
// 0 database queries executed
// Only loads what you access!
```

---

## 🎨 Combining with Other Features

### Lazy + Conditional

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        #[WhenAuth]
        public readonly ?array $privateData = null,
        
        #[Lazy]
        #[WhenCan('view-admin')]
        public readonly ?array $adminData = null,
    ) {}
}
```

### Lazy + Caching

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[Lazy]
        public readonly ?array $posts = null,
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            posts: fn() => Cache::remember(
                "user.{$user->id}.posts",
                3600,
                fn() => PostDTO::collection($user->posts)
            ),
        );
    }
}
```

---

## 💡 Best Practices

### 1. Use Lazy for Expensive Operations

```php
// ✅ Good - lazy for expensive operations
#[Lazy]
public readonly ?array $statistics = null;

// ❌ Bad - eager loading expensive data
public readonly array $statistics;
```

### 2. Use Closures for Lazy Values

```php
// ✅ Good - closure for lazy evaluation
posts: fn() => $user->posts()->get()

// ❌ Bad - eager evaluation
posts: $user->posts()->get()
```

### 3. Document Lazy Properties

```php
/**
 * @property-read array|null $posts Lazy-loaded user posts
 * @property-read array|null $followers Lazy-loaded followers
 */
class UserDTO extends SimpleDTO
{
    // ...
}
```

### 4. Consider Caching

```php
// ✅ Good - cache expensive lazy operations
posts: fn() => Cache::remember('posts', 3600, fn() => $this->loadPosts())

// ❌ Bad - no caching
posts: fn() => $this->loadPosts()
```

---

## 📚 Next Steps

1. [Computed Properties](14-computed-properties.md) - Calculated properties
2. [Collections](15-collections.md) - Working with collections
3. [Performance](27-performance.md) - Optimization tips
4. [Caching](28-caching.md) - Caching strategies

---

**Previous:** [Context-Based Conditions](12-context-based-conditions.md)  
**Next:** [Computed Properties](14-computed-properties.md)

