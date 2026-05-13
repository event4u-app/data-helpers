<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

/**
 * Cache for compiled DataMapper templates.
 *
 * DataMapper templates with {{ }} expressions need to be parsed and compiled.
 * This cache stores the compiled results to avoid repeated regex operations
 * and string parsing.
 *
 * Phase 3 Optimization: Centralized template compilation cache
 * - Caches template expression parsing
 * - Caches path extraction from {{ }} syntax
 * - Caches static vs dynamic value detection
 * - Statistics tracking (hit/miss ratio)
 *
 * Performance Impact:
 * - Eliminates repeated regex operations
 * - Eliminates repeated string parsing
 * - Expected: 20-30% faster template processing
 */
final class TemplateCompilationCache
{
    /**
     * Cache for template expression detection.
     *
     * Format: ['{{ user.name }}' => true, 'John Doe' => false]
     *
     * @var array<string, bool>
     */
    private static array $isTemplateCache = [];

    /**
     * Cache for extracted paths from templates.
     *
     * Format: ['{{ user.name }}' => 'user.name', '{{ items.* }}' => 'items.*']
     *
     * @var array<string, string>
     */
    private static array $extractedPathCache = [];

    /**
     * Cache for compiled template mappings.
     *
     * Format: [hash => ['name' => 'user.name', 'status' => ['__static__' => 'active']]]
     *
     * @var array<string, array<string, string|non-empty-array<string, mixed>>>
     */
    private static array $compiledMappingCache = [];

