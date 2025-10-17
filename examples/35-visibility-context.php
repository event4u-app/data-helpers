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
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        #[Visible(callback: 'canViewSalary')]
        public readonly float $salary,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        // Admin or the user themselves can see email
        return $context?->role === 'admin' || $context?->userId === $this->id;
    }

    private function canViewSalary(mixed $context): bool
    {
        // Only admin can see salary
        return $context?->role === 'admin';
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
print_r($user->withVisibilityContext($adminContext)->toArray());
echo "\n";

// Owner context
$ownerContext = (object)[
    'userId' => 'user-123',
    'role' => 'user',
];

echo "Owner view (can see own email, but not salary):\n";
print_r($user->withVisibilityContext($ownerContext)->toArray());
echo "\n";

// Other user context
$otherContext = (object)[
    'userId' => 'user-456',
    'role' => 'user',
];

echo "Other user view (cannot see email or salary):\n";
print_r($user->withVisibilityContext($otherContext)->toArray());
echo "\n";

// Example 2: Multi-Level Permissions
echo "2. Multi-Level Permissions:\n";
echo str_repeat('-', 60) . "\n";

class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $author,
        #[Visible(callback: 'canViewContent')]
        public readonly string $content,
        #[Visible(callback: 'canViewMetadata')]
        public readonly array $metadata,
        #[Visible(callback: 'canViewAuditLog')]
        public readonly array $auditLog,
    ) {}

    private function canViewContent(mixed $context): bool
    {
        // Owner, editor, or admin can view content
        return in_array($context?->role, ['owner', 'editor', 'admin'], true);
    }

    private function canViewMetadata(mixed $context): bool
    {
        // Editor or admin can view metadata
        return in_array($context?->role, ['editor', 'admin'], true);
    }

    private function canViewAuditLog(mixed $context): bool
    {
        // Only admin can view audit log
        return $context?->role === 'admin';
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
print_r($document->withVisibilityContext($viewerContext)->toArray());
echo "\n";

echo "Editor (can see content and metadata):\n";
print_r($document->withVisibilityContext($editorContext)->toArray());
echo "\n";

echo "Admin (can see everything):\n";
print_r($document->withVisibilityContext($adminContext)->toArray());
echo "\n";

// Example 3: Chaining with Partial Serialization
echo "3. Chaining Context with only() and except():\n";
echo str_repeat('-', 60) . "\n";

class ProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $displayName,
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        #[Visible(callback: 'canViewPhone')]
        public readonly string $phone,
        public readonly string $bio,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        return $context?->role === 'admin' || $context?->isFriend === true;
    }

    private function canViewPhone(mixed $context): bool
    {
        return $context?->role === 'admin';
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
print_r($profile->withVisibilityContext($friendContext)->toArray());
echo "\n";

echo "Stranger view (cannot see email or phone):\n";
print_r($profile->withVisibilityContext($strangerContext)->toArray());
echo "\n";

echo "Friend view with only() - username and email:\n";
print_r($profile->withVisibilityContext($friendContext)->only(['username', 'email'])->toArray());
echo "\n";

echo "Admin view with except() - exclude bio:\n";
$adminContext = (object)['role' => 'admin'];
print_r($profile->withVisibilityContext($adminContext)->except(['bio'])->toArray());
echo "\n";

// Example 4: JSON API Response
echo "4. JSON API Response with Context:\n";
echo str_repeat('-', 60) . "\n";

class ApiUserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        #[Visible(callback: 'canViewEmail')]
        public readonly string $email,
        #[Visible(callback: 'canViewApiKey')]
        public readonly string $apiKey,
    ) {}

    private function canViewEmail(mixed $context): bool
    {
        return ($context?->scope ?? null) === 'full' || ($context?->role ?? null) === 'admin';
    }

    private function canViewApiKey(mixed $context): bool
    {
        return ($context?->scope ?? null) === 'full' && ($context?->userId ?? null) === $this->id;
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
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly float $total,
        #[Visible(callback: 'canViewPaymentDetails')]
        public readonly array $paymentDetails,
        #[Visible(callback: 'canViewInternalNotes')]
        public readonly string $internalNotes,
    ) {}

    private function canViewPaymentDetails(mixed $context): bool
    {
        // Customer can see their own payment details, or admin/finance can see all
        return ($context?->userId ?? null) === $this->customerId
            || in_array($context?->role ?? null, ['admin', 'finance'], true);
    }

    private function canViewInternalNotes(mixed $context): bool
    {
        // Only internal staff can see notes
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
print_r($order->withVisibilityContext($customerContext)->toArray());
echo "\n";

echo "Support view (can see internal notes but not payment):\n";
print_r($order->withVisibilityContext($supportContext)->toArray());
echo "\n";

echo "Finance view (can see everything):\n";
print_r($order->withVisibilityContext($financeContext)->toArray());
echo "\n";

echo "✅  All context-based visibility examples completed!\n";

