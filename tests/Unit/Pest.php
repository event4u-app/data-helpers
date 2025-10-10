<?php

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Define Laravel helper functions (will only work if Laravel is available)
if (!function_exists('setupLaravelCache')) {
    function setupLaravelCache(): void
    {
        $app = new \Illuminate\Container\Container();
        $app->singleton('app', fn(): \Illuminate\Container\Container => $app);
        $app->singleton('cache', fn(): \Illuminate\Cache\Repository => new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore()));
        $app->singleton('cache.store', fn($app) => $app['cache']);
        \Illuminate\Support\Facades\Facade::setFacadeApplication($app);
        \Illuminate\Container\Container::setInstance($app);
    }
}

if (!function_exists('teardownLaravelCache')) {
    function teardownLaravelCache(): void
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        \Illuminate\Container\Container::setInstance(null);
    }
}
