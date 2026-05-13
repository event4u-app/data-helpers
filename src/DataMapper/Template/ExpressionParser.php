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
     * @return array{type: string, path: string, default: mixed, filters: array<int, string>, condition?: string, trueValue?: mixed, falseValue?: mixed, left?: string, right?: mixed}|null
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

            // Extract parentheses first (for nested expressions)
            // This allows: user.name ?? (user.surname ?? "UNKNOWN")
            $extracted = self::extractParentheses($expression);
            $expression = $extracted['expression'];
            $parentheses = $extracted['parentheses'];

            // Check for alias reference: {{ @fullname }} or {{ @user.name ?? 'Unknown' | upper }}
            if (str_starts_with($expression, '@')) {
                $withoutAt = substr($expression, 1); // Remove @

                // Parse filters: @user.email | lower | trim
                $parts = self::splitByPipe($withoutAt);
                $pathWithDefault = array_shift($parts) ?? '';
                $filters = $parts;

                // Parse default value: @user.name ?? 'Unknown'
                $default = null;
                if ('' !== $pathWithDefault && str_contains($pathWithDefault, '??')) {
                    [$pathWithDefault, $defaultStr] = array_map('trim', explode('??', $pathWithDefault, 2));
                    $default = self::parseDefaultValue($defaultStr);
                }

                $result = [
                    'type' => 'alias',
                    'path' => $pathWithDefault,
                    'default' => $default,
                    'filters' => $filters,
                    'parentheses' => $parentheses,
                ];
                $cache[$value] = $result;
                return $result;
            }

            // First, split by pipes to extract filters
            // This allows: user.name ?? "UNKNOWN" | upper
            // Where ?? is applied first, then the filter
            $parts = self::splitByPipe($expression);
            $pathWithOperators = array_shift($parts) ?? '';
            $filters = $parts;

            // Now check for operators in the path part (before filters)
            // Priority: ?? > ?: > ? :

            // Check for null coalescing expression: {{ user.email ?? "default@example.com" }}
            if (self::isNullCoalescingExpression($pathWithOperators)) {
                $parsed = self::parseNullCoalescingExpression($pathWithOperators);
                // Add filters to the result
                $result = [
                    'type' => 'null_coalescing',
                    'path' => '',
                    'default' => null,
                    'filters' => $filters,
                    'left' => $parsed['left'] ?? '',
                    'right' => $parsed['right'] ?? null,
                    'rightIsPath' => $parsed['rightIsPath'] ?? false,
                    'parentheses' => $parentheses,
                ];
                $cache[$value] = $result;
                return $result;
            }

            // Check for elvis expression: {{ user.name ?: "Anonymous" }}
            if (self::isElvisExpression($pathWithOperators)) {
                $parsed = self::parseElvisExpression($pathWithOperators);
                // Add filters to the result
                $result = [
                    'type' => 'elvis',
                    'path' => '',
                    'default' => null,
                    'filters' => $filters,
                    'left' => $parsed['left'] ?? '',
                    'right' => $parsed['right'] ?? null,
                    'parentheses' => $parentheses,
                ];
                $cache[$value] = $result;
                return $result;
            }

            // Check for conditional expression: {{ status == "active" ? 1 : 0 }}
            if (self::isConditionalExpression($pathWithOperators)) {
                $parsed = self::parseConditionalExpression($pathWithOperators);
                // Add filters to the result
                $result = [
                    'type' => 'conditional',
                    'path' => '',
                    'default' => null,
                    'filters' => $filters,
                    'condition' => $parsed['condition'] ?? '',
                    'trueValue' => $parsed['trueValue'] ?? null,
                    'falseValue' => $parsed['falseValue'] ?? null,
                    'parentheses' => $parentheses,
                ];
                $cache[$value] = $result;
                return $result;
            }

            // No operators, just path and filters
            $pathWithDefault = $pathWithOperators;

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
                'parentheses' => $parentheses,
            ];
            $cache[$value] = $result;
            return $result;
        }

        $cache[$value] = null;
        return null;
    }

    /**
     * Check if an expression is a conditional expression (ternary operator).
     *
     * Examples:
     * - status == "active" ? 1 : 0
     * - user.age > 18 ? "adult" : "minor"
     */
    private static function isConditionalExpression(string $expression): bool
    {
        // Must contain ? and : but not inside quotes
        if (!str_contains($expression, '?') || !str_contains($expression, ':')) {
            return false;
        }

        // Check if ? and : are outside quotes
        $inQuotes = false;
        $quoteChar = null;
        $hasQuestion = false;
        $hasColon = false;

        for ($i = 0, $len = strlen($expression); $i < $len; $i++) {
            $char = $expression[$i];

            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $expression[$i - 1])) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                continue;
            }

            if (!$inQuotes) {
                if ('?' === $char) {
                    $hasQuestion = true;
                }
                if (':' === $char) {
                    $hasColon = true;
                }
            }
        }

        return $hasQuestion && $hasColon;
    }

    /**
     * Check if an expression is a null coalescing expression (??).
     *
     * Examples:
     * - user.email ?? "default@example.com"
     * - product.name ?? "Unnamed Product"
     */
    private static function isNullCoalescingExpression(string $expression): bool
    {
        // Must contain ?? but not inside quotes
        if (!str_contains($expression, '??')) {
            return false;
        }

        // Check if ?? is outside quotes
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0, $len = strlen($expression) - 1; $i < $len; $i++) {
            $char = $expression[$i];

            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $expression[$i - 1])) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                continue;
            }

            if (!$inQuotes && '?' === $char && isset($expression[$i + 1]) && '?' === $expression[$i + 1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an expression is an elvis expression (?:).
     *
     * Examples:
     * - user.name ?: "Anonymous"
     * - product.quantity ?: 0
     */
    private static function isElvisExpression(string $expression): bool
    {
        // Must contain ?: but not inside quotes
        if (!str_contains($expression, '?:')) {
            return false;
        }

        // Check if ?: is outside quotes
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0, $len = strlen($expression) - 1; $i < $len; $i++) {
            $char = $expression[$i];

            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $expression[$i - 1])) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                continue;
            }

            if (!$inQuotes && '?' === $char && isset($expression[$i + 1]) && ':' === $expression[$i + 1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a conditional expression.
     *
     * Format: condition ? trueValue : falseValue
     *
     * Examples:
     * - status == "active" ? 1 : 0
     * - user.age > 18 ? "adult" : "minor"
     * - items.*.price > 100 ? "expensive" : "cheap"
     *
     * @return array{type: string, path: string, default: mixed, filters: array<int, string>, condition?: string, trueValue?: mixed, falseValue?: mixed}
     */
    private static function parseConditionalExpression(string $expression): array
    {
        // Split by ? and : respecting quotes
        $parts = self::splitConditionalExpression($expression);

        if (3 !== count($parts)) {
            // Fallback: treat as regular expression
            return [
                'type' => 'expression',
                'path' => $expression,
                'default' => null,
                'filters' => [],
            ];
        }

        [$condition, $trueValue, $falseValue] = $parts;

        return [
            'type' => 'conditional',
            'path' => '', // Not used for conditional expressions
            'default' => null, // Not used for conditional expressions
            'filters' => [], // Not used for conditional expressions
            'condition' => trim($condition),
            'trueValue' => self::parseValue(trim($trueValue)),
            'falseValue' => self::parseValue(trim($falseValue)),
        ];
    }

    /**
     * Parse a null coalescing expression (??).
     *
     * Format: left ?? right
     *
     * Examples:
     * - user.email ?? "default@example.com"
     * - product.name ?? "Unnamed Product"
     *
     * @return array{type: string, path: string, default: mixed, filters: array<int, string>, left?: string, right?: mixed}
     */
    private static function parseNullCoalescingExpression(string $expression): array
    {
        // Split by ?? respecting quotes
        $parts = self::splitByOperator($expression, '??');

        if (2 !== count($parts)) {
            // Fallback: treat as regular expression
            return [
                'type' => 'expression',
                'path' => $expression,
                'default' => null,
                'filters' => [],
            ];
        }

        [$left, $right] = $parts;
        $trimmedRight = trim($right);

        // Detect if right side is an unquoted path (not a string literal, number, or keyword)
        $rightIsPath = '' !== $trimmedRight
            && !str_starts_with($trimmedRight, '"')
            && !str_starts_with($trimmedRight, "'")
            && !is_numeric($trimmedRight)
            && !in_array(strtolower($trimmedRight), ['true', 'false', 'null'], true);

        return [
            'type' => 'null_coalescing',
            'path' => '', // Not used for null coalescing expressions
            'default' => null, // Not used for null coalescing expressions
            'filters' => [], // Not used for null coalescing expressions
            'left' => trim($left),
            'right' => self::parseValue($trimmedRight),
            'rightIsPath' => $rightIsPath,
        ];
    }

    /**
     * Parse an elvis expression (?:).
     *
     * Format: left ?: right
     *
     * Examples:
     * - user.name ?: "Anonymous"
     * - product.quantity ?: 0
     *
     * @return array{type: string, path: string, default: mixed, filters: array<int, string>, left?: string, right?: mixed}
     */
    private static function parseElvisExpression(string $expression): array
    {
        // Split by ?: respecting quotes
        $parts = self::splitByOperator($expression, '?:');

        if (2 !== count($parts)) {
            // Fallback: treat as regular expression
            return [
                'type' => 'expression',
                'path' => $expression,
                'default' => null,
                'filters' => [],
            ];
        }

        [$left, $right] = $parts;

        return [
            'type' => 'elvis',
            'path' => '', // Not used for elvis expressions
            'default' => null, // Not used for elvis expressions
            'filters' => [], // Not used for elvis expressions
            'left' => trim($left),
            'right' => self::parseValue(trim($right)),
        ];
    }

    /**
     * Split conditional expression by ? and : respecting quotes.
     *
     * @return array<int, string>
     */
    private static function splitConditionalExpression(string $expression): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0, $len = strlen($expression); $i < $len; $i++) {
            $char = $expression[$i];

            // Handle quotes
            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $expression[$i - 1])) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                $current .= $char;
                continue;
            }

            // Split by ? and :
            if (!$inQuotes && ('?' === $char || ':' === $char)) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ('' !== $current) {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Split expression by a specific operator respecting quotes.
     *
     * @return array<int, string>
     */
    private static function splitByOperator(string $expression, string $operator): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $operatorLen = strlen($operator);

        for ($i = 0, $len = strlen($expression); $i < $len; $i++) {
            $char = $expression[$i];

            // Handle quotes
            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $expression[$i - 1])) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                }
                $current .= $char;
                continue;
            }

            // Check for operator
            if (!$inQuotes && substr($expression, $i, $operatorLen) === $operator) {
                $parts[] = $current;
                $current = '';
                $i += $operatorLen - 1; // Skip operator characters
                continue;
            }

            $current .= $char;
        }

        if ('' !== $current) {
            $parts[] = $current;
        }

        return $parts;
    }

    /** Parse a value (string, number, boolean, null). */
    private static function parseValue(string $value): mixed
    {
        return self::parseDefaultValue($value);
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

    /**
     * Extract parentheses from an expression and replace them with placeholders.
     *
     * Returns an array with:
     * - 'expression': The expression with parentheses replaced by placeholders
     * - 'parentheses': Array of parsed parentheses contents
     *
     * @return array{expression: string, parentheses: array<int, array<string, mixed>|null>}
     */
    private static function extractParentheses(string $expression): array
    {
        $parentheses = [];
        $placeholderIndex = 0;
        $result = '';
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = null;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];
            $prevChar = 0 < $i ? $expression[$i - 1] : '';

            // Handle string delimiters
            if (("'" === $char || '"' === $char) && '\\' !== $prevChar) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }

                if (0 < $depth) {
                    $current .= $char;
                } else {
                    $result .= $char;
                }
                continue;
            }

            // Skip if we're inside a string
            if ($inString) {
                if (0 < $depth) {
                    $current .= $char;
                } else {
                    $result .= $char;
                }
                continue;
            }

            // Handle opening parenthesis
            if ('(' === $char) {
                // Check if this is a function call (identifier before parenthesis)
                // For now, we treat all parentheses as grouping
                // TODO: Add function support later
                $depth++;
                if (1 < $depth) {
                    $current .= $char;
                }
                continue;
            }

            // Handle closing parenthesis
            if (')' === $char) {
                $depth--;
                if (0 < $depth) {
                    $current .= $char;
                } elseif (0 === $depth) {
                    // Parse the content recursively
                    $parsed = self::parse('{{ ' . trim($current) . ' }}');
                    $parentheses[$placeholderIndex] = $parsed;
                    $result .= '__PAREN_' . $placeholderIndex . '__';
                    $placeholderIndex++;
                    $current = '';
                }
                continue;
            }

            // Regular character
            if (0 < $depth) {
                $current .= $char;
            } else {
                $result .= $char;
            }
        }

        return [
            'expression' => $result,
            'parentheses' => $parentheses,
        ];
    }
}
