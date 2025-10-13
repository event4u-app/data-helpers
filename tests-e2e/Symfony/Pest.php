<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration for Symfony E2E Tests
|--------------------------------------------------------------------------
*/

use event4u\DataHelpers\Config\ConfigHelper;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;

// Load helper functions from main tests directory
require_once __DIR__ . '/../../tests/helpers.php';

// Load .env file BEFORE creating kernel to ensure ENV variables are available during container compilation
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->loadEnv(__DIR__ . '/.env');

// Make kernel and container available in tests
// NOTE: The kernel is NOT booted here because there's no bootstrap.php in the root.
// The Symfony kernel should be booted in individual tests if needed.
// For now, we just ensure ENV variables are loaded and ConfigHelper is reset.
uses()->beforeEach(function (): void {
    // Reset ConfigHelper to ensure fresh configuration
    ConfigHelper::resetInstance();
})->in(__DIR__ . '/tests');

// Reset DataMapper settings before and after each test to ensure test isolation.
uses()->beforeEach(function (): void {
    MapperExceptions::reset();
});
uses()->afterEach(function (): void {
    MapperExceptions::reset();
});
