<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap Laravel Application for E2E Tests
|--------------------------------------------------------------------------
|
| This file creates a minimal Laravel application instance for testing
| the Data Helpers package integration with Laravel.
|
*/

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

// Create application container with Laravel Application methods
$app = new class extends Container {
    public function runningInConsole(): bool
    {
        return true;
    }

    public function configPath(string $path = ''): string
    {
        return __DIR__ . '/config' . ($path ? '/' . $path : '');
    }
};
Container::setInstance($app);

// Bind application instance
$app->instance('app', $app);
$app->instance(Container::class, $app);

// Bind config
$config = new ConfigRepository([
    'data-helpers' => require __DIR__ . '/../../config/data-helpers.php',
]);
$app->instance('config', $config);

// Bind events
$app->singleton('events', fn() => new Dispatcher($app));

// Bind filesystem
$app->singleton('files', fn() => new Filesystem());

// Set up facades
Facade::setFacadeApplication($app);

// Register Data Helpers Service Provider
$provider = new \event4u\DataHelpers\Laravel\DataHelpersServiceProvider($app);
$provider->register();
$provider->boot();

return $app;
