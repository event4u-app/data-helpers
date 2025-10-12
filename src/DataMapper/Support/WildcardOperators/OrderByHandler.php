<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

use event4u\DataHelpers\DataAccessor;

/**
 * Handles ORDER BY sorting for wildcard arrays.
 */
class OrderByHandler
{
    /**
     * Sort wildcard array based on ORDER BY clause.
     *
     * @param array<int|string, mixed> $items Normalized wildcard array
     * @param array<string, string> $orderByClause ORDER BY conditions (field => direction)
     * @param mixed $source Source data for template evaluation
     * @param mixed $target Target data for template evaluation
     * @return array<int|string, mixed> Sorted items
     */
    public static function sort(array $items, array $orderByClause, mixed $source, mixed $target): array
    {
        if ([] === $items || [] === $orderByClause) {
            return $items;
        }

        // Extract sort fields and directions
        $sortFields = [];
        foreach ($orderByClause as $fieldPath => $direction) {
            $sortFields[] = [
                'path' => $fieldPath,
                'direction' => strtoupper($direction),
            ];
        }

        // Create array with indices and values for sorting
        $sortableItems = [];
        foreach ($items as $index => $item) {
            $sortableItems[] = [
                'index' => $index,
                'item' => $item,
            ];
        }

        // Sort using usort with multi-field comparison
        usort($sortableItems, function(array $a, array $b) use ($sortFields, $source, $target): int {
            foreach ($sortFields as $field) {
                $fieldPath = $field['path'];
                $direction = $field['direction'];

                // Get values for comparison
                $valueA = self::resolveFieldValue($fieldPath, $a['index'], $source, $target);
                $valueB = self::resolveFieldValue($fieldPath, $b['index'], $source, $target);

                // Compare values
                $comparison = self::compareValues($valueA, $valueB);

                if (0 !== $comparison) {
                    // Apply direction (DESC reverses the comparison)
                    return 'DESC' === $direction ? -$comparison : $comparison;
                }

                // Values are equal, continue to next sort field
            }

            // All sort fields are equal
            return 0;
        });

        // Extract sorted items
        $result = [];
        foreach ($sortableItems as $sortableItem) {
            $result[$sortableItem['index']] = $sortableItem['item'];
        }

        return $result;
    }

    /**
     * Resolve field value for a specific index.
     *
     * @param string $fieldPath Field path (may be template expression)
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return mixed Resolved value
     */
    private static function resolveFieldValue(string $fieldPath, int|string $index, mixed $source, mixed $target): mixed
    {
        // Replace * with current index
        $actualFieldPath = str_replace('*', (string)$index, $fieldPath);

        // Check if it's a template expression
        if (str_starts_with($actualFieldPath, '{{') && str_ends_with($actualFieldPath, '}}')) {
            // Extract path from template
            $path = trim(substr($actualFieldPath, 2, -2));

            // Remove filters if present
            if (str_contains($path, '|')) {
                [$path] = explode('|', $path, 2);
                $path = trim($path);
            }

            // Try to get value from source first
            $accessor = new DataAccessor($source);
            $result = $accessor->get($path);

            // If not found in source, try target
            if (null === $result && is_array($target)) {
                $accessor = new DataAccessor($target);
                $result = $accessor->get($path);
            }

            return $result;
        }

        // Return literal value
        return $actualFieldPath;
    }

    /**
     * Compare two values for sorting.
     *
     * @param mixed $a First value
     * @param mixed $b Second value
     * @return int -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
     */
    private static function compareValues(mixed $a, mixed $b): int
    {
        // Handle null values (nulls come first)
        if (null === $a && null === $b) {
            return 0;
        }
        if (null === $a) {
            return -1;
        }
        if (null === $b) {
            return 1;
        }

        // Numeric comparison
        if (is_numeric($a) && is_numeric($b)) {
            $numA = is_string($a) ? (float)$a : $a;
            $numB = is_string($b) ? (float)$b : $b;

            if ($numA < $numB) {
                return -1;
            }
            if ($numA > $numB) {
                return 1;
            }
            return 0;
        }

        // String comparison
        if (is_string($a) && is_string($b)) {
            return strcmp($a, $b);
        }

        // Mixed types - convert to string and compare
        $strA = (string)$a;
        $strB = (string)$b;
        return strcmp($strA, $strB);
    }
}

