<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataFilter;
use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OperatorRegistry;

echo "=== Custom Operators Examples ===\n\n";

// Example 1: Create a custom STARTS_WITH operator
class StartsWithOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'STARTS_WITH';
    }

    protected function getConfigSchema(): array
    {
        return ['prefix'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $prefix = $context->getValue('prefix');

        if (!is_string($actualValue) || !is_string($prefix)) {
            return false;
        }

        return str_starts_with($actualValue, $prefix);
    }
}

// Example 2: Create a custom ENDS_WITH operator
class EndsWithOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'ENDS_WITH';
    }

    protected function getConfigSchema(): array
    {
        return ['suffix'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $suffix = $context->getValue('suffix');

        if (!is_string($actualValue) || !is_string($suffix)) {
            return false;
        }

        return str_ends_with($actualValue, $suffix);
    }
}

// Example 3: Create a custom CONTAINS operator
class ContainsOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'CONTAINS';
    }

    protected function getConfigSchema(): array
    {
        return ['substring'];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        $substring = $context->getValue('substring');

        if (!is_string($actualValue) || !is_string($substring)) {
            return false;
        }

        return str_contains($actualValue, $substring);
    }
}

// Register custom operators
OperatorRegistry::register(new StartsWithOperator());
OperatorRegistry::register(new EndsWithOperator());
OperatorRegistry::register(new ContainsOperator());

// Sample data
$products = [
    ['id' => 1, 'name' => 'Laptop Pro', 'sku' => 'ELEC-001', 'email' => 'sales@example.com'],
    ['id' => 2, 'name' => 'Wireless Mouse', 'sku' => 'ELEC-002', 'email' => 'support@example.com'],
    ['id' => 3, 'name' => 'Office Desk', 'sku' => 'FURN-001', 'email' => 'sales@furniture.com'],
    ['id' => 4, 'name' => 'Gaming Chair', 'sku' => 'FURN-002', 'email' => 'info@furniture.com'],
    ['id' => 5, 'name' => 'Laptop Stand', 'sku' => 'ELEC-003', 'email' => 'sales@example.com'],
];

// Use custom STARTS_WITH operator
echo "1. STARTS_WITH - Products with SKU starting with 'ELEC':\n";
/** @var array<int, array<string, mixed>> $electronics */
$electronics = DataFilter::query($products)
    ->addOperator('STARTS_WITH', ['sku' => 'ELEC'])
    ->get();

foreach ($electronics as $product) {
    echo sprintf("   • %s (SKU: %s)\n", $product['name'], $product['sku']);
}
echo "\n";

// Use custom ENDS_WITH operator
echo "2. ENDS_WITH - Emails ending with '@example.com':\n";
/** @var array<int, array<string, mixed>> $exampleEmails */
$exampleEmails = DataFilter::query($products)
    ->addOperator('ENDS_WITH', ['email' => '@example.com'])
    ->get();

foreach ($exampleEmails as $product) {
    echo sprintf("   • %s - %s\n", $product['name'], $product['email']);
}
echo "\n";

// Use custom CONTAINS operator
echo "3. CONTAINS - Products with 'Laptop' in name:\n";
/** @var array<int, array<string, mixed>> $laptops */
$laptops = DataFilter::query($products)
    ->addOperator('CONTAINS', ['name' => 'Laptop'])
    ->get();

foreach ($laptops as $product) {
    echo sprintf("   • %s\n", $product['name']);
}
echo "\n";

// Combine custom operators with built-in operators
echo "4. Combined - Electronics (STARTS_WITH) with sales email (CONTAINS):\n";
/** @var array<int, array<string, mixed>> $result */
$result = DataFilter::query($products)
    ->addOperator('STARTS_WITH', ['sku' => 'ELEC'])
    ->addOperator('CONTAINS', ['email' => 'sales'])
    ->get();

foreach ($result as $product) {
    echo sprintf("   • %s (SKU: %s, Email: %s)\n",
        $product['name'],
        $product['sku'],
        $product['email']
    );
}
echo "\n";

// Use first() method
echo "5. first() - Get first Electronics product:\n";
/** @var array<int, array<string, mixed>> $firstElec */
$firstElec = DataFilter::query($products)
    ->addOperator('STARTS_WITH', ['sku' => 'ELEC'])
    ->first();

if ($firstElec) {
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    echo sprintf("   • %s (SKU: %s)\n", $firstElec['name'], $firstElec['sku']);
}
echo "\n";

// Use count() method
echo "6. count() - Count Electronics products:\n";
/** @var array<int, array<string, mixed>> $count */
/** @phpstan-ignore-next-line unknown */
$count = DataFilter::query($products)
    ->addOperator('STARTS_WITH', ['sku' => 'ELEC'])
    ->count();

/** @phpstan-ignore-next-line unknown */
echo sprintf("   Total: %d products\n", $count);
echo "\n";

echo "=== Custom Operators Complete ===\n";
echo "\nNote: Custom operators work in both Direct mode (DataFilter) and Wildcard mode (QueryBuilder)!\n";
