<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

// Sample data: Sales transactions
$sources = [
    'sales' => [
        ['id' => 1, 'product' => 'Laptop', 'category' => 'Electronics', 'brand' => 'Dell', 'price' => 1200, 'quantity' => 2, 'region' => 'North'],
        ['id' => 2, 'product' => 'Mouse', 'category' => 'Electronics', 'brand' => 'Logitech', 'price' => 25, 'quantity' => 5, 'region' => 'South'],
        ['id' => 3, 'product' => 'Keyboard', 'category' => 'Electronics', 'brand' => 'Corsair', 'price' => 75, 'quantity' => 3, 'region' => 'North'],
        ['id' => 4, 'product' => 'Desk', 'category' => 'Furniture', 'brand' => 'IKEA', 'price' => 300, 'quantity' => 1, 'region' => 'East'],
        ['id' => 5, 'product' => 'Chair', 'category' => 'Furniture', 'brand' => 'Herman Miller', 'price' => 800, 'quantity' => 2, 'region' => 'West'],
        ['id' => 6, 'product' => 'Monitor', 'category' => 'Electronics', 'brand' => 'Samsung', 'price' => 400, 'quantity' => 2, 'region' => 'North'],
        ['id' => 7, 'product' => 'Lamp', 'category' => 'Furniture', 'brand' => 'Philips', 'price' => 50, 'quantity' => 4, 'region' => 'South'],
        ['id' => 8, 'product' => 'Webcam', 'category' => 'Electronics', 'brand' => 'Logitech', 'price' => 100, 'quantity' => 1, 'region' => 'East'],
        ['id' => 9, 'product' => 'Headphones', 'category' => 'Electronics', 'brand' => 'Sony', 'price' => 150, 'quantity' => 3, 'region' => 'West'],
        ['id' => 10, 'product' => 'Bookshelf', 'category' => 'Furniture', 'brand' => 'IKEA', 'price' => 200, 'quantity' => 1, 'region' => 'North'],
    ],
];

echo str_repeat('═', 80) . "\n";
echo "  Example 17: GROUP BY with Aggregations and HAVING\n";
echo str_repeat('═', 80) . "\n\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 1: Basic GROUP BY with COUNT
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 1: Basic GROUP BY with COUNT\n";
echo str_repeat('─', 80) . "\n";

