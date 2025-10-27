<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Symfony\WhenGranted;
use event4u\DataHelpers\SimpleDto\Attributes\Symfony\WhenRole;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   SYMFONY CONDITIONAL ATTRIBUTES                           ║\n";
echo "║                    Phase 17.5 - Symfony Integration                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "Note: These examples use context-based security.\n";
echo "In a real Symfony app, these attributes can also use Security component automatically.\n\n";

// Example 1: WhenGranted - Permission-based access
echo "1. WHEN GRANTED - PERMISSION-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class PostDto extends SimpleDto
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

/** @phpstan-ignore-next-line unknown */
$post = new PostDto('My Post', 'Post content...');

// User with grants array
$editor = (object)['grants' => ['EDIT', 'PUBLISH']];
$admin = (object)['grants' => ['EDIT', 'DELETE', 'PUBLISH']];
$viewer = (object)['grants' => ['VIEW']];

echo "As editor (can edit and publish):\n";
echo json_encode($post->withContext(['user' => $editor])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs admin (can do everything):\n";
echo json_encode($post->withContext(['user' => $admin])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs viewer (read-only):\n";
echo json_encode($post->withContext(['user' => $viewer])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Actions based on user grants\n";

echo "\n";

// Example 2: WhenRole - Symfony role-based access
echo "2. WHEN ROLE - SYMFONY ROLE-BASED ACCESS:\n";
echo "------------------------------------------------------------\n";

class DashboardDto extends SimpleDto
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

/** @phpstan-ignore-next-line unknown */
$dashboard = new DashboardDto('Dashboard');

$admin = (object)['roles' => ['ROLE_ADMIN', 'ROLE_USER']];
$moderator = (object)['roles' => ['ROLE_MODERATOR', 'ROLE_USER']];
$editor = (object)['roles' => ['ROLE_EDITOR', 'ROLE_USER']];
$user = (object)['roles' => ['ROLE_USER']];

echo "As ROLE_ADMIN:\n";
echo json_encode($dashboard->withContext(['user' => $admin])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs ROLE_MODERATOR:\n";
echo json_encode($dashboard->withContext(['user' => $moderator])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs ROLE_EDITOR:\n";
echo json_encode($dashboard->withContext(['user' => $editor])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs ROLE_USER:\n";
echo json_encode($dashboard->withContext(['user' => $user])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different panels based on Symfony roles\n";

echo "\n";

// Example 3: WhenGranted with isGranted method
echo "3. WHEN GRANTED WITH isGranted METHOD:\n";
echo "------------------------------------------------------------\n";

class DocumentDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[WhenGranted('VIEW')]
        public readonly string $content = 'Document content...',

        #[WhenGranted('EDIT')]
        public readonly string $editLink = '/edit',
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$document = new DocumentDto('Important Document');

// User with isGranted method (like Symfony User)
$userWithMethod = new class {
    /** @phpstan-ignore-next-line unknown */
    public function isGranted(string $attribute, $subject = null): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT'], true);
    }
};

$userViewOnly = new class {
    /** @phpstan-ignore-next-line unknown */
    public function isGranted(string $attribute, $subject = null): bool
    {
        return 'VIEW' === $attribute;
    }
};

echo "User with VIEW and EDIT:\n";
echo json_encode($document->withContext(['user' => $userWithMethod])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nUser with VIEW only:\n";
echo json_encode($document->withContext(['user' => $userViewOnly])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Works with isGranted method\n";

echo "\n";

// Example 4: Security context object
echo "4. SECURITY CONTEXT OBJECT:\n";
echo "------------------------------------------------------------\n";

class ApiResourceDto extends SimpleDto
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

$resource = new ApiResourceDto('RES-001', 'My Resource');

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
echo json_encode($resource->withContext(['security' => $security])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Works with security context object\n";

echo "\n";

// Example 5: Combined attributes
echo "5. COMBINED ATTRIBUTES - MULTIPLE CONDITIONS:\n";
echo "------------------------------------------------------------\n";

class SecretDocumentDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[WhenRole('ROLE_ADMIN')]
        #[WhenGranted('VIEW_SECRETS')]
        public readonly string $secretContent = 'Top secret information',
    ) {}
}

$document = new SecretDocumentDto('Classified Document');

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
echo json_encode($document->withContext(['user' => $adminWithPermission])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin without VIEW_SECRETS:\n";
echo json_encode($document->withContext(['user' => $adminWithoutPermission])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nUser with VIEW_SECRETS:\n";
echo json_encode($document->withContext(['user' => $userWithPermission])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  All conditions must be met (AND logic)\n";

echo "\n";

// Example 6: WhenGranted with subject
echo "6. WHEN GRANTED WITH SUBJECT:\n";
echo "------------------------------------------------------------\n";

class ArticleDto extends SimpleDto
{
    public function __construct(
        public readonly string $title,

        #[WhenGranted('EDIT', 'article')]
        public readonly string $editLink = '/edit',
    ) {}
}

$article = new ArticleDto('My Article');
$articleObject = (object)['id' => 1, 'title' => 'My Article', 'author_id' => 1];

// User with isGranted method that checks subject
$owner = new class {
    /** @phpstan-ignore-next-line unknown */
    public function isGranted(string $attribute, $subject = null): bool
    {
        // In real Symfony, this would check if user owns the article
        return 'EDIT' === $attribute && null !== $subject;
    }
};

echo "Owner with article subject:\n";
echo json_encode(
    $article->withContext(['user' => $owner, 'article' => $articleObject])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\n✅  Subject-based authorization\n";

echo "\n";

// Example 7: API Response with Symfony Security
echo "7. API RESPONSE WITH SYMFONY SECURITY:\n";
echo "------------------------------------------------------------\n";

class OrderDto extends SimpleDto
{
    /** @param array<mixed> $paymentDetails */
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

/** @phpstan-ignore-next-line unknown */
$order = new OrderDto('ORD-12345', 'completed', 299.99);

echo "Public API (no user):\n";
echo json_encode($order->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAuthenticated customer (ROLE_USER):\n";
$customer = (object)['roles' => ['ROLE_USER']];
echo json_encode($order->withContext(['user' => $customer])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nSupport staff (ROLE_SUPPORT):\n";
$support = (object)['roles' => ['ROLE_SUPPORT', 'ROLE_USER']];
echo json_encode($order->withContext(['user' => $support])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin with payment access:\n";
$adminWithPayment = (object)[
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    'grants' => ['VIEW_PAYMENT'],
];
echo json_encode($order->withContext(['user' => $adminWithPayment])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

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
