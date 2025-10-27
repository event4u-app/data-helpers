<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Attribute;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    CUSTOM CONDITIONAL ATTRIBUTES                           ║\n";
echo "║                    Phase 17 - Extending Conditional System                 ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Example 1: Custom WhenPremium Attribute
echo "1. CUSTOM WHENPREMIUM ATTRIBUTE:\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenPremium implements ConditionalProperty
{
    /** @param array<mixed> $context */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check if user is premium from context
        $user = $context['user'] ?? null;

        if ($user && method_exists($user, 'isPremium')) {
            /** @phpstan-ignore-next-line unknown */
            return $user->isPremium();
        }

        // Check if user has premium property
        /** @phpstan-ignore-next-line unknown */
        if ($user && isset($user->premium)) {
            return (bool)$user->premium;
        }

        // Check context directly
        return true === ($context['is_premium'] ?? false);
    }
}

class ProductDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $premium_features
     */
    /** @param array<mixed> $premium_features */
    public function __construct(
        public readonly string $name,
        public readonly float $price,

        #[WhenPremium]
        public readonly ?float $discount = null,

        #[WhenPremium]
        public readonly ?array $premium_features = null,
    ) {}
}

// Test with premium user
/** @phpstan-ignore-next-line unknown */
$product = new ProductDto(
    'Premium Product',
    99.99,
    10.00,
    ['feature1', 'feature2']
);

echo "Without premium context:\n";
echo json_encode($product->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nWith premium context:\n";
echo json_encode($product->withContext(['is_premium' => true])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Custom WhenPremium attribute works!\n";
echo "✅  Premium features only shown to premium users\n\n";

// Example 2: Custom WhenEnvironment Attribute
echo "2. CUSTOM WHENENVIRONMENT ATTRIBUTE:\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenEnvironment implements ConditionalProperty
{
    /** @param array<mixed> $environments */
    public function __construct(
        private readonly string|array $environments,
    ) {}

    /** @param array<mixed> $context */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $currentEnv = $context['environment'] ?? 'production';

        $allowedEnvs = is_array($this->environments)
            ? $this->environments
            : [$this->environments];

        return in_array($currentEnv, $allowedEnvs, true);
    }
}

class ApiResponseDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $debug_info
     * @param array<mixed>|null $sql_queries
     */
    /**
     * @param array<mixed> $debug_info
     * @param array<mixed> $sql_queries
     */
    public function __construct(
        public readonly string $status,
        public readonly mixed $data,

        #[WhenEnvironment(['development', 'staging'])]
        public readonly ?array $debug_info = null,

        #[WhenEnvironment('development')]
        public readonly ?array $sql_queries = null,
    ) {}
}

$response = new ApiResponseDto(
    'success',
    ['user' => 'John'],
    ['memory' => '2MB', 'time' => '150ms'],
    ['SELECT * FROM users']
);

