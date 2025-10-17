<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\HashedCast;

echo "================================================================================\n";
echo "SimpleDTO - Hashed Cast Examples\n";
echo "================================================================================\n\n";

// Example 1: User Registration with Password Hashing
echo "Example 1: User Registration with Password Hashing\n";
echo "---------------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
    ) {}

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}

$user = UserDTO::fromArray([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => 'MySecretPassword123!',
]);

echo "Username: {$user->username}\n";
echo "Email: {$user->email}\n";
echo "Password (hashed): {$user->password}\n";
echo "Hash starts with \$2y\$: " . (str_starts_with($user->password, '$2y$') ? 'Yes' : 'No') . "\n\n";

// Example 2: Password Verification
echo "Example 2: Password Verification\n";
echo "---------------------------------\n";

$loginAttempt1 = 'MySecretPassword123!';
$loginAttempt2 = 'WrongPassword';

echo "Login attempt 1: '{$loginAttempt1}'\n";
echo "Valid: " . (HashedCast::verify($loginAttempt1, $user->password) ? 'Yes ✅' : 'No ❌') . "\n\n";

echo "Login attempt 2: '{$loginAttempt2}'\n";
echo "Valid: " . (HashedCast::verify($loginAttempt2, $user->password) ? 'Yes ✅' : 'No ❌') . "\n\n";

// Example 3: Different Hashing Algorithms
echo "Example 3: Different Hashing Algorithms\n";
echo "----------------------------------------\n";

class SecureUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}

    protected function casts(): array
    {
        return ['password' => 'hashed:argon2id'];
    }
}

if (defined('PASSWORD_ARGON2ID')) {
    $secureUser = SecureUserDTO::fromArray([
        'username' => 'jane_doe',
        'password' => 'SuperSecure456!',
    ]);

    echo "Username: {$secureUser->username}\n";
    echo "Password (argon2id): {$secureUser->password}\n";
    echo "Hash starts with \$argon2id\$: " . (str_starts_with($secureUser->password, '$argon2id$') ? 'Yes' : 'No') . "\n";
    echo "Verification: " . (HashedCast::verify('SuperSecure456!', $secureUser->password) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";
} else {
    echo "Argon2id not available on this system\n\n";
}

// Example 4: Already Hashed Passwords (from Database)
echo "Example 4: Already Hashed Passwords (from Database)\n";
echo "----------------------------------------------------\n";

$hashedPassword = password_hash('ExistingPassword', PASSWORD_BCRYPT);

$existingUser = UserDTO::fromArray([
    'username' => 'existing_user',
    'email' => 'existing@example.com',
    'password' => $hashedPassword, // Already hashed
]);

echo "Username: {$existingUser->username}\n";
echo "Password unchanged: " . ($existingUser->password === $hashedPassword ? 'Yes ✅' : 'No ❌') . "\n";
echo "Verification: " . (HashedCast::verify('ExistingPassword', $existingUser->password) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";

// Example 5: API Response (Password Not Exposed)
echo "Example 5: API Response (Password Not Exposed)\n";
echo "-----------------------------------------------\n";

$apiUser = UserDTO::fromArray([
    'username' => 'api_user',
    'email' => 'api@example.com',
    'password' => 'ApiPassword789',
]);

$response = $apiUser->toArray();
echo "API Response:\n";
echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
echo "\nNote: Password is hashed, not plain text ✅\n\n";

// Example 6: Multiple Users with Same Password
echo "Example 6: Multiple Users with Same Password\n";
echo "---------------------------------------------\n";

$user1 = UserDTO::fromArray([
    'username' => 'user1',
    'email' => 'user1@example.com',
    'password' => 'SamePassword',
]);

$user2 = UserDTO::fromArray([
    'username' => 'user2',
    'email' => 'user2@example.com',
    'password' => 'SamePassword',
]);

echo "User 1 password hash: {$user1->password}\n";
echo "User 2 password hash: {$user2->password}\n";
echo "Hashes are different: " . ($user1->password !== $user2->password ? 'Yes ✅' : 'No ❌') . "\n";
echo "(This is expected - bcrypt uses random salts)\n\n";

echo "Both passwords verify correctly:\n";
echo "User 1: " . (HashedCast::verify('SamePassword', $user1->password) ? 'Valid ✅' : 'Invalid ❌') . "\n";
echo "User 2: " . (HashedCast::verify('SamePassword', $user2->password) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

