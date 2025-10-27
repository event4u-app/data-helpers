<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\EndsWith;
use event4u\DataHelpers\SimpleDto\Attributes\Exists;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Ip;
use event4u\DataHelpers\SimpleDto\Attributes\Json;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Size;
use event4u\DataHelpers\SimpleDto\Attributes\StartsWith;
use event4u\DataHelpers\SimpleDto\Attributes\Unique;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;
use event4u\DataHelpers\Validation\ValidationException;

echo str_repeat('=', 80) . "\n";
echo "LARAVEL VALIDATION INTEGRATION\n";
echo str_repeat('=', 80) . "\n\n";

// Example 1: Basic Laravel-Compatible Validation Rules
echo "1. BASIC LARAVEL-COMPATIBLE RULES:\n";
echo str_repeat('-', 80) . "\n";

class UserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        #[Max(50)]
        public readonly string $name,

        #[Between(18, 120)]
        public readonly int $age,

        #[In(['admin', 'user', 'guest'])]
        public readonly string $role,
    ) {}
}

// Show generated Laravel rules
$rules = UserDto::getAllRules();
echo "Generated Laravel Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

// Validate valid data
try {
    $user = UserDto::validateAndCreate([
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'age' => 30,
        'role' => 'admin',
    ]);
    echo "\n✅  Valid user created: {$user->name} ({$user->email})\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "\n❌  Validation failed (unexpected)\n";
}

// Validate invalid data
try {
    UserDto::validateAndCreate([
        'email' => 'invalid-email',
        'name' => 'Jo',
        'age' => 15,
        'role' => 'superadmin',
    ]);
    echo "\n❌  Invalid user created (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "\n✅  Validation failed (expected):\n";
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}

// Example 2: Database Validation Rules (Exists & Unique)
echo "\n\n2. DATABASE VALIDATION RULES:\n";
echo str_repeat('-', 80) . "\n";

class ProductDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Exists('categories', 'id')]
        public readonly int $categoryId,

        #[Required]
        #[Unique('products', 'sku')]
        public readonly string $sku,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

$rules = ProductDto::getAllRules();
echo "Generated Laravel Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\nNote: These rules are Laravel-compatible and will work with Laravel's validator.\n";
echo "      The framework-independent validator will skip 'exists' and 'unique' rules.\n";

// Example 3: Advanced String Validation
echo "\n\n3. ADVANCED STRING VALIDATION:\n";
echo str_repeat('-', 80) . "\n";

class WebsiteDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[StartsWith(['http://', 'https://'])]
        #[EndsWith(['.com', '.org', '.net'])]
        public readonly string $url,

        #[Required]
        #[Size(10)]
        public readonly string $phoneNumber,

        #[Required]
        #[Ip]
        public readonly string $ipAddress,
    ) {}
}

$rules = WebsiteDto::getAllRules();
echo "Generated Laravel Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

try {
    $website = WebsiteDto::validateAndCreate([
        'url' => 'https://example.com',
        'phoneNumber' => '1234567890',
        'ipAddress' => '192.168.1.1',
    ]);
    echo "\n✅  Valid website created\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException) {
    echo "\n❌  Validation failed (unexpected)\n";
}

// Example 4: Password Confirmation
echo "\n\n4. PASSWORD CONFIRMATION:\n";
echo str_repeat('-', 80) . "\n";

class PasswordDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(8)]
        #[Confirmed]
        public readonly string $password,
    ) {}
}

$rules = PasswordDto::getAllRules();
echo "Generated Laravel Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

try {
    $password = PasswordDto::validateAndCreate([
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    echo "\n✅  Password validated successfully\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException) {
    echo "\n❌  Validation failed (unexpected)\n";
}

try {
    PasswordDto::validateAndCreate([
        'password' => 'secret123',
        'password_confirmation' => 'different',
    ]);
    echo "\n❌  Password mismatch not detected (unexpected)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException) {
    echo "\n✅  Password mismatch detected (expected)\n";
}

// Example 5: JSON Settings
echo "\n\n5. JSON SETTINGS VALIDATION:\n";
echo str_repeat('-', 80) . "\n";

class SettingsDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Json]
        public readonly string $preferences,

        #[Required]
        #[Uuid]
        public readonly string $userId,
    ) {}
}

$rules = SettingsDto::getAllRules();
echo "Generated Laravel Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

try {
    $settings = SettingsDto::validateAndCreate([
        'preferences' => '{"theme": "dark", "language": "en"}',
        'userId' => '550e8400-e29b-41d4-a716-446655440000',
    ]);
    echo "\n✅  Valid settings created\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException) {
    echo "\n❌  Validation failed (unexpected)\n";
}

// Example 6: Custom Rules with Attributes
echo "\n\n6. CUSTOM RULES WITH ATTRIBUTES:\n";
echo str_repeat('-', 80) . "\n";

class CustomUserDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}

    protected function rules(): array
    {
        return [
            'email' => 'unique:users,email',
            'name' => 'regex:/^[a-zA-Z\s]+$/',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'This email is already taken.',
            'name.regex' => 'The name may only contain letters and spaces.',
        ];
    }
}

$rules = CustomUserDto::getAllRules();
echo "Generated Laravel Rules (merged with custom rules):\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  - %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\nNote: Custom messages are defined in the messages() method:\n";
echo "  - email.unique: This email is already taken.\n";
echo "  - name.regex: The name may only contain letters and spaces.\n";

echo "\n\n" . str_repeat('=', 80) . "\n";
echo "✅  All Laravel validation integration examples completed!\n";
echo str_repeat('=', 80) . "\n\n";

echo "USAGE IN LARAVEL:\n";
echo str_repeat('-', 80) . "\n";
echo <<<'USAGE'
// In a Laravel Controller:
public function store(Request $request)
{
    // Option 1: Automatic validation with type-hinted parameter
    public function store(UserDto $user)
    {
        // $user is already validated and created
        return response()->json($user);
    }

    // Option 2: Manual validation
    try {
        $user = UserDto::validateAndCreate($request->all());
        return response()->json($user);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }
}

// In a Laravel FormRequest:
class StoreUserRequest extends DtoFormRequest
{
    protected string $dtoClass = UserDto::class;
}

// The Dtos automatically integrate with Laravel's validator when available.
// All validation attributes generate Laravel-compatible rules.
USAGE;
echo "\n" . str_repeat('=', 80) . "\n";
