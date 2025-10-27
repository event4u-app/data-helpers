<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\DataCollection;

// Example 1: Type Inference for fromArray()
echo "Example 1: Type Inference for fromArray()\n";
echo str_repeat('=', 80) . "\n\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

// IDE knows this returns UserDto, not SimpleDto
$user = UserDto::fromArray([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('✅  User created: %s, %s, %s%s', $user->name, $user->age, $user->email, PHP_EOL);
echo "    IDE provides autocomplete for \$user->name, \$user->age, \$user->email\n\n";

// Example 2: Type Inference for fromArray() with Validation Attributes
echo "\nExample 2: Type Inference for fromArray() with Validation Attributes\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Between(0, 999999)]
        public readonly float $price,
    ) {}
}

// IDE knows this returns ProductDto
// Note: Validation attributes are for documentation and Laravel integration
$product = ProductDto::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
]);

echo sprintf('✅  Product created: %s, $%s%s', $product->name, $product->price, PHP_EOL);
echo "    IDE provides autocomplete for \$product->name, \$product->price\n";
echo "    Validation attributes (#[Required], #[Between]) are recognized by IDE\n\n";

// Example 3: Type Inference for collection()
echo "\nExample 3: Type Inference for collection()\n";
echo str_repeat('=', 80) . "\n\n";

// IDE knows this returns DataCollection<UserDto>
$users = UserDto::collection([
    ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com'],
]);

echo "✅  Collection created with {$users->count()} users\n";
echo "    IDE knows \$users is DataCollection<UserDto>\n";
echo "    IDE provides autocomplete for \$users->map(), \$users->filter(), etc.\n\n";

// IDE knows $user is UserDto in foreach
foreach ($users as $user) {
    /** @phpstan-ignore-next-line unknown */
    echo "    - {$user->name} ({$user->age})\n";
}

// Example 4: Cast Type Autocomplete
echo "\n\nExample 4: Cast Type Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class OrderDto extends SimpleDto
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

$order = OrderDto::fromArray([
    'id' => 1,
    'createdAt' => '2024-01-01 12:00:00',
    'total' => '99.99',
]);

echo sprintf(
    '✅  Order created: #%d, %s, $%s%s',
    $order->id,
    $order->createdAt->format('Y-m-d'),
    $order->total,
    PHP_EOL
);
echo "    IDE provides autocomplete for cast types in casts() method\n\n";

// Example 5: Validation Attribute Autocomplete
echo "\nExample 5: Validation Attribute Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class RegistrationDto extends SimpleDto
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

$registration = RegistrationDto::fromArray([
    'email' => 'user@example.com',
    'age' => 25,
]);

echo sprintf('✅  Registration created: %s, %d%s', $registration->email, $registration->age, PHP_EOL);
echo "    IDE provides autocomplete for validation attributes\n";
echo "    Attributes: #[Required], #[Email], #[Between]\n\n";

// Example 6: Property Mapping Autocomplete
echo "\nExample 6: Property Mapping Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

class CustomerDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('customer_id')] // IDE suggests: id, user_id, customer_id, etc.
        public readonly int $id,
        #[MapFrom('customer_name')] // IDE suggests common property names
        public readonly string $name,
    ) {}
}

$customer = CustomerDto::fromArray([
    'customer_id' => 1,
    'customer_name' => 'John Doe',
]);

echo sprintf('✅  Customer created: #%d, %s%s', $customer->id, $customer->name, PHP_EOL);
echo "    IDE provides autocomplete for MapFrom attribute\n\n";

// Example 7: Naming Convention Autocomplete
echo "\nExample 7: Naming Convention Autocomplete\n";
echo str_repeat('=', 80) . "\n\n";

#[MapInputName('snake_case')] // IDE suggests: snake_case, camelCase, kebab-case, PascalCase
class ApiResponseDto extends SimpleDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $emailAddress,
    ) {}
}

$response = ApiResponseDto::fromArray([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com',
]);

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo sprintf('✅  API Response created: %s %s%s', $response->firstName, $response->lastName, PHP_EOL);
echo "    IDE provides autocomplete for naming conventions\n\n";

// Example 8: DataCollection<SimpleDto> Type Hints
echo "\nExample 8: DataCollection<SimpleDto> Type Hints\n";
echo str_repeat('=', 80) . "\n\n";

// Create a DataCollection directly
/** @var DataCollection<SimpleDto> $members */
$members = DataCollection::forDto(UserDto::class, [
    ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com'],
]);

echo "✅  DataCollection created with {$members->count()} members\n";
echo "    IDE knows \$members is DataCollection<UserDto>\n";
echo "    IDE provides autocomplete for \$members->map(), \$members->filter(), etc.\n\n";

// IDE knows $member is UserDto
foreach ($members as $member) {
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    echo "    - {$member->name} ({$member->age})\n";
}

// Example 9: Generic Type Hints
echo "\n\nExample 9: Generic Type Hints\n";
echo str_repeat('=', 80) . "\n\n";

/**
 * Get user names from a collection.
 *
 * @param DataCollection<UserDto> $users
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
echo "    IDE understands generic types: DataCollection<UserDto>\n\n";

echo "\n✅  All examples completed!\n";
echo "\n💡 Tips:\n";
echo "   - Use PHPStorm or IntelliJ IDEA for best IDE support\n";
echo "   - The .phpstorm.meta.php file provides enhanced autocomplete\n";
echo "   - Use PHPDoc annotations for complex types\n";
echo "   - Enable strict types with declare(strict_types=1)\n";
echo "   - Use readonly properties for immutability\n";
