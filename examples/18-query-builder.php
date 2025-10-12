<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper\DataMapperQuery;

/**
 * DataMapper Query Builder - Laravel-style Fluent Interface
 *
 * This example demonstrates the Query Builder for DataMapper, which provides
 * a Laravel-inspired fluent interface for building complex data mapping queries.
 *
 * Features:
 * - Method chaining in any order
 * - WHERE with comparison operators (=, !=, <>, >, <, >=, <=)
 * - Nested WHERE conditions with closures
 * - OR WHERE conditions
 * - ORDER BY, LIMIT, OFFSET
 * - GROUP BY with aggregations (COUNT, SUM, AVG, MIN, MAX, etc.)
 * - HAVING clause for GROUP BY
 * - DISTINCT and LIKE operators
 * - Operators are applied in the order they are called
 */

// Sample product data
$products = [
    ['id' => 1, 'name' => 'Laptop Pro', 'category' => 'Electronics', 'price' => 1299, 'stock' => 5, 'rating' => 4.8],
    ['id' => 2, 'name' => 'Wireless Mouse', 'category' => 'Electronics', 'price' => 29, 'stock' => 50, 'rating' => 4.5],
    ['id' => 3, 'name' => 'Mechanical Keyboard', 'category' => 'Electronics', 'price' => 89, 'stock' => 30, 'rating' => 4.7],
    ['id' => 4, 'name' => '4K Monitor', 'category' => 'Electronics', 'price' => 399, 'stock' => 15, 'rating' => 4.6],
    ['id' => 5, 'name' => 'Standing Desk', 'category' => 'Furniture', 'price' => 299, 'stock' => 8, 'rating' => 4.4],
    ['id' => 6, 'name' => 'Ergonomic Chair', 'category' => 'Furniture', 'price' => 249, 'stock' => 12, 'rating' => 4.9],
    ['id' => 7, 'name' => 'LED Desk Lamp', 'category' => 'Furniture', 'price' => 45, 'stock' => 20, 'rating' => 4.3],
    ['id' => 8, 'name' => 'USB-C Hub', 'category' => 'Electronics', 'price' => 59, 'stock' => 25, 'rating' => 4.2],
];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       DataMapper Query Builder - Comprehensive Examples       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// Example 1: Basic WHERE Filtering
// ============================================================================
echo "┌─ Example 1: Basic WHERE Filtering ─────────────────────────────┐\n";
echo "│ Query: Filter products by category                             │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->get();

echo "Found " . count($result) . " electronics products:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $product) {
    echo "  • {$product['name']}: \${$product['price']} (Stock: {$product['stock']})\n";
}
echo "\n";

// ============================================================================
// Example 2: WHERE with Comparison Operators
// ============================================================================
echo "┌─ Example 2: WHERE with Comparison Operators ───────────────────┐\n";
echo "│ Query: Products priced over \$100                               │\n";
echo "│ Operators: =, !=, <>, >, <, >=, <=                             │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where('price', '>', 100)
    ->orderBy('price', 'DESC')
    ->get();

echo "Found " . count($result) . " products over \$100:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $product) {
    echo sprintf('  • %s: $%d%s', $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 3: Multiple WHERE Conditions (AND Logic)
// ============================================================================
echo "┌─ Example 3: Multiple WHERE Conditions (AND) ───────────────────┐\n";
echo "│ Query: Electronics under \$100 with good ratings                │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->where('price', '<', 100)
    ->where('rating', '>=', 4.5)
    ->get();

echo "Found " . count($result) . " affordable, well-rated electronics:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $product) {
    echo "  • {$product['name']}: \${$product['price']} (Rating: {$product['rating']}⭐)\n";
}
echo "\n";

// ============================================================================
// Example 4: ORDER BY with LIMIT
// ============================================================================
echo "┌─ Example 4: ORDER BY with LIMIT ───────────────────────────────┐\n";
echo "│ Query: Top 3 most expensive products                           │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->orderBy('price', 'DESC')
    ->limit(3)
    ->get();

echo "Top 3 most expensive:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $i => $product) {
    echo "  " . ((int)$i + 1) . sprintf('. %s: $%d%s', $product['name'], $product['price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 5: Nested WHERE with Closure (Complex AND Logic)
// ============================================================================
echo "┌─ Example 5: Nested WHERE with Closure ─────────────────────────┐\n";
echo "│ Query: Electronics over \$50 with high ratings                  │\n";
echo "│ Uses closure for grouping conditions                           │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where(function($query): void {
        $query->where('category', 'Electronics')
              ->where('price', '>', 50)
              ->where('rating', '>=', 4.5);
    })
    ->orderBy('rating', 'DESC')
    ->get();

