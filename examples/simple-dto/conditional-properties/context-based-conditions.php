<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContext;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextEquals;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextIn;
use event4u\DataHelpers\SimpleDTO\Attributes\WhenContextNotNull;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   CONTEXT-BASED CONDITIONS                                 ║\n";
echo "║                    Phase 17.3 - Context Support                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Example 1: Basic context check
echo "1. BASIC CONTEXT CHECK - KEY EXISTS:\n";
echo "------------------------------------------------------------\n";

class UserDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[WhenContext('includePhone')]
        public readonly ?string $phone = null,
    ) {}
}

$user = new UserDTO('John Doe', 'john@example.com', '555-1234');

echo "Without context:\n";
echo json_encode($user->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nWith context (includePhone = true):\n";
echo json_encode($user->withContext(['includePhone' => true])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Property included only when context key exists\n";

echo "\n";

// Example 2: Context value comparison
echo "2. CONTEXT VALUE COMPARISON:\n";
echo "------------------------------------------------------------\n";

class AdminDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,

        #[WhenContext('role', 'admin')]
        public readonly string $adminPanel = '/admin',

        #[WhenContext('role', 'moderator')]
        public readonly string $moderationPanel = '/moderation',
    ) {}
}

$admin = new AdminDTO('John Doe');

echo "As admin:\n";
echo json_encode($admin->withContext(['role' => 'admin'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs moderator:\n";
echo json_encode($admin->withContext(['role' => 'moderator'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAs user:\n";
echo json_encode($admin->withContext(['role' => 'user'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different properties based on context value\n";

echo "\n";

// Example 3: Context with operators
echo "3. CONTEXT WITH OPERATORS:\n";
echo "------------------------------------------------------------\n";

class ProductDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[WhenContext('userLevel', '>=', 5)]
        public readonly float $wholesalePrice = 79.99,

        #[WhenContext('stock', '<', 10)]
        public readonly string $lowStockWarning = 'Low stock!',
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$product = new ProductDTO('Premium Widget', 99.99);

echo "User level 5 (wholesale customer):\n";
echo json_encode($product->withContext(['userLevel' => 5, 'stock' => 50])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nUser level 3 (regular customer) with low stock:\n";
echo json_encode($product->withContext(['userLevel' => 3, 'stock' => 5])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Operators: >=, <=, >, <, !=, ==, ===\n";

echo "\n";

// Example 4: WhenContextIn - Multiple values
echo "4. WHEN CONTEXT IN - MULTIPLE VALUES:\n";
echo "------------------------------------------------------------\n";

class ContentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,

        #[WhenContextIn('role', ['admin', 'moderator', 'editor'])]
        public readonly string $editLink = '/edit',

        #[WhenContextIn('status', ['draft', 'pending'])]
        public readonly string $publishButton = 'Publish',
    ) {}
}

$content = new ContentDTO('Article Title', 'Article content...');

echo "As admin:\n";
echo json_encode(
    $content->withContext(['role' => 'admin', 'status' => 'published'])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\nAs author with draft:\n";
echo json_encode(
    $content->withContext(['role' => 'author', 'status' => 'draft'])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\n✅  Include when context value is in a list\n";

echo "\n";

// Example 5: Multiple conditions (AND logic)
echo "5. MULTIPLE CONDITIONS - AND LOGIC:\n";
echo "------------------------------------------------------------\n";

class PremiumContentDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $title,

        #[WhenContextEquals('subscription', 'premium')]
        #[WhenContextEquals('verified', true)]
        public readonly string $exclusiveContent = 'Premium exclusive content...',
    ) {}
}

$premiumContent = new PremiumContentDTO('Premium Article');

echo "Premium + Verified:\n";
echo json_encode(
    $premiumContent->withContext(['subscription' => 'premium', 'verified' => true])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\nPremium but not verified:\n";
echo json_encode(
    $premiumContent->withContext(['subscription' => 'premium', 'verified' => false])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\nVerified but not premium:\n";
echo json_encode(
    $premiumContent->withContext(['subscription' => 'basic', 'verified' => true])->toArray(),
    JSON_PRETTY_PRINT
) . PHP_EOL;

echo "\n✅  All conditions must be met (AND logic)\n";

echo "\n";

// Example 6: API Response with user context
echo "6. API RESPONSE WITH USER CONTEXT:\n";
echo "------------------------------------------------------------\n";

class OrderDTO extends SimpleDTO
{
    /** @param array<mixed> $items */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly float $total,

        #[WhenContextNotNull('user')]
        public readonly string $customerName = 'John Doe',

        #[WhenContextIn('role', ['admin', 'support'])]
        public readonly string $internalNotes = 'Customer requested express shipping',

        #[WhenContextEquals('includeItems', true)]
        public readonly array $items = [],
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$order = new OrderDTO('ORD-12345', 'completed', 299.99);

echo "Public API (no context):\n";
echo json_encode($order->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAuthenticated user:\n";
echo json_encode($order->withContext(['user' => (object)['id' => 1]])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin view with items:\n";
echo json_encode($order->withContext([
    'user' => (object)['id' => 1],
    'role' => 'admin',
    'includeItems' => true,
])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different data based on user context\n";
echo "✅  Perfect for API responses\n";

echo "\n";

// Example 7: Environment-based features
echo "7. ENVIRONMENT-BASED FEATURES:\n";
echo "------------------------------------------------------------\n";

class AppConfigDTO extends SimpleDTO
{
    /** @param array<mixed> $debugInfo */
    public function __construct(
        public readonly string $appName,
        public readonly string $version,

        #[WhenContext('environment', '!=', 'production')]
        public readonly array $debugInfo = ['memory' => '128MB', 'queries' => 42],

        #[WhenContextEquals('environment', 'development')]
        public readonly string $devToolbar = 'enabled',

        #[WhenContextEquals('environment', 'production')]
        public readonly bool $cacheEnabled = true,
    ) {}
}

$config = new AppConfigDTO('MyApp', '1.0.0');

echo "Development environment:\n";
echo json_encode($config->withContext(['environment' => 'development'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nProduction environment:\n";
echo json_encode($config->withContext(['environment' => 'production'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Different features per environment\n";

echo "\n";

// Example 8: Chaining context
echo "8. CHAINING CONTEXT:\n";
echo "------------------------------------------------------------\n";

class DashboardDTO extends SimpleDTO
{
    /**
     * @param array<mixed> $statistics
     * @param array<mixed> $charts
     */
    public function __construct(
        public readonly string $title,

        #[WhenContextEquals('showStats', true)]
        public readonly array $statistics = ['users' => 1000, 'orders' => 500],

        #[WhenContextEquals('showCharts', true)]
        public readonly array $charts = ['sales', 'traffic'],
    ) {}
}

/** @phpstan-ignore-next-line unknown */
$dashboard = new DashboardDTO('Dashboard');

$enriched = $dashboard
    ->withContext(['showStats' => true])
    ->withContext(['showCharts' => true]);

echo "Dashboard with stats and charts:\n";
echo json_encode($enriched->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Context can be chained\n";
echo "✅  Contexts are merged\n";

echo "\n";

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                           SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  WhenContext('key') - Include when context key exists\n";
echo "✅  WhenContext('key', 'value') - Include when context value equals\n";
echo "✅  WhenContext('key', '>=', 5) - Include with operator comparison\n";
echo "✅  WhenContextEquals('key', 'value') - Shorthand for equality\n";
echo "✅  WhenContextIn('key', ['a', 'b']) - Include when value in list\n";
echo "✅  WhenContextNotNull('key') - Include when key exists and not null\n";
echo "✅  Multiple conditions - AND logic (all must be true)\n";
echo "✅  Chainable - withContext() can be chained\n";
echo "✅  Perfect for role-based access, environment configs, API responses\n";

echo "\n";
