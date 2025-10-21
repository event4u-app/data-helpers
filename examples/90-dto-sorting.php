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
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted (ascending):\n";
echo json_encode($user->sorted()->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted (descending):\n";
echo json_encode($user->sorted('desc')->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

// ============================================================================
// Example 2: Nested Sorting
// ============================================================================

echo "📋  Example 2: Nested Sorting\n";
echo str_repeat('-', 80) . "\n";

class ProductDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $attributes
     * @param array<mixed> $metadata
     */
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
echo json_encode($product->sorted()->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted (with nested):\n";
echo json_encode($product->sorted()->withNestedSort()->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

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
echo json_encode($data->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted by key length:\n";
$sorted = $data->sortedBy(fn($a, $b): int => strlen((string)$a) <=> strlen((string)$b));
echo json_encode($sorted->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted reverse alphabetically:\n";
$sorted = $data->sortedBy(fn($a, $b): int => strcmp((string)$b, (string)$a));
echo json_encode($sorted->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

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
    /** @param array<mixed> $config */
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
echo json_encode($config->sorted()->withNestedSort()->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

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
echo json_encode(array_keys($user->toArray()), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted DTO:\n";
echo json_encode(array_keys($sorted->toArray()), JSON_PRETTY_PRINT) . PHP_EOL;

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
echo json_encode($response->sorted()->only(['name', 'email'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSorted + Except password:\n";
echo json_encode($response->sorted()->except(['password'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n";

echo "✅  All examples completed!\n";

