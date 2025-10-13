<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap Laravel Application for E2E Tests
|--------------------------------------------------------------------------
|
| This file creates a full Laravel application instance for testing
| the Data Helpers package integration with Laravel.
|
*/

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;

// Check if app is already bootstrapped in this process
if (!isset($GLOBALS['__laravel_app_bootstrapped'])) {
    // Create Laravel application (use require instead of require_once to avoid true return)
    $app = require __DIR__ . '/bootstrap/app.php';

    // Set the application instance globally
    Illuminate\Container\Container::setInstance($app);

    // Set up facades
    Facade::setFacadeApplication($app);

    // Bootstrap the application
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Mark as bootstrapped
    $GLOBALS['__laravel_app_bootstrapped'] = $app;
} else {
    $app = $GLOBALS['__laravel_app_bootstrapped'];
}

return $app;
