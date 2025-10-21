<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Regex;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\ValidationException;

echo "=================================================================\n";
echo "NESTED DTO VALIDATION EXAMPLES\n";
echo "=================================================================\n\n";

// ============================================================================
// Example 1: Simple Nested DTO Validation
// ============================================================================

echo "1. SIMPLE NESTED DTO VALIDATION:\n";
echo str_repeat('-', 60) . "\n";

class AddressDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(3)]
        public readonly string $street,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(2)]
        public readonly string $city,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        #[Regex('/^\d{5}$/')]
        public readonly string $zipCode,
    ) {}
}

class UserDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly string $email,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(2)]
        public readonly string $name,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly AddressDTO $address,
    ) {}
}

// Valid data - nested arrays are automatically converted to DTOs
$validData = [
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'zipCode' => '10001',
    ],
];

try {
    $user = UserDTO::fromArray($validData);
    echo "✅  Valid nested DTO created:\n";
    echo sprintf('    Email: %s%s', $user->email, PHP_EOL);
    echo sprintf('    Name: %s%s', $user->name, PHP_EOL);
    echo sprintf(
        '    Address: %s, %s %s%s',
        /** @phpstan-ignore-next-line phpstan-error */
        $user->address->street,
        /** @phpstan-ignore-next-line phpstan-error */
        $user->address->city,
        /** @phpstan-ignore-next-line phpstan-error */
        $user->address->zipCode,
        PHP_EOL
    );
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed (unexpected)\n";
}
echo "\n";

// Invalid nested data
$invalidData = [
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'address' => [
        'street' => 'AB',  // Too short (min: 3)
        'city' => 'NY',    // Valid
        'zipCode' => '123', // Invalid format (must be 5 digits)
    ],
];

try {
    $user = UserDTO::validateAndCreate($invalidData);
    echo "✅  Valid nested DTO created (unexpected)\n";
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Nested validation failed (expected):\n";
    /** @phpstan-ignore-next-line phpstan-error */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// ============================================================================
// Example 2: Multiple Nested DTOs
// ============================================================================

echo "2. MULTIPLE NESTED DTOs:\n";
echo str_repeat('-', 60) . "\n";

class CompanyDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(2)]
        public readonly string $name,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly AddressDTO $mainAddress,

        public readonly ?AddressDTO $billingAddress = null,
    ) {}
}

$validCompanyData = [
    'name' => 'Acme Corp',
    'mainAddress' => [
        'street' => '456 Business Ave',
        'city' => 'San Francisco',
        'zipCode' => '94102',
    ],
    'billingAddress' => [
        'street' => '789 Finance St',
        'city' => 'Los Angeles',
        'zipCode' => '90001',
    ],
];

try {
    $company = CompanyDTO::fromArray($validCompanyData);
    echo "✅  Company with multiple addresses created:\n";
    echo sprintf('    Name: %s%s', $company->name, PHP_EOL);
    /** @phpstan-ignore-next-line phpstan-error */
    echo sprintf('    Main: %s%s', $company->mainAddress->city, PHP_EOL);
    /** @phpstan-ignore-next-line phpstan-error */
    echo sprintf('    Billing: %s%s', $company->billingAddress->city, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed (unexpected)\n";
}
echo "\n";

// ============================================================================
// Example 3: Auto-Validation with Nested DTOs
// ============================================================================

echo "3. AUTO-VALIDATION WITH NESTED DTOs:\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(auto: true, throw: true)]
class AutoValidatedUserDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly string $email,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly AddressDTO $address,
    ) {}
}

try {
    $user = AutoValidatedUserDTO::fromArray([
        'email' => 'invalid-email',  // Invalid email
        'address' => [
            'street' => 'AB',  // Too short
            'city' => 'NY',
            'zipCode' => '123',  // Invalid format
        ],
    ]);
    echo "✅  User created (unexpected)\n";
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Auto-validation failed (expected):\n";
    /** @phpstan-ignore-next-line phpstan-error */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// ============================================================================
// Example 4: Validation Rules Inspection
// ============================================================================

echo "4. VALIDATION RULES INSPECTION:\n";
echo str_repeat('-', 60) . "\n";

$rules = UserDTO::getAllRules();
echo "All validation rules (including nested):\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// ============================================================================
// Example 5: Deeply Nested DTOs
// ============================================================================

echo "5. DEEPLY NESTED DTOs:\n";
echo str_repeat('-', 60) . "\n";

class ContactDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly string $email,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly AddressDTO $address,
    ) {}
}

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(2)]
        public readonly string $name,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly ContactDTO $contact,
    ) {}
}

$deeplyNestedData = [
    'name' => 'Jane Smith',
    'contact' => [
        'email' => 'jane@example.com',
        'address' => [
            'street' => '321 Oak Lane',
            'city' => 'Boston',
            'zipCode' => '02101',
        ],
    ],
];

try {
    $customer = CustomerDTO::fromArray($deeplyNestedData);
    echo "✅  Deeply nested DTO created:\n";
    echo sprintf('    Name: %s%s', $customer->name, PHP_EOL);
    /** @phpstan-ignore-next-line phpstan-error */
    echo sprintf('    Email: %s%s', $customer->contact->email, PHP_EOL);
    /** @phpstan-ignore-next-line phpstan-error */
    echo sprintf('    City: %s%s', $customer->contact->address->city, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Validation failed (unexpected)\n";
}
echo "\n";

// Invalid deeply nested data
$invalidDeeplyNestedData = [
    'name' => 'Jane Smith',
    'contact' => [
        'email' => 'invalid-email',  // Invalid
        'address' => [
            'street' => 'AB',  // Too short
            'city' => 'Boston',
            'zipCode' => '123',  // Invalid format
        ],
    ],
];

try {
    $customer = CustomerDTO::validateAndCreate($invalidDeeplyNestedData);
    echo "✅  Customer created (unexpected)\n";
/** @phpstan-ignore-next-line phpstan-error */
} catch (ValidationException $validationException) {
    echo "❌  Deeply nested validation failed (expected):\n";
    /** @phpstan-ignore-next-line phpstan-error */
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

echo "=================================================================\n";
echo "✅  All nested validation examples completed successfully!\n";
echo "=================================================================\n";

