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
            /** @var TransformerInterface $transformer */
            $transformer = new $transformerClass();

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
        // Check if filter has arguments (contains : outside of quotes)
        if (!str_contains($filter, ':')) {
            return [$filter, []];
        }

        // Fast path: No quotes → simple split
        if (!str_contains($filter, '"') && !str_contains($filter, "'")) {
            $parts = explode(':', $filter);
            $filterName = array_shift($parts);
            return [$filterName, $parts];
        }

        // Regex path: Has quotes → use regex to split respecting quoted strings
        // Match: "..." or '...' (with escape support) or non-colon sequences
        preg_match_all('/
            (?:
                "(?:[^"\\\\]|\\\\.)*"     # Double quoted string with escapes
                |
                \'(?:[^\'\\\\]|\\\\.)*\'  # Single quoted string with escapes
                |
                [^:]+                     # Non-colon characters
            )
        /x', $filter, $matches);

        $parts = $matches[0];

        // Remove quotes from arguments
        $parts = array_map(function(string $part): string {
            $part = trim($part);
            // Remove surrounding quotes if present
            if ((str_starts_with($part, '"') && str_ends_with($part, '"'))
                || (str_starts_with($part, "'") && str_ends_with($part, "'"))) {
                return substr($part, 1, -1);
            }
            return $part;
        }, $parts);

        $filterName = array_shift($parts) ?? '';
        $args = $parts;

        return [$filterName, $args];
    }
}
