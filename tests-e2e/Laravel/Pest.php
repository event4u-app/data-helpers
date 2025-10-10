<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration for Laravel E2E Tests
|--------------------------------------------------------------------------
*/

// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Bootstrap Laravel application
$GLOBALS['laravel_app'] = require __DIR__ . '/bootstrap.php';

// Define helper functions for Laravel tests
if (!function_exists('setupLaravelCache')) {
    function setupLaravelCache(): void
    {
        $app = new \Illuminate\Container\Container();
        $app->singleton('app', fn(): \Illuminate\Container\Container => $app);
        $app->singleton('cache', fn(): \Illuminate\Cache\Repository => new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore()));
        $app->singleton('cache.store', fn($app) => $app['cache']);
        // @phpstan-ignore-next-line - Container is compatible with Application for testing
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

// Make app available in Feature tests (E2E tests that need Laravel)
uses()->beforeEach(function(): void {
    $this->app = $GLOBALS['laravel_app'];
})->in(__DIR__ . '/tests/Feature');

// Include Unit and Integration tests (they should be framework-agnostic)
// These tests will run in the E2E environment but without Laravel-specific setup
uses()->in(__DIR__ . '/tests/Unit');
uses()->in(__DIR__ . '/tests/Integration');

