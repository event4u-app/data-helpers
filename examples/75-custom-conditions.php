<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Attribute;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;

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
    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        // Check if user is premium from context
        $user = $context['user'] ?? null;

        if ($user && method_exists($user, 'isPremium')) {
            return $user->isPremium();
        }

        // Check if user has premium property
        if ($user && isset($user->premium)) {
            return (bool)$user->premium;
        }

        // Check context directly
        return true === ($context['is_premium'] ?? false);
    }
}

class ProductDTO extends SimpleDTO
{
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
$product = new ProductDTO(
    'Premium Product',
    99.99,
    10.00,
    ['feature1', 'feature2']
);

echo "Without premium context:\n";
print_r($product->toArray());

echo "\nWith premium context:\n";
print_r($product->withContext(['is_premium' => true])->toArray());

echo "\n✅  Custom WhenPremium attribute works!\n";
echo "✅  Premium features only shown to premium users\n\n";

// Example 2: Custom WhenEnvironment Attribute
echo "2. CUSTOM WHENENVIRONMENT ATTRIBUTE:\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenEnvironment implements ConditionalProperty
{
    public function __construct(
        private readonly string|array $environments,
    ) {}

    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $currentEnv = $context['environment'] ?? 'production';

        $allowedEnvs = is_array($this->environments)
            ? $this->environments
            : [$this->environments];

        return in_array($currentEnv, $allowedEnvs, true);
    }
}

class ApiResponseDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $status,
        public readonly mixed $data,

        #[WhenEnvironment(['development', 'staging'])]
        public readonly ?array $debug_info = null,

        #[WhenEnvironment('development')]
        public readonly ?array $sql_queries = null,
    ) {}
}

$response = new ApiResponseDTO(
    'success',
    ['user' => 'John'],
    ['memory' => '2MB', 'time' => '150ms'],
    ['SELECT * FROM users']
);

echo "Production environment:\n";
print_r($response->withContext(['environment' => 'production'])->toArray());

echo "\nDevelopment environment:\n";
print_r($response->withContext(['environment' => 'development'])->toArray());

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

    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $features = $context['features'] ?? [];

        return in_array($this->flag, $features, true)
            || true === ($features[$this->flag] ?? false);
    }
}

class UserProfileDTO extends SimpleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,

        #[WhenFeatureFlag('new_profile_design')]
        public readonly ?array $profile_v2 = null,

        #[WhenFeatureFlag('social_features')]
        public readonly ?array $social_links = null,
    ) {}
}

$profile = new UserProfileDTO(
    'John Doe',
    'john@example.com',
    ['theme' => 'dark', 'layout' => 'modern'],
    ['twitter' => '@john', 'github' => 'john']
);

echo "Without feature flags:\n";
print_r($profile->toArray());

echo "\nWith new_profile_design flag:\n";
print_r($profile->withContext(['features' => ['new_profile_design']])->toArray());

echo "\nWith all feature flags:\n";
print_r($profile->withContext([
    'features' => ['new_profile_design', 'social_features'],
])->toArray());

echo "\n✅  Custom WhenFeatureFlag attribute works!\n";
echo "✅  Features only shown when flags are enabled\n\n";

// Example 4: Custom WhenRole Attribute (Generic)
echo "4. CUSTOM WHENROLE ATTRIBUTE (GENERIC):\n";
echo "------------------------------------------------------------\n";

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class WhenRole implements ConditionalProperty
{
    private readonly array $roles;

    public function __construct(string|array $roles)
    {
        $this->roles = is_array($roles) ? $roles : [$roles];
    }

    public function shouldInclude(mixed $value, object $dto, array $context = []): bool
    {
        $user = $context['user'] ?? null;

        if (!$user) {
            return false;
        }

        // Check if user has getRoles() method
        if (method_exists($user, 'getRoles')) {
            $userRoles = $user->getRoles();
            foreach ($this->roles as $role) {
                if (in_array($role, $userRoles, true)) {
                    return true;
                }
            }
            return false;
        }

        // Check if user has roles property
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
        if (isset($user->role)) {
            return in_array($user->role, $this->roles, true);
        }

        return false;
    }
}

class DashboardDTO extends SimpleDTO
{
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

$dashboard = new DashboardDTO(
    'Dashboard',
    ['widget1', 'widget2'],
    ['users', 'settings'],
    ['reports', 'bans']
);

echo "Regular user:\n";
print_r($dashboard->withContext(['user' => $regularUser])->toArray());

echo "\nModerator user:\n";
print_r($dashboard->withContext(['user' => $moderatorUser])->toArray());

echo "\nAdmin user:\n";
print_r($dashboard->withContext(['user' => $adminUser])->toArray());

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
echo "conditional properties system in SimpleDTO!\n";

