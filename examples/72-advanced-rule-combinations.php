<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\In;
use event4u\DataHelpers\SimpleDTO\Attributes\Max;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use event4u\DataHelpers\SimpleDTO\Attributes\Nullable;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredIf;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredUnless;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWith;
use event4u\DataHelpers\SimpleDTO\Attributes\RequiredWithout;
use event4u\DataHelpers\SimpleDTO\Attributes\Sometimes;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    ADVANCED RULE COMBINATIONS                              ║\n";
echo "║                    Phase 16.4 - Conditional Rules                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Example 1: RequiredIf - Conditional Required Fields
echo "1. REQUIRED IF - CONDITIONAL REQUIRED FIELDS:\n";
echo "------------------------------------------------------------\n";

class ShippingDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredIf('shippingMethod', 'delivery')]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $address = null,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredIf('shippingMethod', 'delivery')]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(100)]
        public readonly ?string $city = null,
    ) {}
}

echo "ShippingDTO Validation Rules:\n";
$rules = ShippingDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Address is only required when shippingMethod is 'delivery'\n";
echo "✅  City is only required when shippingMethod is 'delivery'\n";

echo "\n";

// Example 2: RequiredUnless - Required Unless Condition
echo "2. REQUIRED UNLESS - REQUIRED UNLESS CONDITION:\n";
echo "------------------------------------------------------------\n";

class PaymentDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredUnless('paymentMethod', 'free')]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $paymentDetails = null,
    ) {}
}

echo "PaymentDTO Validation Rules:\n";
$rules = PaymentDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Payment details are required unless payment method is 'free'\n";

echo "\n";

// Example 3: RequiredWith - Required With Other Fields
echo "3. REQUIRED WITH - REQUIRED WITH OTHER FIELDS:\n";
echo "------------------------------------------------------------\n";

class ContactDTO extends SimpleDTO
{
    public function __construct(
        #[Nullable]
        public readonly ?string $phone = null,

        #[Nullable]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly ?string $email = null,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredWith(['phone', 'email'])]
        #[In(['phone', 'email', 'both'])]
        public readonly ?string $contactPreference = null,
    ) {}
}

echo "ContactDTO Validation Rules:\n";
$rules = ContactDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Contact preference is required when phone or email is provided\n";

echo "\n";

// Example 4: RequiredWithout - Required Without Other Fields
echo "4. REQUIRED WITHOUT - REQUIRED WITHOUT OTHER FIELDS:\n";
echo "------------------------------------------------------------\n";

class AlternativeContactDTO extends SimpleDTO
{
    public function __construct(
        #[Nullable]
        public readonly ?string $phone = null,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredWithout(['phone'])]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

echo "AlternativeContactDTO Validation Rules:\n";
$rules = AlternativeContactDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Email is required when phone is not provided\n";
echo "✅  At least one contact method must be provided\n";

echo "\n";

// Example 5: Sometimes - Optional Validation
echo "5. SOMETIMES - OPTIONAL VALIDATION:\n";
echo "------------------------------------------------------------\n";

class UpdateUserDTO extends SimpleDTO
{
    public function __construct(
        #[Sometimes]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $email = null,

        #[Sometimes]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(8)]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $password = null,

        #[Sometimes]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Min(3)]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(100)]
        public readonly ?string $name = null,
    ) {}
}

echo "UpdateUserDTO Validation Rules:\n";
$rules = UpdateUserDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Fields are only validated if they are present in the input\n";
echo "✅  Perfect for partial updates (PATCH requests)\n";

echo "\n";

// Example 6: Nullable - Explicitly Allow Null
echo "6. NULLABLE - EXPLICITLY ALLOW NULL:\n";
echo "------------------------------------------------------------\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        public readonly string $name,

        #[Nullable]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Email]
        public readonly ?string $email = null,

        #[Nullable]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(500)]
        public readonly ?string $bio = null,
    ) {}
}

echo "ProfileDTO Validation Rules:\n";
$rules = ProfileDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Email and bio can be null even with other validation rules\n";

echo "\n";

// Example 7: Complex Conditional Scenario
echo "7. COMPLEX CONDITIONAL SCENARIO:\n";
echo "------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        #[In(['pickup', 'delivery'])]
        public readonly string $shippingMethod,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredIf('shippingMethod', 'delivery')]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $deliveryAddress = null,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[Required]
        #[In(['card', 'cash', 'free'])]
        public readonly string $paymentMethod = '',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredUnless('paymentMethod', 'free')]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(255)]
        public readonly ?string $paymentDetails = null,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[RequiredWith(['deliveryAddress'])]
        public readonly ?string $deliveryInstructions = null,

        #[Sometimes]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Max(500)]
        public readonly ?string $notes = null,
    ) {}
}

echo "OrderDTO Validation Rules:\n";
$rules = OrderDTO::getAllRules();
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}

echo "\n✅  Delivery address is required only for delivery orders\n";
echo "✅  Payment details are required unless payment is free\n";
echo "✅  Delivery instructions are required when delivery address is provided\n";
echo "✅  Notes are optional and only validated if provided\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  RequiredIf - Field required when another field has specific value\n";
echo "✅  RequiredUnless - Field required unless another field has specific value\n";
echo "✅  RequiredWith - Field required when any of specified fields are present\n";
echo "✅  RequiredWithout - Field required when any of specified fields are absent\n";
echo "✅  Sometimes - Field only validated if present in input\n";
echo "✅  Nullable - Field can be null even with other validation rules\n";
echo "✅  All conditional rules work with Laravel, Symfony, and framework-independent validators\n";

echo "\n";

