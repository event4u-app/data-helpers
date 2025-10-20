<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;

/**
 * Example 83: DTO Mapper Integration
 *
 * This example demonstrates the integration of DataMapper functionality
 * directly into DTOs with the following mapping priority:
 * 1. Template (highest priority)
 * 2. Attributes (#[MapFrom], #[MapTo])
 * 3. Automapping (fallback)
 */

echo "=== Example 83: DTO Mapper Integration ===\n\n";

// ============================================================================
// Example 1: DTO with Template Definition
// ============================================================================

echo "--- Example 1: DTO with Template Definition ---\n\n";

class UserDTO extends SimpleDTO
{
    /**
     * Define template in DTO.
     * Template has HIGHEST priority!
     */
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ user.id }}',
            'name' => '{{ user.full_name | trim | ucfirst }}',
            'email' => '{{ user.email | lower }}',
            'age' => '{{ user.age }}',
        ];
    }

    /** Define pipeline filters in DTO. */
    protected function mapperPipeline(): array
    {
        return [
            new TrimStrings(),
            new LowercaseEmails(),
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}

// API response with nested structure
$apiResponse = [
    'user' => [
        'id' => 1,
        'full_name' => '  john doe  ',  // Will be trimmed and capitalized
        'email' => 'JOHN@EXAMPLE.COM',  // Will be lowercased
        'age' => 30,
    ],
];

// Create DTO from source - uses template automatically
$user = UserDTO::fromSource($apiResponse);

echo "User from API:\n";
echo sprintf('  ID: %s%s', $user->id, PHP_EOL);
echo sprintf('  Name: %s%s', $user->name, PHP_EOL);  // "John doe" (trimmed, ucfirst)
echo sprintf('  Email: %s%s', $user->email, PHP_EOL);  // "john@example.com" (lowercased)
echo "  Age: {$user->age}\n\n";

// ============================================================================
// Example 2: Template Priority over Attributes
// ============================================================================

echo "--- Example 2: Template Priority over Attributes ---\n\n";

class ProductDTO extends SimpleDTO
{
    /** Template has HIGHEST priority! */
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ product.product_id }}',  // Template wins!
            'name' => '{{ product.title }}',     // Template wins!
            'price' => '{{ product.price }}',    // From template
        ];
    }

    public function __construct(
        #[MapFrom('id')]  // This is ignored because template exists!
        public readonly int $id,

        #[MapFrom('product_name')]  // This is ignored because template exists!
        public readonly string $name,

        public readonly float $price,
    ) {}
}

$productData = [
    'product' => [
        'product_id' => 123,
        'title' => 'Laptop',
        'price' => 999.99,
    ],
    // These are ignored because template exists:
    'id' => 999,
    'product_name' => 'Wrong Name',
];

$product = ProductDTO::fromSource($productData);

echo "Product:\n";
echo sprintf('  ID: %d%s', $product->id, PHP_EOL);      // 123 (from template)
echo sprintf('  Name: %s%s', $product->name, PHP_EOL);  // "Laptop" (from template)
echo "  Price: {$product->price}\n\n";  // 999.99 (from template)

// ============================================================================
// Example 3: Dynamic Template Override
// ============================================================================

echo "--- Example 3: Dynamic Template Override ---\n\n";

class OrderDTO extends SimpleDTO
{
    /** Default template. */
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ order.id }}',
            'total' => '{{ order.total }}',
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly ?string $status = null,
    ) {}
}

$orderData = [
    'order' => [
        'id' => 1,
        'total' => 99.99,
    ],
    'order_status' => 'pending',
];

// Use default template
$order1 = OrderDTO::fromSource($orderData);
echo "Order 1 (default template):\n";
echo sprintf('  ID: %d%s', $order1->id, PHP_EOL);
echo sprintf('  Total: %s%s', $order1->total, PHP_EOL);
echo "  Status: " . ($order1->status ?? 'null') . "\n\n";

// Override template dynamically
$customTemplate = [
    'id' => '{{ order.id }}',
    'total' => '{{ order.total }}',
    'status' => '{{ order_status }}',  // Add status!
];

$order2 = OrderDTO::fromSource($orderData, $customTemplate);
echo "Order 2 (custom template):\n";
echo sprintf('  ID: %d%s', $order2->id, PHP_EOL);
echo sprintf('  Total: %s%s', $order2->total, PHP_EOL);
echo "  Status: {$order2->status}\n\n";

