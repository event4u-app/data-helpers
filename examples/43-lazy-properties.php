<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;

// ============================================================================
// Example 1: Basic Lazy Properties
// ============================================================================

echo "1. BASIC LAZY PROPERTIES:\n";
echo "======================================================================\n\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Lazy]
        public readonly string $biography,
    ) {}
}

$user = UserDTO::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'biography' => 'A very long biography text that we do not want to load by default...',
]);

echo "Default toArray() (biography not included):\n";
print_r($user->toArray());
echo "\n";

echo "With include(['biography']) (biography included):\n";
print_r($user->includeComputed(['biography'])->toArray());
echo "\n";

echo "Direct property access (always works):\n";
echo "Biography: {$user->biography}\n\n";

// ============================================================================
// Example 2: Multiple Lazy Properties
// ============================================================================

echo "2. MULTIPLE LAZY PROPERTIES:\n";
echo "======================================================================\n\n";

class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $author,
        #[Lazy]
        public readonly string $content,
        #[Lazy]
        public readonly string $metadata,
        #[Lazy]
        public readonly array $attachments,
    ) {}
}

$document = DocumentDTO::fromArray([
    'title' => 'Important Document',
    'author' => 'Jane Smith',
    'content' => 'Very long document content...',
    'metadata' => 'Document metadata...',
    'attachments' => ['file1.pdf', 'file2.docx'],
]);

echo "Default (no lazy properties):\n";
print_r($document->toArray());
echo "\n";

echo "Include only content:\n";
print_r($document->includeComputed(['content'])->toArray());
echo "\n";

echo "Include multiple lazy properties:\n";
print_r($document->includeComputed(['content', 'metadata'])->toArray());
echo "\n";

echo "Include all lazy properties:\n";
print_r($document->includeAll()->toArray());
echo "\n";

// ============================================================================
// Example 3: Lazy Properties with JSON
// ============================================================================

echo "3. LAZY PROPERTIES WITH JSON:\n";
echo "======================================================================\n\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        #[Lazy]
        public readonly string $description,
        #[Lazy]
        public readonly array $specifications,
    ) {}
}

$product = ProductDTO::fromArray([
    'name' => 'Laptop',
    'price' => 999.99,
    'description' => 'High-performance laptop with...',
    'specifications' => ['CPU' => 'Intel i7', 'RAM' => '16GB'],
]);

echo "Default JSON (no lazy properties):\n";
echo json_encode($product, JSON_PRETTY_PRINT) . "\n\n";

echo "JSON with description:\n";
echo json_encode($product->includeComputed(['description']), JSON_PRETTY_PRINT) . "\n\n";

echo "JSON with all lazy properties:\n";
echo json_encode($product->includeAll(), JSON_PRETTY_PRINT) . "\n\n";

// ============================================================================
// Example 4: Lazy Properties for Performance
// ============================================================================

echo "4. LAZY PROPERTIES FOR PERFORMANCE:\n";
echo "======================================================================\n\n";

class ImageDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $filename,
        public readonly int $width,
        public readonly int $height,
        #[Lazy]
        public readonly string $base64Data,
        #[Lazy]
        public readonly array $exifData,
    ) {}
}

// Simulate large base64 image data
$largeBase64 = base64_encode(str_repeat('x', 10000));

$image = ImageDTO::fromArray([
    'filename' => 'photo.jpg',
    'width' => 1920,
    'height' => 1080,
    'base64Data' => $largeBase64,
    'exifData' => ['camera' => 'Canon EOS', 'iso' => 400],
]);

echo "Metadata only (fast, no large data):\n";
$metadata = $image->toArray();
echo sprintf('Filename: %s%s', $metadata['filename'], PHP_EOL);
echo sprintf('Dimensions: %sx%s%s', $metadata['width'], $metadata['height'], PHP_EOL);
echo "Base64 data size: " . strlen($largeBase64) . " bytes (not included)\n\n";

