<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Template\ExpressionEvaluator;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;
use event4u\DataHelpers\DataMapper\Template\FilterEngine;

/**
 * Central processor for template expressions.
 *
 * Handles parsing and evaluation of template expressions like:
 * - {{ path }}
 * - {{ path | filter }}
 * - {{ path | filter1 | filter2 }}
 * - {{ path ?? default }}
 * - {{ path | filter ?? default }}
 *
 * This class unifies template expression handling across all mapping methods.
 */
final class TemplateExpressionProcessor
{
    /**
     * Check if a value is a template expression.
     *
     * @param mixed $value Value to check
     * @return bool True if value is a template expression
     */
    public static function isExpression(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return ExpressionParser::hasExpression($value);
    }

    /**
     * Parse a template expression into components.
     *
     * Returns:
     * - path: The data path (e.g., "user.name")
     * - filters: Array of filter names (e.g., ["upper", "trim"])
     * - default: Default value if path is null
     * - hasFilters: Whether filters are present
     *
     * @param string $expression Template expression (with or without {{ }})
     * @return array{path: string, filters: array<string>, default: mixed, hasFilters: bool}
     */
    public static function parse(string $expression): array
    {
        // Remove {{ }} if present
        $expression = trim($expression);
        if (str_starts_with($expression, '{{') && str_ends_with($expression, '}}')) {
            $expression = trim(substr($expression, 2, -2));
        }

        // Check if expression contains filters (|)
        if (!str_contains($expression, '|')) {
            // No filters - check for default value (??)
            if (str_contains($expression, '??')) {
                $parts = explode('??', $expression, 2);
                return [
                    'path' => trim($parts[0]),
                    'filters' => [],
                    'default' => trim($parts[1] ?? ''),
                    'hasFilters' => false,
                ];
            }

            return [
                'path' => $expression,
                'filters' => [],
                'default' => null,
                'hasFilters' => false,
            ];
        }

        // Has filters - use ExpressionParser for full parsing
        $parsed = ExpressionParser::parse('{{ ' . $expression . ' }}');

        if (null === $parsed) {
            return [
                'path' => $expression,
                'filters' => [],
                'default' => null,
                'hasFilters' => false,
            ];
        }

        return [
            'path' => $parsed['path'],
            'filters' => $parsed['filters'],
            'default' => $parsed['default'],
            'hasFilters' => [] !== $parsed['filters'],
        ];
    }

    /**
     * Evaluate a template expression against a data source.
     *
     * For simple mapping (map, mapFromFile):
     * - $source is the data object
     * - $sources is empty
     *
     * For template mapping (mapFromTemplate):
     * - $source is null
     * - $sources contains named data sources
     *
     * @param string $expression Template expression
     * @param mixed $source Single data source (for map, mapFromFile)
     * @param array<string, mixed> $sources Named data sources (for mapFromTemplate)
     * @return mixed Evaluated value
     */
    public static function evaluate(
        string $expression,
        mixed $source = null,
        array $sources = []
    ): mixed {
        // If we have named sources, use ExpressionEvaluator (mapFromTemplate style)
        if ([] !== $sources) {
            return ExpressionEvaluator::evaluate($expression, $sources, []);
        }

        // Otherwise, use simple evaluation (map/mapFromFile style)
        $parsed = self::parse($expression);

        // Get value from source
        $accessor = new DataAccessor($source);
        $value = $accessor->get($parsed['path']);

        // Apply default if value is null
        if (null === $value && null !== $parsed['default']) {
            $value = $parsed['default'];
        }

        // Apply filters if present
        if ($parsed['hasFilters'] && null !== $value) {
            return FilterEngine::apply($value, $parsed['filters']);
        }

        return $value;
    }

    /**
     * Extract path, filters, and default value from a template expression.
     *
     * This is a convenience method for cases where you need to process
     * the path and filters separately (e.g., for wildcard handling).
     *
     * @param string $expression Template expression
     * @return array{path: string, filters: array<string>, default: mixed}
     */
    public static function extractPathAndFilters(string $expression): array
    {
        $parsed = self::parse($expression);

        return [
            'path' => $parsed['path'],
            'filters' => $parsed['filters'],
            'default' => $parsed['default'],
        ];
    }

    /**
     * Apply filters to a value.
     *
     * This is a convenience wrapper around FilterEngine::apply().
     *
     * @param mixed $value Value to filter
     * @param array<string> $filters Filter names
     * @return mixed Filtered value
     */
    public static function applyFilters(mixed $value, array $filters): mixed
    {
        if ([] === $filters) {
            return $value;
        }

        return FilterEngine::apply($value, $filters);
    }

    /**
     * Check if an expression contains filters.
     *
     * @param string $expression Template expression
     * @return bool True if expression contains filters
     */
    public static function hasFilters(string $expression): bool
    {
        $parsed = self::parse($expression);
        return $parsed['hasFilters'];
    }

    /**
     * Extract the path from a template expression (without filters).
     *
     * @param string $expression Template expression
     * @return string Path without filters
     */
    public static function extractPath(string $expression): string
    {
        $parsed = self::parse($expression);
        return $parsed['path'];
    }
}

