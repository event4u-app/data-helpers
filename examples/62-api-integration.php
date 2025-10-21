<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;

echo "=== API Integration Example ===\n\n";

// Example 1: GET Request - Full Resource
echo "1. GET Request - Full Resource\n";
echo str_repeat('-', 50) . "\n";

class UserDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $posts
     * @param array<mixed> $comments
     */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $bio,
        public readonly Lazy|array $posts,
        public readonly Lazy|array $comments,
        public readonly string $createdAt,
    ) {}
}

$user = new UserDTO(
    name: 'John Doe',
    email: 'john@example.com',
    /** @phpstan-ignore-next-line unknown */
    id: 1,
    /** @phpstan-ignore-next-line unknown */
    bio: 'Software developer',
    /** @phpstan-ignore-next-line unknown */
    posts: Lazy::of(fn(): array => ['post1', 'post2', 'post3']),
    /** @phpstan-ignore-next-line unknown */
    comments: Lazy::of(fn(): array => ['comment1', 'comment2']),
    /** @phpstan-ignore-next-line unknown */
    createdAt: '2024-01-01T00:00:00Z',
);

echo "GET /api/users/1\n";
echo "Response (lazy excluded):\n";
echo json_encode($user, JSON_PRETTY_PRINT) . "\n\n";

echo "GET /api/users/1?include=posts\n";
echo "Response (posts included):\n";
echo json_encode($user->include(['posts']), JSON_PRETTY_PRINT) . "\n\n";

echo "GET /api/users/1?include=posts,comments\n";
echo "Response (all lazy included):\n";
echo json_encode($user->includeAll(), JSON_PRETTY_PRINT) . "\n\n";

// Example 2: POST Request - Create Resource
echo "2. POST Request - Create Resource\n";
echo str_repeat('-', 50) . "\n";

class CreateUserDTO extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly Optional|string|null $bio,
    ) {}
}

$requestBody = '{"name":"Jane Doe","email":"jane@example.com","bio":"Designer"}';
echo "POST /api/users\n";
echo "Request body:\n{$requestBody}\n\n";

$createData = json_decode($requestBody, true);
$createUser = CreateUserDTO::fromArray($createData);

echo "Parsed DTO:\n";
echo sprintf('  name: %s%s', $createUser->name, PHP_EOL);
echo sprintf('  email: %s%s', $createUser->email, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  bio present: " . ($createUser->bio->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  bio value: " . ($createUser->bio->get() ?? 'null') . "\n";
echo "\n";

// Simulate creating user
$newUser = new UserDTO(
    name: $createUser->name,
    email: $createUser->email,
    /** @phpstan-ignore-next-line unknown */
    id: 2,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    bio: $createUser->bio->get(),
    /** @phpstan-ignore-next-line unknown */
    posts: Lazy::of(fn(): array => []),
    /** @phpstan-ignore-next-line unknown */
    comments: Lazy::of(fn(): array => []),
    /** @phpstan-ignore-next-line unknown */
    createdAt: date('c'),
);

echo "Response (201 Created):\n";
echo json_encode($newUser, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: PATCH Request - Partial Update
echo "3. PATCH Request - Partial Update\n";
echo str_repeat('-', 50) . "\n";

class UpdateUserDTO extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly Optional|string $name,
        public readonly Optional|string $email,
        public readonly Optional|string|null $bio,
    ) {}
}

$patchBody = '{"name":"John Smith","bio":null}';
echo "PATCH /api/users/1\n";
echo "Request body:\n{$patchBody}\n\n";

$patchData = json_decode($patchBody, true);
$updateUser = UpdateUserDTO::fromArray($patchData);

