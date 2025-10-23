<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;
use event4u\DataHelpers\SimpleDTO\Enums\NamingConvention;

echo "\n";
echo "================================================================================\n";
echo "SimpleDTO - Property Mapping Examples\n";
echo "================================================================================\n";
echo "\n";

// Example 1: Simple Property Mapping
echo "Example 1: Simple Property Mapping (snake_case → camelCase)\n";
echo "--------------------------------------------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $userName,
        #[MapFrom('email_address')]
        public readonly string $emailAddress,
        #[MapFrom('phone_number')]
        public readonly string $phoneNumber,
    ) {
    }
}

$apiData = [
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com',
    'phone_number' => '+49123456789',
];

$user = UserDTO::fromArray($apiData);
echo "Input (snake_case):\n";
echo json_encode($apiData, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nDTO Properties (camelCase):\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('  userName: %s%s', $user->userName, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  emailAddress: %s%s', $user->emailAddress, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  phoneNumber: %s%s', $user->phoneNumber, PHP_EOL);
echo "\n";

// Example 2: Dot Notation for Nested Data
echo "Example 2: Dot Notation for Nested Data\n";
echo "--------------------------------------------------------------------------------\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('user.profile.email')]
        public readonly string $email,
        #[MapFrom('user.profile.age')]
        public readonly int $age,
        #[MapFrom('metadata.created')]
        public readonly string $createdAt,
    ) {
    }
}

$nestedData = [
    'user' => [
        'profile' => [
            'email' => 'john@example.com',
            'age' => 30,
        ],
    ],
    'metadata' => [
        'created' => '2024-01-15',
    ],
];

$profile = ProfileDTO::fromArray($nestedData);
echo "Input (nested structure):\n";
echo json_encode($nestedData, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nDTO Properties (flattened):\n";
echo sprintf('  email: %s%s', $profile->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  age: %s%s', $profile->age, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  createdAt: %s%s', $profile->createdAt, PHP_EOL);
echo "\n";

// Example 3: Multiple Sources with Fallback (Different API Formats)
echo "Example 3: Multiple Sources with Fallback (Different API Formats)\n";
echo "--------------------------------------------------------------------------------\n";

class FlexibleUserDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom(['user.email', 'email', 'emailAddress'])]
        public readonly string $email,
        #[MapFrom(['user.name', 'name', 'userName'])]
        public readonly string $name,
    ) {
    }
}

// API Format 1: Nested structure
$api1Data = [
    'user' => [
        'email' => 'john@api1.com',
        'name' => 'John from API 1',
    ],
];

$user1 = FlexibleUserDTO::fromArray($api1Data);
echo "API Format 1 (nested):\n";
echo json_encode($api1Data, JSON_PRETTY_PRINT) . PHP_EOL;
echo "Result: {$user1->name} ({$user1->email})\n\n";

// API Format 2: Flat structure
$api2Data = [
    'email' => 'jane@api2.com',
    'name' => 'Jane from API 2',
];

$user2 = FlexibleUserDTO::fromArray($api2Data);
echo "API Format 2 (flat):\n";
echo json_encode($api2Data, JSON_PRETTY_PRINT) . PHP_EOL;
echo "Result: {$user2->name} ({$user2->email})\n\n";

// API Format 3: camelCase
$api3Data = [
    'emailAddress' => 'bob@api3.com',
    'userName' => 'Bob from API 3',
];

$user3 = FlexibleUserDTO::fromArray($api3Data);
echo "API Format 3 (camelCase):\n";
echo json_encode($api3Data, JSON_PRETTY_PRINT) . PHP_EOL;
echo "Result: {$user3->name} ({$user3->email})\n";
echo "\n";

// Example 4: Integration with Casts
echo "Example 4: Integration with Casts (Mapping + Type Conversion)\n";
echo "--------------------------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('order_id')]
        public readonly int $orderId,
        #[MapFrom('is_paid')]
        public readonly bool $isPaid,
        #[MapFrom('created_at')]
        public readonly DateTimeImmutable $createdAt,
        #[MapFrom('total_amount')]
        public readonly string $totalAmount,
    ) {
    }

    protected function casts(): array
    {
        return [
            'orderId' => 'integer',
            'isPaid' => 'boolean',
            'createdAt' => 'datetime:Y-m-d H:i:s',
            'totalAmount' => 'decimal:2',
        ];
    }
}

