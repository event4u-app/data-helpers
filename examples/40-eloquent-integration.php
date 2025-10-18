<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\SimpleDTOEloquentTrait;
use Illuminate\Database\Eloquent\Model;

echo "================================================================================\n";
echo "ELOQUENT INTEGRATION - EXAMPLES\n";
echo "================================================================================\n\n";

echo "NOTE: This example uses Laravel's Eloquent Model.\n";
echo "To use Eloquent integration, add 'use SimpleDTOEloquentTrait;' to your DTO.\n\n";

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
// Example 2: Create DTO from Model (fromModel)
// ============================================================================

echo "2. CREATE DTO FROM MODEL (fromModel):\n";
echo "======================================================================\n\n";

class UserDTO extends SimpleDTO
{
    use SimpleDTOEloquentTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}

$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

echo "Original Model:\n";
print_r($user->toArray());
echo "\n";

$userDto = UserDTO::fromModel($user);

echo "DTO created from Model:\n";
print_r($userDto->toArray());
echo "\n";

echo "Access DTO properties:\n";
echo "Name: {$userDto->name}\n";
echo "Email: {$userDto->email}\n";
echo "Age: {$userDto->age}\n\n";

// ============================================================================
// Example 3: Create Model from DTO (toModel)
// ============================================================================

echo "3. CREATE MODEL FROM DTO (toModel):\n";
echo "======================================================================\n\n";

$dto = UserDTO::fromArray([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25,
]);

echo "Original DTO:\n";
print_r($dto->toArray());
echo "\n";

$model = $dto->toModel(User::class);

echo "Model created from DTO:\n";
print_r($model->toArray());
echo "\n";

echo "Model exists flag: " . ($model->exists ? 'true' : 'false') . "\n\n";

// ============================================================================
// Example 4: Update Model from DTO
// ============================================================================

echo "4. UPDATE MODEL FROM DTO:\n";
echo "======================================================================\n\n";

$existingModel = new User([
    'name' => 'Old Name',
    'email' => 'old@example.com',
    'age' => 20,
]);

echo "Existing Model (before update):\n";
print_r($existingModel->toArray());
echo "\n";

$updateDto = UserDTO::fromArray([
    'name' => 'Updated Name',
    'email' => 'updated@example.com',
    'age' => 35,
]);

echo "Update DTO:\n";
print_r($updateDto->toArray());
echo "\n";

// Update model with DTO data
$existingModel->fill($updateDto->toArray());

echo "Model (after update):\n";
print_r($existingModel->toArray());
echo "\n";

// ============================================================================
// Example 5: Round-trip (Model → DTO → Model)
// ============================================================================

echo "5. ROUND-TRIP (Model → DTO → Model):\n";
echo "======================================================================\n\n";

$originalModel = new User([
    'name' => 'Alice Brown',
    'email' => 'alice@example.com',
    'age' => 28,
]);

echo "Original Model:\n";
print_r($originalModel->toArray());
echo "\n";

// Model → DTO
$dto = UserDTO::fromModel($originalModel);

echo "DTO (from Model):\n";
print_r($dto->toArray());
echo "\n";

// DTO → Model
$newModel = $dto->toModel(User::class);

echo "New Model (from DTO):\n";
print_r($newModel->toArray());
echo "\n";

echo "Data preserved: " . ($originalModel->toArray() === $newModel->toArray() ? 'YES ✅' : 'NO ❌') . "\n\n";

// ============================================================================
// Example 6: Eloquent Cast Usage (Conceptual)
// ============================================================================

echo "6. ELOQUENT CAST USAGE (Conceptual):\n";
echo "======================================================================\n\n";

echo "In a real Laravel application, you would use SimpleDTOEloquentCast like this:\n\n";

echo "```php\n";
echo "use event4u\\DataHelpers\\SimpleDTO\\SimpleDTOEloquentCast;\n\n";

echo "class User extends Model\n";
echo "{\n";
echo "    protected \$casts = [\n";
echo "        'address' => AddressDTO::class,\n";
echo "        'settings' => UserSettingsDTO::class,\n";
echo "    ];\n";
echo "}\n\n";

echo "// Usage\n";
echo "\$user = User::find(1);\n";
echo "\$user->address->street; // Access DTO properties\n";
echo "\$user->address = AddressDTO::fromArray(['street' => 'Main St', ...]);\n";
echo "\$user->save(); // Automatically serialized to JSON\n";
echo "```\n\n";

echo "The SimpleDTOEloquentCast handles:\n";
echo "  ✅  Automatic serialization to JSON when saving\n";
echo "  ✅  Automatic deserialization to DTO when retrieving\n";
echo "  ✅  Support for nested DTOs\n";
echo "  ✅  Type safety with IDE autocomplete\n\n";

echo "================================================================================\n";
echo "ALL EXAMPLES COMPLETED SUCCESSFULLY!\n";
echo "================================================================================\n";

