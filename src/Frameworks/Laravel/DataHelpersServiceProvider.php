<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Frameworks\Laravel;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Frameworks\Laravel\Commands\DtoTypeScriptCommand;
use event4u\DataHelpers\Frameworks\Laravel\Commands\MakeDtoCommand;
use event4u\DataHelpers\Frameworks\Laravel\Commands\MigrateSpatieCommand;
use event4u\DataHelpers\MappedDataModel;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for Data Helpers.
 *
 * This provider handles:
 * - Configuration loading and publishing
 * - Automatic MappedDataModel dependency injection
 *
 * ## Automatic Registration
 *
 * This provider is automatically registered via Laravel's package auto-discovery.
 * No manual configuration or registration needed!
 *
 * ## Configuration Publishing
 *
 * Publish the configuration file:
 *
 * ```bash
 * php artisan vendor:publish --tag=data-helpers-config
 * ```
 *
 * ## MappedDataModel Auto-Binding
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
 */
final class DataHelpersServiceProvider extends ServiceProvider
{
    /** Register services. */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/data-helpers.php',
            'data-helpers'
        );

        // Register the binding resolver for automatic MappedDataModel filling
        $this->app->resolving(function($object, Application $app): void {
            if ($object instanceof MappedDataModel && !$object->isMapped()) {
                /** @var Request $request */
                $request = $app->make(Request::class);
                $object->fill($request->all());
            }
        });
    }

    /** Bootstrap services. */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/data-helpers.php' => $this->app->configPath('data-helpers.php'),
            ], 'data-helpers-config');

            // Register commands
            $this->commands([
                MakeDtoCommand::class,
                DtoTypeScriptCommand::class,
                MigrateSpatieCommand::class,
            ]);
        }

        // Initialize configuration from Laravel config
        /** @var Repository $configRepository */
        $configRepository = $this->app->make('config');
        /** @var array<string, mixed> $config */
        $config = $configRepository->get('data-helpers', []);
        DataHelpersConfig::initialize($config);
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
