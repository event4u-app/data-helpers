<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\SimpleDto\DataCollection;

echo "================================================================================\n";
echo "SimpleDto - Collection Cast Examples (Framework-Independent)\n";
echo "================================================================================\n\n";

echo "Note: CollectionCast now creates DataCollection instances (framework-independent).\n";
echo "You must specify a Dto class: 'collection:UserDto'\n\n";

// Example 1: DataCollection<SimpleDto> of Dtos
echo "Example 1: DataCollection<SimpleDto> of Dtos\n";
echo "----------------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}
}

class TeamDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        public readonly DataCollection $members,
    ) {}

    protected function casts(): array
    {
        return ['members' => 'collection:' . UserDto::class];
    }
}

$teamDto = TeamDto::fromArray([
    'name' => 'Development Team',
    'members' => [
        ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com'],
        ['name' => 'Jane Smith', 'age' => 28, 'email' => 'jane@example.com'],
        ['name' => 'Bob Johnson', 'age' => 35, 'email' => 'bob@example.com'],
    ],
]);

echo sprintf('Team: %s%s', $teamDto->name, PHP_EOL);
echo sprintf('Members: %d%s', $teamDto->members->count(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "First member: {$teamDto->members->first()->name} ({$teamDto->members->first()->age} years)\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('Last member: %s%s', $teamDto->members->last()->name, PHP_EOL);
echo "toArray(): " . json_encode($teamDto->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 2: DataCollection<SimpleDto>Of Attribute
echo "Example 2: DataCollection<SimpleDto>Of Attribute\n";
echo "-------------------------------------\n";

#[AutoCast]
class ProductDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}

#[AutoCast]
class OrderDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $orderNumber,
        #[DataCollectionOf(ProductDto::class)]
        public readonly DataCollection $items,
        public readonly float $total,
    ) {}
}

$orderDto = OrderDto::fromArray([
    'orderNumber' => 'ORD-2024-001',
    'items' => [
        ['name' => 'Laptop', 'price' => 999.99, 'stock' => 5],
        ['name' => 'Mouse', 'price' => 29.99, 'stock' => 50],
        ['name' => 'Keyboard', 'price' => 79.99, 'stock' => 30],
    ],
    'total' => 1109.97,
]);

echo sprintf('Order: %s%s', $orderDto->orderNumber, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Items: %s%s', $orderDto->items->count(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
echo "First item: {$orderDto->items->first()->name} (\${$orderDto->items->first()->price})\n";
echo sprintf('Total: $%s%s', $orderDto->total, PHP_EOL);
echo "toArray(): " . json_encode($orderDto->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Nested Collections
echo "Example 3: Nested Collections\n";
echo "-----------------------------\n";

class CommentDto extends SimpleDto
{
    public function __construct(
        public readonly string $author,
        public readonly string $text,
    ) {}
}

class PostDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        #[DataCollectionOf(CommentDto::class)]
        public readonly DataCollection $comments,
    ) {}
}

class BlogDto extends SimpleDto
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $name,
        #[DataCollectionOf(PostDto::class)]
        public readonly DataCollection $posts,
    ) {}
}

$blogDto = BlogDto::fromArray([
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

echo sprintf('Blog: %s%s', $blogDto->name, PHP_EOL);
echo sprintf('Posts: %d%s', $blogDto->posts->count(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('First post: %s%s', $blogDto->posts->first()->title, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('First post comments: %s%s', $blogDto->posts->first()->comments->count(), PHP_EOL);
echo "toArray(): " . json_encode($blogDto->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Empty Collections
echo "Example 4: Empty Collections\n";
echo "----------------------------\n";

$emptyTeam = TeamDto::fromArray([
    'name' => 'Empty Team',
    'members' => [],
]);

echo sprintf('Team: %s%s', $emptyTeam->name, PHP_EOL);
echo sprintf('Members: %d%s', $emptyTeam->members->count(), PHP_EOL);
echo "Is empty: " . ($emptyTeam->members->isEmpty() ? 'Yes' : 'No') . "\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";
