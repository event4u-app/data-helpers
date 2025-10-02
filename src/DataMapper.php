<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\AutoMapper;
use event4u\DataHelpers\DataMapper\Context\EntryContext;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Support\HookInvoker;
use event4u\DataHelpers\DataMapper\Support\MappingEngine;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\TemplateMapper;
use event4u\DataHelpers\DataMapper\Support\ValueTransformer;
use event4u\DataHelpers\DataMapper\Support\WildcardHandler;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use TypeError;

/**
 * DataMapper allows mapping values between different data structures
 * (arrays, DTOs, Models, Collections, JSON/XML strings).
 *
 * Supports dot-notation and wildcards in paths.
 */
class DataMapper
{
    /**
     * Map values from a source to a target using dot-path mappings.
     *
     * Supports two styles:
     * - Simple mapping: ['src.path' => 'dst.path'] with wildcard support ('*').
     * - Structured mapping: array of entries. Each entry may contain:
     *   - source (mixed), target (mixed)
     *   - sourceMapping (string[]) and targetMapping (string[]), or
     *   - mapping: associative ['src' => 'dst'] or list of [src, dst]
     *   - skipNull (bool, optional): per-entry override of global $skipNull
     *   - reindexWildcard (bool, optional): per-entry override of global $reindexWildcard; when true, wildcard indices are compacted (0..n-1)
     *
     * @param mixed $source The source data (array, object, model, DTO, string, etc.)
     * @param mixed $target The target data (array, object, model, DTO, string, etc.)
     * @param array<int|string, mixed> $mapping Either simple path map or structured mapping
     * @param bool $skipNull Global default to skip null values (per-entry 'skipNull' can override)
     * @param bool $reindexWildcard Global default to reindex wildcard results (per-entry 'reindexWildcard' can override)
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks (see App\Enums\DataMapperHook cases)
     * @return mixed The updated target
     */
    public static function map(
        mixed $source,
        mixed $target,
        array $mapping,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        // Ensure target is a supported type for mutation
        if (!is_array($target) && !is_object($target)) {
            $target = [];
        }

        /** @var array<int|string, mixed>|object $target */
        assert(is_array($target) || is_object($target));

        // Normalize enum keys (if any) to string names
        $hooks = HookInvoker::normalizeHooks($hooks);

        // Case 1: simple path-to-path mapping like ['a.b' => 'x.y']
        if (MappingEngine::isSimpleMapping($mapping)) {
            return self::mapSimple($source, $target, $mapping, $skipNull, $reindexWildcard, $hooks);
        }

        // Case 2: structured mapping definitions with source/target objects
        return self::mapStructured(
            $source,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Bulk map values for multiple mapping definitions.
     *
     * Each mapping entry may include 'skipNull' and 'reindexWildcard' to override the globals.
     *
     * @param array<int, array<string, mixed>> $mappings
     * @param bool $skipNull Global default for skipping null values (overridable per entry)
     * @param bool $reindexWildcard Global default for wildcard reindexing (overridable per entry)
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks propagated to each mapping
     * @return array<int, mixed> Array of all updated targets after each mapping
     */
    public static function mapMany(
        array $mappings,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): array {
        $results = [];

        foreach ($mappings as $map) {
            $entrySource = $map['source'] ?? null;
            $entryTarget = $map['target'] ?? [];

            $sourcePathMapping = $map['sourceMapping'] ?? [];
            $targetPathMapping = $map['targetMapping'] ?? [];

            $entrySkipNull = array_key_exists('skipNull', $map) ? (bool)$map['skipNull'] : $skipNull;

            $entryReindex = array_key_exists(
                'reindexWildcard',
                $map
            ) ? (bool)$map['reindexWildcard'] : $reindexWildcard;

            $results[] = self::map($entrySource, $entryTarget, [
                [
                    'source' => $entrySource,
                    'sourceMapping' => $sourcePathMapping,
                    'target' => $entryTarget,
                    'targetMapping' => $targetPathMapping,
                    'skipNull' => $entrySkipNull,
                    'reindexWildcard' => $entryReindex,
                    'hooks' => $map['hooks'] ?? [],
                ],
            ], $skipNull, $reindexWildcard, $hooks, $trimValues, $caseInsensitiveReplace);
        }

        return $results;
    }

    /**
     * Auto-map by matching top-level field names between source and target.
     *
     * Intended for quick scenarios like JSON → Model or DTO → Model where fields share names.
     *
     * Rules:
     * - Only top-level fields are considered (no deep recursion)
     * - When target is an object, we try to bridge snake_case source keys to camelCase target properties
     *   (e.g. payment_status → paymentStatus) if such a property exists on the target
     * - Unknown/unsupported targets are coerced to array
     * - skipNull, reindexWildcard, hooks, trimValues, caseInsensitiveReplace behave as in map()
     *
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks propagated to this mapping
     */
    public static function autoMap(
        mixed $source,
        mixed $target,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
        bool $deep = false,
    ): mixed {
        return AutoMapper::autoMap(
            $source,
            $target,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace,
            $deep
        );
    }

    /**
     * Build a structure from a template (array or JSON string) by resolving dot-path values
     * against a set of named sources.
     *
     * Example:
     *   $sources = ['user' => $userModel, 'addr' => $addressArray];
     *   $template = [
     *     'profile' => [
     *       'fullname' => 'user.name',
     *       'email' => 'user.email',
     *       'street' => 'addr.street',
     *     ]
     *   ];
     *
     * @param array<string,mixed>|string $template Array or JSON string template
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
        return TemplateMapper::mapFromTemplate($template, $sources, $skipNull, $reindexWildcard);
    }



    /**
     * Apply values from a data structure to named targets using a template that defines target destinations.
     *
     * Example:
     *   $targets = ['user' => $userModel, 'addr' => $addressArray];
     *   $template = [
     *     'profile' => [
     *       'fullname' => 'user.name',
     *       'email' => 'user.email',
     *       'street' => 'addr.street',
     *     ]
     *   ];
     *   DataMapper::mapToTargetsFromTemplate($data, $template, $targets);
     *
     * This reads values from $data at the same positions as in $template and writes them into
     * the corresponding target alias/path (e.g. user.name, addr.street).
     *
     * Wildcards in target paths are supported: if the data node is an array and the template value
     * is e.g. "people.*.name", entries are written to people.0.name, people.1.name, ...
     * When skipNull=true, null items are skipped; with reindexWildcard=true, indices are compacted.
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
        return TemplateMapper::mapToTargetsFromTemplate($data, $template, $targets, $skipNull, $reindexWildcard);
    }

    /**
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
        // Recurse arrays by matching keys in template
        if (is_array($templateNode)) {
            foreach ($templateNode as $key => $childTemplate) {
                $childData = is_array($dataNode) && array_key_exists($key, $dataNode) ? $dataNode[$key] : null;
                $targets = self::applyTemplateNodeToTargets(
                    $childData,
                    $childTemplate,
                    $targets,
                    $skipNull,
                    $reindexWildcard
                );
            }

            return $targets;
        }

        // Only strings are destination references; other types are literals (ignored for writing)
        if (!is_string($templateNode)) {
            return $targets;
        }

        [$alias, $path] = self::parseSourceReference($templateNode);
        if (null === $alias || !array_key_exists($alias, $targets)) {
            // literal or unknown alias -> ignore
            return $targets;
        }

        // Skip nulls if requested
        if ($skipNull && null === $dataNode) {
            return $targets;
        }

        $target = $targets[$alias];
        if (!is_array($target) && !is_object($target)) {
            $target = [];
        }

        // If no path provided, assign whole target to the value
        if (null === $path || '' === $path) {
            $targets[$alias] = $dataNode;

            return $targets;
        }

        // Wildcard-aware write
        if (str_contains($path, '*')) {
            $targets[$alias] = self::writeToAliasWithWildcards($target, $path, $dataNode, $skipNull, $reindexWildcard);

            return $targets;
        }

        // Simple write
        $targets[$alias] = DataMutator::set($target, $path, $dataNode);

        return $targets;
    }

    private static function writeToAliasWithWildcards(
        mixed $target,
        string $path,
        mixed $value,
        bool $skipNull,
        bool $reindexWildcard,
    ): mixed {
        $segments = DotPathHelper::segments($path);
        if (!is_array($target) && !is_object($target)) {
            $target = [];
        }

        $write = function (array|object $targetInner, array $segmentsInner, mixed $currentValue) use (
            &$write,
            $skipNull,
            $reindexWildcard
        ): array|object {
            // no wildcard left -> perform simple set
            $wildcardSegmentIndex = null;
            foreach ($segmentsInner as $i => $segment) {
                if ('*' === $segment) {
                    $wildcardSegmentIndex = $i;

                    break;
                }
            }
            if (null === $wildcardSegmentIndex) {
                $finalPath = implode('.', $segmentsInner);

                return DataMutator::set($targetInner, $finalPath, $currentValue);
            }

            // there is a wildcard at $wildcardSegmentIndex
            if (!is_array($currentValue)) {
                // single value -> write at index 0
                $segmentsInner[$wildcardSegmentIndex] = '0';

                return $write($targetInner, $segmentsInner, $currentValue);
            }

            // array value -> iterate via centralized helper
            WildcardHandler::iterateWildcardItems(
                $currentValue,
                $skipNull,
                $reindexWildcard,
                null,
                function (int|string $wildcardIndex, mixed $item) use (
                    &$targetInner,
                    $write,
                    $segmentsInner,
                    $wildcardSegmentIndex
                ): bool {
                    $segmentsCopy = $segmentsInner;
                    $segmentsCopy[$wildcardSegmentIndex] = (string)$wildcardIndex;
                    $targetInner = $write($targetInner, $segmentsCopy, $item);

                    return true;
                }
            );

            return $targetInner;
        };

        return $write($target, $segments, $value);
    }

    /**
     * Apply simple value replacement based on a mapping array.
     *
     * - Only replaces scalar (string|int|float|bool) or null values
     * - Keys supported: string and int (common PHP array keys)
     * - Order: apply on the already transformed value, before hooks like postTransform
     *
     * @param array<int|string, mixed> $replaceMap
     */
    private static function applyReplacement(mixed $value, array $replaceMap, bool $caseInsensitive = false): mixed
    {
        // Only handle scalar or null values; leave arrays/objects untouched
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        // Prefer exact key match for string|int
        if ((is_string($value) || is_int($value)) && array_key_exists($value, $replaceMap)) {
            return $replaceMap[$value];
        }

        // Case-insensitive matching for strings when enabled
        if ($caseInsensitive && is_string($value)) {
            // Build a lowercase map for string keys only
            $lowerMap = [];
            foreach ($replaceMap as $k => $v) {
                if (is_string($k)) {
                    $lowerMap[strtolower($k)] = $v;
                }
            }
            $lowerKey = strtolower($value);
            if (array_key_exists($lowerKey, $lowerMap)) {
                return $lowerMap[$lowerKey];
            }
        }

        // No replacement
        return $value;
    }



    /**
     * Ensure a mixed value is a valid target (array|object) for DataMutator.
     *
     * @return array<int|string, mixed>|object
     */
    private static function asTarget(mixed $candidate): array|object
    {
        if (!is_array($candidate) && !is_object($candidate)) {
            return [];
        }

        return $candidate;
    }



    /**
     * Handle simple path-to-path mapping.
     *
     * @param mixed $source
     * @param array<int|string, mixed>|object $target
     * @param array<string, string> $mapping
     * @param bool $skipNull
     * @param bool $reindexWildcard
     * @param array<string, mixed> $hooks
     * @return array<int|string, mixed>|object
     */
    private static function mapSimple(
        mixed $source,
        array|object $target,
        array $mapping,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
    ): array|object {
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
                WildcardHandler::iterateWildcardItems(
                    $value,
                    $skipNull,
                    $reindexWildcard,
                    function (int|string $_i, string $reason) use (&$mappingIndex): void {
                        if ('null' === $reason) {
                            $mappingIndex++;
                        }
                    },
                    function (int|string $wildcardIndex, mixed $itemValue) use (
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
                            MappingEngine::asTarget($target),
                            (string)$resolvedTargetPath,
                            $writeValue
                        );
                        $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);

                        return true;
                    }
                );
            } else {
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
                    $target = DataMutator::set(MappingEngine::asTarget($target), (string)$targetPath, $writeValue);
                    $target = HookInvoker::invokeTargetHook($hooks, 'afterWrite', $writeContext, $writeValue, $target);
                }
            }

            HookInvoker::invokeHooks($hooks, 'afterPair', $pairContext);
            $mappingIndex++;
        }

        HookInvoker::invokeHooks($hooks, 'afterAll', new AllContext('simple', $mapping, $source, $target));

        return $target;
    }

    /**
     * Handle structured mapping definitions with source/target objects.
     *
     * @param mixed $source
     * @param array<int|string, mixed>|object $target
     * @param array<int, array<string, mixed>> $mapping
     * @param bool $skipNull
     * @param bool $reindexWildcard
     * @param array<string, mixed> $hooks
     * @param bool $trimValues
     * @param bool $caseInsensitiveReplace
     * @return array<int|string, mixed>|object
     */
    private static function mapStructured(
        mixed $source,
        array|object $target,
        array $mapping,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
        bool $trimValues,
        bool $caseInsensitiveReplace,
    ): array|object {
        // Global hook: beforeAll for structured mode
        HookInvoker::invokeHooks($hooks, 'beforeAll', new AllContext('structured', $mapping, $source, $target));

        // Case 2: structured mapping definitions with source/target objects
        foreach ($mapping as $map) {
            if (!is_array($map)) {
                throw new InvalidArgumentException('Advanced mapping definitions must be arrays.');
            }

            $entrySource = $map['source'] ?? $source;

            /** @var array<int|string, mixed>|object $entryTarget */
            $entryTarget = $map['target'] ?? $target;
            if (!is_array($entryTarget) && !is_object($entryTarget)) {
                $entryTarget = [];
            }

            $entrySkipNull = array_key_exists('skipNull', $map) ? (bool)$map['skipNull'] : $skipNull;

            assert(is_array($entryTarget) || is_object($entryTarget));

            $entryReindex = array_key_exists(
                'reindexWildcard',
                $map
            ) ? (bool)$map['reindexWildcard'] : $reindexWildcard;

            $accessor = new DataAccessor($entrySource);

            /** @var array<DataMapperHook|string, mixed> $entryHooks */
            $entryHooks = is_array($map['hooks'] ?? null) ? $map['hooks'] : [];
            $entryHooks = HookInvoker::normalizeHooks($entryHooks);
            $effectiveHooks = HookInvoker::mergeHooks($hooks, $entryHooks);
            HookInvoker::invokeHooks(
                $effectiveHooks,
                'beforeEntry',
                new EntryContext('structured', $map, $entrySource, $entryTarget)
            );

            // Support either explicit source/target mapping arrays, or a single associative/list 'mapping'
            if (isset($map['sourceMapping']) || isset($map['targetMapping'])) {
                $sourcePathMapping = $map['sourceMapping'] ?? [];
                $targetPathMapping = $map['targetMapping'] ?? [];

                /** @var array<int, null|callable>|array<string, null|callable> $transforms */
                $transforms = is_array($map['transforms'] ?? null) ? $map['transforms'] : [];

                /** @var array<int, null|array<string, mixed>>|array<string, null|array<string, mixed>> $replaces */
                $replaces = is_array($map['replaces'] ?? null) ? $map['replaces'] : [];

                if (count($sourcePathMapping) !== count($targetPathMapping)) {
                    $msg = sprintf(
                        'Source and target path arrays must have the same length. Given: source=%d, target=%d',
                        count($sourcePathMapping),
                        count($targetPathMapping)
                    );

                    throw new InvalidArgumentException($msg);
                }

                foreach ($sourcePathMapping as $pairIndex => $sourcePath) {
                    $targetPath = $targetPathMapping[$pairIndex] ?? null;
                    if (null === $targetPath) {
                        continue;
                    }

                    $pairContext = new PairContext(
                        'structured',
                        $pairIndex,
                        (string)$sourcePath,
                        (string)$targetPath,
                        $entrySource,
                        $entryTarget
                    );
                    if (HookInvoker::invokeHooks($effectiveHooks, 'beforePair', $pairContext) === false) {
                        continue;
                    }

                    $value = $accessor->get((string)$sourcePath);
                    if ($entrySkipNull && null === $value) {
                        HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);

                        continue;
                    }

                    // preTransform
                    $value = HookInvoker::invokeValueHook($effectiveHooks, 'preTransform', $pairContext, $value);

                    /** @var null|array<string, mixed> $replaceMap */
                    $replaceMap = null;
                    if (is_array($replaces)) {
                        if (array_is_list($replaces)) {
                            $replaceMap = $replaces[$pairIndex] ?? null;
                        } else {
                            $replaceMap = $replaces[(string)$sourcePath] ?? null;
                        }
                    }

                    $transformFn = $transforms[$pairIndex] ?? null;

                    // Handle wildcard values (always arrays with dot-path keys)
                    if (is_array($value) && str_contains((string)$sourcePath, '*')) {
                        // Normalize wildcard array so indices are numeric (0..n) or original numeric keys
                        $value = WildcardHandler::normalizeWildcardArray($value);

                        WildcardHandler::iterateWildcardItems(
                            $value,
                            $entrySkipNull,
                            $entryReindex,
                            null,
                            function (int|string $wildcardIndex, mixed $itemValue) use (
                                &$entryTarget,
                                $effectiveHooks,
                                $pairContext,
                                $transformFn,
                                $replaceMap,
                                $trimValues,
                                $caseInsensitiveReplace,
                                $sourcePath,
                                $targetPath,
                                $entrySource,
                                $entrySkipNull,
                                $pairIndex
                            ): bool {
                                $pairContext->wildcardIndex = $wildcardIndex;

                                if (is_callable($transformFn)) {
                                    $itemValue = $transformFn($itemValue);
                                }
                                if (is_array($replaceMap)) {
                                    if ($trimValues && is_string($itemValue)) {
                                        $itemValue = trim($itemValue);
                                    }
                                    $itemValue = ValueTransformer::applyReplacement(
                                        $itemValue,
                                        $replaceMap,
                                        $caseInsensitiveReplace
                                    );
                                }

                                $itemValue = HookInvoker::invokeValueHook(
                                    $effectiveHooks,
                                    'postTransform',
                                    $pairContext,
                                    $itemValue
                                );

                                if ($entrySkipNull && null === $itemValue) {
                                    return false;
                                }

                                $resolvedTargetPath = preg_replace(
                                    '/\*/',
                                    (string)$wildcardIndex,
                                    (string)$targetPath,
                                    1
                                );
                                $writeContext = new WriteContext(
                                    'structured',
                                    $pairIndex,
                                    (string)$sourcePath,
                                    (string)$targetPath,
                                    $entrySource,
                                    $entryTarget,
                                    (string)$resolvedTargetPath,
                                    $wildcardIndex
                                );
                                $writeValue = HookInvoker::invokeValueHook(
                                    $effectiveHooks,
                                    'beforeWrite',
                                    $writeContext,
                                    $itemValue
                                );
                                if ('__skip__' === $writeValue) {
                                    return false;
                                }

                                $entryTarget = DataMutator::set(
                                    MappingEngine::asTarget($entryTarget),
                                    (string)$resolvedTargetPath,
                                    $writeValue
                                );
                                $entryTarget = HookInvoker::invokeTargetHook(
                                    $effectiveHooks,
                                    'afterWrite',
                                    $writeContext,
                                    $writeValue,
                                    $entryTarget
                                );

                                return true;
                            }
                        );
                    } else {
                        if (is_callable($transformFn)) {
                            $value = $transformFn($value);
                        }

                        if (is_array($replaceMap)) {
                            if ($trimValues && is_string($value)) {
                                $value = trim($value);
                            }
                            $value = ValueTransformer::applyReplacement($value, $replaceMap, $caseInsensitiveReplace);
                        }

                        $value = HookInvoker::invokeValueHook($effectiveHooks, 'postTransform', $pairContext, $value);
                        if ($entrySkipNull && null === $value) {
                            HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);

                            continue;
                        }
                        $writeContext = new WriteContext(
                            'structured',
                            $pairIndex,
                            (string)$sourcePath,
                            (string)$targetPath,
                            $entrySource,
                            $entryTarget,
                            (string)$targetPath
                        );
                        $writeValue = HookInvoker::invokeValueHook($effectiveHooks, 'beforeWrite', $writeContext, $value);
                        if ('__skip__' !== $writeValue) {
                            $entryTarget = DataMutator::set(
                                MappingEngine::asTarget($entryTarget),
                                (string)$targetPath,
                                $writeValue
                            );
                            $entryTarget = HookInvoker::invokeTargetHook(
                                $effectiveHooks,
                                'afterWrite',
                                $writeContext,
                                $writeValue,
                                $entryTarget
                            );
                        }
                    }

                    HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                }
            } elseif (isset($map['mapping'])) {
                $pairs = $map['mapping'];

                /** @var array<int, null|callable>|array<string, null|callable> $transforms */
                $transforms = is_array($map['transforms'] ?? null) ? $map['transforms'] : [];

                /** @var array<int, null|array<string, mixed>>|array<string, null|array<string, mixed>> $replaces */
                $replaces = is_array($map['replaces'] ?? null) ? $map['replaces'] : [];

                // Associative mapping: ['src' => 'dst']
                if (is_array($pairs) && MappingEngine::isSimpleMapping($pairs)) {
                    $mappingIndexAssoc = 0;
                    foreach ($pairs as $sourcePath => $targetPath) {
                        $pairContext = new PairContext(
                            'structured-assoc',
                            $mappingIndexAssoc,
                            (string)$sourcePath,
                            (string)$targetPath,
                            $entrySource,
                            $entryTarget
                        );
                        if (HookInvoker::invokeHooks($effectiveHooks, 'beforePair', $pairContext) === false) {
                            $mappingIndexAssoc++;

                            continue;
                        }

                        $value = $accessor->get((string)$sourcePath);
                        if ($entrySkipNull && null === $value) {
                            HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                            $mappingIndexAssoc++;

                            continue;
                        }

                        // preTransform
                        $value = HookInvoker::invokeValueHook($effectiveHooks, 'preTransform', $pairContext, $value);

                        $transformFn = null;
                        if (array_key_exists((string)$sourcePath, $transforms)) {
                            /** @var null|callable $fn */
                            $fn = $transforms[(string)$sourcePath];
                            $transformFn = $fn;
                        } elseif (array_is_list($transforms)) {
                            /** @var null|callable $fn2 */
                            $fn2 = $transforms[$mappingIndexAssoc] ?? null;
                            $transformFn = $fn2;
                        }

                        /** @var null|array<string, mixed> $replaceMap */
                        $replaceMap = null;
                        if (is_array($replaces)) {
                            if (array_key_exists((string)$sourcePath, $replaces)) {
                                $replaceMap = is_array(
                                    $replaces[(string)$sourcePath]
                                ) ? $replaces[(string)$sourcePath] : null;
                            } elseif (array_is_list($replaces)) {
                                $replaceMap = $replaces[$mappingIndexAssoc] ?? null;
                            }
                        }

                        if (is_array($value) && str_contains((string)$sourcePath, '*')) {
                            // Normalize wildcard array so indices are numeric (0..n) or original numeric keys
                            $value = WildcardHandler::normalizeWildcardArray($value);
                            WildcardHandler::iterateWildcardItems(
                                $value,
                                $entrySkipNull,
                                $entryReindex,
                                null,
                                function (int|string $wildcardIndex, mixed $itemValue) use (
                                    &$entryTarget,
                                    $effectiveHooks,
                                    $pairContext,
                                    $transformFn,
                                    $replaceMap,
                                    $trimValues,
                                    $caseInsensitiveReplace,
                                    $sourcePath,
                                    $targetPath,
                                    $entrySource,
                                    $mappingIndexAssoc,
                                    $entrySkipNull
                                ): bool {
                                    $pairContext->wildcardIndex = $wildcardIndex;

                                    if (is_callable($transformFn)) {
                                        $itemValue = $transformFn($itemValue);
                                    }

                                    if (is_array($replaceMap)) {
                                        if ($trimValues && is_string($itemValue)) {
                                            $itemValue = trim($itemValue);
                                        }
                                        $itemValue = ValueTransformer::applyReplacement(
                                            $itemValue,
                                            $replaceMap,
                                            $caseInsensitiveReplace
                                        );
                                    }

                                    $itemValue = HookInvoker::invokeValueHook(
                                        $effectiveHooks,
                                        'postTransform',
                                        $pairContext,
                                        $itemValue
                                    );

                                    if ($entrySkipNull && null === $itemValue) {
                                        return false;
                                    }

                                    $resolvedTargetPath = preg_replace(
                                        '/\*/',
                                        (string)$wildcardIndex,
                                        (string)$targetPath,
                                        1
                                    );
                                    $writeContext = new WriteContext(
                                        'structured',
                                        $mappingIndexAssoc,
                                        (string)$sourcePath,
                                        (string)$targetPath,
                                        $entrySource,
                                        $entryTarget,
                                        (string)$resolvedTargetPath,
                                        $wildcardIndex
                                    );
                                    $writeValue = HookInvoker::invokeValueHook(
                                        $effectiveHooks,
                                        'beforeWrite',
                                        $writeContext,
                                        $itemValue
                                    );
                                    if ('__skip__' === $writeValue) {
                                        return false;
                                    }

                                    $entryTarget = DataMutator::set(
                                        MappingEngine::asTarget($entryTarget),
                                        (string)$resolvedTargetPath,
                                        $writeValue
                                    );
                                    $entryTarget = HookInvoker::invokeTargetHook(
                                        $effectiveHooks,
                                        'afterWrite',
                                        $writeContext,
                                        $writeValue,
                                        $entryTarget
                                    );

                                    return true;
                                }
                            );
                        } else {
                            if (is_callable($transformFn)) {
                                $value = $transformFn($value);
                                if ($entrySkipNull && null === $value) {
                                    HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                                    $mappingIndexAssoc++;

                                    continue;
                                }
                            }

                            if (is_array($replaceMap)) {
                                if ($trimValues && is_string($value)) {
                                    $value = trim($value);
                                }
                                $value = ValueTransformer::applyReplacement($value, $replaceMap, $caseInsensitiveReplace);
                            }

                            $value = HookInvoker::invokeValueHook($effectiveHooks, 'postTransform', $pairContext, $value);
                            if ($entrySkipNull && null === $value) {
                                HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                                $mappingIndexAssoc++;

                                continue;
                            }
                            $writeContext = new WriteContext(
                                'structured',
                                $mappingIndexAssoc,
                                (string)$sourcePath,
                                (string)$targetPath,
                                $entrySource,
                                $entryTarget,
                                (string)$targetPath
                            );
                            $writeValue = HookInvoker::invokeValueHook($effectiveHooks, 'beforeWrite', $writeContext, $value);
                            if ('__skip__' !== $writeValue) {
                                $entryTarget = DataMutator::set(
                                    MappingEngine::asTarget($entryTarget),
                                    (string)$targetPath,
                                    $writeValue
                                );
                                $entryTarget = HookInvoker::invokeTargetHook(
                                    $effectiveHooks,
                                    'afterWrite',
                                    $writeContext,
                                    $writeValue,
                                    $entryTarget
                                );
                            }
                        }
                        HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                        $mappingIndexAssoc++;
                    }
                } else {
                    // List of pairs: [[sourcePath, targetPath], ...]
                    $pairIndex = 0;
                    foreach ($pairs as $mappingPair) {
                        if (!is_array($mappingPair) || count($mappingPair) !== 2) {
                            throw new InvalidArgumentException(
                                'Invalid mapping pair. Expected [sourcePath, targetPath].'
                            );
                        }
                        [$sourcePath, $targetPath] = $mappingPair;
                        if (!is_string($sourcePath) || !is_string($targetPath)) {
                            throw new InvalidArgumentException('Mapping paths must be strings.');
                        }
                        $pairContext = new PairContext(
                            'structured-pairs',
                            $pairIndex,
                            $sourcePath,
                            $targetPath,
                            $entrySource,
                            $entryTarget
                        );
                        if (HookInvoker::invokeHooks($effectiveHooks, 'beforePair', $pairContext) === false) {
                            $pairIndex++;

                            continue;
                        }

                        $value = $accessor->get($sourcePath);
                        if ($entrySkipNull && null === $value) {
                            HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                            $pairIndex++;

                            continue;
                        }
                        $value = HookInvoker::invokeValueHook($effectiveHooks, 'preTransform', $pairContext, $value);

                        /** @var null|callable $transformFn */
                        $transformFn = is_array($transforms) && array_is_list(
                            $transforms
                        ) ? ($transforms[$pairIndex] ?? null) : null;

                        /** @var null|array<string, mixed> $replaceMap */
                        $replaceMap = null;
                        if (is_array($replaces) && array_is_list($replaces)) {
                            $replaceMap = $replaces[$pairIndex] ?? null;
                        }

                        if (is_array($value) && str_contains($sourcePath, '*')) {
                            // Normalize wildcard array so indices are numeric (0..n) or original numeric keys
                            $value = WildcardHandler::normalizeWildcardArray($value);
                            WildcardHandler::iterateWildcardItems(
                                $value,
                                $entrySkipNull,
                                $entryReindex,
                                null,
                                function (int|string $wildcardIndex, mixed $itemValue) use (
                                    &$entryTarget,
                                    $effectiveHooks,
                                    $pairContext,
                                    $transformFn,
                                    $replaceMap,
                                    $trimValues,
                                    $caseInsensitiveReplace,
                                    $sourcePath,
                                    $targetPath,
                                    $entrySource,
                                    $pairIndex,
                                    $entrySkipNull
                                ): bool {
                                    $pairContext->wildcardIndex = $wildcardIndex;

                                    if (is_callable($transformFn)) {
                                        $itemValue = $transformFn($itemValue);
                                    }
                                    if (is_array($replaceMap)) {
                                        if ($trimValues && is_string($itemValue)) {
                                            $itemValue = trim($itemValue);
                                        }
                                        $itemValue = ValueTransformer::applyReplacement(
                                            $itemValue,
                                            $replaceMap,
                                            $caseInsensitiveReplace
                                        );
                                    }

                                    $itemValue = HookInvoker::invokeValueHook(
                                        $effectiveHooks,
                                        'postTransform',
                                        $pairContext,
                                        $itemValue
                                    );

                                    if ($entrySkipNull && null === $itemValue) {
                                        return false;
                                    }

                                    $resolvedTargetPath = preg_replace('/\*/', (string)$wildcardIndex, $targetPath, 1);
                                    $writeContext = new WriteContext(
                                        'structured',
                                        $pairIndex,
                                        $sourcePath,
                                        $targetPath,
                                        $entrySource,
                                        $entryTarget,
                                        (string)$resolvedTargetPath,
                                        $wildcardIndex
                                    );
                                    $writeValue = HookInvoker::invokeValueHook(
                                        $effectiveHooks,
                                        'beforeWrite',
                                        $writeContext,
                                        $itemValue
                                    );
                                    if ('__skip__' === $writeValue) {
                                        return false;
                                    }
                                    $entryTarget = DataMutator::set(
                                        MappingEngine::asTarget($entryTarget),
                                        (string)$resolvedTargetPath,
                                        $writeValue
                                    );
                                    $entryTarget = HookInvoker::invokeTargetHook(
                                        $effectiveHooks,
                                        'afterWrite',
                                        $writeContext,
                                        $writeValue,
                                        $entryTarget
                                    );

                                    return true;
                                }
                            );
                        } else {
                            if (is_callable($transformFn)) {
                                $value = $transformFn($value);
                                if ($entrySkipNull && null === $value) {
                                    HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                                    $pairIndex++;

                                    continue;
                                }
                            }
                            if (is_array($replaceMap)) {
                                if ($trimValues && is_string($value)) {
                                    $value = trim($value);
                                }
                                $value = ValueTransformer::applyReplacement($value, $replaceMap, $caseInsensitiveReplace);
                            }

                            $value = HookInvoker::invokeValueHook($effectiveHooks, 'postTransform', $pairContext, $value);
                            if ($entrySkipNull && null === $value) {
                                HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                                $pairIndex++;

                                continue;
                            }
                            $writeContext = new WriteContext(
                                'structured',
                                $pairIndex,
                                $sourcePath,
                                $targetPath,
                                $entrySource,
                                $entryTarget,
                                $targetPath
                            );
                            $writeValue = HookInvoker::invokeValueHook($effectiveHooks, 'beforeWrite', $writeContext, $value);
                            if ('__skip__' !== $writeValue) {
                                $entryTarget = DataMutator::set(MappingEngine::asTarget($entryTarget), $targetPath, $writeValue);
                                $entryTarget = HookInvoker::invokeTargetHook(
                                    $effectiveHooks,
                                    'afterWrite',
                                    $writeContext,
                                    $writeValue,
                                    $entryTarget
                                );
                            }
                        }
                        HookInvoker::invokeHooks($effectiveHooks, 'afterPair', $pairContext);
                        $pairIndex++;
                    }
                }
            } else {
                throw new InvalidArgumentException(
                    'Advanced mapping entry must contain source, target and mapping (or sourceMapping/targetMapping).'
                );
            }

            HookInvoker::invokeHooks(
                $effectiveHooks,
                'afterEntry',
                new EntryContext('structured', $map, $entrySource, $entryTarget)
            );

            $target = $entryTarget;
        }

        // Global hook: afterAll for structured mode
        HookInvoker::invokeHooks($hooks, 'afterAll', new AllContext('structured', $mapping, $source, $target));

        return $target;
    }
}
