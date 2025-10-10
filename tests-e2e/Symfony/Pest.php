<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration for Symfony E2E Tests
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

// Load helper functions from main tests directory
require_once __DIR__ . '/../../tests/helpers.php';

// Bootstrap Symfony kernel
$kernel = require __DIR__ . '/bootstrap.php';
$kernel->boot();

// Make kernel and container available in tests
uses()->beforeEach(function() use ($kernel): void {
    $this->kernel = $kernel;
    $this->container = $kernel->getContainer();
})->in(__DIR__ . '/tests');