echo "With base64 data (slower, includes large data):\n";
$fullData = $image->includeComputed(['base64Data'])->toArray();
echo "Base64 data size: " . strlen((string) $fullData['base64Data']) . " bytes (included)\n\n";

// ============================================================================
// Example 5: Lazy Properties with Chaining
// ============================================================================

echo "5. LAZY PROPERTIES WITH CHAINING:\n";
echo "======================================================================\n\n";

class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $phone,
        #[Lazy]
        public readonly string $address,
        #[Lazy]
        public readonly string $socialSecurityNumber,
    ) {}
}

$profile = UserProfileDTO::fromArray([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    'address' => '123 Main St, City, Country',
    'socialSecurityNumber' => '123-45-6789',
]);

echo "Public profile (only username and email):\n";
print_r($profile->only(['username', 'email'])->toArray());
echo "\n";

echo "Private profile (include address, exclude SSN):\n";
print_r($profile->includeComputed(['address'])->except(['socialSecurityNumber'])->toArray());
echo "\n";

echo "Full profile (include all lazy properties):\n";
print_r($profile->includeAll()->toArray());
echo "\n";

// ============================================================================
// Example 6: Conditional Lazy Loading
// ============================================================================

echo "6. CONDITIONAL LAZY LOADING:\n";
echo "======================================================================\n\n";

class ReportDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $summary,
        #[Lazy(when: 'admin')]
        public readonly string $internalNotes,
        #[Lazy(when: 'admin')]
        public readonly array $auditLog,
    ) {}
}

$report = ReportDTO::fromArray([
    'title' => 'Q4 Report',
    'summary' => 'Summary of Q4 results',
    'internalNotes' => 'Internal notes for admins only',
    'auditLog' => ['2024-01-01: Created', '2024-01-15: Updated'],
]);

echo "Default (no lazy properties):\n";
print_r($report->toArray());
echo "\n";

echo "Explicit include (works even without context):\n";
print_r($report->includeComputed(['internalNotes'])->toArray());
echo "\n";

// ============================================================================
// Example 7: Lazy Properties with Visibility
// ============================================================================

echo "7. LAZY PROPERTIES WITH VISIBILITY:\n";
echo "======================================================================\n\n";

use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;

class SecureUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        #[Lazy]
        #[Hidden]
        public readonly string $password,
    ) {}
}

$secureUser = SecureUserDTO::fromArray([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => 'hashed_password',
]);

echo "Default (password hidden):\n";
print_r($secureUser->toArray());
echo "\n";

echo "With include(['password']) (still hidden due to #[Hidden]):\n";
print_r($secureUser->includeComputed(['password'])->toArray());
echo "\n";

echo "Direct access (still works):\n";
echo "Password: {$secureUser->password}\n\n";

// ============================================================================
// Example 8: Real-World Use Case - API Response
// ============================================================================

echo "8. REAL-WORLD USE CASE - API RESPONSE:\n";
echo "======================================================================\n\n";

class BlogPostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $excerpt,
        public readonly string $author,
        public readonly string $publishedAt,
        #[Lazy]
        public readonly string $content,
        #[Lazy]
        public readonly array $comments,
        #[Lazy]
        public readonly array $relatedPosts,
    ) {}
}

$post = BlogPostDTO::fromArray([
    'id' => 1,
    'title' => 'Introduction to PHP 8.2',
    'excerpt' => 'Learn about the new features in PHP 8.2...',
    'author' => 'John Doe',
    'publishedAt' => '2024-01-15',
    'content' => 'Full blog post content...',
    'comments' => [
        ['author' => 'Jane', 'text' => 'Great post!'],
        ['author' => 'Bob', 'text' => 'Very helpful!'],
    ],
    'relatedPosts' => [2, 3, 4],
]);

echo "List view (fast, no heavy data):\n";
print_r($post->toArray());
echo "\n";

echo "Detail view (include content):\n";
print_r($post->includeComputed(['content'])->toArray());
echo "\n";

echo "Full view (include everything):\n";
print_r($post->includeAll()->toArray());
echo "\n";

echo "======================================================================\n";
echo "All examples completed successfully!\n";

