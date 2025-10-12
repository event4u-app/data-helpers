<?php

declare(strict_types=1);

/**
 * Example 14: Custom Wildcard Operators
 *
 * This example demonstrates how to register custom wildcard operators
 * (like LIMIT, OFFSET, GROUP BY) that can be used alongside the built-in
 * WHERE and ORDER BY operators.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;

echo "=== Custom Wildcard Operators Examples ===\n\n";

// Example data
$products = [
    'products' => [
        ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1200, 'stock' => 5],
        ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25, 'stock' => 50],
        ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300, 'stock' => 10],
        ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 150, 'stock' => 20],
        ['id' => 5, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400, 'stock' => 15],
        ['id' => 6, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 75, 'stock' => 30],
        ['id' => 7, 'name' => 'Bookshelf', 'category' => 'Furniture', 'price' => 200, 'stock' => 8],
    ],
];

// ============================================================================
// Example 1: LIMIT Operator
// ============================================================================
echo "1. LIMIT Operator - Limit results to N items\n";
echo str_repeat('-', 60) . "\n";

// Register LIMIT operator
WildcardOperatorRegistry::register('LIMIT', function(array $items, mixed $config): array {
    if (!is_int($config) || 0 > $config) {
        return $items;
    }

    $result = [];
    $count = 0;

    foreach ($items as $index => $item) {
        if ($count >= $config) {
            break;
        }
        $result[$index] = $item;
        $count++;
    }

    return $result;
});

$template1 = [
    'top_products' => [
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        'LIMIT' => 3,
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result1 = DataMapper::mapFromTemplate($template1, $products, true, true);

echo "Top 3 Most Expensive Products:\n";
foreach ($result1['top_products'] as $product) {
    echo sprintf('  - %s: $%s%s', $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 2: OFFSET Operator
// ============================================================================
echo "2. OFFSET Operator - Skip first N items\n";
echo str_repeat('-', 60) . "\n";

// Register OFFSET operator
WildcardOperatorRegistry::register('OFFSET', function(array $items, mixed $config): array {
    if (!is_int($config) || 0 > $config) {
        return $items;
    }

    $result = [];
    $count = 0;

    foreach ($items as $index => $item) {
        if ($count < $config) {
            $count++;
            continue;
        }
        $result[$index] = $item;
    }

    return $result;
});

$template2 = [
    'paginated_products' => [
        'ORDER BY' => [
            '{{ products.*.name }}' => 'ASC',
        ],
        'OFFSET' => 2,
        'LIMIT' => 3,
        '*' => [
            'name' => '{{ products.*.name }}',
        ],
    ],
];

$result2 = DataMapper::mapFromTemplate($template2, $products, true, true);

echo "Products (page 2, 3 items per page):\n";
foreach ($result2['paginated_products'] as $product) {
    echo sprintf('  - %s%s', $product['name'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 3: GROUP BY Operator
// ============================================================================
echo "3. GROUP BY Operator - Group items and return first of each group\n";
echo str_repeat('-', 60) . "\n";

// Register GROUP BY operator
WildcardOperatorRegistry::register('GROUP BY', function(array $items, mixed $config, mixed $sources): array {
    if (!is_string($config)) {
        return $items;
    }

    $grouped = [];

    foreach ($items as $index => $item) {
        // Extract field value from config (e.g., '{{ products.*.category }}')
        $fieldPath = str_replace('*', (string)$index, $config);

        if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
            $path = trim(substr($fieldPath, 2, -2));
            $accessor = new DataAccessor($sources);
            $groupKey = $accessor->get($path);
        } else {
            $groupKey = $item[$config] ?? 'default';
        }

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [];
        }
        $grouped[$groupKey][] = ['index' => $index, 'item' => $item];
    }

    // Return first item of each group
    $result = [];
    foreach ($grouped as $group) {
        $first = $group[0];
        $result[$first['index']] = $first['item'];
    }

    return $result;
});

$template3 = [
    'categories' => [
        'GROUP BY' => '{{ products.*.category }}',
        'ORDER BY' => [
            '{{ products.*.category }}' => 'ASC',
        ],
        '*' => [
            'category' => '{{ products.*.category }}',
            'example_product' => '{{ products.*.name }}',
        ],
    ],
];

$result3 = DataMapper::mapFromTemplate($template3, $products, true, true);

echo "Product Categories (first product of each):\n";
foreach ($result3['categories'] as $cat) {
    echo sprintf('  - %s: %s%s', $cat['category'], $cat['example_product'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 4: DISTINCT Operator
// ============================================================================
echo "4. DISTINCT Operator - Remove duplicates based on field\n";
echo str_repeat('-', 60) . "\n";

// Register DISTINCT operator
WildcardOperatorRegistry::register('DISTINCT', function(array $items, mixed $config, mixed $sources): array {
    if (!is_string($config)) {
        return $items;
    }

    $seen = [];
    $result = [];

    foreach ($items as $index => $item) {
        // Extract field value
        $fieldPath = str_replace('*', (string)$index, $config);

        if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
            $path = trim(substr($fieldPath, 2, -2));
            $accessor = new DataAccessor($sources);
            $value = $accessor->get($path);
        } else {
            $value = $item[$config] ?? null;
        }

        $key = serialize($value);

        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $result[$index] = $item;
        }
    }

    return $result;
});

$template4 = [
    'unique_categories' => [
        'DISTINCT' => '{{ products.*.category }}',
        '*' => [
            'category' => '{{ products.*.category }}',
        ],
    ],
];

$result4 = DataMapper::mapFromTemplate($template4, $products, true, true);

echo "Unique Categories:\n";
foreach ($result4['unique_categories'] as $cat) {
    echo sprintf('  - %s%s', $cat['category'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 5: Combining Multiple Custom Operators
// ============================================================================
echo "5. Combining Multiple Operators - WHERE + ORDER BY + LIMIT\n";
echo str_repeat('-', 60) . "\n";

$template5 = [
    'affordable_electronics' => [
        'WHERE' => [
            'AND' => [
                '{{ products.*.category }}' => 'Electronics',
                '{{ products.*.price }}' => 500,  // This will match items with price <= 500
            ],
        ],
        'ORDER BY' => [
            '{{ products.*.price }}' => 'ASC',
        ],
        'LIMIT' => 2,
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result5 = DataMapper::mapFromTemplate($template5, $products, true, true);

echo "Top 2 Affordable Electronics:\n";
foreach ($result5['affordable_electronics'] as $product) {
    echo sprintf('  - %s: $%s%s', $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

echo "=== All Examples Completed ===\n";
echo "\nNote: Custom operators are registered globally and persist across mappings.\n";
echo "Use WildcardOperatorRegistry::unregister('OPERATOR_NAME') to remove them.\n";

