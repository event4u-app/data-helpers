<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\DotPathHelper;

/**
 * Core mapping engine that handles the actual mapping logic.
 */
class MappingEngine
{
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
     * Process simple mapping (associative array of source => target paths).
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
            HookInvoker::invokeHooks($hooks, 'beforeAll', new AllContext('simple', $mapping, $source, $target));
        }

        $mappingIndex = 0;
        foreach ($mapping as $sourcePath => $targetPath) {
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
                'beforePair',
                $pairContext
            ) === false) {
                $mappingIndex++;

                continue;
            }

            $value = $accessor->get((string)$sourcePath);

            // Skip null values by default
            if ($skipNull && null === $value) {
                $mappingIndex++;

                continue;
            }

            // preTransform (only if hooks exist)
            if ($hasHooks && $pairContext instanceof PairContext) {
                $value = HookInvoker::invokeValueHook($hooks, 'preTransform', $pairContext, $value);
            }

            // Handle wildcard values (always arrays with dot-path keys)
            // Use cached wildcard check
            if (is_array($value) && DotPathHelper::containsWildcard((string)$sourcePath)) {
                // Normalize wildcard array (flatten dot-path keys to simple list)
                $value = WildcardHandler::normalizeWildcardArray($value);

                $target = self::processWildcardMapping(
                    $value,
                    $target,
                    $sourcePath,
                    $targetPath,
                    $source,
                    $mappingIndex,
                    $skipNull,
                    $reindexWildcard,
                    $hooks,
                    $pairContext
                );
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
            }

            // afterPair hook (only if hooks exist)
            if ($hasHooks && $pairContext instanceof PairContext) {
                HookInvoker::invokeHooks($hooks, 'afterPair', $pairContext);
            }
            $mappingIndex++;
        }

        // Global hook: afterAll (only if hooks exist)
        if ($hasHooks) {
            HookInvoker::invokeHooks($hooks, 'afterAll', new AllContext('simple', $mapping, $source, $target));
        }

        return $target;
    }

    /**
     * Process wildcard mapping (source path contains *).
     *
     * @param array<int|string, mixed> $value
     * @param array<string, mixed> $hooks
     */
    private static function processWildcardMapping(
        array $value,
        mixed $target,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        int $mappingIndex,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
        ?PairContext $pairContext
    ): mixed {
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
                $mappingIndex
            ): bool {
                // Only set wildcardIndex if pairContext exists
                if ($pairContext instanceof PairContext) {
                    $pairContext->wildcardIndex = $wildcardIndex;
                    $itemValue = HookInvoker::invokeValueHook($hooks, 'postTransform', $pairContext, $itemValue);
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
                    $writeValue = HookInvoker::invokeValueHook($hooks, 'beforeWrite', $writeContext, $itemValue);
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
                    $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);
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
            $value = HookInvoker::invokeValueHook($hooks, 'postTransform', $pairContext, $value);
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
            $writeValue = HookInvoker::invokeValueHook($hooks, 'beforeWrite', $writeContext, $value);
        }

        if ('__skip__' !== $writeValue) {
            $target = DataMutator::set(self::asTarget($target), $targetPath, $writeValue);

            if (!HookInvoker::isEmpty($hooks)) {
                $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);
            }
        }

        return $target;
    }

    /**
     * Process a single wildcard item with transformation, replacement, and hooks.
     *
     * This method handles the complete processing pipeline for a single wildcard item:
     * 1. Apply transformation and replacement via ValueTransformer::processValue()
     * 2. Invoke postTransform hook
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

        // Invoke postTransform hook
        $itemValue = HookInvoker::invokeValueHook(
            $hooks,
            'postTransform',
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
            'beforeWrite',
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
            'afterWrite',
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
     * 2. Invoke postTransform hook
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

        // Invoke postTransform hook
        $value = HookInvoker::invokeValueHook($hooks, 'postTransform', $pairContext, $value);

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
        $writeValue = HookInvoker::invokeValueHook($hooks, 'beforeWrite', $writeContext, $value);

        // Skip if hook returned magic skip value
        if ('__skip__' !== $writeValue) {
            // Write value to target
            $target = DataMutator::set(
                self::asTarget($target),
                $targetPath,
                $writeValue
            );

            // Invoke afterWrite hook
            $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);
        }

        return true;
    }
}
