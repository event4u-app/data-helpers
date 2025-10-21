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

/** @var UserDTO $user */
$user = UserDTOFactory::new()->create();
/** @phpstan-ignore-next-line phpstan-error */
echo "Created user: {$user->name} ({$user->email}), Age: {$user->age}\n\n";

// Example 2: Create Multiple DTOs
echo "Example 2: Create Multiple DTOs\n";
echo "--------------------------------\n";

/** @var array<UserDTO> $users */
$users = UserDTOFactory::new()->count(5)->create();
echo "Created " . count($users) . " users:\n";
foreach ($users as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}
echo "\n";

// Example 3: Custom Attributes
echo "Example 3: Custom Attributes\n";
echo "----------------------------\n";

/** @var UserDTO $admin */
$admin = UserDTOFactory::new()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'age' => 35,
]);
/** @phpstan-ignore-next-line phpstan-error */
echo "Created admin: {$admin->name} ({$admin->email}), Age: {$admin->age}\n\n";

// Example 4: Make Array Without Creating DTO
echo "Example 4: Make Array Without Creating DTO\n";
echo "------------------------------------------\n";

/** @var array{name: string, email: string, age: int} $userData */
$userData = UserDTOFactory::new()->make();
echo "User data array:\n";
echo sprintf('  Name: %s%s', $userData['name'], PHP_EOL);
echo sprintf('  Email: %s%s', $userData['email'], PHP_EOL);
echo sprintf('  Age: %d%s', $userData['age'], PHP_EOL);
echo "\n";

// Example 5: Factory States
echo "Example 5: Factory States\n";
echo "-------------------------\n";

/** @var UserDTO $adminUser */
$adminUser = UserDTOFactory::new()
    ->state('admin', ['age' => 99])
    ->create();
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Admin user: %s, Age: %s%s', $adminUser->name, $adminUser->age, PHP_EOL);

/** @var UserDTO $verifiedUser */
$verifiedUser = UserDTOFactory::new()
    ->state('verified', ['name' => 'Verified User'])
    ->create();
echo "Verified user: {$verifiedUser->name}\n\n";

// Example 6: Factory with Multiple Attributes
echo "Example 6: Factory with Multiple Attributes\n";
echo "--------------------------------------------\n";

/** @var array<array{name: string, email: string, age: int}> $users */
$users = UserDTOFactory::new()->count(3)->make();
echo "Created " . count($users) . " user data arrays:\n";
foreach ($users as $userData) {
    echo sprintf('  - %s (%s), Age: %d%s', $userData['name'], $userData['email'], $userData['age'], PHP_EOL);
}
echo "\n";

// Example 7: Factory with Validation
echo "Example 7: Factory with Validation\n";
echo "-----------------------------------\n";

use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Validation\Min;

class ValidatedUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        /** @phpstan-ignore-next-line attribute.notFound */
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly string $email,
        /** @phpstan-ignore-next-line attribute.notFound */
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(18)]
        /** @phpstan-ignore-next-line attribute.notFound */
        /** @phpstan-ignore-next-line attribute.notFound */
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

/** @var ValidatedUserDTO $validatedUser */
$validatedUser = ValidatedUserDTOFactory::new()->create();
echo "Validated user: {$validatedUser->name} ({$validatedUser->email}), Age: {$validatedUser->age}\n\n";

// Example 8: Multiple States
echo "Example 8: Multiple States\n";
echo "--------------------------\n";

/** @var UserDTO $superAdmin */
$superAdmin = UserDTOFactory::new()
    ->state('admin', ['age' => 99])
    ->state('super', ['name' => 'Super Admin'])
    ->create();
/** @phpstan-ignore-next-line phpstan-error */
echo "Super admin: {$superAdmin->name}, Age: {$superAdmin->age}\n\n";

// Example 9: Factory Reset
echo "Example 9: Factory Reset\n";
echo "------------------------\n";

$factory = UserDTOFactory::new();

/** @var array<UserDTO> $users1 */
$users1 = $factory->count(3)->create();
echo "Created " . count($users1) . " users\n";

$factory->reset();
/** @var UserDTO $user2 */
$user2 = $factory->create();
echo "After reset, created single user: {$user2->name}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

