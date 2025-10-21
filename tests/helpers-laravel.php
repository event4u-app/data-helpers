<?php

/**
 * Helper functions for Laravel-specific tests.
 * This file is loaded by various Pest.php files to ensure the functions are available.
 */

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

if (!function_exists('setupLaravelCache')) {
    function setupLaravelCache(): void
    {
        $app = new Container();
        $app->singleton('app', fn(): Container => $app);
        $app->singleton(
            'cache',
            fn(): Repository => new Repository(new ArrayStore())
        );
        $app->singleton('cache.store', fn($app) => $app['cache']);
        /** @phpstan-ignore-next-line unknown */
        Facade::setFacadeApplication($app);
        Container::setInstance($app);
    }
}

if (!function_exists('teardownLaravelCache')) {
    function teardownLaravelCache(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
    }
}
