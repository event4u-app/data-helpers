<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

echo "🔤  SimpleDTO Sorting Examples\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// Example 1: Basic Sorting
// ============================================================================

echo "📋  Example 1: Basic Sorting\n";
echo str_repeat('-', 80) . "\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $zebra = 'z',
        public readonly string $name = 'John',
        public readonly string $email = 'john@example.com',
        public readonly string $alpha = 'a',
    ) {}
}

$user = UserDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
]);

echo "Original (unsorted):\n";
print_r($user->toArray());

echo "\nSorted (ascending):\n";
print_r($user->sorted()->toArray());

echo "\nSorted (descending):\n";
print_r($user->sorted('desc')->toArray());

echo "\n";

// ============================================================================
// Example 2: Nested Sorting
// ============================================================================

echo "📋  Example 2: Nested Sorting\n";
echo str_repeat('-', 80) . "\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name = '',
        public readonly array $attributes = [],
        public readonly array $metadata = [],
    ) {}
}

$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'attributes' => [
        'weight' => '2kg',
        'color' => 'silver',
        'brand' => 'Apple',
    ],
    'metadata' => [
        'zebra' => 'z',
        'alpha' => 'a',
        'beta' => 'b',
    ],
]);

echo "Sorted (top level only):\n";
print_r($product->sorted()->toArray());

echo "\nSorted (with nested):\n";
print_r($product->sorted()->withNestedSort()->toArray());

echo "\n";

// ============================================================================
// Example 3: Custom Sort Callback
// ============================================================================

echo "📋  Example 3: Custom Sort Callback\n";
echo str_repeat('-', 80) . "\n";

class DataDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $a = '1',
        public readonly string $abc = '3',
        public readonly string $ab = '2',
        public readonly string $abcd = '4',
    ) {}
}

$data = DataDTO::fromArray([
    'a' => '1',
    'abc' => '3',
    'ab' => '2',
    'abcd' => '4',
]);

echo "Original:\n";
print_r($data->toArray());

echo "\nSorted by key length:\n";
$sorted = $data->sortedBy(fn($a, $b) => strlen($a) <=> strlen($b));
print_r($sorted->toArray());

echo "\nSorted reverse alphabetically:\n";
$sorted = $data->sortedBy(fn($a, $b) => strcmp($b, $a));
print_r($sorted->toArray());

echo "\n";

// ============================================================================
// Example 4: JSON Serialization
// ============================================================================

echo "📋  Example 4: JSON Serialization\n";
echo str_repeat('-', 80) . "\n";

$user = UserDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
]);

echo "Unsorted JSON:\n";
echo json_encode($user, JSON_PRETTY_PRINT) . "\n";

echo "\nSorted JSON:\n";
echo json_encode($user->sorted(), JSON_PRETTY_PRINT) . "\n";

echo "\n";

// ============================================================================
// Example 5: Deeply Nested Sorting
// ============================================================================

echo "📋  Example 5: Deeply Nested Sorting\n";
echo str_repeat('-', 80) . "\n";

class ConfigDTO extends SimpleDTO
{
    public function __construct(
        public readonly array $config = [],
    ) {}
}

$config = ConfigDTO::fromArray([
    'config' => [
        'zebra' => [
            'nested_z' => 'value_z',
            'nested_a' => 'value_a',
            'nested_m' => [
                'deep_z' => 'deep_z',
                'deep_a' => 'deep_a',
            ],
        ],
        'alpha' => [
            'nested_z' => 'value_z',
            'nested_a' => 'value_a',
        ],
    ],
]);

echo "Sorted with nested:\n";
print_r($config->sorted()->withNestedSort()->toArray());

echo "\n";

// ============================================================================
// Example 6: Immutability
// ============================================================================

echo "📋  Example 6: Immutability\n";
echo str_repeat('-', 80) . "\n";

$user = UserDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
]);

$sorted = $user->sorted();

echo "Original DTO (unchanged):\n";
print_r(array_keys($user->toArray()));

echo "\nSorted DTO:\n";
print_r(array_keys($sorted->toArray()));

echo "\n";

// ============================================================================
// Example 7: Chaining with Other Methods
// ============================================================================

echo "📋  Example 7: Chaining with Other Methods\n";
echo str_repeat('-', 80) . "\n";

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $zebra = 'z',
        public readonly string $name = 'John',
        public readonly string $email = 'john@example.com',
        public readonly string $alpha = 'a',
        public readonly string $password = 'secret',
    ) {}
}

$response = ApiResponseDTO::fromArray([
    'zebra' => 'z',
    'name' => 'John',
    'email' => 'john@example.com',
    'alpha' => 'a',
    'password' => 'secret',
]);

echo "Sorted + Only specific fields:\n";
print_r($response->sorted()->only(['name', 'email'])->toArray());

echo "\nSorted + Except password:\n";
print_r($response->sorted()->except(['password'])->toArray());

echo "\n";

echo "✅  All examples completed!\n";

