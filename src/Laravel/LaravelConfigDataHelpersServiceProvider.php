<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Laravel;

use event4u\DataHelpers\DataHelpersConfig;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

final class LaravelConfigDataHelpersServiceProvider extends ServiceProvider
{
    /** Register services. */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/laravel/data-helpers.php',
            'data-helpers'
        );
    }

    /** Bootstrap services. */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/laravel/data-helpers.php' => $this->app->configPath('data-helpers.php'),
            ], 'data-helpers-config');
        }

        // Initialize configuration from Laravel config
        /** @var Repository $configRepository */
        $configRepository = $this->app->make('config');
        /** @var array<string, mixed> $config */
        $config = $configRepository->get('data-helpers', []);
        DataHelpersConfig::initialize($config);
    }
}

