<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Different;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Ip;
use event4u\DataHelpers\SimpleDto\Attributes\Json;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Same;
use event4u\DataHelpers\SimpleDto\Attributes\Size;
use event4u\DataHelpers\SimpleDto\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;
use event4u\DataHelpers\Validation\ValidationException;

echo "=================================================================\n";
echo "ADVANCED VALIDATION ATTRIBUTES\n";
echo "=================================================================\n\n";

// Example 1: Size Validation
echo "1. SIZE VALIDATION:\n";
echo "------------------------------------------------------------\n";

class PhoneDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Size(10)]  // Exactly 10 characters
        public readonly string $phoneNumber,
    ) {}
}

try {
    $phone = PhoneDto::validateAndCreate(['phoneNumber' => '1234567890']);
    echo sprintf('✅  Valid phone: %s%s', $phone->phoneNumber, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $phone = PhoneDto::validateAndCreate(['phoneNumber' => '123']);
    echo "✅  Valid phone (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Invalid phone (expected): " . $validationException->firstError('phoneNumber') . "\n";
}
echo "\n";

// Example 2: StartsWith / EndsWith
echo "2. STARTS WITH / ENDS WITH VALIDATION:\n";
echo "------------------------------------------------------------\n";

class WebsiteDto extends SimpleDto
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
    $website = WebsiteDto::validateAndCreate([
        'url' => 'https://example.com',
        'domain' => 'example.com',
    ]);
    echo sprintf('✅  Valid website: %s%s', $website->url, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $website = WebsiteDto::validateAndCreate([
        'url' => 'ftp://example.com',
        'domain' => 'example.de',
    ]);
    echo "✅  Valid website (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Invalid website (expected):\n";
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 3: IP Address Validation
echo "3. IP ADDRESS VALIDATION:\n";
echo "------------------------------------------------------------\n";

class ServerDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Ip]
        public readonly string $ipAddress,
    ) {}
}

try {
    $server = ServerDto::validateAndCreate(['ipAddress' => '192.168.1.1']);
    echo sprintf('✅  Valid IP: %s%s', $server->ipAddress, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $server = ServerDto::validateAndCreate(['ipAddress' => '999.999.999.999']);
    echo "✅  Valid IP (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Invalid IP (expected): " . $validationException->firstError('ipAddress') . "\n";
}
echo "\n";

// Example 4: JSON Validation
echo "4. JSON VALIDATION:\n";
echo "------------------------------------------------------------\n";

class ConfigDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Json]
        public readonly string $settings,
    ) {}
}

try {
    $config = ConfigDto::validateAndCreate(['settings' => '{"key": "value"}']);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('✅  Valid JSON: %s%s', $config->settings, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $config = ConfigDto::validateAndCreate(['settings' => 'not-json']);
    echo "✅  Valid JSON (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Invalid JSON (expected): " . $validationException->firstError('settings') . "\n";
}
echo "\n";

// Example 5: UUID Validation
echo "5. UUID VALIDATION:\n";
echo "------------------------------------------------------------\n";

class EntityDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Uuid]
        public readonly string $id,
    ) {}
}

try {
    $entity = EntityDto::validateAndCreate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
    echo sprintf('✅  Valid UUID: %s%s', $entity->id, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $entity = EntityDto::validateAndCreate(['id' => 'not-a-uuid']);
    echo "✅  Valid UUID (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Invalid UUID (expected): " . $validationException->firstError('id') . "\n";
}
echo "\n";

// Example 6: Same / Different Validation
echo "6. SAME / DIFFERENT VALIDATION:\n";
echo "------------------------------------------------------------\n";

class PasswordDto extends SimpleDto
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
    $pwd = PasswordDto::validateAndCreate([
        'password' => 'secret123',
        'passwordConfirmation' => 'secret123',
    ]);
    echo "✅  Passwords match\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $pwd = PasswordDto::validateAndCreate([
        'password' => 'secret123',
        'passwordConfirmation' => 'different',
    ]);
    echo "✅  Passwords match (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Passwords don't match (expected): " . $validationException->firstError('passwordConfirmation') . "\n";
}
echo "\n";

class EmailDto extends SimpleDto
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
    $emails = EmailDto::validateAndCreate([
        'email' => 'john@example.com',
        'alternativeEmail' => 'jane@example.com',
    ]);
    echo "✅  Emails are different\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed\n";
}

try {
    $emails = EmailDto::validateAndCreate([
        'email' => 'john@example.com',
        'alternativeEmail' => 'john@example.com',
    ]);
    echo "✅  Emails are different (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo "❌  Emails must be different (expected): " . $validationException->firstError('alternativeEmail') . "\n";
}
echo "\n";

// Example 7: Complex Validation Rules
echo "7. COMPLEX VALIDATION RULES:\n";
echo "------------------------------------------------------------\n";

class UserDto extends SimpleDto
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
    $user = UserDto::validateAndCreate([
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
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('    Age: %s%s', $user->age, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('    Role: %s%s', $user->role, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('    ID: %s%s', $user->id, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('    Code: %s%s', $user->code, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed:\n";
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

echo "=================================================================\n";
echo "✅  All advanced validation attribute examples completed!\n";
echo "=================================================================\n";
