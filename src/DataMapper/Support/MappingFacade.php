<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Support;

use DOMDocument;
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\AutoMapper;
use event4u\DataHelpers\DataMapper\Context\AllContext;
use event4u\DataHelpers\DataMapper\Context\EntryContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Context\WriteContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\MappingOptions;
use event4u\DataHelpers\DataMapper\TemplateMapper;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\Support\EntityHelper;
use event4u\DataHelpers\Support\FileLoader;
use event4u\DataHelpers\Support\StringFormatDetector;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * MappingFacade - Internal facade for mapping operations.
 *
 * This class contains all the core mapping logic that was previously in DataMapper.
 * It is used internally by FluentDataMapper.
 *
 * @internal This class is not part of the public API and should not be used directly.
 */
class MappingFacade
{
    /** Marker for static values in mapping arrays. */
    public const STATIC_VALUE_MARKER = '__static__';

    /** Default root element name for XML conversion. */
    public const DEFAULT_XML_ROOT = 'root';

    /**
     * @param array<int|string, mixed> $mapping
     * @param array<int|string, mixed> $hooks
     */
    public static function map(
        mixed $source,
        mixed $target,
        array $mapping,
        bool|MappingOptions $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        // Clear exceptions at the start of each mapping
        MapperExceptions::clearExceptions();

        // Support new MappingOptions API
        if ($skipNull instanceof MappingOptions) {
            $options = $skipNull;
            $skipNull = $options->skipNull;
            $reindexWildcard = $options->reindexWildcard;
            $hooks = $options->hooks;
            $trimValues = $options->trimValues;
            $caseInsensitiveReplace = $options->caseInsensitiveReplace;
        }
        // Ensure target is a supported type for mutation
        if (!is_array($target) && !is_object($target)) {
            $target = [];
        }

        /** @var array<int|string, mixed>|object $target */
        // Normalize enum keys (if any) to string names
        $hooks = HookInvoker::normalizeHooks($hooks);

        // Case 1: nested mapping structure like ['profile' => ['name' => 'user.name']]
        if (MappingEngine::isNestedMapping($mapping)) {
            // Check if target is an entity and if any top-level keys are relations
            // If so, handle them separately before flattening
            if (is_object($target) && EntityHelper::isEntity($target)) {
                $relationMappings = [];
                $regularMappings = [];

                foreach ($mapping as $key => $value) {
                    if (is_string($key) && EntityHelper::isRelation($target, $key)) {
                        $relationMappings[$key] = $value;
                    } else {
                        $regularMappings[$key] = $value;
                    }
                }

                // Process relation mappings first (without flattening)
                foreach ($relationMappings as $relationName => $relationMapping) {
                    if (is_array($relationMapping)) {
                        // Map the relation data
                        $relationData = self::map(
                            $source,
                            [],
                            $relationMapping,
                            $skipNull,
                            $reindexWildcard,
                            $hooks,
                            $trimValues,
                            $caseInsensitiveReplace
                        );

                        // Set the relation using EntityHelper
                        EntityHelper::setAttribute($target, $relationName, $relationData);
                    }
                }

                // If there are regular mappings, process them normally
                if ([] !== $regularMappings) {
                    $mapping = MappingEngine::flattenNestedMapping($regularMappings);
                    /** @var array<string, string> $mapping */
                    $result = self::mapSimple(
                        $source,
                        $target,
                        $mapping,
                        $skipNull,
                        $reindexWildcard,
                        $hooks,
                        $trimValues,
                        $caseInsensitiveReplace
                    );

                    // Throw collected exceptions if collectExceptions is false
                    if (!MapperExceptions::isCollectExceptionsEnabled()) {
                        MapperExceptions::throwCollectedExceptions();
                    }

                    return $result;
                }

                // Throw collected exceptions if collectExceptions is false
                if (!MapperExceptions::isCollectExceptionsEnabled()) {
                    MapperExceptions::throwCollectedExceptions();
                }

                return $target;
            }

            // Flatten nested structure to simple source => target format
            $mapping = MappingEngine::flattenNestedMapping($mapping);
            /** @var array<string, string> $mapping */
            $result = self::mapSimple(
                $source,
                $target,
                $mapping,
                $skipNull,
                $reindexWildcard,
                $hooks,
                $trimValues,
                $caseInsensitiveReplace
            );

            // Throw collected exceptions if collectExceptions is false
            if (!MapperExceptions::isCollectExceptionsEnabled()) {
                MapperExceptions::throwCollectedExceptions();
            }

            return $result;
        }

        // Case 2: simple path-to-path mapping like ['a.b' => 'x.y']
        if (MappingEngine::isSimpleMapping($mapping)) {
            /** @var array<string, string> $mapping */
            $result = self::mapSimple(
                $source,
                $target,
                $mapping,
                $skipNull,
                $reindexWildcard,
                $hooks,
                $trimValues,
                $caseInsensitiveReplace
            );

            // Throw collected exceptions if collectExceptions is false
            if (!MapperExceptions::isCollectExceptionsEnabled()) {
                MapperExceptions::throwCollectedExceptions();
            }

            return $result;
        }

        // Case 3: structured mapping definitions with source/target objects
        /** @var array<int, array<string, mixed>> $mapping */
        $result = self::mapStructured(
            $source,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );

        // Throw collected exceptions if collectExceptions is false
        if (!MapperExceptions::isCollectExceptionsEnabled()) {
            MapperExceptions::throwCollectedExceptions();
        }

        return $result;
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
     * @param array<string, mixed> $hooks Optional hooks propagated to this mapping
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
     * Load data from a file (XML or JSON) and use it as source for mapping.
     *
     * Automatically detects file type by extension and parses accordingly:
     * - .xml: Parses XML and converts to array
     * - .json: Parses JSON to array
     *
     * The parsed data is then used as source in the mapping operation.
     *
     * Example:
     *   $project = DataMapper::mapFromFile(
     *       'data/project.xml',
     *       new Project(),
     *       ['xml.number' => 'number', 'xml.title' => 'title']
     *   );
     *
     * @param string $filePath Path to the XML or JSON file
     * @param mixed $target The target data (array, object, model, DTO, etc.)
     * @param array<int|string, mixed> $mapping Mapping definition
     * @param bool|MappingOptions $skipNull Skip null values (or MappingOptions object)
     * @param bool $reindexWildcard Reindex wildcard results
     * @param array<(DataMapperHook|string), mixed> $hooks Optional hooks
     * @param bool $trimValues Trim string values
     * @param bool $caseInsensitiveReplace Case insensitive replace
     * @return mixed The updated target
     * @throws InvalidArgumentException If file doesn't exist or has unsupported format
     */
    public static function mapFromFile(
        string $filePath,
        mixed $target,
        array $mapping,
        bool|MappingOptions $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        // Load file using FileLoader
        $source = FileLoader::loadAsArray($filePath);

        // Detect if target is a JSON or XML string
        $targetFormat = is_string($target) ? StringFormatDetector::detectFormat($target) : null;

        $result = self::map(
            $source,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );

        // Convert result back to JSON/XML string if target was a string
        if (null !== $targetFormat) {
            return self::convertResultToStringFormat($targetFormat, $result);
        }

        return $result;
    }

    /**
     * Convert result to string format (JSON or XML).
     *
     * @param string $format The target format ('json' or 'xml')
     * @param mixed $result The result to convert
     * @return string The converted string
     * @throws InvalidArgumentException If conversion fails
     */
    private static function convertResultToStringFormat(string $format, mixed $result): string
    {
        if ('json' === $format) {
            return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if ('xml' === $format) {
            if (!is_array($result)) {
                MapperExceptions::handleConversionException('Cannot convert non-array result to XML');

                return '';
            }

            /** @var array<string, mixed> $result */
            return self::arrayToXml($result);
        }

        MapperExceptions::handleConversionException("Cannot convert result to unknown format: {$format}");

        return '';
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
     * Map with raw paths (no {{ }} required). Used internally by AutoMapper.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<string, string|array{__static__: mixed}> $mapping Mapping with raw paths (no {{ }} syntax)
     * @param array<string, mixed> $hooks
     * @return array<int|string, mixed>|object
     * @internal
     */
    public static function mapWithRawPaths(
        mixed $source,
        array|object $target,
        array $mapping,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        array $hooks = [],
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): array|object {
        // All paths are treated as dynamic (no static values in AutoMapper)
        return self::mapSimpleInternal(
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
     * Handle simple path-to-path mapping.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<string, string> $mapping
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
        bool $trimValues,
        bool $caseInsensitiveReplace,
    ): array|object {
        // Parse mapping: extract {{ }} expressions to actual paths
        $parsedMapping = TemplateParser::parseMapping($mapping, self::STATIC_VALUE_MARKER);

        return self::mapSimpleInternal(
            $source,
            $target,
            $parsedMapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /**
     * Internal mapping method that works with already-parsed paths (no {{ }} needed).
     *
     * @param array<int|string, mixed>|object $target
     * @param array<string, string|array{__static__: mixed}> $mapping
     * @param array<string, mixed> $hooks
     * @return array<int|string, mixed>|object
     */
    private static function mapSimpleInternal(
        mixed $source,
        array|object $target,
        array $mapping,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): array|object {
        $accessor = new DataAccessor($source);

        // Global hook: beforeAll
        HookInvoker::invokeHooks($hooks, DataMapperHook::BeforeAll->value, new AllContext('simple', $mapping, $source, $target));

        $mappingIndex = 0;
        foreach ($mapping as $targetPath => $sourcePathOrStatic) {
            // Parse mapping entry (extract path, filters, default value, static flag)
            $parsed = MappingParser::parseEntry($sourcePathOrStatic, self::STATIC_VALUE_MARKER);
            $isStatic = $parsed['isStatic'];
            $sourcePath = $parsed['sourcePath'];
            $filters = $parsed['filters'];
            $defaultValue = $parsed['defaultValue'];

            $pairContext = new PairContext(
                'simple',
                $mappingIndex,
                $sourcePath,
                (string)$targetPath,
                $source,
                $target
            );
            if (HookInvoker::invokeHooks($hooks, DataMapperHook::BeforePair->value, $pairContext) === false) {
                $mappingIndex++;

                continue;
            }

            if ($isStatic) {
                // Static value: use as-is
                $value = $sourcePath;
                $actualSourcePath = null;
            } else {
                // Dynamic path: get value from source
                $actualSourcePath = (string)$sourcePath;

                // Check if source path exists (if enabled)
                // Don't throw if there's a default value or filters (filters can handle undefined values)
                if (!$accessor->exists($actualSourcePath) && null === $defaultValue && [] === $filters) {
                    MapperExceptions::handleUndefinedSourceValue($actualSourcePath, $source);
                }

                $value = $accessor->get($actualSourcePath);

                // Apply default value if value is null (from ?? operator)
                if (null === $value && null !== $defaultValue) {
                    $value = $defaultValue;
                }

                // Apply filters BEFORE skipNull check
                // This allows filters like 'default' to replace null values
                // Filters can handle arrays (e.g., callback filters that process array values)
                if ([] !== $filters) {
                    $value = TemplateExpressionProcessor::applyFilters($value, $filters);
                }
            }

            // Skip null values AFTER filters have been applied
            // This allows filters like 'default' to prevent skipping
            if ($skipNull && null === $value) {
                $mappingIndex++;

                continue;
            }

            // beforeTransform
            $value = HookInvoker::invokeValueHook($hooks, DataMapperHook::BeforeTransform->value, $pairContext, $value);

            // Skip if beforeTransform hook returned magic skip value
            if ('__skip__' === $value) {
                $mappingIndex++;

                continue;
            }

            // Apply trimValues (if enabled) - use empty replaceMap to trigger trimming
            if ($trimValues && !$isStatic) {
                $value = ValueTransformer::processValue($value, null, [], $trimValues, $caseInsensitiveReplace);
            }

            // Handle wildcard values (always arrays with dot-path keys) - only for dynamic paths
            if (is_array($value) && !$isStatic && null !== $actualSourcePath && str_contains($actualSourcePath, '*')) {
                // Normalize wildcard array (flatten dot-path keys to simple list)
                $value = WildcardHandler::normalizeWildcardArray($value);

                // Create transform function for filters if present
                $transformFn = null;
                if ([] !== $filters) {
                    $transformFn = (fn(mixed $itemValue): mixed => TemplateExpressionProcessor::applyFilters(
                        $itemValue,
                        $filters
                    ));
                }

                // Use centralized wildcard processing from MappingEngine
                $target = MappingEngine::processWildcardMapping(
                    $value,
                    $target,
                    (string)$sourcePath,
                    (string)$targetPath,
                    $source,
                    $mappingIndex,
                    $skipNull,
                    $reindexWildcard,
                    $hooks,
                    $pairContext,
                    $transformFn,  // Apply filters to each wildcard item
                    null,  // $replaceMap - not available in simple mapping
                    $trimValues,
                    $caseInsensitiveReplace
                );
            } else {
                $value = HookInvoker::invokeValueHook(
                    $hooks,
                    DataMapperHook::AfterTransform->value,
                    $pairContext,
                    $value
                );

                // Skip if afterTransform hook returned magic skip value
                if ('__skip__' === $value) {
                    $mappingIndex++;

                    continue;
                }

                $writeContext = new WriteContext(
                    'simple',
                    $mappingIndex,
                    (string)$sourcePath,
                    (string)$targetPath,
                    $source,
                    $target,
                    (string)$targetPath
                );
                $writeValue = HookInvoker::invokeValueHook($hooks, DataMapperHook::BeforeWrite->value, $writeContext, $value);
                if ('__skip__' !== $writeValue) {
                    // Check if target parent path exists (if enabled)
                    if (MapperExceptions::isThrowOnUndefinedTargetEnabled()) {
                        $targetPathString = (string)$targetPath;
                        if (str_contains($targetPathString, '.')) {
                            // Get parent path (everything before the last dot)
                            $lastDotPos = strrpos($targetPathString, '.');
                            // strrpos cannot return false here because str_contains already confirmed the dot exists
                            assert(false !== $lastDotPos);
                            $parentPath = substr($targetPathString, 0, $lastDotPos);

                            // Check if parent path exists in target
                            $targetAccessor = new DataAccessor(MappingEngine::asTarget($target));
                            if (!$targetAccessor->exists($parentPath)) {
                                MapperExceptions::handleUndefinedTargetValue($parentPath, $target);
                            }
                        }
                    }

                    $target = DataMutator::set(MappingEngine::asTarget($target), (string)$targetPath, $writeValue);

                    /** @var array<int|string, mixed>|object $target */
                    $target = HookInvoker::invokeTargetHook($hooks, DataMapperHook::AfterWrite->value, $writeContext, $writeValue, $target);
                }
            }

            HookInvoker::invokeHooks($hooks, DataMapperHook::AfterPair->value, $pairContext);
            $mappingIndex++;
        }

        HookInvoker::invokeHooks($hooks, DataMapperHook::AfterAll->value, new AllContext('simple', $mapping, $source, $target));

        /** @var array<int|string, mixed>|object */
        return $target;
    }

    /**
     * Resolve entry-specific options from a mapping entry.
     *
     * @param array<string, mixed> $map
     * @param array<int|string, mixed>|object $target
     * @param array<string, mixed> $hooks
     * @return array{
     *     entrySource: mixed,
     *     entryTarget: array<int|string, mixed>|object,
     *     entrySkipNull: bool,
     *     entryReindex: bool,
     *     accessor: DataAccessor,
     *     effectiveHooks: array<string, mixed>
     * }
     */
    private static function resolveEntryOptions(
        array $map,
        mixed $source,
        array|object $target,
        bool $skipNull,
        bool $reindexWildcard,
        array $hooks,
    ): array {
        $entrySource = $map['source'] ?? $source;

        /** @var array<int|string, mixed>|object $entryTarget */
        $entryTarget = $map['target'] ?? $target;
        if (!is_array($entryTarget) && !is_object($entryTarget)) {
            $entryTarget = [];
        }

        /** @var array<int|string, mixed>|object $entryTarget */
        $entrySkipNull = array_key_exists('skipNull', $map) ? (bool)$map['skipNull'] : $skipNull;

        $entryReindex = array_key_exists(
            'reindexWildcard',
            $map
        ) ? (bool)$map['reindexWildcard'] : $reindexWildcard;

        $accessor = new DataAccessor($entrySource);

        /** @var array<DataMapperHook|string, mixed> $entryHooks */
        $entryHooks = is_array($map['hooks'] ?? null) ? $map['hooks'] : [];
        $entryHooks = HookInvoker::normalizeHooks($entryHooks);

        $effectiveHooks = HookInvoker::mergeHooks($hooks, $entryHooks);

        return [
            'entrySource' => $entrySource,
            'entryTarget' => $entryTarget,
            'entrySkipNull' => $entrySkipNull,
            'entryReindex' => $entryReindex,
            'accessor' => $accessor,
            'effectiveHooks' => $effectiveHooks,
        ];
    }

    /**
     * Handle structured mapping definitions with source/target objects.
     *
     * @param array<int|string, mixed>|object $target
     * @param array<int, array<string, mixed>> $mapping
     * @param array<string, mixed> $hooks
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
        HookInvoker::invokeHooks($hooks, DataMapperHook::BeforeAll->value, new AllContext('structured', $mapping, $source, $target));

        // Case 2: structured mapping definitions with source/target objects
        foreach ($mapping as $map) {
            if (!is_array($map)) {
                MapperExceptions::handleInvalidMappingException('Advanced mapping definitions must be arrays.');

                continue;
            }

            $entryOptions = self::resolveEntryOptions(
                $map,
                $source,
                $target, // @phpstan-ignore-line argument.type
                $skipNull,
                $reindexWildcard,
                $hooks
            );
            $entrySource = $entryOptions['entrySource'];
            $entryTarget = $entryOptions['entryTarget'];
            $entrySkipNull = $entryOptions['entrySkipNull'];
            $entryReindex = $entryOptions['entryReindex'];
            $accessor = $entryOptions['accessor'];
            $effectiveHooks = $entryOptions['effectiveHooks'];

            HookInvoker::invokeHooks(
                $effectiveHooks,
                DataMapperHook::BeforeEntry->value,
                new EntryContext('structured', $map, $entrySource, $entryTarget)
            );

            // Support either explicit source/target mapping arrays, or a single associative/list 'mapping'
            if (isset($map['sourceMapping']) || isset($map['targetMapping'])) {
                /** @var array<int|string, mixed> $sourcePathMapping */
                $sourcePathMapping = $map['sourceMapping'] ?? [];

                /** @var array<int|string, mixed> $targetPathMapping */
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

                    MapperExceptions::handleInvalidMappingException($msg);

                    continue;
                }

                foreach ($sourcePathMapping as $pairIndex => $sourcePath) {
                    $targetPath = $targetPathMapping[$pairIndex] ?? null;
                    if (null === $targetPath) {
                        continue;
                    }

                    $pairContext = new PairContext(
                        'structured',
                        is_int($pairIndex) ? $pairIndex : 0,
                        (string)$sourcePath,
                        (string)$targetPath,
                        $entrySource,
                        $entryTarget
                    );
                    if (HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::BeforePair->value, $pairContext) === false) {
                        continue;
                    }

                    $value = $accessor->get((string)$sourcePath);
                    if ($entrySkipNull && null === $value) {
                        HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);

                        continue;
                    }

                    // beforeTransform
                    $value = HookInvoker::invokeValueHook(
                        $effectiveHooks,
                        DataMapperHook::BeforeTransform->value,
                        $pairContext,
                        $value
                    );

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
                            function(int|string $wildcardIndex, mixed $itemValue) use (
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
                                return MappingEngine::processWildcardItem(
                                    $wildcardIndex,
                                    $itemValue,
                                    $entryTarget,
                                    $effectiveHooks,
                                    $pairContext,
                                    $transformFn,
                                    $replaceMap,
                                    $trimValues,
                                    $caseInsensitiveReplace,
                                    'structured',
                                    is_int($pairIndex) ? $pairIndex : 0,
                                    (string)$sourcePath,
                                    (string)$targetPath,
                                    $entrySource,
                                    $entrySkipNull
                                );
                            }
                        );
                    } else {
                        // Process single non-wildcard value
                        $processed = MappingEngine::processSingleValue(
                            $value,
                            $entryTarget,
                            $effectiveHooks,
                            $pairContext,
                            $transformFn,
                            $replaceMap,
                            $trimValues,
                            $caseInsensitiveReplace,
                            'structured',
                            is_int($pairIndex) ? $pairIndex : 0,
                            (string)$sourcePath,
                            (string)$targetPath,
                            $entrySource,
                            $entrySkipNull
                        );

                        if (!$processed) {
                            HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);

                            continue;
                        }
                    }

                    HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
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
                        if (HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::BeforePair->value, $pairContext) === false) {
                            $mappingIndexAssoc++;

                            continue;
                        }

