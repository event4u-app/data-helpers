<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\ClassScopedCache;
use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\TemplateParser;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;
use event4u\DataHelpers\DataMapper\Template\FilterEngine;
use event4u\DataHelpers\Support\FileLoader;

echo "=== Performance & Caching Examples ===\n\n";

// ============================================================================
// 1. Configuration for Performance
// ============================================================================

echo "1. Configuration for Performance\n";
echo str_repeat('-', 50) . "\n";

// Initialize with performance settings
DataHelpersConfig::initialize([
    'cache' => [
        'driver' => 'memory',
        'max_entries' => 2000,
    ],
    'performance_mode' => 'fast', // Use fast mode for better performance
]);

echo "✓ Configured with fast mode and 2000 cache entries\n";
echo "  - Fast mode: ~2x faster parsing\n";
echo "  - Cache: Stores up to 2000 parsed expressions\n\n";

// ============================================================================
// 2. Template Expression Caching
// ============================================================================

echo "2. Template Expression Caching\n";
echo str_repeat('-', 50) . "\n";

$template = [
    'name' => '{{ user.name | trim | upper }}',
    'email' => '{{ user.email | lower }}',
    'tags' => '{{ user.tags | join:", " }}',
];

$sources = [
    'user' => [
        'name' => '  John Doe  ',
        'email' => 'JOHN@EXAMPLE.COM',
        'tags' => ['php', 'laravel', 'symfony'],
    ],
];

// First call - parses and caches
$start = microtime(true);
$result1 = DataMapper::mapFromTemplate($template, $sources);
$time1 = (microtime(true) - $start) * 1000;

// Second call - uses cache
$start = microtime(true);
$result2 = DataMapper::mapFromTemplate($template, $sources);
$time2 = (microtime(true) - $start) * 1000;

echo "First call (parse + cache): " . number_format($time1, 3) . " ms\n";
echo "Second call (from cache):   " . number_format($time2, 3) . " ms\n";
echo "Speedup: " . number_format($time1 / $time2, 2) . "x faster\n\n";

print_r($result1);
echo "\n";

// Get cache statistics
$stats = ExpressionParser::getCacheStats();
echo "Cache statistics:\n";
echo sprintf('  - Size: %d / %d%s', $stats['size'], $stats['max_size'], PHP_EOL);
echo "  - Usage: " . number_format($stats['usage_percentage'], 1) . "%\n\n";

// ============================================================================
// 3. Template Mapping Cache (ClassScopedCache)
// ============================================================================

echo "3. Template Mapping Cache\n";
echo str_repeat('-', 50) . "\n";

$mapping1 = [
    'fullName' => '{{ user.name }}',
    'contactEmail' => '{{ user.email }}',
];

$mapping2 = [
    'customerName' => '{{ customer.name }}',
    'customerEmail' => '{{ customer.email }}',
];

// Parse both mappings (will be cached)
$parsed1 = TemplateParser::parseMapping($mapping1);
$parsed2 = TemplateParser::parseMapping($mapping2);

echo "Parsed mapping 1:\n";
print_r($parsed1);
echo "\n";

echo "Parsed mapping 2:\n";
print_r($parsed2);
echo "\n";

// Get cache statistics for TemplateParser
$stats = ClassScopedCache::getClassStats(TemplateParser::class);
echo "TemplateParser cache statistics:\n";
echo sprintf('  - Cached mappings: %d%s', $stats['count'], PHP_EOL);
echo "  - Max entries: 100 (per class)\n";
echo "  - LRU eviction: enabled\n\n";

// ============================================================================
// 4. File Content Caching
// ============================================================================

echo "4. File Content Caching\n";
echo str_repeat('-', 50) . "\n";

// Create a temporary JSON file
$tempFile = sys_get_temp_dir() . '/test_' . bin2hex(random_bytes(8)) . '.json';
file_put_contents($tempFile, json_encode([
    'name' => 'Test User',
    'email' => 'test@example.com',
]));

// First load - reads from disk and caches
$start = microtime(true);
$data1 = FileLoader::loadAsArray($tempFile);
$time1 = (microtime(true) - $start) * 1000;

// Second load - uses cache (no disk I/O)
$start = microtime(true);
$data2 = FileLoader::loadAsArray($tempFile);
$time2 = (microtime(true) - $start) * 1000;

echo "First load (disk + cache): " . number_format($time1, 3) . " ms\n";
echo "Second load (from cache):  " . number_format($time2, 3) . " ms\n";
echo "Speedup: " . number_format($time1 / $time2, 2) . "x faster\n\n";

// Clean up
unlink($tempFile);

