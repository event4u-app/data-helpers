<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| This file bootstraps the Laravel application for testing.
| It loads the Laravel application and makes all facades and helpers available.
|
*/

// Bootstrap Laravel application (from parent directory)
$app = require_once __DIR__ . '/../bootstrap.php';

// Helper functions for test paths
if (!function_exists('tests_path')) {
    function tests_path(string $path = ''): string
    {
        return __DIR__ . '/' . ltrim($path, '/\\');
    }
}

if (!function_exists('config_path_tests')) {
    function config_path_tests(string $path = ''): string
    {
        return __DIR__ . '/../config/' . ltrim($path, '/\\');
    }
}

return $app;

