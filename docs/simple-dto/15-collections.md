# Collections

Learn how to work with collections of DTOs using DataCollection.

---

## 🎯 What is DataCollection?

DataCollection is a powerful wrapper for working with arrays of DTOs, providing Laravel-like collection methods:

```php
use event4u\DataHelpers\SimpleDTO\DataCollection;

$collection = DataCollection::make([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
    ['name' => 'Bob', 'age' => 35],
], UserDTO::class);

$adults = $collection->filter(fn($user) => $user->age >= 18);
$names = $collection->map(fn($user) => $user->name);
$sorted = $collection->sortBy('age');
```

---

## 🚀 Creating Collections

### From Array of Arrays

```php
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
];

$collection = DataCollection::make($data, UserDTO::class);
```

### From Eloquent Collection

```php
$users = User::all();
$collection = DataCollection::make($users, UserDTO::class);
```

### From Query Builder

```php
$users = User::where('active', true)->get();
$collection = DataCollection::make($users, UserDTO::class);
```

### From Doctrine Entities

```php
$users = $repository->findAll();
$collection = DataCollection::make($users, UserDTO::class);
```

### Empty Collection

```php
$collection = DataCollection::empty(UserDTO::class);
```

---

## 🔍 Filtering

### filter()

```php
$adults = $collection->filter(fn($user) => $user->age >= 18);
$active = $collection->filter(fn($user) => $user->active);
```

### where()

```php
$admins = $collection->where('role', 'admin');
$verified = $collection->where('emailVerified', true);
```

### whereIn()

```php
$roles = $collection->whereIn('role', ['admin', 'moderator']);
```

### whereNotIn()

```php
$users = $collection->whereNotIn('status', ['banned', 'suspended']);
```

### first()

```php
$first = $collection->first();
$admin = $collection->first(fn($user) => $user->role === 'admin');
```

### last()

```php
$last = $collection->last();
```

### find()

```php
$user = $collection->find(fn($user) => $user->id === 1);
```

---

## 🔄 Transformation

### map()

```php
$names = $collection->map(fn($user) => $user->name);
// ['John', 'Jane', 'Bob']

$emails = $collection->map(fn($user) => $user->email);
// ['john@example.com', 'jane@example.com', 'bob@example.com']
```

### mapToArray()

```php
$arrays = $collection->mapToArray();
// [
//     ['name' => 'John', 'email' => 'john@example.com'],
//     ['name' => 'Jane', 'email' => 'jane@example.com'],
// ]
```

### pluck()

```php
$names = $collection->pluck('name');
// ['John', 'Jane', 'Bob']

$emailsByName = $collection->pluck('email', 'name');
// ['John' => 'john@example.com', 'Jane' => 'jane@example.com']
```

### transform()

```php
$collection->transform(fn($user) => 
    $user->with('timestamp', now())
);
```

---

## 📊 Sorting

### sortBy()

```php
$sorted = $collection->sortBy('age');
$sorted = $collection->sortBy('name');
```

### sortByDesc()

```php
$sorted = $collection->sortByDesc('age');
```

### sort()

```php
$sorted = $collection->sort(fn($a, $b) => $a->age <=> $b->age);
```

### reverse()

```php
$reversed = $collection->reverse();
```

---

## 📦 Grouping

### groupBy()

```php
$byRole = $collection->groupBy('role');
// [
//     'admin' => [UserDTO, UserDTO],
//     'user' => [UserDTO, UserDTO, UserDTO],
// ]

$byAge = $collection->groupBy(fn($user) => 
    $user->age >= 18 ? 'adult' : 'minor'
);
```

### chunk()

```php
$chunks = $collection->chunk(10);
// [[UserDTO, ...], [UserDTO, ...], ...]
```

---

## 🔢 Aggregation

### count()

```php
$total = $collection->count();
```

### sum()

```php
$totalAge = $collection->sum('age');
$totalPrice = $collection->sum(fn($product) => $product->price);
```

### avg()

```php
$averageAge = $collection->avg('age');
```

### min()

```php
$youngest = $collection->min('age');
```

### max()

```php
$oldest = $collection->max('age');
```

---

## 📄 Pagination

### paginate()

```php
$paginated = $collection->paginate(perPage: 15, page: 1);

// Returns:
// [
//     'data' => [...],
//     'current_page' => 1,
//     'per_page' => 15,
//     'total' => 100,
//     'last_page' => 7,
//     'from' => 1,
//     'to' => 15,
// ]
```

### simplePaginate()

```php
$paginated = $collection->simplePaginate(perPage: 15, page: 1);

// Returns:
// [
//     'data' => [...],
//     'current_page' => 1,
//     'per_page' => 15,
//     'has_more' => true,
// ]
```

---

## 🎯 Real-World Examples

### Example 1: User List with Filtering

```php
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();
        $collection = DataCollection::make($users, UserDTO::class);
        
        // Filter by role
        if ($request->has('role')) {
            $collection = $collection->where('role', $request->role);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $collection = $collection->where('status', $request->status);
        }
        
        // Sort
        $collection = $collection->sortBy($request->get('sort', 'name'));
        
        // Paginate
        $paginated = $collection->paginate(
            perPage: $request->get('per_page', 15),
            page: $request->get('page', 1)
        );
        
        return response()->json($paginated);
    }
}
```

