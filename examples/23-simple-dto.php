<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

// Example 1: Basic DTO with required properties
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

echo "Example 1: Basic DTO\n";
echo str_repeat('=', 50) . "\n\n";

$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

echo sprintf('User Name: %s%s', $user->name, PHP_EOL);
echo sprintf('User Email: %s%s', $user->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "User Age: {$user->age}\n\n";

echo "As Array:\n";
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs JSON:\n";
echo json_encode($user, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: DTO with optional properties
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
    ) {}
}

echo "\nExample 2: DTO with Optional Properties\n";
echo str_repeat('=', 50) . "\n\n";

$product1 = ProductDTO::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
    'description' => 'High-performance laptop',
]);

echo "Product 1:\n";
echo sprintf('  Name: %s%s', $product1->name, PHP_EOL);
echo sprintf('  Price: $%s%s', $product1->price, PHP_EOL);
echo sprintf('  Description: %s%s', $product1->description, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  Category: " . ($product1->category ?? 'N/A') . "\n\n";

$product2 = ProductDTO::fromArray([
    'name' => 'Mouse',
    'price' => 29.99,
]);

echo "Product 2:\n";
echo sprintf('  Name: %s%s', $product2->name, PHP_EOL);
echo sprintf('  Price: $%s%s', $product2->price, PHP_EOL);
echo "  Description: " . ($product2->description ?? 'N/A') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  Category: " . ($product2->category ?? 'N/A') . "\n\n";

// Example 3: Nested DTOs
class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly AddressDTO $address,
    ) {}
}

echo "\nExample 3: Nested DTOs\n";
echo str_repeat('=', 50) . "\n\n";

$address = AddressDTO::fromArray([
    'street' => '123 Main St',
    'city' => 'New York',
    'zipCode' => '10001',
    'country' => 'USA',
]);

/** @phpstan-ignore-next-line unknown */
$customer = new CustomerDTO(
    name: 'Jane Smith',
    email: 'jane@example.com',
    /** @phpstan-ignore-next-line unknown */
    address: $address,
);

echo sprintf('Customer: %s%s', $customer->name, PHP_EOL);
echo sprintf('Email: %s%s', $customer->email, PHP_EOL);
echo "Address:\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('  %s%s', $customer->address->street, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo sprintf('  %s, %s%s', $customer->address->city, $customer->address->zipCode, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  {$customer->address->country}\n\n";

echo "As JSON:\n";
echo json_encode($customer, JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Using DTOs with DataMapper
use event4u\DataHelpers\DataMapper;

echo "\nExample 4: Using DTOs with DataMapper\n";
echo str_repeat('=', 50) . "\n\n";

$apiResponse = [
    'user' => [
        'full_name' => 'Alice Johnson',
        'email_address' => 'alice@example.com',
        'years_old' => 28,
    ],
];

$mappedData = DataMapper::source($apiResponse)
    ->template([
        'name' => '{{ user.full_name }}',
        'email' => '{{ user.email_address }}',
        'age' => '{{ user.years_old }}',
    ])
    ->map()
    ->toArray();

/** @phpstan-ignore-next-line unknown */
$userDto = UserDTO::fromArray($mappedData);

echo "Mapped User DTO:\n";
echo sprintf('  Name: %s%s', $userDto->name, PHP_EOL);
echo sprintf('  Email: %s%s', $userDto->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  Age: {$userDto->age}\n\n";

// Example 5: Array of DTOs
echo "\nExample 5: Array of DTOs\n";
echo str_repeat('=', 50) . "\n\n";

$productsData = [
    ['name' => 'Keyboard', 'price' => 79.99, 'category' => 'Electronics'],
    ['name' => 'Monitor', 'price' => 299.99, 'category' => 'Electronics'],
    ['name' => 'Desk', 'price' => 199.99, 'category' => 'Furniture'],
];

$products = array_map(
    ProductDTO::fromArray(...),
    $productsData
);

echo "Products:\n";
foreach ($products as $product) {
    /** @phpstan-ignore-next-line unknown */
    echo "  - {$product->name}: \${$product->price} ({$product->category})\n";
}

echo "\nAs JSON Array:\n";
echo json_encode($products, JSON_PRETTY_PRINT) . "\n";
