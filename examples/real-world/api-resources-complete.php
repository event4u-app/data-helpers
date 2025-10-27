<?php

declare(strict_types=1);

/**
 * Complete API Resources Example
 *
 * This example demonstrates building a complete REST API using SimpleDto:
 * - User resources with conditional fields
 * - Collection resources
 * - Paginated responses
 * - Nested resources
 * - Context-based responses
 */

require __DIR__ . '/../bootstrap.php';

use Carbon\Carbon;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Cast;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDto\Attributes\WhenContext;
use event4u\DataHelpers\SimpleDto\Attributes\WhenRole;
use event4u\DataHelpers\SimpleDto\Casts\DateTimeCast;
use event4u\DataHelpers\SimpleDto\DataCollection;

// Skip if Carbon is not available
if (!class_exists('Carbon\Carbon')) {
    echo "⚠️  Skipping: Carbon is not available\n";
    exit(0);
}

// ============================================================================
// Resource Dtos
// ============================================================================

class UserResourceDto extends SimpleDto
{
    /**
     * @param array<string, mixed>|null $stats
     * @param array<string, mixed>|null $profile
     */
    /**
     * @param array<mixed>|null $stats
     * @param array<mixed>|null $profile
     */
    /**
     * @param array<mixed> $stats
     * @param array<mixed> $profile
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly ?string $avatar,

        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,

        // Only for authenticated users
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?string $email = null,

        // Only for admins
        /** @phpstan-ignore-next-line unknown */
        #[WhenRole('admin')]
        public readonly ?string $ipAddress = null,

        /** @phpstan-ignore-next-line unknown */
        #[WhenRole('admin')]
        public readonly ?Carbon $lastLoginAt = null,

        // Context-based fields
        #[WhenContext('include_stats')]
        public readonly ?array $stats = null,

        #[WhenContext('include_profile')]
        public readonly ?array $profile = null,
    ) {}

    #[Computed]
    public function url(): string
    {
        return 'https://api.example.com/users/' . $this->id;
    }
}

class PostResourceDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $comments
     */
    /** @param array<mixed> $comments */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $excerpt,
        public readonly UserResourceDto $author,

        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $publishedAt,

        #[WhenContext('include_content')]
        public readonly ?string $content = null,

        #[WhenContext('include_comments')]
        public readonly ?array $comments = null,
    ) {}

    #[Computed]
    public function url(): string
    {
        return 'https://api.example.com/posts/' . $this->slug;
    }
}

class PaginationMetaDto extends SimpleDto
{
    public function __construct(
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $from,
        public readonly int $to,
    ) {}
}

class PaginationLinksDto extends SimpleDto
{
    public function __construct(
        public readonly string $first,
        public readonly string $last,
        public readonly ?string $prev,
        public readonly ?string $next,
    ) {}
}

class ApiResponseDto extends SimpleDto
{
    public function __construct(
        public readonly mixed $data,
        public readonly ?PaginationMetaDto $meta = null,
        public readonly ?PaginationLinksDto $links = null,
    ) {}
}

class ErrorResponseDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $errors
     */
    /** @param array<mixed> $errors */
    public function __construct(
        public readonly string $message,
        public readonly int $code,
        public readonly ?array $errors = null,
    ) {}
}

// ============================================================================
// Example Usage
// ============================================================================

echo "=== Complete API Resources Example ===\n\n";

// 1. Single Resource
echo "1. Single User Resource (Guest):\n";
echo str_repeat('-', 80) . "\n";

$user = new UserResourceDto(
    id: 1,
    name: 'John Doe',
    username: 'johndoe',
    avatar: 'https://example.com/avatars/john.jpg',
    createdAt: Carbon::now()->subYear(),
    email: 'john@example.com',
    ipAddress: '192.168.1.1',
    lastLoginAt: Carbon::now()->subHour(),
    stats: [
        'posts' => 42,
        'followers' => 150,
        'following' => 75,
    ],
    profile: [
        'bio' => 'Software developer',
        'location' => 'New York',
        'website' => 'https://johndoe.com',
    ],
);

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $user);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 2. Single Resource with Context
echo "2. Single User Resource (with stats):\n";
echo str_repeat('-', 80) . "\n";

