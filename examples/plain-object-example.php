<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasObject;
use event4u\DataHelpers\SimpleDto\SimpleDtoObjectTrait;
use event4u\DataHelpers\Traits\ObjectMappingTrait;

// ============================================================================
// Example 1: Plain PHP Object with Public Properties
// ============================================================================

echo "Example 1: Plain PHP Object with Public Properties\n";
echo str_repeat('=', 80) . "\n\n";

class Product
{
    public int $id = 0;
    public string $name = '';
    public float $price = 0.0;
    public ?string $description = null;
}

class ProductDto extends SimpleDto
{
    use SimpleDtoObjectTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly ?string $description = null,
    ) {}
}

// Create plain object
$product = new Product();
$product->id = 1;
$product->name = 'Laptop';
$product->price = 999.99;
$product->description = 'High-performance laptop';

// Convert to DTO
$productDto = ProductDto::fromObject($product);

echo "Original Product:\n";
echo sprintf('  ID: %d%s', $product->id, PHP_EOL);
echo sprintf('  Name: %s%s', $product->name, PHP_EOL);
echo sprintf('  Price: %s%s', $product->price, PHP_EOL);
echo "  Description: {$product->description}\n\n";

echo "Product DTO:\n";
echo sprintf('  ID: %s%s', $productDto->id, PHP_EOL);
echo sprintf('  Name: %s%s', $productDto->name, PHP_EOL);
echo sprintf('  Price: %s%s', $productDto->price, PHP_EOL);
echo "  Description: {$productDto->description}\n\n";

// Convert back to object
$newProduct = $productDto->toObject(Product::class);

echo "Converted back to Product:\n";
echo sprintf('  ID: %s%s', $newProduct->id, PHP_EOL);
echo sprintf('  Name: %s%s', $newProduct->name, PHP_EOL);
echo sprintf('  Price: %s%s', $newProduct->price, PHP_EOL);
echo "  Description: {$newProduct->description}\n\n";

// ============================================================================
// Example 2: Object with Getters and Setters
// ============================================================================

echo "\nExample 2: Object with Getters and Setters\n";
echo str_repeat('=', 80) . "\n\n";

class Customer
{
    private int $id = 0;
    private string $name = '';
    private string $email = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}

class CustomerDto extends SimpleDto
{
    use SimpleDtoObjectTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Create customer with setters
$customer = new Customer();
$customer->setId(42);
$customer->setName('John Doe');
$customer->setEmail('john@example.com');

// Convert to DTO (uses getters)
$customerDto = CustomerDto::fromObject($customer);

echo "Original Customer:\n";
echo sprintf('  ID: %d%s', $customer->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $customer->getName(), PHP_EOL);
echo "  Email: {$customer->getEmail()}\n\n";

echo "Customer DTO:\n";
echo sprintf('  ID: %d%s', $customerDto->id, PHP_EOL);
echo sprintf('  Name: %s%s', $customerDto->name, PHP_EOL);
echo "  Email: {$customerDto->email}\n\n";

// Convert back to object (uses setters)
$newCustomer = $customerDto->toObject(Customer::class);

echo "Converted back to Customer:\n";
echo sprintf('  ID: %s%s', $newCustomer->getId(), PHP_EOL);
echo sprintf('  Name: %s%s', $newCustomer->getName(), PHP_EOL);
echo "  Email: {$newCustomer->getEmail()}\n\n";

// ============================================================================
// Example 3: Using HasObject Attribute
// ============================================================================

echo "\nExample 3: Using HasObject Attribute\n";
echo str_repeat('=', 80) . "\n\n";

class Order
{
    public int $id = 0;
    public string $orderNumber = '';
    public float $total = 0.0;
}

#[HasObject(Order::class)]
class OrderDto extends SimpleDto
{
    use SimpleDtoObjectTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly float $total,
    ) {}
}

$orderDto = new OrderDto(1, 'ORD-2024-001', 1499.99);

echo "Order DTO:\n";
echo sprintf('  ID: %d%s', $orderDto->id, PHP_EOL);
echo sprintf('  Order Number: %s%s', $orderDto->orderNumber, PHP_EOL);
echo "  Total: {$orderDto->total}\n\n";

// No need to specify class - uses HasObject attribute
$order = $orderDto->toObject();

echo "Converted to Order (using HasObject attribute):\n";
echo sprintf('  ID: %s%s', $order->id, PHP_EOL);
echo sprintf('  Order Number: %s%s', $order->orderNumber, PHP_EOL);
echo "  Total: {$order->total}\n\n";

// ============================================================================
// Example 4: Using ObjectMappingTrait with HasDto Attribute
// ============================================================================

echo "\nExample 4: Using ObjectMappingTrait with HasDto Attribute\n";
echo str_repeat('=', 80) . "\n\n";

class InvoiceDto extends SimpleDto
{
    use SimpleDtoObjectTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $invoiceNumber,
        public readonly float $amount,
    ) {}
}

#[HasDto(InvoiceDto::class)]
class Invoice
{
    use ObjectMappingTrait;

    public int $id = 0;
    public string $invoiceNumber = '';
    public float $amount = 0.0;
}

$invoice = new Invoice();
$invoice->id = 1;
$invoice->invoiceNumber = 'INV-2024-001';
$invoice->amount = 2499.99;

echo "Original Invoice:\n";
echo sprintf('  ID: %d%s', $invoice->id, PHP_EOL);
echo sprintf('  Invoice Number: %s%s', $invoice->invoiceNumber, PHP_EOL);
echo "  Amount: {$invoice->amount}\n\n";

// Convert to DTO using HasDto attribute
$invoiceDto = $invoice->toDto();

echo "Invoice DTO (using HasDto attribute):\n";
echo sprintf('  ID: %s%s', $invoiceDto->id, PHP_EOL);
echo sprintf('  Invoice Number: %s%s', $invoiceDto->invoiceNumber, PHP_EOL);
echo "  Amount: {$invoiceDto->amount}\n\n";

// ============================================================================
// Example 5: Round-Trip Conversion
// ============================================================================

echo "\nExample 5: Round-Trip Conversion\n";
echo str_repeat('=', 80) . "\n\n";

class Article
{
    public int $id = 0;
    public string $title = '';
    public string $content = '';
}

class ArticleDto extends SimpleDto
{
    use SimpleDtoObjectTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
    ) {}
}

// Original object
$originalArticle = new Article();
$originalArticle->id = 1;
$originalArticle->title = 'Plain Object Integration';
$originalArticle->content = 'This is a great feature!';

echo "Original Article:\n";
echo sprintf('  ID: %d%s', $originalArticle->id, PHP_EOL);
echo sprintf('  Title: %s%s', $originalArticle->title, PHP_EOL);
echo "  Content: {$originalArticle->content}\n\n";

// Object → DTO → Object
$articleDto = ArticleDto::fromObject($originalArticle);
$newArticle = $articleDto->toObject(Article::class);

echo "After Round-Trip:\n";
echo sprintf('  ID: %s%s', $newArticle->id, PHP_EOL);
echo sprintf('  Title: %s%s', $newArticle->title, PHP_EOL);
echo "  Content: {$newArticle->content}\n\n";

echo "Data preserved: " . (
    $newArticle->id === $originalArticle->id &&
    $newArticle->title === $originalArticle->title &&
    $newArticle->content === $originalArticle->content
    ? '✅ YES' : '❌ NO'
) . "\n\n";

echo "\n" . str_repeat('=', 80) . "\n";
echo "All examples completed successfully!\n";
echo str_repeat('=', 80) . "\n";
