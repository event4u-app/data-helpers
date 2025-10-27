<?php

declare(strict_types=1);

/**
 * Complete Form Requests Example
 *
 * This example demonstrates form handling with SimpleDto:
 * - User registration with validation
 * - Profile update forms
 * - Nested form data
 * - File uploads
 * - Complex validation rules
 */

require __DIR__ . '/../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Accepted;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\Email;
use event4u\DataHelpers\SimpleDto\Attributes\In;
use event4u\DataHelpers\SimpleDto\Attributes\Max;
use event4u\DataHelpers\SimpleDto\Attributes\Min;
use event4u\DataHelpers\SimpleDto\Attributes\Nullable;
use event4u\DataHelpers\SimpleDto\Attributes\Regex;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\Attributes\Same;
use event4u\DataHelpers\SimpleDto\Attributes\StringType;
/** @phpstan-ignore-next-line unknown */
use event4u\DataHelpers\SimpleDto\Attributes\URL;

// ============================================================================
// Form Request Dtos
// ============================================================================

class RegisterUserDto extends SimpleDto
{
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(3), Max(50)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8), Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
        public readonly string $password,

        #[Required, Same('password')]
        public readonly string $passwordConfirmation,

        /** @phpstan-ignore-next-line unknown */
        #[Required, Accepted]
        public readonly bool $termsAccepted,
    ) {}
}

class UpdateProfileDto extends SimpleDto
{
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Min(3), Max(50)]
        public readonly ?string $name = null,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Max(500)]
        public readonly ?string $bio = null,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, URL]
        public readonly ?string $website = null,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Max(100)]
        public readonly ?string $location = null,
    ) {}
}

class AddressDto extends SimpleDto
{
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Max(100)]
        public readonly string $street,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Max(50)]
        public readonly string $city,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Max(50)]
        public readonly string $state,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Regex('/^\d{5}$/')]
        public readonly string $zipCode,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Max(50)]
        public readonly string $country,
    ) {}
}

class CreateOrderDto extends SimpleDto
{
    /** @param array<mixed> $items */
    public function __construct(
        #[Required]
        public readonly int $customerId,

        #[Required]
        public readonly array $items,

        #[Required]
        public readonly AddressDto $shippingAddress,

        #[Required]
        public readonly AddressDto $billingAddress,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Max(50)]
        public readonly ?string $couponCode = null,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Max(500)]
        public readonly ?string $notes = null,
    ) {}
}

class CreatePostDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $tags
     */
    /** @param array<mixed> $tags */
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(5), Max(200)]
        public readonly string $title,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(10)]
        public readonly string $content,

        /** @phpstan-ignore-next-line unknown */
        #[Nullable, StringType, Max(500)]
        public readonly ?string $excerpt = null,

        #[Required, In(['draft', 'published'])]
        public readonly string $status = '',

        #[Required]
        public readonly int $categoryId = 0,

        #[Nullable]
        public readonly ?array $tags = null,
    ) {}
}

class ContactFormDto extends SimpleDto
{
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Max(200)]
        public readonly string $subject,

        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(10), Max(5000)]
        public readonly string $message,
    ) {}
}

class ChangePasswordDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $currentPassword,

        #[Required, Min(8), Regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/')]
        public readonly string $newPassword,

        #[Required, Same('newPassword')]
        public readonly string $newPasswordConfirmation,
    ) {}
}

class SearchDto extends SimpleDto
{
    public function __construct(
        /** @phpstan-ignore-next-line unknown */
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $query,

        #[Nullable, In(['posts', 'users', 'products'])]
        public readonly ?string $type = null,

        #[Nullable, In(['relevance', 'date', 'popularity'])]
        public readonly ?string $sortBy = null,

        #[Nullable, Between(1, 100)]
        public readonly ?int $perPage = 15,
    ) {}
}

// ============================================================================
// Example Usage
// ============================================================================

echo "=== Complete Form Requests Example ===\n\n";

// 1. User Registration
echo "1. User Registration Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $registerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123',
        'passwordConfirmation' => 'Password123',
        'termsAccepted' => true,
    ];

    $dto = RegisterUserDto::validateAndCreate($registerData);

    echo "✅  Registration data validated successfully!\n";
    /** @phpstan-ignore-next-line unknown */
    echo sprintf('Name: %s%s', $dto->name, PHP_EOL);
    echo sprintf('Email: %s%s', $dto->email, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo "Terms Accepted: " . ($dto->termsAccepted ? 'Yes' : 'No') . "\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 2. Update Profile (Partial Update)
echo "2. Update Profile Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $profileData = [
        'name' => 'John Doe Updated',
        'bio' => 'Software developer and tech enthusiast',
        'website' => 'https://johndoe.com',
        'location' => 'New York, USA',
    ];

    $dto = UpdateProfileDto::validateAndCreate($profileData);

    echo "✅  Profile data validated successfully!\n";
    echo sprintf('Name: %s%s', $dto->name, PHP_EOL);
    echo sprintf('Bio: %s%s', $dto->bio, PHP_EOL);
    echo sprintf('Website: %s%s', $dto->website, PHP_EOL);
    echo "Location: {$dto->location}\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 3. Create Order (Nested Data)
