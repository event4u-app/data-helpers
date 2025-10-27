<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Url;
use event4u\DataHelpers\SimpleDto\Attributes\Uuid;

// Example 1: Auto Rule Inferring
echo "Example 1: Auto Rule Inferring\n";
echo str_repeat('=', 80) . "\n\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,        // Auto: required, string
        public readonly int $age,            // Auto: required, integer
        public readonly ?string $bio = null, // Auto: string (not required)
    ) {
    }
}

$rules = UserDto::getAllRules();
echo "Auto-inferred rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 2: Validation Attributes
echo "Example 2: Validation Attributes\n";
echo str_repeat('=', 80) . "\n\n";

class ProductDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Min(3)]
        #[Max(100)]
        public readonly string $name,

        #[Required]
        #[Between(0, 999999)]
        public readonly float $price,

        #[Required]
        #[In(['draft', 'published', 'archived'])]
        public readonly string $status,

        #[Url]
        public readonly ?string $website = null,
    ) {
    }
}

$rules = ProductDto::getAllRules();
echo "Validation rules with attributes:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 3: Email and URL Validation
echo "Example 3: Email and URL Validation\n";
echo str_repeat('=', 80) . "\n\n";

class ContactDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        #[Max(255)]
        public readonly string $email,

        #[Required]
        #[Url]
        public readonly string $website,

        #[Regex('/^\+?[1-9]\d{1,14}$/')]
        public readonly ?string $phone = null,
    ) {
    }
}

$rules = ContactDto::getAllRules();
echo "Contact validation rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 4: UUID Validation
echo "Example 4: UUID Validation\n";
echo str_repeat('=', 80) . "\n\n";

class EntityDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Uuid]
        public readonly string $id,

        #[Required]
        #[Min(1)]
        public readonly string $name,
    ) {
    }
}

$rules = EntityDto::getAllRules();
echo "Entity validation rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 5: Custom Rules
echo "Example 5: Custom Rules\n";
echo str_repeat('=', 80) . "\n\n";

class RegistrationDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Email]
        public readonly string $email,

        #[Required]
        #[Min(8)]
        public readonly string $password,

        #[Required]
        public readonly string $password_confirmation,
    ) {
    }

    protected function rules(): array
    {
        return [
            'password' => 'confirmed', // Custom rule: password must match password_confirmation
        ];
    }

    protected function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'password_confirmation' => 'password confirmation',
        ];
    }
}

$rules = RegistrationDto::getAllRules();
echo "Registration validation rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 6: Complex Validation
echo "Example 6: Complex Validation\n";
echo str_repeat('=', 80) . "\n\n";

class OrderDto extends SimpleDto
{
    public function __construct(
        #[Required]
        #[Uuid]
        public readonly string $order_id,

        #[Required]
        #[Email]
        public readonly string $customer_email,

        #[Required]
        #[Between(1, 1000)]
        public readonly int $quantity,

        #[Required]
        #[Between(0.01, 999999.99)]
        public readonly float $total_amount,

        #[Required]
        #[In(['pending', 'processing', 'completed', 'cancelled'])]
        public readonly string $status,

        #[Regex('/^[A-Z]{2}\d{6}$/')]
        public readonly ?string $tracking_code = null,
    ) {
    }
}

$rules = OrderDto::getAllRules();
echo "Order validation rules:\n";
foreach ($rules as $field => $fieldRules) {
    echo sprintf('  %s: ', $field) . implode(', ', $fieldRules) . "\n";
}
echo "\n";

// Example 7: Rules Caching
echo "Example 7: Rules Caching\n";
echo str_repeat('=', 80) . "\n\n";

$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    OrderDto::getAllRules();
}
$cached = microtime(true) - $start;

OrderDto::clearRulesCache();

$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    OrderDto::getAllRules();
    OrderDto::clearRulesCache();
}
$uncached = microtime(true) - $start;

echo "Performance comparison (1000 iterations):\n";
echo "  With caching: " . number_format($cached * 1000, 2) . " ms\n";
echo "  Without caching: " . number_format($uncached * 1000, 2) . " ms\n";
echo "  Speedup: " . number_format($uncached / $cached, 1) . "x faster\n";
echo "\n";

echo "✅ All examples completed successfully!\n";
echo "\n";
echo "Note: To use validateAndCreate(), you need Laravel's validator configured.\n";
echo "Example usage:\n";
echo "  \$user = UserDto::validateAndCreate(\$request->all());\n";
echo "\n";
