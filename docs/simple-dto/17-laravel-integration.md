# Laravel Integration

Learn how to use SimpleDTO with Laravel for Eloquent, validation, controllers, and more.

---

## 🎯 Overview

SimpleDTO provides seamless Laravel integration:

- ✅ **Eloquent Integration** - fromModel(), toModel()
- ✅ **Request Validation** - validateAndCreate()
- ✅ **Controller Injection** - Automatic validation
- ✅ **API Resources** - Replace Laravel Resources
- ✅ **Artisan Commands** - make:dto, dto:typescript
- ✅ **Laravel Attributes** - WhenAuth, WhenGuest, WhenCan, WhenRole

---

## 🚀 Installation

```bash
composer require event4u/data-helpers
```

Laravel will automatically discover the service provider.

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=simple-dto-config
```

---

## 🗄️ Eloquent Integration

### From Model

```php
use App\Models\User;

$user = User::find(1);
$dto = UserDTO::fromModel($user);
```

### To Model

```php
$dto = UserDTO::fromArray($request->all());
$user = $dto->toModel(User::class);
$user->save();
```

### Create Model from DTO

```php
$dto = UserDTO::validateAndCreate($request->all());
$user = User::create($dto->toArray());
```

### Update Model from DTO

```php
$user = User::find(1);
$dto = UserDTO::validateAndCreate($request->all());
$user->update($dto->toArray());
```

### With Relationships

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?array $posts = null,
    ) {}
    
    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            posts: PostDTO::collection($user->posts),
        );
    }
}

$user = User::with('posts')->find(1);
$dto = UserDTO::fromModel($user);
```

---

## ✅ Request Validation

### Basic Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;

class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email, Unique('users', 'email')]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// In controller
public function store(Request $request)
{
    $dto = CreateUserDTO::validateAndCreate($request->all());
    $user = User::create($dto->toArray());
    return response()->json($user, 201);
}
```

### Controller Injection with Auto-Validation

```php
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;

#[ValidateRequest]
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// Automatic validation!
public function store(CreateUserDTO $dto)
{
    $user = User::create($dto->toArray());
    return response()->json($user, 201);
}
```

### Custom Validation Messages

```php
class CreateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
    ) {}
    
    public function messages(): array
    {
        return [
            'email.required' => 'Please provide an email address',
            'email.email' => 'Please provide a valid email address',
        ];
    }
}
```

---

## 🎨 API Resources

### Replace Laravel API Resources

```php
// Old Laravel Resource
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->isAdmin(), $this->email),
        ];
    }
}

// New SimpleDTO
class UserResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}

// In controller
public function show(User $user)
{
    return response()->json(UserResourceDTO::fromModel($user));
}
```

### Collection Resources

```php
public function index()
{
    $users = User::all();
    $dtos = DataCollection::make($users, UserResourceDTO::class);
    
    return response()->json($dtos);
}
```

### With Pagination

```php
public function index(Request $request)
{
    $users = User::paginate(15);
    $dtos = DataCollection::make($users->items(), UserResourceDTO::class);
    
    return response()->json([
        'data' => $dtos->toArray(),
        'meta' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
    ]);
}
```

---

## 🔐 Laravel-Specific Attributes

### WhenAuth

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}

// Automatically checks auth()->check()
```

### WhenGuest

```php
class PageDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenGuest]
        public readonly ?string $loginPrompt = null,
    ) {}
}
```

### WhenCan

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenCan('edit')]
        public readonly ?string $editUrl = null,
        
        #[WhenCan('delete')]
        public readonly ?string $deleteUrl = null,
    ) {}
}

// With subject
$dto = PostDTO::fromModel($post);
$array = $dto->withContext(['subject' => $post])->toArray();
```

### WhenRole

```php
class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenRole('admin')]
        public readonly ?array $adminPanel = null,
        
        #[WhenRole(['admin', 'moderator'])]
        public readonly ?array $moderationTools = null,
    ) {}
}
```

---

## 🎯 Real-World Examples

### Example 1: CRUD API

```php
class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json(
            DataCollection::make($users, UserResourceDTO::class)
        );
    }
    
    public function store(CreateUserDTO $dto)
    {
        $user = User::create($dto->toArray());
        return response()->json(
            UserResourceDTO::fromModel($user),
            201
        );
    }
    
    public function show(User $user)
    {
        return response()->json(
            UserResourceDTO::fromModel($user)
        );
    }
    
    public function update(UpdateUserDTO $dto, User $user)
    {
        $user->update($dto->toArray());
        return response()->json(
            UserResourceDTO::fromModel($user)
        );
    }
    
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
```

### Example 2: Form Request Replacement

```php
// Old Laravel Form Request
class StorePostRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}

