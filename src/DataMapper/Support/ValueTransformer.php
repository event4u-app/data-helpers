<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use ReflectionClass;
use ReflectionException;

/**
 * Handles value transformations like replacement, trimming, and case conversion.
 */
class ValueTransformer
{
    /**
     * Apply replacement map to a value.
     *
     * - Keys supported: string and int (common PHP array keys)
     * - Order: apply on the already transformed value, before hooks like postTransform
     *
     * @param array<int|string, mixed> $replaceMap
     */
    public static function applyReplacement(mixed $value, array $replaceMap, bool $caseInsensitive = false): mixed
    {
        // Only handle scalar or null values; leave arrays/objects untouched
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        // Null: check if there's a null key in the map
        if (null === $value) {
            return $replaceMap[null] ?? $value;
        }

        // For case-insensitive, build a lowercase map
        if ($caseInsensitive && is_string($value)) {
            $lowerMap = [];
            foreach ($replaceMap as $k => $v) {
                if (is_string($k)) {
                    $lowerMap[strtolower($k)] = $v;
                }
            }
            $lowerValue = strtolower($value);

            return $lowerMap[$lowerValue] ?? $value;
        }

        // Direct lookup
        if (is_int($value) || is_string($value)) {
            return $replaceMap[$value] ?? $value;
        }

        return $value;
    }

    /** Convert snake_case or kebab-case to camelCase. */
    public static function toCamelCase(string $name): string
    {
        // Cache results for better performance
        static $cache = [];

        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $result = $name;
        $result = str_replace(['-', '_'], ' ', $result);
        $result = ucwords($result);
        $result = str_replace(' ', '', $result);
        $result = lcfirst($result);

        $cache[$name] = $result;
        return $result;
    }

    /** Check if an object has a property (public or private). */
    public static function objectHasProperty(object $object, string $property): bool
    {
        // Fast path: public prop exists
        if (property_exists($object, $property)) {
            return true;
        }

        // Check private/protected via reflection
        try {
            $reflection = new ReflectionClass($object);

            return $reflection->hasProperty($property);
        } catch (ReflectionException) {
            return false;
        }
    }

    /**
     * Process a value through transformation and replacement pipeline.
     *
     * This method applies:
     * 1. Custom transformation function (if provided)
     * 2. Trimming (if enabled and value is string)
     * 3. Replacement mapping (if provided)
     *
     * @param mixed $value The value to process
     * @param null|callable(mixed): mixed $transformFn Optional transformation function
     * @param null|array<int|string, mixed> $replaceMap Optional replacement map
     * @param bool $trimValues Whether to trim string values before replacement
     * @param bool $caseInsensitiveReplace Whether replacement should be case-insensitive
     * @return mixed The processed value
     */
    public static function processValue(
        mixed $value,
        ?callable $transformFn,
        ?array $replaceMap,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false
    ): mixed {
        // Apply custom transformation
        if (is_callable($transformFn)) {
            $value = $transformFn($value);
        }

        // Trim string values if requested (before replacement)
        if ($trimValues && is_string($value)) {
            $value = trim($value);
        }

        // Apply replacement map
        if (is_array($replaceMap)) {
            return self::applyReplacement($value, $replaceMap, $caseInsensitiveReplace);
        }

        return $value;
    }
}
