<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                         WITH() METHOD                                      ║\n";
echo "║                    Phase 17.2 - Dynamic Properties                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Example 1: Basic with() usage
echo "1. BASIC WITH() USAGE - ADD SINGLE PROPERTY:\n";
echo "------------------------------------------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$user = new UserDto('John Doe', 'john@example.com');

echo "Original Dto:\n";
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nWith additional 'role' property:\n";
echo json_encode($user->with('role', 'admin')->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Original Dto is not modified\n";
echo "✅  Additional property is added to output\n";

echo "\n";

// Example 2: Add multiple properties
echo "2. ADD MULTIPLE PROPERTIES - ARRAY SYNTAX:\n";
echo "------------------------------------------------------------\n";

$userWithMetadata = $user->with([
    'role' => 'admin',
    'status' => 'active',
    'level' => 5,
    'verified' => true,
]);

echo "User with metadata:\n";
echo json_encode($userWithMetadata->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Multiple properties added at once\n";
echo "✅  Clean and readable syntax\n";

echo "\n";

// Example 3: Chaining with() calls
echo "3. CHAINING WITH() CALLS:\n";
echo "------------------------------------------------------------\n";

$userChained = $user
    ->with('role', 'admin')
    ->with('status', 'active')
    ->with('lastLogin', '2024-01-15 10:30:00');

echo "User with chained properties:\n";
echo json_encode($userChained->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Fluent interface for building complex outputs\n";
echo "✅  Each call returns a new instance\n";

echo "\n";

// Example 4: Lazy evaluation with callbacks
echo "4. LAZY EVALUATION - CALLBACKS:\n";
echo "------------------------------------------------------------\n";

class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly float $taxRate = 0.19,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$product = new ProductDto('Laptop', 999.99, 0.19);

$productWithCalculations = $product->with([
    'priceWithTax' => fn($dto): float => round($dto->price * (1 + $dto->taxRate), 2),
    'tax' => fn($dto): float => round($dto->price * $dto->taxRate, 2),
    'discount10' => fn($dto): float => round($dto->price * 0.9, 2),
]);

echo "Product with calculated values:\n";
echo json_encode($productWithCalculations->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Callbacks are evaluated lazily\n";
echo "✅  Access to Dto properties in callbacks\n";
echo "✅  Perfect for computed values\n";

echo "\n";

// Example 5: Nested Dtos
echo "5. NESTED Dtos - AUTOMATIC CONVERSION:\n";
echo "------------------------------------------------------------\n";

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class CustomerDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$address = new AddressDto('123 Main St', 'New York', 'USA');
/** @phpstan-ignore-next-line unknown */
$customer = new CustomerDto('Jane Doe', 'jane@example.com');

$customerWithAddress = $customer->with('address', $address);

echo "Customer with nested address:\n";
echo json_encode($customerWithAddress->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Nested Dtos are automatically converted to arrays\n";
echo "✅  Clean nested structure\n";

echo "\n";

// Example 6: API Response with metadata
echo "6. API RESPONSE WITH METADATA:\n";
echo "------------------------------------------------------------\n";

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly float $total,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$order = new OrderDto('ORD-12345', 'completed', 299.99);

$apiResponse = $order->with([
    'meta' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        /** @phpstan-ignore-next-line unknown */
        'requestId' => uniqid(),
    ],
    'links' => [
        'self' => '/api/orders/ORD-12345',
        'customer' => '/api/customers/123',
        'items' => '/api/orders/ORD-12345/items',
    ],
]);

echo "API Response:\n";
echo json_encode($apiResponse->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Perfect for API responses with metadata\n";
echo "✅  Add links, timestamps, versions, etc.\n";

echo "\n";

// Example 7: Conditional data
echo "7. CONDITIONAL DATA - DYNAMIC PROPERTIES:\n";
echo "------------------------------------------------------------\n";

class ArticleDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly bool $isPremium = false,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$article = new ArticleDto('Premium Article', 'This is premium content...', true);

$publicArticle = $article->with([
    'preview' => fn($dto) => $dto->isPremium ? substr((string)$dto->content, 0, 50) . '...' : $dto->content,
    'requiresSubscription' => fn($dto) => $dto->isPremium,
]);

echo "Article with conditional data:\n";
echo json_encode($publicArticle->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Add properties based on conditions\n";
echo "✅  Perfect for public vs. private data\n";

echo "\n";

// Example 8: JSON Serialization
echo "8. JSON SERIALIZATION:\n";
echo "------------------------------------------------------------\n";

$userJson = $user->with([
    'role' => 'admin',
    'permissions' => ['read', 'write', 'delete'],
    'metadata' => [
        'lastLogin' => '2024-01-15 10:30:00',
        'loginCount' => 42,
    ],
]);

echo "JSON output:\n";
echo json_encode($userJson, JSON_PRETTY_PRINT);

echo "\n\n✅  Works seamlessly with json_encode()\n";
echo "✅  All additional data is included\n";
echo "✅  Callbacks are evaluated\n";

echo "\n";

// Example 9: Combining with other features
echo "9. COMBINING WITH OTHER FEATURES:\n";
echo "------------------------------------------------------------\n";

class ReportDto extends SimpleDto
{
    /** @param array<mixed> $data */
    public function __construct(
        public readonly string $title,
        public readonly array $data,
    ) {}
}

$report = new ReportDto('Sales Report', ['jan' => 1000, 'feb' => 1200, 'mar' => 1500]);

$enrichedReport = $report
    ->with('total', fn($dto): float|int => array_sum($dto->data))
    ->with('average', fn($dto): float => round(array_sum($dto->data) / count($dto->data), 2))
    ->with('generated', date('Y-m-d H:i:s'))
    ->wrap('report');

echo "Enriched and wrapped report:\n";
echo json_encode($enrichedReport->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Combines with wrap() method\n";
echo "✅  Combines with other Dto features\n";
echo "✅  Flexible and powerful\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  with(\$key, \$value) - Add single property\n";
echo "✅  with([\$key => \$value]) - Add multiple properties\n";
echo "✅  Chainable - Multiple with() calls\n";
echo "✅  Lazy evaluation - Callbacks for computed values\n";
echo "✅  Nested Dtos - Automatic conversion to arrays\n";
echo "✅  JSON serialization - Works with json_encode()\n";
echo "✅  Immutable - Original Dto is not modified\n";
echo "✅  Flexible - Perfect for API responses, metadata, computed values\n";

echo "\n";
