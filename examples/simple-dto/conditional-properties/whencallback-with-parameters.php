<?php

declare(strict_types=1);

/**
 * WhenCallback with Parameters Example
 *
 * This example demonstrates the new WhenCallback syntax that supports:
 * - String references to global functions or static methods
 * - Positional parameters
 * - Named parameters
 * - Works in PHP attributes (no closure limitation!)
 */

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\WhenCallback;

// Global helper functions
/**
 * @param object{age: int} $dto
 * @param array<string, mixed> $context
 */
function hasMinimumAge(object $dto, mixed $value, array $context, int $minAge): bool
{
    return $dto->age >= $minAge;
}

/**
 * @param array<string, mixed> $context
 */
function hasPermission(object $dto, mixed $value, array $context, string $permission): bool
{
    return in_array($permission, $context['permissions'] ?? []);
}

/**
 * @param array<string, mixed> $context
 */
function hasRole(object $dto, mixed $value, array $context, string $role, bool $strict = false): bool
{
    if ($strict) {
        return ($context['role'] ?? null) === $role;
    }

    return in_array($role, $context['roles'] ?? []);
}

// Dto with WhenCallback using various syntaxes
class UserDto extends SimpleDto
{
    /**
     * @param array<mixed>|null $premiumFeatures
     * @param array<mixed>|null $adminData
     * @param array<mixed>|null $reports
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $role,

        // 1. Global function with positional parameter
        #[WhenCallback('hasMinimumAge', [18])]
        public readonly ?string $adultContent = null,

        // 2. Global function with positional parameter (higher age)
        #[WhenCallback('hasMinimumAge', [21])]
        public readonly ?string $alcoholContent = null,

        // 3. Static method with positional parameter
        #[WhenCallback('static::checkSubscription', ['premium'])]
        public readonly ?array $premiumFeatures = null,

        // 4. Static method with named parameters
        #[WhenCallback('static::hasAccessLevel', ['level' => 'admin', 'strict' => true])]
        public readonly ?array $adminData = null,

        // 5. Context-based with positional parameter
        #[WhenCallback('hasPermission', ['view_reports'])]
        public readonly ?array $reports = null,

        // 6. Context-based with named parameters
        #[WhenCallback('hasRole', ['role' => 'editor', 'strict' => false])]
        public readonly ?string $editorTools = null,
    ) {}

    /** @param array<string, mixed> $context */
    public static function checkSubscription(object $dto, mixed $value, array $context, string $tier): bool
    {
        return ($context['subscription'] ?? null) === $tier;
    }

    /** @param array<string, mixed> $context */
    public static function hasAccessLevel(
        object $dto,
        mixed $value,
        array $context,
        string $level,
        bool $strict = false
    ): bool
    {
        if ($strict) {
            return ($context['access_level'] ?? null) === $level;
        }

        return in_array($level, $context['access_levels'] ?? []);
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  WhenCallback with Parameters\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Example 1: Young user (age 16)
echo "1️⃣  Young User (age 16)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$youngUser = new UserDto(
    name: 'Alice',
    age: 16,
    role: 'user',
    adultContent: 'Adult movies',
    alcoholContent: 'Beer ads',
    premiumFeatures: ['feature1', 'feature2'],
    adminData: ['sensitive' => 'data'],
    reports: ['report1'],
    editorTools: 'Editor panel',
);

$array = $youngUser->toArray();
echo "Properties included:\n";
foreach (array_keys($array) as $key) {
    echo sprintf('  • %s%s', $key, PHP_EOL);
}
echo "\n✅  Age-restricted content excluded (age < 18)\n\n";

// Example 2: Adult user (age 25)
echo "2️⃣  Adult User (age 25)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$adultUser = new UserDto(
    name: 'Bob',
    age: 25,
    role: 'user',
    adultContent: 'Adult movies',
    alcoholContent: 'Beer ads',
    premiumFeatures: ['feature1', 'feature2'],
    adminData: ['sensitive' => 'data'],
    reports: ['report1'],
    editorTools: 'Editor panel',
);

