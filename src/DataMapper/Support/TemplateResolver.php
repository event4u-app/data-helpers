<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\Converters\JsonConverter;
use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\DataMapper\Template\ExpressionEvaluator;
use event4u\DataHelpers\DataMapper\Template\ExpressionParser;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Support\StringFormatDetector;
use InvalidArgumentException;
use Throwable;

/**
 * Resolves template-based mapping operations.
 *
 * This class handles the resolution of templates that reference values by alias.path notation.
 * It supports wildcards, operators (WHERE, ORDER BY, etc.), and multi-pass alias resolution.
 */
final class TemplateResolver
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
            $template = self::convertStringToArray($template, 'template');
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

                    // Free memory: result not needed anymore
                    unset($result);

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

                    // Free memory: normalized not needed anymore
                    unset($normalized);

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

        /** @var array<string, mixed> $node */

        // Check if this is a wildcard mapping with WHERE clause
        if (self::hasWildcardMapping($node)) {
            return self::resolveWildcardMapping($node, $sources, $skipNull, $reindexWildcard, $aliases);
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
            $data = self::convertStringToArray($data, 'data');
        }

        if (is_string($template)) {
            $template = self::convertStringToArray($template, 'template');
        }

        return self::applyTemplateNodeToTargets($data, $template, $targets, $skipNull, $reindexWildcard);
    }

    /**
     * Convert a string to an array using the appropriate converter based on format detection.
     *
     * @param string $string The string to convert
     * @param string $context Context for error messages (e.g., 'data', 'template')
     * @return array<string, mixed>
     * @throws InvalidArgumentException If the string format is not supported or invalid
     */
    private static function convertStringToArray(string $string, string $context = 'string'): array
    {
        $format = StringFormatDetector::detectFormat($string);

        try {
            return match ($format) {
                'json' => (new JsonConverter())->toArray($string),
                'xml' => (new XmlConverter())->toArray($string),
                default => throw new InvalidArgumentException(
                    sprintf('Unsupported format for %s. Expected JSON or XML.', $context)
                ),
            };
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Invalid %s %s: ',
                $format,
                $context
            ) . $throwable->getMessage(), 0, $throwable);
        }
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
                DataMutator::make($target)->set($path ?? '', $dataNode);
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
                DataMutator::make($target)->set($path, $value);
                return $target;
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
                    DataMutator::make($target)->set($itemPath, $itemValue);
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

    /**
     * Check if array has wildcard mapping structure (contains '*' key).
     *
     * @param array<string, mixed> $array
     */
    private static function hasWildcardMapping(array $array): bool
    {
        return isset($array['*']);
    }

    /**
     * Resolve wildcard mapping with optional operators (WHERE, ORDER BY, etc.).
     *
     * @param array<string, mixed> $mapping Wildcard mapping (may contain WHERE, ORDER BY, *)
     * @param array<string, mixed> $sources Source data
     * @param bool $skipNull Skip null values
     * @param bool $reindexWildcard Reindex wildcard results
     * @param array<string, mixed> $aliases Already resolved aliases
     * @return array<int|string, mixed> Resolved wildcard array
     */
    private static function resolveWildcardMapping(
        array $mapping,
        array $sources,
        bool $skipNull,
        bool $reindexWildcard,
        array $aliases
    ): array {
        // Extract operators and wildcard template
        $operators = [];
        $wildcardTemplate = null;

        foreach ($mapping as $key => $value) {
            if ('*' === $key) {
                $wildcardTemplate = $value;
            } elseif (WildcardOperatorRegistry::has((string)$key)) {
                $operators[] = [
                    'name' => (string)$key,
                    'config' => $value,
                ];
            }
        }

        if (null === $wildcardTemplate) {
            return [];
        }

        // First, we need to determine the source wildcard path
        // by finding the first wildcard expression in the template
        $sourceWildcardPath = self::findWildcardPath($wildcardTemplate);

        if (null === $sourceWildcardPath) {
            // No wildcard path found - just resolve normally
            $result = self::resolveTemplateNode($wildcardTemplate, $sources, $skipNull, $reindexWildcard, $aliases);
            return is_array($result) ? $result : [];
        }

        // Evaluate the wildcard path to get all items
        $wildcardData = ExpressionEvaluator::evaluate($sourceWildcardPath, $sources, $aliases);

        if (!is_array($wildcardData)) {
            return [];
        }

        // Normalize wildcard array
        $wildcardData = WildcardHandler::normalizeWildcardArray($wildcardData);

        // Apply operators in order
        foreach ($operators as $operator) {
            $handler = WildcardOperatorRegistry::get($operator['name']);
            $wildcardData = $handler($wildcardData, $operator['config'], $sources, $aliases);

            // Update sources with the modified wildcard data for GROUP BY operator
            // (GROUP BY adds aggregation fields that need to be available in template expressions)
            if ('GROUP BY' === $operator['name']) {
                $sourceKey = self::extractSourceKey($sourceWildcardPath);
                if (null !== $sourceKey) {
                    // Check if wildcardData contains arrays (items with fields)
                    // If so, update sources to make new fields available in template expressions
                    $hasArrayItems = false;
                    foreach ($wildcardData as $item) {
                        if (is_array($item)) {
                            $hasArrayItems = true;
                            break;
                        }
                    }

                    if ($hasArrayItems) {
                        $sources[$sourceKey] = $wildcardData;
                    }
                }
            }
        }

        // Now map each item through the template
        $result = [];
        $outputIndex = 0;

        foreach ($wildcardData as $index => $itemValue) {
            // Resolve the template for this item, replacing * with actual index
            $resolved = self::resolveWildcardTemplateForIndex(
                $wildcardTemplate,
                $sources,
                $aliases,
                $index
            );

            if ($skipNull && null === $resolved) {
                continue;
            }

            // Use reindexed or original index
            $targetIndex = $reindexWildcard ? $outputIndex : $index;
            $result[$targetIndex] = $resolved;

            if ($reindexWildcard) {
                $outputIndex++;
            }
        }

        return $result;
    }

    /**
     * Find the first wildcard path in a template.
     *
     * @param mixed $template Template to search
     * @return string|null First wildcard path found, or null
     */
    private static function findWildcardPath(mixed $template): ?string
    {
        if (is_string($template) && str_contains($template, '*')) {
            return $template;
        }

        if (is_array($template)) {
            foreach ($template as $value) {
                $found = self::findWildcardPath($value);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Extract source key from wildcard path.
     *
     * Examples:
     * - "{{ sales.*.category }}" => "sales"
     * - "{{ items.*.name }}" => "items"
     *
     * @param string $wildcardPath Wildcard path
     * @return string|null Source key or null
     */
    private static function extractSourceKey(string $wildcardPath): ?string
    {
        // Remove {{ }} if present
        $path = trim($wildcardPath);
        if (str_starts_with($path, '{{') && str_ends_with($path, '}}')) {
            $path = trim(substr($path, 2, -2));
        }

        // Remove filters if present
        if (str_contains($path, '|')) {
            [$path] = explode('|', $path, 2);
            $path = trim($path);
        }

        // Extract first segment before .*
        if (str_contains($path, '.*')) {
            [$sourceKey] = explode('.*', $path, 2);
            return trim($sourceKey);
        }

        return null;
    }

    /**
     * Resolve wildcard template for a specific index.
     *
     * @param mixed $template Template to resolve
     * @param array<string, mixed> $sources Source data
     * @param array<string, mixed> $aliases Aliases
     * @param int|string $index Current wildcard index
     * @return mixed Resolved template
     */
    private static function resolveWildcardTemplateForIndex(
        mixed $template,
        array $sources,
        array $aliases,
        int|string $index
    ): mixed {
        if (is_string($template)) {
            // If it's an expression with wildcard, replace * with index and evaluate
            if (ExpressionParser::hasExpression($template) && str_contains($template, '*')) {
                $indexedExpression = str_replace('*', (string)$index, $template);
                return ExpressionEvaluator::evaluate($indexedExpression, $sources, $aliases);
            }

            // Regular expression without wildcard
            if (ExpressionParser::hasExpression($template)) {
                return ExpressionEvaluator::evaluate($template, $sources, $aliases);
            }

            // Literal string
            return $template;
        }

        if (is_array($template)) {
            $result = [];
            foreach ($template as $key => $value) {
                $resolved = self::resolveWildcardTemplateForIndex($value, $sources, $aliases, $index);
                $result[$key] = $resolved;
            }
            return $result;
        }

        return $template;
    }
}
