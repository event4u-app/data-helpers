<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;

echo "=== Partial Updates Example ===\n\n";

// Example 1: Basic Partial Updates
echo "1. Basic Partial Updates\n";
echo str_repeat('-', 50) . "\n";

class UserUpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $email,
        public readonly Optional|int $age,
        public readonly Optional|string|null $bio,
    ) {}
}

echo "Update only name:\n";
$update1 = UserUpdateDTO::fromArray(['name' => 'John Doe']);
$partial1 = $update1->partial();
echo "  partial: " . json_encode($partial1) . "\n";
echo "  keys: " . implode(', ', array_keys($partial1)) . "\n";
echo "\n";

echo "Update name and email:\n";
$update2 = UserUpdateDTO::fromArray(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
$partial2 = $update2->partial();
echo "  partial: " . json_encode($partial2) . "\n";
echo "  keys: " . implode(', ', array_keys($partial2)) . "\n";
echo "\n";

echo "Update bio to null (explicit):\n";
$update3 = UserUpdateDTO::fromArray(['bio' => null]);
$partial3 = $update3->partial();
echo "  partial: " . json_encode($partial3) . "\n";
echo "  keys: " . implode(', ', array_keys($partial3)) . "\n";
echo "  bio is present: yes (explicitly set to null)\n";
echo "\n";

// Example 2: Partial Updates with Lazy Properties
echo "2. Partial Updates with Lazy Properties\n";
echo str_repeat('-', 50) . "\n";

class DocumentUpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $title,
        public readonly Optional|Lazy|string $content,
        public readonly Optional|Lazy|array $metadata,
    ) {}
}

echo "Update only title:\n";
$docUpdate1 = DocumentUpdateDTO::fromArray(['title' => 'New Title']);
$docPartial1 = $docUpdate1->partial();
echo "  partial: " . json_encode($docPartial1) . "\n";
echo "\n";

echo "Update title and content (lazy):\n";
$docUpdate2 = DocumentUpdateDTO::fromArray(['title' => 'Another Title', 'content' => 'New content...']);
$docPartial2 = $docUpdate2->partial();
echo "  partial: " . json_encode($docPartial2) . "\n";
echo "  content unwrapped: yes (Lazy wrapper removed)\n";
echo "\n";

// Example 3: PATCH Request Simulation
echo "3. PATCH Request Simulation\n";
echo str_repeat('-', 50) . "\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $bio,
        public readonly array $settings,
    ) {}
}

class ProfileUpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $username,
        public readonly Optional|string $email,
        public readonly Optional|string|null $bio,
        public readonly Optional|array $settings,
    ) {}
}

// Original profile
$profile = new ProfileDTO(
    id: '123',
    username: 'john_doe',
    email: 'john@example.com',
    bio: 'Original bio',
    settings: ['theme' => 'light', 'notifications' => true],
);

echo "Original profile:\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// PATCH request: Update only username and bio
$patchData = ['username' => 'john_doe_updated', 'bio' => null];
$update = ProfileUpdateDTO::fromArray($patchData);
$changes = $update->partial();

echo "PATCH request data:\n";
echo json_encode($patchData, JSON_PRETTY_PRINT) . "\n\n";

echo "Extracted changes (partial):\n";
echo json_encode($changes, JSON_PRETTY_PRINT) . "\n\n";

// Apply changes to original profile
$updatedProfile = new ProfileDTO(
    id: $profile->id,
    username: $changes['username'] ?? $profile->username,
    email: $changes['email'] ?? $profile->email,
    bio: array_key_exists('bio', $changes) ? $changes['bio'] : $profile->bio,
    settings: $changes['settings'] ?? $profile->settings,
);

echo "Updated profile:\n";
echo json_encode($updatedProfile->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Nested Partial Updates
echo "4. Nested Partial Updates\n";
echo str_repeat('-', 50) . "\n";

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class UserWithAddressUpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|AddressDTO $address,
    ) {}
}

echo "Update with nested DTO:\n";
$nestedUpdate = UserWithAddressUpdateDTO::fromArray([
    'name' => 'Alice',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'country' => 'USA',
    ],
]);
$nestedPartial = $nestedUpdate->partial();
echo "  partial keys: " . implode(', ', array_keys($nestedPartial)) . "\n";
echo "  address type: " . (get_debug_type($nestedPartial['address'])) . "\n";
if (is_object($nestedPartial['address']) && method_exists($nestedPartial['address'], 'toArray')) {
    echo "  address data: " . json_encode($nestedPartial['address']->toArray()) . "\n";
} else {
    echo "  address data: " . json_encode($nestedPartial['address']) . "\n";
}
echo "\n";

// Example 5: Partial with Validation
echo "5. Partial with Validation\n";
echo str_repeat('-', 50) . "\n";

class ValidatedUpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $email,
        public readonly Optional|int $age,
    ) {}

    public function validatePartial(): array
    {
        $errors = [];
        $partial = $this->partial();

        if (isset($partial['email']) && !filter_var($partial['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (isset($partial['age']) && (0 > $partial['age'] || 150 < $partial['age'])) {
            $errors['age'] = 'Age must be between 0 and 150';
        }

        return $errors;
    }
}

echo "Valid update:\n";
$validUpdate = ValidatedUpdateDTO::fromArray(['email' => 'valid@example.com', 'age' => 25]);
$validErrors = $validUpdate->validatePartial();
echo "  errors: " . ($validErrors === [] ? 'none' : json_encode($validErrors)) . "\n";
echo "  partial: " . json_encode($validUpdate->partial()) . "\n";
echo "\n";

echo "Invalid update:\n";
$invalidUpdate = ValidatedUpdateDTO::fromArray(['email' => 'invalid-email', 'age' => 200]);
$invalidErrors = $invalidUpdate->validatePartial();
echo "  errors: " . json_encode($invalidErrors) . "\n";
echo "  partial: " . json_encode($invalidUpdate->partial()) . "\n";
echo "\n";

// Example 6: Partial with Empty Updates
echo "6. Partial with Empty Updates\n";
echo str_repeat('-', 50) . "\n";

echo "Empty update (no fields):\n";
$emptyUpdate = UserUpdateDTO::fromArray([]);
$emptyPartial = $emptyUpdate->partial();
echo "  partial: " . json_encode($emptyPartial) . "\n";
echo "  is empty: " . ($emptyPartial === [] ? 'yes' : 'no') . "\n";
echo "\n";

// Example 7: Partial with All Fields
echo "7. Partial with All Fields\n";
echo str_repeat('-', 50) . "\n";

echo "Update all fields:\n";
$fullUpdate = UserUpdateDTO::fromArray([
    'name' => 'Full Name',
    'email' => 'full@example.com',
    'age' => 30,
    'bio' => 'Full bio',
]);
$fullPartial = $fullUpdate->partial();
echo "  partial: " . json_encode($fullPartial) . "\n";
echo "  keys: " . implode(', ', array_keys($fullPartial)) . "\n";
echo "\n";

echo "✅  All examples completed successfully!\n";

