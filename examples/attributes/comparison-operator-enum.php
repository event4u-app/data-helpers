<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;
use event4u\DataHelpers\SimpleDTO\Enums\ComparisonOperator;

echo "=== ComparisonOperator Enum Example ===\n\n";

// Example 1: Using ComparisonOperator enum in WhenValue
echo "1️⃣  WhenValue with Enum:\n";
echo str_repeat('-', 50) . "\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,

        #[WhenValue('price', ComparisonOperator::GreaterThan, 100)]
        public readonly ?string $premiumBadge = 'PREMIUM',

        #[WhenValue('stock', ComparisonOperator::LessThanOrEqual, 5)]
        public readonly ?string $lowStockWarning = 'LOW STOCK',

        #[WhenValue('price', ComparisonOperator::StrictEqual, 0.0)]
        public readonly ?string $freeLabel = 'FREE',
    ) {}
}

$products = [
    new ProductDTO('Laptop', 1200.00, 10),
    new ProductDTO('Mouse', 25.00, 3),
    new ProductDTO('Ebook', 0.0, 100),
];

foreach ($products as $product) {
    echo sprintf('Product: %s%s', $product->name, PHP_EOL);
    echo sprintf('  Price: $%s, Stock: %s%s', $product->price, $product->stock, PHP_EOL);
    $data = $product->toArray();
    echo "  Badges: ";
    $badges = [];
    if (isset($data['premiumBadge'])) {
        $badges[] = $data['premiumBadge'];
    }
    if (isset($data['lowStockWarning'])) {
        $badges[] = $data['lowStockWarning'];
    }
    if (isset($data['freeLabel'])) {
        $badges[] = $data['freeLabel'];
    }
    echo ([] === $badges ? 'None' : implode(', ', $badges)) . "\n\n";
}

// Example 2: All comparison operators
echo "2️⃣  All Comparison Operators:\n";
echo str_repeat('-', 50) . "\n";

$testValue = 10;
$compareValue = 10;

echo "Testing: {$testValue} vs {$compareValue}\n\n";

foreach (ComparisonOperator::cases() as $operator) {
    $result = $operator->compare($testValue, $compareValue);
    $resultStr = $result ? '✅ true' : '❌ false';
    echo sprintf('  %s (%s): %s%s', $operator->name, $operator->value, $resultStr, PHP_EOL);
}

echo "\n";

// Example 3: Strict vs loose comparison
echo "3️⃣  Strict vs Loose Comparison:\n";
echo str_repeat('-', 50) . "\n";

$stringValue = '10';
$intValue = 10;

echo "Comparing: '{$stringValue}' (string) vs {$intValue} (int)\n\n";

$operators = [
    ComparisonOperator::LooseEqual,
    ComparisonOperator::StrictEqual,
    ComparisonOperator::NotEqual,
    ComparisonOperator::StrictNotEqual,
];

foreach ($operators as $operator) {
    $result = $operator->compare($stringValue, $intValue);
    $resultStr = $result ? '✅ true' : '❌ false';
    $strictInfo = $operator->isStrict() ? ' (strict)' : ' (loose)';
    echo sprintf('  %s%s: %s%s', $operator->name, $strictInfo, $resultStr, PHP_EOL);
}

echo "\n";

// Example 4: Backward compatibility with strings
echo "4️⃣  Backward Compatibility (String):\n";
echo str_repeat('-', 50) . "\n";

class LegacyProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[WhenValue('price', '>', 50)]
        public readonly ?string $expensiveLabel = 'EXPENSIVE',
    ) {}
}

$legacyProduct = new LegacyProductDTO('Keyboard', 75.00);
echo "String-based WhenValue still works!\n";
echo sprintf('Product: %s, Price: $%s%s', $legacyProduct->name, $legacyProduct->price, PHP_EOL);
$legacyData = $legacyProduct->toArray();
echo "Has expensiveLabel: " . (isset($legacyData['expensiveLabel']) ? 'Yes' : 'No') . "\n\n";

// Example 5: Operator helper methods
echo "5️⃣  Operator Helper Methods:\n";
echo str_repeat('-', 50) . "\n";

$operators = [
    ComparisonOperator::StrictEqual,
    ComparisonOperator::NotEqual,
    ComparisonOperator::GreaterThan,
];

foreach ($operators as $operator) {
    echo "{$operator->name} ({$operator->value}):\n";
    echo "  Is strict? " . ($operator->isStrict() ? 'Yes' : 'No') . "\n";
    echo "  Is equality? " . ($operator->isEquality() ? 'Yes' : 'No') . "\n";
    echo "  Is inequality? " . ($operator->isInequality() ? 'Yes' : 'No') . "\n";
    echo "  Is relational? " . ($operator->isRelational() ? 'Yes' : 'No') . "\n\n";
}

// Example 6: Parse from string
echo "6️⃣  Parse from String:\n";
echo str_repeat('-', 50) . "\n";

$operatorStrings = ['>', '===', '!=', '<=', 'invalid'];

foreach ($operatorStrings as $str) {
    $operator = ComparisonOperator::fromString($str);
    if ($operator instanceof ComparisonOperator) {
        echo sprintf("'%s' → %s%s", $str, $operator->name, PHP_EOL);
    } else {
        echo "'{$str}' → Invalid operator\n";
    }
}

echo "\n";

// Example 7: Complex conditional DTO
echo "7️⃣  Complex Conditional DTO:\n";
echo str_repeat('-', 50) . "\n";

class UserDTO extends SimpleDTO
{
    /** @param array<string>|null $adminPanel */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly float $balance,
        public readonly string $role,

        #[WhenValue('age', ComparisonOperator::GreaterThanOrEqual, 18)]
        public readonly ?string $adultContent = 'Adult content available',

        #[WhenValue('balance', ComparisonOperator::GreaterThan, 1000)]
        public readonly ?string $premiumFeatures = 'Premium features enabled',

        #[WhenValue('role', ComparisonOperator::StrictEqual, 'admin')]
        public readonly ?array $adminPanel = ['dashboard', 'users', 'settings'],
    ) {}
}

$users = [
    new UserDTO('Alice', 25, 1500.00, 'admin'),
    new UserDTO('Bob', 16, 50.00, 'user'),
    new UserDTO('Charlie', 30, 500.00, 'user'),
];

foreach ($users as $user) {
    echo "User: {$user->name} (age: {$user->age}, balance: \${$user->balance}, role: {$user->role})\n";
    $data = $user->toArray();
    echo "  Available features:\n";
    if (isset($data['adultContent'])) {
        echo sprintf('    - %s%s', $data['adultContent'], PHP_EOL);
    }
    if (isset($data['premiumFeatures'])) {
        echo sprintf('    - %s%s', $data['premiumFeatures'], PHP_EOL);
    }
    if (isset($data['adminPanel'])) {
        echo "    - Admin panel: " . implode(', ', $data['adminPanel']) . "\n";
    }
    if (!isset($data['adultContent']) && !isset($data['premiumFeatures']) && !isset($data['adminPanel'])) {
        echo "    - Basic features only\n";
    }
    echo "\n";
}

echo "✅ All examples completed successfully!\n";
