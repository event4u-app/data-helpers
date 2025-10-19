# Context-Based Conditions

Learn how to use context to dynamically control property inclusion in DTOs.

---

## 🎯 What is Context?

Context is additional data passed to a DTO that can be used to determine which properties to include:

```php
$dto = UserDTO::fromArray(['name' => 'John', 'email' => 'john@example.com']);

// Without context
$array = $dto->toArray();
// ['name' => 'John']

// With context
$array = $dto->withContext(['include_email' => true])->toArray();
// ['name' => 'John', 'email' => 'john@example.com']
```

---

## 🚀 Basic Usage

### Setting Context

```php
$dto = UserDTO::fromArray($data);

// Single context value
$dto = $dto->withContext(['user' => auth()->user()]);

// Multiple context values
$dto = $dto->withContext([
    'user' => auth()->user(),
    'environment' => app()->environment(),
    'include_email' => true,
]);
```

### Using Context in Attributes

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenContext('include_email')]
        public readonly ?string $email = null,
        
        #[WhenContext('include_phone')]
        public readonly ?string $phone = null,
    ) {}
}

$array = $dto->withContext(['include_email' => true])->toArray();
```

---

## 📋 Context-Based Attributes

### WhenContext

Include when context key exists:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        
        #[WhenContext('include_description')]
        public readonly ?string $description = null,
        
        #[WhenContext('include_stock')]
        public readonly ?int $stock = null,
    ) {}
}

// Include description
$array = $dto->withContext(['include_description' => true])->toArray();

// Include both
$array = $dto->withContext([
    'include_description' => true,
    'include_stock' => true,
])->toArray();
```

### WhenContextEquals

Include when context value equals specific value:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenContextEquals('view', 'detailed')]
        public readonly ?string $description = null,
        
        #[WhenContextEquals('view', 'admin')]
        public readonly ?float $cost = null,
    ) {}
}

// Detailed view
$array = $dto->withContext(['view' => 'detailed'])->toArray();

// Admin view
$array = $dto->withContext(['view' => 'admin'])->toArray();
```

### WhenContextIn

Include when context value is in list:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn;

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        public readonly array $data,
        
        #[WhenContextIn('environment', ['development', 'staging'])]
        public readonly ?array $debugInfo = null,
        
        #[WhenContextIn('role', ['admin', 'developer'])]
        public readonly ?array $internalData = null,
    ) {}
}

$array = $dto->withContext(['environment' => 'development'])->toArray();
```

### WhenContextNotNull

Include when context key is not null:

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextNotNull;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        
        #[WhenContextNotNull('user')]
        public readonly ?string $customerName = null,
        
        #[WhenContextNotNull('shipping_address')]
        public readonly ?string $shippingInfo = null,
    ) {}
}

$array = $dto->withContext(['user' => $user])->toArray();
```

---

## 🎯 Real-World Examples

### Example 1: API Views

```php
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        
        #[WhenContextEquals('view', 'list')]
        public readonly ?string $thumbnail = null,
        
        #[WhenContextEquals('view', 'detailed')]
        public readonly ?string $description = null,
        
        #[WhenContextEquals('view', 'detailed')]
        public readonly ?array $images = null,
        
        #[WhenContextEquals('view', 'admin')]
        public readonly ?float $cost = null,
        
        #[WhenContextEquals('view', 'admin')]
        public readonly ?int $stock = null,
    ) {}
}

// List view
$array = $dto->withContext(['view' => 'list'])->toArray();

// Detailed view
$array = $dto->withContext(['view' => 'detailed'])->toArray();

// Admin view
$array = $dto->withContext(['view' => 'admin'])->toArray();
```

### Example 2: User Permissions

```php
class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        
        #[WhenContextNotNull('user')]
        public readonly ?string $content = null,
        
        #[WhenContextIn('user_role', ['editor', 'admin'])]
        public readonly ?string $editUrl = null,
        
        #[WhenContextEquals('user_role', 'admin')]
        public readonly ?string $deleteUrl = null,
    ) {}
}

$user = auth()->user();
$array = $dto->withContext([
    'user' => $user,
    'user_role' => $user->role,
])->toArray();
```

### Example 3: Environment-Specific Data

```php
class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        public readonly array $data,
        
        #[WhenContextIn('environment', ['development', 'staging'])]
        public readonly ?array $debugInfo = null,
        
        #[WhenContextEquals('environment', 'development')]
        public readonly ?array $sqlQueries = null,
        
        #[WhenContextEquals('environment', 'development')]
        public readonly ?float $executionTime = null,
    ) {}
}

