<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;

/**
 * Cache for validation rules per DTO class.
 *
 * Validation rule extraction from attributes is expensive. This cache stores
 * the extracted rules to avoid repeated reflection and attribute parsing.
 *
 * Phase 3 Optimization: Centralized validation rules cache
 * - Caches validation rules per DTO class
 * - Caches validation rules per property
 * - Integrates with ConstructorMetadata
 * - Statistics tracking (hit/miss ratio)
 *
 * Performance Impact:
 * - Eliminates repeated attribute parsing
 * - Eliminates repeated rule extraction
 * - Expected: 30-40% faster validation operations
 *
 * Note: This complements ConstructorMetadata which already caches attributes.
 * ValidationCache focuses specifically on extracted validation rules.
 */
final class ValidationCache
{
    /**
     * Cache for validation rules per DTO class.
     *
     * Format: ['DtoClass' => ['propertyName' => ['required' => true, 'email' => true, ...]]]
     *
     * @var array<class-string, array<string, array<string, mixed>>>
     */
    private static array $rulesCache = [];

    /**
     * Cache for property-level validation rules.
     *
     * Format: ['DtoClass:propertyName' => ['required' => true, 'email' => true, ...]]
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $propertyRulesCache = [];

    /**
     * Statistics for cache performance monitoring.
     *
     * @var array{hits: int, misses: int, size: int}
     */
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
    ];

    /** Maximum cache size before cleanup (prevent memory leaks) */
    private const MAX_CACHE_SIZE = 500;

    /**
     * Get all validation rules for a DTO class (cached).
     *
     * @param class-string $dtoClass The DTO class name
     * @return array<string, array<string, mixed>> Validation rules per property
     */
    public static function getRules(string $dtoClass): array
    {
        // Check cache first
        if (isset(self::$rulesCache[$dtoClass])) {
            self::$stats['hits']++;

            return self::$rulesCache[$dtoClass];
        }

        self::$stats['misses']++;

        // Extract rules from ConstructorMetadata
        $metadata = ConstructorMetadata::get($dtoClass);
        $rules = [];

        foreach ($metadata['parameters'] as $paramName => $paramData) {
            $propertyRules = self::extractRulesFromAttributes($paramData['attributes']);
            if ([] !== $propertyRules) {
                $rules[$paramName] = $propertyRules;
            }
        }

        // Cache and return
        self::$rulesCache[$dtoClass] = $rules;
        self::updateSize();

        return $rules;
    }

    /**
     * Get validation rules for a specific property (cached).
     *
     * @param class-string $dtoClass The DTO class name
     * @param string $propertyName The property name
     * @return array<string, mixed> Validation rules for the property
     */
    public static function getPropertyRules(string $dtoClass, string $propertyName): array
    {
        $cacheKey = $dtoClass . ':' . $propertyName;

        // Check cache first
        if (isset(self::$propertyRulesCache[$cacheKey])) {
            self::$stats['hits']++;

            return self::$propertyRulesCache[$cacheKey];
        }

        self::$stats['misses']++;

        // Get all rules for the class
        $allRules = self::getRules($dtoClass);

        // Extract rules for this property
        $propertyRules = $allRules[$propertyName] ?? [];

        // Cache and return
        self::$propertyRulesCache[$cacheKey] = $propertyRules;
        self::updateSize();

        return $propertyRules;
    }

    /**
     * Check if a DTO class has any validation rules (cached).
     *
     * @param class-string $dtoClass The DTO class name
     */
    public static function hasRules(string $dtoClass): bool
    {
        $rules = self::getRules($dtoClass);

        return [] !== $rules;
    }

    /**
     * Check if a property has validation rules (cached).
     *
     * @param class-string $dtoClass The DTO class name
     * @param string $propertyName The property name
     */
    public static function hasPropertyRules(string $dtoClass, string $propertyName): bool
    {
        $rules = self::getPropertyRules($dtoClass, $propertyName);

        return [] !== $rules;
    }

    /**
     * Warm the cache with commonly used DTO classes.
     *
     * @param array<int, class-string> $dtoClasses List of DTO class names to pre-cache
     */
    public static function warm(array $dtoClasses): void
    {
        foreach ($dtoClasses as $dtoClass) {
            self::getRules($dtoClass);
        }
    }

    /**
     * Clear all caches.
     */
    public static function clear(): void
    {
        self::$rulesCache = [];
        self::$propertyRulesCache = [];
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
        ];
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, hitRatio: float}
     */
    public static function getStats(): array
    {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRatio = $total > 0 ? self::$stats['hits'] / $total : 0.0;

        return [
            'hits' => self::$stats['hits'],
            'misses' => self::$stats['misses'],
            'size' => self::$stats['size'],
            'hitRatio' => $hitRatio,
        ];
    }

    /**
     * Reset statistics without clearing the cache.
     */
    public static function resetStats(): void
    {
        self::$stats['hits'] = 0;
        self::$stats['misses'] = 0;
        // Keep size as is
    }

    /**
     * Extract validation rules from attribute instances.
     *
     * @param array<string, object> $attributes Attribute instances
     * @return array<string, mixed> Extracted validation rules
     */
    private static function extractRulesFromAttributes(array $attributes): array
    {
        $rules = [];

        // Common validation attribute classes
        $validationAttributes = [
            'event4u\DataHelpers\SimpleDto\Attributes\Required',
            'event4u\DataHelpers\SimpleDto\Attributes\Email',
            'event4u\DataHelpers\SimpleDto\Attributes\Url',
            'event4u\DataHelpers\SimpleDto\Attributes\Min',
            'event4u\DataHelpers\SimpleDto\Attributes\Max',
            'event4u\DataHelpers\SimpleDto\Attributes\Between',
            'event4u\DataHelpers\SimpleDto\Attributes\In',
            'event4u\DataHelpers\SimpleDto\Attributes\NotIn',
            'event4u\DataHelpers\SimpleDto\Attributes\Regex',
            'event4u\DataHelpers\SimpleDto\Attributes\Alpha',
            'event4u\DataHelpers\SimpleDto\Attributes\AlphaNum',
            'event4u\DataHelpers\SimpleDto\Attributes\Numeric',
            'event4u\DataHelpers\SimpleDto\Attributes\Integer',
            'event4u\DataHelpers\SimpleDto\Attributes\String',
            'event4u\DataHelpers\SimpleDto\Attributes\Boolean',
            'event4u\DataHelpers\SimpleDto\Attributes\Array',
            'event4u\DataHelpers\SimpleDto\Attributes\Date',
            'event4u\DataHelpers\SimpleDto\Attributes\DateFormat',
            'event4u\DataHelpers\SimpleDto\Attributes\Before',
            'event4u\DataHelpers\SimpleDto\Attributes\After',
            'event4u\DataHelpers\SimpleDto\Attributes\Confirmed',
            'event4u\DataHelpers\SimpleDto\Attributes\Same',
            'event4u\DataHelpers\SimpleDto\Attributes\Different',
            'event4u\DataHelpers\SimpleDto\Attributes\Accepted',
            'event4u\DataHelpers\SimpleDto\Attributes\Declined',
            'event4u\DataHelpers\SimpleDto\Attributes\Ip',
            'event4u\DataHelpers\SimpleDto\Attributes\Ipv4',
            'event4u\DataHelpers\SimpleDto\Attributes\Ipv6',
            'event4u\DataHelpers\SimpleDto\Attributes\Json',
            'event4u\DataHelpers\SimpleDto\Attributes\Uuid',
        ];

        foreach ($validationAttributes as $attributeClass) {
            if (isset($attributes[$attributeClass])) {
                $attribute = $attributes[$attributeClass];
                $ruleName = strtolower(class_basename($attributeClass));

                // Extract rule parameters if available
                if (method_exists($attribute, 'toArray')) {
                    $rules[$ruleName] = $attribute->toArray();
                } elseif (method_exists($attribute, 'getValue')) {
                    $rules[$ruleName] = $attribute->getValue();
                } else {
                    $rules[$ruleName] = true;
                }
            }
        }

        return $rules;
    }

    /**
     * Update cache size statistic.
     */
    private static function updateSize(): void
    {
        self::$stats['size'] = count(self::$rulesCache) + count(self::$propertyRulesCache);

        // Cleanup if cache is too large
        if (self::$stats['size'] >= self::MAX_CACHE_SIZE) {
            self::cleanup();
        }
    }

    /**
     * Cleanup old entries when cache is too large.
     *
     * Removes oldest 20% of entries (simple LRU approximation).
     */
    private static function cleanup(): void
    {
        $removeCount = (int)(self::MAX_CACHE_SIZE * 0.2 / 2); // Divide by 2 caches

        self::$rulesCache = array_slice(self::$rulesCache, $removeCount, null, true);
        self::$propertyRulesCache = array_slice(self::$propertyRulesCache, $removeCount, null, true);

        self::updateSize();
    }
}

/**
 * Get the class basename (without namespace).
 *
 * @param string $class
 */
function class_basename(string $class): string
{
    $parts = explode('\\', $class);

    return end($parts);
}

