<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use InvalidArgumentException;

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

        return !empty($mapping);
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
        $accessor = new DataAccessor($source);

        // Global hook: beforeAll
        HookInvoker::invokeHooks($hooks, 'beforeAll', new AllContext('simple', $mapping, $source, $target));

        $mappingIndex = 0;
        foreach ($mapping as $sourcePath => $targetPath) {
            $pairContext = new PairContext(
                'simple',
                $mappingIndex,
                (string)$sourcePath,
                (string)$targetPath,
                $source,
                $target
            );

            if (HookInvoker::invokeHooks($hooks, 'beforePair', $pairContext) === false) {
                $mappingIndex++;

                continue;
            }

            $value = $accessor->get((string)$sourcePath);

            // Skip null values by default
            if ($skipNull && null === $value) {
                $mappingIndex++;

                continue;
            }

            // preTransform
            $value = HookInvoker::invokeValueHook($hooks, 'preTransform', $pairContext, $value);

            // Handle wildcard values (always arrays with dot-path keys)
            if (is_array($value) && str_contains((string)$sourcePath, '*')) {
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

            HookInvoker::invokeHooks($hooks, 'afterPair', $pairContext);
            $mappingIndex++;
        }

        HookInvoker::invokeHooks($hooks, 'afterAll', new AllContext('simple', $mapping, $source, $target));

        return $target;
    }

    /**
     * Process wildcard mapping (source path contains *).
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
        PairContext $pairContext
    ): mixed {
        WildcardHandler::iterateWildcardItems(
            $value,
            $skipNull,
            $reindexWildcard,
            function(int $_i, string $reason) use (&$mappingIndex): void {
                if ('null' === $reason) {
                    $mappingIndex++;
                }
            },
            function(int $wildcardIndex, mixed $itemValue) use (
                &$target,
                $hooks,
                $pairContext,
                $sourcePath,
                $targetPath,
                $source,
                $mappingIndex
            ): bool {
                $pairContext->wildcardIndex = $wildcardIndex;
                $itemValue = HookInvoker::invokeValueHook($hooks, 'postTransform', $pairContext, $itemValue);
                $resolvedTargetPath = preg_replace('/\*/', (string)$wildcardIndex, (string)$targetPath, 1);
                $writeContext = new WriteContext(
                    'simple',
                    $mappingIndex,
                    (string)$sourcePath,
                    (string)$targetPath,
                    $source,
                    $target,
                    (string)$resolvedTargetPath,
                    $wildcardIndex
                );
                $writeValue = HookInvoker::invokeValueHook($hooks, 'beforeWrite', $writeContext, $itemValue);
                if ('__skip__' === $writeValue) {
                    return false;
                }
                $target = DataMutator::set(
                    self::asTarget($target),
                    (string)$resolvedTargetPath,
                    $writeValue
                );
                $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);

                return true;
            }
        );

        return $target;
    }

    /**
     * Process single (non-wildcard) mapping.
     */
    private static function processSingleMapping(
        mixed $value,
        mixed $target,
        string $sourcePath,
        string $targetPath,
        mixed $source,
        int $mappingIndex,
        array $hooks,
        PairContext $pairContext
    ): mixed {
        $value = HookInvoker::invokeValueHook($hooks, 'postTransform', $pairContext, $value);
        $writeContext = new WriteContext(
            'simple',
            $mappingIndex,
            (string)$sourcePath,
            (string)$targetPath,
            $source,
            $target,
            (string)$targetPath
        );
        $writeValue = HookInvoker::invokeValueHook($hooks, 'beforeWrite', $writeContext, $value);
        if ('__skip__' !== $writeValue) {
            $target = DataMutator::set(self::asTarget($target), (string)$targetPath, $writeValue);
            $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);
        }

        return $target;
    }
}

