<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

echo "=== DataMapper Conditional Expressions Examples ===\n\n";

// Example 1: Transform status to 0 or 1
echo "Example 1: Transform status to 0 or 1\n";
echo "--------------------------------------\n";

$users = [
    ['name' => 'Alice', 'status' => 'active'],
    ['name' => 'Bob', 'status' => 'inactive'],
    ['name' => 'Charlie', 'status' => 'active'],
];

$result = DataMapper::source(['users' => $users])
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name }}',
            'active' => '{{ users.*.status == "active" ? 1 : 0 }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($users);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 2: Age category
echo "Example 2: Age category (adult/minor)\n";
echo "--------------------------------------\n";

$people = [
    ['name' => 'Alice', 'age' => 25],
    ['name' => 'Bob', 'age' => 17],
    ['name' => 'Charlie', 'age' => 30],
];

$result = DataMapper::source(['people' => $people])
    ->template([
        'people.*' => [
            'name' => '{{ people.*.name }}',
            'category' => '{{ people.*.age >= 18 ? "adult" : "minor" }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($people);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 3: Price category
echo "Example 3: Price category (expensive/cheap)\n";
echo "--------------------------------------------\n";

$products = [
    ['name' => 'Laptop', 'price' => 1200],
    ['name' => 'Mouse', 'price' => 25],
    ['name' => 'Monitor', 'price' => 350],
];

$result = DataMapper::source(['products' => $products])
    ->template([
        'products.*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
            'expensive' => '{{ products.*.price > 100 ? true : false }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($products);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 4: Boolean flags
echo "Example 4: Boolean flags\n";
echo "------------------------\n";

$orders = [
    ['id' => 1, 'total' => 150, 'status' => 'completed'],
    ['id' => 2, 'total' => 50, 'status' => 'pending'],
    ['id' => 3, 'total' => 200, 'status' => 'completed'],
];

$result = DataMapper::source(['orders' => $orders])
    ->template([
        'orders.*' => [
            'id' => '{{ orders.*.id }}',
            'total' => '{{ orders.*.total }}',
            'is_completed' => '{{ orders.*.status == "completed" ? true : false }}',
            'is_large_order' => '{{ orders.*.total >= 100 ? true : false }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($orders);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 5: Nested conditions with comparison operators
echo "Example 5: All comparison operators\n";
echo "------------------------------------\n";

$items = [
    ['name' => 'Item A', 'quantity' => 10],
    ['name' => 'Item B', 'quantity' => 5],
    ['name' => 'Item C', 'quantity' => 15],
];

$result = DataMapper::source(['items' => $items])
    ->template([
        'items.*' => [
            'name' => '{{ items.*.name }}',
            'quantity' => '{{ items.*.quantity }}',
            'low_stock' => '{{ items.*.quantity < 10 ? 1 : 0 }}',
            'medium_stock' => '{{ items.*.quantity <= 10 ? 1 : 0 }}',
            'high_stock' => '{{ items.*.quantity > 10 ? 1 : 0 }}',
            'very_high_stock' => '{{ items.*.quantity >= 15 ? 1 : 0 }}',
            'not_low_stock' => '{{ items.*.quantity != 5 ? 1 : 0 }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($items);
echo "\nOutput:\n";
print_r($result);
