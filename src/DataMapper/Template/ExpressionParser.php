<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

final class ExpressionParser
{
    /** Check if a string contains a template expression {{ ... }}. */
    public static function hasExpression(string $value): bool
    {
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
        // Template expression: {{ ... }}
        if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value, $matches)) {
            $expression = trim($matches[1]);

            // Check for alias reference: {{ @fullname }}
            if (str_starts_with($expression, '@')) {
                $path = substr($expression, 1); // Remove @
                return [
                    'type' => 'alias',
                    'path' => $path,
                    'default' => null,
                    'filters' => [],
                ];
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

            return [
                'type' => 'expression',
                'path' => $pathWithDefault,
                'default' => $default,
                'filters' => $filters,
            ];
        }

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
        // Slow path: Has quotes → char-by-char to preserve quoted content
        // Note: Regex is tricky here because we need to keep quotes with their surrounding text
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $escaped = false;

        for ($i = 0; strlen($expression) > $i; $i++) {
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

        // String literal
        if ((str_starts_with($value, "'") && str_ends_with($value, "'"))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return substr($value, 1, -1);
        }

        // Boolean
        if ('true' === strtolower($value)) {
            return true;
        }
        if ('false' === strtolower($value)) {
            return false;
        }

        // Null
        if ('null' === strtolower($value)) {
            return null;
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }
}