echo "Premium electronics:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $product) {
    echo "  • {$product['name']}: \${$product['price']} ({$product['rating']}⭐)\n";
}
echo "\n";

// ============================================================================
// Example 6: OR WHERE Conditions
// ============================================================================
echo "┌─ Example 6: OR WHERE Conditions ───────────────────────────────┐\n";
echo "│ Query: Furniture OR products under \$50                         │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where('category', 'Furniture')
    ->orWhere('price', '<', 50)
    ->get();

echo "Found " . count($result) . " products:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $product) {
    echo "  • {$product['name']}: \${$product['price']} ({$product['category']})\n";
}
echo "\n";

// ============================================================================
// Example 7: GROUP BY with Aggregations
// ============================================================================
echo "┌─ Example 7: GROUP BY with Aggregations ────────────────────────┐\n";
echo "│ Query: Product statistics by category                          │\n";
echo "│ Aggregations: COUNT, AVG, SUM, MIN, MAX                        │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->groupBy('category', [
        'total_products' => ['COUNT'],
        'avg_price' => ['AVG', 'price'],
        'total_stock' => ['SUM', 'stock'],
        'min_price' => ['MIN', 'price'],
        'max_price' => ['MAX', 'price'],
    ])
    ->get();

echo "Category statistics:\n";
/** @var array{category: string, total_products: int, avg_price: float, total_stock: int, min_price: int, max_price: int} $group */
foreach ($result as $group) {
    echo "  • {$group['category']}:\n";
    echo sprintf('    - Products: %s%s', $group['total_products'], PHP_EOL);
    echo "    - Avg Price: \$" . number_format($group['avg_price'], 2) . "\n";
    echo sprintf('    - Total Stock: %s%s', $group['total_stock'], PHP_EOL);
    echo sprintf('    - Price Range: $%s - $%s%s', $group['min_price'], $group['max_price'], PHP_EOL);
}
echo "\n";

// ============================================================================
// Example 8: Complex Query - Chaining Multiple Operators
// ============================================================================
echo "┌─ Example 8: Complex Query - Multiple Operators ────────────────┐\n";
echo "│ Query: In-stock electronics, sorted by rating, top 3           │\n";
echo "│ Demonstrates: WHERE + ORDER BY + LIMIT in custom order         │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

$result = DataMapperQuery::query()
    ->source('products', $products)
    ->where('category', 'Electronics')
    ->where('stock', '>', 0)
    ->orderBy('rating', 'DESC')
    ->limit(3)
    ->get();

echo "Top 3 in-stock electronics by rating:\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($result as $i => $product) {
    echo "  " . ((int)$i + 1) . ". {$product['name']}: {$product['rating']}⭐ (\${$product['price']}, Stock: {$product['stock']})\n";
}
echo "\n";

// ============================================================================
// Example 9: Operator Order Matters
// ============================================================================
echo "┌─ Example 9: Operator Order Matters ────────────────────────────┐\n";
echo "│ Demonstrates: Operators are applied in the order called        │\n";
echo "│ Query A: LIMIT → WHERE (limits first, then filters)            │\n";
echo "│ Query B: WHERE → LIMIT (filters first, then limits)            │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";

// Query A: LIMIT first, then WHERE
$resultA = DataMapperQuery::query()
    ->source('products', $products)
    ->limit(4)  // Limit to first 4 products
    ->where('category', 'Electronics')  // Then filter
    ->get();

echo "Query A (LIMIT → WHERE): " . count($resultA) . " results\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($resultA as $product) {
    echo sprintf('  • %s%s', $product['name'], PHP_EOL);
}
echo "\n";

// Query B: WHERE first, then LIMIT
$resultB = DataMapperQuery::query()
    ->source('products', $products)
    ->where('category', 'Electronics')  // Filter first
    ->limit(4)  // Then limit
    ->get();

echo "Query B (WHERE → LIMIT): " . count($resultB) . " results\n";
/** @var array{id: int, name: string, category: string, price: int, stock: int, rating: float} $product */
foreach ($resultB as $product) {
    echo sprintf('  • %s%s', $product['name'], PHP_EOL);
}
echo "\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         Examples Complete                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
