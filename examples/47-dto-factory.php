<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\DTOFactory;

echo "================================================================================\n";
echo "SimpleDTO - DTO Factory Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Factory
echo "Example 1: Basic Factory\n";
echo "------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

class UserDTOFactory extends DTOFactory
{
    protected string $dtoClass = UserDTO::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'age' => $this->faker->numberBetween(18, 80),
        ];
    }
}

$user = UserDTOFactory::new()->create();
echo "Created user: {$user->name} ({$user->email}), Age: {$user->age}\n\n";

// Example 2: Create Multiple DTOs
echo "Example 2: Create Multiple DTOs\n";
echo "--------------------------------\n";

$users = UserDTOFactory::new()->count(5)->create();
echo "Created " . count($users) . " users:\n";
foreach ($users as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}
echo "\n";

// Example 3: Custom Attributes
echo "Example 3: Custom Attributes\n";
echo "----------------------------\n";

$admin = UserDTOFactory::new()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'age' => 35,
]);
echo "Created admin: {$admin->name} ({$admin->email}), Age: {$admin->age}\n\n";

// Example 4: Make Array Without Creating DTO
echo "Example 4: Make Array Without Creating DTO\n";
echo "------------------------------------------\n";

$userData = UserDTOFactory::new()->make();
echo "User data array:\n";
echo "  Name: {$userData['name']}\n";
echo "  Email: {$userData['email']}\n";
echo "  Age: {$userData['age']}\n\n";

// Example 5: Factory States
echo "Example 5: Factory States\n";
echo "-------------------------\n";

$adminUser = UserDTOFactory::new()
    ->state('admin', ['age' => 99])
    ->create();
echo "Admin user: {$adminUser->name}, Age: {$adminUser->age}\n";

$verifiedUser = UserDTOFactory::new()
    ->state('verified', ['name' => 'Verified User'])
    ->create();
echo "Verified user: {$verifiedUser->name}\n\n";

// Example 6: Factory with Multiple Attributes
echo "Example 6: Factory with Multiple Attributes\n";
echo "--------------------------------------------\n";

$users = UserDTOFactory::new()->count(3)->make();
echo "Created " . count($users) . " user data arrays:\n";
foreach ($users as $userData) {
    echo "  - {$userData['name']} ({$userData['email']}), Age: {$userData['age']}\n";
}
echo "\n";

// Example 7: Factory with Validation
echo "Example 7: Factory with Validation\n";
echo "-----------------------------------\n";

use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Max;

class ValidatedUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[Email]
        public readonly string $email,
        #[Min(18)]
        #[Max(100)]
        public readonly int $age,
    ) {}
}

class ValidatedUserDTOFactory extends DTOFactory
{
    protected string $dtoClass = ValidatedUserDTO::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'age' => $this->faker->numberBetween(18, 100),
        ];
    }
}

$validatedUser = ValidatedUserDTOFactory::new()->create();
echo "Validated user: {$validatedUser->name} ({$validatedUser->email}), Age: {$validatedUser->age}\n\n";

// Example 8: Multiple States
echo "Example 8: Multiple States\n";
echo "--------------------------\n";

$superAdmin = UserDTOFactory::new()
    ->state('admin', ['age' => 99])
    ->state('super', ['name' => 'Super Admin'])
    ->create();
echo "Super admin: {$superAdmin->name}, Age: {$superAdmin->age}\n\n";

// Example 9: Factory Reset
echo "Example 9: Factory Reset\n";
echo "------------------------\n";

$factory = UserDTOFactory::new();

$users1 = $factory->count(3)->create();
echo "Created " . count($users1) . " users\n";

$factory->reset();
$user2 = $factory->create();
echo "After reset, created single user: {$user2->name}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

