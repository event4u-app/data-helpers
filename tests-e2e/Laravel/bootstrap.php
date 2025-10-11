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

// Create Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';

// Set the application instance globally
Illuminate\Container\Container::setInstance($app);

// Set up facades
Facade::setFacadeApplication($app);

// Bootstrap the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

return $app;