// ============================================================================
// Example 4: Attributes as Fallback (No Template)
// ============================================================================

echo "--- Example 4: Attributes as Fallback (No Template) ---\n\n";

class CustomerDTO extends SimpleDTO
{
    // No template() method defined!
    // Attributes will be used instead.

    public function __construct(
        #[MapFrom('customer_id')]
        public readonly int $id,

        #[MapFrom('customer_name')]
        public readonly string $name,

        #[MapFrom('customer_email')]
        public readonly string $email,
    ) {}
}

$customerData = [
    'customer_id' => 1,
    'customer_name' => 'Jane Doe',
    'customer_email' => 'jane@example.com',
];

$customer = CustomerDTO::fromArray($customerData);

echo "Customer (using attributes):\n";
echo sprintf('  ID: %d%s', $customer->id, PHP_EOL);
echo sprintf('  Name: %s%s', $customer->name, PHP_EOL);
echo "  Email: {$customer->email}\n\n";

// ============================================================================
// Example 5: Automapping as Fallback (No Template, No Attributes)
// ============================================================================

echo "--- Example 5: Automapping as Fallback ---\n\n";

class SimpleUserDTO extends SimpleDTO
{
    // No template() method
    // No #[MapFrom] attributes
    // Uses automapping!

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

$simpleData = [
    'id' => 1,
    'name' => 'Bob Smith',
    'email' => 'bob@example.com',
];

$simpleUser = SimpleUserDTO::fromArray($simpleData);

echo "Simple User (automapping):\n";
echo sprintf('  ID: %d%s', $simpleUser->id, PHP_EOL);
echo sprintf('  Name: %s%s', $simpleUser->name, PHP_EOL);
echo "  Email: {$simpleUser->email}\n\n";

// ============================================================================
// Example 6: Complex Template with Filters
// ============================================================================

echo "--- Example 6: Complex Template with Filters ---\n\n";

class BlogPostDTO extends SimpleDTO
{
    protected function mapperTemplate(): array
    {
        return [
            'id' => '{{ post.id }}',
            'title' => '{{ post.title | trim | ucfirst }}',
            'slug' => '{{ post.slug | lower }}',
            'author' => '{{ post.author.name | trim }}',
            'published' => '{{ post.published_at }}',
        ];
    }

    protected function mapperPipeline(): array
    {
        return [
            new TrimStrings(),
        ];
    }

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $author,
        public readonly ?string $published = null,
    ) {}
}

$blogData = [
    'post' => [
        'id' => 1,
        'title' => '  my first post  ',
        'slug' => 'MY-FIRST-POST',
        'author' => [
            'name' => '  John Doe  ',
        ],
        'published_at' => '2024-01-15',
    ],
];

$post = BlogPostDTO::fromSource($blogData);

echo "Blog Post:\n";
echo sprintf('  ID: %s%s', $post->id, PHP_EOL);
echo sprintf('  Title: %s%s', $post->title, PHP_EOL);      // "My first post" (trimmed, ucfirst)
echo sprintf('  Slug: %s%s', $post->slug, PHP_EOL);        // "my-first-post" (lowercased)
echo sprintf('  Author: %s%s', $post->author, PHP_EOL);    // "John Doe" (trimmed)
echo "  Published: {$post->published}\n\n";

// ============================================================================
// Summary
// ============================================================================

echo "=== Summary ===\n\n";
echo "Mapping Priority:\n";
echo "1. Template (highest priority) - defined in template() method\n";
echo "2. Attributes (#[MapFrom], #[MapTo]) - fallback if no template\n";
echo "3. Automapping - fallback if no template and no attributes\n\n";

echo "Features:\n";
echo "✅ Define templates in DTO class (template() method)\n";
echo "✅ Define filters in DTO class (filters() method)\n";
echo "✅ Override templates dynamically (fromSource parameter)\n";
echo "✅ Override filters dynamically (fromSource parameter)\n";
echo "✅ Automatic integration with fromArray()\n";
echo "✅ Template expressions with filters ({{ value | filter }})\n";
echo "✅ Dot notation for nested data ({{ user.profile.name }})\n";
echo "✅ Backward compatible - existing code still works!\n\n";

echo "Done!\n";

