<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

final class ExpressionParser
{
    public static function hasExpression(string $value): bool
    {
        return str_contains($value, '{{') || str_starts_with($value, '@');
    }

    /** @return array{type: string, path: string, default: mixed, filters: array<int, string>}|null */
    public static function parse(string $value): ?array
    {
        // Alias reference: @profile.fullname
        if (str_starts_with($value, '@')) {
            return [
                'type' => 'alias',
                'path' => substr($value, 1),
                'default' => null,
                'filters' => [],
            ];
        }

        // Template expression: {{ ... }}
        if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value, $matches)) {
            $expression = trim($matches[1]);

            // Parse filters: user.email | lower | trim
            $parts = array_map('trim', explode('|', $expression));
            $pathWithDefault = array_shift($parts);
            $filters = $parts;

            // Parse default value: user.name ?? 'Unknown'
            $default = null;
            if (str_contains($pathWithDefault, '??')) {
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
