<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

use event4u\DataHelpers\DataAccessor;
use InvalidArgumentException;

/**
 * Groups wildcard arrays by one or more fields with aggregation support.
 *
 * Similar to SQL GROUP BY with aggregation functions (SUM, COUNT, AVG, MIN, MAX, etc.).
 *
 * Supports:
 * - Single or multiple grouping fields
 * - Aggregation functions (SUM, COUNT, AVG, MIN, MAX, FIRST, LAST, COLLECT, CONCAT)
 * - HAVING clause for filtering groups after aggregation
 */
class GroupByOperator
{
    /**
     * Apply GROUP BY to wildcard array.
     *
     * @param array<int|string, mixed> $items Normalized wildcard array
     * @param array<string, mixed> $config GROUP BY configuration
     * @param mixed $sources Source data for template evaluation
     * @param array<string, mixed> $aliases Already resolved aliases
     * @return array<int|string, mixed> Grouped items with aggregations
     */
    public static function group(array $items, array $config, mixed $sources, array $aliases): array
    {
        if ([] === $items || [] === $config) {
            return $items;
        }

        // Extract configuration
        $groupFields = self::extractGroupFields($config);

        /** @var array<string, mixed> $aggregations */
        $aggregations = $config['aggregations'] ?? [];

        /** @var array<string, mixed> $having */
        $having = $config['HAVING'] ?? $config['having'] ?? [];

        if ([] === $groupFields) {
            return $items;
        }

        // Group items by field(s)
        $groups = self::groupItems($items, $groupFields, $sources, $aliases);

        // Apply aggregations to each group
        $result = self::applyAggregations($groups, $aggregations, $sources, $aliases);

        // Apply HAVING filter
        if ([] !== $having) {
            return self::applyHaving($result, $having);
        }

        return $result;
    }

    /**
     * Extract group fields from config.
     *
     * @param array<string, mixed> $config Configuration
     * @return array<int, string> Group field paths
     */
    private static function extractGroupFields(array $config): array
    {
        // Support both 'field' (single) and 'fields' (multiple)
        if (isset($config['field']) && is_string($config['field'])) {
            return [$config['field']];
        }

        if (isset($config['fields']) && is_array($config['fields'])) {
            return array_values(array_filter($config['fields'], 'is_string'));
        }

        return [];
    }

    /**
     * Group items by field(s).
     *
     * @param array<int|string, mixed> $items Items to group
     * @param array<int, string> $groupFields Field paths for grouping
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return array<string, array{items: array<int|string, mixed>, key_values: array<int, mixed>}> Grouped items
     */
    private static function groupItems(
        array $items,
        array $groupFields,
        mixed $sources,
        array $aliases
    ): array {
        $groups = [];

        foreach ($items as $index => $item) {
            // Get values for all group fields
            $keyValues = [];
            foreach ($groupFields as $fieldPath) {
                $actualFieldPath = str_replace('*', (string)$index, $fieldPath);
                $value = self::resolveValue($actualFieldPath, $sources, $aliases);
                $keyValues[] = $value;
            }

            // Create group key from all field values
            $groupKey = self::createGroupKey($keyValues);

            // Initialize group if not exists
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'items' => [],
                    'key_values' => $keyValues,
                ];
            }

            // Get the full item from sources
            // Extract source path from first group field (e.g., "sales" from "{{ sales.*.category }}")
            $fullItem = self::getFullItemFromSources($groupFields[0], $index, $sources, $aliases);

