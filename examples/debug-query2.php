<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

$data = [
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['id' => 2, 'name' => 'Bob', 'email' => null],
    ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
];

echo "=== Test: whereNull ===\n";
$result = DataMapper::query()
    ->source('users', $data)
    ->whereNull('email')
    ->get();

echo "Result count: " . count($result) . "\n";
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
