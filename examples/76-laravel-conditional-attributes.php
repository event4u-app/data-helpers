<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenCan;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenGuest;
use event4u\DataHelpers\SimpleDTO\Attributes\Laravel\WhenRole;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   LARAVEL CONDITIONAL ATTRIBUTES                           ║\n";
echo "║                    Phase 17.4 - Laravel Integration                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "Note: These examples use context-based authentication.\n";
echo "In a real Laravel app, these attributes can also use Auth::user() automatically.\n\n";

// Example 1: WhenAuth - Authenticated users only
echo "1. WHEN AUTH - AUTHENTICATED USERS ONLY:\n";
echo "------------------------------------------------------------\n";

class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenAuth]
        public readonly string $email = 'john@example.com',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenAuth]
        public readonly string $phone = '555-1234',
    ) {}
}

$profile = new UserProfileDTO('John Doe', 'johndoe');

echo "As guest:\n";
echo json_encode($profile->withContext(['user' => null])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs authenticated user:\n";
$authenticatedUser = (object)['id' => 1, 'name' => 'John'];
echo json_encode($profile->withContext(['user' => $authenticatedUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Sensitive data only shown to authenticated users\n";

echo "\n";

// Example 2: WhenGuest - Guest users only
echo "2. WHEN GUEST - GUEST USERS ONLY:\n";
echo "------------------------------------------------------------\n";

class PageDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenGuest]
        public readonly string $loginPrompt = 'Please log in to see more',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenGuest]
        public readonly string $registerLink = '/register',
    ) {}
}

$page = new PageDTO('Welcome', 'Welcome to our site!');

echo "As guest:\n";
echo json_encode($page->withContext(['user' => null])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs authenticated user:\n";
echo json_encode($page->withContext(['user' => $authenticatedUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Guest-specific content only shown to non-authenticated users\n";

echo "\n";

// Example 3: WhenRole - Role-based access
echo "3. WHEN ROLE - ROLE-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenRole('admin')]
        public readonly string $adminPanel = '/admin',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenRole(['admin', 'moderator'])]
        public readonly string $moderationPanel = '/moderation',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenRole('editor')]
        public readonly string $editorPanel = '/editor',
    ) {}
}

/** @phpstan-ignore-next-line phpstan-error */
$dashboard = new DashboardDTO('Dashboard');

$admin = (object)['id' => 1, 'role' => 'admin'];
$moderator = (object)['id' => 2, 'role' => 'moderator'];
$editor = (object)['id' => 3, 'role' => 'editor'];
$user = (object)['id' => 4, 'role' => 'user'];

echo "As admin:\n";
echo json_encode($dashboard->withContext(['user' => $admin])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs moderator:\n";
echo json_encode($dashboard->withContext(['user' => $moderator])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs editor:\n";
echo json_encode($dashboard->withContext(['user' => $editor])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs regular user:\n";
echo json_encode($dashboard->withContext(['user' => $user])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different panels based on user role\n";

echo "\n";

// Example 4: WhenCan - Permission-based access
echo "4. WHEN CAN - PERMISSION-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,

        #[WhenCan('edit-post')]
        public readonly string $editLink = '/edit',

        #[WhenCan('delete-post')]
        public readonly string $deleteLink = '/delete',

        #[WhenCan('publish-post')]
        public readonly string $publishButton = 'Publish',
    ) {}
}

/** @phpstan-ignore-next-line phpstan-error */
$post = new PostDTO('My Post', 'Post content...');

// User with can() method (like Laravel User model)
$editorUser = new class {
    public function can(string $ability): bool
    {
        return in_array($ability, ['edit-post', 'publish-post'], true);
    }
};

$adminUser = new class {
    public function can(string $ability): bool
    {
        return true; // Admin can do everything
    }
};

$viewerUser = new class {
    public function can(string $ability): bool
    {
        return false; // Viewer can't do anything
    }
};

echo "As editor (can edit and publish):\n";
echo json_encode($post->withContext(['user' => $editorUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs admin (can do everything):\n";
echo json_encode($post->withContext(['user' => $adminUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs viewer (read-only):\n";
echo json_encode($post->withContext(['user' => $viewerUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Actions based on user permissions\n";

echo "\n";

// Example 5: Combined attributes
echo "5. COMBINED ATTRIBUTES - MULTIPLE CONDITIONS:\n";
echo "------------------------------------------------------------\n";

class SecretDocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenAuth]
        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenRole('admin')]
        #[WhenCan('view-secrets')]
        public readonly string $secretContent = 'Top secret information',
    ) {}
}

$document = new SecretDocumentDTO('Classified Document');

$adminWithPermission = new class {
    public string $role = 'admin';
    public function can(string $ability): bool
    {
        return 'view-secrets' === $ability;
    }
};

$adminWithoutPermission = new class {
    public string $role = 'admin';
    public function can(string $ability): bool
    {
        return false;
    }
};

$regularUser = new class {
    public string $role = 'user';
    public function can(string $ability): bool
    {
        return false;
    }
};

echo "Admin with permission:\n";
echo json_encode($document->withContext(['user' => $adminWithPermission])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin without permission:\n";
echo json_encode($document->withContext(['user' => $adminWithoutPermission])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nRegular user:\n";
echo json_encode($document->withContext(['user' => $regularUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nGuest:\n";
echo json_encode($document->withContext(['user' => null])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  All conditions must be met (AND logic)\n";

echo "\n";

// Example 6: API Response with user context
echo "6. API RESPONSE WITH USER CONTEXT:\n";
echo "------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $paymentDetails
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly float $total,

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenAuth]
        public readonly string $customerName = 'John Doe',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenAuth]
        public readonly string $customerEmail = 'john@example.com',

        /** @phpstan-ignore-next-line attribute.notFound */
        #[WhenRole(['admin', 'support'])]
        public readonly string $internalNotes = 'Customer requested express shipping',

        #[WhenCan('view-payment-details')]
        public readonly array $paymentDetails = ['method' => 'credit_card', 'last4' => '1234'],
    ) {}
}

/** @phpstan-ignore-next-line phpstan-error */
$order = new OrderDTO('ORD-12345', 'completed', 299.99);

echo "Public API (guest):\n";
echo json_encode($order->withContext(['user' => null])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAuthenticated customer:\n";
$customer = (object)['id' => 1, 'role' => 'customer'];
echo json_encode($order->withContext(['user' => $customer])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSupport staff:\n";
$support = new class {
    public string $role = 'support';
    public function can(string $ability): bool
    {
        return false;
    }
};
echo json_encode($order->withContext(['user' => $support])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin with payment access:\n";
$adminWithPayment = new class {
    public string $role = 'admin';
    public function can(string $ability): bool
    {
        return 'view-payment-details' === $ability;
    }
};
echo json_encode($order->withContext(['user' => $adminWithPayment])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different data visibility based on user role and permissions\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  WhenAuth - Include property when user is authenticated\n";
echo "✅  WhenGuest - Include property when user is guest\n";
echo "✅  WhenRole('admin') - Include property when user has role\n";
echo "✅  WhenRole(['admin', 'moderator']) - Include when user has any role\n";
echo "✅  WhenCan('edit-post') - Include when user has permission\n";
echo "✅  Multiple conditions - AND logic (all must be true)\n";
echo "✅  Works with context: \$dto->withContext(['user' => \$user])\n";
echo "✅  Works with Laravel: Automatically uses Auth::user() and Gate::allows()\n";
echo "✅  Perfect for API responses, role-based access, permission checks\n";

echo "\n";

