<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;

// ============================================================================
// Example 1: Backed String Enum
// ============================================================================

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly OrderStatus $status,
        public readonly float $total,
    ) {}

    protected function casts(): array
    {
        return [
            'status' => 'enum:OrderStatus',
        ];
    }
}

echo "Example 1: Backed String Enum\n";
echo "==============================\n\n";

$order = OrderDTO::fromArray([
    'orderId' => 'ORD-12345',
    'status' => 'processing',  // String wird zu Enum
    'total' => 99.99,
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('Order ID: %s%s', $order->orderId, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "Status: {$order->status->value} ({$order->status->name})\n";
echo "Total: \${$order->total}\n\n";

// toArray() konvertiert Enum zurück zu String
$array = $order->toArray();
echo "toArray(): ".json_encode($array, JSON_PRETTY_PRINT)."\n\n";

// JSON Serialization
echo "JSON: ".json_encode($order, JSON_PRETTY_PRINT)."\n\n";

// ============================================================================
// Example 2: Backed Integer Enum
// ============================================================================

enum Priority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case URGENT = 4;
}

class TaskDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly Priority $priority,
        public readonly bool $completed = false,
    ) {}

    protected function casts(): array
    {
        return [
            'priority' => 'enum:Priority',
            'completed' => 'boolean',
        ];
    }
}

echo "Example 2: Backed Integer Enum\n";
echo "===============================\n\n";

$task = TaskDTO::fromArray([
    'title' => 'Fix critical bug',
    'priority' => 4,  // Integer wird zu Enum
    'completed' => 0,
]);

echo sprintf('Task: %s%s', $task->title, PHP_EOL);
echo "Priority: {$task->priority->name} (value: {$task->priority->value})\n";
echo "Completed: ".($task->completed ? 'Yes' : 'No')."\n\n";

// toArray() konvertiert Enum zurück zu Integer
$array = $task->toArray();
echo "toArray(): ".json_encode($array, JSON_PRETTY_PRINT)."\n\n";

// ============================================================================
// Example 3: Unit Enum (no backing value)
// ============================================================================

enum Color
{
    case RED;
    case GREEN;
    case BLUE;
    case YELLOW;
}

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly Color $color,
        public readonly float $price,
    ) {}

    protected function casts(): array
    {
        return [
            'color' => 'enum:Color',
        ];
    }
}

echo "Example 3: Unit Enum (no backing value)\n";
echo "========================================\n\n";

$product = ProductDTO::fromArray([
    'name' => 'T-Shirt',
    'color' => 'BLUE',  // String (case name) wird zu Enum
    'price' => 29.99,
]);

echo sprintf('Product: %s%s', $product->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Color: %s%s', $product->color->name, PHP_EOL);
echo "Price: \${$product->price}\n\n";

// toArray() konvertiert Unit Enum zurück zu Name (String)
$array = $product->toArray();
echo "toArray(): ".json_encode($array, JSON_PRETTY_PRINT)."\n\n";

// ============================================================================
// Example 4: Null Handling
// ============================================================================

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly ?OrderStatus $lastOrderStatus = null,
    ) {}

    protected function casts(): array
    {
        return [
            'lastOrderStatus' => 'enum:OrderStatus',
        ];
    }
}

echo "Example 4: Null Handling\n";
echo "========================\n\n";

$newUser = UserDTO::fromArray([
    'username' => 'john_doe',
    'lastOrderStatus' => null,
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $newUser->username, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Last Order Status: ".($newUser->lastOrderStatus?->value ?? 'None')."\n\n";

$existingUser = UserDTO::fromArray([
    'username' => 'jane_doe',
    'lastOrderStatus' => 'delivered',
]);

/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: %s%s', $existingUser->username, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Last Order Status: {$existingUser->lastOrderStatus->value}\n\n";

// ============================================================================
// Example 5: Invalid Value Handling
// ============================================================================

echo "Example 5: Invalid Value Handling\n";
echo "==================================\n\n";

class ConfigDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?Priority $priority = null,
    ) {}

    protected function casts(): array
    {
        return [
            'priority' => 'enum:Priority',
        ];
    }
}

// Invalid enum value wird zu null
$config = ConfigDTO::fromArray([
    'name' => 'Test Config',
    'priority' => 999,  // Invalid value
]);

echo sprintf('Config: %s%s', $config->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Priority: ".($config->priority?->name ?? 'Invalid (null)')."\n\n";

// ============================================================================
// Example 6: Multiple Enums in One DTO
// ============================================================================

enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
}

class InvoiceDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly OrderStatus $status,
        public readonly PaymentMethod $paymentMethod,
        public readonly Priority $priority,
        public readonly float $amount,
    ) {}

    protected function casts(): array
    {
        return [
            'status' => 'enum:OrderStatus',
            'paymentMethod' => 'enum:PaymentMethod',
            'priority' => 'enum:Priority',
        ];
    }
}

echo "Example 6: Multiple Enums in One DTO\n";
echo "=====================================\n\n";

$invoice = InvoiceDTO::fromArray([
    'invoiceNumber' => 'INV-2024-001',
    'status' => 'pending',
    'paymentMethod' => 'credit_card',
    'priority' => 2,
    'amount' => 1299.99,
]);

echo sprintf('Invoice: %s%s', $invoice->invoiceNumber, PHP_EOL);
echo sprintf('Status: %s%s', $invoice->status->value, PHP_EOL);
echo sprintf('Payment Method: %s%s', $invoice->paymentMethod->value, PHP_EOL);
echo sprintf('Priority: %s%s', $invoice->priority->name, PHP_EOL);
echo "Amount: \${$invoice->amount}\n\n";

$array = $invoice->toArray();
echo "toArray(): ".json_encode($array, JSON_PRETTY_PRINT)."\n\n";

echo "✅  All examples completed successfully!\n";
