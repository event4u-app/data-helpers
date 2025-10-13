<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

use event4u\DataHelpers\Cache\LruCache;
use event4u\DataHelpers\DataHelpersConfig;

final class ExpressionParser
{
    private static ?LruCache $cache = null;
    /** Check if a string contains a template expression {{ ... }}. */
    public static function hasExpression(string $value): bool
    {
        // Short-circuit: if {{ not found, no need to check for }}
        return str_contains($value, '{{') && str_contains($value, '}}');
    }

    /**
     * Parse a template expression {{ ... }}.
     *
     * Returns null if the string is not a valid {{ }} expression.
     *
     * @return array{type: string, path: string, default: mixed, filters: array<int, string>}|null
     */
    public static function parse(string $value): ?array
    {
        // Check cache first - use get() directly to avoid double lookup
        $cached = self::getCache()->get($value);
        if (null !== $cached || self::getCache()->has($value)) {
            // PHPStan: We know it's either the correct array structure or null from cache
            /** @var array{type: string, path: string, default: mixed, filters: array<int, string>}|null $cached */
            return $cached;
        }

        // Template expression: {{ ... }}
        if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value, $matches)) {
            $expression = trim($matches[1]);

            // Check for alias reference: {{ @fullname }}
            if (str_starts_with($expression, '@')) {
                $path = substr($expression, 1); // Remove @
                $result = [
                    'type' => 'alias',
                    'path' => $path,
                    'default' => null,
                    'filters' => [],
                ];

                // Cache result
                self::getCache()->set($value, $result);

                return $result;
            }

            // Parse filters: user.email | lower | trim
            // Split by | but respect quoted strings
            $parts = self::splitByPipe($expression);
            $pathWithDefault = array_shift($parts) ?? '';
            $filters = $parts;

            // Parse default value: user.name ?? 'Unknown'
            $default = null;
            if ('' !== $pathWithDefault && str_contains($pathWithDefault, '??')) {
                [$pathWithDefault, $defaultStr] = array_map('trim', explode('??', $pathWithDefault, 2));
                $default = self::parseDefaultValue($defaultStr);
            }

            $result = [
                'type' => 'expression',
                'path' => $pathWithDefault,
                'default' => $default,
                'filters' => $filters,
            ];

            // Cache result
            self::getCache()->set($value, $result);

            return $result;
        }

        // Cache null result
        self::getCache()->set($value, null);

        return null;
    }

    /**
     * Split expression by pipe (|) but respect quoted strings.
     *
     * Example: 'user.name | join:" | " | trim' -> ['user.name', 'join:" | "', 'trim']
     *
     * Note: This method uses FilterEngine's useFastSplit setting to determine parsing mode.
     *
     * @return array<int, string>
     */
    private static function splitByPipe(string $expression): array
    {
        // Fast path: No quotes → simple split
        if (!str_contains($expression, '"') && !str_contains($expression, "'")) {
            return array_map('trim', explode('|', $expression));
        }

        // Use FilterEngine's fast split setting
        // Note: We keep quotes in output here - they're removed later by FilterEngine
        if (FilterEngine::isFastSplitEnabled()) {
            return self::splitByPipeFast($expression);
        }

        return self::splitByPipeSafe($expression);
    }

    /**
     * Fast pipe split: Simple quote toggle without escape handling.
     *
     * @return array<int, string>
     */
    public static function splitByPipeFast(string $expression): array
    {
        return self::splitByPipeInternal($expression, false);
    }

    /**
     * Safe pipe split: Full escape handling.
     *
     * @return array<int, string>
     */
    public static function splitByPipeSafe(string $expression): array
    {
        return self::splitByPipeInternal($expression, true);
    }

    /**
     * Internal pipe split implementation.
     *
     * @param bool $handleEscapes Whether to handle escape sequences
     * @return array<int, string>
     */
    private static function splitByPipeInternal(string $expression, bool $handleEscapes): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $escaped = false;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            // Handle escape sequences (only in safe mode)
            if ($handleEscapes && $escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ($handleEscapes && '\\' === $char) {
                $escaped = true;
                $current .= $char;
                continue;
            }

            // Handle quotes
            if ($handleEscapes) {
                // Safe mode: Track quote character
                if (('"' === $char || "'" === $char) && !$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                    $current .= $char;
                    continue;
                }
                if ($char === $quoteChar && $inQuotes) {
                    $inQuotes = false;
                    $quoteChar = null;
                    $current .= $char;
                    continue;
                }
            } elseif ('"' === $char) {
                // Fast mode: Simple toggle on double quotes only
                $inQuotes = !$inQuotes;
                $current .= $char;
                continue;
            }

            // Split on pipe if not in quotes
            if ('|' === $char && !$inQuotes) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ('' !== $current) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private static function parseDefaultValue(string $value): mixed
    {
        $value = trim($value);

        // String literal - remove quotes
        if ((str_starts_with($value, "'") && str_ends_with($value, "'"))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return substr($value, 1, -1);
        }

        // Number - check before keywords to avoid converting "123" as string
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // Keywords (case-insensitive) - use match for better performance
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }

    /** Get or initialize cache instance. */
    private static function getCache(): LruCache
    {
        if (!self::$cache instanceof LruCache) {
            $maxEntries = DataHelpersConfig::getCacheMaxEntries();
            self::$cache = new LruCache($maxEntries);
        }

        return self::$cache;
    }

    /** Clear cache and reset instance (for testing). */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, max_size: int|null}
     */
    public static function getCacheStats(): array
    {
        return self::getCache()->getStats();
    }
}
