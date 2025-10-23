<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDTO\Enums\NamingConvention;
use event4u\DataHelpers\SimpleDTO\Support\NameTransformer;

echo "=== NamingConvention Enum Example ===\n\n";

// Example 1: Using NamingConvention enum in MapInputName
echo "1️⃣  MapInputName with Enum:\n";
echo str_repeat('-', 50) . "\n";

#[MapInputName(NamingConvention::SnakeCase)]
class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
        public readonly int $userId,
    ) {}
}

$userData = [
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com',
    'user_id' => 123,
];

$user = UserDTO::fromArray($userData);
echo "Input (snake_case): " . json_encode($userData) . "\n";
echo "DTO properties (camelCase): userName={$user->userName}, emailAddress={$user->emailAddress}, userId={$user->userId}\n\n";

// Example 2: Using NamingConvention enum in MapOutputName
echo "2️⃣  MapOutputName with Enum:\n";
echo str_repeat('-', 50) . "\n";

#[MapOutputName(NamingConvention::KebabCase)]
class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $productName,
        public readonly float $productPrice,
        public readonly string $categoryName,
    ) {}
}

$product = new ProductDTO('Laptop', 999.99, 'Electronics');
$output = $product->toArray();
echo "DTO properties (camelCase): productName, productPrice, categoryName\n";
echo "Output (kebab-case): " . json_encode($output) . "\n\n";

// Example 3: Using NameTransformer with enum
echo "3️⃣  NameTransformer with Enum:\n";
echo str_repeat('-', 50) . "\n";

$names = ['userName', 'emailAddress', 'productPrice'];

foreach (NamingConvention::cases() as $convention) {
    echo $convention->value . ':
';
    foreach ($names as $name) {
        $transformed = $convention->transform($name);
        echo sprintf('  %s → %s%s', $name, $transformed, PHP_EOL);
    }
    echo "\n";
}

// Example 4: Backward compatibility with strings
echo "4️⃣  Backward Compatibility (String):\n";
echo str_repeat('-', 50) . "\n";

#[MapInputName('snake_case')]
class LegacyDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}

$legacyData = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
];

$legacy = LegacyDTO::fromArray($legacyData);
echo "String-based MapInputName still works!\n";
echo "Input: " . json_encode($legacyData) . "\n";
echo "DTO: firstName={$legacy->firstName}, lastName={$legacy->lastName}\n\n";

// Example 5: All naming conventions
echo "5️⃣  All Naming Conventions:\n";
echo str_repeat('-', 50) . "\n";

$originalName = 'myPropertyName';
echo "Original: {$originalName}\n\n";

echo "Using Enum:\n";
foreach (NamingConvention::cases() as $convention) {
    $transformed = $convention->transform($originalName);
    echo sprintf('  %s (%s): %s%s', $convention->name, $convention->value, $transformed, PHP_EOL);
}

echo "\nUsing NameTransformer (backward compatible):\n";
echo "  snake_case: " . NameTransformer::transform($originalName, 'snake_case') . "\n";
echo "  camelCase: " . NameTransformer::transform($originalName, 'camelCase') . "\n";
echo "  kebab-case: " . NameTransformer::transform($originalName, 'kebab-case') . "\n";
echo "  PascalCase: " . NameTransformer::transform($originalName, 'PascalCase') . "\n\n";

// Example 6: Enum helper methods
echo "6️⃣  Enum Helper Methods:\n";
echo str_repeat('-', 50) . "\n";

echo "All values: " . implode(', ', NamingConvention::values()) . "\n";
echo "Is 'snake_case' valid? " . (NamingConvention::isValid('snake_case') ? 'Yes' : 'No') . "\n";
echo "Is 'invalid_format' valid? " . (NamingConvention::isValid('invalid_format') ? 'Yes' : 'No') . "\n";

$parsed = NamingConvention::fromString('kebab-case');
echo "Parse 'kebab-case': " . ($parsed instanceof NamingConvention ? $parsed->name : 'null') . "\n\n";

// Example 7: Complex transformation
echo "7️⃣  Complex Transformation:\n";
echo str_repeat('-', 50) . "\n";

#[MapInputName(NamingConvention::SnakeCase)]
#[MapOutputName(NamingConvention::PascalCase)]
class ComplexDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $emailAddress,
    ) {}
}

$complexInput = [
    'first_name' => 'Alice',
    'last_name' => 'Johnson',
    'email_address' => 'alice@example.com',
];

$complex = ComplexDTO::fromArray($complexInput);
$complexOutput = $complex->toArray();

echo "Input (snake_case): " . json_encode($complexInput) . "\n";
echo "DTO (camelCase): firstName, lastName, emailAddress\n";
echo "Output (PascalCase): " . json_encode($complexOutput) . "\n\n";

echo "✅ All examples completed successfully!\n";