$userWithContext = $user->withContext(['include_stats' => true]);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $userWithContext);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 3. Collection Resource
echo "3. User Collection:\n";
echo str_repeat('-', 80) . "\n";

$users = [
    new UserResourceDto(
        id: 1,
        name: 'John Doe',
        username: 'johndoe',
        avatar: 'https://example.com/avatars/john.jpg',
        createdAt: Carbon::now()->subYear(),
    ),
    new UserResourceDto(
        id: 2,
        name: 'Jane Smith',
        username: 'janesmith',
        avatar: 'https://example.com/avatars/jane.jpg',
        createdAt: Carbon::now()->subMonths(6),
    ),
    new UserResourceDto(
        id: 3,
        name: 'Bob Johnson',
        username: 'bobjohnson',
        avatar: 'https://example.com/avatars/bob.jpg',
        createdAt: Carbon::now()->subMonths(3),
    ),
];

/** @var DataCollection<SimpleDto> $collection */
/** @phpstan-ignore-next-line unknown */
$collection = DataCollection::forDto(UserResourceDto::class, $users);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $collection->toArray());
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 4. Paginated Resource
echo "4. Paginated User Collection:\n";
echo str_repeat('-', 80) . "\n";

$paginatedUsers = array_slice($users, 0, 2);
/** @var DataCollection<SimpleDto> $collection */
/** @phpstan-ignore-next-line unknown */
$collection = DataCollection::forDto(UserResourceDto::class, $paginatedUsers);

$meta = new PaginationMetaDto(
    currentPage: 1,
    lastPage: 2,
    perPage: 2,
    total: 3,
    from: 1,
    to: 2,
);

$links = new PaginationLinksDto(
    first: 'https://api.example.com/users?page=1',
    last: 'https://api.example.com/users?page=2',
    prev: null,
    next: 'https://api.example.com/users?page=2',
);

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(
    data: $collection->toArray(),
    /** @phpstan-ignore-next-line unknown */
    meta: $meta,
    /** @phpstan-ignore-next-line unknown */
    links: $links,
);

echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 5. Nested Resource
echo "5. Post with Author (Nested Resource):\n";
echo str_repeat('-', 80) . "\n";

$post = new PostResourceDto(
    id: 1,
    title: 'Getting Started with PHP 8.2',
    slug: 'getting-started-with-php-82',
    excerpt: 'Learn about the new features in PHP 8.2',
    author: new UserResourceDto(
        id: 1,
        name: 'John Doe',
        username: 'johndoe',
        avatar: 'https://example.com/avatars/john.jpg',
        createdAt: Carbon::now()->subYear(),
    ),
    publishedAt: Carbon::now()->subDays(3),
    content: 'Full post content here...',
    comments: [
        ['id' => 1, 'content' => 'Great post!'],
        ['id' => 2, 'content' => 'Very helpful!'],
    ],
);

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $post);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 6. Post with Context (include content)
echo "6. Post with Content (Context):\n";
echo str_repeat('-', 80) . "\n";

$postWithContent = $post->withContext(['include_content' => true]);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $postWithContent);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 7. Error Response
echo "7. Error Response:\n";
echo str_repeat('-', 80) . "\n";

$error = new ErrorResponseDto(
    message: 'Validation failed',
    code: 422,
    errors: [
        'email' => ['The email field is required.'],
        'password' => ['The password must be at least 8 characters.'],
    ],
);

echo json_encode($error->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 8. Success Response
echo "8. Success Response (Created):\n";
echo str_repeat('-', 80) . "\n";

$newUser = new UserResourceDto(
    id: 4,
    name: 'Alice Williams',
    username: 'alicew',
    avatar: null,
    createdAt: Carbon::now(),
);

/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$response = new ApiResponseDto(data: $newUser);
echo json_encode($response->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  Complete API resources example completed!\n";
