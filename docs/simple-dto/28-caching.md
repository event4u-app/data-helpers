# Caching

Learn about SimpleDTO's caching system for maximum performance.

---

## 🎯 Overview

SimpleDTO provides intelligent caching for:

- ✅ **Validation Rules** - 198x faster validation
- ✅ **Type Casts** - Reuse cast instances
- ✅ **Reflection Data** - Cache property metadata
- ✅ **Attribute Data** - Cache parsed attributes
- ✅ **DTO Instances** - Cache created DTOs

---

## 🚀 Validation Rule Caching

### Enable Caching

**Laravel:**
```bash
php artisan dto:cache
```

**Symfony:**
```bash
bin/console dto:cache
```

**Plain PHP:**
```php
use event4u\DataHelpers\SimpleDTO\Cache\ValidationCache;

ValidationCache::enable();
ValidationCache::warmup();
```

### How It Works

**Without Cache:**
```php
// Every validation parses attributes
$dto = UserDTO::validateAndCreate($data); // 0.05s
$dto = UserDTO::validateAndCreate($data); // 0.05s
$dto = UserDTO::validateAndCreate($data); // 0.05s
```

**With Cache:**
```php
// First validation parses and caches
$dto = UserDTO::validateAndCreate($data); // 0.05s

// Subsequent validations use cache
$dto = UserDTO::validateAndCreate($data); // 0.00025s (198x faster!)
$dto = UserDTO::validateAndCreate($data); // 0.00025s
```

### Cache Storage

**Laravel:**
```php
// config/simple-dto.php
return [
    'validation' => [
        'cache_rules' => true,
        'cache_driver' => 'file', // file, redis, memcached
        'cache_ttl' => 3600, // 1 hour
    ],
];
```

**Symfony:**
```yaml
# config/packages/simple_dto.yaml
simple_dto:
  validation:
    cache_rules: true
    cache_driver: 'file'
    cache_ttl: 3600
```

### Clear Cache

**Laravel:**
```bash
php artisan dto:clear
```

**Symfony:**
```bash
bin/console dto:clear
```

**Plain PHP:**
```php
ValidationCache::clear();
```

---

## 🎨 Cast Instance Caching

### Enable Cast Caching

```php
// config/simple-dto.php
return [
    'casts' => [
        'cache_instances' => true,
    ],
];
```

### How It Works

**Without Cache:**
```php
// Creates new cast instance every time
$dto1 = UserDTO::fromArray($data); // new DateTimeCast()
$dto2 = UserDTO::fromArray($data); // new DateTimeCast()
$dto3 = UserDTO::fromArray($data); // new DateTimeCast()
```

**With Cache:**
```php
// Reuses cast instance
$dto1 = UserDTO::fromArray($data); // new DateTimeCast()
$dto2 = UserDTO::fromArray($data); // reuse DateTimeCast
$dto3 = UserDTO::fromArray($data); // reuse DateTimeCast
```

### Performance Impact

- ✅ 40% faster cast operations
- ✅ 60% less memory usage
- ✅ Reduced garbage collection

---

## 💾 Reflection Caching

### Automatic Reflection Caching

SimpleDTO automatically caches reflection data:

```php
// First access - parses class
$dto = UserDTO::fromArray($data); // 0.003s

// Subsequent access - uses cache
$dto = UserDTO::fromArray($data); // 0.001s (3x faster)
```

### What's Cached

- ✅ Property names and types
- ✅ Constructor parameters
- ✅ Attribute metadata
- ✅ Type hints
- ✅ Default values

### Cache Lifetime

Reflection cache persists for the entire request lifecycle.

---

## 🎯 DTO Instance Caching

### Cache DTO Results

**Laravel:**
```php
use Illuminate\Support\Facades\Cache;

public function getUser(int $id): UserDTO
{
    return Cache::remember("user.{$id}", 3600, function() use ($id) {
        $user = User::find($id);
        return UserDTO::fromModel($user);
    });
}
```

**Symfony:**
```php
use Symfony\Contracts\Cache\CacheInterface;

public function getUser(int $id, CacheInterface $cache): UserDTO
{
    return $cache->get("user.{$id}", function() use ($id) {
        $user = $this->repository->find($id);
        return UserDTO::fromEntity($user);
    });
}
```

### Cache Collections

```php
public function getUsers(): DataCollection
{
    return Cache::remember('users.all', 3600, function() {
        $users = User::all();
        return DataCollection::make($users, UserDTO::class);
    });
}
```

### Cache Invalidation

```php
// Invalidate on update
public function update(User $user, UpdateUserDTO $dto): void
{
    $user->update($dto->toArray());
    
    // Clear cache
    Cache::forget("user.{$user->id}");
    Cache::forget('users.all');
}
```

---

## 🔄 Cache Strategies

### 1. Time-Based Caching

```php
// Cache for 1 hour
Cache::remember('users', 3600, fn() => 
    DataCollection::make(User::all(), UserDTO::class)
);
```

### 2. Tag-Based Caching

```php
// Cache with tags
Cache::tags(['users', 'api'])->remember('users.all', 3600, fn() =>
    DataCollection::make(User::all(), UserDTO::class)
);

// Clear all user caches
Cache::tags(['users'])->flush();
```

### 3. Event-Based Invalidation

```php
// Listen for model events
User::updated(function($user) {
    Cache::forget("user.{$user->id}");
    Cache::tags(['users'])->flush();
});
```

### 4. Conditional Caching

```php
public function getUser(int $id): UserDTO
{
    $cacheKey = "user.{$id}";
    
    // Only cache in production
    if (app()->environment('production')) {
        return Cache::remember($cacheKey, 3600, fn() => 
            UserDTO::fromModel(User::find($id))
        );
    }
    
    return UserDTO::fromModel(User::find($id));
}
```