$array = $dto->withContext([
    'environment' => app()->environment(),
])->toArray();
```

### Example 4: Feature Flags

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        
        #[WhenContext('feature_new_profile')]
        public readonly ?array $profileV2 = null,
        
        #[WhenContext('feature_social_links')]
        public readonly ?array $socialLinks = null,
        
        #[WhenContext('feature_achievements')]
        public readonly ?array $achievements = null,
    ) {}
}

$array = $dto->withContext([
    'feature_new_profile' => Feature::enabled('new_profile'),
    'feature_social_links' => Feature::enabled('social_links'),
    'feature_achievements' => Feature::enabled('achievements'),
])->toArray();
```

---

## 🔄 Combining Context with Other Conditions

### Context + Auth

```php
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenAuth]
        #[WhenContext('include_email')]
        public readonly ?string $email = null,
    ) {}
}

// Both conditions must be true:
// 1. User must be authenticated
// 2. Context must include 'include_email'
$array = $dto->withContext(['include_email' => true])->toArray();
```

### Context + Permissions

```php
class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        
        #[WhenCan('edit')]
        #[WhenContextEquals('view', 'admin')]
        public readonly ?string $editUrl = null,
    ) {}
}
```

---

## 🎨 Dynamic Context

### From Request

```php
class ProductController extends Controller
{
    public function show(Request $request, Product $product)
    {
        $dto = ProductDTO::fromModel($product);
        
        $array = $dto->withContext([
            'view' => $request->query('view', 'default'),
            'include_reviews' => $request->boolean('include_reviews'),
            'include_related' => $request->boolean('include_related'),
        ])->toArray();
        
        return response()->json($array);
    }
}
```

### From User Preferences

```php
$user = auth()->user();

$array = $dto->withContext([
    'language' => $user->language,
    'currency' => $user->currency,
    'timezone' => $user->timezone,
    'theme' => $user->theme,
])->toArray();
```

### From Application State

```php
$array = $dto->withContext([
    'environment' => app()->environment(),
    'debug' => config('app.debug'),
    'version' => config('app.version'),
    'locale' => app()->getLocale(),
])->toArray();
```

---

## 🔍 Accessing Context in Custom Logic

### In WhenCallback

```php
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCallback;

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenCallback(function ($value, $dto, $context) {
            return isset($context['user']) && $context['user']->isAdmin();
        })]
        public readonly ?array $adminData = null,
    ) {}
}
```

### In Custom Attributes

```php
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

#[Attribute]
class WhenPremium implements ConditionalProperty
{
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        return isset($context['user']) && $context['user']->isPremium();
    }
}

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        
        #[WhenPremium]
        public readonly ?float $discount = null,
    ) {}
}
```

---

## 📦 Context in Collections

### Apply Context to Collection

```php
$collection = DataCollection::make($products, ProductDTO::class);

$array = $collection
    ->withContext(['view' => 'detailed'])
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

## 🎯 Context Inheritance

### Nested DTOs Inherit Context

```php
class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly UserDTO $customer,
        
        #[WhenContext('include_items')]
        public readonly ?array $items = null,
    ) {}
}

// Context is passed to nested UserDTO
$array = $dto->withContext([
    'include_items' => true,
    'include_email' => true,  // Used by UserDTO
])->toArray();
```

---

## 💡 Best Practices

### 1. Use Descriptive Context Keys

```php
// ✅ Good - descriptive keys
->withContext(['include_email' => true, 'view' => 'detailed'])

// ❌ Bad - unclear keys
->withContext(['e' => true, 'v' => 'd'])
```

### 2. Document Context Requirements

```php
/**
 * @context include_email bool Include email in output
 * @context view string View type: 'list', 'detailed', 'admin'
 * @context user User Current authenticated user
 */
class UserDTO extends SimpleDTO
{
    // ...
}
```

### 3. Provide Default Context

```php
class UserDTO extends SimpleDTO
{
    public static function withDefaultContext(array $data): self
    {
        return self::fromArray($data)->withContext([
            'environment' => app()->environment(),
            'locale' => app()->getLocale(),
        ]);
    }
}
```

### 4. Validate Context

```php
class UserDTO extends SimpleDTO
{
    public function withContext(array $context): self
    {
        $allowed = ['view', 'include_email', 'include_phone'];
        $invalid = array_diff(array_keys($context), $allowed);
        
        if (!empty($invalid)) {
            throw new InvalidArgumentException(
                'Invalid context keys: ' . implode(', ', $invalid)
            );
        }
        
        return parent::withContext($context);
    }
}
```

---

## 📚 Next Steps

1. [Conditional Properties](10-conditional-properties.md) - All conditional attributes
2. [with() Method](11-with-method.md) - Dynamic properties
3. [Lazy Properties](13-lazy-properties.md) - Lazy loading
4. [Security & Visibility](22-security-visibility.md) - Hidden properties

---

**Previous:** [with() Method](11-with-method.md)  
**Next:** [Lazy Properties](13-lazy-properties.md)

