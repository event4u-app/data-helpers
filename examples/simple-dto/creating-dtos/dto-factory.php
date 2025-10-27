<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\DtoFactory;

echo "================================================================================\n";
echo "SimpleDto - Dto Factory Examples\n";
echo "================================================================================\n\n";

// Example 1: Basic Factory
echo "Example 1: Basic Factory\n";
echo "------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}
}

class UserDtoFactory extends DtoFactory
{
    protected string $dtoClass = UserDto::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'age' => $this->faker->numberBetween(18, 80),
        ];
    }
}

/** @var UserDto $user */
$user = UserDtoFactory::new()->create();
/** @phpstan-ignore-next-line unknown */
echo "Created user: {$user->name} ({$user->email}), Age: {$user->age}\n\n";

// Example 2: Create Multiple Dtos
echo "Example 2: Create Multiple Dtos\n";
echo "--------------------------------\n";

/** @var array<UserDto> $users */
$users = UserDtoFactory::new()->count(5)->create();
echo "Created " . count($users) . " users:\n";
foreach ($users as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}
echo "\n";

// Example 3: Custom Attributes
echo "Example 3: Custom Attributes\n";
echo "----------------------------\n";

/** @var UserDto $admin */
$admin = UserDtoFactory::new()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'age' => 35,
]);
/** @phpstan-ignore-next-line unknown */
echo "Created admin: {$admin->name} ({$admin->email}), Age: {$admin->age}\n\n";

// Example 4: Make Array Without Creating Dto
echo "Example 4: Make Array Without Creating Dto\n";
echo "------------------------------------------\n";

/** @var array{name: string, email: string, age: int} $userData */
$userData = UserDtoFactory::new()->make();
echo "User data array:\n";
echo sprintf('  Name: %s%s', $userData['name'], PHP_EOL);
echo sprintf('  Email: %s%s', $userData['email'], PHP_EOL);
echo sprintf('  Age: %d%s', $userData['age'], PHP_EOL);
echo "\n";

// Example 5: Factory States
echo "Example 5: Factory States\n";
echo "-------------------------\n";

/** @var UserDto $adminUser */
$adminUser = UserDtoFactory::new()
    ->state('admin', ['age' => 99])
    ->create();
/** @phpstan-ignore-next-line unknown */
echo sprintf('Admin user: %s, Age: %s%s', $adminUser->name, $adminUser->age, PHP_EOL);

/** @var UserDto $verifiedUser */
$verifiedUser = UserDtoFactory::new()
    ->state('verified', ['name' => 'Verified User'])
    ->create();
echo "Verified user: {$verifiedUser->name}\n\n";

// Example 6: Factory with Multiple Attributes
echo "Example 6: Factory with Multiple Attributes\n";
echo "--------------------------------------------\n";

/** @var array<array{name: string, email: string, age: int}> $users */
$users = UserDtoFactory::new()->count(3)->make();
echo "Created " . count($users) . " user data arrays:\n";
foreach ($users as $userData) {
    echo sprintf('  - %s (%s), Age: %d%s', $userData['name'], $userData['email'], $userData['age'], PHP_EOL);
}
echo "\n";

// Example 7: Factory with Validation
echo "Example 7: Factory with Validation\n";
echo "-----------------------------------\n";

use event4u\DataHelpers\SimpleDto\Attributes\Validation\Email;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Validation\Min;

class ValidatedUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        /** @phpstan-ignore-next-line unknown */
        #[Email]
        public readonly string $email,
        /** @phpstan-ignore-next-line unknown */
        #[Min(18)]
        /** @phpstan-ignore-next-line unknown */
        #[Max(100)]
        public readonly int $age,
    ) {}
}

class ValidatedUserDtoFactory extends DtoFactory
{
    protected string $dtoClass = ValidatedUserDto::class;

    protected function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'age' => $this->faker->numberBetween(18, 100),
        ];
    }
}

/** @var ValidatedUserDto $validatedUser */
$validatedUser = ValidatedUserDtoFactory::new()->create();
echo "Validated user: {$validatedUser->name} ({$validatedUser->email}), Age: {$validatedUser->age}\n\n";

// Example 8: Multiple States
echo "Example 8: Multiple States\n";
echo "--------------------------\n";

/** @var UserDto $superAdmin */
$superAdmin = UserDtoFactory::new()
    ->state('admin', ['age' => 99])
    ->state('super', ['name' => 'Super Admin'])
    ->create();
/** @phpstan-ignore-next-line unknown */
echo "Super admin: {$superAdmin->name}, Age: {$superAdmin->age}\n\n";

// Example 9: Factory Reset
echo "Example 9: Factory Reset\n";
echo "------------------------\n";

$factory = UserDtoFactory::new();

/** @var array<UserDto> $users1 */
$users1 = $factory->count(3)->create();
echo "Created " . count($users1) . " users\n";

$factory->reset();
/** @var UserDto $user2 */
$user2 = $factory->create();
echo "After reset, created single user: {$user2->name}\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
