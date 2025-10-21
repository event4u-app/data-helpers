<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToBoolean;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToFloat;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\CastToInteger;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\ConvertEmptyToNull;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\RemoveNullValues;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\StripTags;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\MappedDataModel;

/**
 * Example 1: User Registration Model
 *
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property int $age
 * @property bool $is_active
 * @property int $registered_at
 */
/** @phpstan-ignore-next-line class.notFound */
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
        return (int)($this->age ?? 0);
    }

    public function getRegisteredAt(): int
    {
        return $this->registered_at ?? time();
    }
}

/**
 * Example 2: Product Update Model
 *
 * @property string $name
 * @property float $price
 * @property string $sku
 * @property int $quantity
 * @property array<int, string> $tags
 */
/** @phpstan-ignore-next-line class.notFound */
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
        return (float)($this->price ?? 0);
    }

    public function getSku(): string
    {
        return strtoupper(trim($this->sku ?? ''));
    }

    public function isInStock(): bool
    {
        return 0 < ($this->quantity ?? 0);
    }
}

/**
 * Example 3: Complex Nested Order Model
 *
 * @property array{email: string, name: string} $customer
 * @property array<int, mixed> $items
 * @property float $total
 * @property string $currency
 * @property string $status
 */
/** @phpstan-ignore-next-line class.notFound */
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
        return (float)($this->total ?? 0);
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
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\nAccess via magic getter (raw):\n";
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Email (raw): %s%s', $user->email, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('First name: %s%s', $user->first_name, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Last name: %s%s', $user->last_name, PHP_EOL);

echo "\nAccess via custom getters (transformed):\n";
echo "Email (clean): " . $user->getEmail() . "\n";
echo "Full name: " . $user->getFullName() . "\n";
echo "Age (int): " . $user->getAge() . "\n";

echo "\nOriginal Data:\n";
$originalEmail = $user->getOriginal('email');
echo "Original email: " . (is_string($originalEmail) ? $originalEmail : '') . "\n";
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
echo json_encode($product->toArray(), JSON_PRETTY_PRINT) . "\n";

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
echo json_encode($order->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\n=== Example 4: Laravel-Style Controller Usage ===\n\n";

/**
 * Simulate Laravel controller method
 *
 * @return array<string, mixed>
 */
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
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Example 5: Validation Example ===\n\n";

/**
 * Validated Model Example
 *
 * @property string $email
 * @property int $age
 */
/** @phpstan-ignore-next-line class.notFound */
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
            new TrimStrings(),
            new LowercaseEmails(),
        ];
    }

    /** @return array<string, string> */
    public function validate(): array
    {
        $errors = [];

        // Validate email
        /** @phpstan-ignore-next-line phpstan-error */
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Validate age
        /** @phpstan-ignore-next-line phpstan-error */
        if (18 > $this->age) {
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
echo json_encode($validRequest->validate(), JSON_PRETTY_PRINT) . "\n";

$invalidRequest = new ValidatedModel([
    'email' => 'invalid-email',
    'age' => 15,
]);

echo "\nInvalid Request:\n";
echo "Is valid: " . ($invalidRequest->isValid() ? 'Yes' : 'No') . "\n";
echo json_encode($invalidRequest->validate(), JSON_PRETTY_PRINT) . "\n";

echo "\n=== Example 6: Debugging with Original Data ===\n\n";

$debugRequest = new UserRegistrationModel([
    'email' => '  MESSY@EXAMPLE.COM  ',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'age' => '25',
]);

echo "What was sent (original):\n";
echo json_encode($debugRequest->getOriginalData(), JSON_PRETTY_PRINT) . "\n";

echo "\nWhat was processed (mapped):\n";
echo json_encode($debugRequest->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\nTemplate used:\n";
echo json_encode($debugRequest->getTemplate(), JSON_PRETTY_PRINT) . "\n";

echo "\n=== Example 7: Using Pipes for Data Transformation ===\n\n";

/**
 * User With Pipes Model
 *
 * @property string $email
 * @property string $name
 * @property string $company
 */
/** @phpstan-ignore-next-line class.notFound */
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
            new TrimStrings(),      // Trim all strings
            new LowercaseEmails(),  // Lowercase email fields
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
echo json_encode($cleanModel->getOriginalData(), JSON_PRETTY_PRINT) . "\n";

echo "\nMapped Data (cleaned by pipes):\n";
echo json_encode($cleanModel->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\nNotice how:\n";
echo "- Email is lowercased: contact@example.com\n";
echo "- All strings are trimmed\n";
echo "- Pipes are applied automatically before mapping\n";

echo "\n=== Example 8: Advanced Pipes - Type Casting ===\n\n";

/**
 * Product Model with Type Casting
 *
 * @property int $product_id
 * @property string $name
 * @property float $price
 * @property int $quantity
 * @property bool $is_active
 * @property string $description
 */
/** @phpstan-ignore-next-line class.notFound */
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
            new TrimStrings(),        // Trim all strings first
            new StripTags(),          // Remove HTML tags
            new CastToInteger(),      // Cast id, quantity to int
            new CastToFloat(),        // Cast price to float
            new CastToBoolean(),      // Cast is_active to bool
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
echo json_encode($product->getOriginalData(), JSON_PRETTY_PRINT) . "\n";

echo "\nMapped Data (typed and cleaned):\n";
echo json_encode($product->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\nType verification:\n";
$data = $product->toArray();
echo "- product_id is " . gettype($data['product_id']) . ": " . var_export($data['product_id'], true) . "\n";
echo "- price is " . gettype($data['price']) . ": " . var_export($data['price'], true) . "\n";
echo "- quantity is " . gettype($data['quantity']) . ": " . var_export($data['quantity'], true) . "\n";
echo "- is_active is " . gettype($data['is_active']) . ": " . var_export($data['is_active'], true) . "\n";
echo "- description (HTML stripped): " . var_export($data['description'], true) . "\n";

echo "\n=== Example 9: Handling Empty Values ===\n\n";

/**
 * User Profile Model
 *
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $bio
 */
/** @phpstan-ignore-next-line class.notFound */
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
            new TrimStrings(),
            new ConvertEmptyToNull(),  // Convert empty strings to null
            new RemoveNullValues(),    // Don't include null values in result
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
echo json_encode($profile->getOriginalData(), JSON_PRETTY_PRINT) . "\n";

echo "\nMapped Data (empty values removed):\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . "\n";

echo "\nNotice:\n";
echo "- Empty 'phone' field is not in the result\n";
echo "- Empty 'bio' field (whitespace) is trimmed, converted to null, then removed\n";
echo "- Only fields with actual values are included\n";
