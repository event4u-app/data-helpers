<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

/**
 * Utility class for parsing template expressions with {{ }} syntax.
 *
 * Templates use {{ }} to mark dynamic values that should be resolved
 * from source data, while plain strings are treated as static values.
 *
 * Examples:
 * - '{{ user.name }}' → dynamic path 'user.name'
 * - 'John Doe' → static value 'John Doe'
 * - '{{ items.* }}' → wildcard path 'items.*'
 */
final class TemplateParser
{
    /** Regular expression pattern for matching {{ }} expressions. */
    private const TEMPLATE_PATTERN = '/^\{\{\s*(.+?)\s*\}\}$/';

    /**
     * Check if a string contains a template expression ({{ }}).
     *
     * @param string $value The string to check
     * @return bool True if the string is a template expression
     */
    public static function isTemplate(string $value): bool
    {
        // Fast path: Check for {{ and }} first before using regex
        if (!str_contains($value, '{{') || !str_contains($value, '}}')) {
            return false;
        }

        return 1 === preg_match(self::TEMPLATE_PATTERN, $value);
    }

    /**
     * Extract the path from a template expression.
     *
     * If the string is wrapped in {{ }}, extracts and returns the inner path.
     * This method now properly handles filters and default values.
     *
     * Examples:
     * - '{{ user.name }}' → 'user.name'
     * - '{{ user.name | upper }}' → 'user.name | upper' (preserves filters)
     * - '{{ user.name ?? "Unknown" }}' → 'user.name ?? "Unknown"' (preserves default)
     * - '{{ items.* }}' → 'items.*'
     * - 'user.name' → 'user.name'
     * - 'John Doe' → 'John Doe'
     *
     * Note: This method preserves the full expression including filters and defaults.
     * Use TemplateExpressionProcessor for parsing these components.
     *
     * @param string $template The template string
     * @return string The extracted path or original string
     */
    public static function extractPath(string $template): string
    {
        // Fast path: Check for {{ and }} first
        if (!str_contains($template, '{{') || !str_contains($template, '}}')) {
            return $template;
        }

        if (preg_match(self::TEMPLATE_PATTERN, $template, $matches)) {
            return trim($matches[1]);
        }

        return $template;
    }

    /**
     * Try to extract path from template expression.
     *
     * Performance-optimized version that combines isTemplate() and extractPath()
     * into a single method to avoid duplicate str_contains() and preg_match() calls.
     *
     * @param string $template The template string
     * @return string|null The extracted path if template, null otherwise
     */
    public static function tryExtractTemplate(string $template): ?string
    {
        // Fast path: Check for {{ and }} first
        if (!str_contains($template, '{{') || !str_contains($template, '}}')) {
            return null;
        }

        // Try to extract path with single preg_match
        if (preg_match(self::TEMPLATE_PATTERN, $template, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Parse a mapping array, separating template expressions from static values.
     *
     * Converts a mapping like:
     * ```php
     * [
     *     'name' => '{{ user.name }}',
     *     'age' => '{{ user.age }}',
     *     'status' => 'active',
     * ]
     * ```
     *
     * Into:
     * ```php
     * [
     *     'name' => 'user.name',
     *     'age' => 'user.age',
     *     'status' => ['__static__' => 'active'],
     * ]
     * ```
     *
     * @param array<string, mixed> $mapping The mapping to parse
     * @param string $staticMarker The marker to use for static values (default: '__static__')
     * @return array<string, string|array{__static__: mixed}> Parsed mapping
     */
    public static function parseMapping(array $mapping, string $staticMarker = '__static__'): array
    {
        // Performance optimization: Cache the entire parsed mapping
        static $cache = [];
        static $cacheHits = 0;
        static $cacheMisses = 0;

        // Create a cache key from the mapping (using hash for non-security purposes)
        $cacheKey = hash('xxh128', serialize($mapping) . $staticMarker);

        if (isset($cache[$cacheKey])) {
            $cacheHits++;
            return $cache[$cacheKey];
        }

        $cacheMisses++;
        $parsed = [];

        foreach ($mapping as $targetPath => $sourcePath) {
            if (is_string($sourcePath)) {
                // Performance optimization: Use tryExtractTemplate() to avoid duplicate checks
                // This combines isTemplate() and extractPath() into a single call
                $extracted = self::tryExtractTemplate($sourcePath);
                if (null !== $extracted) {
                    $parsed[$targetPath] = $extracted;
                    continue;
                }
            }

            // Static value: use special marker
            /** @var array{__static__: mixed} $staticValue */
            $staticValue = [$staticMarker => $sourcePath];
            $parsed[$targetPath] = $staticValue;
        }

        /** @var array<string, string|array{__static__: mixed}> $parsed */
        $cache[$cacheKey] = $parsed;

        // Limit cache size to prevent memory issues (keep last 50 entries)
        if (count($cache) > 100) {
            $cache = array_slice($cache, -50, 50, true);
        }

        return $parsed;
    }

    /**
     * Wrap a path in template syntax.
     *
     * Examples:
     * - 'user.name' → '{{ user.name }}'
     * - 'items.*' → '{{ items.* }}'
     *
     * @param string $path The path to wrap
     * @return string The wrapped template
     */
    public static function wrap(string $path): string
    {
        return '{{ ' . $path . ' }}';
    }

    /**
     * Check if a value is a static value marker.
     *
     * @param mixed $value The value to check
     * @param string $staticMarker The marker to check for (default: '__static__')
     * @return bool True if the value is a static marker array
     */
    public static function isStaticValue(mixed $value, string $staticMarker = '__static__'): bool
    {
        return is_array($value) && isset($value[$staticMarker]);
    }

    /**
     * Extract the static value from a static marker array.
     *
     * @param array<string, mixed> $value The static marker array
     * @param string $staticMarker The marker to extract from (default: '__static__')
     * @return mixed The static value
     */
    public static function extractStaticValue(array $value, string $staticMarker = '__static__'): mixed
    {
        return $value[$staticMarker]; // @phpstan-ignore-line offsetAccess.notFound
    }

    /**
     * Normalize a path or template to a plain path.
     *
     * This is useful when you have a value that might be either:
     * - A template: '{{ user.name }}'
     * - A plain path: 'user.name'
     * - A static value: 'John Doe'
     *
     * And you want to extract the path if it's a template, or return it as-is.
     *
     * @param string $value The value to normalize
     * @return string The normalized path
     */
    public static function normalizePath(string $value): string
    {
        return self::extractPath($value);
    }
}
