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

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo sprintf('Email: %s%s', $result['email'], PHP_EOL);
echo "Phone: " . ($result['phone'] ?? 'null') . "\n";
echo "Address: " . ($result['address'] ?? 'null') . "\n\n";

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

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo "Count: " . ($result['count'] ?? 'null') . "\n";
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

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo "Value: " . ($result['value'] ?? 'null') . "\n";
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

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo "IntZero: " . ($result['intZero'] ?? 'null') . "\n";
echo "StringZero: " . ($result['stringZero'] ?? 'null') . "\n\n";

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

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo "Active: " . (false === $result['active'] ? 'false' : (null === $result['active'] ? 'null' : 'true')) . "\n";
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

echo "Name: " . ($result['name'] ?? 'null') . "\n";
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

echo sprintf('ID: %s%s', $result['id'], PHP_EOL);
echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo sprintf('Email: %s%s', $result['email'], PHP_EOL);
echo "Phone: " . ($result['phone'] ?? 'null') . "\n";
echo "Address: " . ($result['address'] ?? 'null') . "\n";
echo "Bio: " . ($result['bio'] ?? 'null') . "\n";
echo "Tags: " . (null === $result['tags'] ? 'null' : json_encode($result['tags'])) . "\n";
echo "Score: " . ($result['score'] ?? 'null') . "\n\n";

// Example 7: Convert Boolean False
echo "Example 7: Convert Boolean False\n";
echo "---------------------------------\n";

$template = [
    'name' => '{{ data.name }}',
    'notifications' => '{{ data.notifications }}',  // No filter - keep false
    'newsletter' => '{{ data.newsletter | empty_to_null:"false" }}',  // Convert false to null
];

$data = [
    'data' => [
        'name' => 'John Doe',
        'notifications' => false,
        'newsletter' => false,
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo sprintf('Name: %s%s', $result['name'], PHP_EOL);
echo "Notifications: " . (null === $result['notifications'] ? 'null' : ($result['notifications'] ? 'true' : 'false')) . "\n";
echo "Newsletter: " . (null === $result['newsletter'] ? 'null' : ($result['newsletter'] ? 'true' : 'false')) . "\n\n";

// Example 8: Combine All Options
echo "Example 8: Combine All Options\n";
echo "-------------------------------\n";

$template = [
    'empty_string' => '{{ data.empty_string | empty_to_null:"zero,string_zero,false" }}',
    'empty_array' => '{{ data.empty_array | empty_to_null:"zero,string_zero,false" }}',
    'zero' => '{{ data.zero | empty_to_null:"zero,string_zero,false" }}',
    'string_zero' => '{{ data.string_zero | empty_to_null:"zero,string_zero,false" }}',
    'false' => '{{ data.false | empty_to_null:"zero,string_zero,false" }}',
    'true' => '{{ data.true | empty_to_null:"zero,string_zero,false" }}',
    'value' => '{{ data.value | empty_to_null:"zero,string_zero,false" }}',
];

$data = [
    'data' => [
        'empty_string' => '',
        'empty_array' => [],
        'zero' => 0,
        'string_zero' => '0',
        'false' => false,
        'true' => true,
        'value' => 'hello',
    ],
];

$result = DataMapper::source($data)->template($template)->skipNull(false)->map()->getTarget();

echo "Empty string: " . ($result['empty_string'] ?? 'null') . "\n";
echo "Empty array: " . (null === $result['empty_array'] ? 'null' : json_encode($result['empty_array'])) . "\n";
echo "Zero: " . ($result['zero'] ?? 'null') . "\n";
echo "String zero: " . ($result['string_zero'] ?? 'null') . "\n";
echo "False: " . (null === $result['false'] ? 'null' : ($result['false'] ? 'true' : 'false')) . "\n";
echo "True: " . (null === $result['true'] ? 'null' : ($result['true'] ? 'true' : 'false')) . "\n";
echo "Value: " . ($result['value'] ?? 'null') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
