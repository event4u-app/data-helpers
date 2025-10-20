<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromJson;

echo "=== SimpleDTO Visibility & Security Examples ===\n\n";

// Example 1: Hidden Attribute
echo "1. Hidden Attribute - Hide from both toArray() and JSON:\n";
echo str_repeat('-', 60) . "\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
        #[Hidden]
        public readonly string $apiKey,
    ) {}
}

$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'super-secret-password',
    'apiKey' => 'sk_live_1234567890',
]);

echo "Direct property access:\n";
echo sprintf('  Name: %s%s', $user->name, PHP_EOL);
echo sprintf('  Email: %s%s', $user->email, PHP_EOL);
echo sprintf('  Password: %s%s', $user->password, PHP_EOL);
echo "  API Key: {$user->apiKey}\n\n";

echo "toArray() output:\n";
print_r($user->toArray());
echo "\n";

echo "JSON output:\n";
echo json_encode($user, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: HiddenFromArray - Hide only from toArray()
echo "2. HiddenFromArray - Hide from toArray() only:\n";
echo str_repeat('-', 60) . "\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[HiddenFromArray]
        public readonly string $internalSku,
        #[HiddenFromArray]
        public readonly int $stockLevel,
    ) {}
}

$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
    'internalSku' => 'INT-LAP-001',
    'stockLevel' => 42,
]);

echo "toArray() output (internal fields hidden):\n";
print_r($product->toArray());
echo "\n";

echo "JSON output (internal fields visible):\n";
echo json_encode($product, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: HiddenFromJson - Hide only from JSON
echo "3. HiddenFromJson - Hide from JSON only:\n";
echo str_repeat('-', 60) . "\n";

class OrderDTO extends SimpleDTO {
    public function __construct(
        public readonly string $orderId,
        public readonly float $total,
        #[HiddenFromJson]
        public readonly string $debugInfo,
        #[HiddenFromJson]
        public readonly array $processingSteps,
    ) {}
}

$order = OrderDTO::fromArray([
    'orderId' => 'ORD-12345',
    'total' => 149.99,
    'debugInfo' => 'Processed in 0.5s',
    'processingSteps' => ['validate', 'charge', 'fulfill'],
]);

echo "toArray() output (debug info visible):\n";
print_r($order->toArray());
echo "\n";

echo "JSON output (debug info hidden):\n";
echo json_encode($order, JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Partial Serialization with only()
echo "4. Partial Serialization - only():\n";
echo str_repeat('-', 60) . "\n";

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $address,
    ) {}
}

$customer = CustomerDTO::fromArray([
    'id' => 'CUST-001',
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'phone' => '+1-555-0123',
    'address' => '123 Main St, NYC',
]);

echo "Full output:\n";
print_r($customer->toArray());
echo "\n";

echo "Only name and email:\n";
print_r($customer->only(['name', 'email'])->toArray());
echo "\n";

echo "Only id (JSON):\n";
echo json_encode($customer->only(['id']), JSON_PRETTY_PRINT) . "\n\n";

// Example 5: Partial Serialization with except()
echo "5. Partial Serialization - except():\n";
echo str_repeat('-', 60) . "\n";

echo "Exclude phone and address:\n";
print_r($customer->except(['phone', 'address'])->toArray());
echo "\n";

echo "Exclude email (JSON):\n";
echo json_encode($customer->except(['email']), JSON_PRETTY_PRINT) . "\n\n";

// Example 6: Combined Visibility Rules
echo "6. Combined Visibility Rules:\n";
echo str_repeat('-', 60) . "\n";

class SecureUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
        #[Hidden]
        public readonly string $apiKey,
        public readonly string $role,
    ) {}
}

$secureUser = SecureUserDTO::fromArray([
    'id' => 'USR-001',
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => 'secret',
    'apiKey' => 'key123',
    'role' => 'admin',
]);

echo "Full output (password & apiKey hidden):\n";
print_r($secureUser->toArray());
echo "\n";

echo "Only name and email (password still hidden):\n";
print_r($secureUser->only(['name', 'email', 'password'])->toArray());
echo "\n";

echo "Except role (password & apiKey still hidden):\n";
print_r($secureUser->except(['role'])->toArray());
echo "\n";

// Example 7: Real-World API Response
echo "7. Real-World API Response:\n";
echo str_repeat('-', 60) . "\n";

class ApiUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $email,
        #[Hidden]
        public readonly string $passwordHash,
        #[HiddenFromJson]
        public readonly string $internalNotes,
        public readonly string $createdAt,
    ) {}
}

$apiUser = ApiUserDTO::fromArray([
    'id' => 'USR-789',
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'passwordHash' => '$2y$10$...',
    'internalNotes' => 'VIP customer',
    'createdAt' => '2024-01-01',
]);

echo "Public API response (JSON):\n";
echo json_encode($apiUser, JSON_PRETTY_PRINT) . "\n\n";

echo "Internal database export (toArray):\n";
print_r($apiUser->toArray());
echo "\n";

echo "Public profile (only specific fields):\n";
echo json_encode($apiUser->only(['username', 'createdAt']), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  All visibility examples completed!\n";

