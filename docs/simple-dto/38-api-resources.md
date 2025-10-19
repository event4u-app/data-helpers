# API Resources

Complete guide to using SimpleDTO for REST API responses.

---

## 🎯 Overview

SimpleDTO is perfect for API resources:

- ✅ **Type Safety** - Guaranteed data structure
- ✅ **Conditional Fields** - Show/hide based on auth
- ✅ **Computed Properties** - Calculate values
- ✅ **Nested Resources** - Complex structures
- ✅ **Collections** - List responses
- ✅ **Pagination** - Paginated responses

---

## 🚀 Basic API Resource

### User Resource

```php
class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly ?string $avatar,
        
        #[Cast(DateTimeCast::class)]
        #[MapTo('created_at')]
        public readonly Carbon $createdAt,
        
        #[WhenAuth]
        public readonly ?string $email = null,
        
        #[WhenRole('admin')]
        public readonly ?string $ipAddress = null,
    ) {}
}
```

### Controller

```php
class UserController extends Controller
{
    public function show(User $user): JsonResponse
    {
        $dto = UserResourceDTO::fromModel($user);
        
        return response()->json($dto->toArray());
    }
}
```

**Response (Guest):**
```json
{
  "id": 1,
  "name": "John Doe",
  "username": "johndoe",
  "avatar": "https://example.com/avatar.jpg",
  "created_at": "2024-01-15T10:30:00Z"
}
```

**Response (Authenticated):**
```json
{
  "id": 1,
  "name": "John Doe",
  "username": "johndoe",
  "avatar": "https://example.com/avatar.jpg",
  "created_at": "2024-01-15T10:30:00Z",
  "email": "john@example.com"
}
```

---

## 📋 Collection Resources

### User List

```php
class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::all();
        
        $collection = DataCollection::make($users, UserResourceDTO::class);
        
        return response()->json([
            'data' => $collection->toArray(),
            'meta' => [
                'total' => $collection->count(),
            ],
        ]);
    }
}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe"
    },
    {
      "id": 2,
      "name": "Jane Smith",
      "username": "janesmith"
    }
  ],
  "meta": {
    "total": 2
  }
}
```

---

## 📄 Paginated Resources

### Paginated Users

```php
class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::paginate($request->input('per_page', 15));
        
        $collection = DataCollection::make($users->items(), UserResourceDTO::class);
        
        return response()->json([
            'data' => $collection->toArray(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ]);
    }
}
```

---

## 🎨 Nested Resources

### Post with Author and Comments

```php
class PostResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $excerpt,
        public readonly AuthorResourceDTO $author,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $publishedAt,
        
        #[WhenContext('include_content')]
        public readonly ?string $content = null,
        
        #[WhenContext('include_comments')]
        public readonly ?array $comments = null,
    ) {}
    
    #[Computed]
    public function url(): string
    {
        return route('posts.show', $this->slug);
    }
}

class AuthorResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly ?string $avatar,
    ) {}
}
```

### Controller with Context

```php
class PostController extends Controller
{
    public function show(Post $post, Request $request): JsonResponse
    {
        $dto = PostResourceDTO::fromModel($post);
        
        // Add context based on query parameters
        $context = [];
        
        if ($request->has('include_content')) {
            $context['include_content'] = true;
        }
        
        if ($request->has('include_comments')) {
            $context['include_comments'] = true;
        }
        
        return response()->json(
            $dto->withContext($context)->toArray()
        );
    }
}
```

**Request:**
```
GET /api/posts/my-post?include_content=1&include_comments=1
```

**Response:**
```json
{
  "id": 1,
  "title": "My Post",
  "slug": "my-post",
  "excerpt": "This is an excerpt",
  "author": {
    "id": 1,
    "name": "John Doe",
    "username": "johndoe",
    "avatar": "https://example.com/avatar.jpg"
  },
  "published_at": "2024-01-15T10:30:00Z",
  "url": "https://example.com/posts/my-post",
  "content": "Full post content...",
  "comments": [...]
}
```

---

## 🔐 Permission-Based Resources

### Admin vs User View

```php
class ProductResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly string $description,
        
        // Only for authenticated users
        #[WhenAuth]
        public readonly ?bool $inWishlist = null,
        
        // Only for admins
        #[WhenRole('admin')]
        public readonly ?float $cost = null,
        
        #[WhenRole('admin')]
        public readonly ?int $stock = null,
        
        #[WhenRole('admin')]
        public readonly ?array $analytics = null,
    ) {}
}
```

**Response (Guest):**
```json
{
  "id": 1,
  "name": "Product Name",
  "price": 99.99,
  "description": "Product description"
}
```

**Response (User):**
```json
{
  "id": 1,
  "name": "Product Name",
  "price": 99.99,
  "description": "Product description",
  "in_wishlist": true
}
```

**Response (Admin):**
```json
{
  "id": 1,
  "name": "Product Name",
  "price": 99.99,
  "description": "Product description",
  "in_wishlist": true,
  "cost": 45.00,
  "stock": 150,
  "analytics": {...}
}
```

---

## 🎯 CRUD API Example

### Complete CRUD Controller

```php
class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::all();
        $collection = DataCollection::make($products, ProductResourceDTO::class);
        
        return response()->json(['data' => $collection->toArray()]);
    }
    
    public function store(CreateProductDTO $dto): JsonResponse
    {
        $product = Product::create($dto->toArray());
        $resource = ProductResourceDTO::fromModel($product);
        
        return response()->json($resource->toArray(), 201);
    }
    
    public function show(Product $product): JsonResponse
    {
        $dto = ProductResourceDTO::fromModel($product);
        
        return response()->json($dto->toArray());
    }
    
    public function update(Product $product, UpdateProductDTO $dto): JsonResponse
    {
        $product->update($dto->toArray());
        $resource = ProductResourceDTO::fromModel($product->fresh());
        
        return response()->json($resource->toArray());
    }
    
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        
        return response()->json(null, 204);
    }
}
```

---

## 📚 Next Steps

1. [Form Requests](39-form-requests.md) - Form handling
2. [Real-World Examples](37-real-world-examples.md) - More examples
3. [Testing DTOs](40-testing-dtos.md) - Testing strategies

---

**Previous:** [Real-World Examples](37-real-world-examples.md)  
**Next:** [Form Requests](39-form-requests.md)