    /**
     * Statistics for cache performance monitoring.
     *
     * @var array{hits: int, misses: int, size: int}
     */
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
    ];

    /** Regular expression pattern for matching a SINGLE {{ }} expression */
    private const TEMPLATE_PATTERN = '/^\{\{\s*(.+?)\s*\}\}$/';

    /** Maximum cache size before cleanup (prevent memory leaks) */
    private const MAX_CACHE_SIZE = 500;

    /**
     * Check if a string contains multiple {{ }} expression blocks.
     *
     * Values like '{{ a | trim }} {{ b | ucfirst }}' are NOT single template expressions.
     * They must be handled as multi-expression strings (string interpolation).
     */
    private static function isMultiExpression(string $value): bool
    {
        return substr_count($value, '{{') > 1;
    }

    /**
     * Check if a string is a template expression (cached).
     *
     * @param string $value The string to check
     * @return bool True if the string is a template expression
     */
    public static function isTemplate(string $value): bool
    {
        // Check cache first
        if (array_key_exists($value, self::$isTemplateCache)) {
            self::$stats['hits']++;

            return self::$isTemplateCache[$value];
        }

        self::$stats['misses']++;

        // Fast path: Check for {{ and }} first before using regex
        if (!str_contains($value, '{{') || !str_contains($value, '}}')) {
            self::$isTemplateCache[$value] = false;
            self::updateSize();

            return false;
        }

        // Multiple {{ }} blocks are NOT a single template expression
        if (self::isMultiExpression($value)) {
            self::$isTemplateCache[$value] = false;
            self::updateSize();

            return false;
        }

        $isTemplate = 1 === preg_match(self::TEMPLATE_PATTERN, $value);
        self::$isTemplateCache[$value] = $isTemplate;
        self::updateSize();

        return $isTemplate;
    }

    /**
     * Extract path from template expression (cached).
     *
     * Examples:
     * - '{{ user.name }}' → 'user.name'
     * - '{{ user.name | upper }}' → 'user.name | upper' (preserves filters)
     * - '{{ items.* }}' → 'items.*'
     * - 'John Doe' → 'John Doe' (not a template)
     *
     * @param string $template The template string
     * @return string The extracted path or original string
     */
    public static function extractPath(string $template): string
    {
        // Check cache first
        if (isset(self::$extractedPathCache[$template])) {
            self::$stats['hits']++;

            return self::$extractedPathCache[$template];
        }

        self::$stats['misses']++;

        // Fast path: Check for {{ and }} first
        if (!str_contains($template, '{{') || !str_contains($template, '}}')) {
            self::$extractedPathCache[$template] = $template;
            self::updateSize();

            return $template;
        }

        // Multiple {{ }} blocks are NOT a single template expression
        if (self::isMultiExpression($template)) {
            self::$extractedPathCache[$template] = $template;
            self::updateSize();

            return $template;
        }

        // Try to extract path
        if (preg_match(self::TEMPLATE_PATTERN, $template, $matches)) {
            $path = trim($matches[1]);
            self::$extractedPathCache[$template] = $path;
            self::updateSize();

            return $path;
        }

        // Not a template - return as is
        self::$extractedPathCache[$template] = $template;
        self::updateSize();

        return $template;
    }

    /**
     * Try to extract path from template expression (cached).
     *
     * Performance-optimized version that combines isTemplate() and extractPath().
     *
     * @param string $template The template string
     * @return string|null The extracted path if template, null otherwise
     */
    public static function tryExtractTemplate(string $template): ?string
    {
        // Check if we already know this is not a template
        if (isset(self::$isTemplateCache[$template]) && !self::$isTemplateCache[$template]) {
            self::$stats['hits']++;

            return null;
        }

        // Check if we already extracted this path
        if (isset(self::$extractedPathCache[$template])) {
            self::$stats['hits']++;

            // Return null if it's not a template (path equals template)
            $path = self::$extractedPathCache[$template];

            return $path === $template ? null : $path;
        }

        self::$stats['misses']++;

        // Fast path: Check for {{ and }} first
        if (!str_contains($template, '{{') || !str_contains($template, '}}')) {
            self::$isTemplateCache[$template] = false;
            self::$extractedPathCache[$template] = $template;
            self::updateSize();

            return null;
        }

        // Multiple {{ }} blocks are NOT a single template expression
        if (self::isMultiExpression($template)) {
            self::$isTemplateCache[$template] = false;
            self::$extractedPathCache[$template] = $template;
            self::updateSize();

            return null;
        }

        // Try to extract path with single preg_match
        if (preg_match(self::TEMPLATE_PATTERN, $template, $matches)) {
            $path = trim($matches[1]);
            self::$isTemplateCache[$template] = true;
            self::$extractedPathCache[$template] = $path;
            self::updateSize();

            return $path;
        }

        // Not a template
        self::$isTemplateCache[$template] = false;
        self::$extractedPathCache[$template] = $template;
        self::updateSize();

        return null;
    }

    /**
     * Parse and compile a mapping array (cached).
     *
     * Converts templates to paths and marks static values.
     *
     * @param array<string, mixed> $mapping The mapping to parse
     * @param string $staticMarker The marker for static values (default: '__static__')
     * @return array<string, string|non-empty-array<string, mixed>> Compiled mapping
     */
    public static function compileMapping(array $mapping, string $staticMarker = '__static__'): array
    {
        // Create cache key from mapping
        $cacheKey = hash('xxh128', serialize($mapping) . $staticMarker);

        // Check cache first
        if (isset(self::$compiledMappingCache[$cacheKey])) {
            self::$stats['hits']++;

            return self::$compiledMappingCache[$cacheKey];
        }

        self::$stats['misses']++;

        $compiled = [];

        foreach ($mapping as $targetPath => $sourcePath) {
            if (is_string($sourcePath)) {
                // Try to extract template path
                $extracted = self::tryExtractTemplate($sourcePath);
                if (null !== $extracted) {
                    $compiled[$targetPath] = $extracted;
                    continue;
                }
            }

            // Not a template - mark as static
            $compiled[$targetPath] = [$staticMarker => $sourcePath];
        }

        // Cache and return
        self::$compiledMappingCache[$cacheKey] = $compiled;
        self::updateSize();

        return $compiled;
    }

    /**
     * Warm the cache with commonly used templates.
     *
     * @param array<int, string> $templates List of template strings to pre-compile
     */
    public static function warm(array $templates): void
    {
        foreach ($templates as $template) {
            self::tryExtractTemplate($template);
        }
    }

    /** Clear all caches. */
    public static function clear(): void
    {
        self::$isTemplateCache = [];
        self::$extractedPathCache = [];
        self::$compiledMappingCache = [];
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
        ];
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, hitRatio: float}
     */
    public static function getStats(): array
    {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRatio = 0 < $total ? self::$stats['hits'] / $total : 0.0;

        return [
            'hits' => self::$stats['hits'],
            'misses' => self::$stats['misses'],
            'size' => self::$stats['size'],
            'hitRatio' => $hitRatio,
        ];
    }

    /** Reset statistics without clearing the cache. */
    public static function resetStats(): void
    {
        self::$stats['hits'] = 0;
        self::$stats['misses'] = 0;
        // Keep size as is
    }

    /** Update cache size statistic. */
    private static function updateSize(): void
    {
        self::$stats['size'] = count(self::$isTemplateCache)
            + count(self::$extractedPathCache)
            + count(self::$compiledMappingCache);

        // Cleanup if cache is too large
        if (self::MAX_CACHE_SIZE <= self::$stats['size']) {
            self::cleanup();
        }
    }

    /**
     * Cleanup old entries when cache is too large.
     *
     * Removes oldest 20% of entries from each cache (simple LRU approximation).
     */
    private static function cleanup(): void
    {
        $removeCount = (int)(self::MAX_CACHE_SIZE * 0.2 / 3); // Divide by 3 caches

        self::$isTemplateCache = array_slice(self::$isTemplateCache, $removeCount, null, true);
        self::$extractedPathCache = array_slice(self::$extractedPathCache, $removeCount, null, true);
        self::$compiledMappingCache = array_slice(self::$compiledMappingCache, $removeCount, null, true);

        self::updateSize();
    }
}
