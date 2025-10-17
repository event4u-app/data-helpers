<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\DataCollection;

// Example 1: Type Inference for fromArray()
echo "Example 1: Type Inference for fromArray()\n";
echo str_repeat('=', 80) . "\n\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

// IDE knows this returns UserDTO, not SimpleDTO
$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

echo "✅  User created: {$user->name}, {$user->age}, {$user->email}\n";
echo "    IDE provides autocomplete for \$user->name, \$user->age, \$user->email\n\n";

// Example 2: Type Inference for fromArray() with Validation Attributes
echo "\nExample 2: Type Inference for fromArray() with Validation Attributes\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Between(0, 999999)]
        public readonly float $price,
    ) {}
}

// IDE knows this returns ProductDTO
// Note: Validation attributes are for documentation and Laravel integration
$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
]);

echo "✅  Product created: {$product->name}, \${$product->price}\n";
echo "    IDE provides autocomplete for \$product->name, \$product->price\n";
echo "    Validation attributes (#[Required], #[Between]) are recognized by IDE\n\n";

// Example 3: Type Inference for collection()
echo "\nExample 3: Type Inference for collection()\n";
echo str_repeat('=', 80) . "\n\n";

// IDE knows this returns DataCollection<UserDTO>
$users = UserDTO::collection([
    ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com'],
]);

echo "✅  Collection created with {$users->count()} users\n";
echo "    IDE knows \$users is DataCollection<UserDTO>\n";
echo "    IDE provides autocomplete for \$users->map(), \$users->filter(), etc.\n\n";

// IDE knows $user is UserDTO in foreach
foreach ($users as $user) {
    echo "    - {$user->name} ({$user->age})\n";
}

// Example 4: Cast Type Autocomplete
echo "\n\nExample 4: Cast Type Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $createdAt,
        public readonly float $total,
    ) {}

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime', // IDE suggests: datetime, date, timestamp, etc.
            'total' => 'float',        // IDE suggests: float, decimal, decimal:2, etc.
        ];
    }
}

$order = OrderDTO::fromArray([
    'id' => 1,
    'createdAt' => '2024-01-01 12:00:00',
    'total' => '99.99',
]);

echo "✅  Order created: #{$order->id}, {$order->createdAt->format('Y-m-d')}, \${$order->total}\n";
echo "    IDE provides autocomplete for cast types in casts() method\n\n";

// Example 5: Validation Attribute Autocomplete
echo "\nExample 5: Validation Attribute Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class RegistrationDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email] // IDE knows this is a validation attribute
        public readonly string $email,
        #[Required]
        #[Between(18, 120)] // IDE suggests common values: 18, 100, etc.
        public readonly int $age,
    ) {}
}

$registration = RegistrationDTO::fromArray([
    'email' => 'user@example.com',
    'age' => 25,
]);

echo "✅  Registration created: {$registration->email}, {$registration->age}\n";
echo "    IDE provides autocomplete for validation attributes\n";
echo "    Attributes: #[Required], #[Email], #[Between]\n\n";

// Example 6: Property Mapping Autocomplete
echo "\nExample 6: Property Mapping Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        #[MapFrom('customer_id')] // IDE suggests: id, user_id, customer_id, etc.
        public readonly int $id,
        #[MapFrom('customer_name')] // IDE suggests common property names
        public readonly string $name,
    ) {}
}

$customer = CustomerDTO::fromArray([
    'customer_id' => 1,
    'customer_name' => 'John Doe',
]);

echo "✅  Customer created: #{$customer->id}, {$customer->name}\n";
echo "    IDE provides autocomplete for MapFrom attribute\n\n";

// Example 7: Naming Convention Autocomplete
echo "\nExample 7: Naming Convention Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

#[MapInputName('snake_case')] // IDE suggests: snake_case, camelCase, kebab-case, PascalCase
class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $emailAddress,
    ) {}
}

$response = ApiResponseDTO::fromArray([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com',
]);

echo "✅  API Response created: {$response->firstName} {$response->lastName}\n";
echo "    IDE provides autocomplete for naming conventions\n\n";

// Example 8: DataCollection Type Hints
echo "\nExample 8: DataCollection Type Hints\n";
echo str_repeat('=', 80) . "\n\n";

// Create a DataCollection directly
$members = DataCollection::forDto(UserDTO::class, [
    ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com'],
]);

echo "✅  DataCollection created with {$members->count()} members\n";
echo "    IDE knows \$members is DataCollection<UserDTO>\n";
echo "    IDE provides autocomplete for \$members->map(), \$members->filter(), etc.\n\n";

// IDE knows $member is UserDTO
foreach ($members as $member) {
    echo "    - {$member->name} ({$member->age})\n";
}

// Example 9: Generic Type Hints
echo "\n\nExample 9: Generic Type Hints\n";
echo str_repeat('=', 80) . "\n\n";

/**
 * Get user names from a collection.
 *
 * @param DataCollection<UserDTO> $users
 * @return array<int, string>
 */
function getUserNames(DataCollection $users): array
{
    $names = [];
    foreach ($users as $user) {
        $names[] = $user->name;
    }
    return $names;
}

$names = getUserNames($users);
echo "✅  User names: " . implode(', ', $names) . "\n";
echo "    IDE understands generic types: DataCollection<UserDTO>\n\n";

echo "\n✅  All examples completed!\n";
echo "\n💡 Tips:\n";
echo "   - Use PHPStorm or IntelliJ IDEA for best IDE support\n";
echo "   - The .phpstorm.meta.php file provides enhanced autocomplete\n";
echo "   - Use PHPDoc annotations for complex types\n";
echo "   - Enable strict types with declare(strict_types=1)\n";
echo "   - Use readonly properties for immutability\n";

