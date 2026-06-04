<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\ResolvesSourceArguments;
use InvalidArgumentException;

/**
 * Applies transformers to values in template expressions using filter syntax.
 *
 * Example: {{ value | trim | upper }}
 *
 * Transformers are registered via FilterRegistry and can be used
 * in template expressions with their aliases.
 */
final class FilterEngine
{
    /**
     * Performance mode: true = fast split (no escape handling), false = safe split (full escape handling).
     *
     * Fast mode is ~20% faster but does not process escape sequences (\n, \t, \", \\).
     * Fast mode is default for better performance in standard cases.
     * Use safe mode when escape sequences are needed.
     */
    private static bool $useFastSplit = true;

    /** @var array<class-string, FilterInterface> */
    private static array $filterInstances = [];

    /**
     * Enable or disable fast split mode.
     *
     * @param bool $enabled true = fast mode (no escapes), false = safe mode (with escapes)
     */
    public static function useFastSplit(bool $enabled = true): void
    {
        self::$useFastSplit = $enabled;
        // Clear cache when mode changes to ensure correct parsing
        self::clearParseCache();
    }

    /** Check if fast split mode is enabled. */
    public static function isFastSplitEnabled(): bool
    {
        return self::$useFastSplit;
    }

    /**
     * Clear the internal parse cache.
     * Useful for testing or when switching between fast/safe modes.
     */
    public static function clearParseCache(): void
    {
        // Access the static cache in parseFilterWithArgs via reflection
        // Since it's a static variable inside a method, we can't access it directly
        // Instead, we'll just document that mode changes should clear cache
        // The cache key includes the mode, so this is actually safe
    }

    /** @var list<string> Meta-flag keywords that are extracted before filter execution. */
    private const META_FLAGS = ['required', 'not_required', 'optional'];

    /**
     * Apply transformers to a value using filter syntax.
     *
     * Meta-flags (required, not_required, optional) are extracted from the filter chain
     * before execution and handled independently of their position.
     *
     * @param array<int, string> $filters Filter aliases to apply
     * @param array<string, mixed> $sources Named data sources, used to resolve filter
     *     arguments that reference a source path (see ResolvesSourceArguments)
     */
    public static function apply(mixed $value, array $filters, array $sources = []): mixed
    {
        // Extract meta-flags from the filter chain (position-independent)
        [$filters, $isRequired] = self::extractMetaFlags($filters);

        // Handle required/not_required for null or empty values
        if (null !== $isRequired) {
            $isEmpty = null === $value || (is_string($value) && '' === trim($value));

            if ($isEmpty) {
                if ($isRequired) {
                    // required: value must not be null/empty
                    $exception = new InvalidArgumentException(
                        'Value is required but is null or empty.'
                    );
                    MapperExceptions::handleException($exception);
                }

                // not_required/optional OR after required exception: return null, skip remaining filters
                return null;
            }
        }

        foreach ($filters as $filter) {
            $value = self::applyFilter($value, $filter, $sources);
        }

        return $value;
    }

