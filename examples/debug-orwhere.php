<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataFilter;

$products = [
    ['id' => 1, 'category' => 'A', 'price' => 100],
    ['id' => 2, 'category' => 'B', 'price' => 50],
    ['id' => 3, 'category' => 'C', 'price' => 150],
];

echo "=== Test: orWhere ===\n";
/** @var array<int, array<string, mixed>> $result */
$result = DataFilter::query($products)
    ->where('category', '=', 'A')
    ->orWhere('category', '=', 'C')
    ->get();

echo "Result count: " . count($result) . "\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