$orderData = [
    'order_id' => '12345',           // String → int
    'is_paid' => '1',                // String → bool
    'created_at' => '2024-01-15 14:30:00',  // String → DateTimeImmutable
    'total_amount' => 99.99,         // Float → decimal string
];

$order = OrderDTO::fromArray($orderData);
echo "Input (mixed types):\n";
echo json_encode($orderData, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\nDTO Properties (typed & formatted):\n";
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo sprintf('  orderId: %s (', $order->orderId) . gettype($order->orderId) . ")\n";
/** @phpstan-ignore-next-line unknown */
echo "  isPaid: " . ($order->isPaid ? 'true' : 'false') . " (" . gettype($order->isPaid) . ")\n";
echo sprintf('  createdAt: %s (', $order->createdAt->format('Y-m-d H:i:s')) . $order->createdAt::class . ")\n";
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo sprintf('  totalAmount: %s (', $order->totalAmount) . gettype($order->totalAmount) . ")\n";
echo "\n";

// Example 5: Real-World Use Case - Multiple REST APIs
echo "Example 5: Real-World Use Case - Multiple REST APIs\n";
echo "--------------------------------------------------------------------------------\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom(['product.id', 'id', 'productId'])]
        public readonly int $id,
        #[MapFrom(['product.name', 'name', 'title'])]
        public readonly string $name,
        #[MapFrom(['product.price', 'price', 'amount'])]
        public readonly string $price,
        #[MapFrom(['product.stock', 'stock', 'quantity', 'available'])]
        public readonly int $stock,
    ) {
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }
}

// Shopify API Response
$shopifyData = [
    'product' => [
        'id' => 123,
        'name' => 'Awesome Product',
        'price' => 29.99,
        'stock' => 50,
    ],
];

$product1 = ProductDTO::fromArray($shopifyData);
echo "Shopify API Response:\n";
echo "  Product: {$product1->name} (ID: {$product1->id})\n";
echo "  Price: €{$product1->price}, Stock: {$product1->stock}\n\n";

// WooCommerce API Response
$wooCommerceData = [
    'id' => 456,
    'title' => 'Great Product',
    'amount' => 39.99,
    'available' => 25,
];

$product2 = ProductDTO::fromArray($wooCommerceData);
echo "WooCommerce API Response:\n";
echo "  Product: {$product2->name} (ID: {$product2->id})\n";
echo "  Price: €{$product2->price}, Stock: {$product2->stock}\n\n";

// Custom API Response
$customData = [
    'productId' => 789,
    'name' => 'Super Product',
    'price' => 49.99,
    'quantity' => 100,
];

$product3 = ProductDTO::fromArray($customData);
echo "Custom API Response:\n";
echo "  Product: {$product3->name} (ID: {$product3->id})\n";
echo sprintf('  Price: €%s, Stock: %d%s', $product3->price, $product3->stock, PHP_EOL);
echo "\n";

// Example 6: MapTo Attribute - Output Mapping
echo "Example 6: MapTo Attribute - Output Mapping\n";
echo "--------------------------------------------------------------------------------\n";

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        #[MapTo('user_id')]
        public readonly int $userId,
        #[MapTo('user_name')]
        public readonly string $userName,
        #[MapTo('email_address')]
        public readonly string $emailAddress,
    ) {
    }
}

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$dto = new ApiResponseDTO(
    /** @phpstan-ignore-next-line unknown */
    userId: 123,
    /** @phpstan-ignore-next-line unknown */
    userName: 'John Doe',
    /** @phpstan-ignore-next-line unknown */
    emailAddress: 'john@example.com'
);

echo "DTO Properties (camelCase):\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('  userId: %s%s', $dto->userId, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('  userName: %s%s', $dto->userName, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  emailAddress: {$dto->emailAddress}\n\n";