$template1 = [
    'categories' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_count' => ['COUNT'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'count' => '{{ sales.*.product_count }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result1 = DataMapper::source($sources)->template($template1)->map()->getTarget();
echo json_encode($result1, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 2: GROUP BY with SUM and AVG
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 2: GROUP BY with SUM and AVG\n";
echo str_repeat('─', 80) . "\n";

$template2 = [
    'category_stats' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
                'avg_price' => ['AVG', '{{ sales.*.price }}'],
                'total_quantity' => ['SUM', '{{ sales.*.quantity }}'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'total_revenue' => '{{ sales.*.total_revenue }}',
            'avg_price' => '{{ sales.*.avg_price }}',
            'total_quantity' => '{{ sales.*.total_quantity }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result2 = DataMapper::source($sources)->template($template2)->map()->getTarget();
echo json_encode($result2, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 3: GROUP BY with MIN and MAX
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 3: GROUP BY with MIN and MAX\n";
echo str_repeat('─', 80) . "\n";

$template3 = [
    'price_ranges' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'min_price' => ['MIN', '{{ sales.*.price }}'],
                'max_price' => ['MAX', '{{ sales.*.price }}'],
                'price_range' => ['CONCAT', '{{ sales.*.price }}', ' - '],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'cheapest' => '{{ sales.*.min_price }}',
            'most_expensive' => '{{ sales.*.max_price }}',
            'all_prices' => '{{ sales.*.price_range }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result3 = DataMapper::source($sources)->template($template3)->map()->getTarget();
echo json_encode($result3, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 4: GROUP BY with FIRST and LAST
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 4: GROUP BY with FIRST and LAST\n";
echo str_repeat('─', 80) . "\n";

$template4 = [
    'category_products' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'first_product' => ['FIRST', '{{ sales.*.product }}'],
                'last_product' => ['LAST', '{{ sales.*.product }}'],
                'first_brand' => ['FIRST', '{{ sales.*.brand }}'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'first' => '{{ sales.*.first_product }}',
            'last' => '{{ sales.*.last_product }}',
            'brand' => '{{ sales.*.first_brand }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result4 = DataMapper::source($sources)->template($template4)->map()->getTarget();
echo json_encode($result4, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 5: GROUP BY with COLLECT
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 5: GROUP BY with COLLECT (collect all values into array)\n";
echo str_repeat('─', 80) . "\n";

$template5 = [
    'brand_products' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.brand }}',
            'aggregations' => [
                'all_products' => ['COLLECT', '{{ sales.*.product }}'],
                'all_prices' => ['COLLECT', '{{ sales.*.price }}'],
            ],
        ],
        '*' => [
            'brand' => '{{ sales.*.brand }}',
            'products' => '{{ sales.*.all_products }}',
            'prices' => '{{ sales.*.all_prices }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result5 = DataMapper::source($sources)->template($template5)->map()->getTarget();
echo json_encode($result5, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 6: GROUP BY with CONCAT
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 6: GROUP BY with CONCAT (concatenate values with separator)\n";
echo str_repeat('─', 80) . "\n";

$template6 = [
    'category_summary' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_list' => ['CONCAT', '{{ sales.*.product }}', ', '],
                'brand_list' => ['CONCAT', '{{ sales.*.brand }}', ' | '],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'products' => '{{ sales.*.product_list }}',
            'brands' => '{{ sales.*.brand_list }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result6 = DataMapper::source($sources)->template($template6)->map()->getTarget();
echo json_encode($result6, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 7: GROUP BY with HAVING clause (filter groups)
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 7: GROUP BY with HAVING (only categories with more than 3 products)\n";
echo str_repeat('─', 80) . "\n";

$template7 = [
    'popular_categories' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_count' => ['COUNT'],
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
            ],
            'HAVING' => [
                'product_count' => ['>', 3],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'count' => '{{ sales.*.product_count }}',
            'revenue' => '{{ sales.*.total_revenue }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result7 = DataMapper::source($sources)->template($template7)->map()->getTarget();
echo json_encode($result7, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 8: GROUP BY with multiple HAVING conditions
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 8: GROUP BY with multiple HAVING conditions (AND logic)\n";
echo str_repeat('─', 80) . "\n";

$template8 = [
    'high_value_categories' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'product_count' => ['COUNT'],
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
                'avg_price' => ['AVG', '{{ sales.*.price }}'],
            ],
            'HAVING' => [
                'product_count' => ['>=', 3],
                'total_revenue' => ['>', 1000],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'count' => '{{ sales.*.product_count }}',
            'revenue' => '{{ sales.*.total_revenue }}',
            'avg_price' => '{{ sales.*.avg_price }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result8 = DataMapper::source($sources)->template($template8)->map()->getTarget();
echo json_encode($result8, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 9: GROUP BY multiple fields
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 9: GROUP BY multiple fields (category AND region)\n";
echo str_repeat('─', 80) . "\n";

$template9 = [
    'category_region_stats' => [
        'GROUP BY' => [
            'fields' => [
                '{{ sales.*.category }}',
                '{{ sales.*.region }}',
            ],
            'aggregations' => [
                'product_count' => ['COUNT'],
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'region' => '{{ sales.*.region }}',
            'count' => '{{ sales.*.product_count }}',
            'revenue' => '{{ sales.*.total_revenue }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result9 = DataMapper::source($sources)->template($template9)->map()->getTarget();
echo json_encode($result9, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ═══════════════════════════════════════════════════════════════════════════════
// Example 10: Complex - GROUP BY with all aggregations and HAVING
// ═══════════════════════════════════════════════════════════════════════════════
echo "Example 10: Complex example with all aggregation types\n";
echo str_repeat('─', 80) . "\n";

$template10 = [
    'comprehensive_stats' => [
        'GROUP BY' => [
            'field' => '{{ sales.*.category }}',
            'aggregations' => [
                'count' => ['COUNT'],
                'total_revenue' => ['SUM', '{{ sales.*.price }}'],
                'avg_price' => ['AVG', '{{ sales.*.price }}'],
                'min_price' => ['MIN', '{{ sales.*.price }}'],
                'max_price' => ['MAX', '{{ sales.*.price }}'],
                'first_product' => ['FIRST', '{{ sales.*.product }}'],
                'last_product' => ['LAST', '{{ sales.*.product }}'],
                'all_brands' => ['COLLECT', '{{ sales.*.brand }}'],
                'product_names' => ['CONCAT', '{{ sales.*.product }}', ', '],
            ],
            'HAVING' => [
                'count' => ['>=', 3],
            ],
        ],
        '*' => [
            'category' => '{{ sales.*.category }}',
            'product_count' => '{{ sales.*.count }}',
            'revenue' => '{{ sales.*.total_revenue }}',
            'avg_price' => '{{ sales.*.avg_price }}',
            'price_range' => [
                'min' => '{{ sales.*.min_price }}',
                'max' => '{{ sales.*.max_price }}',
            ],
            'products' => [
                'first' => '{{ sales.*.first_product }}',
                'last' => '{{ sales.*.last_product }}',
                'all' => '{{ sales.*.product_names }}',
            ],
            'brands' => '{{ sales.*.all_brands }}',
        ],
    ],
];

/** @phpstan-ignore-next-line unknown */
$result10 = DataMapper::source($sources)->template($template10)->map()->getTarget();
echo json_encode($result10, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo str_repeat('═', 80) . "\n";
echo "  All examples completed successfully!\n";
echo str_repeat('═', 80) . "\n";