echo "Parsed DTO:\n";
/** @phpstan-ignore-next-line unknown */
echo "  name present: " . ($updateUser->name->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  email present: " . ($updateUser->email->isPresent() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  bio present: " . ($updateUser->bio->isPresent() ? 'yes' : 'no') . "\n";
echo "\n";

$changes = $updateUser->partial();
echo "Extracted changes:\n";
echo json_encode($changes, JSON_PRETTY_PRINT) . "\n\n";

// Apply changes to existing user
$updatedUser = new UserDTO(
    /** @phpstan-ignore-next-line unknown */
    name: $changes['name'] ?? $user->name,
    /** @phpstan-ignore-next-line unknown */
    email: $changes['email'] ?? $user->email,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    id: $user->id,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    bio: array_key_exists('bio', $changes) ? $changes['bio'] : $user->bio,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    posts: $user->posts,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    comments: $user->comments,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    createdAt: $user->createdAt,
);

echo "Response (200 OK):\n";
echo json_encode($updatedUser, JSON_PRETTY_PRINT) . "\n\n";

// Example 4: PUT Request - Full Update
echo "4. PUT Request - Full Update\n";
echo str_repeat('-', 50) . "\n";

$putBody = '{"name":"John Updated","email":"john.updated@example.com","bio":"Updated bio"}';
echo "PUT /api/users/1\n";
echo "Request body:\n{$putBody}\n\n";

$putData = json_decode($putBody, true);
$fullUpdateUser = CreateUserDTO::fromArray($putData);

$replacedUser = new UserDTO(
    name: $fullUpdateUser->name,
    email: $fullUpdateUser->email,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    id: $user->id,
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    bio: $fullUpdateUser->bio->get(),
    /** @phpstan-ignore-next-line unknown */
    posts: Lazy::of(fn(): array => ['post1', 'post2', 'post3']),
    /** @phpstan-ignore-next-line unknown */
    comments: Lazy::of(fn(): array => ['comment1', 'comment2']),
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    createdAt: $user->createdAt,
);

echo "Response (200 OK):\n";
echo json_encode($replacedUser, JSON_PRETTY_PRINT) . "\n\n";

// Example 5: JSON:API Format
echo "5. JSON:API Format\n";
echo str_repeat('-', 50) . "\n";

class JsonApiUserDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $attributes
     * @param array<mixed> $relationships
     */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly array $attributes,
        public readonly Optional|array $relationships,
    ) {}
}

$jsonApiUser = new JsonApiUserDTO(
    id: 1,
    type: 'users',
    attributes: [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Software developer',
    ],
    relationships: Optional::of([
        'posts' => [
            'data' => [
                ['type' => 'posts', 'id' => 1],
                ['type' => 'posts', 'id' => 2],
            ],
        ],
    ]),
);

echo "GET /api/users/1 (JSON:API format)\n";
echo "Response:\n";
echo json_encode(['data' => $jsonApiUser->toArray()], JSON_PRETTY_PRINT) . "\n\n";

// Example 6: Error Handling
echo "6. Error Handling\n";
echo str_repeat('-', 50) . "\n";

class ErrorDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $errors
     */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly int $status,
        public readonly string $message,
        public readonly Optional|array $errors,
        public readonly Optional|string $trace,
    ) {}
}

$validationError = new ErrorDTO(
    status: 422,
    message: 'Validation failed',
    errors: Optional::of([
        'email' => ['The email field is required.'],
        'name' => ['The name must be at least 3 characters.'],
    ]),
    trace: Optional::empty(),
);

echo "Response (422 Unprocessable Entity):\n";
echo json_encode($validationError, JSON_PRETTY_PRINT) . "\n\n";

$serverError = new ErrorDTO(
    status: 500,
    message: 'Internal server error',
    errors: Optional::empty(),
    trace: Optional::of('Stack trace...'),
);

echo "Response (500 Internal Server Error, with trace):\n";
echo json_encode($serverError, JSON_PRETTY_PRINT) . "\n\n";

// Example 7: Pagination
echo "7. Pagination\n";
echo str_repeat('-', 50) . "\n";

class PaginatedResponseDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $data
     * @param array<mixed> $meta
     * @param array<mixed> $links
     */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly array $data,
        public readonly array $meta,
        public readonly Optional|array $links,
    ) {}
}

$users = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
];

$paginatedResponse = new PaginatedResponseDTO(
    data: $users,
    meta: [
        'current_page' => 1,
        'per_page' => 10,
        'total' => 2,
        'last_page' => 1,
    ],
    links: Optional::of([
        'first' => '/api/users?page=1',
        'last' => '/api/users?page=1',
        'prev' => null,
        'next' => null,
    ]),
);

echo "GET /api/users?page=1\n";
echo "Response:\n";
echo json_encode($paginatedResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "✅  All examples completed successfully!\n";

