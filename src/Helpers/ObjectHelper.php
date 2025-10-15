<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Helpers;

use ReflectionClass;

/**
 * Helper class for object operations.
 *
 * Provides utilities for copying objects with deep cloning support.
 */
final class ObjectHelper
{
    /**
     * Create a copy of an object.
     *
     * @param object $object The object to copy
     * @param bool $recursive Whether to recursively copy nested objects and arrays (default: true)
     * @param int $maxLevel Maximum recursion depth (default: 10)
     * @return object A copy of the object
     */
    public static function copy(object $object, bool $recursive = true, int $maxLevel = 10): object
    {
        return self::copyLevel($object, $recursive, $maxLevel, 1);
    }

    /**
     * Internal recursive copy method.
     *
     * @param object $object The object to copy
     * @param bool $recursive Whether to recursively copy nested objects and arrays
     * @param int $maxLevel Maximum recursion depth
     * @param int $currentLevel Current recursion level (starts at 1)
     * @return object A copy of the object
     */
    private static function copyLevel(object $object, bool $recursive, int $maxLevel, int $currentLevel): object
    {
        // Create a shallow clone first
        $copy = clone $object;

        // If not recursive or max level reached, return shallow clone
        if (!$recursive || $currentLevel > $maxLevel) {
            return $copy;
        }

        // Use reflection to access all properties (including private/protected)
        $reflection = new ReflectionClass($object);

        // Get all declared properties (including inherited ones)
        $properties = [];
        $currentClass = $reflection;

        while ($currentClass) {
            foreach ($currentClass->getProperties() as $property) {
                $propertyName = $property->getName();
                // Avoid duplicates from inheritance
                if (!isset($properties[$propertyName])) {
                    $properties[$propertyName] = $property;
                }
            }
            $currentClass = $currentClass->getParentClass();
        }

        // Check if we have readonly properties that need deep copying
        $hasReadonlyWithObjects = false;
        foreach ($properties as $property) {
            if ($property->isReadOnly() && $property->isInitialized($copy)) {
                $value = $property->getValue($copy);
                if (is_object($value) || (is_array($value) && self::containsObjects($value))) {
                    $hasReadonlyWithObjects = true;
                    break;
                }
            }
        }

        // If we have readonly properties with objects, we need to recreate the object
        if ($hasReadonlyWithObjects) {
            return self::recreateObjectWithCopiedValues(
                $object,
                $reflection,
                $properties,
                $recursive,
                $maxLevel,
                $currentLevel
            );
        }

        // Deep copy each declared property
        foreach ($properties as $property) {
            if (!$property->isInitialized($copy)) {
                continue;
            }

            // Skip readonly properties (they are already copied by clone)
            if ($property->isReadOnly()) {
                continue;
            }

            $value = $property->getValue($copy);

            // Deep copy the value (increment level for nested objects)
            $copiedValue = self::copyValue($value, $recursive, $maxLevel, $currentLevel);

            $property->setValue($copy, $copiedValue);
        }

        // Handle dynamic properties (e.g., stdClass)
        $objectVars = get_object_vars($copy);
        foreach ($objectVars as $propertyName => $value) {
            // Skip if already handled as declared property
            if (isset($properties[$propertyName])) {
                continue;
            }

            // Deep copy the dynamic property value
            $copy->$propertyName = self::copyValue($value, $recursive, $maxLevel, $currentLevel);
        }

        return $copy;
    }

    /** Check if an array contains objects. */
    private static function containsObjects(array $array): bool
    {
        foreach ($array as $item) {
            if (is_object($item)) {
                return true;
            }
            if (is_array($item) && self::containsObjects($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Recreate an object with deep-copied values for readonly properties.
     *
     * @param array<string, ReflectionProperty> $properties
     */
    private static function recreateObjectWithCopiedValues(
        object $object,
        ReflectionClass $reflection,
        array $properties,
        bool $recursive,
        int $maxLevel,
        int $currentLevel,
    ): object {
        // Create new instance without calling constructor
        $copy = $reflection->newInstanceWithoutConstructor();

        // Copy all properties with deep copying
        foreach ($properties as $property) {
            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);

            // Deep copy the value
            $copiedValue = self::copyValue($value, $recursive, $maxLevel, $currentLevel);

            // Set the value (works even for readonly properties before initialization)
            $property->setValue($copy, $copiedValue);
        }

        return $copy;
    }

    /**
     * Copy a value (handles objects, arrays, and primitives).
     *
     * @param mixed $value The value to copy
     * @param bool $recursive Whether to recursively copy nested objects and arrays
     * @param int $maxLevel Maximum recursion depth
     * @param int $currentLevel Current recursion level
     * @return mixed A copy of the value
     */
    private static function copyValue(mixed $value, bool $recursive, int $maxLevel, int $currentLevel): mixed
    {
        // Handle objects (increment level for nested objects)
        if (is_object($value)) {
            return self::copyLevel($value, $recursive, $maxLevel, $currentLevel + 1);
        }

        // Handle arrays
        if (is_array($value)) {
            return self::copyArray($value, $recursive, $maxLevel, $currentLevel);
        }

        // Primitives (int, string, bool, float, null, resource) are copied by value
        return $value;
    }

    /**
     * Deep copy an array.
     *
     * @param array<array-key, mixed> $array The array to copy
     * @param bool $recursive Whether to recursively copy nested objects and arrays
     * @param int $maxLevel Maximum recursion depth
     * @param int $currentLevel Current recursion level
     * @return array<array-key, mixed> A copy of the array
     */
    private static function copyArray(array $array, bool $recursive, int $maxLevel, int $currentLevel): array
    {
        $copy = [];

        foreach ($array as $key => $value) {
            $copy[$key] = self::copyValue($value, $recursive, $maxLevel, $currentLevel);
        }

        return $copy;
    }
}

