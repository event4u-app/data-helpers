# with() Method

Learn how to dynamically add properties to DTOs without modifying the class definition.

---

## 🎯 What is the with() Method?

The `with()` method allows you to add additional properties to a DTO's output without changing the class:

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

$array = $dto
    ->with('timestamp', now())
    ->with('version', '1.0')
    ->toArray();

// [
//     'name' => 'John',
//     'email' => 'john@example.com',
//     'timestamp' => '2024-01-15 10:00:00',
//     'version' => '1.0',
// ]
```

---

## 🚀 Basic Usage

### Add Single Property

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

$array = $dto->with('timestamp', now())->toArray();
// Adds 'timestamp' to output
```

### Add Multiple Properties

```php
$array = $dto
    ->with('timestamp', now())
    ->with('version', '1.0')
    ->with('environment', 'production')
    ->toArray();
```

### Chainable

```php
$json = $dto
    ->with('timestamp', now())
    ->with('version', '1.0')
    ->toJson();
```

---

## 🎨 Advanced Usage

### Add Computed Values

```php
$dto = UserDTO::fromArray(['firstName' => 'John', 'lastName' => 'Doe']);

$array = $dto
    ->with('fullName', $dto->firstName . ' ' . $dto->lastName)
    ->with('initials', $dto->firstName[0] . $dto->lastName[0])
    ->toArray();

// [
//     'firstName' => 'John',
//     'lastName' => 'Doe',
//     'fullName' => 'John Doe',
//     'initials' => 'JD',
// ]
```

### Add Nested Data

```php
$array = $dto
    ->with('meta', [
        'timestamp' => now(),
        'version' => '1.0',
        'environment' => 'production',
    ])
    ->toArray();

// [
//     'name' => 'John',
//     'email' => 'john@example.com',
//     'meta' => [
//         'timestamp' => '2024-01-15 10:00:00',
//         'version' => '1.0',
//         'environment' => 'production',
//     ],
// ]
```

### Add Related Data

```php
$user = User::find(1);
$dto = UserDTO::fromModel($user);

$array = $dto
    ->with('posts', $user->posts->map(fn($post) => PostDTO::fromModel($post)))
    ->with('comments', $user->comments->count())
    ->toArray();
```

---

## 🔄 Lazy Evaluation

### Using Closures

```php
$array = $dto
    ->with('timestamp', fn() => now())
    ->with('random', fn() => rand(1, 100))
    ->toArray();

// Closures are evaluated when toArray() is called
```

### Expensive Operations

```php
$array = $dto
    ->with('statistics', fn() => $this->calculateExpensiveStats())
    ->with('recommendations', fn() => $this->getRecommendations())
    ->toArray();

// Only calculated when needed
```

---

## 🎯 Real-World Examples

### Example 1: API Metadata

```php
class UserController extends Controller
{
    public function show(User $user)
    {
        $dto = UserDTO::fromModel($user);
        
        return response()->json(
            $dto
                ->with('meta', [
                    'timestamp' => now()->toIso8601String(),
                    'version' => '1.0',
                    'request_id' => request()->id(),
                ])
                ->toArray()
        );
    }
}
```

### Example 2: Pagination Data

```php
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::paginate(15);
        $dtos = DataCollection::make($users->items(), UserDTO::class);
        
        return response()->json(
            $dtos
                ->with('pagination', [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ])
                ->toArray()
        );
    }
}
```

### Example 3: HATEOAS Links

```php
$dto = PostDTO::fromModel($post);

$array = $dto
    ->with('links', [
        'self' => route('posts.show', $post),
        'edit' => route('posts.edit', $post),
        'delete' => route('posts.destroy', $post),
        'author' => route('users.show', $post->user_id),
    ])
    ->toArray();
```

### Example 4: Conditional Metadata

```php
$dto = UserDTO::fromModel($user);

$array = $dto
    ->with('is_online', $user->isOnline())
    ->with('last_seen', $user->last_seen_at)
    ->when(auth()->user()->isAdmin(), fn($dto) => 
        $dto->with('admin_notes', $user->admin_notes)
    )
    ->toArray();
```

---

## 🔍 Combining with Context

### with() + withContext()

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

$array = $dto
    ->withContext(['user' => auth()->user()])
    ->with('timestamp', now())
    ->with('request_id', request()->id())
    ->toArray();
```

---

## 🎨 Conditional with()

### Using when()

```php
$array = $dto
    ->with('timestamp', now())
    ->when(auth()->check(), fn($dto) => 
        $dto->with('user_id', auth()->id())
    )
    ->when($includeStats, fn($dto) => 
        $dto->with('statistics', $this->getStats())
    )
    ->toArray();
```

### Using unless()

```php
$array = $dto
    ->with('timestamp', now())
    ->unless(app()->environment('production'), fn($dto) => 
        $dto->with('debug_info', $this->getDebugInfo())
    )
    ->toArray();
