<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\Cache\HashValidatedCache;
use event4u\DataHelpers\DataMapper;

echo "=== Hash-Validated Cache Demo ===\n\n";

// Example 1: Template caching with automatic invalidation
echo "1. Template Caching with Auto-Invalidation\n";
echo "-------------------------------------------\n";

$template1 = [
    'name' => '{{ user.name | upper }}',
    'email' => '{{ user.email | lower }}',
];

$sources = [
    'user' => [
        'name' => 'John Doe',
        'email' => 'JOHN@EXAMPLE.COM',
    ],
];

// First call - cache miss, will calculate
$result1 = HashValidatedCache::remember(
    'TemplateDemo',
    'user_template',
    $template1,
    fn(): array => DataMapper::mapFromTemplate($template1, $sources)
);

echo "First call (cache miss):\n";
echo json_encode($result1, JSON_PRETTY_PRINT) . "\n\n";

// Second call with same template - cache hit
$result2 = HashValidatedCache::remember(
    'TemplateDemo',
    'user_template',
    $template1,
    fn(): array => DataMapper::mapFromTemplate($template1, $sources)
);

echo "Second call (cache hit - same template):\n";
echo json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";

// Third call with modified template - cache invalidated, recalculated
$template2 = [
    'name' => '{{ user.name | lower }}',  // Changed: upper -> lower
    'email' => '{{ user.email | lower }}',
];

$result3 = HashValidatedCache::remember(
    'TemplateDemo',
    'user_template',
    $template2,  // Different template!
    fn(): array => DataMapper::mapFromTemplate($template2, $sources)
);

echo "Third call (cache invalidated - template changed):\n";
echo json_encode($result3, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: Configuration caching
echo "2. Configuration Caching\n";
echo "------------------------\n";

$config1 = [
    'max_entries' => 100,
    'ttl' => 3600,
    'driver' => 'memory',
];

$processedConfig1 = HashValidatedCache::remember(
    'ConfigDemo',
    'app_config',
    $config1,
    function() use ($config1): array {
        echo "  → Processing config (expensive operation)...\n";
        // Simulate expensive config processing
        return array_merge($config1, ['processed' => true, 'timestamp' => time()]);
    }
);

echo "First config load:\n";
echo json_encode($processedConfig1, JSON_PRETTY_PRINT) . "\n\n";

// Same config - cache hit
$processedConfig2 = HashValidatedCache::remember(
    'ConfigDemo',
    'app_config',
    $config1,
    function() use ($config1): array {
        echo "  → Processing config (expensive operation)...\n";
        return array_merge($config1, ['processed' => true, 'timestamp' => time()]);
    }
);

echo "Second config load (cache hit):\n";
echo json_encode($processedConfig2, JSON_PRETTY_PRINT) . "\n\n";

// Modified config - cache invalidated
$config2 = [
    'max_entries' => 200,  // Changed!
    'ttl' => 3600,
    'driver' => 'memory',
];

$processedConfig3 = HashValidatedCache::remember(
    'ConfigDemo',
    'app_config',
    $config2,
    function() use ($config2): array {
        echo "  → Processing config (expensive operation)...\n";
        return array_merge($config2, ['processed' => true, 'timestamp' => time()]);
    }
);

echo "Third config load (cache invalidated - config changed):\n";
echo json_encode($processedConfig3, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: String-based source data
echo "3. String-Based Source Data\n";
echo "---------------------------\n";

$sql1 = "SELECT * FROM users WHERE active = 1";
$parsedSql1 = HashValidatedCache::remember(
    'SqlDemo',
    'query_1',
    $sql1,
    function() use ($sql1): array {
        echo "  → Parsing SQL query...\n";
        return ['query' => $sql1, 'parsed' => true];
    }
);

echo "First SQL parse:\n";
echo json_encode($parsedSql1, JSON_PRETTY_PRINT) . "\n\n";

// Same SQL - cache hit
$parsedSql2 = HashValidatedCache::remember(
    'SqlDemo',
    'query_1',
    $sql1,
    function() use ($sql1): array {
        echo "  → Parsing SQL query...\n";
        return ['query' => $sql1, 'parsed' => true];
    }
);

echo "Second SQL parse (cache hit):\n";
echo json_encode($parsedSql2, JSON_PRETTY_PRINT) . "\n\n";

// Modified SQL - cache invalidated
$sql2 = "SELECT * FROM users WHERE active = 1 AND verified = 1";  // Changed!
$parsedSql3 = HashValidatedCache::remember(
    'SqlDemo',
    'query_1',
    $sql2,
    function() use ($sql2): array {
        echo "  → Parsing SQL query...\n";
        return ['query' => $sql2, 'parsed' => true];
    }
);

echo "Third SQL parse (cache invalidated - query changed):\n";
echo json_encode($parsedSql3, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Demo Complete ===\n";
echo "\nKey Takeaways:\n";
echo "  ✅ Cache is automatically invalidated when source data changes\n";
echo "  ✅ No manual cache clearing needed after code/template changes\n";
echo "  ✅ Works with arrays, strings, objects, and file paths\n";
echo "  ✅ Uses fast xxh128 hashing algorithm\n";

