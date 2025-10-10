<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\ClassScopedCache;

/**
 * Show cache statistics.
 *
 * Usage:
 *   php scripts/cache-stats.php
 */

echo "📊  Cache Statistics\n";
echo "==================\n\n";

// Get global stats
$stats = CacheHelper::getStats();

echo "Global Cache:\n";
echo sprintf('  - Total Entries: %d%s', $stats['size'], PHP_EOL);
echo sprintf('  - Total Hits: %d%s', $stats['hits'], PHP_EOL);
echo sprintf('  - Total Misses: %d%s', $stats['misses'], PHP_EOL);
$hitRate = 0 < ($stats['hits'] + $stats['misses'])
    ? $stats['hits'] / ($stats['hits'] + $stats['misses'])
    : 0;
echo "  - Hit Rate: " . number_format($hitRate * 100, 2) . "%\n";
echo "\n";

// Show class-specific stats
$classes = [
    'event4u\\DataHelpers\\DataMapper\\Support\\TemplateParser',
    'event4u\\DataHelpers\\DataMapper\\Support\\FileLoader',
    'event4u\\DataHelpers\\DataMapper\\Template\\FilterEngine',
    'event4u\\DataHelpers\\DataMapper\\Support\\ValueTransformer',
    'event4u\\DataHelpers\\DataMapper\\Support\\EntityHelper',
    'event4u\\DataHelpers\\DataMapper',
];

echo "Class-Scoped Cache:\n";
echo "-------------------\n";

foreach ($classes as $class) {
    $classStats = ClassScopedCache::getClassStats($class);
    $shortName = substr($class, strrpos($class, '\\') + 1);

    if (0 < $classStats['count']) {
        echo "  {$shortName}:\n";
        echo sprintf('    - Entries: %d%s', $classStats['count'], PHP_EOL);
        echo "    - Keys: " . implode(', ', array_slice($classStats['keys'], 0, 3));

        if (3 < $classStats['count']) {
            echo " ... (+" . ($classStats['count'] - 3) . " more)";
        }

        echo "\n";
    }
}

echo "\n";
echo "Memory Usage:\n";
echo "  - Current: " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "  - Peak: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "\n";