// New SimpleDTO
#[ValidateRequest]
class CreatePostDTO extends SimpleDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $title,
        
        #[Required, StringType]
        public readonly string $content,
        
        #[Required, Exists('categories', 'id')]
        public readonly int $categoryId,
    ) {}
}

// In controller
public function store(CreatePostDTO $dto)
{
    $post = Post::create($dto->toArray());
    return response()->json($post, 201);
}
```

### Example 3: Nested Resources

```php
class PostResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly CategoryDTO $category,
        
        #[WhenContext('include_comments')]
        public readonly ?array $comments = null,
    ) {}
    
    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            title: $post->title,
            content: $post->content,
            author: AuthorDTO::fromModel($post->author),
            category: CategoryDTO::fromModel($post->category),
            comments: CommentDTO::collection($post->comments),
        );
    }
}

// In controller
public function show(Request $request, Post $post)
{
    $dto = PostResourceDTO::fromModel($post);
    
    if ($request->boolean('include_comments')) {
        $dto = $dto->withContext(['include_comments' => true]);
    }
    
    return response()->json($dto);
}
```

---

## 🛠️ Artisan Commands

### Create DTO

```bash
php artisan make:dto UserDTO
php artisan make:dto User/ProfileDTO
```

### Generate TypeScript

```bash
php artisan dto:typescript
php artisan dto:typescript --output=resources/js/types
```

### List DTOs

```bash
php artisan dto:list
```

### Validate DTO

```bash
php artisan dto:validate UserDTO
```

---

## 🔄 Middleware Integration

### Custom Middleware

```php
class TransformToDTO
{
    public function handle(Request $request, Closure $next, string $dtoClass)
    {
        $dto = $dtoClass::validateAndCreate($request->all());
        $request->merge(['dto' => $dto]);
        
        return $next($request);
    }
}

// In routes
Route::post('/users', [UserController::class, 'store'])
    ->middleware('transform.dto:' . CreateUserDTO::class);
```

---

## 📦 Service Provider

### Custom Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DTOServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register custom casts
        $this->app->bind(CustomCast::class, function () {
            return new CustomCast();
        });
        
        // Register custom validation rules
        Validator::extend('custom_rule', function ($attribute, $value) {
            return /* validation logic */;
        });
    }
}
```

---

## 🎨 Queue Integration

### Queueable DTOs

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessUserJob implements ShouldQueue
{
    public function __construct(
        public UserDTO $user
    ) {}
    
    public function handle()
    {
        // Process user DTO
    }
}

// Dispatch
ProcessUserJob::dispatch(UserDTO::fromModel($user));
```

---

## 💡 Best Practices

### 1. Use DTOs for API Responses

```php
// ✅ Good - consistent API responses
return response()->json(UserResourceDTO::fromModel($user));

// ❌ Bad - inconsistent responses
return response()->json($user);
```

### 2. Validate at Controller Entry

```php
// ✅ Good - validate early
public function store(CreateUserDTO $dto)
{
    $user = User::create($dto->toArray());
}

// ❌ Bad - validate late
public function store(Request $request)
{
    $user = User::create($request->all());
    // Validation happens in model or later
}
```

### 3. Use Type Hints

```php
// ✅ Good - type hinted
public function store(CreateUserDTO $dto): JsonResponse

// ❌ Bad - no type hints
public function store($dto)
```

### 4. Separate Request and Response DTOs

```php
// ✅ Good - separate DTOs
class CreateUserDTO extends SimpleDTO { /* ... */ }
class UserResourceDTO extends SimpleDTO { /* ... */ }

// ❌ Bad - same DTO for both
class UserDTO extends SimpleDTO { /* ... */ }
```

---

## 📚 Next Steps

1. [Symfony Integration](18-symfony-integration.md) - Symfony features
2. [Validation](07-validation.md) - Advanced validation
3. [API Resources](38-api-resources.md) - REST API examples
4. [Artisan Commands](25-artisan-commands.md) - All commands

---

**Previous:** [Nested DTOs](16-nested-dtos.md)  
**Next:** [Symfony Integration](18-symfony-integration.md)

