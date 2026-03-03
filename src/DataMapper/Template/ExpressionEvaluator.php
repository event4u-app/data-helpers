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

        // Null coalescing expression: {{ user.email ?? "default@example.com" }}
        if ('null_coalescing' === $parsed['type']) {
            return self::evaluateNullCoalescing($parsed, $sources, $aliases);
        }

        // Elvis expression: {{ user.name ?: "Anonymous" }}
        if ('elvis' === $parsed['type']) {
            return self::evaluateElvis($parsed, $sources, $aliases);
        }

        // Conditional expression: {{ status == "active" ? 1 : 0 }}
        if ('conditional' === $parsed['type']) {
            return self::evaluateConditional($parsed, $sources, $aliases);
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

    /**
     * Evaluate a null coalescing expression (??).
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateNullCoalescing(array $parsed, array $sources, array $aliases): mixed
    {
        $left = $parsed['left'] ?? '';
        $right = $parsed['right'] ?? null;
        /** @var array<int, string> $filters */
        $filters = $parsed['filters'] ?? [];
        /** @var array<int, array<string, mixed>|null> $parentheses */
        $parentheses = $parsed['parentheses'] ?? [];

        // Resolve placeholders in left and right
        if (is_string($left)) {
            $left = self::resolvePlaceholders($left, $parentheses, $sources, $aliases);
        }
        $right = self::resolvePlaceholders($right, $parentheses, $sources, $aliases);

        // Resolve left value
        if (is_string($left)) {
            $leftValue = self::resolveValue($left, $sources, $aliases);
        } else {
            $leftValue = $left;
        }

        // Apply null coalescing
        $result = $leftValue ?? $right;

        // Apply filters if present
        if ([] !== $filters) {
            return FilterEngine::apply($result, $filters);
        }

        return $result;
    }

    /**
     * Evaluate an elvis expression (?:).
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateElvis(array $parsed, array $sources, array $aliases): mixed
    {
        $left = $parsed['left'] ?? '';
        $right = $parsed['right'] ?? null;
        /** @var array<int, string> $filters */
        $filters = $parsed['filters'] ?? [];
        /** @var array<int, array<string, mixed>|null> $parentheses */
        $parentheses = $parsed['parentheses'] ?? [];

        // Resolve placeholders in left and right
        if (is_string($left)) {
            $left = self::resolvePlaceholders($left, $parentheses, $sources, $aliases);
        }
        $right = self::resolvePlaceholders($right, $parentheses, $sources, $aliases);

        // Resolve left value
        if (is_string($left)) {
            $leftValue = self::resolveValue($left, $sources, $aliases);
        } else {
            $leftValue = $left;
        }

        // Apply elvis operator
        $result = $leftValue ?: $right;

        // Apply filters if present
        if ([] !== $filters) {
            return FilterEngine::apply($result, $filters);
        }

        return $result;
    }

    /**
     * Evaluate a conditional expression.
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateConditional(array $parsed, array $sources, array $aliases): mixed
    {
        $condition = $parsed['condition'] ?? '';
        $trueValue = $parsed['trueValue'] ?? null;
        $falseValue = $parsed['falseValue'] ?? null;
        /** @var array<int, string> $filters */
        $filters = $parsed['filters'] ?? [];
        /** @var array<int, array<string, mixed>|null> $parentheses */
        $parentheses = $parsed['parentheses'] ?? [];

        // Resolve placeholders in condition, trueValue, and falseValue
        if (is_string($condition)) {
            $condition = self::resolvePlaceholders($condition, $parentheses, $sources, $aliases);
        }
        $trueValue = self::resolvePlaceholders($trueValue, $parentheses, $sources, $aliases);
        $falseValue = self::resolvePlaceholders($falseValue, $parentheses, $sources, $aliases);

        // Evaluate the condition
        if (is_string($condition)) {
            $conditionResult = self::evaluateCondition($condition, $sources, $aliases);
        } else {
            $conditionResult = (bool)$condition;
        }

        // Apply conditional
        $result = $conditionResult ? $trueValue : $falseValue;

        // Apply filters if present
        if ([] !== $filters) {
            return FilterEngine::apply($result, $filters);
        }

        return $result;
    }

    /**
     * Evaluate a condition expression.
     *
     * Supports:
     * - Equality: ==, !=
     * - Comparison: >, <, >=, <=
     * - Logical: &&, ||
     *
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateCondition(string $condition, array $sources, array $aliases): bool
    {
        // Parse the condition
        // Support operators: ==, !=, >, <, >=, <=, &&, ||
        $operators = ['==', '!=', '>=', '<=', '>', '<', '&&', '||'];

        foreach ($operators as $operator) {
            if (str_contains($condition, $operator)) {
                $parts = self::splitByOperator($condition, $operator);

                if (2 === count($parts)) {
                    [$left, $right] = array_map('trim', $parts);

                    // Resolve left and right values
                    $leftValue = self::resolveValue($left, $sources, $aliases);
                    $rightValue = self::resolveValue($right, $sources, $aliases);

                    // Evaluate the operator
                    return self::compareValues($leftValue, $rightValue, $operator);
                }
            }
        }

        // If no operator found, treat as boolean value
        $value = self::resolveValue($condition, $sources, $aliases);
        return (bool)$value;
    }

    /**
     * Split a condition by operator, respecting quotes.
     *
     * @return array<int, string>
     */
    private static function splitByOperator(string $condition, string $operator): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $operatorLen = strlen($operator);

        for ($i = 0, $len = strlen($condition); $i < $len; $i++) {
            $char = $condition[$i];

            // Handle quotes
            if (('"' === $char || "'" === $char) && (0 === $i || '\\' !== $condition[$i - 1])) {
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
            if (!$inQuotes && substr($condition, $i, $operatorLen) === $operator) {
                $parts[] = $current;
                $current = '';
                $i += $operatorLen - 1; // Skip operator
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
     * Resolve a value from a string (can be a path, literal, or expression).
     *
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function resolveValue(string $value, array $sources, array $aliases): mixed
    {
        $value = trim($value);

        // String literal - remove quotes
        if ((str_starts_with($value, "'") && str_ends_with($value, "'"))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return substr($value, 1, -1);
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // Boolean/null keywords
        $lower = strtolower($value);
        if ('true' === $lower) {
            return true;
        }
        if ('false' === $lower) {
            return false;
        }
        if ('null' === $lower) {
            return null;
        }

        // Check for pipe filters in the value (e.g., "equipment.*.status | LOWER")
        // Only split if the pipe is not inside quotes
        if (str_contains($value, '|')) {
            $parts = ExpressionParser::splitByPipeFast($value);
            if (1 < count($parts)) {
                $path = trim(array_shift($parts));
                $resolved = self::resolveSourcePath($path, $sources);
                return FilterEngine::apply($resolved, $parts);
            }
        }

        // Otherwise, treat as a path and resolve from sources
        return self::resolveSourcePath($value, $sources);
    }

    /** Compare two values using an operator. */
    private static function compareValues(mixed $left, mixed $right, string $operator): bool
    {
        return match ($operator) {
            '==' => $left == $right,
            '!=' => $left != $right,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            '&&' => $left && $right,
            '||' => $left || $right,
            default => false,
        };
    }

    /**
     * Resolve parentheses placeholders in a value.
     *
     * Replaces __PAREN_0__, __PAREN_1__, etc. with evaluated values.
     *
     * @param array<int, array<string, mixed>|null> $parentheses Parsed parentheses contents
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function resolvePlaceholders(
        mixed $value,
        array $parentheses,
        array $sources,
        array $aliases
    ): mixed {
        if (!is_string($value)) {
            return $value;
        }

        // Check if value contains placeholders
        if (!str_contains($value, '__PAREN_')) {
            return $value;
        }

        // Replace each placeholder with its evaluated value
        foreach ($parentheses as $index => $parsed) {
            $placeholder = '__PAREN_' . $index . '__';
            if (str_contains($value, $placeholder)) {
                // Evaluate the parenthesis content
                if (null === $parsed) {
                    $evaluated = null;
                } else {
                    $evaluated = self::evaluateParsed($parsed, $sources, $aliases);
                }

                // If the value is ONLY the placeholder, return the evaluated value directly
                if ($value === $placeholder) {
                    return $evaluated;
                }

                // Otherwise, replace the placeholder in the string
                // Convert evaluated value to string for replacement
                // Wrap string values in quotes so resolveValue() treats them as literals
                // when used in conditions like: (path | LOWER) == "active"
                $replacement = match (true) {
                    is_string($evaluated) => '"' . str_replace('"', '\\"', $evaluated) . '"',
                    is_numeric($evaluated) => (string)$evaluated,
                    is_bool($evaluated) => $evaluated ? 'true' : 'false',
                    null === $evaluated => 'null',
                    default => json_encode($evaluated) ?: '',
                };

                $value = str_replace($placeholder, $replacement, $value);
            }
        }

        return $value;
    }

    /**
     * Evaluate a parsed expression.
     *
     * This is a helper method to evaluate already-parsed expressions (e.g., from parentheses).
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateParsed(array $parsed, array $sources, array $aliases): mixed
    {
        $type = $parsed['type'] ?? 'expression';

        return match ($type) {
            'null_coalescing' => self::evaluateNullCoalescing($parsed, $sources, $aliases),
            'elvis' => self::evaluateElvis($parsed, $sources, $aliases),
            'conditional' => self::evaluateConditional($parsed, $sources, $aliases),
            'alias' => self::evaluateAlias($parsed, $sources, $aliases),
            'expression' => self::evaluateExpression($parsed, $sources, $aliases),
            default => null,
        };
    }

    /**
     * Evaluate an alias expression.
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateAlias(array $parsed, array $sources, array $aliases): mixed
    {
        /** @var array<int, array<string, mixed>|null> $parentheses */
        $parentheses = $parsed['parentheses'] ?? [];

        // First try to resolve from aliases (already resolved values)
        /** @var string $path */
        $path = $parsed['path'] ?? '';
        $result = self::resolveAlias($path, $aliases);

        // If not found in aliases, try to resolve from sources
        if (null === $result) {
            $result = self::resolveSourcePath($path, $sources);
        }

        // Apply default if value is null
        if (null === $result && null !== $parsed['default']) {
            $default = self::resolvePlaceholders($parsed['default'], $parentheses, $sources, $aliases);
            $result = $default;
        }

        // Apply filters
        /** @var array<int, string> $filters */
        $filters = $parsed['filters'] ?? [];
        if ([] !== $filters) {
            return TemplateExpressionProcessor::applyFilters($result, $filters);
        }

        return $result;
    }

    /**
     * Evaluate a regular expression.
     *
     * @param array<string, mixed> $parsed
     * @param array<string, mixed> $sources
     * @param array<string, mixed> $aliases
     */
    private static function evaluateExpression(array $parsed, array $sources, array $aliases): mixed
    {
        /** @var array<int, array<string, mixed>|null> $parentheses */
        $parentheses = $parsed['parentheses'] ?? [];

        /** @var string $path */
        $path = $parsed['path'] ?? '';
        $resolved = self::resolveSourcePath($path, $sources);

        // Apply default if value is null
        if (null === $resolved && null !== $parsed['default']) {
            $default = self::resolvePlaceholders($parsed['default'], $parentheses, $sources, $aliases);
            $resolved = $default;
        }

        // Apply filters using TemplateExpressionProcessor (handles wildcards correctly)
        /** @var array<int, string> $filters */
        $filters = $parsed['filters'] ?? [];
        if ([] !== $filters) {
            // Check if this is a wildcard result (array with dot-path keys or numeric keys from wildcard)
            // If so, apply filters to each element instead of the whole array
            if (is_array($resolved) && str_contains($path, '*')) {
                $filtered = [];
                foreach ($resolved as $key => $item) {
                    $filtered[$key] = TemplateExpressionProcessor::applyFilters($item, $filters);
                }
                return $filtered;
            }

            return TemplateExpressionProcessor::applyFilters($resolved, $filters);
        }

        return $resolved;
    }
}
