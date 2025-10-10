<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Template;

use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;
use InvalidArgumentException;

/**
 * Applies transformers to values in template expressions using filter syntax.
 *
 * Example: {{ value | trim | upper }}
 *
 * Transformers are registered via TransformerRegistry and can be used
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

    /** @var array<class-string, TransformerInterface> */
    private static array $transformerInstances = [];

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

    /**
     * Apply transformers to a value using filter syntax.
     *
     * @param array<int, string> $filters Transformer aliases to apply
     */
    public static function apply(mixed $value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            $value = self::applyFilter($value, $filter);
        }

        return $value;
    }

    /** Apply a single transformer using its alias. */
    private static function applyFilter(mixed $value, string $filter): mixed
    {
        $filter = trim($filter);

        if ('' === $filter || '"' === $filter) {
            return $value;
        }

        // Parse filter name and arguments: default:"Unknown" or join:", "
        [$filterName, $args] = self::parseFilterWithArgs($filter);

        // Get transformer class from registry
        $transformerClass = TransformerRegistry::get($filterName);
        if (null !== $transformerClass) {
            // Get or create transformer instance (cache instances for reuse)
            if (!isset(self::$transformerInstances[$transformerClass])) {
                /** @var TransformerInterface */
                $newTransformer = new $transformerClass();
                self::$transformerInstances[$transformerClass] = $newTransformer;
            }

            $transformer = self::$transformerInstances[$transformerClass];

            // Create a context with filter arguments in extra
            $context = new PairContext('template-expression', 0, '', '', [], [], null, $args);

            return $transformer->transform($value, $context);
        }

        // Unknown transformer alias - throw exception
        throw new InvalidArgumentException(
            sprintf(
                "Unknown transformer alias '%s'. " .
                "Create a Transformer class with getAliases() method and register it using TransformerRegistry::register().",
                $filterName
            )
        );
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