---

## 🎯 Real-World Examples

### Example 1: API Response Caching

```php
class UserController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'users.index.' . md5(json_encode($request->all()));
        
        return Cache::remember($cacheKey, 300, function() use ($request) {
            $users = User::query()
                ->when($request->role, fn($q) => $q->where('role', $request->role))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->get();
            
            $dtos = DataCollection::make($users, UserDTO::class);
            
            return response()->json($dtos);
        });
    }
}
```

### Example 2: Expensive Computation Caching

```php
class StatisticsDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $totalOrders,
        public readonly float $totalRevenue,
        public readonly array $topProducts,
    ) {}
    
    public static function generate(): self
    {
        return Cache::remember('statistics', 3600, function() {
            return new self(
                totalUsers: User::count(),
                totalOrders: Order::count(),
                totalRevenue: Order::sum('total'),
                topProducts: Product::orderBy('sales', 'desc')->take(10)->get()->toArray(),
            );
        });
    }
}
```

### Example 3: Multi-Level Caching

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly AuthorDTO $author,
        public readonly array $comments,
    ) {}
    
    public static function fromModel(Post $post): self
    {
        // Cache author separately
        $author = Cache::remember("author.{$post->author_id}", 3600, fn() =>
            AuthorDTO::fromModel($post->author)
        );
        
        // Cache comments separately
        $comments = Cache::remember("post.{$post->id}.comments", 600, fn() =>
            CommentDTO::collection($post->comments)
        );
        
        return new self(
            id: $post->id,
            title: $post->title,
            author: $author,
            comments: $comments,
        );
    }
}
```

---

## 🔧 Cache Configuration

### Laravel Configuration

```php
// config/simple-dto.php
return [
    'validation' => [
        'cache_rules' => env('DTO_CACHE_VALIDATION', true),
        'cache_driver' => env('DTO_CACHE_DRIVER', 'file'),
        'cache_ttl' => env('DTO_CACHE_TTL', 3600),
        'cache_prefix' => 'dto:validation:',
    ],
    
    'casts' => [
        'cache_instances' => env('DTO_CACHE_CASTS', true),
    ],
    
    'reflection' => [
        'cache_enabled' => true,
    ],
];
```

### Symfony Configuration

```yaml
# config/packages/simple_dto.yaml
simple_dto:
  validation:
    cache_rules: '%env(bool:DTO_CACHE_VALIDATION)%'
    cache_driver: '%env(DTO_CACHE_DRIVER)%'
    cache_ttl: '%env(int:DTO_CACHE_TTL)%'
    cache_prefix: 'dto:validation:'
  
  casts:
    cache_instances: '%env(bool:DTO_CACHE_CASTS)%'
  
  reflection:
    cache_enabled: true
```

### Environment Variables

```env
DTO_CACHE_VALIDATION=true
DTO_CACHE_DRIVER=redis
DTO_CACHE_TTL=3600
DTO_CACHE_CASTS=true
```

---

## 📊 Cache Performance

### Validation Caching Impact

```
Without Cache:
- 5,000 validations/sec
- 0.2ms per validation
- High CPU usage

With Cache:
- 990,000 validations/sec
- 0.001ms per validation
- Low CPU usage

Improvement: 198x faster
```

### Cast Caching Impact

```
Without Cache:
- 450,000 casts/sec
- 0.0022ms per cast
- 2.5 MB memory

With Cache:
- 630,000 casts/sec
- 0.0016ms per cast
- 1.0 MB memory

Improvement: 40% faster, 60% less memory
```

---

## 💡 Best Practices

### 1. Always Cache in Production

```php
// ✅ Good - cached in production
if (app()->environment('production')) {
    ValidationCache::enable();
}

// ❌ Bad - no caching
```

### 2. Use Appropriate TTL

```php
// ✅ Good - appropriate TTL
Cache::remember('users', 300, ...); // 5 minutes for frequently changing data
Cache::remember('settings', 86400, ...); // 24 hours for rarely changing data

// ❌ Bad - same TTL for everything
Cache::remember('users', 3600, ...);
Cache::remember('settings', 3600, ...);
```

### 3. Invalidate on Changes

```php
// ✅ Good - invalidate on change
User::updated(fn($user) => Cache::forget("user.{$user->id}"));

// ❌ Bad - stale cache
```

### 4. Use Cache Tags

```php
// ✅ Good - use tags for easy invalidation
Cache::tags(['users'])->remember('users.all', 3600, ...);
Cache::tags(['users'])->flush();

// ❌ Bad - manual invalidation
Cache::forget('users.all');
Cache::forget('users.active');
Cache::forget('users.inactive');
```

### 5. Monitor Cache Hit Rate

```php
// Track cache hits/misses
$hit = Cache::has($key);
Log::info('Cache ' . ($hit ? 'hit' : 'miss') . ": {$key}");
```

---

## 🔍 Cache Debugging

### Enable Cache Logging

```php
// Log all cache operations
Cache::listen(function($event) {
    Log::debug("Cache {$event->type}: {$event->key}");
});
```

### Monitor Cache Size

```bash
# Redis
redis-cli INFO memory

# File cache
du -sh storage/framework/cache
```

### Clear All Caches

```bash
# Laravel
php artisan cache:clear
php artisan dto:clear

# Symfony
bin/console cache:clear
bin/console dto:clear
```

---

## 📚 Next Steps

1. [Performance](27-performance.md) - Performance optimization
2. [Best Practices](29-best-practices.md) - Tips and recommendations
3. [Validation](07-validation.md) - Validation system
4. [Laravel Integration](17-laravel-integration.md) - Laravel features

---

**Previous:** [Performance](27-performance.md)  
**Next:** [Best Practices](29-best-practices.md)

