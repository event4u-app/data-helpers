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
        $parsed = ExpressionParser::parse($value);

        if (null === $parsed) {
            return $value;
        }

        // Alias reference: @profile.fullname
        if ('alias' === $parsed['type']) {
            return self::resolveAlias($parsed['path'], $aliases);
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
