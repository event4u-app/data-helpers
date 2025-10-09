<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\DataMapper\Support\TemplateParser;
use event4u\DataHelpers\DataMapper\Support\WildcardHandler;
use event4u\DataHelpers\DataMapper\Template\ExpressionEvaluator;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;
use event4u\DataHelpers\DataMutator;
use InvalidArgumentException;

/**
 * Handles template-based mapping operations.
 */
class TemplateMapper
{
    /**
     * Build a new array from a template that references values by alias.path notation.
     *
     * Template may be an array or JSON string. Each leaf value that looks like "alias.path"
     * is resolved against the provided sources map. Unknown aliases are treated as literals.
     *
     * @param array<string,mixed>|string $template Template describing the output structure (array or JSON)
     * @param array<string,mixed> $sources Map of source name => source data (array/object/model/collection)
     * @param bool $skipNull Skip null values (omit keys where a resolved value is null)
     * @param bool $reindexWildcard Reindex wildcard results sequentially (0..n-1) instead of preserving original numeric keys
     * @return array<string,mixed>
     */
    public static function mapFromTemplate(
        array|string $template,
        array $sources,
        bool $skipNull = true,
        bool $reindexWildcard = false,
    ): array {
        if (is_string($template)) {
            $template = json_decode($template, true);
            if (!is_array($template)) {
                throw new InvalidArgumentException('Invalid JSON template');
            }
        }

        // Resolve template with multi-pass alias resolution
        return self::resolveTemplateWithAliases($template, $sources, $skipNull, $reindexWildcard);
    }

    /**
     * Resolve template with multi-pass alias resolution.
     * This allows alias references to work regardless of order.
     *
     * @param array<string,mixed> $template
     * @param array<string,mixed> $sources
     * @return array<string,mixed>
     */
    private static function resolveTemplateWithAliases(
        array $template,
        array $sources,
        bool $skipNull,
        bool $reindexWildcard,
    ): array {
        // First pass: resolve all non-alias references
        $result = [];
        $pendingAliases = [];

        foreach ($template as $key => $value) {
            if (is_string($value) && ExpressionParser::hasExpression($value)) {
                $parsed = ExpressionParser::parse($value);
                if (null !== $parsed && 'alias' === $parsed['type']) {
                    // This is an alias reference - defer it
                    $pendingAliases[$key] = $value;
                    continue;
                }
            }

            // Resolve non-alias values
            $resolved = self::resolveTemplateNode($value, $sources, $skipNull, $reindexWildcard, $result);
            if ($skipNull && null === $resolved) {
                continue;
            }
            $result[$key] = $resolved;
        }

        // Second pass: resolve alias references (with multiple iterations if needed)
        $maxIterations = 10; // Prevent infinite loops
        $iteration = 0;
        while ([] !== $pendingAliases && $iteration < $maxIterations) {
            $stillPending = [];
            foreach ($pendingAliases as $key => $value) {
                $resolved = self::resolveTemplateNode($value, $sources, $skipNull, $reindexWildcard, $result);
                if (null === $resolved && $skipNull) {
                    // Alias not yet resolved - try again in next iteration
                    $stillPending[$key] = $value;
                } else {
                    $result[$key] = $resolved;
                }
            }
            $pendingAliases = $stillPending;
            $iteration++;
        }

        // If there are still pending aliases after max iterations, resolve them as null
        foreach (array_keys($pendingAliases) as $key) {
            if (!$skipNull) {
                $result[$key] = null;
            }
        }

        return $result;
    }

    /**
     * Resolve a template node recursively.
     *
     * @param array<string,mixed> $sources
     * @param array<string,mixed> $aliases Already resolved aliases (for @references)
     */
    private static function resolveTemplateNode(
        mixed $node,
        array $sources,
        bool $skipNull,
        bool $reindexWildcard,
        array $aliases,
    ): mixed {
        // Scalar or null: check if it's a source reference or expression
        if (is_string($node)) {
            // Only {{ }} expressions are treated as dynamic source references
            if (ExpressionParser::hasExpression($node)) {
                $result = ExpressionEvaluator::evaluate($node, $sources, $aliases);

                // Check if result is a wildcard array (has dot-path keys)
                if (is_array($result) && self::isWildcardArray($result)) {
                    // First normalize the array (convert dot-path keys to numeric indices)
                    $normalized = WildcardHandler::normalizeWildcardArray($result);

                    // Then apply skipNull and reindex
                    $filtered = [];
                    WildcardHandler::iterateWildcardItems(
                        $normalized,
                        $skipNull,
                        $reindexWildcard,
                        null,
                        function(int|string $index, mixed $value) use (&$filtered): true {
                            $filtered[$index] = $value;
                            return true;
                        }
                    );

                    return $filtered;
                }

                // Return result (can be null if path doesn't exist)
                return $result;
            }

            // Check if it's an alias reference (simple key without dots)
            if (!str_contains($node, '.') && isset($aliases[$node])) {
                return $aliases[$node];
            }

            // Not a {{ }} expression and not an alias: treat as static literal string
            return $node;
        }

        if (!is_array($node)) {
            return $node;
        }

        // Array: recursively resolve each element
        $result = [];
        foreach ($node as $key => $value) {
            // Pass current result as aliases for nested @references
            // Avoid array_merge in loop - use + operator for better performance
            $currentAliases = $result + $aliases;
            $resolved = self::resolveTemplateNode($value, $sources, $skipNull, $reindexWildcard, $currentAliases);

            // Skip null values if requested
            if ($skipNull && null === $resolved) {
                continue;
            }

            $result[$key] = $resolved;
        }

        return $result;
    }

