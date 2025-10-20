<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;

echo "=== Optional & Lazy Combinations Example ===\n\n";

// Example 1: All Combinations
echo "1. All Property Type Combinations\n";
echo str_repeat('-', 50) . "\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        // Regular properties
        public readonly string $name,
        public readonly int $age,

        // Optional only
        public readonly Optional|string $email,

        // Lazy only
        public readonly Lazy|string $biography,

        // Optional + Nullable
        public readonly Optional|string|null $address,

        // Lazy + Nullable
        public readonly Lazy|string|null $notes,

        // Optional + Lazy (can be missing AND lazy!)
        public readonly Optional|Lazy|string $preferences,

        // Optional + Lazy + Nullable (all three!)
        public readonly Optional|Lazy|string|null $metadata,

        // Nullable only (with default)
        public readonly ?string $phone = null,
    ) {}
}

echo "Creating user with minimal data:\n";
$user1 = UserDTO::fromArray([
    'name' => 'John Doe',
    'age' => 30,
    'biography' => 'Long biography...',
    'phone' => null,
    'notes' => 'Some notes...',
]);

echo sprintf('  name: %s%s', $user1->name, PHP_EOL);
echo sprintf('  age: %s%s', $user1->age, PHP_EOL);
echo "  email present: " . ($user1->email->isPresent() ? 'yes' : 'no') . "\n";
echo "  biography loaded: " . ($user1->biography->isLoaded() ? 'yes' : 'no') . "\n";
echo "  phone: " . ($user1->phone ?? 'null') . "\n";
echo "  address present: " . ($user1->address->isPresent() ? 'yes' : 'no') . "\n";
echo "  notes loaded: " . ($user1->notes->isLoaded() ? 'yes' : 'no') . "\n";
echo "  preferences present: " . ($user1->preferences->isPresent() ? 'yes' : 'no') . "\n";
echo "  metadata present: " . ($user1->metadata->isPresent() ? 'yes' : 'no') . "\n";
echo "\n";

// Example 2: toArray with Combinations
echo "2. toArray with Combinations\n";
echo str_repeat('-', 50) . "\n";

echo "Default toArray (lazy excluded):\n";
$array1 = $user1->toArray();
echo json_encode($array1, JSON_PRETTY_PRINT) . "\n\n";

echo "Include all lazy properties:\n";
$array2 = $user1->includeAll()->toArray();
echo json_encode($array2, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Optional + Lazy Combination
echo "3. Optional + Lazy Combination\n";
echo str_repeat('-', 50) . "\n";

class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Optional|Lazy|string $content,  // Can be missing AND lazy!
    ) {}
}

echo "Missing content:\n";
$doc1 = DocumentDTO::fromArray(['title' => 'Document 1']);
echo "  content present: " . ($doc1->content->isPresent() ? 'yes' : 'no') . "\n";
echo "  toArray: " . json_encode($doc1->toArray()) . "\n";
echo "\n";

echo "Present content (wrapped in Lazy):\n";
$doc2 = DocumentDTO::fromArray(['title' => 'Document 2', 'content' => 'Long content...']);
echo "  content present: " . ($doc2->content->isPresent() ? 'yes' : 'no') . "\n";
echo "  content is Optional: " . ($doc2->content instanceof Optional ? 'yes' : 'no') . "\n";
echo "  content value is Lazy: " . ($doc2->content->get() instanceof Lazy ? 'yes' : 'no') . "\n";
echo "  toArray (lazy excluded): " . json_encode($doc2->toArray()) . "\n";
echo "  toArray (lazy included): " . json_encode($doc2->include(['content'])->toArray()) . "\n";
echo "\n";

// Example 4: Partial Updates with Combinations
echo "4. Partial Updates with Combinations\n";
echo str_repeat('-', 50) . "\n";

class UpdateDTO extends SimpleDTO
{
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string|null $email,
        public readonly Optional|int $age,
        public readonly Optional|Lazy|string $bio,
    ) {}
}