$output = $dto->toArray();
echo "Output (snake_case):\n";
echo json_encode($output, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 7: Nested Output with Dot Notation
echo "Example 7: Nested Output with Dot Notation\n";
echo "--------------------------------------------------------------------------------\n";

class NestedOutputDTO extends SimpleDTO
{
    public function __construct(
        #[MapTo('user.profile.email')]
        public readonly string $email,
        #[MapTo('user.profile.age')]
        public readonly int $age,
        #[MapTo('metadata.created')]
        public readonly string $createdAt,
    ) {
    }
}

$nestedDto = new NestedOutputDTO(
    email: 'john@example.com',
    age: 30,
    createdAt: '2024-01-15'
);

echo "DTO Properties (flat):\n";
echo sprintf('  email: %s%s', $nestedDto->email, PHP_EOL);
echo sprintf('  age: %d%s', $nestedDto->age, PHP_EOL);
echo "  createdAt: {$nestedDto->createdAt}\n\n";

$nestedOutput = $nestedDto->toArray();
echo "Output (nested structure):\n";
echo json_encode($nestedOutput, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 8: Bidirectional Mapping (MapFrom + MapTo)
echo "Example 8: Bidirectional Mapping (MapFrom + MapTo)\n";
echo "--------------------------------------------------------------------------------\n";

class BidirectionalDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom(['user.email', 'email'])]
        #[MapTo('contact.email')]
        public readonly string $email,
        #[MapFrom('user_name')]
        #[MapTo('contact.name')]
        public readonly string $name,
    ) {
    }
}

// Input: flat structure
$inputData = [
    'email' => 'john@example.com',
    'user_name' => 'John Doe',
];

echo "Input (flat structure):\n";
echo json_encode($inputData, JSON_PRETTY_PRINT) . PHP_EOL;

$bidirectionalDto = BidirectionalDTO::fromArray($inputData);
echo "\nDTO Properties:\n";
echo sprintf('  email: %s%s', $bidirectionalDto->email, PHP_EOL);
echo sprintf('  name: %s%s', $bidirectionalDto->name, PHP_EOL);

// Output: nested structure
$bidirectionalOutput = $bidirectionalDto->toArray();
echo "\nOutput (nested structure):\n";
echo json_encode($bidirectionalOutput, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 9: JSON Serialization with MapTo
echo "Example 9: JSON Serialization with MapTo\n";
echo "--------------------------------------------------------------------------------\n";

class JsonDTO extends SimpleDTO
{
    public function __construct(
        #[MapTo('user_id')]
        public readonly int $userId,
        #[MapTo('user_name')]
        public readonly string $userName,
        #[MapTo('is_active')]
        public readonly bool $isActive,
    ) {
    }
}

$jsonDto = new JsonDTO(
    userId: 456,
    userName: 'Jane Doe',
    isActive: true
);

$json = json_encode($jsonDto, JSON_PRETTY_PRINT);
echo "JSON Output:\n";
echo $json;
echo "\n\n";

// Example 10: Mapping Configuration Inspection
echo "Example 10: Mapping Configuration Inspection\n";
echo "--------------------------------------------------------------------------------\n";

$inputMappingConfig = ProductDTO::getMappingConfig();
echo "ProductDTO Input Mapping Configuration:\n";
foreach ($inputMappingConfig as $property => $sources) {
    $sourcesStr = is_array($sources) ? implode(', ', $sources) : $sources;
    echo "  {$property} ← [{$sourcesStr}]\n";
}
echo "\n";

$outputMappingConfig = BidirectionalDTO::getOutputMappingConfig();
echo "BidirectionalDTO Output Mapping Configuration:\n";
foreach ($outputMappingConfig as $property => $target) {
    echo sprintf('  %s → %s%s', $property, $target, PHP_EOL);
}
echo "\n";

// Example 11: MapInputName - Automatic Input Transformation
echo "Example 11: MapInputName - Automatic Input Transformation\n";
echo "--------------------------------------------------------------------------------\n";
echo "💡 Tip: Use NamingConvention enum for type-safe naming conventions!\n";
echo "    Available: SnakeCase, CamelCase, KebabCase, PascalCase\n\n";

#[MapInputName(NamingConvention::SnakeCase)]  // ✨ Using enum instead of string!
class UserInputDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
        public readonly int $userId,
        public readonly bool $isActive,
    ) {
    }
}

$snakeCaseInput = [
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com',
    'user_id' => 123,
    'is_active' => true,
];

echo "Input (snake_case):\n";
echo json_encode($snakeCaseInput, JSON_PRETTY_PRINT) . PHP_EOL;