    /**
     * Extract path from template string.
     * Supports both '{{ alias.path }}' and 'alias.path' formats.
     */
    private static function extractPathFromTemplate(string $value): string
    {
        return TemplateParser::extractPath($value);
    }

    /**
     * Parse a source reference like "alias" or "alias.path.to.value".
     * Returns [alias, path|null] or [null, null] if not a reference.
     *
     * @return array{0:null|string,1:null|string}
     */
    private static function parseSourceReference(string $value): array
    {
        if ('' === $value) {
            return [null, null];
        }

        $parts = explode('.', $value, 2);
        $alias = $parts[0];
        $path = $parts[1] ?? null;

        return [$alias, $path];
    }

    /**
     * Check if an array looks like a wildcard result (has dot-path keys).
     *
     * @param array<int|string, mixed> $array
     */
    private static function isWildcardResult(array $array): bool
    {
        foreach (array_keys($array) as $key) {
            if (is_string($key) && str_contains($key, '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply values (matching the template shape) back into real targets using alias paths.
     *
     * @param array<string,mixed>|string $data Data with values matching the template shape (array or JSON)
     * @param array<string,mixed>|string $template Template describing destination alias.paths (array or JSON)
     * @param array<string,mixed> $targets Map of alias => target (array/object/model/collection)
     * @return array<string,mixed>                  Updated targets map
     */
    public static function mapToTargetsFromTemplate(
        array|string $data,
        array|string $template,
        array $targets,
        bool $skipNull = true,
        bool $reindexWildcard = false,
    ): array {
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid JSON data');
            }
        }

        if (is_string($template)) {
            $template = json_decode($template, true);
            if (!is_array($template)) {
                throw new InvalidArgumentException('Invalid JSON template');
            }
        }

        return self::applyTemplateNodeToTargets($data, $template, $targets, $skipNull, $reindexWildcard);
    }

    /**
     * Apply a template node to targets recursively.
     *
     * @param array<string,mixed> $targets
     * @return array<string,mixed>
     */
    private static function applyTemplateNodeToTargets(
        mixed $dataNode,
        mixed $templateNode,
        array $targets,
        bool $skipNull,
        bool $reindexWildcard,
    ): array {
        // If template is a string reference, write dataNode to that alias.path
        if (is_string($templateNode)) {
            // Extract path from {{ }} if present, otherwise use as-is
            $cleanPath = self::extractPathFromTemplate($templateNode);

            [$alias, $path] = self::parseSourceReference($cleanPath);
            if (null === $alias || !isset($targets[$alias])) {
                // Unknown alias or no target: skip
                return $targets;
            }

            $target = $targets[$alias];

            // If path contains wildcard and dataNode is array, write each element
            if (null !== $path && str_contains($path, '*') && is_array($dataNode)) {
                if (is_array($target) || is_object($target)) {
                    $target = self::writeToAliasWithWildcards($target, $path, $dataNode, $skipNull, $reindexWildcard);
                }
            } elseif (is_array($target) || is_object($target)) {
                // Simple write
                $target = DataMutator::set($target, $path ?? '', $dataNode);
            }

            $targets[$alias] = $target;

            return $targets;
        }

        // If template is array, recursively apply each element
        if (is_array($templateNode)) {
            foreach ($templateNode as $key => $templateValue) {
                $dataValue = is_array($dataNode) && array_key_exists($key, $dataNode) ? $dataNode[$key] : null;

                // Skip null values if requested
                if ($skipNull && null === $dataValue) {
                    continue;
                }

                $targets = self::applyTemplateNodeToTargets(
                    $dataValue,
                    $templateValue,
                    $targets,
                    $skipNull,
                    $reindexWildcard
                );
            }
        }

        return $targets;
    }

    /** Write array values to target using wildcard path. */
    private static function writeToAliasWithWildcards(
        mixed $target,
        string $path,
        mixed $value,
        bool $skipNull,
        bool $reindexWildcard,
    ): mixed {
        if (!is_array($value)) {
            if (is_array($target) || is_object($target)) {
                return DataMutator::set($target, $path, $value);
            }

            return $target;
        }

        // Normalize wildcard array if needed
        if (self::isWildcardResult($value)) {
            $value = WildcardHandler::normalizeWildcardArray($value);
        }

        // Iterate and write each item
        WildcardHandler::iterateWildcardItems(
            $value,
            $skipNull,
            $reindexWildcard,
            null,
            function(int|string $index, mixed $itemValue) use (&$target, $path): true {
                $itemPath = str_replace('*', (string)$index, $path);
                if (is_array($target) || is_object($target)) {
                    $target = DataMutator::set($target, $itemPath, $itemValue);
                }

                return true;
            }
        );

        return $target;
    }

    /**
     * Check if an array is a wildcard result (has dot-path keys).
     *
     * @param array<int|string, mixed> $array
     */
    private static function isWildcardArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        // Check if any key contains a dot (indicating a dot-path key)
        foreach (array_keys($array) as $key) {
            if (is_string($key) && str_contains($key, '.')) {
                return true;
            }
        }

        return false;
    }
}