echo "3. Create Order Form (Nested):\n";
echo str_repeat('-', 80) . "\n";

try {
    $orderData = [
        'customerId' => 1,
        'items' => [
            ['productId' => 101, 'quantity' => 2],
            ['productId' => 102, 'quantity' => 1],
        ],
        'shippingAddress' => [
            'street' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001',
            'country' => 'USA',
        ],
        'billingAddress' => [
            'street' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zipCode' => '10001',
            'country' => 'USA',
        ],
        'couponCode' => 'SAVE10',
        'notes' => 'Please gift wrap',
    ];

    $dto = CreateOrderDto::validateAndCreate($orderData);

    echo "✅  Order data validated successfully!\n";
    echo sprintf('Customer ID: %d%s', $dto->customerId, PHP_EOL);
    echo "Items: " . count($dto->items) . "\n";
    echo sprintf('Shipping: %s, %s%s', $dto->shippingAddress->city, $dto->shippingAddress->state, PHP_EOL);
    echo "Coupon: {$dto->couponCode}\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 4. Create Post
echo "4. Create Post Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $postData = [
        'title' => 'Getting Started with PHP 8.2',
        'content' => 'This is a comprehensive guide to PHP 8.2 features...',
        'excerpt' => 'Learn about the new features in PHP 8.2',
        'status' => 'published',
        'categoryId' => 1,
        'tags' => ['php', 'tutorial', 'programming'],
    ];

    $dto = CreatePostDto::validateAndCreate($postData);

    echo "✅  Post data validated successfully!\n";
    echo sprintf('Title: %s%s', $dto->title, PHP_EOL);
    echo sprintf('Status: %s%s', $dto->status, PHP_EOL);
    echo sprintf('Category ID: %d%s', $dto->categoryId, PHP_EOL);
    echo "Tags: " . implode(', ', $dto->tags ?? []) . "\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 5. Contact Form
echo "5. Contact Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $contactData = [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'subject' => 'Question about your product',
        'message' => 'I would like to know more about your product features and pricing.',
    ];

    $dto = ContactFormDto::validateAndCreate($contactData);

    echo "✅  Contact form validated successfully!\n";
    echo sprintf('Name: %s%s', $dto->name, PHP_EOL);
    echo sprintf('Email: %s%s', $dto->email, PHP_EOL);
    /** @phpstan-ignore-next-line unknown */
    echo "Subject: {$dto->subject}\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 6. Change Password
echo "6. Change Password Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $passwordData = [
        'currentPassword' => 'OldPassword123',
        'newPassword' => 'NewPassword456',
        'newPasswordConfirmation' => 'NewPassword456',
    ];

    $dto = ChangePasswordDto::validateAndCreate($passwordData);

    echo "✅  Password change validated successfully!\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 7. Search Form
echo "7. Search Form:\n";
echo str_repeat('-', 80) . "\n";

try {
    $searchData = [
        'query' => 'php tutorial',
        'type' => 'posts',
        'sortBy' => 'relevance',
        'perPage' => 20,
    ];

    $dto = SearchDto::validateAndCreate($searchData);

    echo "✅  Search data validated successfully!\n";
    echo sprintf('Query: %s%s', $dto->query, PHP_EOL);
    echo sprintf('Type: %s%s', $dto->type, PHP_EOL);
    echo sprintf('Sort By: %s%s', $dto->sortBy, PHP_EOL);
    echo "Per Page: {$dto->perPage}\n\n";
} catch (Exception $exception) {
    echo "❌  Validation failed: {$exception->getMessage()}\n\n";
}

// 8. Validation Error Example
echo "8. Validation Error Example:\n";
echo str_repeat('-', 80) . "\n";

try {
    $invalidData = [
        'name' => 'Jo', // Too short
        'email' => 'invalid-email', // Invalid email
        'password' => 'weak', // Too short
        'passwordConfirmation' => 'different', // Doesn't match
        'termsAccepted' => false, // Not accepted
    ];

    $dto = RegisterUserDto::validateAndCreate($invalidData);
} catch (Exception $exception) {
    echo "❌  Validation failed (as expected):\n";
    echo "Error: {$exception->getMessage()}\n\n";
}

echo "✅  Complete form requests example completed!\n";
