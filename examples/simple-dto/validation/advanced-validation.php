<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Confirmed;
use event4u\DataHelpers\SimpleDto\Attributes\ConfirmedBy;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\NotIn;
use event4u\DataHelpers\SimpleDto\Attributes\Required;

echo "=== SimpleDto Advanced Validation Examples ===\n\n";
echo "Note: This example shows validation rules extraction.\n";
echo "For actual validation, use validateAndCreate() in a Laravel environment.\n\n";

// Example 1: NotIn Attribute
echo "1. NotIn Attribute - Forbidden Values\n";
echo str_repeat('-', 50) . "\n";

class UserRegistrationDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(3)]
        #[Max(20)]
        #[NotIn(['admin', 'root', 'system', 'administrator'])]
        public readonly string $username,

        #[Required]
        #[Email]
        public readonly string $email,
    ) {
    }
}

$rules = UserRegistrationDto::getAllRules();
echo "Validation Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf("  %-15s: %s\n", $field, implode(', ', $fieldRules));
}
echo "\n✅  Username 'admin' would be rejected (not_in rule)\n";
echo "✅  Username 'john_doe' would be accepted\n";

echo "\n";

// Example 2: Confirmed Attribute
echo "2. Confirmed Attribute - Password Confirmation\n";
echo str_repeat('-', 50) . "\n";

class PasswordChangeDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Confirmed]
        #[Min(8)]
        public readonly string $password,

        public readonly string $password_confirmed,
    ) {
    }
}

$rules = PasswordChangeDto::getAllRules();
echo "Validation Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf("  %-25s: %s\n", $field, implode(', ', $fieldRules));
}
echo "\n✅  Laravel will automatically check that 'password_confirmed' matches 'password'\n";
echo "✅  The 'confirmed' rule looks for a field with '_confirmed' suffix\n";

echo "\n";

// Example 3: ConfirmedBy Attribute - Custom Confirmation Field
echo "3. ConfirmedBy Attribute - Custom Confirmation Field\n";
echo str_repeat('-', 50) . "\n";

class EmailChangeDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[ConfirmedBy('emailVerification')]
        public readonly string $newEmail,

        #[Required]
        #[Email]
        public readonly string $emailVerification,
    ) {
    }
}

$rules = EmailChangeDto::getAllRules();
echo "Validation Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf("  %-25s: %s\n", $field, implode(', ', $fieldRules));
}
echo "\n✅  ConfirmedBy uses 'same:emailVerification' rule\n";
echo "✅  This allows custom confirmation field names\n";

echo "\n";

// Example 4: Complex Registration Form
echo "4. Complex Registration Form - Multiple Validations\n";
echo str_repeat('-', 50) . "\n";

class ComplexRegistrationDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(3)]
        #[Max(20)]
        #[NotIn(['admin', 'root', 'system'])]
        public readonly string $username,

        #[Required]
        #[Email]
        #[Max(255)]
        public readonly string $email,

        #[Required]
        #[Confirmed]
        #[Min(8)]
        public readonly string $password,

        public readonly string $password_confirmed,

        #[Required]
        #[In(['user', 'moderator', 'editor'])]
        public readonly string $role,
    ) {
    }
}

$rules = ComplexRegistrationDto::getAllRules();
echo "All Validation Rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf("  %-25s: %s\n", $field, implode(', ', $fieldRules));
}

echo "\n✅  This Dto combines multiple validation attributes\n";
echo "✅  NotIn prevents reserved usernames\n";
echo "✅  Confirmed ensures password match\n";
echo "✅  In restricts role to allowed values\n";

echo "\n=== Examples Complete ===\n";
