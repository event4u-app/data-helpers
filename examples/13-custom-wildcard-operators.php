<?php

declare(strict_types=1);

/**
 * Example 14: Custom Wildcard Operators
 *
 * This example demonstrates:
 * - Built-in operators: WHERE, ORDER BY, LIMIT, OFFSET
 * - How to register custom wildcard operators
 * - Combining multiple operators
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;

echo "=== Wildcard Operators Examples ===\n\n";

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
// Example 1: Built-in Operators (WHERE, ORDER BY, LIMIT, OFFSET)
// ============================================================================
echo "1. Built-in Operators\n";
echo str_repeat('-', 60) . "\n";

$template1 = [
    'top_electronics' => [
        'WHERE' => [
            '{{ products.*.category }}' => 'Electronics',
        ],
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        'OFFSET' => 1,  // Skip the most expensive
        'LIMIT' => 2,   // Get the next 2
        '*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result1 = DataMapper::source($products)
    ->template($template1)
    ->skipNull(true)
    ->reindexWildcard(true)
    ->map()
    ->toArray();

echo "Top Electronics (2nd and 3rd most expensive):\n";
/** @var array<int|string, array{name: string, price: float}> $topElectronics */
$topElectronics = $result1['top_electronics'] ?? [];
foreach ($topElectronics as $product) {
    echo sprintf("  - %s: $%s%s", $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 2: Custom DISTINCT Operator
// ============================================================================
echo "2. Custom DISTINCT Operator\n";
echo str_repeat('-', 60) . "\n";

// Register DISTINCT operator
WildcardOperatorRegistry::register('DISTINCT', function(array $items, mixed $config, mixed $sources): array {
    if (!is_string($config)) {
        return $items;
    }

    $seen = [];
    $result = [];

    foreach ($items as $index => $item) {
        // Access the source data to get the field value
        $accessor = new DataAccessor($sources);
        $fieldPath = str_replace('*', (string)$index, $config);

        if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
            $path = trim(substr($fieldPath, 2, -2));
            $value = $accessor->get($path);
        } else {
            $value = $accessor->get(sprintf('products.%s.%s', $index, $config));
        }

        $key = json_encode($value);

        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $result[$index] = $item;
        }
    }

    return $result;
});

$template2 = [
    'unique_categories' => [
        'DISTINCT' => '{{ products.*.category }}',
        '*' => [
            'category' => '{{ products.*.category }}',
        ],
    ],
];

$result2 = DataMapper::source($products)
    ->template($template2)
    ->skipNull(true)
    ->reindexWildcard(true)
    ->map()
    ->toArray();

echo "Unique Categories:\n";
/** @var array<int|string, array{category: string}> $uniqueCategories */
$uniqueCategories = $result2['unique_categories'] ?? [];
foreach ($uniqueCategories as $item) {
    echo sprintf("  - %s%s", $item['category'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 3: Custom GROUP BY Operator
// ============================================================================
echo "3. Custom GROUP BY Operator\n";
echo str_repeat('-', 60) . "\n";

// Register GROUP BY operator (returns first item of each group)
WildcardOperatorRegistry::register('GROUP BY', function(array $items, mixed $config, mixed $sources): array {
    if (!is_string($config)) {
        return $items;
    }

    $grouped = [];

    foreach ($items as $index => $item) {
        // Access the source data to get the grouping field
        $accessor = new DataAccessor($sources);
        $fieldPath = str_replace('*', (string)$index, $config);

        if (str_starts_with($fieldPath, '{{') && str_ends_with($fieldPath, '}}')) {
            $path = trim(substr($fieldPath, 2, -2));
            $groupKey = $accessor->get($path);
        } else {
            $groupKey = $accessor->get(sprintf('products.%s.%s', $index, $config));
        }

        /** @var int|string $groupKey */

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = ['index' => $index, 'item' => $item];
        }
    }

    // Return first item of each group
    $result = [];
    foreach ($grouped as $group) {
        $result[$group['index']] = $group['item'];
    }

    return $result;
});

$template3 = [
    'grouped_by_category' => [
        'GROUP BY' => '{{ products.*.category }}',
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        '*' => [
            'name' => '{{ products.*.name }}',
            'category' => '{{ products.*.category }}',
            'price' => '{{ products.*.price }}',
        ],
    ],
];

$result3 = DataMapper::source($products)
    ->template($template3)
    ->skipNull(true)
    ->reindexWildcard(true)
    ->map()
    ->toArray();

echo "First Product per Category (sorted by price DESC):\n";
/** @var array<int|string, array{name: string, category: string, price: float}> $groupedByCategory */
$groupedByCategory = $result3['grouped_by_category'] ?? [];
foreach ($groupedByCategory as $product) {
    echo sprintf("  - %s (%s): $%s%s", $product['name'], $product['category'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 4: Custom EVEN_IDS Operator
// ============================================================================
echo "4. Custom EVEN_IDS Operator\n";
echo str_repeat('-', 60) . "\n";

// Register EVEN_IDS operator (filters items with even IDs)
WildcardOperatorRegistry::register('EVEN_IDS', function(array $items, mixed $config, mixed $sources): array {
    $result = [];
    foreach ($items as $index => $item) {
        $accessor = new DataAccessor($sources);
        $id = $accessor->get(sprintf('products.%s.id', $index));

        if (is_int($id) && 0 === $id % 2) {
            $result[$index] = $item;
        }
    }
    return $result;
});

$template4 = [
    'even_id_products' => [
        'EVEN_IDS' => true,
        '*' => [
            'id' => '{{ products.*.id }}',
            'name' => '{{ products.*.name }}',
        ],
    ],
];

$result4 = DataMapper::source($products)
    ->template($template4)
    ->skipNull(true)
    ->reindexWildcard(true)
    ->map()
    ->toArray();

echo "Products with Even IDs:\n";
/** @var array<int|string, array{id: int, name: string}> $evenIdProducts */
$evenIdProducts = $result4['even_id_products'] ?? [];
foreach ($evenIdProducts as $product) {
    echo sprintf("  - ID %s: %s%s", $product['id'], $product['name'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 5: Combining All Operators
// ============================================================================
echo "5. Combining All Operators\n";
echo str_repeat('-', 60) . "\n";

$template5 = [
    'complex_query' => [
        'WHERE' => [
            '{{ products.*.category }}' => 'Electronics',
        ],
        'ORDER BY' => [
            '{{ products.*.price }}' => 'DESC',
        ],
        'OFFSET' => 1,
        'LIMIT' => 2,
        '*' => [
            'name' => '{{ products.*.name }}',
            'category' => '{{ products.*.category }}',
            'price' => '{{ products.*.price }}',
            'stock' => '{{ products.*.stock }}',
        ],
    ],
];

$result5 = DataMapper::source($products)
    ->template($template5)
    ->skipNull(true)
    ->reindexWildcard(true)
    ->map()
    ->toArray();

echo "Complex Query (Electronics, sorted by price DESC, skip 1, limit 2):\n";
/** @var array<int|string, array{name: string, category: string, price: float, stock: int}> $complexQuery */
$complexQuery = $result5['complex_query'] ?? [];
foreach ($complexQuery as $product) {
    echo sprintf(
        "  - %s (%s): $%s, Stock: %s%s",
        $product['name'],
        $product['category'],
        $product['price'],
        $product['stock'],
        PHP_EOL
    );
}
echo "\n";

echo "✅  All examples completed successfully!\n";

