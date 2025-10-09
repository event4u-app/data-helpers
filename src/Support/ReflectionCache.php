<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support;

use ReflectionClass;
use ReflectionProperty;

/**
 * Caches Reflection objects for better performance.
 *
 * Reflection operations are expensive, so we cache ReflectionClass
 * and ReflectionProperty instances to avoid repeated lookups.
 */
final class ReflectionCache
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classes = [];

    /** @var array<class-string, array<string, null|ReflectionProperty>> */
    private static array $properties = [];

    /**
     * Get cached ReflectionClass for an object or class name.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @return ReflectionClass<object>
     */
    public static function getClass(object|string $objectOrClass): ReflectionClass
    {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return self::$classes[$class] ??= new ReflectionClass($class);
    }

    /**
     * Get cached ReflectionProperty for an object's property.
     *
     * Returns null if the property doesn't exist.
     *
     * @param object $object Object instance
     * @param string $name Property name
     * @return ReflectionProperty|null
     */
    public static function getProperty(object $object, string $name): ?ReflectionProperty
    {
        $class = $object::class;

        // Check if we have cached properties for this class
        if (!isset(self::$properties[$class])) {
            self::$properties[$class] = [];
        }

        // Check if we've already looked up this property (including negative lookups)
        if (array_key_exists($name, self::$properties[$class])) {
            return self::$properties[$class][$name];
        }

        // Look up the property
        $refClass = self::getClass($object);
        if (!$refClass->hasProperty($name)) {
            // Cache negative lookup
            self::$properties[$class][$name] = null;

            return null;
        }

        $property = $refClass->getProperty($name);
        self::$properties[$class][$name] = $property;

        return $property;
    }

    /**
     * Check if a class has a specific property.
     *
     * @param object|class-string $objectOrClass Object instance or class name
     * @param string $name Property name
     * @return bool
     */
    public static function hasProperty(object|string $objectOrClass, string $name): bool
    {
        if (is_object($objectOrClass)) {
            return null !== self::getProperty($objectOrClass, $name);
        }

        $refClass = self::getClass($objectOrClass);

        return $refClass->hasProperty($name);
    }

    /**
     * Clear all cached reflection data.
     *
     * Useful for testing or when dealing with dynamic class loading.
     */
    public static function clear(): void
    {
        self::$classes = [];
        self::$properties = [];
    }

    /**
     * Clear cached data for a specific class.
     *
     * @param class-string $class Class name
     */
    public static function clearClass(string $class): void
    {
        unset(self::$classes[$class], self::$properties[$class]);
    }
}

