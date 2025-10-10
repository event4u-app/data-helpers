<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\DataMapper\Support\TemplateParser;

echo "🔧 Configuration Example\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// 1. Default Configuration (No Setup Required)
// ============================================================================

echo "1️⃣  Default Configuration\n";
echo str_repeat('-', 80) . "\n";

echo "The package works out of the box with sensible defaults:\n\n";

echo "Default Cache Max Entries: " . DataHelpersConfig::getCacheMaxEntries() . "\n";
$defaultTtl = DataHelpersConfig::get('cache.default_ttl');
echo "Default Cache TTL: " . (null !== $defaultTtl ? (string)$defaultTtl : 'null (forever)') . "\n";
echo "Performance Mode: " . DataHelpersConfig::getPerformanceMode() . "\n";
echo "Configuration Source: " . DataHelpersConfig::getSource() . "\n\n";

// ============================================================================
// 2. Custom Configuration (Plain PHP)
// ============================================================================

echo "2️⃣  Custom Configuration (Plain PHP)\n";
echo str_repeat('-', 80) . "\n";

// Set custom configuration
$customConfig = [
    'cache' => [
        'max_entries' => 500,  // Reduce cache size
        'default_ttl' => 1800, // 30 minutes
    ],
];

DataHelpersConfig::initialize($customConfig);

echo "Custom configuration applied:\n";
echo "Cache Max Entries: " . DataHelpersConfig::getCacheMaxEntries() . "\n";
$customTtl = DataHelpersConfig::get('cache.default_ttl');
echo "Cache TTL: " . (null !== $customTtl ? (string)$customTtl : 'null') . " seconds\n";
echo "Configuration Source: " . DataHelpersConfig::getSource() . "\n\n";

// ============================================================================
// 3. Configuration Impact on Caching
// ============================================================================

echo "3️⃣  Configuration Impact on Caching\n";
echo str_repeat('-', 80) . "\n";

// Clear cache first
CacheHelper::flush();

// Create some cache entries
$template1 = ['name' => '{{ user.name | upper }}'];
$template2 = ['email' => '{{ user.email | lower }}'];
$template3 = ['age' => '{{ user.age | default:18 }}'];

TemplateParser::parseMapping($template1);
TemplateParser::parseMapping($template2);
TemplateParser::parseMapping($template3);

// Check cache stats
$stats = CacheHelper::getStats();
echo "Cache entries created: " . $stats['size'] . "\n";
echo "Max entries allowed: " . DataHelpersConfig::getCacheMaxEntries() . "\n\n";

// ============================================================================
// 4. Framework-Specific Configuration Examples
// ============================================================================

echo "4️⃣  Framework-Specific Configuration\n";
echo str_repeat('-', 80) . "\n\n";

echo "📖 Laravel Configuration:\n";
echo "   1. Publish config:\n";
echo "      php artisan vendor:publish --tag=data-helpers-config\n\n";
echo "   2. Edit config/data-helpers.php:\n";
echo "      return [\n";
echo "          'cache' => [\n";
echo "              'max_entries' => env('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000),\n";
echo "              'default_ttl' => env('DATA_HELPERS_CACHE_TTL', 3600),\n";
echo "          ],\n";
echo "      ];\n\n";
echo "   3. Add to .env:\n";
echo "      DATA_HELPERS_CACHE_MAX_ENTRIES=1000\n";
echo "      DATA_HELPERS_CACHE_TTL=3600\n\n";

echo "📖 Symfony Configuration:\n";
echo "   1. Create config/packages/data_helpers.yaml:\n";
echo "      data_helpers:\n";
echo "        cache:\n";
echo "          max_entries: '%env(int:DATA_HELPERS_CACHE_MAX_ENTRIES)%'\n";
echo "          default_ttl: '%env(int:DATA_HELPERS_CACHE_TTL)%'\n\n";
echo "   2. Add to .env:\n";
echo "      DATA_HELPERS_CACHE_MAX_ENTRIES=1000\n";
echo "      DATA_HELPERS_CACHE_TTL=3600\n\n";

echo "📖 Plain PHP Configuration:\n";
echo "   1. Create config/data-helpers.php:\n";
echo "      return [\n";
echo "          'cache' => [\n";
echo "              'max_entries' => 1000,\n";
echo "              'default_ttl' => 3600,\n";
echo "          ],\n";
echo "      ];\n\n";
echo "   2. Load in bootstrap.php:\n";
echo "      \$config = require __DIR__ . '/config/data-helpers.php';\n";
echo "      event4u\\DataHelpers\\DataHelpersConfig::initialize(\$config);\n\n";

// ============================================================================
// 5. Cache Management Commands
// ============================================================================

echo "5️⃣  Cache Management Commands\n";
echo str_repeat('-', 80) . "\n\n";

echo "Clear all caches:\n";
echo "   composer cache:clear\n\n";

echo "Show cache statistics:\n";
echo "   composer cache:stats\n\n";

echo "Clear cache for specific class:\n";
echo "   php scripts/cache-clear.php 'event4u\\DataHelpers\\DataMapper\\Support\\TemplateParser'\n\n";

// ============================================================================
// 6. Configuration Best Practices
// ============================================================================

echo "6️⃣  Configuration Best Practices\n";
echo str_repeat('-', 80) . "\n\n";

echo "✅ Use environment variables for different environments:\n";
echo "   - Development: Higher cache limits, shorter TTL\n";
echo "   - Production: Optimized cache limits, longer TTL\n\n";

echo "✅ Monitor cache hit rates:\n";
echo "   - Use composer cache:stats to check performance\n";
echo "   - Adjust max_entries based on memory usage\n\n";

echo "✅ Clear cache after deployments:\n";
echo "   - Run composer cache:clear in deployment scripts\n";
echo "   - Ensures fresh cache with new code\n\n";

echo "✅ Use hash-validated cache for templates:\n";
echo "   - Automatically invalidates when templates change\n";
echo "   - No manual cache clearing needed\n\n";

// ============================================================================
// Summary
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "📖 For detailed configuration options, see: docs/configuration.md\n";
echo str_repeat('=', 80) . "\n";

