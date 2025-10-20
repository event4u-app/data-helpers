<?php

declare(strict_types=1);

/**
 * Real-World Example: E-Commerce Platform
 *
 * This example demonstrates a complete e-commerce system using SimpleDTO:
 * - Product catalog with categories
 * - Shopping cart with items
 * - Order processing
 * - Payment handling
 * - Conditional visibility based on user roles
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenRole;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

// ============================================================================
// DTOs
// ============================================================================

class CategoryDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
    ) {}
}

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly float $price,
        public readonly ?float $salePrice,
        public readonly string $description,
        public readonly CategoryDTO $category,
        /** @var string[] */
        public readonly array $images,
        /** @var string[] */
        public readonly array $tags,
        public readonly int $stock,
        public readonly bool $inStock,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        // Only visible to authenticated users
        #[WhenAuth]
        public readonly ?bool $inWishlist = null,
        
        // Only visible to admins
        #[WhenRole('admin')]
        public readonly ?float $cost = null,
        
        #[WhenRole('admin')]
        public readonly ?int $totalSold = null,
    ) {}
    
    #[Computed]
    public function discount(): ?float
    {
        if (!$this->salePrice) {
            return null;
        }
        
        return round((($this->price - $this->salePrice) / $this->price) * 100, 2);
    }
    
    #[Computed]
    public function finalPrice(): float
    {
        return $this->salePrice ?? $this->price;
    }
}

class CartItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly ProductDTO $product,
        public readonly int $quantity,
    ) {}
    
    #[Computed]
    public function subtotal(): float
    {
        return $this->product->finalPrice() * $this->quantity;
    }
}

class CartDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,
        /** @var CartItemDTO[] */
        public readonly array $items,
        public readonly ?string $couponCode,
    ) {}
    
    #[Computed]
    public function subtotal(): float
    {
        return array_sum(array_map(
            fn(CartItemDTO $item): float => $item->subtotal(),
            $this->items
        ));
    }
    
    #[Computed]
    public function discount(): float
    {
        if (!$this->couponCode) {
            return 0;
        }
        
        // Example: 10% discount
        return $this->subtotal() * 0.1;
    }
    
    #[Computed]
    public function tax(): float
    {
        return ($this->subtotal() - $this->discount()) * 0.19; // 19% VAT
    }
    
    #[Computed]
    public function total(): float
    {
        return $this->subtotal() - $this->discount() + $this->tax();
    }
    
    #[Computed]
    public function itemCount(): int
    {
        return array_sum(array_map(
            fn(CartItemDTO $item): int => $item->quantity,
            $this->items
        ));
    }
}

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $zipCode,
        public readonly string $country,
    ) {}
}

class CustomerDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        
        #[WhenAuth]
        public readonly ?string $phone = null,
    ) {}
}

class PaymentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $method,
        public readonly string $status,
        public readonly float $amount,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $paidAt,
        
        #[Hidden]
        public readonly string $transactionId,
        
        #[WhenRole('admin')]
        public readonly ?string $gatewayResponse = null,
    ) {}
}

class OrderItemDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $quantity,
        public readonly float $price,
        public readonly float $total,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly CustomerDTO $customer,
        /** @var OrderItemDTO[] */
        public readonly array $items,
        public readonly AddressDTO $shippingAddress,
        public readonly AddressDTO $billingAddress,
        public readonly string $status,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $tax,
        public readonly float $shipping,
        public readonly float $total,
        
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[Cast(DateTimeCast::class)]
        public readonly ?Carbon $shippedAt,
        
        #[WhenAuth]
        public readonly ?PaymentDTO $payment = null,
        
        #[WhenRole('admin')]
        public readonly ?array $internalNotes = null,
    ) {}
}

// ============================================================================
// Example Usage
// ============================================================================

echo "=== E-Commerce Platform Example ===\n\n";

