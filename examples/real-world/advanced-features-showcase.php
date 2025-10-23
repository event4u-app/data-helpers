<?php

declare(strict_types=1);

/**
 * Advanced Features Showcase
 *
 * This example demonstrates all advanced features of SimpleDTO:
 * - All 18 conditional attributes
 * - with() method
 * - Context-based conditions
 * - Lazy properties
 * - Computed properties
 * - Collections
 * - Nested DTOs
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Skip if Carbon is not available
if (!class_exists('Carbon\Carbon')) {
    echo "⚠️  Skipping: Carbon is not available\n";
    exit(0);
}

use Carbon\Carbon;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenRole;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenValue;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDTO\DataCollection;

function isTrue(mixed $value): bool
{
    /** @phpstan-ignore-next-line unknown */
    return true === $value;
}

// ============================================================================
// Advanced DTO with All Features
// ============================================================================

class AdvancedUserDTO extends SimpleDTO
{
    /**
     * @param array<mixed>|null $profile
     * @param array<mixed>|null $detailedInfo
     * @param array<mixed>|null $adminPanel
     * @param array<mixed>|null $posts
     */
    /**
     * @param array<mixed> $profile
     * @param array<mixed> $detailedInfo
     * @param array<mixed> $adminPanel
     * @param array<mixed> $posts
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $status,
        public readonly bool $isActive,
        public readonly bool $isVerified,
        public readonly ?string $deletedAt,

        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,

        // Hidden property
        #[Hidden]
        public readonly string $password,

        // Core conditional attributes
        // Note: WhenCallback with closures or function calls cannot be used in attributes (PHP limitation)
        // Use string reference to named function instead: #[WhenCallback('isTrue')]
        // #[WhenCallback(isTrue(true))]
        // public readonly ?string $callbackField = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('status', '=', 'active')]
        public readonly ?string $activeData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('deletedAt', '=', null)]
        public readonly ?string $notDeletedData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('deletedAt', '!=', null)]
        public readonly ?string $deletedData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('isActive', '=', true)]
        public readonly ?string $activeUserData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('isActive', '=', false)]
        public readonly ?string $inactiveUserData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenValue('role', '=', 'admin')]
        public readonly ?string $adminData = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenIn('status', ['active', 'pending'])]
        public readonly ?string $statusData = null,

        // Context-based attributes
        #[WhenContext('include_profile')]
        public readonly ?array $profile = null,

        #[WhenContextEquals('view', 'detailed')]
        public readonly ?array $detailedInfo = null,

        // Laravel-specific attributes
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?string $privateEmail = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenRole('admin')]
        public readonly ?array $adminPanel = null,

        // Lazy property
        #[Lazy]
        public readonly ?array $posts = null,
    ) {}

    // Computed properties
    #[Computed]
    public function fullName(): string
    {
        return strtoupper($this->name);
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return 'admin' === $this->role;
    }

    #[Computed]
    public function accountAge(): int
    {
        /** @phpstan-ignore-next-line unknown */
        return (int)$this->createdAt->diffInDays(Carbon::now());
    }
}

// ============================================================================
// Example Usage
// ============================================================================

echo "=== Advanced Features Showcase ===\n\n";

// 1. Create DTO with all features
echo "1. Create Advanced DTO:\n";
echo str_repeat('-', 80) . "\n";

$user = new AdvancedUserDTO(
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    role: 'admin',
    status: 'active',
    isActive: true,
    isVerified: true,
    deletedAt: null,
    createdAt: Carbon::now()->subYear(),
    password: 'hashed_password',
    // callbackField: 'callback data', // Removed due to PHP attribute limitation
    activeData: 'active user data',
    notDeletedData: 'not deleted',
    deletedData: null,
    activeUserData: 'active',
    inactiveUserData: null,
    adminData: 'admin data',
    statusData: 'status data',
    profile: [
        'bio' => 'Software developer',
        'location' => 'New York',
    ],
    detailedInfo: [
        'lastLogin' => '2024-01-15',
        'ipAddress' => '192.168.1.1',
    ],
    privateEmail: 'private@example.com',
    adminPanel: ['dashboard', 'users', 'settings'],
    posts: null,
);

echo sprintf('User: %s%s', $user->name, PHP_EOL);
echo sprintf('Role: %s%s', $user->role, PHP_EOL);
echo sprintf('Status: %s%s', $user->status, PHP_EOL);
echo sprintf('Full Name (Computed): %s%s', $user->fullName(), PHP_EOL);
echo "Is Admin (Computed): " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
echo "Account Age (Computed): {$user->accountAge()} days\n\n";