echo "Update only email (set to null):\n";
$update1 = UpdateDTO::fromArray(['email' => null]);
$partial1 = $update1->partial();
echo "  partial: " . json_encode($partial1) . "\n";
echo "  email is present: yes (explicitly set to null)\n";
echo "\n";

echo "Update name and age:\n";
$update2 = UpdateDTO::fromArray(['name' => 'Jane', 'age' => 25]);
$partial2 = $update2->partial();
echo "  partial: " . json_encode($partial2) . "\n";
echo "\n";

// Example 5: Nested Optional + Lazy
echo "5. Nested Optional + Lazy\n";
echo str_repeat('-', 50) . "\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly Optional|Lazy|array $settings,
        public readonly Optional|Lazy|string|null $avatar,
    ) {}
}

$profile1 = ProfileDTO::fromArray(['username' => 'john_doe']);
echo "Missing settings and avatar:\n";
echo "  settings present: " . ($profile1->settings->isPresent() ? 'yes' : 'no') . "\n";
echo "  avatar present: " . ($profile1->avatar->isPresent() ? 'yes' : 'no') . "\n";
echo "  toArray: " . json_encode($profile1->toArray()) . "\n";
echo "\n";

$profile2 = ProfileDTO::fromArray([
    'username' => 'jane_doe',
    'settings' => ['theme' => 'dark', 'notifications' => true],
    'avatar' => null,
]);
echo "Present settings (lazy), explicit null avatar:\n";
echo "  settings present: " . ($profile2->settings->isPresent() ? 'yes' : 'no') . "\n";
echo "  avatar present: " . ($profile2->avatar->isPresent() ? 'yes' : 'no') . "\n";
$avatarValue = $profile2->avatar->get(); // Get Lazy wrapper
$avatarActualValue = $avatarValue instanceof Lazy ? $avatarValue->get() : $avatarValue;
echo "  avatar value: " . (null === $avatarActualValue ? 'null' : 'not null') . "\n";
echo "  toArray (lazy excluded): " . json_encode($profile2->toArray()) . "\n";
echo "  toArray (lazy included): " . json_encode($profile2->includeAll()->toArray()) . "\n";
echo "\n";

// Example 6: Type Checking
echo "6. Type Checking\n";
echo str_repeat('-', 50) . "\n";

$user2 = UserDTO::fromArray([
    'name' => 'Alice',
    'age' => 28,
    'email' => 'alice@example.com',
    'biography' => 'Bio...',
    'notes' => 'Notes...',
]);

echo "Type checks:\n";
echo "  email is Optional: " . ($user2->email instanceof Optional ? 'yes' : 'no') . "\n";
echo "  biography is Lazy: " . ($user2->biography instanceof Lazy ? 'yes' : 'no') . "\n";
echo "  phone is nullable: " . (property_exists(
    $user2,
    'phone'
) && null !== $user2->phone ? 'no' : 'yes (not set)') . "\n";
echo "  address is Optional: " . ($user2->address instanceof Optional ? 'yes' : 'no') . "\n";
echo "  notes is Lazy: " . ($user2->notes instanceof Lazy ? 'yes' : 'no') . "\n";
echo "\n";

// Example 7: JSON Serialization
echo "7. JSON Serialization\n";
echo str_repeat('-', 50) . "\n";

$user3 = UserDTO::fromArray([
    'name' => 'Bob',
    'age' => 35,
    'email' => 'bob@example.com',
    'biography' => 'Long bio...',
    'phone' => '123-456-7890',
    'address' => '123 Main St',
    'notes' => 'Important notes...',
    'preferences' => 'Dark theme',
    'metadata' => 'Some metadata',
]);

echo "JSON (lazy excluded):\n";
echo json_encode($user3, JSON_PRETTY_PRINT) . "\n\n";

echo "JSON (lazy included):\n";
echo json_encode($user3->includeAll(), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  All examples completed successfully!\n";