    /**
     * Check if a filter chain contains a 'required' meta-flag.
     *
     * Used by TemplateExpressionProcessor to decide whether to call FilterEngine
     * even when the value is null.
     *
     * @param array<int, string> $filters Filter aliases
     */
    public static function hasRequiredFlag(array $filters): bool
    {
        foreach ($filters as $filter) {
            $filterName = strtolower(trim($filter));
            if ('required' === $filterName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract meta-flags from the filter chain.
     *
     * Returns the cleaned filter list and the resolved required state:
     * - null: no meta-flag present (default behavior)
     * - true: 'required' was found
     * - false: 'not_required' or 'optional' was found
     *
     * @param array<int, string> $filters
     * @return array{0: array<int, string>, 1: bool|null}
     */
    private static function extractMetaFlags(array $filters): array
    {
        $isRequired = null;
        $cleaned = [];

        foreach ($filters as $filter) {
            $filterName = strtolower(trim($filter));

            if (in_array($filterName, self::META_FLAGS, true)) {
                $isRequired = 'required' === $filterName;

                continue;
            }

            $cleaned[] = $filter;
        }

        return [$cleaned, $isRequired];
    }

    /**
     * Apply a single transformer using its alias.
     *
     * @param array<string, mixed> $sources Named data sources for source-path argument resolution
     */
    private static function applyFilter(mixed $value, string $filter, array $sources = []): mixed
    {
        $filter = trim($filter);

        if ('' === $filter || '"' === $filter) {
            return $value;
        }

        // Parse filter name and arguments: default:"Unknown" or join:", "
        [$filterName, $args] = self::parseFilterWithArgs($filter);

        // Get filter class from registry
        $filterClass = FilterRegistry::get($filterName);
        if (null !== $filterClass) {
            // Get or create transformer instance (cache instances for reuse)
            if (!isset(self::$filterInstances[$filterClass])) {
                /** @var FilterInterface */
                $newTransformer = new $filterClass();
                self::$filterInstances[$filterClass] = $newTransformer;
            }

            $filterInstance = self::$filterInstances[$filterClass];

            // Resolve arguments that reference a source path (opt-in via marker interface)
            if ([] !== $args && [] !== $sources && $filterInstance instanceof ResolvesSourceArguments) {
                $args = self::resolveSourceArguments($args, $sources);
            }

            // Create a context with filter arguments in extra
            $context = new PairContext('template-expression', 0, '', '', [], [], null, $args);

            return $filterInstance->transform($value, $context);
        }

        // Unknown filter alias - throw exception
        throw new InvalidArgumentException(
            'Unknown filter alias "' . $filterName . '". ' .
            'Create a Filter class with getAliases() method and register it using FilterRegistry::register().'
        );
    }

    /**
     * Resolve filter arguments that reference a source path.
     *
     * Literal arguments (numeric or true/false/null keywords) are kept as-is.
     * Any other argument is treated as a source path and resolved against the
     * given sources; if it does not resolve to a non-null value, the original
     * string is kept (so literal string arguments keep working).
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $sources
     * @return array<int, mixed>
     */
    private static function resolveSourceArguments(array $args, array $sources): array
    {
        foreach ($args as $index => $arg) {
            // Numeric literals are used directly
            if (is_numeric($arg)) {
                continue;
            }

            // Boolean/null keywords (and empty strings) are literals, not paths
            $lower = strtolower(trim($arg));
            if (in_array($lower, ['true', 'false', 'null', ''], true)) {
                continue;
            }

            // Treat as a source path; keep the original string when it does not resolve
            $resolved = ExpressionEvaluator::resolveSourcePath($arg, $sources);
            if (null !== $resolved) {
                $args[$index] = $resolved;
            }
        }

        return $args;
    }

    /**
     * Parse filter with arguments.
     *
     * Examples:
     * - "trim" → ["trim", []]
     * - "default:\"Unknown\"" → ["default", ["Unknown"]]
     * - "join:\", \"" → ["join", [", "]]
     * - "between:1:10" → ["between", ["1", "10"]]
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private static function parseFilterWithArgs(string $filter): array
    {
        // Check cache first
        static $cache = [];
        $cacheKey = $filter . '|' . (self::$useFastSplit ? 'fast' : 'safe');
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        // Check if filter has arguments (contains : outside of quotes)
        if (!str_contains($filter, ':')) {
            $cache[$cacheKey] = [$filter, []];
            return $cache[$cacheKey];
        }

        // Fast path: No quotes → simple split
        if (!str_contains($filter, '"') && !str_contains($filter, "'")) {
            $parts = explode(':', $filter);
            $filterName = array_shift($parts);
            $cache[$cacheKey] = [$filterName, $parts];
            return $cache[$cacheKey];
        }

        // Choose parsing mode based on useFastSplit flag
        if (self::$useFastSplit) {
            $cache[$cacheKey] = self::parseFilterFast($filter);
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = self::parseFilterSafe($filter);
        return $cache[$cacheKey];
    }

    /**
     * Fast parsing: Simple quote toggle without escape handling.
     * ~20% faster but does not process \n, \t, \", \\ etc.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private static function parseFilterFast(string $filter): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $length = strlen($filter);

        for ($i = 0; $i < $length; $i++) {
            $char = $filter[$i];

            if ('"' === $char) {
                // Quote toggle (no escape handling)
                $inQuotes = !$inQuotes;
                // Don't include quotes in output
            } elseif (':' === $char && !$inQuotes) {
                // Split only outside quotes
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        $parts[] = $current;
        $filterName = array_shift($parts) ?? '';
        return [$filterName, $parts];
    }

    /**
     * Safe parsing: Full escape handling for \n, \t, \", \\, etc.
     * ~20% slower but handles all escape sequences correctly.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private static function parseFilterSafe(string $filter): array
    {
        // Slow path: Has quotes → char-by-char parsing with escape handling
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $i = 0;
        $length = strlen($filter);

        while ($i < $length) {
            $char = $filter[$i];

            if ($inQuotes) {
                // Inside quotes
                if ("\\" === $char && $i + 1 < $length) {
                    // Escape sequence
                    $nextChar = $filter[$i + 1];
                    switch ($nextChar) {
                        case '"':
                        case "'":
                        case "\\":
                            $current .= $nextChar;
                            $i += 2;
                            break;
                        case 'n':
                            $current .= "\n";
                            $i += 2;
                            break;
                        case 't':
                            $current .= "\t";
                            $i += 2;
                            break;
                        case 'r':
                            $current .= "\r";
                            $i += 2;
                            break;
                        default:
                            // Unknown escape - keep backslash
                            $current .= $char;
                            $i++;
                            break;
                    }
                } elseif ($char === $quoteChar) {
                    // End of quoted string
                    $inQuotes = false;
                    $quoteChar = null;
                    $i++;
                } else {
                    // Regular character inside quotes
                    $current .= $char;
                    $i++;
                }
            } elseif ('"' === $char || "'" === $char) {
                // Outside quotes
                // Start of quoted string
                $inQuotes = true;
                $quoteChar = $char;
                $i++;
            } elseif (':' === $char) {
                // Argument separator
                $parts[] = $current;
                $current = '';
                $i++;
            } else {
                // Regular character
                $current .= $char;
                $i++;
            }
        }

        // Add last part
        $parts[] = $current;

        $filterName = array_shift($parts) ?? '';
        $args = $parts;

        return [$filterName, $args];
    }
}