### Example 2: Product Catalog

```php
class ProductController extends Controller
{
    public function catalog(Request $request)
    {
        $products = Product::all();
        $collection = DataCollection::make($products, ProductDTO::class);
        
        // Filter by category
        if ($request->has('category')) {
            $collection = $collection->where('category', $request->category);
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $collection = $collection->filter(
                fn($product) => $product->price >= $request->min_price
            );
        }
        
        if ($request->has('max_price')) {
            $collection = $collection->filter(
                fn($product) => $product->price <= $request->max_price
            );
        }
        
        // Filter in stock
        if ($request->boolean('in_stock')) {
            $collection = $collection->filter(
                fn($product) => $product->stock > 0
            );
        }
        
        // Sort
        $sortBy = $request->get('sort', 'name');
        $collection = match($sortBy) {
            'price_asc' => $collection->sortBy('price'),
            'price_desc' => $collection->sortByDesc('price'),
            'name' => $collection->sortBy('name'),
            'newest' => $collection->sortByDesc('createdAt'),
            default => $collection,
        };
        
        return response()->json($collection->paginate(24));
    }
}
```

### Example 3: Statistics Dashboard

```php
class DashboardController extends Controller
{
    public function statistics()
    {
        $orders = Order::all();
        $collection = DataCollection::make($orders, OrderDTO::class);
        
        return response()->json([
            'total_orders' => $collection->count(),
            'total_revenue' => $collection->sum('total'),
            'average_order' => $collection->avg('total'),
            'largest_order' => $collection->max('total'),
            'smallest_order' => $collection->min('total'),
            'by_status' => $collection->groupBy('status')->map(
                fn($group) => $group->count()
            ),
            'by_month' => $collection->groupBy(
                fn($order) => $order->createdAt->format('Y-m')
            )->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('total'),
            ]),
        ]);
    }
}
```

---

## 🔄 Chaining Methods

```php
$result = $collection
    ->filter(fn($user) => $user->active)
    ->where('role', 'admin')
    ->sortBy('name')
    ->map(fn($user) => $user->with('timestamp', now()))
    ->paginate(15);
```

---

## 🎨 Combining with Context

### Apply Context to All Items

```php
$collection = DataCollection::make($users, UserDTO::class);

$array = $collection
    ->withContext(['include_email' => true])
    ->toArray();
```

### Per-Item Context

```php
$array = $collection
    ->map(fn($dto, $index) => 
        $dto->withContext(['index' => $index])
    )
    ->toArray();
```

---

## 📊 Serialization

### toArray()

```php
$array = $collection->toArray();
// [
//     ['name' => 'John', 'email' => 'john@example.com'],
//     ['name' => 'Jane', 'email' => 'jane@example.com'],
// ]
```

### toJson()

```php
$json = $collection->toJson();
// [{"name":"John","email":"john@example.com"},{"name":"Jane","email":"jane@example.com"}]
```

### with()

```php
$array = $collection
    ->with('meta', [
        'total' => $collection->count(),
        'timestamp' => now(),
    ])
    ->toArray();
```

---

## 🔍 Checking Contents

### isEmpty()

```php
if ($collection->isEmpty()) {
    return response()->json(['message' => 'No users found'], 404);
}
```

### isNotEmpty()

```php
if ($collection->isNotEmpty()) {
    // Process collection
}
```

### contains()

```php
$hasAdmin = $collection->contains(fn($user) => $user->role === 'admin');
```

### every()

```php
$allActive = $collection->every(fn($user) => $user->active);
```

### some()

```php
$hasInactive = $collection->some(fn($user) => !$user->active);
```

---

## 💡 Best Practices

### 1. Use Type Hints

```php
// ✅ Good - type hinted
/** @var DataCollection<UserDTO> */
$collection = DataCollection::make($users, UserDTO::class);

// ❌ Bad - no type hint
$collection = DataCollection::make($users, UserDTO::class);
```

### 2. Chain Methods

```php
// ✅ Good - chained
$result = $collection
    ->filter(fn($user) => $user->active)
    ->sortBy('name')
    ->paginate(15);

// ❌ Bad - separate calls
$filtered = $collection->filter(fn($user) => $user->active);
$sorted = $filtered->sortBy('name');
$result = $sorted->paginate(15);
```

### 3. Use Lazy Loading for Large Collections

```php
// ✅ Good - lazy loading
$collection = DataCollection::make($users, UserDTO::class);
$paginated = $collection->paginate(15);

// ❌ Bad - loading all at once
$all = $collection->toArray();
```

### 4. Cache Expensive Operations

```php
// ✅ Good - cache collection
$collection = Cache::remember('users', 3600, fn() => 
    DataCollection::make(User::all(), UserDTO::class)
);
```

---

## 📚 Next Steps

1. [Nested DTOs](16-nested-dtos.md) - Working with nested structures
2. [Lazy Properties](13-lazy-properties.md) - Lazy loading
3. [Performance](27-performance.md) - Optimization tips
4. [API Resources](38-api-resources.md) - REST API examples

---

**Previous:** [Computed Properties](14-computed-properties.md)  
**Next:** [Nested DTOs](16-nested-dtos.md)

