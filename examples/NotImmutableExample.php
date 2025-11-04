<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use event4u\DataHelpers\SimpleDto\Attributes\NotImmutable;
use event4u\DataHelpers\SimpleDto\SimpleDto;

// ============================================================================
// Example 1: Class-level #[NotImmutable] - All properties mutable
// ============================================================================

#[NotImmutable]
class MutableUserDto extends SimpleDto
{
    public function __construct(
        public string $name,
        public int $age,
        public string $email,
    ) {}
}

echo "Example 1: Class-level #[NotImmutable]\n";
echo "========================================\n\n";

$user = MutableUserDto::from([
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
]);

echo "Initial state:\n";
echo sprintf('  Name: %s%s', $user->name, PHP_EOL);
echo sprintf('  Age: %d%s', $user->age, PHP_EOL);
echo "  Email: {$user->email}\n\n";

// All properties can be modified
$user->name = 'Jane Doe';
$user->age = 31;
$user->email = 'jane@example.com';

echo "After modification:\n";
echo sprintf('  Name: %s%s', $user->name, PHP_EOL);
echo sprintf('  Age: %d%s', $user->age, PHP_EOL);
echo "  Email: {$user->email}\n\n";

// ============================================================================
// Example 2: Property-level #[NotImmutable] - Only specific properties mutable
// ============================================================================

class PartiallyMutableUserDto extends SimpleDto
{
    public function __construct(
        public string $id,                 // Immutable (no NotImmutable)
        public string $name,               // Immutable (no NotImmutable)
        #[NotImmutable]
        public int $loginCount,            // Mutable
        #[NotImmutable]
        public ?string $lastLoginAt,       // Mutable
    ) {}
}

echo "\nExample 2: Property-level #[NotImmutable]\n";
echo "==========================================\n\n";

$user2 = PartiallyMutableUserDto::from([
    'id' => 'user-123',
    'name' => 'Alice',
    'loginCount' => 0,
    'lastLoginAt' => null,
]);

echo "Initial state:\n";
echo sprintf('  ID: %s%s', $user2->id, PHP_EOL);
echo sprintf('  Name: %s%s', $user2->name, PHP_EOL);
echo sprintf('  Login Count: %d%s', $user2->loginCount, PHP_EOL);
echo "  Last Login: " . ($user2->lastLoginAt ?? 'never') . "\n\n";

// Mutable properties can be modified
$user2->loginCount = 1;
$user2->lastLoginAt = '2025-10-31 10:00:00';

echo "After login:\n";
echo sprintf('  ID: %s%s', $user2->id, PHP_EOL);
echo sprintf('  Name: %s%s', $user2->name, PHP_EOL);
echo sprintf('  Login Count: %d%s', $user2->loginCount, PHP_EOL);
echo "  Last Login: {$user2->lastLoginAt}\n\n";

// Try to modify immutable property (will throw exception)
echo "Trying to modify immutable property 'name'...\n";
try {
    $user2->name = 'Bob';
    echo "  ❌ Should have thrown exception!\n";
} catch (RuntimeException $runtimeException) {
    echo sprintf('  ✅ Exception caught: %s%s', $runtimeException->getMessage(), PHP_EOL);
}

// ============================================================================
// Example 3: Use case - Counter/Statistics DTO
// ============================================================================

class StatisticsDto extends SimpleDto
{
    public function __construct(
        public string $id,
        public string $name,
        #[NotImmutable]
        public int $viewCount = 0,
        #[NotImmutable]
        public int $likeCount = 0,
        #[NotImmutable]
        public int $shareCount = 0,
    ) {}

    public function incrementViews(): void
    {
        $this->viewCount++;
    }

    public function incrementLikes(): void
    {
        $this->likeCount++;
    }

    public function incrementShares(): void
    {
        $this->shareCount++;
    }
}

echo "\n\nExample 3: Statistics DTO with mutable counters\n";
echo "================================================\n\n";

$stats = StatisticsDto::from([
    'id' => 'post-456',
    'name' => 'My Blog Post',
]);

echo "Initial statistics:\n";
echo sprintf('  Post: %s%s', $stats->name, PHP_EOL);
echo sprintf('  Views: %d%s', $stats->viewCount, PHP_EOL);
echo sprintf('  Likes: %d%s', $stats->likeCount, PHP_EOL);
echo "  Shares: {$stats->shareCount}\n\n";

// Simulate user interactions
$stats->incrementViews();
$stats->incrementViews();
$stats->incrementViews();
$stats->incrementLikes();
$stats->incrementShares();

echo "After user interactions:\n";
echo sprintf('  Post: %s%s', $stats->name, PHP_EOL);
echo sprintf('  Views: %d%s', $stats->viewCount, PHP_EOL);
echo sprintf('  Likes: %d%s', $stats->likeCount, PHP_EOL);
echo "  Shares: {$stats->shareCount}\n\n";

// ============================================================================
// Example 4: Performance - Zero overhead after first scan
// ============================================================================

echo "\nExample 4: Performance Test\n";
echo "===========================\n\n";

$iterations = 10000;

// Test 1: Mutable property access
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $stats->viewCount++;
}
$duration = (microtime(true) - $start) * 1000000; // Convert to microseconds
$perOp = $duration / $iterations;

echo "Mutable property modification:\n";
echo "  Total: " . number_format($duration, 2) . " μs\n";
echo "  Per operation: " . number_format($perOp, 3) . " μs\n";
echo "  Operations/sec: " . number_format(1000000 / $perOp, 0) . "\n\n";

echo "✅ All examples completed successfully!\n";
