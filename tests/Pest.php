<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn() => $this->toBe(1));

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

/*
|--------------------------------------------------------------------------
| Setup Laravel Cache for Testing
|--------------------------------------------------------------------------
|
| Setup a minimal Laravel environment for cache testing.
|
*/
function setupLaravelCache(): void
{
    $app = new Container();
    $app->singleton('app', fn(): Container => $app);

    // Setup cache
    // @phpstan-ignore-next-line - Test helper for Laravel cache
    $app->singleton('cache', fn(): Repository => // @phpstan-ignore-next-line - Test helper
    new Repository(new ArrayStore())
    );

    // Setup cache store
    $app->singleton('cache.store', fn($app) => $app['cache']);

    // Set facade application
    // @phpstan-ignore-next-line - Test helper for Laravel facades
    Facade::setFacadeApplication($app);

    // Make app() function work
    Container::setInstance($app);
}

function teardownLaravelCache(): void
{
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Container::setInstance(null);
}