                        $value = $accessor->get((string)$sourcePath);
                        if ($entrySkipNull && null === $value) {
                            HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                            $mappingIndexAssoc++;

                            continue;
                        }

                        // beforeTransform
                        $value = HookInvoker::invokeValueHook(
                            $effectiveHooks,
                            DataMapperHook::BeforeTransform->value,
                            $pairContext,
                            $value
                        );

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
                                function(int|string $wildcardIndex, mixed $itemValue) use (
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
                                    return MappingEngine::processWildcardItem(
                                        $wildcardIndex,
                                        $itemValue,
                                        $entryTarget,
                                        $effectiveHooks,
                                        $pairContext,
                                        $transformFn,
                                        $replaceMap,
                                        $trimValues,
                                        $caseInsensitiveReplace,
                                        'structured',
                                        $mappingIndexAssoc,
                                        (string)$sourcePath,
                                        (string)$targetPath,
                                        $entrySource,
                                        $entrySkipNull
                                    );
                                }
                            );
                        } else {
                            // Process single non-wildcard value
                            $processed = MappingEngine::processSingleValue(
                                $value,
                                $entryTarget,
                                $effectiveHooks,
                                $pairContext,
                                $transformFn,
                                $replaceMap,
                                $trimValues,
                                $caseInsensitiveReplace,
                                'structured',
                                $mappingIndexAssoc,
                                (string)$sourcePath,
                                (string)$targetPath,
                                $entrySource,
                                $entrySkipNull
                            );

                            if (!$processed) {
                                HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                                $mappingIndexAssoc++;

                                continue;
                            }
                        }
                        HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                        $mappingIndexAssoc++;
                    }
                } else {
                    // List of pairs: [[sourcePath, targetPath], ...]
                    $pairIndex = 0;

                    /** @var array<int, mixed> $pairs */
                    foreach ($pairs as $mappingPair) {
                        if (!is_array($mappingPair) || count($mappingPair) !== 2) {
                            MapperExceptions::handleInvalidMappingException(
                                'Invalid mapping pair. Expected [sourcePath, targetPath].'
                            );

                            continue;
                        }
                        [$sourcePath, $targetPath] = $mappingPair;
                        if (!is_string($sourcePath) || !is_string($targetPath)) {
                            MapperExceptions::handleInvalidMappingException('Mapping paths must be strings.');

                            continue;
                        }
                        $pairContext = new PairContext(
                            'structured-pairs',
                            $pairIndex,
                            $sourcePath,
                            $targetPath,
                            $entrySource,
                            $entryTarget
                        );
                        if (HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::BeforePair->value, $pairContext) === false) {
                            $pairIndex++;

                            continue;
                        }

                        $value = $accessor->get($sourcePath);
                        if ($entrySkipNull && null === $value) {
                            HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                            $pairIndex++;

                            continue;
                        }
                        $value = HookInvoker::invokeValueHook(
                            $effectiveHooks,
                            DataMapperHook::BeforeTransform->value,
                            $pairContext,
                            $value
                        );

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
                                function(int|string $wildcardIndex, mixed $itemValue) use (
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
                                    return MappingEngine::processWildcardItem(
                                        $wildcardIndex,
                                        $itemValue,
                                        $entryTarget,
                                        $effectiveHooks,
                                        $pairContext,
                                        $transformFn,
                                        $replaceMap,
                                        $trimValues,
                                        $caseInsensitiveReplace,
                                        'structured',
                                        $pairIndex,
                                        $sourcePath,
                                        $targetPath,
                                        $entrySource,
                                        $entrySkipNull
                                    );
                                }
                            );
                        } else {
                            // Process single non-wildcard value
                            $processed = MappingEngine::processSingleValue(
                                $value,
                                $entryTarget,
                                $effectiveHooks,
                                $pairContext,
                                $transformFn,
                                $replaceMap,
                                $trimValues,
                                $caseInsensitiveReplace,
                                'structured',
                                $pairIndex,
                                $sourcePath,
                                $targetPath,
                                $entrySource,
                                $entrySkipNull
                            );

                            if (!$processed) {
                                HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                                $pairIndex++;

                                continue;
                            }
                        }
                        HookInvoker::invokeHooks($effectiveHooks, DataMapperHook::AfterPair->value, $pairContext);
                        $pairIndex++;
                    }
                }
            } else {
                MapperExceptions::handleInvalidMappingException(
                    'Advanced mapping entry must contain source, target and mapping (or sourceMapping/targetMapping).'
                );

                continue;
            }

            HookInvoker::invokeHooks(
                $effectiveHooks,
                DataMapperHook::AfterEntry->value,
                new EntryContext('structured', $map, $entrySource, $entryTarget)
            );

            $target = $entryTarget;
        }

        // Global hook: afterAll for structured mode
        HookInvoker::invokeHooks($hooks, DataMapperHook::AfterAll->value, new AllContext('structured', $mapping, $source, $target));

        /** @var array<int|string, mixed>|object $target */
        return $target;
    }

    /**
     * Convert array to XML string.
     *
     * @param array<string, mixed> $array The array to convert
     * @param string $rootElement The root element name
     * @return string The XML string
     */
    private static function arrayToXml(array $array, string $rootElement = self::DEFAULT_XML_ROOT): string
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><' . $rootElement . '></' . $rootElement . '>'
        );
        self::arrayToXmlRecursive($array, $xml, null);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $xmlString = $xml->asXML();
        if (false === $xmlString) {
            MapperExceptions::handleRuntimeException('Failed to convert SimpleXMLElement to XML string');

            return '';
        }

        $dom->loadXML($xmlString);

        $result = $dom->saveXML();
        if (false === $result) {
            MapperExceptions::handleRuntimeException('Failed to save XML document');

            return '';
        }

        return $result;
    }

    /**
     * Recursively convert array to XML.
     *
     * @param array<string, mixed> $array The array to convert
     * @param SimpleXMLElement $xml The XML element to append to
     * @param string|null $parentKey The parent key name for singularization
     */
    private static function arrayToXmlRecursive(array $array, SimpleXMLElement $xml, ?string $parentKey): void
    {
        foreach ($array as $key => $value) {
            $elementName = $key;

            // Handle numeric keys - use singular of parent key
            if (is_numeric($key)) {
                if (null !== $parentKey) {
                    $elementName = self::singularize($parentKey);
                } else {
                    $elementName = 'item';
                }
            }

            // Sanitize key to be valid XML element name
            $elementName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$elementName);

            if (is_array($value)) {
                $subnode = $xml->addChild((string)$elementName);
                if ($subnode instanceof SimpleXMLElement) {
                    /** @var array<string, mixed> $value */
                    self::arrayToXmlRecursive($value, $subnode, (string)$key);
                }
            } else {
                $xml->addChild(
                    (string)$elementName,
                    htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                );
            }
        }
    }

    /**
     * Convert plural word to singular.
     *
     * @param string $word The word to singularize
     * @return string The singularized word
     */
    private static function singularize(string $word): string
    {
        // Check cache first
        static $cache = [];
        if (isset($cache[$word])) {
            return $cache[$word];
        }

        // Common irregular plurals
        $irregulars = [
            'people' => 'person',
            'men' => 'man',
            'women' => 'woman',
            'children' => 'child',
            'teeth' => 'tooth',
            'feet' => 'foot',
            'mice' => 'mouse',
            'geese' => 'goose',
        ];

        $lower = strtolower($word);
        if (isset($irregulars[$lower])) {
            $cache[$word] = $irregulars[$lower];
            return $cache[$word];
        }

        // Words ending in 'ies' -> 'y' (e.g., categories -> category)
        if (preg_match('/(.+)ies$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 'y';
            return $cache[$word];
        }

        // Words ending in 'ves' -> 'fe' or 'f' (e.g., knives -> knife, wolves -> wolf)
        if (preg_match('/(.+)ves$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 'f';
            return $cache[$word];
        }

        // Words ending in 'ses' -> 's' (e.g., cases -> case)
        if (preg_match('/(.+)ses$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 's';
            return $cache[$word];
        }

        // Words ending in 'xes' -> 'x' (e.g., boxes -> box)
        if (preg_match('/(.+)xes$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 'x';
            return $cache[$word];
        }

        // Words ending in 'ches' -> 'ch' (e.g., churches -> church)
        if (preg_match('/(.+)ches$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 'ch';
            return $cache[$word];
        }

        // Words ending in 'shes' -> 'sh' (e.g., dishes -> dish)
        if (preg_match('/(.+)shes$/i', $word, $matches)) {
            $cache[$word] = $matches[1] . 'sh';
            return $cache[$word];
        }

        // Words ending in 's' but not 'ss' -> remove 's' (e.g., departments -> department)
        if (preg_match('/(.+[^s])s$/i', $word, $matches)) {
            $cache[$word] = $matches[1];
            return $cache[$word];
        }

        // If no rule matches, return the word as-is
        $cache[$word] = $word;
        return $word;
    }
}
