<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\Different;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Ip;
use event4u\DataHelpers\SimpleDTO\Attributes\Json;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Same;
use event4u\DataHelpers\SimpleDTO\Attributes\Size;
use event4u\DataHelpers\SimpleDTO\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDTO\Attributes\Uuid;
use event4u\DataHelpers\Validation\ValidationException;

echo "=================================================================\n";
echo "ADVANCED VALIDATION ATTRIBUTES\n";
echo "=================================================================\n\n";

// Example 1: Size Validation
echo "1. SIZE VALIDATION:\n";
echo "------------------------------------------------------------\n";

class PhoneDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Size(10)]  // Exactly 10 characters
        public readonly string $phoneNumber,
    ) {}
}

try {
    $phone = PhoneDTO::validateAndCreate(['phoneNumber' => '1234567890']);
    echo sprintf('✅  Valid phone: %s%s', $phone->phoneNumber, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $phone = PhoneDTO::validateAndCreate(['phoneNumber' => '123']);
    echo "✅  Valid phone (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid phone (expected): " . $validationException->firstError('phoneNumber') . "\n";
}
echo "\n";

// Example 2: StartsWith / EndsWith
echo "2. STARTS WITH / ENDS WITH VALIDATION:\n";
echo "------------------------------------------------------------\n";

class WebsiteDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[StartsWith(['http://', 'https://'])]
        public readonly string $url,
        
        #[Required]
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly string $domain,
    ) {}
}

try {
    $website = WebsiteDTO::validateAndCreate([
        'url' => 'https://example.com',
        'domain' => 'example.com',
    ]);
    echo sprintf('✅  Valid website: %s%s', $website->url, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $website = WebsiteDTO::validateAndCreate([
        'url' => 'ftp://example.com',
        'domain' => 'example.de',
    ]);
    echo "✅  Valid website (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid website (expected):\n";
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 3: IP Address Validation
echo "3. IP ADDRESS VALIDATION:\n";
echo "------------------------------------------------------------\n";

class ServerDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Ip]
        public readonly string $ipAddress,
    ) {}
}

try {
    $server = ServerDTO::validateAndCreate(['ipAddress' => '192.168.1.1']);
    echo sprintf('✅  Valid IP: %s%s', $server->ipAddress, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $server = ServerDTO::validateAndCreate(['ipAddress' => '999.999.999.999']);
    echo "✅  Valid IP (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid IP (expected): " . $validationException->firstError('ipAddress') . "\n";
}
echo "\n";

// Example 4: JSON Validation
echo "4. JSON VALIDATION:\n";
echo "------------------------------------------------------------\n";

class ConfigDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Json]
        public readonly string $settings,
    ) {}
}

try {
    $config = ConfigDTO::validateAndCreate(['settings' => '{"key": "value"}']);
    echo sprintf('✅  Valid JSON: %s%s', $config->settings, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $config = ConfigDTO::validateAndCreate(['settings' => 'not-json']);
    echo "✅  Valid JSON (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid JSON (expected): " . $validationException->firstError('settings') . "\n";
}
echo "\n";

// Example 5: UUID Validation
echo "5. UUID VALIDATION:\n";
echo "------------------------------------------------------------\n";

class EntityDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Uuid]
        public readonly string $id,
    ) {}
}

try {
    $entity = EntityDTO::validateAndCreate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
    echo sprintf('✅  Valid UUID: %s%s', $entity->id, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $entity = EntityDTO::validateAndCreate(['id' => 'not-a-uuid']);
    echo "✅  Valid UUID (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid UUID (expected): " . $validationException->firstError('id') . "\n";
}
echo "\n";

// Example 6: Same / Different Validation
echo "6. SAME / DIFFERENT VALIDATION:\n";
echo "------------------------------------------------------------\n";

class PasswordDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $password,
        
        #[Required]
        #[Same('password')]
        public readonly string $passwordConfirmation,
    ) {}
}

try {
    $pwd = PasswordDTO::validateAndCreate([
        'password' => 'secret123',
        'passwordConfirmation' => 'secret123',
    ]);
    echo "✅  Passwords match\n";
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $pwd = PasswordDTO::validateAndCreate([
        'password' => 'secret123',
        'passwordConfirmation' => 'different',
    ]);
    echo "✅  Passwords match (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Passwords don't match (expected): " . $validationException->firstError('passwordConfirmation') . "\n";
}
echo "\n";

class EmailDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,
        
        #[Required]
        #[Email]
        #[Different('email')]
        public readonly string $alternativeEmail,
    ) {}
}

try {
    $emails = EmailDTO::validateAndCreate([
        'email' => 'john@example.com',
        'alternativeEmail' => 'jane@example.com',
    ]);
    echo "✅  Emails are different\n";
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $emails = EmailDTO::validateAndCreate([
        'email' => 'john@example.com',
        'alternativeEmail' => 'john@example.com',
    ]);
    echo "✅  Emails are different (unexpected)\n";
} catch (ValidationException $validationException) {
    echo "❌  Emails must be different (expected): " . $validationException->firstError('alternativeEmail') . "\n";
}
echo "\n";

// Example 7: Complex Validation Rules
echo "7. COMPLEX VALIDATION RULES:\n";
echo "------------------------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Between(3, 50)]
        public readonly string $name,
        
        #[Required]
        #[Email]
        public readonly string $email,
        
        #[Required]
        #[Between(18, 120)]
        public readonly int $age,
        
        #[Required]
        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
        
        #[Required]
        #[Uuid]
        public readonly string $id,
        
        #[Required]
        #[Regex('/^[A-Z]{2}\d{6}$/')]  // e.g., AB123456
        public readonly string $code,
    ) {}
}

try {
    $user = UserDTO::validateAndCreate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
        'role' => 'admin',
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'code' => 'AB123456',
    ]);
    echo "✅  Valid user created\n";
    echo sprintf('    Name: %s%s', $user->name, PHP_EOL);
    echo sprintf('    Email: %s%s', $user->email, PHP_EOL);
    echo sprintf('    Age: %s%s', $user->age, PHP_EOL);
    echo sprintf('    Role: %s%s', $user->role, PHP_EOL);
    echo sprintf('    ID: %s%s', $user->id, PHP_EOL);
    echo sprintf('    Code: %s%s', $user->code, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed:\n";
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

echo "=================================================================\n";
echo "✅  All advanced validation attribute examples completed!\n";
echo "=================================================================\n";

