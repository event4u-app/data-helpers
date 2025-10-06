# MappedDataModel

Automatic request mapping with template-based transformation for Laravel and Symfony.

## Overview

`MappedDataModel` provides Laravel-style model binding with automatic data transformation using templates. Similar to Laravel's Form Requests, but with powerful template-based mapping capabilities.

## Features

- ✅ **Automatic Request Binding** - Works with Laravel and Symfony controllers
- ✅ **Template-Based Mapping** - Transform request data using templates
- ✅ **Dual Data Access** - Access both original and mapped values
- ✅ **Type Safety** - Automatic type conversion via filters
- ✅ **Clean Serialization** - JSON/Array output contains only mapped values
- ✅ **Validation Support** - Easy to add custom validation
- ✅ **Framework Agnostic** - Core functionality works without frameworks

## Basic Usage

### 1. Define Your Model

```php
use event4u\DataHelpers\MappedDataModel;

class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '@request.email | lower | trim',
            'name' => '@request.first_name + " " + @request.last_name | trim',
            'age' => '@request.age | int',
            'is_active' => true,
        ];
    }
}
```

### 2. Use in Controller

#### Laravel

```php
class UserController extends Controller
{
    public function register(UserRegistrationModel $request)
    {
        // $request is automatically instantiated and mapped
        $user = User::create($request->toArray());

        return response()->json([
            'user' => $user,
            'original_email' => $request->getOriginal('email'), // For debugging
        ]);
    }
}
```

#### Symfony

```php
class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(UserRegistrationModel $request): JsonResponse
    {
        $user = $this->userRepository->create($request->toArray());

        return $this->json($user);
    }
}
```

## Template Syntax

Templates use the same syntax as `TemplateMapper`:

```php
protected function template(): array
{
    return [
        // Simple field mapping with filters
        'email' => '@request.email | lower | trim',

        // Combining fields
        'full_name' => '@request.first_name + " " + @request.last_name',

        // Type conversion
        'age' => '@request.age | int',
        'price' => '@request.price | float',

        // Default values
        'status' => '@request.status | default:pending',

        // Static values
        'is_active' => true,
        'created_at' => '@now',

        // Nested structures
        'address' => [
            'street' => '@request.street | trim',
            'city' => '@request.city | trim',
            'zip' => '@request.zip',
        ],

        // Pass-through arrays
        'tags' => '@request.tags',
    ];
}
```

## Data Access

### Mapped Values (Default)

```php
$model = new UserRegistrationModel($data);

// Magic getter
$email = $model->email;

// get() method
$email = $model->get('email');
$email = $model->get('email', 'default@example.com');

// Check existence
if ($model->has('email')) {
    // ...
}

// Get all mapped data
$mapped = $model->toArray();
```

### Original Values

```php
// Get original (unmapped) value
$originalEmail = $model->getOriginal('email');

// Get all original data
$original = $model->getOriginalData();

// Check if original field exists
if ($model->hasOriginal('email')) {
    // ...
}
```

### Why Access Original Values?

- **Debugging** - See what was actually sent
- **Validation** - Validate before transformation
- **Logging** - Log original input for audit trails
- **Comparison** - Compare before/after transformation

## Serialization

Only mapped values are serialized by default:

```php
$model = new UserRegistrationModel([
    'email' => '  ALICE@EXAMPLE.COM  ',
    'first_name' => 'Alice',
    'last_name' => 'Smith',
    'age' => '30',
    'extra_field' => 'ignored',
]);

// toArray() - only mapped fields
$array = $model->toArray();
// [
//     'email' => 'alice@example.com',
//     'name' => 'Alice Smith',
//     'age' => 30,
//     'is_active' => true,
// ]

// JSON serialization - only mapped fields
$json = json_encode($model);
// {"email":"alice@example.com","name":"Alice Smith","age":30,"is_active":true}

// String conversion
$string = (string) $model; // Same as json_encode()
```

## Validation

Add validation methods to your model:

```php
class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '@request.email | lower | trim',
            'age' => '@request.age | int',
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if ($this->age < 18) {
            $errors['age'] = 'Must be 18 or older';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}

// Usage
$request = new UserRegistrationModel($data);

if (!$request->isValid()) {
    return response()->json([
        'errors' => $request->validate(),
    ], 422);
}
```

## Framework Integration

### Laravel Setup

**✅ Automatic Setup** - The service provider is automatically registered via Laravel's package auto-discovery.

No configuration needed! Just install the package:

```bash
composer require event4u/data-helpers
```

**Use in Controllers:**

```php
public function store(UserRegistrationModel $request)
{
    // Automatically instantiated and mapped
    return User::create($request->toArray());
}
```

### Symfony Setup

1. **Register Value Resolver**

In `config/services.yaml`:

```yaml
services:
    event4u\DataHelpers\Integration\SymfonyMappedModelResolver:
        tags:
            - { name: controller.argument_value_resolver, priority: 50 }
```

Or with autoconfigure (Symfony 6.1+):

```yaml
services:
    _defaults:
        autoconfigure: true

    event4u\DataHelpers\Integration\SymfonyMappedModelResolver: ~
```

2. **Use in Controllers**

```php
#[Route('/register', methods: ['POST'])]
public function register(UserRegistrationModel $request): JsonResponse
{
    return $this->json($request->toArray());
}
```

## Advanced Examples

### Complex Nested Mapping

```php
class OrderModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'customer' => [
                'email' => '@request.customer_email | lower | trim',
                'name' => '@request.customer_name | trim',
                'phone' => '@request.customer_phone',
            ],
            'items' => '@request.items',
            'shipping' => [
                'address' => '@request.shipping_address',
                'method' => '@request.shipping_method | default:standard',
            ],
            'total' => '@request.total | float',
            'currency' => '@request.currency | upper | default:EUR',
            'status' => 'pending',
            'created_at' => '@now',
        ];
    }
}
```

### With Custom Methods

```php
class ProductUpdateModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'name' => '@request.name | trim',
            'price' => '@request.price | float',
            'sku' => '@request.sku | upper | trim',
        ];
    }

    public function isPriceChanged(): bool
    {
        $original = $this->getOriginal('price');
        return $original !== null && (float)$original !== $this->price;
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2) . ' EUR';
    }
}
```

## Best Practices

1. **Keep Templates Simple** - Complex logic belongs in services, not templates
2. **Use Validation** - Add validation methods for business rules
3. **Access Original for Debugging** - Use `getOriginal()` for logging/debugging
4. **Type Conversion** - Always use filters for type conversion (`| int`, `| float`)
5. **Default Values** - Use `| default:value` for optional fields
6. **Immutability** - Models are immutable by default (no setters)

## Comparison with Laravel Form Requests

| Feature | MappedDataModel | Laravel Form Request |
|---------|---------------------|----------------------|
| Automatic Binding | ✅ | ✅ |
| Validation | ✅ (custom) | ✅ (built-in) |
| Data Transformation | ✅ (template-based) | ❌ |
| Original Data Access | ✅ | ❌ |
| Framework Agnostic | ✅ | ❌ (Laravel only) |
| Authorization | ❌ | ✅ |

## See Also

- [TemplateMapper](./template-mapper.md) - Template syntax reference
- [DataMapper](./data-mapper.md) - Core mapping functionality
- [Examples](../examples/08-abstract-mapped-model.php) - More examples

