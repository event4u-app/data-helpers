<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File.
 *
 * This file is executed before running tests and performs:
 * - Cache warming for all SimpleDto classes
 * - Environment setup
 *
 * Phase 11a: Persistent cache is warmed up before tests to ensure
 * maximum performance during test execution.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Console\WarmCacheCommand;

// Warm up cache for test DTOs
try {
    echo "Warming up cache for test DTOs...\n";
    $command = new WarmCacheCommand();

    // Warm cache for all test DTOs (silent mode)
    $directories = [
        __DIR__ . '/Utils/SimpleDtos',
        __DIR__ . '/Utils/Dtos',
    ];

    // Execute cache warming silently
    $command->execute($directories, verbose: false, validate: false);
} catch (Throwable) {
    // Silently fail - cache warming is optional
    // Tests will still work, just slower on first run
}
