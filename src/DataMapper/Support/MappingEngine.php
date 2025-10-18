<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\Helpers\DotPathHelper;
use event4u\DataHelpers\Support\EntityHelper;

/**
 * Core mapping engine that handles the actual mapping logic.
 */
class MappingEngine
{
    /**
     * Check if mapping is a nested structure (target structure with source paths as values).
     *
     * Example:
     *   ['profile' => ['name' => 'user.name', 'email' => 'user.email']]
     *
     * This is different from structured mapping which has numeric keys and 'source'/'target' keys.
     *
     * @param array<int|string, mixed> $mapping
     */
    public static function isNestedMapping(array $mapping): bool
    {
        // Structured mapping has numeric keys (0, 1, 2, ...)
        // Nested mapping has string keys (profile, user, ...)
        foreach ($mapping as $key => $value) {
            // If key is numeric, it's structured mapping, not nested
            if (is_int($key)) {
                return false;
            }

            // If value is array, it could be nested mapping
            if (is_array($value)) {
                // Check if it's a structured mapping entry (has 'source' or 'target' keys)
                return !(isset($value['source']) || isset($value['target']) || isset($value['mapping']));
            }
        }

        return false;
    }

    /**
     * Check if mapping is a simple associative array (dot-path → dot-path).
     *
     * @param array<int|string, mixed> $mapping
     */
    public static function isSimpleMapping(array $mapping): bool
    {
        foreach ($mapping as $value) {
            if (!is_string($value)) {
                return false;
            }
        }

        return [] !== $mapping;
    }

