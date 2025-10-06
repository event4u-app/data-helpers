<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Integration;

use event4u\DataHelpers\MappedDataModel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for automatic MappedDataModel binding.
 *
 * This provider enables automatic dependency injection of MappedDataModel subclasses
 * in Laravel controllers, similar to Form Request validation.
 *
 * ## Automatic Registration
 *
 * This provider is automatically registered via Laravel's package auto-discovery.
 * No manual configuration or registration needed!
 *
 * ## Usage in Controllers
 *
 * Simply type-hint your MappedDataModel subclass in controller methods:
 *
 * ```php
 * class UserController extends Controller
 * {
 *     public function register(UserRegistrationModel $model)
 *     {
 *         // $model is automatically instantiated with request data
 *         $user = User::create($model->toArray());
 *         return response()->json($user);
 *     }
 * }
 * ```
 *
 * The model will be automatically filled with the current request data.
 */
class LaravelMappedModelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the binding resolver
        $this->app->resolving(function ($object, Application $app): void {
            if ($object instanceof MappedDataModel && !$object->isMapped()) {
                $request = $app->make(Request::class);
                $object->fill($request->all());
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register route model binding for MappedDataModel subclasses
        $this->app->afterResolving(function ($resolved, Application $app): void {
            if (!$resolved instanceof MappedDataModel) {
                return;
            }

            // If already mapped, skip
            if ($resolved->isMapped()) {
                return;
            }

            // Get request data and fill the model
            $request = $app->make(Request::class);
            $resolved->fill($request->all());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
