<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\MappedDataModel;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToInteger;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToFloat;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\CastToBoolean;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\ConvertEmptyToNull;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\RemoveNullValues;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\StripTags;

// Example 1: User Registration Model
class UserRegistrationModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'first_name' => '{{ request.first_name }}',
            'last_name' => '{{ request.last_name }}',
            'age' => '{{ request.age }}',
            'is_active' => true,
            'registered_at' => '{{ request.timestamp }}',
        ];
    }

    // Custom getters with transformation
    public function getEmail(): string
    {
        return strtolower(trim($this->email ?? ''));
    }

    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getAge(): int
    {
        return (int) ($this->age ?? 0);
    }

    public function getRegisteredAt(): int
    {
        return $this->registered_at ?? time();
    }
}

// Example 2: Product Update Model
class ProductUpdateModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'name' => '{{ request.product_name }}',
            'price' => '{{ request.price }}',
            'sku' => '{{ request.sku }}',
            'quantity' => '{{ request.quantity }}',
            'tags' => '{{ request.tags }}',
        ];
    }

    public function getName(): string
    {
        return trim($this->name ?? '');
    }

    public function getPrice(): float
    {
        return (float) ($this->price ?? 0);
    }

    public function getSku(): string
    {
        return strtoupper(trim($this->sku ?? ''));
    }

    public function isInStock(): bool
    {
        return ($this->quantity ?? 0) > 0;
    }
}

// Example 3: Complex Nested Order Model
class OrderModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'customer' => [
                'email' => '{{ request.customer_email }}',
                'name' => '{{ request.customer_name }}',
            ],
            'items' => '{{ request.items }}',
            'total' => '{{ request.total }}',
            'currency' => '{{ request.currency }}',
            'status' => 'pending',
        ];
    }

    public function getCustomerEmail(): string
    {
        $customer = $this->customer ?? [];
        return strtolower(trim($customer['email'] ?? ''));
    }

    public function getTotal(): float
    {
        return (float) ($this->total ?? 0);
    }

    public function getCurrency(): string
    {
        return strtoupper($this->currency ?? 'EUR');
    }
}

echo "=== Example 1: User Registration ===\n\n";

$requestData = [
    'email' => '  ALICE@EXAMPLE.COM  ',
    'first_name' => 'Alice',
    'last_name' => 'Smith',
    'age' => '30',
    'timestamp' => time(),
];

$user = new UserRegistrationModel($requestData);

echo "Mapped Data:\n";
print_r($user->toArray());

echo "\nAccess via magic getter (raw):\n";
echo "Email (raw): {$user->email}\n";
echo "First name: {$user->first_name}\n";
echo "Last name: {$user->last_name}\n";

echo "\nAccess via custom getters (transformed):\n";
echo "Email (clean): " . $user->getEmail() . "\n";
echo "Full name: " . $user->getFullName() . "\n";
echo "Age (int): " . $user->getAge() . "\n";

echo "\nOriginal Data:\n";
echo "Original email: " . $user->getOriginal('email') . "\n";
echo "Original age type: " . gettype($user->getOriginal('age')) . "\n";

echo "\n=== Example 2: Product Update ===\n\n";

$productData = [
    'product_name' => '  Gaming Mouse  ',
    'price' => '49.99',
    'sku' => 'gm-001',
    'quantity' => 15,
    'tags' => ['gaming', 'peripherals'],
];

$product = new ProductUpdateModel($productData);

echo "Mapped Data:\n";
print_r($product->toArray());

