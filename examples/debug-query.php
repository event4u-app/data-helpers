<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

$data = [
    ['id' => 1, 'name' => 'Alice', 'email' => null],
    ['id' => 2, 'name' => null, 'email' => 'bob@example.com'],
    ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
];

echo "=== Test 1: whereNotNull ===\n";
$result = DataMapper::query()
    ->source('users', $data)
    ->whereNotNull('name')
    ->whereNotNull('email')
    ->get();

echo "Result count: " . count($result) . "\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n=== Test 2: exists ===\n";
$result2 = DataMapper::query()
    ->source('users', $data)
    ->exists('email')
    ->get();

echo "Result count: " . count($result2) . "\n";
echo json_encode($result2, JSON_PRETTY_PRINT) . PHP_EOL;