    /**
     * Flatten nested mapping structure to simple target => source format.
     *
     * Converts:
     *   ['profile' => ['name' => 'user.name', 'email' => 'user.email']]
     * To:
     *   ['profile.name' => 'user.name', 'profile.email' => 'user.email']
     *
     * @param array<int|string, mixed> $mapping
     * @return array<string, string>
     */
    public static function flattenNestedMapping(array $mapping, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($mapping as $targetKey => $value) {
            $targetPath = '' === $prefix ? (string)$targetKey : $prefix . '.' . $targetKey;

            if (is_array($value)) {
                // Recursively flatten nested arrays
                // Avoid array_merge in loop - use foreach instead for better performance
                foreach (self::flattenNestedMapping($value, $targetPath) as $k => $v) {
                    $flattened[$k] = $v;
                }
            } elseif (is_string($value)) {
                // Leaf node: value is the source path
                // Keep target => source format
                $flattened[$targetPath] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Ensure a mixed value is a valid target (array|object) for DataMutator.
     *
     * @return array<int|string, mixed>|object
     */
    public static function asTarget(mixed $candidate): array|object
    {
        if (!is_array($candidate) && !is_object($candidate)) {
            return [];
        }

        return $candidate;
    }

    /**
     * Process simple mapping (associative array of target => source paths).
     *
     * @param array<string, string> $mapping
     * @param array<string, mixed> $hooks
     */
    public static function processSimpleMapping(
        mixed $source,
        mixed $target,
        array $mapping,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
        bool $trimValues,
        bool $caseInsensitiveReplace,
    ): mixed {
        // Early return for empty mapping
        if ([] === $mapping) {
            return $target;
        }

        $accessor = new DataAccessor($source);
        $hasHooks = !HookInvoker::isEmpty($hooks);

        // Global hook: beforeAll (only if hooks exist)
        if ($hasHooks) {
            HookInvoker::invokeHooks(
                $hooks,
                DataMapperHook::BeforeAll->value,
                new AllContext('simple', $mapping, $source, $target)
            );
        }

        $mappingIndex = 0;
        foreach ($mapping as $targetPath => $sourcePath) {
            // Create context only if hooks exist
            $pairContext = $hasHooks ? new PairContext(
                'simple',
                $mappingIndex,
                (string)$sourcePath,
                (string)$targetPath,
                $source,
                $target
            ) : null;

            if ($hasHooks && $pairContext instanceof PairContext && HookInvoker::invokeHooks(
                $hooks,
                DataMapperHook::BeforePair->value,
                $pairContext
            ) === false) {
                $mappingIndex++;

                continue;
            }

            // Check if sourcePath is a {{ }} expression or a static value
            $isExpression = is_string($sourcePath) && preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $sourcePath, $matches);

            if ($isExpression) {
                // Extract the actual path from {{ }} and get value from source
                $actualSourcePath = trim($matches[1]);
                $value = $accessor->get($actualSourcePath);
            } else {
                // Not a {{ }} expression: treat as static literal value
                $value = $sourcePath;
                $actualSourcePath = null;
            }

            // Skip null values by default
            if ($skipNull && null === $value) {
                $mappingIndex++;

                continue;
            }

            // beforeTransform (only if hooks exist)
            if ($hasHooks && $pairContext instanceof PairContext) {
                $value = HookInvoker::invokeValueHook(
                    $hooks,
                    DataMapperHook::BeforeTransform->value,
                    $pairContext,
                    $value
                );
            }

            // Handle wildcard values (always arrays with dot-path keys)
            // Use cached wildcard check - only for expressions
            if (is_array($value) && $isExpression && null !== $actualSourcePath && DotPathHelper::containsWildcard(
                $actualSourcePath
            )) {
                // Normalize wildcard array (flatten dot-path keys to simple list)
                $normalizedValue = WildcardHandler::normalizeWildcardArray($value);

                // Free memory: original value not needed anymore
                unset($value);

                $target = self::processWildcardMapping(
                    $normalizedValue,
                    $target,
                    $sourcePath,
                    $targetPath,
                    $source,
                    $mappingIndex,
                    $skipNull,
                    $reindexWildcard,
                    $hooks,
                    $pairContext,
                    null,  // $transformFn - not available in simple mapping
                    null,  // $replaceMap - not available in simple mapping
                    $trimValues,
                    $caseInsensitiveReplace
                );

                // Free memory: normalized value not needed anymore
                unset($normalizedValue);
            } else {
                $target = self::processSingleMapping(
                    $value,
                    $target,
                    $sourcePath,
                    $targetPath,
                    $source,
                    $mappingIndex,
                    $hooks,
                    $pairContext
                );

                // Free memory: value not needed anymore
                unset($value);
            }

            // afterPair hook (only if hooks exist)
            if ($hasHooks && $pairContext instanceof PairContext) {
                HookInvoker::invokeHooks($hooks, DataMapperHook::AfterPair->value, $pairContext);
            }
            $mappingIndex++;
        }

        // Global hook: afterAll (only if hooks exist)
        if ($hasHooks) {
            HookInvoker::invokeHooks(
                $hooks,
                DataMapperHook::AfterAll->value,
                new AllContext('simple', $mapping, $source, $target)
            );
        }

        return $target;
    }

    /**
     * Process wildcard mapping (source path contains *).
     *
     * This method handles the logic for mapping wildcard values to target paths.
     * It distinguishes between two cases:
     * 1. Target path has NO wildcard: collects all values into an array
     * 2. Target path HAS wildcard: writes each value individually
     *
     * @param array<int|string, mixed> $value Normalized wildcard array (numeric indices)
     * @param mixed $target The target data structure
     * @param string $sourcePath The source path (for context)
     * @param string $targetPath The target path (may contain wildcard)
     * @param mixed $source The source data (for context)
     * @param int $mappingIndex Current mapping index
     * @param bool $skipNull Whether to skip null values
     * @param bool $reindexWildcard Whether to reindex wildcard results
     * @param array<string, mixed> $hooks Optional hooks
     * @param PairContext|null $pairContext Optional pair context for hooks
     * @param (callable(mixed): mixed)|null $transformFn Optional custom transformation function
     * @param array<string, mixed>|null $replaceMap Optional replacement map
     * @param bool $trimValues Whether to trim string values
     * @param bool $caseInsensitiveReplace Whether to use case-insensitive replacement
     * @return mixed The updated target
     */
    public static function processWildcardMapping(
        array $value,
        mixed $target,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        int $mappingIndex,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
        ?PairContext $pairContext,
        ?callable $transformFn = null,
        ?array $replaceMap = null,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false
    ): mixed {
        // Check if target path contains wildcard
        $targetHasWildcard = DotPathHelper::containsWildcard($targetPath);

        // If target has no wildcard, collect all values into an array
        if (!$targetHasWildcard) {
            $collectedValues = [];

            WildcardHandler::iterateWildcardItems(
                $value,
                $skipNull,
                $reindexWildcard,
                function(int|string $_i, string $reason) use (&$mappingIndex): void {
                    if ('null' === $reason) {
                        $mappingIndex++;
                    }
                },
                function(int|string $wildcardIndex, mixed $itemValue) use (
                    &$collectedValues,
                    $hooks,
                    $pairContext,
                    $reindexWildcard,
                    $transformFn,
                    $replaceMap,
                    $trimValues,
                    $caseInsensitiveReplace
                ): bool {
                    // Apply all transformations (custom filters, trimming, replacement)
                    $itemValue = ValueTransformer::processValue(
                        $itemValue,
                        $transformFn,
                        $replaceMap,
                        $trimValues,
                        $caseInsensitiveReplace
                    );

                    // Only set wildcardIndex if pairContext exists
                    if ($pairContext instanceof PairContext) {
                        $pairContext->wildcardIndex = $wildcardIndex;
                        $itemValue = HookInvoker::invokeValueHook(
                            $hooks,
                            DataMapperHook::AfterTransform->value,
                            $pairContext,
                            $itemValue
                        );
                    }

                    // Collect values into array
                    if ($reindexWildcard) {
                        $collectedValues[] = $itemValue;
                    } else {
                        $collectedValues[$wildcardIndex] = $itemValue;
                    }

                    return true;
                }
            );

            // Write the collected array to target
            $writeContext = new WriteContext(
                'simple',
                $mappingIndex,
                $sourcePath,
                $targetPath,
                $source,
                $target,
                $targetPath,
                null
            );

            $writeValue = $collectedValues;
            if (!HookInvoker::isEmpty($hooks)) {
                $writeValue = HookInvoker::invokeValueHook(
                    $hooks,
                    DataMapperHook::BeforeWrite->value,
                    $writeContext,
                    $collectedValues
                );
            }

            // Free memory: collectedValues not needed anymore
            unset($collectedValues);

            if ('__skip__' !== $writeValue) {
                // Check if target is an entity and targetPath is a relation
                // If so, use EntityHelper::setAttribute which will handle relation mapping
                if (is_object($target) && EntityHelper::isEntity($target)) {
                    // Extract first segment of target path (e.g., 'departments' from 'departments.name')
                    $segments = DotPathHelper::segments($targetPath);
                    $firstSegment = $segments[0] ?? '';

                    // Free memory: segments not needed anymore
                    unset($segments);

                    if ($firstSegment && EntityHelper::isRelation(
                        $target,
                        $firstSegment
                    )) {
                        // This is a relation - let EntityHelper handle it
                        EntityHelper::setAttribute($target, $firstSegment, $writeValue);

                        if (!HookInvoker::isEmpty($hooks)) {
                            return HookInvoker::invokeTargetHook(
                                $hooks,
                                DataMapperHook::AfterWrite->value,
                                $writeContext,
                                $writeValue,
                                $target
                            );
                        }

                        return $target;
                    }
                }

                // Normal write to target
                $target = DataMutator::set(
                    self::asTarget($target),
                    $targetPath,
                    $writeValue
                );

                if (!HookInvoker::isEmpty($hooks)) {
                    $target = HookInvoker::invokeTargetHook(
                        $hooks,
                        DataMapperHook::AfterWrite->value,
                        $writeContext,
                        $writeValue,
                        $target
                    );
                }
            }

            return $target;
        }

        // Target has wildcard - write each value individually
        WildcardHandler::iterateWildcardItems(
            $value,
            $skipNull,
            $reindexWildcard,
            function(int|string $_i, string $reason) use (&$mappingIndex): void {
                if ('null' === $reason) {
                    $mappingIndex++;
                }
            },
            function(int|string $wildcardIndex, mixed $itemValue) use (
                &$target,
                $hooks,
                $pairContext,
                $sourcePath,
                $targetPath,
                $source,
                $mappingIndex,
                $transformFn,
                $replaceMap,
                $trimValues,
                $caseInsensitiveReplace
            ): bool {
                // Apply all transformations (custom filters, trimming, replacement)
                $itemValue = ValueTransformer::processValue(
                    $itemValue,
                    $transformFn,
                    $replaceMap,
                    $trimValues,
                    $caseInsensitiveReplace
                );

                // Only set wildcardIndex if pairContext exists
                if ($pairContext instanceof PairContext) {
                    $pairContext->wildcardIndex = $wildcardIndex;
                    $itemValue = HookInvoker::invokeValueHook(
                        $hooks,
                        DataMapperHook::AfterTransform->value,
                        $pairContext,
                        $itemValue
                    );
                }

                $resolvedTargetPath = preg_replace('/\*/', (string)$wildcardIndex, $targetPath, 1);
                $writeContext = new WriteContext(
                    'simple',
                    $mappingIndex,
                    $sourcePath,
                    $targetPath,
                    $source,
                    $target,
                    (string)$resolvedTargetPath,
                    $wildcardIndex
                );

                $writeValue = $itemValue;
                if (!HookInvoker::isEmpty($hooks)) {
                    $writeValue = HookInvoker::invokeValueHook(
                        $hooks,
                        DataMapperHook::BeforeWrite->value,
                        $writeContext,
                        $itemValue
                    );
                }

                if ('__skip__' === $writeValue) {
                    return false;
                }
                $target = DataMutator::set(
                    self::asTarget($target),
                    (string)$resolvedTargetPath,
                    $writeValue
                );

                if (!HookInvoker::isEmpty($hooks)) {
                    $target = HookInvoker::invokeTargetHook(
                        $hooks,
                        DataMapperHook::AfterWrite->value,
                        $writeContext,
                        $writeValue,
                        $target
                    );
                }

                return true;
            }
        );

        return $target;
    }

    /**
     * Process single (non-wildcard) mapping.
     *
     * @param array<string, mixed> $hooks
     */
    private static function processSingleMapping(
        mixed $value,
        mixed $target,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        int $mappingIndex,
        array $hooks,
        ?PairContext $pairContext
    ): mixed {
        // Only invoke hooks if pairContext exists (i.e., hooks are configured)
        if ($pairContext instanceof PairContext) {
            $value = HookInvoker::invokeValueHook($hooks, DataMapperHook::AfterTransform->value, $pairContext, $value);
        }

        $writeContext = new WriteContext(
            'simple',
            $mappingIndex,
            $sourcePath,
            $targetPath,
            $source,
            $target,
            $targetPath
        );

        $writeValue = $value;
        if (!HookInvoker::isEmpty($hooks)) {
            $writeValue = HookInvoker::invokeValueHook(
                $hooks,
                DataMapperHook::BeforeWrite->value,
                $writeContext,
                $value
            );
        }

        if ('__skip__' !== $writeValue) {
            $target = DataMutator::set(self::asTarget($target), $targetPath, $writeValue);

            if (!HookInvoker::isEmpty($hooks)) {
                $target = HookInvoker::invokeTargetHook(
                    $hooks,
                    DataMapperHook::AfterWrite->value,
                    $writeContext,
                    $writeValue,
                    $target
                );
            }
        }

        return $target;
    }

    /**
     * Process a single wildcard item with transformation, replacement, and hooks.
     *
     * This method handles the complete processing pipeline for a single wildcard item:
     * 1. Apply transformation and replacement via ValueTransformer::processValue()
     * 2. Invoke afterTransform hook
     * 3. Check for null (if skipNull is enabled)
     * 4. Resolve target path with wildcard index
     * 5. Invoke beforeWrite hook
     * 6. Write value to target
     * 7. Invoke afterWrite hook
     *
     * @param int|string $wildcardIndex The wildcard index (numeric or string key)
     * @param mixed $itemValue The value to process
     * @param mixed $target The target to write to (passed by reference)
     * @param array $hooks Hook configuration
     * @param PairContext $pairContext Context for hooks
     * @param null|callable(mixed): mixed $transformFn Optional transformation function
     * @param null|array<int|string, mixed> $replaceMap Optional replacement map
     * @param bool $trimValues Whether to trim string values
     * @param bool $caseInsensitiveReplace Whether replacement is case-insensitive
     * @param string $mode Mapping mode ('simple' or 'structured')
     * @param int $mappingIndex Index of the current mapping
     * @param string $sourcePath Source path (may contain wildcard)
     * @param string $targetPath Target path (may contain wildcard)
     * @param mixed $source Original source data
     * @param bool $skipNull Whether to skip null values
     * @param array<string, mixed> $hooks
     * @return bool True if item was processed, false if skipped
     * @phpstan-ignore ergebnis.noParameterPassedByReference
     */
    public static function processWildcardItem(
        int|string $wildcardIndex,
        mixed $itemValue,
        mixed &$target,
        array $hooks,
        PairContext $pairContext,
        ?callable $transformFn,
        ?array $replaceMap,
        bool $trimValues,
        bool $caseInsensitiveReplace,
        string $mode,
        int $mappingIndex,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        bool $skipNull
    ): bool {
        // Update context with wildcard index
        $pairContext->wildcardIndex = $wildcardIndex;

        // Process value through transformation and replacement pipeline
        $itemValue = ValueTransformer::processValue(
            $itemValue,
            $transformFn,
            $replaceMap,
            $trimValues,
            $caseInsensitiveReplace
        );

        // Invoke afterTransform hook
        $itemValue = HookInvoker::invokeValueHook(
            $hooks,
            DataMapperHook::AfterTransform->value,
            $pairContext,
            $itemValue
        );

        // Skip null values if requested
        if ($skipNull && null === $itemValue) {
            return false;
        }

        // Resolve target path with wildcard index
        $resolvedTargetPath = preg_replace(
            '/\*/',
            (string)$wildcardIndex,
            $targetPath,
            1
        );

        // Create write context
        $writeContext = new WriteContext(
            $mode,
            $mappingIndex,
            $sourcePath,
            $targetPath,
            $source,
            $target,
            $resolvedTargetPath,
            $wildcardIndex
        );

        // Invoke beforeWrite hook
        $writeValue = HookInvoker::invokeValueHook(
            $hooks,
            DataMapperHook::BeforeWrite->value,
            $writeContext,
            $itemValue
        );

        // Skip if hook returned magic skip value
        if ('__skip__' === $writeValue) {
            return false;
        }

        // Write value to target
        $target = DataMutator::set(
            self::asTarget($target),
            $resolvedTargetPath ?? '',
            $writeValue
        );

        // Invoke afterWrite hook
        $target = HookInvoker::invokeTargetHook(
            $hooks,
            DataMapperHook::AfterWrite->value,
            $writeContext,
            $writeValue,
            $target
        );

        return true;
    }

    /**
     * Process a single non-wildcard value with transformation, replacement, and hooks.
     *
     * This method handles the complete processing pipeline for a single non-wildcard value:
     * 1. Apply transformation and replacement via ValueTransformer::processValue()
     * 2. Invoke afterTransform hook
     * 3. Check for null (if skipNull is enabled) - returns false if should skip
     * 4. Create write context
     * 5. Invoke beforeWrite hook
     * 6. Write value to target (if not skipped)
     * 7. Invoke afterWrite hook
     *
     * @param mixed $value The value to process
     * @param mixed $target The target to write to (passed by reference)
     * @param array<string, mixed> $hooks Hook configuration
     * @param PairContext $pairContext Context for hooks
     * @param null|callable(mixed): mixed $transformFn Optional transformation function
     * @param null|array<int|string, mixed> $replaceMap Optional replacement map
     * @param bool $trimValues Whether to trim string values
     * @param bool $caseInsensitiveReplace Whether replacement is case-insensitive
     * @param string $mode Mapping mode ('simple' or 'structured')
     * @param int $mappingIndex Index of the current mapping
     * @param string $sourcePath Source path
     * @param string $targetPath Target path
     * @param mixed $source Original source data
     * @param bool $skipNull Whether to skip null values
     * @return bool True if value was processed, false if skipped (caller should continue to next iteration)
     * @phpstan-ignore ergebnis.noParameterPassedByReference
     */
    public static function processSingleValue(
        mixed $value,
        mixed &$target,
        array $hooks,
        PairContext $pairContext,
        ?callable $transformFn,
        ?array $replaceMap,
        bool $trimValues,
        bool $caseInsensitiveReplace,
        string $mode,
        int $mappingIndex,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        bool $skipNull
    ): bool {
        // Process value through transformation and replacement pipeline
        $value = ValueTransformer::processValue(
            $value,
            $transformFn,
            $replaceMap,
            $trimValues,
            $caseInsensitiveReplace
        );

        // Invoke afterTransform hook
        $value = HookInvoker::invokeValueHook($hooks, DataMapperHook::AfterTransform->value, $pairContext, $value);

        // Skip null values if requested
        if ($skipNull && null === $value) {
            return false;
        }

        // Create write context
        $writeContext = new WriteContext(
            $mode,
            $mappingIndex,
            $sourcePath,
            $targetPath,
            $source,
            $target,
            $targetPath
        );

        // Invoke beforeWrite hook
        $writeValue = HookInvoker::invokeValueHook($hooks, DataMapperHook::BeforeWrite->value, $writeContext, $value);

        // Skip if hook returned magic skip value
        if ('__skip__' !== $writeValue) {
            // Write value to target
            $target = DataMutator::set(
                self::asTarget($target),
                $targetPath,
                $writeValue
            );

            // Invoke afterWrite hook
            $target = HookInvoker::invokeTargetHook(
                $hooks,
                DataMapperHook::AfterWrite->value,
                $writeContext,
                $writeValue,
                $target
            );
        }

        return true;
    }
}