// 2. Serialize without context
echo "2. Serialize without Context:\n";
echo str_repeat('-', 80) . "\n";
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 3. Serialize with context
echo "3. Serialize with Context (include_profile):\n";
echo str_repeat('-', 80) . "\n";
$userWithProfile = $user->withContext(['include_profile' => true]);
echo json_encode($userWithProfile->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 4. Serialize with multiple contexts
echo "4. Serialize with Multiple Contexts:\n";
echo str_repeat('-', 80) . "\n";
$userWithMultipleContexts = $user->withContext([
    'include_profile' => true,
    'view' => 'detailed',
]);
echo json_encode($userWithMultipleContexts->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 5. with() method - add dynamic properties
echo "5. with() Method - Add Dynamic Properties:\n";
echo str_repeat('-', 80) . "\n";
$userWithExtra = $user
    ->with('extraField', 'extra value')
    ->with('anotherField', 'another value');
echo json_encode($userWithExtra->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 6. Collections
echo "6. Collections:\n";
echo str_repeat('-', 80) . "\n";

$users = [
    new AdvancedUserDTO(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        role: 'admin',
        status: 'active',
        isActive: true,
        isVerified: true,
        deletedAt: null,
        createdAt: Carbon::now()->subYear(),
        password: 'hashed',
    ),
    new AdvancedUserDTO(
        id: 2,
        name: 'Jane Smith',
        email: 'jane@example.com',
        role: 'user',
        status: 'active',
        isActive: true,
        isVerified: false,
        deletedAt: null,
        createdAt: Carbon::now()->subMonths(6),
        password: 'hashed',
    ),
    new AdvancedUserDTO(
        id: 3,
        name: 'Bob Johnson',
        email: 'bob@example.com',
        role: 'user',
        status: 'inactive',
        isActive: false,
        isVerified: true,
        deletedAt: null,
        createdAt: Carbon::now()->subMonths(3),
        password: 'hashed',
    ),
];

/** @var DataCollection<SimpleDTO> $collection */
/** @phpstan-ignore-next-line unknown */
$collection = DataCollection::forDto(AdvancedUserDTO::class, $users);

/** @phpstan-ignore-next-line class.notFound */
echo sprintf('Total users: %s%s', $collection->count(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Active users: " . $collection->filter(fn($u) => $u->isActive)->count() . "\n";
/** @phpstan-ignore-next-line unknown */
echo "Admins: " . $collection->filter(fn($u): bool => 'admin' === $u->role)->count() . "\n\n";

// 7. Collection methods
echo "7. Collection Methods:\n";
echo str_repeat('-', 80) . "\n";

/** @var DataCollection<SimpleDTO> $activeUsers */
/** @phpstan-ignore-next-line unknown */
$activeUsers = $collection->filter(fn($u) => $u->isActive);
echo "Active users:\n";
/** @phpstan-ignore-next-line class.notFound */
foreach ($activeUsers as $u) {
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    echo "  - {$u->name} ({$u->role})\n";
}
echo "\n";

// Note: DataCollection doesn't have sortBy() method
// Use map() to extract names and sort manually if needed
/** @phpstan-ignore-next-line unknown */
$names = $collection->map(fn($u) => $u->name);
echo "User names:\n";
foreach ($names as $name) {
    echo sprintf('  - %s%s', $name, PHP_EOL);
}
echo "\n";

// Note: DataCollection doesn't have pluck() method, use map() instead
/** @phpstan-ignore-next-line unknown */
$names = $collection->map(fn($u) => $u->name);
echo "Names: " . implode(', ', $names) . "\n\n";

// 8. Nested DTOs
echo "8. Nested DTOs:\n";
echo str_repeat('-', 80) . "\n";

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly AdvancedUserDTO $author,
    ) {}
}

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$post = new PostDTO(
    title: 'My First Post',
    /** @phpstan-ignore-next-line unknown */
    id: 1,
    /** @phpstan-ignore-next-line unknown */
    author: $user,
);

echo sprintf('Post: %s%s', $post->title, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Author: %s%s', $post->author->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Author Role: {$post->author->role}\n\n";

echo json_encode($post->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 9. Conditional visibility based on status
echo "9. Conditional Visibility (Different Statuses):\n";
echo str_repeat('-', 80) . "\n";

$activeUser = new AdvancedUserDTO(
    id: 1,
    name: 'Active User',
    email: 'active@example.com',
    role: 'user',
    status: 'active',
    isActive: true,
    isVerified: true,
    deletedAt: null,
    createdAt: Carbon::now(),
    password: 'hashed',
    activeData: 'This is visible',
    statusData: 'Status data visible',
);

$inactiveUser = new AdvancedUserDTO(
    id: 2,
    name: 'Inactive User',
    email: 'inactive@example.com',
    role: 'user',
    status: 'inactive',
    isActive: false,
    isVerified: true,
    deletedAt: null,
    createdAt: Carbon::now(),
    password: 'hashed',
    inactiveUserData: 'This is visible for inactive',
);

echo "Active User:\n";
echo json_encode($activeUser->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "Inactive User:\n";
echo json_encode($inactiveUser->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  Advanced features showcase completed!\n";
