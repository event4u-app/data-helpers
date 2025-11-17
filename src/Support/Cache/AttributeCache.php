<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use event4u\DataHelpers\Support\ReflectionCache;
use ReflectionClass;
use ReflectionProperty;

/**
 * Cache for attribute metadata per class and property.
 *
 * Attribute reflection is expensive. This cache stores attribute instances
 * to avoid repeated ReflectionClass::getAttributes() calls.
 *
 * Phase 3 Optimization: Centralized attribute metadata cache
 * - Caches class-level attributes
 * - Caches property-level attributes
 * - Caches attribute instances (not just names)
 * - Statistics tracking (hit/miss ratio)
 * - Integrates with ReflectionCache
 *
 * Performance Impact:
 * - Eliminates repeated getAttributes() calls
 * - Eliminates repeated newInstance() calls
 * - Expected: 25-35% faster attribute operations
 */
final class AttributeCache
{
    /**
     * Cache for class-level attributes.
     *
     * Format: ['ClassName' => ['AttributeClass' => AttributeInstance]]
     *
     * @var array<class-string, array<class-string, object>>
     */
    private static array $classAttributesCache = [];

    /**
     * Cache for property-level attributes.
     *
     * Format: ['ClassName' => ['propertyName' => ['AttributeClass' => AttributeInstance]]]
     *
     * @var array<class-string, array<string, array<class-string, object>>>
     */
    private static array $propertyAttributesCache = [];

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
     * Get all attributes for a class (cached).
     *
     * @param class-string|object $classOrObject The class name or object instance
     * @return array<class-string, object> Attribute instances keyed by attribute class
     */
    public static function getClassAttributes(string|object $classOrObject): array
    {
        $className = is_object($classOrObject) ? $classOrObject::class : $classOrObject;

        // Check cache first
        if (isset(self::$classAttributesCache[$className])) {
            self::$stats['hits']++;

            return self::$classAttributesCache[$className];
        }

        self::$stats['misses']++;

        // Get reflection class (uses ReflectionCache)
        $reflection = ReflectionCache::getClass($classOrObject);

        // Extract all attributes
        $attributes = [];
        foreach ($reflection->getAttributes() as $attribute) {
            $attributeClass = $attribute->getName();
            $attributes[$attributeClass] = $attribute->newInstance();
        }

        // Cache and return
        self::$classAttributesCache[$className] = $attributes;
        self::updateSize();

        return $attributes;
    }

    /**
     * Get a specific attribute for a class (cached).
     *
     * @template T of object
     * @param class-string|object $classOrObject The class name or object instance
     * @param class-string<T> $attributeClass The attribute class to find
     * @return T|null The attribute instance or null if not found
     */
    public static function getClassAttribute(string|object $classOrObject, string $attributeClass): ?object
    {
        $attributes = self::getClassAttributes($classOrObject);

        /** @var T|null */
        return $attributes[$attributeClass] ?? null;
    }

    /**
     * Check if a class has a specific attribute (cached).
     *
     * @param class-string|object $classOrObject The class name or object instance
     * @param class-string $attributeClass The attribute class to check
     */
    public static function hasClassAttribute(string|object $classOrObject, string $attributeClass): bool
    {
        $attributes = self::getClassAttributes($classOrObject);

        return isset($attributes[$attributeClass]);
    }

    /**
     * Get all attributes for a property (cached).
     *
     * @param class-string|object $classOrObject The class name or object instance
     * @param string $propertyName The property name
     * @return array<class-string, object> Attribute instances keyed by attribute class
     */
    public static function getPropertyAttributes(string|object $classOrObject, string $propertyName): array
    {
        $className = is_object($classOrObject) ? $classOrObject::class : $classOrObject;

        // Check cache first
        if (isset(self::$propertyAttributesCache[$className][$propertyName])) {
            self::$stats['hits']++;

            return self::$propertyAttributesCache[$className][$propertyName];
        }

        self::$stats['misses']++;

        // Get reflection class (uses ReflectionCache)
        $reflection = ReflectionCache::getClass($classOrObject);

        // Check if property exists
        if (!$reflection->hasProperty($propertyName)) {
            // Cache empty result
            self::$propertyAttributesCache[$className][$propertyName] = [];
            self::updateSize();

            return [];
        }

        // Get property reflection
        $property = $reflection->getProperty($propertyName);

        // Extract all attributes
        $attributes = [];
        foreach ($property->getAttributes() as $attribute) {
            $attributeClass = $attribute->getName();
            $attributes[$attributeClass] = $attribute->newInstance();
        }

        // Cache and return
        if (!isset(self::$propertyAttributesCache[$className])) {
            self::$propertyAttributesCache[$className] = [];
        }
        self::$propertyAttributesCache[$className][$propertyName] = $attributes;
        self::updateSize();

        return $attributes;
    }

    /**
     * Get a specific attribute for a property (cached).
     *
     * @template T of object
     * @param class-string|object $classOrObject The class name or object instance
     * @param string $propertyName The property name
     * @param class-string<T> $attributeClass The attribute class to find
     * @return T|null The attribute instance or null if not found
     */
    public static function getPropertyAttribute(
        string|object $classOrObject,
        string $propertyName,
        string $attributeClass
    ): ?object {
        $attributes = self::getPropertyAttributes($classOrObject, $propertyName);

        /** @var T|null */
        return $attributes[$attributeClass] ?? null;
    }

    /**
     * Check if a property has a specific attribute (cached).
     *
     * @param class-string|object $classOrObject The class name or object instance
     * @param string $propertyName The property name
     * @param class-string $attributeClass The attribute class to check
     */
    public static function hasPropertyAttribute(
        string|object $classOrObject,
        string $propertyName,
        string $attributeClass
    ): bool {
        $attributes = self::getPropertyAttributes($classOrObject, $propertyName);

        return isset($attributes[$attributeClass]);
    }

    /**
     * Warm the cache with commonly used classes.
     *
     * @param array<int, class-string> $classes List of class names to pre-cache
     */
    public static function warm(array $classes): void
    {
        foreach ($classes as $className) {
            // Cache class attributes
            self::getClassAttributes($className);

            // Cache property attributes
            $reflection = new ReflectionClass($className);
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                self::getPropertyAttributes($className, $property->getName());
            }
        }
    }

    /**
     * Clear all caches.
     */
    public static function clear(): void
    {
        self::$classAttributesCache = [];
        self::$propertyAttributesCache = [];
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
     * Update cache size statistic.
     */
    private static function updateSize(): void
    {
        $propertyCount = 0;
        foreach (self::$propertyAttributesCache as $properties) {
            $propertyCount += count($properties);
        }

        self::$stats['size'] = count(self::$classAttributesCache) + $propertyCount;

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

        self::$classAttributesCache = array_slice(self::$classAttributesCache, $removeCount, null, true);
        self::$propertyAttributesCache = array_slice(self::$propertyAttributesCache, $removeCount, null, true);

        self::updateSize();
    }
}