// ============================================================================
// 5. Filter/Transformer Instance Caching
// ============================================================================

echo "5. Filter/Transformer Instance Caching\n";
echo str_repeat('-', 50) . "\n";

// Apply filters multiple times
$values = ['  hello  ', '  WORLD  ', '  test  '];

echo "Applying 'trim' filter to multiple values:\n";
foreach ($values as $value) {
    $result = FilterEngine::apply($value, ['trim']);
    $resultStr = is_string($result) ? $result : (string)$result;
    echo "  '" . $value . "' -> '" . $resultStr . "'\n";
}

echo "\n✓ Transformer instances are reused (not created each time)\n";
echo "  - First call: creates instance and caches it\n";
echo "  - Subsequent calls: reuse cached instance\n\n";

// ============================================================================
// 6. Performance Mode Switching
// ============================================================================

echo "6. Performance Mode Switching\n";
echo str_repeat('-', 50) . "\n";

// Fast mode (default)
FilterEngine::useFastSplit(true);
echo "Fast mode enabled (default)\n";
echo "  - ~2x faster parsing\n";
echo "  - No escape sequence handling\n";
echo "  - Use for: {{ value | trim }}, {{ tags | join:\", \" }}\n\n";

// Safe mode (for escape sequences)
FilterEngine::useFastSplit(false);
echo "Safe mode enabled\n";
echo "  - Full escape sequence handling\n";
echo "  - Use for: {{ value | default:\"Line1\\nLine2\" }}\n";
echo "  - Processes: \\n, \\t, \\\", \\\\\n\n";

// Switch back to fast mode
FilterEngine::useFastSplit(true);
echo "✓ Switched back to fast mode\n\n";

// ============================================================================
// 7. Cache Management
// ============================================================================

echo "7. Cache Management\n";
echo str_repeat('-', 50) . "\n";

// Get statistics before clearing
$stats = ExpressionParser::getCacheStats();
echo "Before clearing:\n";
echo sprintf('  - ExpressionParser cache size: %d%s', $stats['size'], PHP_EOL);

$stats = ClassScopedCache::getClassStats(TemplateParser::class);
echo '  - TemplateParser cache size: ' . $stats['count'] . "\n\n";

// Clear specific caches
ExpressionParser::clearCache();
ClassScopedCache::clearClass(TemplateParser::class);

echo "✓ Cleared ExpressionParser and TemplateParser caches\n\n";

// Clear all caches
CacheHelper::clear();
echo "✓ Cleared all caches\n\n";

// ============================================================================
// 8. LRU Eviction Example
// ============================================================================

echo "8. LRU Eviction Example\n";
echo str_repeat('-', 50) . "\n";

// Create many mappings to trigger eviction
echo "Creating 105 different mappings (max is 100)...\n";

for ($i = 1; 105 >= $i; $i++) {
    $mapping = [
        'field' . $i => sprintf('{{ user.field%d }}', $i),
    ];
    TemplateParser::parseMapping($mapping);
}

$stats = ClassScopedCache::getClassStats(TemplateParser::class);
echo "After creating 105 mappings:\n";
echo '  - Cache size: ' . $stats['count'] . " (max: 100)\n";
echo "  - Oldest 5 entries were evicted (LRU)\n\n";

// ============================================================================
// 9. Performance Best Practices
// ============================================================================

echo "9. Performance Best Practices\n";
echo str_repeat('-', 50) . "\n";

echo "✓ Use fast mode (default) for 90%+ of use cases\n";
echo "✓ Increase cache size for large applications (2000-5000)\n";
echo "✓ Reuse mappings/templates instead of creating new ones\n";
echo "✓ Use FileLoader for repeated file access\n";
echo "✓ Batch operations with mapMany() instead of individual map() calls\n";
echo "✓ Monitor cache statistics to optimize cache size\n";
echo "✓ Clear caches periodically in long-running processes\n\n";

// ============================================================================
// 10. Memory Usage Estimation
// ============================================================================

echo "10. Memory Usage Estimation\n";
echo str_repeat('-', 50) . "\n";

echo "Typical memory usage for caching:\n";
echo "  - 1000 template expressions: ~500 KB\n";
echo "  - 100 template mappings: ~100-200 KB\n";
echo "  - 50 loaded files: ~50-500 KB (depends on file size)\n";
echo "  - 20 transformer instances: ~20-40 KB\n";
echo "  - 500 string operations: ~50-100 KB\n";
echo "  - 100 reflection entries: ~50 KB\n";
echo "  - Total: ~1-2 MB for typical applications\n\n";

echo "=== End of Performance & Caching Examples ===\n";