            // Add item to group (use full item if available, otherwise use the item from $items)
            $groups[$groupKey]['items'][$index] = $fullItem ?? $item;
        }

        return $groups;
    }

    /**
     * Get full item from sources.
     *
     * @param string $fieldPath Field path with wildcard (e.g., "{{ sales.*.category }}")
     * @param int|string $index Item index
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return mixed Full item or null
     */
    private static function getFullItemFromSources(
        string $fieldPath,
        int|string $index,
        mixed $sources,
        array $aliases
    ): mixed {
        // Extract source path (e.g., "sales.*" from "{{ sales.*.category }}")
        $path = trim($fieldPath);
        if (str_starts_with($path, '{{') && str_ends_with($path, '}}')) {
            $path = trim(substr($path, 2, -2));
        }

        // Remove filters if present
        if (str_contains($path, '|')) {
            [$path] = explode('|', $path, 2);
            $path = trim($path);
        }

        // Extract base path before the last dot (e.g., "sales.*" from "sales.*.category")
        if (str_contains($path, '.*')) {
            [$basePath] = explode('.*', $path, 2);
            $itemPath = $basePath . '.' . $index;

            return self::resolveValue('{{ ' . $itemPath . ' }}', $sources, $aliases);
        }

        return null;
    }

    /**
     * Create a unique group key from field values.
     *
     * @param array<int, mixed> $values Field values
     * @return string Unique group key
     */
    private static function createGroupKey(array $values): string
    {
        return hash('sha256', serialize($values));
    }

    /**
     * Apply aggregations to grouped items.
     *
     * @param array<string, array{items: array<int|string, mixed>, key_values: array<int, mixed>}> $groups Grouped items
     * @param array<string, mixed> $aggregations Aggregation configuration
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return array<int|string, mixed> Items with aggregations
     */
    private static function applyAggregations(
        array $groups,
        array $aggregations,
        mixed $sources,
        array $aliases
    ): array {
        $result = [];
        $resultIndex = 0;

        foreach ($groups as $groupData) {
            $groupItems = $groupData['items'];
            $firstIndex = array_key_first($groupItems);

            if (null === $firstIndex) {
                continue;
            }

            // Start with first item of group
            $firstItem = $groupItems[$firstIndex];

            // Ensure we have an array to work with
            if (is_array($firstItem)) {
                $aggregatedItem = $firstItem;
            } else {
                // If first item is not an array, create a new array
                $aggregatedItem = [];
            }

            // Apply each aggregation
            foreach ($aggregations as $aggName => $aggConfig) {
                if (!is_array($aggConfig) || !isset($aggConfig[0])) {
                    continue;
                }

                $function = is_string($aggConfig[0]) ? strtoupper($aggConfig[0]) : '';
                $fieldPath = $aggConfig[1] ?? null;
                $separator = $aggConfig[2] ?? null;

                $aggregatedValue = self::aggregate(
                    $function,
                    $groupItems,
                    $fieldPath,
                    $sources,
                    $aliases,
                    $separator
                );

                // Store aggregation result in item
                $aggregatedItem[$aggName] = $aggregatedValue;
            }

            $result[$resultIndex] = $aggregatedItem;
            $resultIndex++;
        }

        return $result;
    }

    /**
     * Apply aggregation function to group items.
     *
     * @param string $function Aggregation function (SUM, COUNT, AVG, etc.)
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @param mixed $separator Separator for CONCAT
     * @return mixed Aggregated value
     */
    private static function aggregate(
        string $function,
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases,
        mixed $separator = null
    ): mixed {
        return match ($function) {
            'COUNT' => count($groupItems),
            'SUM' => self::aggregateSum($groupItems, $fieldPath, $sources, $aliases),
            'AVG', 'AVERAGE' => self::aggregateAvg($groupItems, $fieldPath, $sources, $aliases),
            'MIN' => self::aggregateMin($groupItems, $fieldPath, $sources, $aliases),
            'MAX' => self::aggregateMax($groupItems, $fieldPath, $sources, $aliases),
            'FIRST' => self::aggregateFirst($groupItems, $fieldPath, $sources, $aliases),
            'LAST' => self::aggregateLast($groupItems, $fieldPath, $sources, $aliases),
            'COLLECT' => self::aggregateCollect($groupItems, $fieldPath, $sources, $aliases),
            'CONCAT' => self::aggregateConcat($groupItems, $fieldPath, $sources, $aliases, $separator),
            default => throw new InvalidArgumentException('Unknown aggregation function: ' . $function),
        };
    }

    /**
     * SUM aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return int|float Sum of values
     */
    private static function aggregateSum(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): int|float {
        $sum = 0;

        foreach (array_keys($groupItems) as $index) {
            if (null === $fieldPath) {
                continue;
            }

            $actualFieldPath = str_replace('*', (string)$index, $fieldPath);
            $value = self::resolveValue($actualFieldPath, $sources, $aliases);

            if (is_numeric($value)) {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * AVG aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return float Average value
     */
    private static function aggregateAvg(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): float {
        $count = count($groupItems);

        if (0 === $count) {
            return 0.0;
        }

        $sum = self::aggregateSum($groupItems, $fieldPath, $sources, $aliases);

        return (float)($sum / $count);
    }

    /**
     * MIN aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return mixed Minimum value
     */
    private static function aggregateMin(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): mixed {
        $min = null;

        foreach (array_keys($groupItems) as $index) {
            if (null === $fieldPath) {
                continue;
            }

            $actualFieldPath = str_replace('*', (string)$index, $fieldPath);
            $value = self::resolveValue($actualFieldPath, $sources, $aliases);

            if (null === $min || $value < $min) {
                $min = $value;
            }
        }

        return $min;
    }

    /**
     * MAX aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return mixed Maximum value
     */
    private static function aggregateMax(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): mixed {
        $max = null;

        foreach (array_keys($groupItems) as $index) {
            if (null === $fieldPath) {
                continue;
            }

            $actualFieldPath = str_replace('*', (string)$index, $fieldPath);
            $value = self::resolveValue($actualFieldPath, $sources, $aliases);

            if (null === $max || $value > $max) {
                $max = $value;
            }
        }

        return $max;
    }

    /**
     * FIRST aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return mixed First value
     */
    private static function aggregateFirst(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): mixed {
        $firstIndex = array_key_first($groupItems);

        if (null === $firstIndex || null === $fieldPath) {
            return null;
        }

        $actualFieldPath = str_replace('*', (string)$firstIndex, $fieldPath);

        return self::resolveValue($actualFieldPath, $sources, $aliases);
    }

    /**
     * LAST aggregation.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return mixed Last value
     */
    private static function aggregateLast(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): mixed {
        $lastIndex = array_key_last($groupItems);

        if (null === $lastIndex || null === $fieldPath) {
            return null;
        }

        $actualFieldPath = str_replace('*', (string)$lastIndex, $fieldPath);

        return self::resolveValue($actualFieldPath, $sources, $aliases);
    }

    /**
     * COLLECT aggregation - collects all values into an array.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @return array<int, mixed> Collected values
     */
    private static function aggregateCollect(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases
    ): array {
        $collected = [];

        foreach (array_keys($groupItems) as $index) {
            if (null === $fieldPath) {
                continue;
            }

            $actualFieldPath = str_replace('*', (string)$index, $fieldPath);
            $value = self::resolveValue($actualFieldPath, $sources, $aliases);
            $collected[] = $value;
        }

        return $collected;
    }

    /**
     * CONCAT aggregation - concatenates all values into a string.
     *
     * @param array<int|string, mixed> $groupItems Items in the group
     * @param string|null $fieldPath Field path to aggregate
     * @param mixed $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @param mixed $separator Separator string
     * @return string Concatenated values
     */
    private static function aggregateConcat(
        array $groupItems,
        ?string $fieldPath,
        mixed $sources,
        array $aliases,
        mixed $separator = null
    ): string {
        $values = self::aggregateCollect($groupItems, $fieldPath, $sources, $aliases);

        $separator = is_string($separator) ? $separator : ', ';

        return implode($separator, array_map(fn(mixed $v): string => (string)$v, $values));
    }

    /**
     * Apply HAVING filter to grouped results.
     *
     * @param array<int|string, mixed> $items Grouped items with aggregations
     * @param array<string, mixed> $having HAVING conditions
     * @return array<int|string, mixed> Filtered items
     */
    private static function applyHaving(array $items, array $having): array
    {
        $result = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            /** @var array<string, mixed> $item */
            if (self::matchesHavingConditions($item, $having)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Check if item matches HAVING conditions.
     *
     * @param array<string, mixed> $item Item with aggregations
     * @param array<string, mixed> $conditions HAVING conditions
     * @return bool True if all conditions match
     */
    private static function matchesHavingConditions(array $item, array $conditions): bool
    {
        foreach ($conditions as $field => $condition) {
            if (!isset($item[$field])) {
                return false;
            }

            $value = $item[$field];

            // Handle array condition [operator, expected_value]
            if (is_array($condition) && isset($condition[0])) {
                $operator = is_string($condition[0]) ? $condition[0] : '=';
                $expectedValue = $condition[1] ?? null;
                if (!self::compareValues($value, $operator, $expectedValue)) {
                    return false;
                }
            } elseif ($value != $condition) {
                // Direct comparison
                return false;
            }
        }

        return true;
    }

    /**
     * Compare values using operator.
     *
     * @param mixed $actual Actual value
     * @param string $operator Comparison operator
     * @param mixed $expected Expected value
     * @return bool True if comparison matches
     */
    private static function compareValues(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => false,
        };
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