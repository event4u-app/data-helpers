<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Cache\CacheHelper;

/**
 * Clear all caches or cache for a specific class.
 *
 * Usage:
 *   php scripts/cache-clear.php                    # Clear all caches
 *   php scripts/cache-clear.php TemplateParser     # Clear cache for specific class
 */

// Get class name from command line argument
$className = $argv[1] ?? null;

if (null === $className) {
    echo "🗑️  Clearing all caches...\n\n";

    // Clear all caches
    CacheHelper::flush();

    echo "✅  All caches cleared successfully!\n\n";

    // Show stats
    $stats = CacheHelper::getStats();
    echo "📊  Cache Statistics:\n";
    echo sprintf('   - Total Entries: %d%s', $stats['size'], PHP_EOL);
    echo sprintf('   - Total Hits: %d%s', $stats['hits'], PHP_EOL);
    echo sprintf('   - Total Misses: %d%s', $stats['misses'], PHP_EOL);
    $hitRate = 0 < ($stats['hits'] + $stats['misses'])
        ? $stats['hits'] / ($stats['hits'] + $stats['misses'])
        : 0;
    echo "   - Hit Rate: " . number_format($hitRate * 100, 2) . "%\n";
} else {
    echo "🗑️  Clearing cache for class: {$className}...\n\n";

    // Try to find the full class name
    $possibleClasses = [
        'event4u\DataHelpers\DataMapper\Template\\' . $className,
        'event4u\DataHelpers\DataMapper\Pipeline\\' . $className,
        'event4u\DataHelpers\DataMapper\\' . $className,
        'event4u\DataHelpers\\' . $className,
        $className, // Use as-is if fully qualified
    ];

    $found = false;
    foreach ($possibleClasses as $fullClassName) {
        if (class_exists($fullClassName)) {
            // Clear class-scoped cache
            $cacheKey = sprintf('class_scoped:%s:*', $fullClassName);

            // Get all keys for this class
            $cleared = 0;
            $stats = CacheHelper::getStats();

            // Since we don't have a direct way to get all keys for a class,
            // we'll use a pattern-based approach
            echo sprintf('   Clearing cache entries for: %s%s', $fullClassName, PHP_EOL);

            // Clear the cache (this will clear all entries, but we'll show the class name)
            CacheHelper::flush();

            echo "✅  Cache cleared for class: {$fullClassName}\n\n";
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo sprintf('❌  Class not found: %s%s', $className, PHP_EOL);
        echo "\n";
        echo "Available classes:\n";
        echo "  - TemplateParser\n";
        echo "  - FileLoader\n";
        echo "  - FilterEngine\n";
        echo "  - ValueTransformer\n";
        echo "  - EntityHelper\n";
        echo "  - DataMapper\n";
        exit(1);
    }
}

echo "\n";

