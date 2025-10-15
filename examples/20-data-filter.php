<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataFilter;

echo "=== DataFilter Examples ===\n\n";

// Sample data
$products = [
    ['id' => 1, 'name' => 'Laptop Pro', 'price' => 1299, 'category' => 'Electronics', 'stock' => 15, 'rating' => 4.5],
    ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 29, 'category' => 'Electronics', 'stock' => 50, 'rating' => 4.2],
    ['id' => 3, 'name' => 'Office Desk', 'price' => 399, 'category' => 'Furniture', 'stock' => 8, 'rating' => 4.7],
    ['id' => 4, 'name' => 'Gaming Chair', 'price' => 299, 'category' => 'Furniture', 'stock' => 12, 'rating' => 4.4],
    ['id' => 5, 'name' => 'USB-C Cable', 'price' => 15, 'category' => 'Electronics', 'stock' => 100, 'rating' => 4.0],
    ['id' => 6, 'name' => 'Monitor Stand', 'price' => 79, 'category' => 'Furniture', 'stock' => 25, 'rating' => 4.3],
    ['id' => 7, 'name' => 'Mechanical Keyboard', 'price' => 149, 'category' => 'Electronics', 'stock' => 30, 'rating' => 4.8],
    ['id' => 8, 'name' => 'Bookshelf', 'price' => 199, 'category' => 'Furniture', 'stock' => 5, 'rating' => 4.6],
];

// Example 1: Simple WHERE filtering
echo "1. Simple WHERE - Electronics only:\n";
$electronics = DataFilter::query($products)
    ->where('category', 'Electronics')
    ->get();

foreach ($electronics as $product) {
    echo sprintf("   • %s - $%d\n", $product['name'], $product['price']);
}
echo "\n";

// Example 2: Comparison operators
echo "2. Comparison - Products over $100:\n";
$expensive = DataFilter::query($products)
    ->where('price', '>', 100)
    ->get();

foreach ($expensive as $product) {
    echo sprintf("   • %s - $%d (%s)\n", $product['name'], $product['price'], $product['category']);
}
echo "\n";

// Example 3: BETWEEN
echo "3. BETWEEN - Products priced $100-$300:\n";
$midRange = DataFilter::query($products)
    ->between('price', 100, 300)
    ->get();

foreach ($midRange as $product) {
    echo sprintf("   • %s - $%d\n", $product['name'], $product['price']);
}
echo "\n";

// Example 4: WHERE IN
echo "4. WHERE IN - Electronics or Furniture:\n";
$categories = DataFilter::query($products)
    ->whereIn('category', ['Electronics', 'Furniture'])
    ->get();

echo sprintf("   Found %d products\n\n", count($categories));

// Example 5: LIKE pattern matching
echo "5. LIKE - Products with 'Pro' in name:\n";
$proProducts = DataFilter::query($products)
    ->like('name', '%Pro%')
    ->get();

foreach ($proProducts as $product) {
    echo sprintf("   • %s\n", $product['name']);
}
echo "\n";

// Example 6: ORDER BY
echo "6. ORDER BY - Products sorted by price (DESC):\n";
$sorted = DataFilter::query($products)
    ->orderBy('price', 'DESC')
    ->limit(5)
    ->get();

foreach ($sorted as $product) {
    echo sprintf("   • %s - $%d\n", $product['name'], $product['price']);
}
echo "\n";

// Example 7: Complex query with chaining
echo "7. Complex Query - Top 3 Electronics by rating:\n";
$topElectronics = DataFilter::query($products)
    ->where('category', 'Electronics')
    ->where('stock', '>', 20)
    ->orderBy('rating', 'DESC')
    ->limit(3)
    ->get();

foreach ($topElectronics as $product) {
    echo sprintf("   • %s - Rating: %.1f (Stock: %d)\n",
        $product['name'],
        $product['rating'],
        $product['stock']
    );
}
echo "\n";

// Example 8: Null handling
echo "8. NULL Handling:\n";
$productsWithNulls = [
    ['id' => 1, 'name' => 'Active Product', 'deleted_at' => null],
    ['id' => 2, 'name' => 'Deleted Product', 'deleted_at' => '2024-01-01'],
    ['id' => 3, 'name' => 'Another Active', 'deleted_at' => null],
];

$active = DataFilter::query($productsWithNulls)
    ->whereNull('deleted_at')
    ->get();

echo "   Active products:\n";
foreach ($active as $product) {
    echo sprintf("     • %s\n", $product['name']);
}
echo "\n";

echo "=== All Examples Complete ===\n";