echo "\nJSON Serialization:\n";
echo json_encode($product, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Example 3: Complex Order ===\n\n";

$orderData = [
    'customer_email' => 'BOB@EXAMPLE.COM',
    'customer_name' => 'Bob Johnson',
    'items' => [
        ['id' => 1, 'qty' => 2],
        ['id' => 2, 'qty' => 1],
    ],
    'total' => '149.99',
    'currency' => 'usd',
];

$order = new OrderModel($orderData);

echo "Mapped Data:\n";
print_r($order->toArray());

echo "\n=== Example 4: Laravel-Style Controller Usage ===\n\n";

// Simulate Laravel controller method
function registerUser(UserRegistrationModel $request): array
{
    // $request is automatically instantiated and mapped
    return [
        'success' => true,
        'user' => $request->toArray(),
        'original_email' => $request->getOriginal('email'),
    ];
}

// Simulate request
$result = registerUser(new UserRegistrationModel($requestData));
echo "Controller Response:\n";
print_r($result);

echo "\n=== Example 5: Validation Example ===\n\n";

class ValidatedModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'age' => '{{ request.age }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            LowercaseEmails::class,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        // Validate email
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Validate age
        if ($this->age < 18) {
            $errors['age'] = 'Must be 18 or older';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}

$validRequest = new ValidatedModel([
    'email' => 'valid@example.com',
    'age' => 25,
]);

echo "Valid Request:\n";
echo "Is valid: " . ($validRequest->isValid() ? 'Yes' : 'No') . "\n";
print_r($validRequest->validate());

$invalidRequest = new ValidatedModel([
    'email' => 'invalid-email',
    'age' => 15,
]);

echo "\nInvalid Request:\n";
echo "Is valid: " . ($invalidRequest->isValid() ? 'Yes' : 'No') . "\n";
print_r($invalidRequest->validate());

echo "\n=== Example 6: Debugging with Original Data ===\n\n";

$debugRequest = new UserRegistrationModel([
    'email' => '  MESSY@EXAMPLE.COM  ',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'age' => '25',
]);

echo "What was sent (original):\n";
print_r($debugRequest->getOriginalData());

echo "\nWhat was processed (mapped):\n";
print_r($debugRequest->toArray());

echo "\nTemplate used:\n";
print_r($debugRequest->getTemplate());

echo "\n=== Example 7: Using Pipes for Data Transformation ===\n\n";

class UserWithPipesModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'name' => '{{ request.name }}',
            'company' => '{{ request.company }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,      // Trim all strings
            LowercaseEmails::class,  // Lowercase email fields
        ];
    }
}

$messyData = [
    'email' => '  CONTACT@EXAMPLE.COM  ',
    'name' => '  John Doe  ',
    'company' => '  Acme Corp  ',
];

$cleanModel = new UserWithPipesModel($messyData);

echo "Original Data (messy):\n";
print_r($cleanModel->getOriginalData());

echo "\nMapped Data (cleaned by pipes):\n";
print_r($cleanModel->toArray());

echo "\nNotice how:\n";
echo "- Email is lowercased: contact@example.com\n";
echo "- All strings are trimmed\n";
echo "- Pipes are applied automatically before mapping\n";

echo "\n=== Example 8: Advanced Pipes - Type Casting ===\n\n";

class ProductModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'product_id' => '{{ request.product_id }}',
            'name' => '{{ request.name }}',
            'price' => '{{ request.price }}',
            'quantity' => '{{ request.quantity }}',
            'is_active' => '{{ request.is_active }}',
            'description' => '{{ request.description }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,        // Trim all strings first
            StripTags::class,          // Remove HTML tags
            CastToInteger::class,      // Cast id, quantity to int
            CastToFloat::class,        // Cast price to float
            CastToBoolean::class,      // Cast is_active to bool
        ];
    }
}

$rawProductData = [
    'product_id' => '12345',           // String -> will be cast to int
    'name' => '  Gaming Mouse  ',     // Will be trimmed
    'price' => '49.99',                // String -> will be cast to float
    'quantity' => '100',               // String -> will be cast to int
    'is_active' => '1',                // String -> will be cast to bool
    'description' => '<p>Great product!</p>',  // HTML will be stripped
];

$product = new ProductModel($rawProductData);

echo "Original Data (raw strings):\n";
print_r($product->getOriginalData());

echo "\nMapped Data (typed and cleaned):\n";
print_r($product->toArray());

echo "\nType verification:\n";
$data = $product->toArray();
echo "- product_id is " . gettype($data['product_id']) . ": " . var_export($data['product_id'], true) . "\n";
echo "- price is " . gettype($data['price']) . ": " . var_export($data['price'], true) . "\n";
echo "- quantity is " . gettype($data['quantity']) . ": " . var_export($data['quantity'], true) . "\n";
echo "- is_active is " . gettype($data['is_active']) . ": " . var_export($data['is_active'], true) . "\n";
echo "- description (HTML stripped): " . var_export($data['description'], true) . "\n";

echo "\n=== Example 9: Handling Empty Values ===\n\n";

class UserProfileModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'name' => '{{ request.name }}',
            'email' => '{{ request.email }}',
            'phone' => '{{ request.phone }}',
            'bio' => '{{ request.bio }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            TrimStrings::class,
            ConvertEmptyToNull::class,  // Convert empty strings to null
            RemoveNullValues::class,    // Don't include null values in result
        ];
    }
}

$incompleteData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '',           // Empty string
    'bio' => '   ',          // Only whitespace
];

$profile = new UserProfileModel($incompleteData);

echo "Original Data:\n";
print_r($profile->getOriginalData());

echo "\nMapped Data (empty values removed):\n";
print_r($profile->toArray());

echo "\nNotice:\n";
echo "- Empty 'phone' field is not in the result\n";
echo "- Empty 'bio' field (whitespace) is trimmed, converted to null, then removed\n";
echo "- Only fields with actual values are included\n";
