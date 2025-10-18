<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Support\TemplateExpressionProcessor;

final class ExpressionEvaluator
{
    /**
     * Evaluate a template expression.
     *
     * @param array<string, mixed> $sources Source data
     * @param array<string, mixed> $aliases Already resolved aliases (for @references)
     */
    public static function evaluate(
        string $value,
        array $sources,
        array $aliases = []
    ): mixed {
        // Check if value contains multiple {{ }} expressions
        if (preg_match_all('/\{\{[^}]+\}\}/', $value, $matches) > 1) {
            // Multiple expressions - replace each one
            return self::evaluateMultipleExpressions($value, $sources, $aliases);
        }

        $parsed = ExpressionParser::parse($value);

        if (null === $parsed) {
            return $value;
        }

        // Alias reference: @profile.fullname or @user.name or @user.name ?? 'Unknown' | upper
        if ('alias' === $parsed['type']) {
            // First try to resolve from aliases (already resolved values)
            $result = self::resolveAlias($parsed['path'], $aliases);

            // If not found in aliases, try to resolve from sources
            if (null === $result) {
                $result = self::resolveSourcePath($parsed['path'], $sources);
            }

            // Apply default if value is null
            if (null === $result && null !== $parsed['default']) {
                $result = $parsed['default'];
            }

            // Apply filters
            if ([] !== $parsed['filters']) {
                return TemplateExpressionProcessor::applyFilters($result, $parsed['filters']);
            }

            return $result;
        }

        // Expression: {{ user.name ?? 'Unknown' | lower }}
        if ('expression' === $parsed['type']) {
            $resolved = self::resolveSourcePath($parsed['path'], $sources);

            // Apply default if value is null
            if (null === $resolved && null !== $parsed['default']) {
                $resolved = $parsed['default'];
            }

            // Apply filters using TemplateExpressionProcessor (handles wildcards correctly)
            if ([] !== $parsed['filters']) {
                // Check if this is a wildcard result (array with dot-path keys or numeric keys from wildcard)
                // If so, apply filters to each element instead of the whole array
                if (is_array($resolved) && str_contains($parsed['path'], '*')) {
                    $filtered = [];
                    foreach ($resolved as $key => $item) {
                        $filtered[$key] = TemplateExpressionProcessor::applyFilters($item, $parsed['filters']);
                    }
                    return $filtered;
                }

                return TemplateExpressionProcessor::applyFilters($resolved, $parsed['filters']);
            }

            return $resolved;
        }

        return $value;
    }

    /**
     * Evaluate a string with multiple {{ }} expressions.
     *
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateMultipleExpressions(
        string $value,
        array $sources,
        array $aliases
    ): string {
        $result = preg_replace_callback(
            '/\{\{([^}]+)\}\}/',
            function(array $matches) use ($sources, $aliases): string {
                $expression = '{{ ' . trim($matches[1]) . ' }}';
                $result = self::evaluate($expression, $sources, $aliases);
                return (string)$result;
            },
            $value
        );

        return $result ?? $value;
    }

    /**
     * Resolve an alias reference like @profile.fullname.
     *
     * @param array<string, mixed> $aliases
     */
    private static function resolveAlias(string $path, array $aliases): mixed
    {
        $accessor = new DataAccessor($aliases);
        return $accessor->get($path);
    }

    /**
     * Resolve a source path like user.name.
     *
     * @param array<string, mixed> $sources
     */
    private static function resolveSourcePath(string $path, array $sources): mixed
    {
        // Special case: if sources has a single entry with empty key, use it as the direct source
        // This allows {{ customer_name }} instead of requiring {{ source.customer_name }}
        if (1 === count($sources) && isset($sources[''])) {
            $accessor = new DataAccessor($sources['']);
            return $accessor->get($path);
        }

        // Parse alias.path
        $parts = explode('.', $path, 2);
        $alias = $parts[0];
        $subPath = $parts[1] ?? null;

        if (!isset($sources[$alias])) {
            return null;
        }

        // If no subpath, return the source value directly
        if (null === $subPath) {
            return $sources[$alias];
        }

        // Otherwise, use DataAccessor to get the nested value
        $accessor = new DataAccessor($sources[$alias]);
        return $accessor->get($subPath);
    }
}
