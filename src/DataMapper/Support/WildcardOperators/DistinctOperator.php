<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

use event4u\DataHelpers\DataAccessor;

/**
 * Removes duplicate items from wildcard arrays based on a field.
 *
 * Similar to SQL DISTINCT.
 */
class DistinctOperator
{
    /**
     * Apply DISTINCT filter to wildcard array.
     *
     * @param array<int|string, mixed> $items Normalized wildcard array
     * @param mixed $config Field path to check for uniqueness (string) or true for entire item
     * @param mixed $sources Source data for template evaluation
     * @param array<string, mixed> $aliases Already resolved aliases
     * @return array<int|string, mixed> Filtered items with duplicates removed
     */
    public static function filter(array $items, mixed $config, mixed $sources, array $aliases): array
    {
        if ([] === $items) {
            return [];
        }

        // If config is true, use entire item for uniqueness check
        if (true === $config) {
            return self::distinctByItem($items);
        }

        // If config is a string, use it as field path
        if (!is_string($config)) {
            return $items;
        }

        return self::distinctByField($items, $config, $sources, $aliases);
    }

    /**
     * Remove duplicates based on entire item.
     *
     * @param array<int|string, mixed> $items Items to filter
     * @return array<int|string, mixed> Unique items
     */
    private static function distinctByItem(array $items): array
    {
        $seen = [];
        $result = [];

        foreach ($items as $index => $item) {
            $serialized = serialize($item);
            if (!isset($seen[$serialized])) {
                $seen[$serialized] = true;
                $result[$index] = $item;
            }
        }

        return $result;
    }

    /**
     * Remove duplicates based on a specific field.
     *
     * @param array<int|string, mixed> $items Items to filter
     * @param string $fieldPath Field path to check for uniqueness
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Already resolved aliases
     * @return array<int|string, mixed> Unique items
     */
    private static function distinctByField(
        array $items,
        string $fieldPath,
        mixed $sources,
        array $aliases
    ): array {
        $seen = [];
        $result = [];

        foreach ($items as $index => $item) {
            // Resolve field path (replace * with current index)
            $actualFieldPath = str_replace('*', (string)$index, $fieldPath);

            // Get value from source
            $value = self::resolveValue($actualFieldPath, $sources, $aliases);

            // Serialize value for comparison (handles arrays, objects, etc.)
            $serialized = serialize($value);

            if (!isset($seen[$serialized])) {
                $seen[$serialized] = true;
                $result[$index] = $item;
            }
        }

        return $result;
    }

    /**
     * Resolve a value from a field path.
     *
     * @param string $value Field path (may be template expression)
     * @param mixed $source Source data
     * @param array<string, mixed> $target Target data (aliases)
     * @return mixed Resolved value
     */
    private static function resolveValue(string $value, mixed $source, array $target): mixed
    {
        // Check if it's a template expression
        if (str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
            // Extract path from template
            $path = trim(substr($value, 2, -2));

            // Remove filters if present
            if (str_contains($path, '|')) {
                [$path] = explode('|', $path, 2);
                $path = trim($path);
            }

            // Try to get value from source first
            if (is_array($source) || is_object($source)) {
                $accessor = new DataAccessor($source);
                $result = $accessor->get($path);

                if (null !== $result) {
                    return $result;
                }
            }

            // If not found in source, try target (aliases)
            if ([] !== $target) {
                $accessor = new DataAccessor($target);
                $result = $accessor->get($path);

                if (null !== $result) {
                    return $result;
                }
            }

            return null;
        }

        // Return literal value
        return $value;
    }
}

