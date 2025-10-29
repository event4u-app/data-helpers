#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cache Warming Script for Data Helpers SimpleDtos.
 *
 * This script pre-generates persistent cache entries for all SimpleDto classes
 * found in the specified directories. This is useful for:
 *
 * - Production deployments (warm cache before serving traffic)
 * - CI/CD pipelines (warm cache before running tests)
 * - Development (speed up first request)
 *
 * Usage:
 *   php bin/warm-cache.php [options] [directories...]
 *
 * Options:
 *   -v, --verbose     Show detailed output
 *   -q, --quiet       Suppress all output except errors
 *   --no-validate     Skip cache validation after warming
 *   -h, --help        Show this help message
 *
 * Examples:
 *   php bin/warm-cache.php src/Dtos
 *   php bin/warm-cache.php -v src/Dtos tests/Fixtures
 *   php bin/warm-cache.php --no-validate src/Dtos
 *
 * Exit codes:
 *   0 - Success
 *   1 - Errors occurred during cache warming
 */

// Find autoloader
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../../../autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    fwrite(STDERR, "Error: Could not find Composer autoloader.\n");
    fwrite(STDERR, "Please run 'composer install' first.\n");
    exit(1);
}

use event4u\DataHelpers\Console\WarmCacheCommand;

// Parse command line arguments
$options = [
    'verbose' => false,
    'quiet' => false,
    'validate' => true,
    'help' => false,
];

$directories = [];

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];

    switch ($arg) {
        case '-v':
        case '--verbose':
            $options['verbose'] = true;
            break;

        case '-q':
        case '--quiet':
            $options['quiet'] = true;
            break;

        case '--no-validate':
            $options['validate'] = false;
            break;

        case '-h':
        case '--help':
            $options['help'] = true;
            break;

        default:
            if (str_starts_with($arg, '-')) {
                fwrite(STDERR, "Unknown option: {$arg}\n");
                fwrite(STDERR, "Use --help for usage information.\n");
                exit(1);
            }
            $directories[] = $arg;
            break;
    }
}

// Show help if requested
if ($options['help']) {
    echo <<<'HELP'
Data Helpers - Cache Warming Script

This script pre-generates persistent cache entries for all SimpleDto classes
found in the specified directories.

Usage:
  php bin/warm-cache.php [options] [directories...]

Options:
  -v, --verbose     Show detailed output
  -q, --quiet       Suppress all output except errors
  --no-validate     Skip cache validation after warming
  -h, --help        Show this help message

Examples:
  php bin/warm-cache.php src/Dtos
  php bin/warm-cache.php -v src/Dtos tests/Fixtures
  php bin/warm-cache.php --no-validate src/Dtos

Exit codes:
  0 - Success
  1 - Errors occurred during cache warming

HELP;
    exit(0);
}

// Validate directories
if (empty($directories)) {
    fwrite(STDERR, "Error: No directories specified.\n");
    fwrite(STDERR, "Usage: php bin/warm-cache.php [options] [directories...]\n");
    fwrite(STDERR, "Use --help for more information.\n");
    exit(1);
}

// Execute command
try {
    $command = new WarmCacheCommand();
    $exitCode = $command->execute(
        $directories,
        $options['verbose'] && !$options['quiet'],
        $options['validate']
    );

    exit($exitCode);
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("Fatal error: %s\n", $e->getMessage()));
    fwrite(STDERR, sprintf("  in %s:%d\n", $e->getFile(), $e->getLine()));

    if ($options['verbose']) {
        fwrite(STDERR, "\nStack trace:\n");
        fwrite(STDERR, $e->getTraceAsString() . "\n");
    }

    exit(1);
}

