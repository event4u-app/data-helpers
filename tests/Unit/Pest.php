<?php

use event4u\DataHelpers\DataMapper\MapperExceptions;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

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

/*
|--------------------------------------------------------------------------
| Test Hooks
|--------------------------------------------------------------------------
|
| Reset DataMapper settings before and after each test to ensure test isolation.
|
*/

beforeEach(function(): void {
    MapperExceptions::reset();
});
afterEach(function(): void {
    MapperExceptions::reset();
});
