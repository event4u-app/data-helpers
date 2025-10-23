<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Casts\HashedCast;
use event4u\DataHelpers\SimpleDTO\Enums\HashAlgorithm;

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

/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $user->username, PHP_EOL);
echo sprintf('Email: %s%s', $user->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Password (hashed): %s%s', $user->password, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Hash starts with \$2y\$: " . (str_starts_with((string)$user->password, '$2y$') ? 'Yes' : 'No') . "\n\n";

// Example 2: Password Verification
echo "Example 2: Password Verification\n";
echo "---------------------------------\n";

$loginAttempt1 = 'MySecretPassword123!';
$loginAttempt2 = 'WrongPassword';

echo "Login attempt 1: '{$loginAttempt1}'\n";
/** @phpstan-ignore-next-line unknown */
echo "Valid: " . (HashedCast::verify($loginAttempt1, $user->password) ? 'Yes ✅' : 'No ❌') . "\n\n";

echo "Login attempt 2: '{$loginAttempt2}'\n";
/** @phpstan-ignore-next-line unknown */
echo "Valid: " . (HashedCast::verify($loginAttempt2, $user->password) ? 'Yes ✅' : 'No ❌') . "\n\n";

// Example 3: Different Hashing Algorithms
echo "Example 3: Different Hashing Algorithms\n";
echo "----------------------------------------\n";
echo "💡 Tip: Use HashAlgorithm enum for type-safe algorithm selection!\n";
echo "    Available: Bcrypt, Argon2i, Argon2id\n\n";

class SecureUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}

    protected function casts(): array
    {
        // ✨ Using enum value for type safety!
        return ['password' => 'hashed:' . HashAlgorithm::Argon2id->value];
    }
}

if (defined('PASSWORD_ARGON2ID')) {
    $secureUser = SecureUserDTO::fromArray([
        'username' => 'jane_doe',
        'password' => 'SuperSecure456!',
    ]);

    echo sprintf('Username: %s%s', $secureUser->username, PHP_EOL);
    echo sprintf('Password (argon2id): %s%s', $secureUser->password, PHP_EOL);
    echo "Hash starts with \$argon2id\$: " . (str_starts_with(
            $secureUser->password,
        '$argon2id$'
    ) ? 'Yes' : 'No') . "\n";
    echo "Verification: " . (HashedCast::verify(
        'SuperSecure456!',
        $secureUser->password
    ) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";
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

/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $existingUser->username, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Password unchanged: " . ($existingUser->password === $hashedPassword ? 'Yes ✅' : 'No ❌') . "\n";
echo "Verification: " . (HashedCast::verify(
    'ExistingPassword',
    /** @phpstan-ignore-next-line unknown */
    $existingUser->password
) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";

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

/** @phpstan-ignore-next-line unknown */
echo sprintf('User 1 password hash: %s%s', $user1->password, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('User 2 password hash: %s%s', $user2->password, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "Hashes are different: " . ($user1->password !== $user2->password ? 'Yes ✅' : 'No ❌') . "\n";
echo "(This is expected - bcrypt uses random salts)\n\n";

echo "Both passwords verify correctly:\n";
/** @phpstan-ignore-next-line unknown */
echo "User 1: " . (HashedCast::verify('SamePassword', $user1->password) ? 'Valid ✅' : 'Invalid ❌') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "User 2: " . (HashedCast::verify('SamePassword', $user2->password) ? 'Valid ✅' : 'Invalid ❌') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
