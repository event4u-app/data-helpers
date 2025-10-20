<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;

echo "================================================================================\n";
echo "COMPUTED PROPERTIES - EXAMPLES\n";
echo "================================================================================\n\n";

// ============================================================================
// Example 1: Basic Computed Property
// ============================================================================

echo "1. BASIC COMPUTED PROPERTY:\n";
echo "======================================================================\n\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly float $price,
        public readonly int $quantity,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->price * $this->quantity;
    }
}

$order = OrderDTO::fromArray([
    'price' => 100.0,
    'quantity' => 2,
]);

echo "Order Data:\n";
print_r($order->toArray());
echo "\n";

echo "Direct access to computed property:\n";
echo "total() = " . $order->total() . "\n\n";

// ============================================================================
// Example 2: Multiple Computed Properties
// ============================================================================

echo "2. MULTIPLE COMPUTED PROPERTIES:\n";
echo "======================================================================\n\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly float $price,
        public readonly float $taxRate,
        public readonly int $quantity,
        public readonly float $discount = 0.0,
    ) {}

    #[Computed]
    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }

    #[Computed]
    public function discountAmount(): float
    {
        return $this->subtotal() * $this->discount;
    }

    #[Computed]
    public function subtotalAfterDiscount(): float
    {
        return $this->subtotal() - $this->discountAmount();
    }

    #[Computed]
    public function tax(): float
    {
        return $this->subtotalAfterDiscount() * $this->taxRate;
    }

    #[Computed]
    public function total(): float
    {
        return $this->subtotalAfterDiscount() + $this->tax();
    }
}

$product = ProductDTO::fromArray([
    'price' => 100.0,
    'taxRate' => 0.19,
    'quantity' => 3,
    'discount' => 0.10, // 10% discount
]);

echo "Product with multiple computed properties:\n";
print_r($product->toArray());
echo "\n";

// ============================================================================
// Example 3: Computed Property with Custom Name
// ============================================================================

echo "3. COMPUTED PROPERTY WITH CUSTOM NAME:\n";
echo "======================================================================\n\n";

class InvoiceDTO extends SimpleDTO
{
    public function __construct(
        public readonly float $amount,
        public readonly float $taxRate,
    ) {}

    #[Computed(name: 'taxAmount')]
    public function calculateTax(): float
    {
        return $this->amount * $this->taxRate;
    }

    #[Computed(name: 'totalAmount')]
    public function calculateTotal(): float
    {
        return $this->amount + $this->calculateTax();
    }
}

$invoice = InvoiceDTO::fromArray([
    'amount' => 1000.0,
    'taxRate' => 0.19,
]);

echo "Invoice with custom computed property names:\n";
print_r($invoice->toArray());
echo "\n";

// ============================================================================
// Example 4: Lazy Computed Properties
// ============================================================================

echo "4. LAZY COMPUTED PROPERTIES:\n";
echo "======================================================================\n\n";

class ReportDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $data,
    ) {}

    #[Computed]
    public function summary(): string
    {
        return sprintf('Report: %s with ', $this->name) . count($this->data) . " items";
    }

    #[Computed(lazy: true)]
    public function expensiveAnalysis(): array
    {
        echo "  → Computing expensive analysis...\n";
        // Simulate expensive computation
        usleep(100000); // 0.1 seconds

        return [
            'total' => count($this->data),
            'average' => array_sum($this->data) / count($this->data),
            'max' => max($this->data),
            'min' => min($this->data),
        ];
    }

    #[Computed(lazy: true)]
    public function detailedStats(): array
    {
        echo "  → Computing detailed stats...\n";
        // Simulate expensive computation
        usleep(100000); // 0.1 seconds

        return [
            'median' => $this->calculateMedian($this->data),
            'stdDev' => $this->calculateStdDev($this->data),
        ];
    }

    private function calculateMedian(array $data): float
    {
        sort($data);
        $count = count($data);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($data[$middle - 1] + $data[$middle]) / 2;
        }

        return $data[$middle];
    }

    private function calculateStdDev(array $data): float
    {
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(fn($x): float|int => ($x - $mean) ** 2, $data)) / count($data);

        return sqrt($variance);
    }
}

$report = ReportDTO::fromArray([
    'name' => 'Sales Report',
    'data' => [100, 200, 150, 300, 250, 180, 220],
]);

echo "Report without lazy properties (fast):\n";
print_r($report->toArray());
echo "\n";

echo "Report with expensiveAnalysis included (slow):\n";
print_r($report->includeComputed(['expensiveAnalysis'])->toArray());
echo "\n";

echo "Report with all lazy properties included (very slow):\n";
print_r($report->includeComputed(['expensiveAnalysis', 'detailedStats'])->toArray());
echo "\n";

// ============================================================================
// Example 5: Computed Properties with Caching
// ============================================================================

echo "5. COMPUTED PROPERTIES WITH CACHING:\n";
echo "======================================================================\n\n";

class CachedComputationDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $value,
    ) {}

    #[Computed(cache: true)]
    public function expensiveComputation(): int
    {
        echo "  → Computing (this should only happen once)...\n";
        usleep(50000); // 0.05 seconds

        return $this->value * 2;
    }
}

$cached = CachedComputationDTO::fromArray(['value' => 42]);

echo "First toArray() call (computes and caches):\n";
$array1 = $cached->toArray();
print_r($array1);
echo "\n";

echo "Second toArray() call (uses cache - no computation message):\n";
$array2 = $cached->toArray();
print_r($array2);
echo "\n";

echo "Third toArray() call (still uses cache):\n";
$array3 = $cached->toArray();
print_r($array3);
echo "\n";

echo "Note: Direct method calls bypass cache (by design):\n";
echo "Direct call: " . $cached->expensiveComputation() . "\n\n";

echo "Clear cache and call toArray() again:\n";
$cached->clearComputedCache('expensiveComputation');
$array4 = $cached->toArray();
print_r($array4);
echo "\n";

// ============================================================================
// Example 6: JSON Serialization with Computed Properties
// ============================================================================

echo "6. JSON SERIALIZATION WITH COMPUTED PROPERTIES:\n";
echo "======================================================================\n\n";

class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly int $age,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    #[Computed]
    public function isAdult(): bool
    {
        return 18 <= $this->age;
    }

    #[Computed(lazy: true)]
    public function initials(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }
}

$profile = UserProfileDTO::fromArray([
    'firstName' => 'John',
    'lastName' => 'Doe',
    'age' => 30,
]);

echo "JSON without lazy properties:\n";
echo json_encode($profile, JSON_PRETTY_PRINT) . "\n\n";

echo "JSON with lazy properties:\n";
echo json_encode($profile->includeComputed(['initials']), JSON_PRETTY_PRINT) . "\n\n";

echo "================================================================================\n";
echo "ALL EXAMPLES COMPLETED SUCCESSFULLY!\n";
echo "================================================================================\n";

