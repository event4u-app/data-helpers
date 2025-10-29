#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cache Clearing Script for Data Helpers.
 *
 * This script clears all persistent cache entries.
 *
 * Usage:
 *   php bin/clear-cache.php [options]
 *
 * Options:
 *   -v, --verbose     Show detailed output
 *   -h, --help        Show this help message
 *
 * Examples:
 *   php bin/clear-cache.php
 *   php bin/clear-cache.php -v
 *
 * Exit codes:
 *   0 - Success
 *   1 - Errors occurred
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

use event4u\DataHelpers\Support\Cache\CacheManager;

// Parse command line arguments
$verbose = false;
$help = false;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];

    switch ($arg) {
        case '-v':
        case '--verbose':
            $verbose = true;
            break;

        case '-h':
        case '--help':
            $help = true;
            break;

        default:
            fwrite(STDERR, "Unknown option: {$arg}\n");
            fwrite(STDERR, "Use --help for usage information.\n");
            exit(1);
    }
}

// Show help if requested
if ($help) {
    echo <<<'HELP'
Data Helpers - Cache Clearing Script

This script clears all persistent cache entries.

Usage:
  php bin/clear-cache.php [options]

Options:
  -v, --verbose     Show detailed output
  -h, --help        Show this help message

Examples:
  php bin/clear-cache.php
  php bin/clear-cache.php -v

Exit codes:
  0 - Success
  1 - Errors occurred

HELP;
    exit(0);
}

// Execute cache clearing
try {
    if ($verbose) {
        echo "\n";
        echo "\033[0;34m" . str_repeat('━', 60) . "\033[0m\n";
        echo "\033[0;34m  Data Helpers - Cache Clearing\033[0m\n";
        echo "\033[0;34m" . str_repeat('━', 60) . "\033[0m\n";
        echo "\n";
    }

    $cleared = CacheManager::clear();

    if ($cleared) {
        if ($verbose) {
            echo "\033[0;32m✅  Cache cleared successfully\033[0m\n";
        } else {
            echo "Cache cleared.\n";
        }

        if ($verbose) {
            echo "\n";
        }

        exit(0);
    } else {
        if ($verbose) {
            echo "\033[0;33m⚠️  Cache was already empty or could not be cleared\033[0m\n";
            echo "\n";
        } else {
            echo "Cache was already empty.\n";
        }

        exit(0);
    }
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("Error clearing cache: %s\n", $e->getMessage()));

    if ($verbose) {
        fwrite(STDERR, sprintf("  in %s:%d\n", $e->getFile(), $e->getLine()));
        fwrite(STDERR, "\nStack trace:\n");
        fwrite(STDERR, $e->getTraceAsString() . "\n");
    }

    exit(1);
}

