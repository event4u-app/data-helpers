<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\SimpleDTO\Attributes\Symfony\WhenRole;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   SYMFONY CONDITIONAL ATTRIBUTES                           ║\n";
echo "║                    Phase 17.5 - Symfony Integration                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "Note: These examples use context-based security.\n";
echo "In a real Symfony app, these attributes can also use Security component automatically.\n\n";

// Example 1: WhenGranted - Permission-based access
echo "1. WHEN GRANTED - PERMISSION-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,

        #[WhenGranted('EDIT')]
        public readonly string $editLink = '/edit',

        #[WhenGranted('DELETE')]
        public readonly string $deleteLink = '/delete',

        #[WhenGranted('PUBLISH')]
        public readonly string $publishButton = 'Publish',
    ) {}
}

$post = new PostDTO('My Post', 'Post content...');

// User with grants array
$editor = (object)['grants' => ['EDIT', 'PUBLISH']];
$admin = (object)['grants' => ['EDIT', 'DELETE', 'PUBLISH']];
$viewer = (object)['grants' => ['VIEW']];

echo "As editor (can edit and publish):\n";
print_r($post->withContext(['user' => $editor])->toArray());

echo "\nAs admin (can do everything):\n";
print_r($post->withContext(['user' => $admin])->toArray());

echo "\nAs viewer (read-only):\n";
print_r($post->withContext(['user' => $viewer])->toArray());

echo "\n✅  Actions based on user grants\n";

echo "\n";

// Example 2: WhenRole - Symfony role-based access
echo "2. WHEN ROLE - SYMFONY ROLE-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class DashboardDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenRole('ROLE_ADMIN')]
        public readonly string $adminPanel = '/admin',

        #[WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR'])]
        public readonly string $moderationPanel = '/moderation',

        #[WhenRole('ROLE_EDITOR')]
        public readonly string $editorPanel = '/editor',
    ) {}
}

$dashboard = new DashboardDTO('Dashboard');

$admin = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
$moderator = (object)['roles' => ['ROLE_MODERATOR', 'ROLE_USER']];
$editor = (object)['roles' => ['ROLE_EDITOR', 'ROLE_USER']];
$user = (object)['roles' => ['ROLE_USER']];

echo "As ROLE_ADMIN:\n";
print_r($dashboard->withContext(['user' => $admin])->toArray());

echo "\nAs ROLE_MODERATOR:\n";
print_r($dashboard->withContext(['user' => $moderator])->toArray());

echo "\nAs ROLE_EDITOR:\n";
print_r($dashboard->withContext(['user' => $editor])->toArray());

echo "\nAs ROLE_USER:\n";
print_r($dashboard->withContext(['user' => $user])->toArray());

echo "\n✅  Different panels based on Symfony roles\n";

echo "\n";

// Example 3: WhenGranted with isGranted method
echo "3. WHEN GRANTED WITH isGranted METHOD:\n";
echo "------------------------------------------------------------\n";

class DocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenGranted('VIEW')]
        public readonly string $content = 'Document content...',

        #[WhenGranted('EDIT')]
        public readonly string $editLink = '/edit',
    ) {}
}

$document = new DocumentDTO('Important Document');

// User with isGranted method (like Symfony User)
$userWithMethod = new class {
    public function isGranted(string $attribute, $subject = null): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT'], true);
    }
};

$userViewOnly = new class {
    public function isGranted(string $attribute, $subject = null): bool
    {
        return $attribute === 'VIEW';
    }
};

echo "User with VIEW and EDIT:\n";
print_r($document->withContext(['user' => $userWithMethod])->toArray());

echo "\nUser with VIEW only:\n";
print_r($document->withContext(['user' => $userViewOnly])->toArray());

echo "\n✅  Works with isGranted method\n";

echo "\n";

// Example 4: Security context object
echo "4. SECURITY CONTEXT OBJECT:\n";
echo "------------------------------------------------------------\n";

class ApiResourceDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,

        #[WhenRole('ROLE_ADMIN')]
        public readonly string $internalId = 'INT-12345',

        #[WhenGranted('EDIT')]
        public readonly string $editEndpoint = '/api/resource/edit',
    ) {}
}

$resource = new ApiResourceDTO('RES-001', 'My Resource');

// Security object (like Symfony AuthorizationCheckerInterface)
$security = new class {
    public function isGranted(string $attribute): bool
    {
        return match ($attribute) {
            'ROLE_ADMIN' => true,
            'EDIT' => true,
            default => false,
        };
    }
};

