<?php

declare(strict_types=1);

/**
 * Real-World Example: Blog Platform
 *
 * This example demonstrates a complete blog system using SimpleDTO:
 * - Blog posts with authors
 * - Comments with replies
 * - Categories and tags
 * - Reading time calculation
 * - Conditional visibility
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;
use event4u\DataHelpers\SimpleDTO\Attributes\Computed;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenAuth;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenCan;
use event4u\DataHelpers\SimpleDTO\Casts\DateTimeCast;

// ============================================================================
// DTOs
// ============================================================================

class AuthorDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly ?string $avatar,
        public readonly ?string $bio,
        
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?string $email = null,
    ) {}
}

class CategoryDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly int $postCount,
    ) {}
}

class CommentDTO extends SimpleDTO
{
    /**
     * @param array<mixed>|null $replies
     */
    /** @param array<mixed> $replies */
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly ?int $parentId,
        
        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $createdAt,
        
        #[Lazy]
        public readonly ?array $replies = null,
        
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?bool $canEdit = null,
        
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?bool $canDelete = null,
    ) {}
}

class PostDTO extends SimpleDTO
{
    /**
     * @param array<mixed>|null $comments
     */
    /**
     * @param array<mixed> $tags
     * @param array<mixed> $comments
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $excerpt,
        public readonly string $content,
        public readonly AuthorDTO $author,
        public readonly CategoryDTO $category,
        /** @var string[] */
        public readonly array $tags,
        public readonly string $status,
        public readonly int $views,
        public readonly int $commentCount,
        
        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $publishedAt,
        
        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly ?Carbon $updatedAt,
        
        #[Lazy]
        public readonly ?array $comments = null,
        
        /** @phpstan-ignore-next-line unknown */
        #[WhenAuth]
        public readonly ?string $editUrl = null,
        
        /** @phpstan-ignore-next-line unknown */
        #[WhenCan('edit')]
        public readonly ?string $deleteUrl = null,
    ) {}
    
    #[Computed]
    public function readingTime(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return (int)ceil($words / 200); // 200 words per minute
    }
    
    #[Computed]
    public function url(): string
    {
        return 'https://blog.example.com/posts/' . $this->slug;
    }
    
    #[Computed]
    public function isRecent(): bool
    {
        return $this->publishedAt->isAfter(Carbon::now()->subDays(7));
    }
}

class PostListItemDTO extends SimpleDTO
{
    /** @param array<mixed> $tags */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $excerpt,
        public readonly AuthorDTO $author,
        public readonly CategoryDTO $category,
        /** @var string[] */
        public readonly array $tags,
        public readonly int $views,
        public readonly int $commentCount,
        
        /** @phpstan-ignore-next-line unknown */
        #[Cast(DateTimeCast::class)]
        public readonly Carbon $publishedAt,
    ) {}
    
    #[Computed]
    public function url(): string
    {
        return 'https://blog.example.com/posts/' . $this->slug;
    }
}

// ============================================================================
// Example Usage
// ============================================================================

echo "=== Blog Platform Example ===\n\n";

// 1. Create Author
echo "1. Author Profile:\n";
echo str_repeat('-', 80) . "\n";

$author = new AuthorDTO(
    name: 'Jane Smith',
    email: 'jane@example.com',
    /** @phpstan-ignore-next-line unknown */
    id: 1,
    /** @phpstan-ignore-next-line unknown */
    username: 'janesmith',
    /** @phpstan-ignore-next-line unknown */
    avatar: 'https://example.com/avatars/jane.jpg',
    /** @phpstan-ignore-next-line unknown */
    bio: 'Tech writer and developer advocate',
);