echo "Production environment:\n";
echo json_encode($response->withContext(['environment' => 'production'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nDevelopment environment:\n";
echo json_encode($response->withContext(['environment' => 'development'])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Custom WhenEnvironment attribute works!\n";
echo "✅  Debug info only shown in dev/staging\n";
echo "✅  SQL queries only shown in development\n\n";

// Example 3: Custom WhenFeatureFlag Attribute
echo "3. CUSTOM WHENFEATUREFLAG ATTRIBUTE:\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenFeatureFlag implements ConditionalProperty
{
    public function __construct(
        private readonly string $flag,
    ) {}

    /** @param array<mixed> $context */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $features = $context['features'] ?? [];

        return in_array($this->flag, $features, true)
            || true === ($features[$this->flag] ?? false);
    }
}

class UserProfileDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $profile_v2
     * @param array<mixed>|null $social_links
     */
    /**
     * @param array<mixed> $profile_v2
     * @param array<mixed> $social_links
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[WhenFeatureFlag('new_profile_design')]
        public readonly ?array $profile_v2 = null,

        #[WhenFeatureFlag('social_features')]
        public readonly ?array $social_links = null,
    ) {}
}

$profile = new UserProfileDto(
    'John Doe',
    'john@example.com',
    ['theme' => 'dark', 'layout' => 'modern'],
    ['twitter' => '@john', 'github' => 'john']
);

echo "Without feature flags:\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nWith new_profile_design flag:\n";
echo json_encode($profile->withContext(['features' => ['new_profile_design']])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nWith all feature flags:\n";
echo json_encode($profile->withContext([
    'features' => ['new_profile_design', 'social_features'],
])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Custom WhenFeatureFlag attribute works!\n";
echo "✅  Features only shown when flags are enabled\n\n";

// Example 4: Custom WhenRole Attribute (Generic)
echo "4. CUSTOM WHENROLE ATTRIBUTE (GENERIC):\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenRole implements ConditionalProperty
{
    /** @phpstan-ignore-next-line unknown */
    private readonly array $roles;

    /** @param array<mixed> $roles */
    public function __construct(string|array $roles)
    {
        $this->roles = is_array($roles) ? $roles : [$roles];
    }

    /** @param array<mixed> $context */
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $user = $context['user'] ?? null;

        if (!$user) {
            return false;
        }

        // Check if user has getRoles() method
        if (method_exists($user, 'getRoles')) {
            /** @phpstan-ignore-next-line unknown */
            $userRoles = $user->getRoles();
            foreach ($this->roles as $role) {
                if (in_array($role, $userRoles, true)) {
                    return true;
                }
            }
            return false;
        }

        // Check if user has roles property
        /** @phpstan-ignore-next-line unknown */
        if (isset($user->roles)) {
            $userRoles = is_array($user->roles) ? $user->roles : [$user->roles];
            foreach ($this->roles as $role) {
                if (in_array($role, $userRoles, true)) {
                    return true;
                }
            }
            return false;
        }

        // Check if user has role property
        /** @phpstan-ignore-next-line unknown */
        if (isset($user->role)) {
            return in_array($user->role, $this->roles, true);
        }

        return false;
    }
}

class DashboardDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $admin_panel
     * @param array<mixed>|null $moderation_tools
     */
    /**
     * @param array<mixed> $widgets
     * @param array<mixed> $admin_panel
     * @param array<mixed> $moderation_tools
     */
    public function __construct(
        public readonly string $title,
        public readonly array $widgets,

        #[WhenRole('admin')]
        public readonly ?array $admin_panel = null,

        #[WhenRole(['admin', 'moderator'])]
        public readonly ?array $moderation_tools = null,
    ) {}
}

// Mock user objects
$adminUser = (object)['name' => 'Admin', 'role' => 'admin'];
$moderatorUser = (object)['name' => 'Moderator', 'role' => 'moderator'];
$regularUser = (object)['name' => 'User', 'role' => 'user'];

$dashboard = new DashboardDto(
    'Dashboard',
    ['widget1', 'widget2'],
    ['users', 'settings'],
    ['reports', 'bans']
);

echo "Regular user:\n";
echo json_encode($dashboard->withContext(['user' => $regularUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nModerator user:\n";
echo json_encode($dashboard->withContext(['user' => $moderatorUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\nAdmin user:\n";
echo json_encode($dashboard->withContext(['user' => $adminUser])->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n✅  Custom WhenRole attribute works!\n";
echo "✅  Admin panel only for admins\n";
echo "✅  Moderation tools for admins and moderators\n\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              SUMMARY                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅  Custom conditional attributes are easy to create\n";
echo "✅  Implement ConditionalProperty interface\n";
echo "✅  Use shouldInclude() method for logic\n";
echo "✅  Access context via \$context parameter\n";
echo "✅  Combine with existing conditional attributes\n";
echo "✅  Framework-agnostic and flexible\n\n";

echo "Examples created:\n";
echo "  1. WhenPremium - Premium user features\n";
echo "  2. WhenEnvironment - Environment-specific data\n";
echo "  3. WhenFeatureFlag - Feature flag support\n";
echo "  4. WhenRole - Generic role-based access\n\n";

echo "All examples demonstrate the power and flexibility of the\n";
echo "conditional properties system in SimpleDto!\n";
