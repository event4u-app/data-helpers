<?php

declare(strict_types=1);

/**
 * Bootstrap file for examples
 *
 * This file finds and loads the Composer autoloader from the repository root,
 * regardless of where the example file is located in the examples directory.
 */

// Find the vendor/autoload.php file by traversing up the directory tree
$dir = __DIR__;
$maxLevels = 5; // Maximum levels to traverse up
$level = 0;

while ($level < $maxLevels) {
    $autoloadPath = $dir . '/vendor/autoload.php';
    
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
        return;
    }
    
    // Go up one level
    $parentDir = dirname($dir);
    
    // Stop if we've reached the root
    if ($parentDir === $dir) {
        break;
    }
    
    $dir = $parentDir;
    $level++;
}

// If we get here, we couldn't find the autoloader
throw new RuntimeException(
    'Could not find vendor/autoload.php. Please run "composer install" in the repository root.'
);
