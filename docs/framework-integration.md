# Framework Integration

The `event4u/data-helpers` package works with **any PHP 8.2+ project** and provides optional framework integrations for Laravel and Symfony.

## Laravel Integration

### Automatic Setup ✅

The Laravel service provider is **automatically registered** via Laravel's package auto-discovery.

**No configuration needed!** Just install the package:

```bash
composer require event4u/data-helpers
```

The `LaravelMappedModelServiceProvider` will be automatically loaded and enables:
- ✅ Automatic dependency injection of `MappedDataModel` in controllers
- ✅ Request data automatically mapped to your models
- ✅ Type casting and transformation pipelines

### Usage in Laravel Controllers

```php
use event4u\DataHelpers\MappedDataModel;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToInteger;

class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => 'request.email',
            'name' => 'request.name',
            'age' => 'request.age',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            CastToInteger::class,
        ];
    }
}

// Controller
class UserController extends Controller
{
    public function register(UserRegistrationModel $model)
    {
        // $model is automatically instantiated with request data
        // All transformations are already applied!
        
        $user = User::create($model->toArray());
        
        return response()->json($user);
    }
}
```

### How It Works

1. Laravel's service container detects the `MappedDataModel` type hint
2. The service provider creates an instance of your model
3. Current request data is automatically passed to `fill()`
4. All pipes and transformations are applied
5. Your controller receives the fully mapped and typed model

---

## Symfony Integration

### Manual Setup Required

For Symfony, you need to manually register the value resolver in your `config/services.yaml`:

```yaml
services:
    event4u\DataHelpers\Integration\SymfonyMappedModelResolver:
        tags:
            - { name: controller.argument_value_resolver, priority: 50 }
```

Or use autoconfigure (Symfony 6.1+):

```yaml
services:
    _defaults:
        autoconfigure: true

    event4u\DataHelpers\Integration\SymfonyMappedModelResolver: ~
```

### Usage in Symfony Controllers

```php
use event4u\DataHelpers\MappedDataModel;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => 'request.email',
            'name' => 'request.name',
            'age' => 'request.age',
        ];
    }

    protected function pipes(): array
    {
        return [TrimStrings::class];
    }
}

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(UserRegistrationModel $model): JsonResponse
    {
        // $model is automatically instantiated with request data
        $user = $this->userRepository->create($model->toArray());
        
        return $this->json($user);
    }
}
```

### How It Works

1. Symfony's argument resolver detects the `MappedDataModel` type hint
2. The `SymfonyMappedModelResolver` creates an instance
3. Request data (JSON or form data) is automatically extracted
4. All pipes and transformations are applied
5. Your controller receives the fully mapped model

---

## Standalone PHP (No Framework)

You can use `MappedDataModel` without any framework:

```php
use event4u\DataHelpers\MappedDataModel;

class ProductModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'product_id' => 'request.id',
            'name' => 'request.name',
            'price' => 'request.price',
        ];
    }
}

// Manual instantiation
$data = $_POST; // or json_decode(file_get_contents('php://input'), true);
$product = new ProductModel($data);

// Access mapped data
echo $product->product_id;
echo $product->name;
echo $product->price;

// Or get as array
$array = $product->toArray();
```

---

## Comparison

| Feature | Laravel | Symfony | Standalone |
|---------|---------|---------|------------|
| **Auto-Discovery** | ✅ Yes | ❌ Manual setup | N/A |
| **Controller Injection** | ✅ Yes | ✅ Yes | ❌ Manual |
| **Request Data Extraction** | ✅ Automatic | ✅ Automatic | ⚠️ Manual |
| **Type Casting** | ✅ Yes | ✅ Yes | ✅ Yes |
| **Transformation Pipes** | ✅ Yes | ✅ Yes | ✅ Yes |
| **Validation** | ⚠️ Custom | ⚠️ Custom | ⚠️ Custom |

---

## Benefits of Framework Integration

### Laravel
- **Zero Configuration**: Works out of the box
- **Familiar Pattern**: Similar to Form Requests
- **Type Safety**: Automatic type casting
- **Clean Controllers**: Less boilerplate code

### Symfony
- **Standard Integration**: Uses Symfony's ValueResolverInterface
- **Flexible**: Works with JSON and form data
- **Type Safe**: Full type hinting support
- **Clean Code**: Separation of concerns

### Standalone
- **No Dependencies**: Works anywhere
- **Full Control**: Manual data handling
- **Lightweight**: Minimal overhead
- **Portable**: Easy to integrate

---

## Advanced Configuration

### Custom Service Provider (Laravel)

If you need to customize the service provider behavior:

```php
// In your AppServiceProvider
use event4u\DataHelpers\MappedDataModel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->resolving(MappedDataModel::class, function ($model, $app) {
            // Custom logic before filling
            if (!$model->isMapped()) {
                $request = $app->make(Request::class);
                $model->fill($request->all());
            }
        });
    }
}
```

### Custom Value Resolver (Symfony)

If you need custom resolver logic:

```php
use event4u\DataHelpers\MappedDataModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CustomMappedModelResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        
        if (!$type || !is_subclass_of($type, MappedDataModel::class)) {
            return [];
        }

        // Custom data extraction
        $data = $this->extractData($request);
        
        $model = new $type();
        $model->fill($data);
        
        yield $model;
    }
    
    private function extractData(Request $request): array
    {
        // Your custom logic here
        return $request->request->all() ?: json_decode($request->getContent(), true) ?? [];
    }
}
```

---

## Troubleshooting

### Laravel: Service Provider Not Loading

If the service provider is not automatically loaded:

1. Clear cache: `php artisan cache:clear`
2. Dump autoload: `composer dump-autoload`
3. Check `composer.json` has the `extra.laravel.providers` section

### Symfony: Resolver Not Working

If the resolver is not being called:

1. Verify `services.yaml` configuration
2. Clear cache: `php bin/console cache:clear`
3. Check that the resolver is tagged correctly
4. Ensure Symfony version is 6.0+

### Type Casting Not Working

If automatic type casting is not applied:

1. Check field names match the patterns (e.g., `is_active`, `product_id`, `price`)
2. Verify pipes are defined in `pipes()` method
3. Ensure transformers are in the correct order

---

## See Also

- [MappedDataModel Documentation](mapped-data-model.md)
- [Transformers Documentation](transformers.md)
- [Examples](../examples/08-mapped-data-model.php)

