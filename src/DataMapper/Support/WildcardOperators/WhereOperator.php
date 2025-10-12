<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

use event4u\DataHelpers\DataAccessor;

/**
 * Filters wildcard arrays based on WHERE clauses.
 *
 * Supports Laravel Query Builder-style WHERE conditions with AND/OR logic.
 */
class WhereOperator
{
    /**
     * Apply WHERE clause filter to wildcard array.
     *
     * @param array<int|string, mixed> $items Normalized wildcard array
     * @param array<string, mixed> $whereClause WHERE conditions
     * @param mixed $source Source data for template evaluation
     * @param mixed $target Target data for template evaluation
     * @return array<int|string, mixed> Filtered items
     */
    public static function filter(array $items, array $whereClause, mixed $source, mixed $target): array
    {
        if ([] === $items) {
            return [];
        }

        $filtered = [];

        foreach ($items as $index => $item) {
            if (self::matchesCondition($whereClause, $index, $source, $target)) {
                $filtered[$index] = $item;
            }
        }

        return $filtered;
    }

    /**
     * Check if an item matches the WHERE condition.
     *
     * @param array<string, mixed> $condition WHERE condition
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return bool True if item matches condition
     */
    private static function matchesCondition(array $condition, int|string $index, mixed $source, mixed $target): bool
    {
        // Check for AND/OR operators (case-insensitive)
        foreach ($condition as $key => $value) {
            $keyUpper = strtoupper((string)$key);

            if ('AND' === $keyUpper && is_array($value)) {
                /** @var array<string, mixed> $value */
                return self::matchesAndCondition($value, $index, $source, $target);
            }

            if ('OR' === $keyUpper && is_array($value)) {
                /** @var array<string, mixed> $value */
                return self::matchesOrCondition($value, $index, $source, $target);
            }
        }

        // No explicit AND/OR - treat as implicit AND
        return self::matchesAndCondition($condition, $index, $source, $target);
    }

    /**
     * Check if item matches AND condition (all conditions must match).
     *
     * @param array<string, mixed> $conditions AND conditions
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return bool True if all conditions match
     */
    private static function matchesAndCondition(
        array $conditions,
        int|string $index,
        mixed $source,
        mixed $target,
    ): bool {
        foreach ($conditions as $key => $expectedValue) {
            $keyUpper = strtoupper((string)$key);

            // Handle nested OR within AND
            if ('OR' === $keyUpper && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (!self::matchesOrCondition($expectedValue, $index, $source, $target)) {
                    return false;
                }
                continue;
            }

            // Handle nested AND within AND
            if ('AND' === $keyUpper && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (!self::matchesAndCondition($expectedValue, $index, $source, $target)) {
                    return false;
                }
                continue;
            }

            // Handle array of conditions (for multiple OR groups)
            if (is_int($key) && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (!self::matchesCondition($expectedValue, $index, $source, $target)) {
                    return false;
                }
                continue;
            }

            // Regular field comparison
            if (!self::matchesFieldCondition($key, $expectedValue, $index, $source, $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if item matches OR condition (at least one condition must match).
     *
     * @param array<string, mixed> $conditions OR conditions
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return bool True if at least one condition matches
     */
    private static function matchesOrCondition(array $conditions, int|string $index, mixed $source, mixed $target): bool
    {
        foreach ($conditions as $key => $expectedValue) {
            $keyUpper = strtoupper((string)$key);

            // Handle nested AND within OR
            if ('AND' === $keyUpper && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (self::matchesAndCondition($expectedValue, $index, $source, $target)) {
                    return true;
                }
                continue;
            }

            // Handle nested OR within OR
            if ('OR' === $keyUpper && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (self::matchesOrCondition($expectedValue, $index, $source, $target)) {
                    return true;
                }
                continue;
            }

            // Handle array of conditions (for multiple OR groups)
            if (is_int($key) && is_array($expectedValue)) {
                /** @var array<string, mixed> $expectedValue */
                if (self::matchesCondition($expectedValue, $index, $source, $target)) {
                    return true;
                }
                continue;
            }

            // Regular field comparison
            if (self::matchesFieldCondition($key, $expectedValue, $index, $source, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a field matches the expected value.
     *
     * @param string $fieldPath Field path (may be template expression)
     * @param mixed $expectedValue Expected value (may be template expression)
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return bool True if field matches expected value
     */
    private static function matchesFieldCondition(
        string $fieldPath,
        mixed $expectedValue,
        int|string $index,
        mixed $source,
        mixed $target,
    ): bool {
        // Resolve field path (replace * with current index)
        $actualFieldPath = str_replace('*', (string)$index, $fieldPath);

        // Get actual value from source
        $actualValue = self::resolveValue($actualFieldPath, $source, $target);

        // Check if expectedValue is an array with operator and value
        if (is_array($expectedValue) && isset($expectedValue[0]) && is_string($expectedValue[0])) {
            $operator = $expectedValue[0];
            $compareValue = $expectedValue[1] ?? null;

            // Resolve compare value (may be template expression)
            $resolvedCompareValue = self::resolveValue($compareValue, $source, $target);

            return self::compareValues($actualValue, $operator, $resolvedCompareValue);
        }

        // Resolve expected value (may be template expression)
        $resolvedExpectedValue = self::resolveValue($expectedValue, $source, $target);

        // Compare values (loose comparison to handle type differences)
        return $actualValue == $resolvedExpectedValue;
    }

    /**
     * Compare two values using the specified operator.
     *
     * @param mixed $actualValue Actual value from data
     * @param string $operator Comparison operator (=, !=, <>, >, <, >=, <=)
     * @param mixed $expectedValue Expected value to compare against
     * @return bool True if comparison matches
     */
    private static function compareValues(mixed $actualValue, string $operator, mixed $expectedValue): bool
    {
        return match ($operator) {
            '=' => $actualValue == $expectedValue,
            '!=' => $actualValue != $expectedValue,
            '<>' => $actualValue != $expectedValue,
            '>' => $actualValue > $expectedValue,
            '<' => $actualValue < $expectedValue,
            '>=' => $actualValue >= $expectedValue,
            '<=' => $actualValue <= $expectedValue,
            default => $actualValue == $expectedValue, // Fallback to equality
        };
    }

    /**
     * Resolve a value (may be template expression or literal).
     *
     * @param mixed $value Value to resolve
     * @param mixed $source Source data
     * @param mixed $target Target data
     * @return mixed Resolved value
     */
    private static function resolveValue(mixed $value, mixed $source, mixed $target): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

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
        return $value;
    }
}

