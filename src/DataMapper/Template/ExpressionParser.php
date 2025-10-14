<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

final class ExpressionParser
{
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
        // Simple in-method cache for repeated expressions
        static $cache = [];
        if (isset($cache[$value])) {
            return $cache[$value];
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
                $cache[$value] = $result;
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
            $cache[$value] = $result;
            return $result;
        }

        $cache[$value] = null;
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
        $parts = [];
        $current = '';
        $inQuotes = false;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ('"' === $char) {
                $inQuotes = !$inQuotes;
                $current .= $char;
            } elseif ('|' === $char && !$inQuotes) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ('' !== $current) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Safe pipe split: Full escape handling.
     *
     * @return array<int, string>
     */
    public static function splitByPipeSafe(string $expression): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $escaped = false;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ('\\' === $char) {
                $escaped = true;
                $current .= $char;
                continue;
            }

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
}
