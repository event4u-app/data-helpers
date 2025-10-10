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

// Load helper functions
require_once __DIR__ . '/tests/helpers.php';

// Bootstrap Laravel application
$GLOBALS['laravel_app'] = require __DIR__ . '/bootstrap.php';

// Make app available in Feature tests (E2E tests that need Laravel)
uses()->beforeEach(function(): void {
    $this->app = $GLOBALS['laravel_app'];
})->in(__DIR__ . '/tests/Feature');

// Include Unit and Integration tests (they should be framework-agnostic)
// These tests will run in the E2E environment but without Laravel-specific setup
uses()->in(__DIR__ . '/tests/Unit');
uses()->in(__DIR__ . '/tests/Integration');