```

---

## 📦 with() on Collections

### DataCollection with()

```php
$collection = DataCollection::make($users, UserDTO::class);

$array = $collection
    ->with('meta', [
        'total' => $collection->count(),
        'timestamp' => now(),
    ])
    ->toArray();
```

### Per-Item with()

```php
$collection = DataCollection::make($users, UserDTO::class);

$array = $collection
    ->map(fn($dto) => 
        $dto->with('is_online', $dto->isOnline())
    )
    ->toArray();
```

---

## 🔄 Overriding Properties

### Override Existing Property

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

$array = $dto
    ->with('name', 'Jane Doe')  // Overrides 'name'
    ->toArray();

// [
//     'name' => 'Jane Doe',  // Overridden
//     'email' => 'john@example.com',
// ]
```

### Transform Existing Property

```php
$array = $dto
    ->with('email', strtolower($dto->email))
    ->toArray();
```

---

## 🎯 Performance Considerations

### Lazy Evaluation

```php
// ✅ Good - lazy evaluation
$array = $dto
    ->with('expensive', fn() => $this->expensiveOperation())
    ->toArray();

// ❌ Bad - eager evaluation
$array = $dto
    ->with('expensive', $this->expensiveOperation())
    ->toArray();
```

### Caching

```php
// ✅ Good - cache expensive operations
$array = $dto
    ->with('statistics', fn() => Cache::remember('stats', 3600, fn() => 
        $this->calculateStats()
    ))
    ->toArray();
```

---

## 🎨 Custom with() Methods

### Create Custom Methods

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
    
    public function withTimestamp(): self
    {
        return $this->with('timestamp', now());
    }
    
    public function withMeta(array $meta): self
    {
        return $this->with('meta', $meta);
    }
    
    public function withLinks(): self
    {
        return $this->with('links', [
            'self' => route('users.show', $this->id),
            'edit' => route('users.edit', $this->id),
        ]);
    }
}

// Usage
$array = $dto
    ->withTimestamp()
    ->withMeta(['version' => '1.0'])
    ->withLinks()
    ->toArray();
```

---

## 🔍 Debugging

### Check Added Properties

```php
$dto = $dto
    ->with('timestamp', now())
    ->with('version', '1.0');

// Get all added properties
$added = $dto->getAddedProperties();
print_r($added);

// Check if property was added
$hasTimestamp = $dto->hasAddedProperty('timestamp');
```

---

## 💡 Best Practices

### 1. Use Closures for Expensive Operations

```php
// ✅ Good - lazy evaluation
->with('stats', fn() => $this->calculateStats())

// ❌ Bad - eager evaluation
->with('stats', $this->calculateStats())
```

### 2. Group Related Data

```php
// ✅ Good - grouped metadata
->with('meta', [
    'timestamp' => now(),
    'version' => '1.0',
    'environment' => 'production',
])

// ❌ Bad - scattered metadata
->with('timestamp', now())
->with('version', '1.0')
->with('environment', 'production')
```

### 3. Use Custom Methods for Common Patterns

```php
// ✅ Good - reusable method
->withTimestamp()
->withMeta(['version' => '1.0'])

// ❌ Bad - repeated code
->with('timestamp', now())
->with('meta', ['version' => '1.0'])
```

### 4. Chain Methods

```php
// ✅ Good - chained
$json = $dto
    ->withContext(['user' => $user])
    ->with('timestamp', now())
    ->with('version', '1.0')
    ->toJson();

// ❌ Bad - separate calls
$dto = $dto->withContext(['user' => $user]);
$dto = $dto->with('timestamp', now());
$dto = $dto->with('version', '1.0');
$json = $dto->toJson();
```

---

## 🎯 Use Cases

### 1. API Versioning

```php
$array = $dto
    ->with('api_version', '2.0')
    ->with('deprecated_fields', ['old_field'])
    ->toArray();
```

### 2. Request Tracking

```php
$array = $dto
    ->with('request_id', request()->id())
    ->with('timestamp', now())
    ->with('user_agent', request()->userAgent())
    ->toArray();
```

### 3. A/B Testing

```php
$array = $dto
    ->with('experiment_id', $experiment->id)
    ->with('variant', $experiment->variant)
    ->toArray();
```

### 4. Feature Flags

```php
$array = $dto
    ->with('features', [
        'new_ui' => Feature::enabled('new_ui'),
        'beta_features' => Feature::enabled('beta'),
    ])
    ->toArray();
```

---

## 📚 Next Steps

1. [Context-Based Conditions](12-context-based-conditions.md) - Advanced context usage
2. [Conditional Properties](10-conditional-properties.md) - Dynamic properties
3. [Computed Properties](14-computed-properties.md) - Calculated properties
4. [Collections](15-collections.md) - Working with collections

---

**Previous:** [Conditional Properties](10-conditional-properties.md)  
**Next:** [Context-Based Conditions](12-context-based-conditions.md)

