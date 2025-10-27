<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\DataMapper;

echo "================================================================================\n";
echo "DataMapper - ConvertEmptyToNull Filter Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Usage - Default Behavior
echo "Example 1: Basic Usage - Default Behavior\n";
echo "------------------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'email' => '{{ data.email }}',
    'phone' => '{{ data.phone | empty_to_null }}',
    'address' => '{{ data.address | empty_to_null }}',
];

$data = [
    'data' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '',      // Empty string → null
        'address' => '',    // Empty string → null
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Name: {$result['name']}\n";
echo "Email: {$result['email']}\n";
echo "Phone: " . ($result['phone'] === null ? 'null' : $result['phone']) . "\n";
echo "Address: " . ($result['address'] === null ? 'null' : $result['address']) . "\n\n";

// Example 2: Convert Integer Zero
echo "Example 2: Convert Integer Zero\n";
echo "--------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'count' => '{{ data.count | empty_to_null:"zero" }}',
    'total' => '{{ data.total }}',  // No filter - keep zero
];

$data = [
    'data' => [
        'name' => 'Product',
        'count' => 0,   // Zero → null (with filter)
        'total' => 0,   // Zero → 0 (without filter)
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Name: {$result['name']}\n";
echo "Count: " . ($result['count'] === null ? 'null' : $result['count']) . "\n";
echo "Total: {$result['total']}\n\n";

// Example 3: Convert String Zero
echo "Example 3: Convert String Zero\n";
echo "-------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'value' => '{{ data.value | empty_to_null:"string_zero" }}',
    'code' => '{{ data.code }}',  // No filter - keep "0"
];

$data = [
    'data' => [
        'name' => 'Item',
        'value' => '0',   // String zero → null (with filter)
        'code' => '0',    // String zero → "0" (without filter)
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Name: {$result['name']}\n";
echo "Value: " . ($result['value'] === null ? 'null' : $result['value']) . "\n";
echo "Code: {$result['code']}\n\n";

// Example 4: Convert Both Zero Types
echo "Example 4: Convert Both Zero Types\n";
echo "-----------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'intZero' => '{{ data.intZero | empty_to_null:"zero,string_zero" }}',
    'stringZero' => '{{ data.stringZero | empty_to_null:"zero,string_zero" }}',
];

$data = [
    'data' => [
        'name' => 'Test',
        'intZero' => 0,      // Integer zero → null
        'stringZero' => '0', // String zero → null
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Name: {$result['name']}\n";
echo "IntZero: " . ($result['intZero'] === null ? 'null' : $result['intZero']) . "\n";
echo "StringZero: " . ($result['stringZero'] === null ? 'null' : $result['stringZero']) . "\n\n";

// Example 5: Boolean False is NOT Converted
echo "Example 5: Boolean False is NOT Converted\n";
echo "------------------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'active' => '{{ data.active | empty_to_null:"zero,string_zero" }}',
    'verified' => '{{ data.verified | empty_to_null:"zero,string_zero" }}',
];

$data = [
    'data' => [
        'name' => 'User',
        'active' => false,   // Boolean false → false (NOT null!)
        'verified' => true,  // Boolean true → true
    ],
];

$result = DataMapper::source($data)->template($template)->map()->getTarget();

echo "Name: {$result['name']}\n";
echo "Active: " . ($result['active'] === false ? 'false' : ($result['active'] === null ? 'null' : 'true')) . "\n";
echo "Verified: " . ($result['verified'] ? 'true' : 'false') . "\n\n";

// Example 6: Chaining with Other Filters
echo "Example 6: Chaining with Other Filters\n";
echo "---------------------------------------\n";

$template = [
    'name' => '{{ data.name | trim | empty_to_null }}',
    'email' => '{{ data.email | trim | lower | empty_to_null }}',
];

$data = [
    'data' => [
        'name' => '   ',           // Whitespace → trim → empty string → null
        'email' => '  TEST@EXAMPLE.COM  ',  // Trim + lowercase
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Name: " . ($result['name'] === null ? 'null' : $result['name']) . "\n";
echo "Email: {$result['email']}\n\n";

// Example 7: Real-World API Response Cleaning
echo "Example 7: Real-World API Response Cleaning\n";
echo "--------------------------------------------\n";

$template = [
    'id' => '{{ api.id }}',
    'name' => '{{ api.name }}',
    'email' => '{{ api.email }}',
    'phone' => '{{ api.phone | empty_to_null }}',
    'address' => '{{ api.address | empty_to_null }}',
    'bio' => '{{ api.bio | empty_to_null }}',
    'tags' => '{{ api.tags | empty_to_null }}',
    'score' => '{{ api.score | empty_to_null:"zero" }}',
];

$apiResponse = [
    'api' => [
        'id' => 123,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '',          // Empty optional field
        'address' => '',        // Empty optional field
        'bio' => '',            // Empty optional field
        'tags' => [],           // Empty array
        'score' => 0,           // Zero score → null
    ],
];

$result = DataMapper::source($apiResponse)->template($template)->skipNull(false)->map()->getTarget();

echo "ID: {$result['id']}\n";
echo "Name: {$result['name']}\n";
echo "Email: {$result['email']}\n";
echo "Phone: " . ($result['phone'] === null ? 'null' : $result['phone']) . "\n";
echo "Address: " . ($result['address'] === null ? 'null' : $result['address']) . "\n";
echo "Bio: " . ($result['bio'] === null ? 'null' : $result['bio']) . "\n";
echo "Tags: " . ($result['tags'] === null ? 'null' : json_encode($result['tags'])) . "\n";
echo "Score: " . ($result['score'] === null ? 'null' : $result['score']) . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

