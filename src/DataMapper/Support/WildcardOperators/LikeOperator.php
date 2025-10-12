<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support\WildcardOperators;

use event4u\DataHelpers\DataAccessor;

/**
 * Filters wildcard arrays using SQL LIKE-style pattern matching.
 *
 * Supports:
 * - % for zero or more characters
 * - _ for exactly one character
 * - Case-insensitive matching by default
 */
class LikeOperator
{
    /**
     * Apply LIKE filter to wildcard array.
     *
     * @param array<int|string, mixed> $items Normalized wildcard array
     * @param array<string, mixed> $config LIKE conditions (field => pattern)
     * @param mixed $sources Source data for template evaluation
     * @param array<string, mixed> $aliases Already resolved aliases
     * @return array<int|string, mixed> Filtered items
     */
    public static function filter(array $items, array $config, mixed $sources, array $aliases): array
    {
        if ([] === $items || [] === $config) {
            return $items;
        }

        $result = [];

        foreach ($items as $index => $item) {
            if (self::matchesLikeConditions($config, $index, $sources, $aliases)) {
                $result[$index] = $item;
            }
        }

        return $result;
    }

    /**
     * Check if an item matches all LIKE conditions.
     *
     * @param array<string, mixed> $conditions LIKE conditions
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param array<string, mixed> $target Target data (aliases)
     * @return bool True if all conditions match
     */
    private static function matchesLikeConditions(
        array $conditions,
        int|string $index,
        mixed $source,
        array $target
    ): bool {
        foreach ($conditions as $fieldPath => $pattern) {
            if (!self::matchesLikeCondition($fieldPath, $pattern, $index, $source, $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a field matches a LIKE pattern.
     *
     * @param string $fieldPath Field path (may be template expression)
     * @param mixed $pattern Pattern to match (string or array with pattern and options)
     * @param int|string $index Current item index
     * @param mixed $source Source data
     * @param array<string, mixed> $target Target data (aliases)
     * @return bool True if field matches pattern
     */
    private static function matchesLikeCondition(
        string $fieldPath,
        mixed $pattern,
        int|string $index,
        mixed $source,
        array $target
    ): bool {
        // Resolve field path (replace * with current index)
        $actualFieldPath = str_replace('*', (string)$index, $fieldPath);

        // Get actual value from source
        $actualValue = self::resolveValue($actualFieldPath, $source, $target);

        // Convert to string for comparison
        if (!is_string($actualValue) && !is_numeric($actualValue)) {
            return false;
        }

        $actualValue = (string)$actualValue;

        // Handle pattern configuration
        $patternString = '';
        $caseSensitive = false;

        if (is_array($pattern)) {
            // Array format: ['pattern' => 'value', 'case_sensitive' => true]
            $patternString = (string)($pattern['pattern'] ?? $pattern[0] ?? '');
            $caseSensitive = (bool)($pattern['case_sensitive'] ?? false);
        } else {
            // Simple string pattern
            $patternString = (string)$pattern;
        }

        // Resolve pattern if it's a template expression
        $resolvedPattern = self::resolveValue($patternString, $source, $target);
        if (!is_string($resolvedPattern) && !is_numeric($resolvedPattern)) {
            return false;
        }

        $resolvedPattern = (string)$resolvedPattern;

        // Convert LIKE pattern to regex
        $regex = self::likeToRegex($resolvedPattern, $caseSensitive);

        // Match against regex
        return 1 === preg_match($regex, $actualValue);
    }

    /**
     * Convert SQL LIKE pattern to regex.
     *
     * @param string $pattern LIKE pattern (% for any chars, _ for single char)
     * @param bool $caseSensitive Whether to use case-sensitive matching
     * @return string Regex pattern
     */
    private static function likeToRegex(string $pattern, bool $caseSensitive = false): string
    {
        // Replace LIKE wildcards with unique placeholders
        $pattern = str_replace(
            ['%', '_'],
            ['XLIKEPCTX', 'XLIKEUSCX'],
            $pattern
        );

        // Escape special regex characters
        $escaped = preg_quote($pattern, '/');

        // Replace placeholders with regex equivalents
        $escaped = str_replace(
            ['XLIKEPCTX', 'XLIKEUSCX'],
            ['.*', '.'],
            $escaped
        );

        // Build regex with anchors
        $flags = $caseSensitive ? '' : 'i';

        return '/^' . $escaped . '$/' . $flags;
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

