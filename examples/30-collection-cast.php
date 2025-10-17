<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDTO\DataCollection;

echo "================================================================================\n";
echo "SimpleDTO - Collection Cast Examples (Framework-Independent)\n";
echo "================================================================================\n\n";

echo "Note: CollectionCast now creates DataCollection instances (framework-independent).\n";
echo "You must specify a DTO class: 'collection:UserDTO'\n\n";

// Example 1: DataCollection of DTOs
echo "Example 1: DataCollection of DTOs\n";
echo "----------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

class TeamDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly DataCollection $members,
    ) {}

    protected function casts(): array
    {
        return ['members' => 'collection:' . UserDTO::class];
    }
}

$teamDTO = TeamDTO::fromArray([
    'name' => 'Development Team',
    'members' => [
        ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'age' => 28, 'email' => 'jane@example.com'],
        ['name' => 'Bob Johnson', 'age' => 35, 'email' => 'bob@example.com'],
    ],
]);

echo "Team: {$teamDTO->name}\n";
echo "Members: {$teamDTO->members->count()}\n";
echo "First member: {$teamDTO->members->first()->name} ({$teamDTO->members->first()->age} years)\n";
echo "Last member: {$teamDTO->members->last()->name}\n";
echo "toArray(): " . json_encode($teamDTO->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 2: DataCollectionOf Attribute
echo "Example 2: DataCollectionOf Attribute\n";
echo "-------------------------------------\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $orderNumber,
        #[DataCollectionOf(ProductDTO::class)]
        public readonly DataCollection $items,
        public readonly float $total,
    ) {}
}

$orderDTO = OrderDTO::fromArray([
    'orderNumber' => 'ORD-2024-001',
    'items' => [
        ['name' => 'Laptop', 'price' => 999.99, 'stock' => 5],
        ['name' => 'Mouse', 'price' => 29.99, 'stock' => 50],
        ['name' => 'Keyboard', 'price' => 79.99, 'stock' => 30],
    ],
    'total' => 1109.97,
]);

echo "Order: {$orderDTO->orderNumber}\n";
echo "Items: {$orderDTO->items->count()}\n";
echo "First item: {$orderDTO->items->first()->name} (\${$orderDTO->items->first()->price})\n";
echo "Total: \${$orderDTO->total}\n";
echo "toArray(): " . json_encode($orderDTO->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Nested Collections
echo "Example 3: Nested Collections\n";
echo "-----------------------------\n";

class CommentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $author,
        public readonly string $text,
    ) {}
}

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(CommentDTO::class)]
        public readonly DataCollection $comments,
    ) {}
}

class BlogDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        #[DataCollectionOf(PostDTO::class)]
        public readonly DataCollection $posts,
    ) {}
}

$blogDTO = BlogDTO::fromArray([
    'name' => 'Tech Blog',
    'posts' => [
        [
            'title' => 'PHP 8.2 Features',
            'content' => 'PHP 8.2 brings many new features...',
            'comments' => [
                ['author' => 'Alice', 'text' => 'Great article!'],
                ['author' => 'Bob', 'text' => 'Very informative.'],
            ],
        ],
        [
            'title' => 'Laravel 11',
            'content' => 'Laravel 11 is amazing...',
            'comments' => [
                ['author' => 'Charlie', 'text' => 'Can\'t wait to try it!'],
            ],
        ],
    ],
]);

echo "Blog: {$blogDTO->name}\n";
echo "Posts: {$blogDTO->posts->count()}\n";
echo "First post: {$blogDTO->posts->first()->title}\n";
echo "First post comments: {$blogDTO->posts->first()->comments->count()}\n";
echo "toArray(): " . json_encode($blogDTO->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Empty Collections
echo "Example 4: Empty Collections\n";
echo "----------------------------\n";

$emptyTeam = TeamDTO::fromArray([
    'name' => 'Empty Team',
    'members' => [],
]);

echo "Team: {$emptyTeam->name}\n";
echo "Members: {$emptyTeam->members->count()}\n";
echo "Is empty: " . ($emptyTeam->members->isEmpty() ? 'Yes' : 'No') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";