$array = $adultUser->toArray();
echo "Properties included:\n";
foreach (array_keys($array) as $key) {
    echo sprintf('  • %s%s', $key, PHP_EOL);
}
echo "\n✅  Adult content included (age >= 18)\n";
echo "✅  Alcohol content included (age >= 21)\n\n";

// Example 3: Premium user with context
echo "3️⃣  Premium User with Context\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$premiumUser = new UserDto(
    name: 'Charlie',
    age: 30,
    role: 'premium',
    adultContent: 'Adult movies',
    alcoholContent: 'Beer ads',
    premiumFeatures: ['advanced_analytics', 'priority_support'],
    adminData: ['sensitive' => 'data'],
    reports: ['monthly_report', 'quarterly_report'],
    editorTools: 'Editor panel',
);

$context = [
    'subscription' => 'premium',
    'permissions' => ['view_reports', 'edit_content'],
    'roles' => ['editor', 'contributor'],
];

$array = $premiumUser->withContext($context)->toArray();
echo "Properties included:\n";
foreach (array_keys($array) as $key) {
    echo sprintf('  • %s%s', $key, PHP_EOL);
}
echo "\n✅  Premium features included (subscription = premium)\n";
echo "✅  Reports included (has permission)\n";
echo "✅  Editor tools included (has editor role)\n\n";

// Example 4: Admin user with strict access level
echo "4️⃣  Admin User with Strict Access Level\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$adminUser = new UserDto(
    name: 'Diana',
    age: 35,
    role: 'admin',
    adultContent: 'Adult movies',
    alcoholContent: 'Beer ads',
    premiumFeatures: ['feature1'],
    adminData: ['users' => 1000, 'revenue' => 50000],
    reports: ['report1'],
    editorTools: 'Editor panel',
);

$adminContext = [
    'subscription' => 'premium',
    'access_level' => 'admin',
    'permissions' => ['view_reports', 'manage_users'],
    'roles' => ['admin', 'editor'],
];

$array = $adminUser->withContext($adminContext)->toArray();
echo "Properties included:\n";
foreach (array_keys($array) as $key) {
    echo sprintf('  • %s%s', $key, PHP_EOL);
}
echo "\n✅  Admin data included (access_level = admin, strict mode)\n";
echo "✅  All age-restricted content included\n\n";

// Example 5: Regular user without special permissions
echo "5️⃣  Regular User (no special permissions)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$regularUser = new UserDto(
    name: 'Eve',
    age: 28,
    role: 'user',
    adultContent: 'Adult movies',
    alcoholContent: 'Beer ads',
    premiumFeatures: ['feature1'],
    adminData: ['sensitive' => 'data'],
    reports: ['report1'],
    editorTools: 'Editor panel',
);

$regularContext = [
    'subscription' => 'free',
    'permissions' => ['view_profile'],
    'roles' => ['user'],
];

$array = $regularUser->withContext($regularContext)->toArray();
echo "Properties included:\n";
foreach (array_keys($array) as $key) {
    echo sprintf('  • %s%s', $key, PHP_EOL);
}
echo "\n✅  Only basic properties and age-appropriate content included\n";
echo "❌  Premium features excluded (not premium subscriber)\n";
echo "❌  Admin data excluded (not admin)\n";
echo "❌  Reports excluded (no permission)\n";
echo "❌  Editor tools excluded (not editor)\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Summary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "✅  WhenCallback now supports string references (works in attributes!)\n";
echo "✅  Positional parameters: #[WhenCallback('func', [param1, param2])]\n";
echo "✅  Named parameters: #[WhenCallback('func', ['key' => value])]\n";
echo "✅  Static methods: #[WhenCallback('static::method', [params])]\n";
echo "✅  Context-based conditions with custom parameters\n";
echo "✅  No more PHP closure limitation in attributes!\n\n";
