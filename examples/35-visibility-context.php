<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Visible;

echo "=== SimpleDTO Context-Based Visibility Examples ===\n\n";

// Example 1: Role-Based Visibility
echo "1. Role-Based Visibility:\n";
echo str_repeat('-', 60) . "\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewSalary')]
        public readonly float $salary,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        // Admin or the user themselves can see email
        /** @phpstan-ignore-next-line phpstan-error */
        return 'admin' === $context?->role || $context?->userId === $this->id;
    }

    private function canViewSalary(mixed $context): bool
    {
        // Only admin can see salary
        /** @phpstan-ignore-next-line phpstan-error */
        return 'admin' === $context?->role;
    }
}

$user = UserDTO::fromArray([
    'id' => 'user-123',
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'salary' => 75000.0,
]);

// Admin context
$adminContext = (object)[
    'userId' => 'admin-001',
    'role' => 'admin',
];

echo "Admin view:\n";
echo json_encode($user->withVisibilityContext($adminContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Owner context
$ownerContext = (object)[
    'userId' => 'user-123',
    'role' => 'user',
];

echo "Owner view (can see own email, but not salary):\n";
echo json_encode($user->withVisibilityContext($ownerContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Other user context
$otherContext = (object)[
    'userId' => 'user-456',
    'role' => 'user',
];

echo "Other user view (cannot see email or salary):\n";
echo json_encode($user->withVisibilityContext($otherContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 2: Multi-Level Permissions
echo "2. Multi-Level Permissions:\n";
echo str_repeat('-', 60) . "\n";

class DocumentDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $metadata
     * @param array<mixed> $auditLog
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $author,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewContent')]
        public readonly string $content,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewMetadata')]
        public readonly array $metadata,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewAuditLog')]
        public readonly array $auditLog,
    ) {}

    private function canViewContent(mixed $context): bool
    {
        // Owner, editor, or admin can view content
        /** @phpstan-ignore-next-line phpstan-error */
        return in_array($context?->role, ['owner', 'editor', 'admin'], true);
    }

    private function canViewMetadata(mixed $context): bool
    {
        // Editor or admin can view metadata
        /** @phpstan-ignore-next-line phpstan-error */
        return in_array($context?->role, ['editor', 'admin'], true);
    }

    private function canViewAuditLog(mixed $context): bool
    {
        // Only admin can view audit log
        /** @phpstan-ignore-next-line phpstan-error */
        return 'admin' === $context?->role;
    }
}

$document = DocumentDTO::fromArray([
    'id' => 'doc-001',
    'title' => 'Confidential Report',
    'author' => 'Jane Smith',
    'content' => 'This is the document content...',
    'metadata' => ['created' => '2024-01-01', 'version' => 2],
    'auditLog' => ['2024-01-01: Created', '2024-01-02: Edited'],
]);

$viewerContext = (object)['role' => 'viewer'];
$editorContext = (object)['role' => 'editor'];
$adminContext = (object)['role' => 'admin'];

echo "Viewer (basic info only):\n";
echo json_encode($document->withVisibilityContext($viewerContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Editor (can see content and metadata):\n";
echo json_encode($document->withVisibilityContext($editorContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Admin (can see everything):\n";
echo json_encode($document->withVisibilityContext($adminContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 3: Chaining with Partial Serialization
echo "3. Chaining Context with only() and except():\n";
echo str_repeat('-', 60) . "\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $displayName,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewPhone')]
        public readonly string $phone,
        public readonly string $bio,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        /** @phpstan-ignore-next-line phpstan-error */
        return 'admin' === $context?->role || true === $context?->isFriend;
    }

    private function canViewPhone(mixed $context): bool
    {
        /** @phpstan-ignore-next-line phpstan-error */
        return 'admin' === $context?->role;
    }
}

$profile = ProfileDTO::fromArray([
    'username' => 'johndoe',
    'displayName' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1-555-0123',
    'bio' => 'Software developer',
]);

$friendContext = (object)['role' => 'user', 'isFriend' => true];
$strangerContext = (object)['role' => 'user', 'isFriend' => false];

echo "Friend view (can see email):\n";
echo json_encode($profile->withVisibilityContext($friendContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Stranger view (cannot see email or phone):\n";
echo json_encode($profile->withVisibilityContext($strangerContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Friend view with only() - username and email:\n";
echo json_encode($profile->withVisibilityContext($friendContext)->only(['username', 'email'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Admin view with except() - exclude bio:\n";
$adminContext = (object)['role' => 'admin'];
echo json_encode($profile->withVisibilityContext($adminContext)->except(['bio'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

// Example 4: JSON API Response
echo "4. JSON API Response with Context:\n";
echo str_repeat('-', 60) . "\n";

class ApiUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewApiKey')]
        public readonly string $apiKey,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        /** @phpstan-ignore-next-line phpstan-error */
        return 'full' === ($context?->scope ?? null) || 'admin' === ($context?->role ?? null);
    }

    private function canViewApiKey(mixed $context): bool
    {
        /** @phpstan-ignore-next-line phpstan-error */
        return 'full' === ($context?->scope ?? null) && ($context?->userId ?? null) === $this->id;
    }
}

$apiUser = ApiUserDTO::fromArray([
    'id' => 'user-789',
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'apiKey' => 'sk_live_1234567890',
]);

// Public API endpoint (minimal scope)
$publicContext = (object)['scope' => 'public'];
echo "Public API response:\n";
echo json_encode($apiUser->withVisibilityContext($publicContext), JSON_PRETTY_PRINT) . "\n\n";

// Authenticated API endpoint (full scope)
$authenticatedContext = (object)['scope' => 'full', 'userId' => 'user-789', 'role' => 'user'];
echo "Authenticated API response (own profile):\n";
echo json_encode($apiUser->withVisibilityContext($authenticatedContext), JSON_PRETTY_PRINT) . "\n\n";

// Admin API endpoint
$adminApiContext = (object)['scope' => 'full', 'role' => 'admin'];
echo "Admin API response:\n";
echo json_encode($apiUser->withVisibilityContext($adminApiContext), JSON_PRETTY_PRINT) . "\n\n";

// Example 5: Complex Business Logic
echo "5. Complex Business Logic:\n";
echo str_repeat('-', 60) . "\n";

class OrderDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $paymentDetails
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly float $total,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewPaymentDetails')]
        public readonly array $paymentDetails,
        /** @phpstan-ignore-next-line attribute.notFound */
        #[Visible(callback: 'canViewInternalNotes')]
        public readonly string $internalNotes,
    ) {}

    private function canViewPaymentDetails(mixed $context): bool
    {
        // Customer can see their own payment details, or admin/finance can see all
        /** @phpstan-ignore-next-line phpstan-error */
        return ($context?->userId ?? null) === $this->customerId
            /** @phpstan-ignore-next-line phpstan-error */
            || in_array($context?->role ?? null, ['admin', 'finance'], true);
    }

    private function canViewInternalNotes(mixed $context): bool
    {
        // Only internal staff can see notes
        /** @phpstan-ignore-next-line phpstan-error */
        return in_array($context?->role ?? null, ['admin', 'support', 'finance'], true);
    }
}

$order = OrderDTO::fromArray([
    'orderId' => 'ORD-12345',
    'customerId' => 'CUST-001',
    'total' => 299.99,
    'paymentDetails' => ['method' => 'credit_card', 'last4' => '4242'],
    'internalNotes' => 'Customer requested expedited shipping',
]);

$customerContext = (object)['userId' => 'CUST-001', 'role' => 'customer'];
$supportContext = (object)['role' => 'support'];
$financeContext = (object)['role' => 'finance'];

echo "Customer view (can see own payment details):\n";
echo json_encode($order->withVisibilityContext($customerContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Support view (can see internal notes but not payment):\n";
echo json_encode($order->withVisibilityContext($supportContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "Finance view (can see everything):\n";
echo json_encode($order->withVisibilityContext($financeContext)->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
echo "\n";

echo "✅  All context-based visibility examples completed!\n";