// 1. Create Product Catalog
echo "1. Product Catalog:\n";
echo str_repeat('-', 80) . "\n";

$category = new CategoryDTO(
    id: 1,
    name: 'Electronics',
    slug: 'electronics',
    description: 'Electronic devices and accessories',
);

$product = new ProductDTO(
    id: 101,
    name: 'Wireless Headphones',
    slug: 'wireless-headphones',
    price: 99.99,
    salePrice: 79.99,
    description: 'Premium wireless headphones with noise cancellation',
    category: $category,
    images: [
        'https://example.com/images/headphones-1.jpg',
        'https://example.com/images/headphones-2.jpg',
    ],
    tags: ['wireless', 'audio', 'bluetooth'],
    stock: 50,
    inStock: true,
    createdAt: Carbon::now()->subDays(30),
    inWishlist: true,
    cost: 45.00,
    totalSold: 150,
);

echo sprintf('Product: %s%s', $product->name, PHP_EOL);
echo sprintf('Price: $%s%s', $product->price, PHP_EOL);
echo sprintf('Sale Price: $%s%s', $product->salePrice, PHP_EOL);
echo "Discount: {$product->discount()}%\n";
echo sprintf('Final Price: $%s%s', $product->finalPrice(), PHP_EOL);
echo sprintf('Stock: %d%s', $product->stock, PHP_EOL);
echo "Category: {$product->category->name}\n\n";

// 2. Shopping Cart
echo "2. Shopping Cart:\n";
echo str_repeat('-', 80) . "\n";

$cart = new CartDTO(
    userId: 1,
    items: [
        new CartItemDTO(product: $product, quantity: 2),
    ],
    couponCode: 'SAVE10',
);

echo sprintf('Items in cart: %d%s', $cart->itemCount(), PHP_EOL);
echo sprintf('Subtotal: $%s%s', $cart->subtotal(), PHP_EOL);
echo sprintf('Discount: $%s%s', $cart->discount(), PHP_EOL);
echo sprintf('Tax: $%s%s', $cart->tax(), PHP_EOL);
echo "Total: \${$cart->total()}\n\n";

// 3. Create Order
echo "3. Order Processing:\n";
echo str_repeat('-', 80) . "\n";

$order = new OrderDTO(
    id: 1001,
    orderNumber: 'ORD-2024-001',
    customer: new CustomerDTO(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+1234567890',
    ),
    items: [
        new OrderItemDTO(
            productId: 101,
            productName: 'Wireless Headphones',
            quantity: 2,
            price: 79.99,
            total: 159.98,
        ),
    ],
    shippingAddress: new AddressDTO(
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zipCode: '10001',
        country: 'USA',
    ),
    billingAddress: new AddressDTO(
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zipCode: '10001',
        country: 'USA',
    ),
    status: 'processing',
    subtotal: 159.98,
    discount: 15.99,
    tax: 27.36,
    shipping: 9.99,
    total: 181.34,
    createdAt: Carbon::now(),
    shippedAt: null,
    payment: new PaymentDTO(
        method: 'credit_card',
        status: 'completed',
        amount: 181.34,
        paidAt: Carbon::now(),
        transactionId: 'txn_1234567890',
        gatewayResponse: '{"status": "success"}',
    ),
    internalNotes: ['Customer requested gift wrapping'],
);

echo sprintf('Order: %s%s', $order->orderNumber, PHP_EOL);
echo sprintf('Customer: %s%s', $order->customer->name, PHP_EOL);
echo sprintf('Status: %s%s', $order->status, PHP_EOL);
echo sprintf('Total: $%s%s', $order->total, PHP_EOL);
echo "Created: {$order->createdAt->format('Y-m-d H:i:s')}\n\n";

// 4. Serialize for API (Guest)
echo "4. API Response (Guest):\n";
echo str_repeat('-', 80) . "\n";
echo json_encode($product->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  E-Commerce example completed!\n";

