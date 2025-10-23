<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Lazy as LazyAttribute;
use event4u\DataHelpers\Support\Lazy;

echo "=== Lazy Union Types Example ===\n\n";

// Example 1: Basic Lazy Union Type
echo "1. Basic Lazy Union Type\n";
echo str_repeat('-', 50) . "\n";

class DocumentDTO1 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly Lazy|string $content,  // Union type syntax!
    ) {}
}

$doc1 = DocumentDTO1::fromArray([
    'title' => 'My Document',
    'content' => 'Very long content...',
]);

echo "Document created:\n";
echo sprintf('  title: %s%s', $doc1->title, PHP_EOL);
echo "  content type: " . $doc1->content::class . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  content loaded: " . ($doc1->content->isLoaded() ? 'yes' : 'no') . "\n";
echo "\n";

echo "toArray (content excluded by default):\n";
echo json_encode($doc1->toArray()) . "\n";
echo "\n";

echo "Include content:\n";
echo json_encode($doc1->include(['content'])->toArray()) . "\n";
echo "\n";

// Example 2: Lazy with Attribute (Backward Compatible)
echo "2. Lazy with Attribute (Backward Compatible)\n";
echo str_repeat('-', 50) . "\n";

class DocumentDTO2 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        #[LazyAttribute]
        public readonly Lazy|string $content,  // Attribute syntax
    ) {}
}

$doc2 = DocumentDTO2::fromArray([
    'title' => 'Another Document',
    'content' => 'More content...',
]);

echo "Document with attribute:\n";
echo sprintf('  title: %s%s', $doc2->title, PHP_EOL);
/** @phpstan-ignore-next-line unknown */
echo "  content loaded: " . ($doc2->content->isLoaded() ? 'yes' : 'no') . "\n";
echo "\n";

// Example 3: Lazy Wrapper Methods
echo "3. Lazy Wrapper Methods\n";
echo str_repeat('-', 50) . "\n";

$lazy1 = Lazy::of(fn(): string => 'expensive computation');
echo "Lazy not loaded:\n";
echo "  isLoaded: " . ($lazy1->isLoaded() ? 'yes' : 'no') . "\n";
echo "\n";

echo "Get value (loads it):\n";
echo "  value: " . $lazy1->get() . "\n";
echo "  isLoaded: " . ($lazy1->isLoaded() ? 'yes' : 'no') . "\n";
echo "\n";

$lazy2 = Lazy::value('already loaded');
echo "Lazy with value:\n";
echo "  isLoaded: " . ($lazy2->isLoaded() ? 'yes' : 'no') . "\n";
echo "  value: " . $lazy2->get() . "\n";
echo "\n";

// Example 4: Map and Transform
echo "4. Map and Transform\n";
echo str_repeat('-', 50) . "\n";

$lazy3 = Lazy::of(fn(): string => 'hello');
/** @var DataCollection<SimpleDTO> $mapped */
/** @phpstan-ignore-next-line unknown */
/** @phpstan-ignore-next-line unknown */
$mapped = $lazy3->map(strtoupper(...));

echo "Map before loading:\n";
echo "  original loaded: " . ($lazy3->isLoaded() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  mapped loaded: " . ($mapped->isLoaded() ? 'yes' : 'no') . "\n";
/** @phpstan-ignore-next-line unknown */
echo "  mapped value: " . $mapped->get() . "\n";
echo "\n";

// Example 5: Conditional Loading
echo "5. Conditional Loading\n";
echo str_repeat('-', 50) . "\n";

class DocumentDTO3 extends SimpleDTO
{
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        #[LazyAttribute(when: 'admin')]
        public readonly Lazy|string $internalNotes,
    ) {}
}

$doc3 = DocumentDTO3::fromArray([
    'title' => 'Confidential Document',
    'internalNotes' => 'Internal notes...',
]);

echo "Without context:\n";
echo json_encode($doc3->toArray()) . "\n";
echo "\n";

echo "With admin context:\n";
echo json_encode($doc3->withVisibilityContext('admin')->toArray()) . "\n";
echo "\n";

// Example 6: Multiple Lazy Properties
echo "6. Multiple Lazy Properties\n";
echo str_repeat('-', 50) . "\n";

class BlogPostDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $comments
     * @param array<mixed> $relatedPosts
     */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    /** @phpstan-ignore-next-line unknown */
    public function __construct(
        public readonly string $title,
        public readonly string $excerpt,
        public readonly Lazy|string $content,
        public readonly Lazy|array $comments,
        public readonly Lazy|array $relatedPosts,
    ) {}
}

$post = BlogPostDTO::fromArray([
    'title' => 'My Blog Post',
    'excerpt' => 'Short excerpt...',
    'content' => 'Full content...',
    'comments' => ['comment1', 'comment2'],
    'relatedPosts' => [1, 2, 3],
]);

echo "Default (no lazy properties):\n";
echo json_encode($post->toArray()) . "\n";
echo "\n";

echo "Include specific lazy property:\n";
echo json_encode($post->include(['content'])->toArray()) . "\n";
echo "\n";

echo "Include all lazy properties:\n";
echo json_encode($post->includeAll()->toArray()) . "\n";
echo "\n";

// Example 7: Lazy toString
echo "7. Lazy toString\n";
echo str_repeat('-', 50) . "\n";

$lazy4 = Lazy::of(fn(): string => 'value');
echo "Not loaded: " . $lazy4 . "\n";

$lazy4->get();
echo "Loaded: " . $lazy4 . "\n";
echo "\n";

$lazy5 = Lazy::value(null);
echo "Null value: " . $lazy5 . "\n";
echo "\n";

$lazy6 = Lazy::value(123);
echo "Scalar value: " . $lazy6 . "\n";
echo "\n";

echo "✅  All examples completed successfully!\n";
