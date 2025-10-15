<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataFilter;

$products = [
    ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 1200, 'stock' => 5, 'tags' => [
        'premium',
        'business',
    ]],
    ['id' => 2, 'name' => 'Mouse', 'category' => 'Electronics', 'price' => 25, 'stock' => 50, 'tags' => ['basic']],
    ['id' => 3, 'name' => 'Desk', 'category' => 'Furniture', 'price' => 300, 'stock' => 10, 'tags' => ['office']],
    ['id' => 4, 'name' => 'Chair', 'category' => 'Furniture', 'price' => 150, 'stock' => 20, 'tags' => [
        'office',
        'ergonomic',
    ]],
    ['id' => 5, 'name' => 'Monitor', 'category' => 'Electronics', 'price' => 400, 'stock' => 15, 'tags' => ['premium']],
    ['id' => 6, 'name' => 'Keyboard', 'category' => 'Electronics', 'price' => 80, 'stock' => 30, 'tags' => ['basic']],
];

echo "=== Complex Example 1: Multiple WHERE conditions + ORDER BY + LIMIT ===\n";
$result = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('price', '>', 50)
    ->where('stock', '>=', 10)
    ->orderBy('price', 'DESC')
    ->limit(3)
    ->get();

echo "Found " . count($result) . " products:\n";
foreach ($result as $product) {
    printf("  • %s - $%d (Stock: %d)\n", $product['name'], $product['price'], $product['stock']);
}
echo "\n";

echo "=== Complex Example 2: BETWEEN + LIKE + ORDER BY ===\n";
$result = DataFilter::query($products)
    ->between('price', 100, 500)
    ->like('name', '%o%')  // Contains 'o'
    ->orderBy('name', 'ASC')
    ->get();

echo "Found " . count($result) . " products:\n";
foreach ($result as $product) {
    printf("  • %s - $%d\n", $product['name'], $product['price']);
}
echo "\n";

echo "=== Complex Example 3: WHERE IN + NOT NULL + OFFSET ===\n";
$result = DataFilter::query($products)
    ->whereIn('category', ['Electronics', 'Furniture'])
    ->whereNotNull('stock')
    ->orderBy('id', 'ASC')
    ->offset(2)
    ->limit(3)
    ->get();

echo "Found " . count($result) . " products (offset 2, limit 3):\n";
foreach ($result as $product) {
    printf("  • ID %d: %s - %s\n", $product['id'], $product['name'], $product['category']);
}
echo "\n";

echo "=== Complex Example 4: Chaining with first() ===\n";
$cheapest = DataFilter::query($products)
    ->where('category', '=', 'Electronics')
    ->where('stock', '>', 20)
    ->orderBy('price', 'ASC')
    ->first();

if ($cheapest) {
    printf("Cheapest Electronics with stock > 20: %s - $%d\n", $cheapest['name'], $cheapest['price']);
} else {
    echo "No products found\n";
}
echo "\n";

echo "=== Complex Example 5: count() with complex filters ===\n";
$count = DataFilter::query($products)
    ->where('price', '>=', 100)
    ->where('price', '<=', 500)
    ->whereIn('category', ['Electronics', 'Furniture'])
    ->count();

echo "Products between $100-$500 in Electronics/Furniture: {$count}\n\n";

echo "=== Complex Example 6: Nested data access ===\n";
$users = [
    ['name' => 'Alice', 'profile' => ['age' => 30, 'city' => 'Berlin']],
    ['name' => 'Bob', 'profile' => ['age' => 25, 'city' => 'Munich']],
    ['name' => 'Charlie', 'profile' => ['age' => 35, 'city' => 'Berlin']],
];

$result = DataFilter::query($users)
    ->where('profile.city', '=', 'Berlin')
    ->where('profile.age', '>', 28)
    ->get();

echo "Users from Berlin older than 28:\n";
foreach ($result as $user) {
    printf("  • %s (Age: %d)\n", $user['name'], $user['profile']['age']);
}
echo "\n";

echo "=== All Complex Examples Complete ===\n";