echo sprintf('Author: %s%s', $author->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Username: @%s%s', $author->username, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Bio: {$author->bio}\n\n";

// 2. Create Category
echo "2. Category:\n";
echo str_repeat('-', 80) . "\n";

$category = new CategoryDTO(
    id: 1,
    name: 'Technology',
    slug: 'technology',
    description: 'Latest tech news and tutorials',
    /** @phpstan-ignore-next-line unknown */
    postCount: 42,
);

echo sprintf('Category: %s%s', $category->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Posts: {$category->postCount}\n\n";

// 3. Create Blog Post
echo "3. Blog Post:\n";
echo str_repeat('-', 80) . "\n";

$post = new PostDTO(
    title: 'Getting Started with PHP 8.2',
    content: str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100),
    /** @phpstan-ignore-next-line unknown */
    tags: ['php', 'tutorial', 'programming'],
    /** @phpstan-ignore-next-line unknown */
    id: 1,
    /** @phpstan-ignore-next-line unknown */
    slug: 'getting-started-with-php-82',
    /** @phpstan-ignore-next-line unknown */
    excerpt: 'Learn about the new features in PHP 8.2',
    /** @phpstan-ignore-next-line unknown */
    author: $author,
    /** @phpstan-ignore-next-line unknown */
    category: $category,
    /** @phpstan-ignore-next-line unknown */
    status: 'published',
    /** @phpstan-ignore-next-line unknown */
    views: 1250,
    /** @phpstan-ignore-next-line unknown */
    commentCount: 15,
    /** @phpstan-ignore-next-line unknown */
    publishedAt: Carbon::now()->subDays(3),
    /** @phpstan-ignore-next-line unknown */
    updatedAt: Carbon::now()->subDay(),
    /** @phpstan-ignore-next-line unknown */
    comments: null,
    /** @phpstan-ignore-next-line unknown */
    editUrl: '/admin/posts/1/edit',
    /** @phpstan-ignore-next-line unknown */
    deleteUrl: '/admin/posts/1/delete',
);

echo sprintf('Title: %s%s', $post->title, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Author: %s%s', $post->author->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Category: %s%s', $post->category->name, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Tags: " . implode(', ', $post->tags) . "\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('Published: %s%s', $post->publishedAt->diffForHumans(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Views: %s%s', $post->views, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo sprintf('Comments: %s%s', $post->commentCount, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Reading Time: {$post->readingTime()} min\n";
/** @phpstan-ignore-next-line unknown */
echo sprintf('URL: %s%s', $post->url(), PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "Recent: " . ($post->isRecent() ? 'Yes' : 'No') . "\n\n";

// 4. Create Comments
echo "4. Comments:\n";
echo str_repeat('-', 80) . "\n";

$comment1 = new CommentDTO(
    id: 1,
    content: 'Great article! Very helpful.',
    author: new AuthorDTO(
        name: 'John Doe',
        email: 'john@example.com',
        /** @phpstan-ignore-next-line unknown */
        id: 2,
        /** @phpstan-ignore-next-line unknown */
        username: 'johndoe',
        /** @phpstan-ignore-next-line unknown */
        avatar: 'https://example.com/avatars/john.jpg',
        /** @phpstan-ignore-next-line unknown */
        bio: null,
    ),
    parentId: null,
    createdAt: Carbon::now()->subHours(2),
    replies: null,
    canEdit: true,
    canDelete: false,
);

$comment2 = new CommentDTO(
    id: 2,
    content: 'Thanks for the feedback!',
    author: $author,
    parentId: 1,
    createdAt: Carbon::now()->subHour(),
    replies: null,
    canEdit: true,
    canDelete: true,
);

echo "Comment 1:\n";
echo sprintf('  Author: %s%s', $comment1->author->name, PHP_EOL);
echo sprintf('  Content: %s%s', $comment1->content, PHP_EOL);
echo "  Posted: {$comment1->createdAt->diffForHumans()}\n\n";

echo "Comment 2 (Reply):\n";
echo sprintf('  Author: %s%s', $comment2->author->name, PHP_EOL);
echo sprintf('  Content: %s%s', $comment2->content, PHP_EOL);
echo "  Posted: {$comment2->createdAt->diffForHumans()}\n\n";

// 5. Post List for Homepage
echo "5. Post List (Homepage):\n";
echo str_repeat('-', 80) . "\n";

$posts = [
    new PostListItemDTO(
        id: 1,
        title: 'Getting Started with PHP 8.2',
        slug: 'getting-started-with-php-82',
        excerpt: 'Learn about the new features in PHP 8.2',
        author: $author,
        category: $category,
        tags: ['php', 'tutorial'],
        views: 1250,
        commentCount: 15,
        publishedAt: Carbon::now()->subDays(3),
    ),
    new PostListItemDTO(
        id: 2,
        title: 'Building REST APIs with Laravel',
        slug: 'building-rest-apis-with-laravel',
        excerpt: 'A comprehensive guide to building REST APIs',
        author: $author,
        category: $category,
        tags: ['laravel', 'api'],
        views: 890,
        commentCount: 8,
        publishedAt: Carbon::now()->subDays(5),
    ),
];

foreach ($posts as $postItem) {
    echo sprintf('- %s%s', $postItem->title, PHP_EOL);
    echo "  By {$postItem->author->name} | {$postItem->views} views | {$postItem->commentCount} comments\n";
    echo "  {$postItem->url()}\n\n";
}

// 6. Serialize for API
echo "6. API Response (Post Detail):\n";
echo str_repeat('-', 80) . "\n";
echo json_encode($post->toArray(), JSON_PRETTY_PRINT) . "\n\n";

echo "✅  Blog platform example completed!\n";
