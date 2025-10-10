<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

if (!function_exists('setupLaravelCache')) {
    function setupLaravelCache(): void
    {
        $app = new Container();
        $app->singleton('app', fn(): Container => $app);

        // Setup cache
        $app->singleton('cache', fn(): Repository => new Repository(new ArrayStore()));

        // Setup cache store
        $app->singleton('cache.store', fn($app) => $app['cache']);

        // Set facade application
        Facade::setFacadeApplication($app);

        // Make app() function work
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

