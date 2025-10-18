<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for DTO integration.
 *
 * Registers the DTOValueResolver for automatic controller injection.
 *
 * Usage:
 * Add to config/app.php:
 * ```php
 * 'providers' => [
 *     // ...
 *     event4u\DataHelpers\Laravel\DTOServiceProvider::class,
 * ],
 * ```
 */
class DTOServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DTOValueResolver::class, function (Application $app) {
            return new DTOValueResolver(
                $app->make('request'),
                $app->make('validator')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom parameter resolver for Laravel 11+
        if (method_exists($this->app, 'resolving')) {
            // This will be called when resolving controller methods
            // Laravel 11+ uses attribute-based routing and parameter resolution
        }
    }
}

