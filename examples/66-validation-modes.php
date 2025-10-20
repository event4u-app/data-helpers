<?php

declare(strict_types=1);

/**
 * Phase 15.4: Validation Modes
 *
 * This example demonstrates different validation modes:
 * - Auto-validate on fromArray()
 * - Manual validation with validate()
 * - Throw exception with validateOrFail()
 * - Non-throwing validation with validateData()
 * - Controller injection with auto-validation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Validation\ValidationException;

echo "=== Phase 15.4: Validation Modes ===\n\n";

// Example 1: Auto-validate on fromArray()
echo "1. Auto-validate on fromArray()\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true, auto: true)]
class AutoValidateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

try {
    $dto = AutoValidateUserDTO::fromArray([
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
    echo sprintf('✅  Valid data: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}

try {
    $dto = AutoValidateUserDTO::fromArray([
        'email' => 'invalid-email',
        'name' => 'Jo',
    ]);
    echo sprintf('✅  Valid data: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed (expected):\n";
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 2: Manual validation with validate()
echo "2. Manual validation with validate()\n";
echo str_repeat('-', 60) . "\n";

class ManualValidateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

$data = [
    'email' => 'jane@example.com',
    'name' => 'Jane Doe',
];

try {
    $validated = ManualValidateUserDTO::validate($data);
    echo "✅  Validation passed\n";
    echo "    Validated data: " . json_encode($validated) . "\n";

    $dto = ManualValidateUserDTO::fromArray($validated);
    echo sprintf('    DTO created: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}
echo "\n";

// Example 3: Throw exception with validateOrFail()
echo "3. Throw exception with validateOrFail()\n";
echo str_repeat('-', 60) . "\n";

$validData = [
    'email' => 'bob@example.com',
    'name' => 'Bob Smith',
];

$invalidData = [
    'email' => 'not-an-email',
    'name' => 'Bo',
];

try {
    $validated = ManualValidateUserDTO::validateOrFail($validData);
    echo "✅  Valid data passed: " . json_encode($validated) . "\n";
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}

try {
    $validated = ManualValidateUserDTO::validateOrFail($invalidData);
    echo "✅  Valid data passed: " . json_encode($validated) . "\n";
} catch (ValidationException $validationException) {
    echo "❌  Invalid data failed (expected):\n";
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 4: Non-throwing validation with validateData()
echo "4. Non-throwing validation with validateData()\n";
echo str_repeat('-', 60) . "\n";

$result1 = ManualValidateUserDTO::validateData($validData);
if ($result1->isValid()) {
    echo "✅  Valid data:\n";
    echo "    Validated: " . json_encode($result1->validated()) . "\n";
    $dto = ManualValidateUserDTO::fromArray($result1->validated());
    echo sprintf('    DTO: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} else {
    echo "❌  Validation failed\n";
}

$result2 = ManualValidateUserDTO::validateData($invalidData);
if ($result2->isValid()) {
    echo "✅  Valid data\n";
} else {
    echo "❌  Invalid data (expected):\n";
    foreach ($result2->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 5: ValidateRequest with throw: true
echo "5. ValidateRequest with throw: true\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true)]
class ThrowingUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

try {
    $dto = ThrowingUserDTO::validateAndCreate($validData);
    echo sprintf('✅  Valid data: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}

try {
    $dto = ThrowingUserDTO::validateAndCreate($invalidData);
    echo sprintf('✅  Valid data: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Invalid data failed (expected):\n";
    foreach ($validationException->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 6: ValidateRequest with throw: false
echo "6. ValidateRequest with throw: false\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: false)]
class NonThrowingUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

$result3 = NonThrowingUserDTO::validateData($validData);
if ($result3->isValid()) {
    echo "✅  Valid data:\n";
    echo "    Validated: " . json_encode($result3->validated()) . "\n";
} else {
    echo "❌  Validation failed\n";
}

$result4 = NonThrowingUserDTO::validateData($invalidData);
if ($result4->isValid()) {
    echo "✅  Valid data\n";
} else {
    echo "❌  Invalid data (expected):\n";
    foreach ($result4->errors() as $field => $errors) {
        echo sprintf('    - %s: ', $field) . implode(', ', $errors) . "\n";
    }
}
echo "\n";

// Example 7: Partial validation (only/except)
echo "7. Partial validation (only/except)\n";
echo str_repeat('-', 60) . "\n";

#[ValidateRequest(throw: true, only: ['email'])]
class PartialOnlyUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

try {
    // Only email is validated, name is ignored
    $validated = PartialOnlyUserDTO::validateOrFail([
        'email' => 'test@example.com',
        'name' => 'X', // Too short, but not validated
    ]);
    $dto = PartialOnlyUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'X',
    ]);
    echo sprintf('✅  Only email validated: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}

#[ValidateRequest(throw: true, except: ['name'])]
class PartialExceptUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(3)]
        public readonly string $name,
    ) {}
}

try {
    // Name is excluded from validation
    $validated = PartialExceptUserDTO::validateOrFail([
        'email' => 'test@example.com',
        'name' => 'X', // Too short, but not validated
    ]);
    $dto = PartialExceptUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'X',
    ]);
    echo sprintf('✅  Name excluded from validation: %s, %s%s', $dto->email, $dto->name, PHP_EOL);
} catch (ValidationException $validationException) {
    echo "❌  Validation failed: " . $validationException->getMessage() . "\n";
}
echo "\n";

echo "=== Validation Modes Complete! ===\n";
echo "\n";
echo "Summary:\n";
echo "  ✅  Auto-validate on fromArray() (#[ValidateRequest(auto: true)])\n";
echo "  ✅  Manual validation (validate())\n";
echo "  ✅  Throw exception (validateOrFail())\n";
echo "  ✅  Non-throwing validation (validateData())\n";
echo "  ✅  ValidateRequest with throw: true/false\n";
echo "  ✅  Partial validation (only/except)\n";

