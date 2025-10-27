<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\Support\ReflectionCache;

echo "=== Optimized Reflection Caching ===\n\n";

// Example 1: ReflectionClass Caching
echo "1. ReflectionClass Caching\n";
echo "-------------------------\n";

class UserDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        public readonly int $age,
        public readonly string $email,
    ) {}

    #[Computed]
    public function displayName(): string
    {
        return strtoupper($this->name);
    }
}

// First call - creates ReflectionClass
$ref1 = ReflectionCache::getClass(UserDto::class);
echo "First call: " . $ref1->getName() . "\n";

// Second call - uses cached ReflectionClass
$ref2 = ReflectionCache::getClass(UserDto::class);
echo "Second call: " . $ref2->getName() . "\n";
echo "Same instance: " . ($ref1 === $ref2 ? 'Yes' : 'No') . "\n\n";

// Example 2: Property Caching
echo "2. Property Caching\n";
echo "------------------\n";

$user = UserDto::fromArray(['user_name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com']);

$properties = ReflectionCache::getProperties($user);
echo "Properties found: " . count($properties) . "\n";
foreach (array_keys($properties) as $name) {
    if (!str_starts_with($name, '__') && !in_array(
        $name,
        [
            'onlyProperties',
            'exceptProperties',
            'visibilityContext',
            'computedCache',
            'includedComputed',
            'includedLazy',
            'includeAllLazy',
            'wrapKey',
            'objectVarsCache',
            'castedProperties',
        ]
    )) {
        echo sprintf('  - %s%s', $name, PHP_EOL);
    }
}
echo "\n";

// Example 3: Method Caching
echo "3. Method Caching\n";
echo "----------------\n";

$methods = ReflectionCache::getMethods($user);
echo "Public methods found: " . count($methods) . "\n";
$displayedCount = 0;
foreach (array_keys($methods) as $name) {
    if (!str_starts_with($name, '__') && 5 > $displayedCount) {
        echo sprintf('  - %s%s', $name, PHP_EOL);
        $displayedCount++;
    }
}
echo "\n";

// Example 4: Attribute Caching
echo "4. Attribute Caching\n";
echo "-------------------\n";

$nameAttrs = ReflectionCache::getPropertyAttributes($user, 'name');
echo "Attributes on 'name' property: " . count($nameAttrs) . "\n";
foreach (array_keys($nameAttrs) as $attrName) {
    echo "  - " . basename(str_replace('\\', '/', (string)$attrName)) . "\n";
}

$methodAttrs = ReflectionCache::getMethodAttributes($user, 'displayName');
echo "Attributes on 'displayName' method: " . count($methodAttrs) . "\n";
foreach (array_keys($methodAttrs) as $attrName) {
    echo "  - " . basename(str_replace('\\', '/', (string)$attrName)) . "\n";
}
echo "\n";

// Example 5: Cache Statistics
echo "5. Cache Statistics\n";
echo "------------------\n";

$stats = ReflectionCache::getStats();
echo sprintf('Classes cached: %d%s', $stats['classes'], PHP_EOL);
echo sprintf('Properties cached: %d%s', $stats['properties'], PHP_EOL);
echo sprintf('Methods cached: %d%s', $stats['methods'], PHP_EOL);
echo sprintf('Property attributes cached: %d%s', $stats['propertyAttributes'], PHP_EOL);
echo sprintf('Method attributes cached: %d%s', $stats['methodAttributes'], PHP_EOL);
echo sprintf('Class attributes cached: %d%s', $stats['classAttributes'], PHP_EOL);
echo "Estimated memory: " . number_format($stats['estimatedMemory']) . " bytes\n\n";

// Example 6: Performance Comparison
echo "6. Performance Comparison\n";
echo "------------------------\n";

// Clear cache
ReflectionCache::clear();

// Without cache (first call)
$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    $ref = ReflectionCache::getClass(UserDto::class);
    $props = ReflectionCache::getProperties(UserDto::class);
}
$firstRun = microtime(true) - $start;

