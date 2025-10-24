<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\SimpleDTO;

echo "=== DataAccessor Structure Introspection Examples ===\n\n";

// Example 1: Basic Structure Analysis
echo "Example 1: Basic Structure Analysis\n";
echo "------------------------------------\n";

$data = [
    'name' => 'John Doe',
    'age' => 30,
    'active' => true,
    'balance' => 1234.56,
    'tags' => ['php', 'laravel', 'symfony'],
];

$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

echo "Data:\n";
print_r($data);

echo "\nStructure (flat):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n";

// Example 2: Nested Arrays with Wildcards
echo "Example 2: Nested Arrays with Wildcards\n";
echo "----------------------------------------\n";

$data = [
    'users' => [
        ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 25],
        ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => 35],
    ],
];

$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

echo "Structure (flat with wildcards):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n";

// Example 3: Multidimensional Structure
echo "Example 3: Multidimensional Structure\n";
echo "-------------------------------------\n";

$accessor = new DataAccessor($data);
$structure = $accessor->getStructureMultidimensional();

echo "Structure (multidimensional):\n";
print_r($structure);

echo "\n";

// Example 4: Union Types
echo "Example 4: Union Types for Mixed Values\n";
echo "----------------------------------------\n";

$data = [
    'values' => [
        'string value',
        42,
        null,
        true,
        3.14,
    ],
];

$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

echo "Data with mixed types:\n";
print_r($data);

echo "\nStructure (shows union types):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n";

// Example 5: Deeply Nested Structures
echo "Example 5: Deeply Nested Structures\n";
echo "------------------------------------\n";

$data = [
    'company' => [
        'name' => 'Acme Corp',
        'departments' => [
            [
                'name' => 'Engineering',
                'employees' => [
                    ['name' => 'Alice', 'age' => 30],
                    ['name' => 'Bob', 'age' => 25],
                ],
            ],
            [
                'name' => 'Sales',
                'employees' => [
                    ['name' => 'Charlie', 'age' => 35],
                ],
            ],
        ],
    ],
];

$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

echo "Structure (deeply nested with multiple wildcards):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n";

// Example 6: Working with DTOs
echo "Example 6: Working with DTOs\n";
echo "-----------------------------\n";

class EmailDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $type,
        public readonly bool $verified = false,
    ) {}
}

class UserDTO extends SimpleDTO
{
    /** @param array<int, EmailDTO> $emails */
    public function __construct(
        public readonly string $name,
        public readonly array $emails,
    ) {}
}

$dto = new UserDTO(
    name: 'John Doe',
    emails: [
        new EmailDTO(email: 'john@work.com', type: 'work', verified: true),
        new EmailDTO(email: 'john@home.com', type: 'home', verified: false),
    ]
);

$accessor = new DataAccessor($dto);
$structure = $accessor->getStructure();

echo "Structure (with DTO class names):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n";

// Example 7: API Response Validation
echo "Example 7: API Response Validation\n";
echo "-----------------------------------\n";

$apiResponse = [
    'status' => 'success',
    'data' => [
        'users' => [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ],
    ],
];

$expectedStructure = [
    'status' => 'string',
    'data' => 'array',
    'data.users' => 'array',
    'data.users.*' => 'array',
    'data.users.*.id' => 'int',
    'data.users.*.name' => 'string',
    'data.users.*.email' => 'string',
];

$accessor = new DataAccessor($apiResponse);
$actualStructure = $accessor->getStructure();

echo "Expected structure:\n";
foreach ($expectedStructure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\nActual structure:\n";
foreach ($actualStructure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\nValidation:\n";
$isValid = true;
foreach ($expectedStructure as $path => $expectedType) {
    if (!isset($actualStructure[$path])) {
        echo sprintf('  ❌  Missing path: %s%s', $path, PHP_EOL);
        $isValid = false;
    } elseif ($actualStructure[$path] !== $expectedType) {
        echo "  ❌  Type mismatch at '{$path}': expected '{$expectedType}', got '{$actualStructure[$path]}'\n";
        $isValid = false;
    } else {
        echo "  ✅  '{$path}' => '{$expectedType}'\n";
    }
}

if ($isValid) {
    echo "\n✅  API response structure is valid!\n";
} else {
    echo "\n❌  API response structure is invalid!\n";
}

echo "\n";

// Example 8: Mixed Nested Structures
echo "Example 8: Mixed Nested Structures\n";
echo "-----------------------------------\n";

$data = [
    'items' => [
        ['name' => 'Item 1', 'price' => 10.5],
        ['name' => 'Item 2', 'price' => 20],
        ['name' => 'Item 3', 'price' => null],
    ],
];

$accessor = new DataAccessor($data);
$structure = $accessor->getStructure();

echo "Structure (with union types for nullable values):\n";
foreach ($structure as $path => $type) {
    echo "  '{$path}' => '{$type}'\n";
}

echo "\n=== Examples Complete ===\n";
