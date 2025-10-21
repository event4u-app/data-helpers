<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Between;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\ValidationException;

echo "=== Phase 15.1: Framework-Independent Request Validation ===\n\n";

// Example 1: Basic Validation with ValidateRequest Attribute
echo "1. Basic Validation with ValidateRequest Attribute\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true)]
class UserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Between(18, 120)]
        public readonly int $age,
    ) {}
}

try {
    $user = UserDTO::validateAndCreate([
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'age' => 30,
    ]);

    echo "✅  Validation passed!\n";
    echo sprintf('    Email: %s%s', $user->email, PHP_EOL);
    echo sprintf('    Name: %s%s', $user->name, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('    Age: %s%s', $user->age, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('❌  Validation failed: %s%s', $validationException->getMessage(), PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo json_encode($validationException->errors(), JSON_PRETTY_PRINT) . PHP_EOL;
}

echo "\n";

// Example 2: Validation Failure
echo "2. Validation Failure\n";
echo str_repeat('-', 60) . "\n";

try {
    $user = UserDTO::validateAndCreate([
        'email' => 'invalid-email',
        'name' => 'Jo',  // Too short
        'age' => 15,     // Too young
    ]);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed!\n";
    /** @phpstan-ignore-next-line unknown */
    echo "    Error count: {$validationException->errorCount()}\n\n";

    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $messages) {
        echo "    {$field}:\n";
        foreach ($messages as $message) {
            echo sprintf('      - %s%s', $message, PHP_EOL);
        }
    }
}

echo "\n";

// Example 3: ValidationResult (without throwing)
echo "3. ValidationResult (without throwing)\n";
echo str_repeat('-', 60) . "\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(3)]
        public readonly string $name,

        #[Required]
        #[Min(0)]
        public readonly float $price,
    ) {}
}

$result = ProductDTO::validateData([
    'name' => 'Product',
    'price' => 99.99,
]);

if ($result->isValid()) {
    echo "✅  Validation passed!\n";
    echo "    Validated data:\n";
    foreach ($result->validated() as $key => $value) {
        echo sprintf('      %s: %s%s', $key, $value, PHP_EOL);
    }

    $product = ProductDTO::fromArray($result->validated());
    echo sprintf('    Created DTO: %s - $%s%s', $product->name, $product->price, PHP_EOL);
} else {
    echo "❌  Validation failed!\n";
    foreach ($result->errors() as $field => $messages) {
        echo sprintf('    %s: ', $field) . implode(', ', $messages) . "\n";
    }
}

echo "\n";

// Example 4: ValidationResult with Errors
echo "4. ValidationResult with Errors\n";
echo str_repeat('-', 60) . "\n";

$result = ProductDTO::validateData([
    'name' => 'P',  // Too short
    'price' => -10, // Negative
]);

if ($result->isFailed()) {
    echo "❌  Validation failed!\n";
    echo "    Error count: {$result->errorCount()}\n\n";

    foreach ($result->errors() as $field => $messages) {
        echo "    {$field}:\n";
        foreach ($messages as $message) {
            echo sprintf('      - %s%s', $message, PHP_EOL);
        }
    }

    echo "\n    First error for 'name': {$result->firstError('name')}\n";
    echo "    Has error for 'price': " . ($result->hasError('price') ? 'Yes' : 'No') . "\n";
}

echo "\n";

// Example 5: Partial Validation (only/except)
echo "5. Partial Validation (only/except)\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true, only: ['email', 'name'])]
class PartialUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,

        public readonly ?string $bio = null,  // No validation rules
    ) {}
}

try {
    // Only email and name are validated, bio is skipped
    $user = PartialUserDTO::validateAndCreate([
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'bio' => 'x',  // Would fail if there were min rules
    ]);

    echo "✅  Validation passed (only email and name validated)!\n";
    echo sprintf('    Email: %s%s', $user->email, PHP_EOL);
    echo sprintf('    Name: %s%s', $user->name, PHP_EOL);
    echo "    Bio: {$user->bio} (not validated)\n";
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('❌  Validation failed: %s%s', $validationException->getMessage(), PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $messages) {
        echo sprintf('    %s: ', $field) . implode(', ', $messages) . "\n";
    }
}

echo "\n";

// Example 6: Custom Error Messages
echo "6. Custom Error Messages\n";
echo str_repeat('-', 60) . "\n";

class CustomMessageDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $username,
    ) {}

    protected function messages(): array
    {
        return [
            'email.required' => 'Please provide your email address',
            'email.email' => 'Please provide a valid email address',
            'username.required' => 'Username is required',
            'username.min' => 'Username must be at least 3 characters',
        ];
    }
}

try {
    $user = CustomMessageDTO::validateAndCreate([
        'email' => 'invalid',
        'username' => 'ab',
    ]);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed with custom messages:\n";
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $messages) {
        echo "    {$field}:\n";
        foreach ($messages as $message) {
            echo sprintf('      - %s%s', $message, PHP_EOL);
        }
    }
}

echo "\n";

// Example 7: Custom Attribute Names
echo "7. Custom Attribute Names\n";
echo str_repeat('-', 60) . "\n";

class CustomAttributeDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        public readonly string $password,
    ) {}

    protected function attributes(): array
    {
        return [
            'email' => 'E-Mail-Adresse',
            'password' => 'Passwort',
        ];
    }
}

try {
    $user = CustomAttributeDTO::validateAndCreate([
        'email' => 'invalid',
        'password' => 'short',
    ]);
/** @phpstan-ignore-next-line unknown */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed with custom attribute names:\n";
    /** @phpstan-ignore-next-line unknown */
    foreach ($validationException->errors() as $field => $messages) {
        echo "    {$field}:\n";
        foreach ($messages as $message) {
            echo sprintf('      - %s%s', $message, PHP_EOL);
        }
    }
}

echo "\n";

// Example 8: Auto-Validate Check
echo "8. Auto-Validate Check\n";
echo str_repeat('-', 60) . "\n";

echo "UserDTO should auto-validate: " . (UserDTO::shouldAutoValidate() ? 'Yes' : 'No') . "\n";
echo "ProductDTO should auto-validate: " . (ProductDTO::shouldAutoValidate() ? 'Yes' : 'No') . "\n";

$attr = UserDTO::getValidateRequestAttribute();
if ($attr instanceof ValidateRequest) {
    echo "UserDTO ValidateRequest settings:\n";
    echo "  - throw: " . ($attr->throw ? 'true' : 'false') . "\n";
    echo "  - stopOnFirstFailure: " . ($attr->stopOnFirstFailure ? 'true' : 'false') . "\n";
    echo "  - only: " . ([] !== $attr->only ? implode(', ', $attr->only) : 'none') . "\n";
    echo "  - except: " . ([] !== $attr->except ? implode(', ', $attr->except) : 'none') . "\n";
}

echo "\n=== All Examples Completed! ===\n";

