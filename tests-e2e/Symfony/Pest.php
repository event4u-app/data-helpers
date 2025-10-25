<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration for Symfony E2E Tests
|--------------------------------------------------------------------------
*/

use event4u\DataHelpers\DataMapper\MapperExceptions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// Symfony loads .env file automatically in tests/bootstrap.php

// Use KernelTestCase for Feature tests to enable bootKernel() and getContainer()
uses(KernelTestCase::class)
    ->beforeEach(function (): void {
        self::bootKernel();
        MapperExceptions::reset();
    })
    ->in('tests/Feature');

// Reset MapperExceptions before and after each test to ensure test isolation
uses()->beforeEach(function (): void {
    MapperExceptions::reset();
})->in('tests/Fixtures', 'tests/Integration', 'tests/Unit');

uses()->afterEach(function (): void {
    MapperExceptions::reset();
})->in('tests/Fixtures', 'tests/Integration', 'tests/Unit', 'tests/Unit', 'tests/Feature');
