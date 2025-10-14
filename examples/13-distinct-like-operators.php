<?php

declare(strict_types=1);

/**
 * Example 16: DISTINCT and LIKE Wildcard Operators
 *
 * This example demonstrates the built-in DISTINCT and LIKE operators
 * for filtering and deduplicating wildcard arrays.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

echo "=== DISTINCT and LIKE Operators ===\n\n";

// Example data: Products with duplicates
$products = [
    'products' => [
        ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'electronics', 'price' => 1200, 'brand' => 'TechCorp'],
        ['id' => 2, 'name' => 'Mouse', 'category' => 'electronics', 'price' => 25, 'brand' => 'TechCorp'],
        ['id' => 3, 'name' => 'Desk Pro', 'category' => 'furniture', 'price' => 500, 'brand' => 'FurnCo'],
        ['id' => 4, 'name' => 'Chair', 'category' => 'furniture', 'price' => 200, 'brand' => 'FurnCo'],
        ['id' => 5, 'name' => 'Monitor Pro', 'category' => 'electronics', 'price' => 300, 'brand' => 'TechCorp'],
        ['id' => 6, 'name' => 'Keyboard', 'category' => 'electronics', 'price' => 75, 'brand' => 'TechCorp'],
        ['id' => 7, 'name' => 'Laptop Basic', 'category' => 'electronics', 'price' => 800, 'brand' => 'BudgetTech'],
    ],
];

// ============================================================================
// Example 1: DISTINCT by field
// ============================================================================
echo "1. DISTINCT by category\n";
echo str_repeat('-', 60) . "\n";

$template1 = [
    'categories' => [
        'DISTINCT' => '{{ products.*.category }}',
        '*' => [
            'category' => '{{ products.*.category }}',
            'name' => '{{ products.*.name }}',
        ],
    ],
];

$result1 = DataMapper::mapFromTemplate($template1, $products, true, true);

echo "Unique categories (first occurrence):\n";
/** @var array<int|string, array{category: string, name: string}> $categories */
$categories = $result1['categories'] ?? [];
foreach ($categories as $item) {
    echo sprintf("  - %s (from: %s)%s", $item['category'], $item['name'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 2: LIKE with % wildcard
// ============================================================================
echo "2. LIKE with % wildcard (contains 'Pro')\n";
echo str_repeat('-', 60) . "\n";

$template2 = [
    'pro_products' => [
        'LIKE' => [
            '{{ products.*.name }}' => '%Pro%',
        ],
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result2 = DataMapper::mapFromTemplate($template2, $products, true, true);

echo "Products with 'Pro' in name:\n";
/** @var array<int|string, array{name: string, price: int}> $proProducts */
$proProducts = $result2['pro_products'] ?? [];
foreach ($proProducts as $product) {
    echo sprintf("  - %s: $%d%s", $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 3: LIKE with _ wildcard
// ============================================================================
echo "3. LIKE with _ wildcard (single character)\n";
echo str_repeat('-', 60) . "\n";

$template3 = [
    'tech_brands' => [
        'LIKE' => [
            '{{ products.*.brand }}' => 'Tech____',
        ],
        'DISTINCT' => '{{ products.*.brand }}',
        '*' => [
            'brand' => '{{ products.*.brand }}',
        ],
    ],
];

$result3 = DataMapper::mapFromTemplate($template3, $products, true, true);

echo "Brands matching 'Tech____' pattern:\n";
/** @var array<int|string, array{brand: string}> $techBrands */
$techBrands = $result3['tech_brands'] ?? [];
foreach ($techBrands as $item) {
    echo sprintf("  - %s%s", $item['brand'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 4: Combining LIKE, DISTINCT, ORDER BY, LIMIT
// ============================================================================
echo "4. Combining LIKE, DISTINCT, ORDER BY, LIMIT\n";
echo str_repeat('-', 60) . "\n";

$template4 = [
    'electronics_brands' => [
        'WHERE' => [
            '{{ products.*.category }}' => 'electronics',
        ],
        'DISTINCT' => '{{ products.*.brand }}',
        'ORDER BY' => [
            '{{ products.*.brand }}' => 'asc',
        ],
        'LIMIT' => 2,
        '*' => [
            'brand' => '{{ products.*.brand }}',
        ],
    ],
];

$result4 = DataMapper::mapFromTemplate($template4, $products, true, true);

echo "Top 2 electronics brands (alphabetically):\n";
/** @var array<int|string, array{brand: string}> $electronicsBrands */
$electronicsBrands = $result4['electronics_brands'] ?? [];
foreach ($electronicsBrands as $item) {
    echo sprintf("  - %s%s", $item['brand'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 5: LIKE with case-sensitive matching
// ============================================================================
echo "5. LIKE with case-sensitive matching\n";
echo str_repeat('-', 60) . "\n";

$template5 = [
    'uppercase_pro' => [
        'LIKE' => [
            '{{ products.*.name }}' => [
                'pattern' => '%Pro',
                'case_sensitive' => true,
            ],
        ],
        '*' => [
            'name' => '{{ products.*.name }}',
        ],
    ],
];

$result5 = DataMapper::mapFromTemplate($template5, $products, true, true);

echo "Products ending with 'Pro' (case-sensitive):\n";
/** @var array<int|string, array{name: string}> $uppercasePro */
$uppercasePro = $result5['uppercase_pro'] ?? [];
foreach ($uppercasePro as $product) {
    echo sprintf("  - %s%s", $product['name'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 6: Multiple LIKE conditions (AND logic)
// ============================================================================
echo "6. Multiple LIKE conditions (AND logic)\n";
echo str_repeat('-', 60) . "\n";

$template6 = [
    'tech_electronics' => [
        'LIKE' => [
            '{{ products.*.brand }}' => 'Tech%',
            '{{ products.*.category }}' => 'electronics',
        ],
        '*' => [
            'name' => '{{ products.*.name }}',
            'brand' => '{{ products.*.brand }}',
        ],
    ],
];

$result6 = DataMapper::mapFromTemplate($template6, $products, true, true);

echo "TechCorp electronics:\n";
/** @var array<int|string, array{name: string, brand: string}> $techElectronics */
$techElectronics = $result6['tech_electronics'] ?? [];
foreach ($techElectronics as $product) {
    echo sprintf("  - %s (%s)%s", $product['name'], $product['brand'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 7: DISTINCT with entire item
// ============================================================================
echo "7. DISTINCT with entire item (remove exact duplicates)\n";
echo str_repeat('-', 60) . "\n";

$duplicateData = [
    'items' => [
        ['name' => 'Item A', 'value' => 100],
        ['name' => 'Item B', 'value' => 200],
        ['name' => 'Item A', 'value' => 100], // Exact duplicate
        ['name' => 'Item C', 'value' => 300],
    ],
];

$template7 = [
    'unique_items' => [
        'DISTINCT' => true,
        '*' => [
            'name' => '{{ items.*.name }}',
            'value' => '{{ items.*.value }}',
        ],
    ],
];

$result7 = DataMapper::mapFromTemplate($template7, $duplicateData, true, true);

echo "Unique items (exact match):\n";
/** @var array<int|string, array{name: string, value: int}> $uniqueItems */
$uniqueItems = $result7['unique_items'] ?? [];
foreach ($uniqueItems as $item) {
    echo sprintf("  - %s: %d%s", $item['name'], $item['value'], PHP_EOL);
}
echo "\n";

echo "✅  All examples completed successfully!\n";
echo "\n📝  Key Takeaways:\n";
echo "  - DISTINCT removes duplicates based on a field or entire item\n";
echo "  - LIKE supports SQL-style pattern matching with % and _\n";
echo "  - LIKE is case-insensitive by default\n";
echo "  - Both operators can be combined with WHERE, ORDER BY, LIMIT, OFFSET\n";
echo "  - Operators are applied in the order they appear in the template\n";