// With cache (subsequent calls)
$start = microtime(true);
for ($i = 0; 1000 > $i; $i++) {
    $ref = ReflectionCache::getClass(UserDto::class);
    $props = ReflectionCache::getProperties(UserDto::class);
}
$cachedRun = microtime(true) - $start;

echo "1000 iterations (first run): " . number_format($firstRun * 1000, 2) . " ms\n";
echo "1000 iterations (cached): " . number_format($cachedRun * 1000, 2) . " ms\n";
echo "Speedup: " . number_format($firstRun / $cachedRun, 2) . "x\n\n";

// Example 7: Multiple Dtos
echo "7. Multiple Dtos\n";
echo "---------------\n";

class AddressDto extends SimpleDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class OrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $total,
        public readonly string $status,
    ) {}
}

// Cache all Dtos
ReflectionCache::getClass(UserDto::class);
ReflectionCache::getClass(AddressDto::class);
ReflectionCache::getClass(OrderDto::class);

$stats = ReflectionCache::getStats();
echo sprintf('Total classes cached: %d%s', $stats['classes'], PHP_EOL);
echo "Total estimated memory: " . number_format($stats['estimatedMemory']) . " bytes\n\n";

// Example 8: Cache Clearing
echo "8. Cache Clearing\n";
echo "----------------\n";

$statsBefore = ReflectionCache::getStats();
echo "Before clearing:\n";
echo sprintf('  Classes: %d%s', $statsBefore['classes'], PHP_EOL);
echo sprintf('  Properties: %d%s', $statsBefore['properties'], PHP_EOL);

ReflectionCache::clear();

$statsAfter = ReflectionCache::getStats();
echo "After clearing:\n";
echo sprintf('  Classes: %d%s', $statsAfter['classes'], PHP_EOL);
echo "  Properties: {$statsAfter['properties']}\n\n";

// Example 9: Selective Cache Clearing
echo "9. Selective Cache Clearing\n";
echo "--------------------------\n";

// Rebuild cache
ReflectionCache::getClass(UserDto::class);
ReflectionCache::getClass(AddressDto::class);
ReflectionCache::getClass(OrderDto::class);

$statsBefore = ReflectionCache::getStats();
echo "Before clearing UserDto:\n";
echo sprintf('  Classes: %d%s', $statsBefore['classes'], PHP_EOL);

ReflectionCache::clearClass(UserDto::class);

$statsAfter = ReflectionCache::getStats();
echo "After clearing UserDto:\n";
echo "  Classes: {$statsAfter['classes']}\n\n";

// Example 10: Real-World Usage
echo "10. Real-World Usage\n";
echo "-------------------\n";

$start = microtime(true);

// Create 1000 Dto instances
$users = [];
for ($i = 0; 1000 > $i; $i++) {
    $users[] = UserDto::fromArray([
        'user_name' => 'User ' . $i,
        'age' => 20 + ($i % 50),
        'email' => sprintf('user%d@example.com', $i),
    ]);
}

$duration = microtime(true) - $start;

echo "Created 1000 Dto instances in: " . number_format($duration * 1000, 2) . " ms\n";
echo "Average per instance: " . number_format(($duration / 1000) * 1000, 2) . " ms\n";
echo "Throughput: " . number_format(1000 / $duration) . " instances/second\n\n";

$finalStats = ReflectionCache::getStats();
echo "Final cache statistics:\n";
echo sprintf('  Classes: %d%s', $finalStats['classes'], PHP_EOL);
echo sprintf('  Properties: %d%s', $finalStats['properties'], PHP_EOL);
echo sprintf('  Methods: %d%s', $finalStats['methods'], PHP_EOL);
echo "  Estimated memory: " . number_format($finalStats['estimatedMemory']) . " bytes\n\n";

echo "=== Optimized Reflection Complete ===\n";