echo "With security context:\n";
print_r($resource->withContext(['security' => $security])->toArray());

echo "\n✅  Works with security context object\n";

echo "\n";

// Example 5: Combined attributes
echo "5. COMBINED ATTRIBUTES - MULTIPLE CONDITIONS:\n";
echo "------------------------------------------------------------\n";

class SecretDocumentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenRole('ROLE_ADMIN')]
        #[WhenGranted('VIEW_SECRETS')]
        public readonly string $secretContent = 'Top secret information',
    ) {}
}

$document = new SecretDocumentDTO('Classified Document');

$adminWithPermission = (object)[
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    'grants' => ['VIEW_SECRETS', 'EDIT'],
];

$adminWithoutPermission = (object)[
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    'grants' => ['EDIT'],
];

$userWithPermission = (object)[
    'roles' => ['ROLE_USER'],
    'grants' => ['VIEW_SECRETS'],
];

echo "Admin with VIEW_SECRETS:\n";
print_r($document->withContext(['user' => $adminWithPermission])->toArray());

echo "\nAdmin without VIEW_SECRETS:\n";
print_r($document->withContext(['user' => $adminWithoutPermission])->toArray());

echo "\nUser with VIEW_SECRETS:\n";
print_r($document->withContext(['user' => $userWithPermission])->toArray());

echo "\n✅  All conditions must be met (AND logic)\n";

echo "\n";

// Example 6: WhenGranted with subject
echo "6. WHEN GRANTED WITH SUBJECT:\n";
echo "------------------------------------------------------------\n";

class ArticleDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenGranted('EDIT', 'article')]
        public readonly string $editLink = '/edit',
    ) {}
}

$article = new ArticleDTO('My Article');
$articleObject = (object)['id' => 1, 'title' => 'My Article', 'author_id' => 1];

// User with isGranted method that checks subject
$owner = new class {
    public function isGranted(string $attribute, $subject = null): bool
    {
        // In real Symfony, this would check if user owns the article
        return $attribute === 'EDIT' && $subject !== null;
    }
};

echo "Owner with article subject:\n";
print_r($article->withContext(['user' => $owner, 'article' => $articleObject])->toArray());

echo "\n✅  Subject-based authorization\n";

echo "\n";

// Example 7: API Response with Symfony Security
echo "7. API RESPONSE WITH SYMFONY SECURITY:\n";
echo "------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly float $total,

        #[WhenRole('ROLE_USER')]
        public readonly string $customerName = 'John Doe',

        #[WhenRole(['ROLE_ADMIN', 'ROLE_SUPPORT'])]
        public readonly string $internalNotes = 'Customer requested express shipping',

        #[WhenGranted('VIEW_PAYMENT')]
        public readonly array $paymentDetails = ['method' => 'credit_card', 'last4' => '1234'],
    ) {}
}

$order = new OrderDTO('ORD-12345', 'completed', 299.99);

echo "Public API (no user):\n";
print_r($order->toArray());

echo "\nAuthenticated customer (ROLE_USER):\n";
$customer = (object)['roles' => ['ROLE_USER']];
print_r($order->withContext(['user' => $customer])->toArray());

echo "\nSupport staff (ROLE_SUPPORT):\n";
$support = (object)['roles' => ['ROLE_SUPPORT', 'ROLE_USER']];
print_r($order->withContext(['user' => $support])->toArray());

echo "\nAdmin with payment access:\n";
$adminWithPayment = (object)[
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    'grants' => ['VIEW_PAYMENT'],
];
print_r($order->withContext(['user' => $adminWithPayment])->toArray());

echo "\n✅  Different data visibility based on Symfony roles and grants\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  WhenGranted('EDIT') - Include when user is granted attribute\n";
echo "✅  WhenGranted('EDIT', 'subject') - Include with subject check\n";
echo "✅  WhenRole('ROLE_ADMIN') - Include when user has role\n";
echo "✅  WhenRole(['ROLE_ADMIN', 'ROLE_MODERATOR']) - Include when user has any role\n";
echo "✅  Multiple conditions - AND logic (all must be true)\n";
echo "✅  Works with context: \$dto->withContext(['user' => \$user])\n";
echo "✅  Works with security: \$dto->withContext(['security' => \$security])\n";
echo "✅  Works with Symfony: Automatically uses Security component\n";
echo "✅  Supports user->isGranted() method\n";
echo "✅  Supports user->getRoles() method (Symfony UserInterface)\n";
echo "✅  Perfect for API responses, role-based access, permission checks\n";

echo "\n";

