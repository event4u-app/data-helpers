<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenFalse;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenNotNull;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenTrue;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    CONDITIONAL PROPERTIES                                  ║\n";
echo "║                    Phase 17.1 - Core Attributes                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Example 1: WhenNotNull - Only include when value is present
echo "1. WHEN NOT NULL - ONLY INCLUDE WHEN VALUE IS PRESENT:\n";
echo "------------------------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[WhenNotNull]
        public readonly ?string $phone = null,

        #[WhenNotNull]
        public readonly ?string $website = null,
    ) {}
}

$user1 = new UserDTO('John Doe', 'john@example.com', '555-1234', null);
$user2 = new UserDTO('Jane Doe', 'jane@example.com', null, null);

echo "User 1 (with phone):\n";
print_r($user1->toArray());

echo "\nUser 2 (without phone):\n";
print_r($user2->toArray());

echo "\n✅  Phone is only included when it has a value\n";
echo "✅  Website is excluded when null\n";

echo "\n";

// Example 2: WhenTrue/WhenFalse - Boolean conditions
echo "2. WHEN TRUE/FALSE - BOOLEAN CONDITIONS:\n";
echo "------------------------------------------------------------\n";

class FeatureDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenTrue]
        public readonly bool $isPremium = false,

        #[WhenFalse]
        public readonly bool $isDisabled = false,
    ) {}
}

$feature1 = new FeatureDTO('Feature A', true, false);
$feature2 = new FeatureDTO('Feature B', false, true);

echo "Feature 1 (premium, enabled):\n";
print_r($feature1->toArray());

echo "\nFeature 2 (not premium, disabled):\n";
print_r($feature2->toArray());

echo "\n✅  isPremium is only included when true\n";
echo "✅  isDisabled is only included when true (false value)\n";

echo "\n";

// Example 3: WhenEquals - Value comparison
echo "3. WHEN EQUALS - VALUE COMPARISON:\n";
echo "------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,

        #[WhenEquals('completed')]
        public readonly string $status = 'pending',

        #[WhenEquals('express')]
        public readonly string $shippingType = 'standard',
    ) {}
}

$order1 = new OrderDTO('ORD-001', 'completed', 'express');
$order2 = new OrderDTO('ORD-002', 'pending', 'standard');

echo "Order 1 (completed, express):\n";
print_r($order1->toArray());

echo "\nOrder 2 (pending, standard):\n";
print_r($order2->toArray());

echo "\n✅  Status is only included when 'completed'\n";
echo "✅  ShippingType is only included when 'express'\n";

echo "\n";

// Example 4: WhenIn - Value in list
echo "4. WHEN IN - VALUE IN LIST:\n";
echo "------------------------------------------------------------\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[WhenIn(['active', 'featured'])]
        public readonly string $status = 'draft',
    ) {}
}

$product1 = new ProductDTO('Product A', 99.99, 'active');
$product2 = new ProductDTO('Product B', 149.99, 'draft');

echo "Product 1 (active):\n";
print_r($product1->toArray());

echo "\nProduct 2 (draft):\n";
print_r($product2->toArray());

echo "\n✅  Status is only included when 'active' or 'featured'\n";
echo "✅  Draft status is excluded\n";

echo "\n";

// Example 5: WhenValue - Field comparison
echo "5. WHEN VALUE - FIELD COMPARISON:\n";
echo "------------------------------------------------------------\n";

class PremiumProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[WhenValue('price', '>', 100)]
        public readonly ?string $premiumBadge = 'PREMIUM',
    ) {}
}

$product1 = new PremiumProductDTO('Expensive Product', 150.0);
$product2 = new PremiumProductDTO('Cheap Product', 50.0);

echo "Product 1 (price > 100):\n";
print_r($product1->toArray());

echo "\nProduct 2 (price <= 100):\n";
print_r($product2->toArray());

echo "\n✅  Premium badge is only included when price > 100\n";

echo "\n";

// Example 6: Complex scenario with multiple conditions
echo "6. COMPLEX SCENARIO - MULTIPLE CONDITIONS:\n";
echo "------------------------------------------------------------\n";

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,

        #[WhenTrue]
        public readonly bool $hasData = false,

        #[WhenNotNull]
        public readonly ?array $data = null,

        #[WhenFalse]
        public readonly bool $hasError = false,

        #[WhenNotNull]
        public readonly ?string $error = null,
    ) {}
}

$successResponse = new ApiResponseDTO(
    success: true,
    message: 'Data retrieved successfully',
    hasData: true,
    data: ['id' => 1, 'name' => 'John'],
    hasError: false,
    error: null
);

$errorResponse = new ApiResponseDTO(
    success: false,
    message: 'An error occurred',
    hasData: false,
    data: null,
    hasError: true,
    error: 'Database connection failed'
);

echo "Success Response:\n";
print_r($successResponse->toArray());

echo "\nError Response:\n";
print_r($errorResponse->toArray());

echo "\n✅  Success response only includes data-related fields\n";
echo "✅  Error response only includes error-related fields\n";
echo "✅  Clean API responses without unnecessary fields\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  WhenNotNull - Include property only when not null\n";
echo "✅  WhenNull - Include property only when null\n";
echo "✅  WhenTrue - Include property only when true\n";
echo "✅  WhenFalse - Include property only when false\n";
echo "✅  WhenEquals - Include property when it equals a specific value\n";
echo "✅  WhenIn - Include property when value is in a list\n";
echo "✅  WhenValue - Include property based on another field's value\n";
echo "✅  WhenCallback - Include property based on custom logic\n";
echo "✅  All conditions work with toArray() and jsonSerialize()\n";
echo "✅  Perfect for flexible API responses\n";

echo "\n";

