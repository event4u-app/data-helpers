<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;
use Illuminate\Database\Eloquent\Model;

// Skip if Eloquent is not available
if (!class_exists('Illuminate\Database\Eloquent\Model')) {
    echo "⚠️  Skipping: Eloquent is not available\n";
    exit(0);
}

echo "================================================================================\n";
echo "ELOQUENT INTEGRATION - EXAMPLES\n";
echo "================================================================================\n\n";

echo "NOTE: This example uses Laravel's Eloquent Model.\n";
echo "To use Eloquent integration, add 'use SimpleDtoEloquentTrait;' to your Dto.\n\n";

// ============================================================================
// Example 1: Eloquent Model (using Laravel's Model)
// ============================================================================

echo "1. ELOQUENT MODEL:\n";
echo "======================================================================\n\n";

/**
 * User Model extending Laravel's Eloquent Model.
 * In a real Laravel app, this would be in app/Models/User.php
 */
class User extends Model
{
    protected $fillable = ['name', 'email', 'age', 'address'];

    // Disable timestamps for this example
    public $timestamps = false;
}

echo "User Model created (extends Illuminate\\Database\\Eloquent\\Model)\n\n";

// ============================================================================
// Example 2: Create Dto from Model (fromModel)
// ============================================================================

echo "2. CREATE Dto FROM MODEL (fromModel):\n";
echo "======================================================================\n\n";

class UserDto extends SimpleDto
{
    use SimpleDtoEloquentTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

echo "Original Model:\n";
/** @phpstan-ignore-next-line unknown */
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

/** @phpstan-ignore-next-line unknown */
$userDto = UserDto::fromModel($user);

echo "Dto created from Model:\n";
echo json_encode($userDto->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Access Dto properties:\n";
echo sprintf('Name: %s%s', $userDto->name, PHP_EOL);
echo sprintf('Email: %s%s', $userDto->email, PHP_EOL);
echo "Age: {$userDto->age}\n\n";

// ============================================================================
// Example 3: Create Model from Dto (toModel)
// ============================================================================

echo "3. CREATE MODEL FROM Dto (toModel):\n";
echo "======================================================================\n\n";

$dto = UserDto::fromArray([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25,
]);

echo "Original Dto:\n";
echo json_encode($dto->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

/** @phpstan-ignore-next-line unknown */
$model = $dto->toModel(User::class);

echo "Model created from Dto:\n";
echo json_encode($model->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Model exists flag: " . ($model->exists ? 'true' : 'false') . "\n\n";

// ============================================================================
// Example 4: Update Model from Dto
// ============================================================================

echo "4. UPDATE MODEL FROM Dto:\n";
echo "======================================================================\n\n";

/** @phpstan-ignore-next-line unknown */
$existingModel = new User([
    'name' => 'Old Name',
    'email' => 'old@example.com',
    'age' => 20,
]);

echo "Existing Model (before update):\n";
/** @phpstan-ignore-next-line unknown */
echo json_encode($existingModel->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

$updateDto = UserDto::fromArray([
    'name' => 'Updated Name',
    'email' => 'updated@example.com',
    'age' => 35,
]);

echo "Update Dto:\n";
echo json_encode($updateDto->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Update model with Dto data
/** @phpstan-ignore-next-line unknown */
$existingModel->fill($updateDto->toArray());

echo "Model (after update):\n";
/** @phpstan-ignore-next-line unknown */
echo json_encode($existingModel->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// ============================================================================
// Example 5: Round-trip (Model → Dto → Model)
// ============================================================================

echo "5. ROUND-TRIP (Model → Dto → Model):\n";
echo "======================================================================\n\n";

/** @phpstan-ignore-next-line unknown */
$originalModel = new User([
    'name' => 'Alice Brown',
    'email' => 'alice@example.com',
    'age' => 28,
]);

echo "Original Model:\n";
/** @phpstan-ignore-next-line unknown */
echo json_encode($originalModel->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Model → Dto
/** @phpstan-ignore-next-line unknown */
$dto = UserDto::fromModel($originalModel);

echo "Dto (from Model):\n";
echo json_encode($dto->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Dto → Model
$newModel = $dto->toModel(User::class);

echo "New Model (from Dto):\n";
echo json_encode($newModel->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

/** @phpstan-ignore-next-line unknown */
echo "Data preserved: " . ($originalModel->toArray() === $newModel->toArray() ? 'YES ✅' : 'NO ❌') . "\n\n";

// ============================================================================
// Example 6: Eloquent Cast Usage (Conceptual)
// ============================================================================

echo "6. ELOQUENT CAST USAGE (Conceptual):\n";
echo "======================================================================\n\n";

echo "In a real Laravel application, you would use SimpleDtoEloquentCast like this:\n\n";

echo "```php\n";
echo "use event4u\\DataHelpers\\SimpleDto\\SimpleDtoEloquentCast;\n\n";

echo "class User extends Model\n";
echo "{\n";
echo "    protected \$casts = [\n";
echo "        'address' => AddressDto::class,\n";
echo "        'settings' => UserSettingsDto::class,\n";
echo "    ];\n";
echo "}\n\n";

echo "// Usage\n";
echo "\$user = User::find(1);\n";
echo "\$user->address->street; // Access Dto properties\n";
echo "\$user->address = AddressDto::fromArray(['street' => 'Main St', ...]);\n";
echo "\$user->save(); // Automatically serialized to JSON\n";
echo "```\n\n";

echo "The SimpleDtoEloquentCast handles:\n";
echo "  ✅  Automatic serialization to JSON when saving\n";
echo "  ✅  Automatic deserialization to Dto when retrieving\n";
echo "  ✅  Support for nested Dtos\n";
echo "  ✅  Type safety with IDE autocomplete\n\n";

echo "================================================================================\n";
echo "ALL EXAMPLES COMPLETED SUCCESSFULLY!\n";
echo "================================================================================\n";
