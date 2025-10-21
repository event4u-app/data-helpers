<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DateTimeImmutable;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Cast;

echo "================================================================================\n";
echo "SimpleDTO - Timestamp Cast Examples\n";
echo "================================================================================\n\n";

// Example 1: Unix Timestamp to DateTime
echo "Example 1: Unix Timestamp to DateTime\n";
echo "--------------------------------------\n";

class EventDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}

    protected function casts(): array
    {
        return [
            'createdAt' => 'timestamp',
            'updatedAt' => 'timestamp',
        ];
    }
}

$event = EventDTO::fromArray([
    'name' => 'Conference 2024',
    'createdAt' => 1704067200, // 2024-01-01 00:00:00 UTC
    'updatedAt' => 1704153600, // 2024-01-02 00:00:00 UTC
]);

echo sprintf('Event: %s%s', $event->name, PHP_EOL);
echo sprintf('Created: %s%s', $event->createdAt->format('Y-m-d H:i:s'), PHP_EOL);
echo sprintf('Updated: %s%s', $event->updatedAt->format('Y-m-d H:i:s'), PHP_EOL);
echo "Timestamp: {$event->createdAt->getTimestamp()}\n\n";

// Example 2: DateTime to Unix Timestamp (toArray)
echo "Example 2: DateTime to Unix Timestamp (toArray)\n";
echo "------------------------------------------------\n";

$event2 = EventDTO::fromArray([
    'name' => 'Workshop 2024',
    'createdAt' => new DateTimeImmutable('2024-03-15 10:00:00'),
    'updatedAt' => new DateTimeImmutable('2024-03-15 15:30:00'),
]);

$array = $event2->toArray();
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Event: %s%s', $array['name'], PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Created (timestamp): %s%s', $array['createdAt'], PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Updated (timestamp): %s%s', $array['updatedAt'], PHP_EOL);
echo "JSON: " . json_encode($array) . "\n\n";

// Example 3: API Response with Timestamps
echo "Example 3: API Response with Timestamps\n";
echo "----------------------------------------\n";

class UserActivityDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $action,
        public readonly DateTimeImmutable $timestamp,
    ) {}

    protected function casts(): array
    {
        return ['timestamp' => 'timestamp'];
    }
}

$activities = [
    ['userId' => 1, 'action' => 'login', 'timestamp' => 1704067200],
    ['userId' => 1, 'action' => 'view_page', 'timestamp' => 1704067260],
    ['userId' => 1, 'action' => 'logout', 'timestamp' => 1704070800],
];

echo "User Activities:\n";
foreach ($activities as $activityData) {
    $activity = UserActivityDTO::fromArray($activityData);
    echo sprintf('- %s at %s%s', $activity->action, $activity->timestamp->format('H:i:s'), PHP_EOL);
}
echo "\n";

// Example 4: Database Records
echo "Example 4: Database Records\n";
echo "---------------------------\n";

class PostDTO extends SimpleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $content,
        public readonly DateTimeImmutable $publishedAt,
        public readonly ?DateTimeImmutable $deletedAt = null,
    ) {}

    protected function casts(): array
    {
        return [
            'publishedAt' => 'timestamp',
            'deletedAt' => 'timestamp',
        ];
    }
}

$post = PostDTO::fromArray([
    'id' => 1,
    'title' => 'Getting Started with DTOs',
    'content' => 'DTOs are great for...',
    'publishedAt' => 1704067200,
    'deletedAt' => null,
]);

echo sprintf('Post: %s%s', $post->title, PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo sprintf('Published: %s%s', $post->publishedAt->format('Y-m-d H:i:s'), PHP_EOL);
/** @phpstan-ignore-next-line phpstan-error */
echo "Deleted: " . ($post->deletedAt ? $post->deletedAt->format('Y-m-d H:i:s') : 'No') . "\n";
echo "toArray: " . json_encode($post->toArray()) . "\n\n";

// Example 5: Time Calculations
echo "Example 5: Time Calculations\n";
echo "----------------------------\n";

$now = time();
$yesterday = $now - 86400;
$tomorrow = $now + 86400;

$event3 = EventDTO::fromArray([
    'name' => 'Time Travel Event',
    'createdAt' => $yesterday,
    'updatedAt' => $tomorrow,
]);

$diff = $event3->updatedAt->getTimestamp() - $event3->createdAt->getTimestamp();
$days = $diff / 86400;

echo sprintf('Event: %s%s', $event3->name, PHP_EOL);
echo sprintf('Created: %s%s', $event3->createdAt->format('Y-m-d H:i:s'), PHP_EOL);
echo sprintf('Updated: %s%s', $event3->updatedAt->format('Y-m-d H:i:s'), PHP_EOL);
echo "Difference: {$days} days\n\n";

echo "================================================================================\n";
echo "All examples completed successfully!\n";
echo "================================================================================\n";

