<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper;

echo "=== DataMapper Null Coalescing (??) and Elvis (?:) Operators ===\n\n";

// Example 1: Null Coalescing Operator (??)
echo "Example 1: Null Coalescing Operator (??)\n";
echo "-------------------------------------------\n";

$users = [
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => null],
    ['name' => 'Charlie'], // email missing
];

$result = DataMapper::source(['users' => $users])
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name }}',
            'email' => '{{ users.*.email ?? "no-email@example.com" }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($users);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 2: Elvis Operator (?:)
echo "Example 2: Elvis Operator (?:)\n";
echo "--------------------------------\n";

$users = [
    ['name' => 'Alice'],
    ['name' => ''], // empty string
    ['name' => null], // null
];

$result = DataMapper::source(['users' => $users])
    ->template([
        'users.*' => [
            'name' => '{{ users.*.name ?: "Anonymous" }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($users);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 3: Difference between ?? and ?:
echo "Example 3: Difference between ?? and ?:\n";
echo "-----------------------------------------\n";

$data = [
    'email_null' => null,
    'email_empty' => '',
    'quantity_zero' => 0,
    'active_false' => false,
];

$result = DataMapper::source(['data' => $data])
    ->template([
        // ?? only triggers on null
        'email_null_coalescing' => '{{ data.email_null ?? "default" }}',
        'email_empty_coalescing' => '{{ data.email_empty ?? "default" }}',
        'quantity_zero_coalescing' => '{{ data.quantity_zero ?? 10 }}',
        'active_false_coalescing' => '{{ data.active_false ?? true }}',

        // ?: triggers on any falsy value (null, false, 0, "", [])
        'email_null_elvis' => '{{ data.email_null ?: "default" }}',
        'email_empty_elvis' => '{{ data.email_empty ?: "default" }}',
        'quantity_zero_elvis' => '{{ data.quantity_zero ?: 10 }}',
        'active_false_elvis' => '{{ data.active_false ?: true }}',
    ])
    ->skipNull(false)
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($data);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 4: Nested properties
echo "Example 4: Nested properties\n";
echo "-----------------------------\n";

$user = [
    'profile' => [
        'email' => null,
        'bio' => '',
    ],
];

$result = DataMapper::source(['user' => $user])
    ->template([
        'email' => '{{ user.profile.email ?? "default@example.com" }}',
        'bio' => '{{ user.profile.bio ?: "No bio available" }}',
    ])
    ->skipNull(false)
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($user);
echo "\nOutput:\n";
print_r($result);
echo "\n";

// Example 5: Combined with other features
echo "Example 5: Combined with wildcards and WHERE\n";
echo "---------------------------------------------\n";

$products = [
    ['name' => 'Laptop', 'price' => 1200, 'description' => null],
    ['name' => 'Mouse', 'price' => 25, 'description' => ''],
    ['name' => 'Monitor', 'price' => 350, 'description' => 'High quality display'],
];

$result = DataMapper::source(['products' => $products])
    ->template([
        'products.*' => [
            'name' => '{{ products.*.name }}',
            'price' => '{{ products.*.price }}',
            'description' => '{{ products.*.description ?? "No description available" }}',
            'summary' => '{{ products.*.description ?: "No summary" }}',
        ],
    ])
    ->map()
    ->getTarget();

echo "Input:\n";
print_r($products);
echo "\nOutput:\n";
print_r($result);
