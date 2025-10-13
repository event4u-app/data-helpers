<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration for Laravel E2E Tests
|--------------------------------------------------------------------------
*/

use event4u\DataHelpers\DataMapper\MapperExceptions;

// Bootstrap Laravel application (Laravel will load .env file automatically)
require __DIR__ . '/bootstrap.php';

// Reset MapperExceptions before and after each test to ensure test isolation
uses()->beforeEach(function (): void {
    MapperExceptions::reset();
})->in('tests/Fixtures', 'tests/Integration', 'tests/Unit', 'tests/Feature');

uses()->afterEach(function (): void {
    MapperExceptions::reset();
})->in('tests/Fixtures', 'tests/Integration', 'tests/Unit', 'tests/Feature');