$userDto = UserInputDTO::fromArray($snakeCaseInput);
echo "\nDTO Properties (camelCase):\n";
echo sprintf('  userName: %s%s', $userDto->userName, PHP_EOL);
echo sprintf('  emailAddress: %s%s', $userDto->emailAddress, PHP_EOL);
echo sprintf('  userId: %d%s', $userDto->userId, PHP_EOL);
echo "  isActive: " . ($userDto->isActive ? 'true' : 'false') . "\n";
echo "\n";

// Example 12: MapOutputName - Automatic Output Transformation
echo "Example 12: MapOutputName - Automatic Output Transformation\n";
echo "--------------------------------------------------------------------------------\n";
echo "💡 Tip: Enum provides IDE autocomplete and prevents typos!\n\n";

#[MapOutputName(NamingConvention::SnakeCase)]  // ✨ Using enum!
class UserOutputDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
        public readonly int $userId,
        public readonly bool $isActive,
    ) {
    }
}

$userOutputDto = new UserOutputDTO(
    userName: 'Jane Doe',
    emailAddress: 'jane@example.com',
    userId: 456,
    isActive: false
);

echo "DTO Properties (camelCase):\n";
echo sprintf('  userName: %s%s', $userOutputDto->userName, PHP_EOL);
echo sprintf('  emailAddress: %s%s', $userOutputDto->emailAddress, PHP_EOL);
echo sprintf('  userId: %d%s', $userOutputDto->userId, PHP_EOL);
echo "  isActive: " . ($userOutputDto->isActive ? 'true' : 'false') . "\n";

$snakeCaseOutput = $userOutputDto->toArray();
echo "\nOutput (snake_case):\n";
echo json_encode($snakeCaseOutput, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 13: Combined MapInputName and MapOutputName
echo "Example 13: Combined MapInputName and MapOutputName\n";
echo "--------------------------------------------------------------------------------\n";
echo "💡 Tip: Mix different conventions for input and output!\n\n";

#[MapInputName(NamingConvention::SnakeCase)]   // ✨ Input: snake_case
#[MapOutputName(NamingConvention::KebabCase)]  // ✨ Output: kebab-case
class TransformDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
    ) {
    }
}

echo "Input (snake_case):\n";
$transformInput = [
    'user_name' => 'Bob Smith',
    'email_address' => 'bob@example.com',
];
echo json_encode($transformInput, JSON_PRETTY_PRINT) . PHP_EOL;

$transformDto = TransformDTO::fromArray($transformInput);
echo "\nDTO Properties (camelCase):\n";
echo sprintf('  userName: %s%s', $transformDto->userName, PHP_EOL);
echo sprintf('  emailAddress: %s%s', $transformDto->emailAddress, PHP_EOL);

$kebabOutput = $transformDto->toArray();
echo "\nOutput (kebab-case):\n";
echo json_encode($kebabOutput, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 14: MapInputName/MapOutputName with MapFrom/MapTo Override
echo "Example 14: MapInputName/MapOutputName with MapFrom/MapTo Override\n";
echo "--------------------------------------------------------------------------------\n";

#[MapInputName('snake_case')]
#[MapOutputName('snake_case')]
class OverrideDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('custom_email_input')]
        #[MapTo('custom_email_output')]
        public readonly string $email,
        public readonly string $userName,
    ) {
    }
}

echo "Input (snake_case with custom override):\n";
$overrideInput = [
    'custom_email_input' => 'override@example.com',
    'user_name' => 'Override User',
];
echo json_encode($overrideInput, JSON_PRETTY_PRINT) . PHP_EOL;

$overrideDto = OverrideDTO::fromArray($overrideInput);
echo "\nDTO Properties:\n";
echo sprintf('  email: %s%s', $overrideDto->email, PHP_EOL);
echo sprintf('  userName: %s%s', $overrideDto->userName, PHP_EOL);

$overrideOutput = $overrideDto->toArray();
echo "\nOutput (snake_case with custom override):\n";
echo json_encode($overrideOutput, JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Note: 'email' uses custom mapping (MapFrom/MapTo), 'userName' uses automatic transformation (MapInputName/MapOutputName)\n";
echo "\n";

echo "================================================================================\n";
echo "✅  All examples completed successfully!\n";
echo "================================================================================\n";
echo "\n";
