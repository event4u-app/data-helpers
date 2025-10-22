<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Support\MappingFacade;
use event4u\DataHelpers\DataMapper\Support\MappingReverser;
use event4u\DataHelpers\DataMapper\Support\WildcardOperatorRegistry;
use event4u\DataHelpers\Enums\DataMapperHook;
use event4u\DataHelpers\Helpers\ObjectHelper;
use event4u\DataHelpers\Support\FileLoader;
use event4u\DataHelpers\Support\StringFormatDetector;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * FluentDataMapper - Fluent API for data mapping.
 *
 * Provides a fluent interface for configuring and executing data mappings.
 *
 * Example:
 *   $mapper = DataMapper::source($source);
 *   $result = $mapper
 *       ->target($target)
 *       ->template($template)
 *       ->pipeline([new TrimStrings()])
 *       ->map();
 *
 *   $result->getTarget();
 *   $result->toJson();
 *   $result->toArray();
 */
final class FluentDataMapper
{
    private mixed $source = null;

    private mixed $target = [];

    /** @var array<int|string, mixed> */
    private array $template = [];

    /** @var array<int|string, mixed> */
    private array $originalTemplate = [];

    /** @var array<int, FilterInterface|class-string<FilterInterface>> */
    private array $pipelineFilters = [];

    /** @var array<string, array<int, FilterInterface>> */
    private array $propertyFilters = [];

    /** @var array<string, MapperQuery> */
    private array $queries = [];

    private bool $skipNull = true;

    private bool $reindexWildcard = false;

    private bool $trimValues = false;

    private bool $caseInsensitiveReplace = false;

    private bool $deep = false;

    /** @var array<string, mixed> */
    private array $hooks = [];

    private ?MappingOptions $mappingOptions = null;

    private ?string $discriminatorField = null;

    /** @var array<string, class-string> */
    private array $discriminatorMap = [];

    private ?DataMapperExceptionHandler $exceptionHandler = null;

    private bool $reverseMapping = false;

    /**
     * Create a new FluentDataMapper instance.
     *
     * @param mixed $source Optional source data (array, object, model, DTO, JSON, XML, file path)
     */
    public function __construct(mixed $source = null)
    {
        if (null !== $source) {
            $this->setSource($source);
        }
        $this->exceptionHandler = new DataMapperExceptionHandler();
    }

    /**
     * Set the source data with automatic file detection.
     *
     * If the source is a string and points to an existing file, it will be loaded automatically.
     *
     * @param mixed $source Source data (array, object, model, DTO, JSON, XML, or file path)
     */
    public function source(mixed $source): self
    {
        if (is_string($source) && file_exists($source)) {
            return $this->sourceFile($source);
        }

        return $this->setSource($source);
    }

    /**
     * Set the source from a file.
     *
     * @param string $filePath Path to the source file (JSON, XML, etc.)
     */
    public function sourceFile(string $filePath): self
    {
        $source = FileLoader::loadAsArray($filePath);
        return $this->setSource($source);
    }

    /**
     * Set multiple named sources for multi-source mapping.
     *
     * @param array<string, mixed> $sources Named sources (e.g., ['user' => [...], 'company' => [...]])
     */
    public function sources(array $sources): self
    {
        return $this->setSource($sources);
    }

    /**
     * Internal method to set the source.
     *
     * @param mixed $source Source data
     */
    private function setSource(mixed $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set the target.
     *
     * @param mixed $target Target (Object, Array, String (JSON/XML), String (Klassenname))
     */
    public function target(mixed $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Set the template.
     *
     * @param array<int|string, mixed>|string $template Mapping template (array, JSON string, or XML string)
     */
    public function template(array|string $template): self
    {
        // Convert JSON/XML strings to arrays
        if (is_string($template)) {
            $template = $this->parseTemplateString($template);
        }

        $this->template = $template;
        // Store original template for reference
        if ([] === $this->originalTemplate) {
            $this->originalTemplate = $template;
        }

        return $this;
    }

    /**
     * Parse a template string (JSON or XML) to an array.
     *
     * @param string $template Template string
     * @return array<int|string, mixed>
     * @throws InvalidArgumentException If the template is not valid JSON or XML
     */
    private function parseTemplateString(string $template): array
    {
        // Try JSON first
        if (StringFormatDetector::isJson($template)) {
            $decoded = json_decode($template, true);
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('Invalid JSON template');
            }
            return $decoded;
        }

        // Try XML
        if (StringFormatDetector::isXml($template)) {
            $accessor = new DataAccessor($template);
            return $accessor->toArray();
        }

        throw new InvalidArgumentException('Template must be a valid JSON or XML string, or an array');
    }

    /**
     * Set pipeline filters.
     *
     * @param array<int, FilterInterface|class-string<FilterInterface>> $filters Filter instances or class names
     */
    public function pipeline(array $filters): self
    {
        $this->pipelineFilters = $filters;

        return $this;
    }

    /**
     * Build hooks from pipeline filters.
     *
     * @param array<int, FilterInterface|class-string<FilterInterface>> $filters
     * @return array<string, mixed>
     */
    private function buildPipelineHooks(array $filters): array
    {
        $hooks = [];

        foreach ($filters as $filter) {
            if (is_string($filter)) {
                $filter = new $filter();
            }

            $hookName = $filter->getHook();
            $filterName = $filter->getFilter();
            $callback = fn($value, $context) => $filter->transform($value, $context);

            if (null !== $filterName) {
                if (!isset($hooks[$hookName])) {
                    $hooks[$hookName] = [];
                }
                if (!is_array($hooks[$hookName])) {
                    $hooks[$hookName] = [$hooks[$hookName]];
                }
                $hooks[$hookName][$filterName] = $callback;
            } elseif (!isset($hooks[$hookName])) {
                $hooks[$hookName] = $callback;
            } elseif (is_callable($hooks[$hookName])) {
                $hooks[$hookName] = [$hooks[$hookName], $callback];
            } elseif (is_array($hooks[$hookName])) {
                $hooks[$hookName][] = $callback;
            }
        }

        return $hooks;
    }

    /**
     * Get the original template (before any modifications).
     *
     * @return array<int|string, mixed>
     */
    public function getOriginalTemplate(): array
    {
        return $this->originalTemplate;
    }

    /** Start a reset chain to reset template parts to original values. */
    public function reset(): DataMapperReset
    {
        return new DataMapperReset($this);
    }

    /** Start a delete chain to delete template operators. */
    public function delete(): DataMapperDelete
    {
        return new DataMapperDelete($this);
    }

    /**
     * Reset entire template to original template.
     * Also clears all queries to prevent them from being reapplied.
     *
     * @internal Used by DataMapperReset
     */
    public function resetTemplateToOriginal(): void
    {
        $this->template = $this->originalTemplate;
        // Clear queries to prevent them from being reapplied
        $this->queries = [];
    }

    /**
     * Reset a specific operator in template to original value.
     * Also clears corresponding query conditions.
     *
     * @param string $operator Operator name (e.g., 'WHERE', 'ORDER BY')
     * @internal Used by DataMapperReset
     */
    public function resetTemplateOperator(string $operator): void
    {
        $this->template = $this->resetOperatorRecursive($this->template, $this->originalTemplate, $operator);

        // Clear corresponding query conditions
        foreach ($this->queries as $query) {
            $query->clearConditionsForOperator($operator);
        }
    }

    /**
     * Delete a specific operator from template.
     *
     * @param string $operator Operator name (e.g., 'WHERE', 'ORDER BY')
     * @internal Used by DataMapperDelete
     */
    public function deleteTemplateOperator(string $operator): void
    {
        $this->template = $this->deleteOperatorRecursive($this->template, $operator);
    }

    /**
     * Recursively reset operator in template to original value.
     *
     * @param array<int|string, mixed> $template Current template
     * @param array<int|string, mixed> $original Original template
     * @param string $operator Operator to reset
     * @return array<int|string, mixed>
     */
    private function resetOperatorRecursive(array $template, array $original, string $operator): array
    {
        foreach ($template as $key => &$value) {
            if (is_array($value)) {
                // Check if this level has the operator
                if (isset($value[$operator])) {
                    // Reset to original value if exists, otherwise delete
                    $originalValue = $original[$key] ?? null;
                    if (is_array($originalValue) && isset($originalValue[$operator])) {
                        $value[$operator] = $originalValue[$operator];
                    } else {
                        unset($value[$operator]);
                    }
                }

                // Recurse into nested arrays
                if (isset($original[$key]) && is_array($original[$key])) {
                    $value = $this->resetOperatorRecursive($value, $original[$key], $operator);
                } else {
                    $value = $this->resetOperatorRecursive($value, [], $operator);
                }
            }
        }

        return $template;
    }

    /**
     * Recursively delete operator from template.
     *
     * @param array<int|string, mixed> $template Current template
     * @param string $operator Operator to delete
     * @return array<int|string, mixed>
     */
    private function deleteOperatorRecursive(array $template, string $operator): array
    {
        foreach ($template as &$value) {
            if (is_array($value)) {
                // Delete operator if exists at this level
                if (isset($value[$operator])) {
                    unset($value[$operator]);
                }

                // Recurse into nested arrays
                $value = $this->deleteOperatorRecursive($value, $operator);
            }
        }

        return $template;
    }

    /**
     * Extend the template (merge with existing template).
     *
     * This method merges the provided template with the existing template,
     * allowing you to add or override specific mappings without replacing
     * the entire template.
     *
     * Example:
     *   $mapper->template(['name' => '{{ user.name }}'])
     *          ->extendTemplate(['email' => '{{ user.email }}']);
     *   // Result: ['name' => '{{ user.name }}', 'email' => '{{ user.email }}']
     *
     * @param array<int|string, mixed> $template Template to merge
     */
    public function extendTemplate(array $template): self
    {
        $this->template = array_merge($this->template, $template);

        return $this;
    }

    /**
     * Add a single pipeline filter to the existing filters.
     *
     * This method appends a filter to the existing pipeline filters,
     * allowing you to build up the filter chain incrementally.
     *
     * Example:
     *   $mapper->pipeline([new TrimStrings()])
     *          ->addPipelineFilter(new UppercaseStrings());
     *   // Result: [TrimStrings, UppercaseStrings]
     *
     * @param FilterInterface $filter Filter to add
     */
    public function addPipelineFilter(FilterInterface $filter): self
    {
        $this->pipelineFilters[] = $filter;

        return $this;
    }

    /**
     * Set discriminator for automatic subclass selection.
     *
     * This method enables Liskov Substitution Principle by automatically
     * selecting the correct subclass based on a discriminator field value.
     *
     * Example:
     *   $mapper->target(Animal::class)
     *          ->discriminator('type', [
     *              'dog' => Dog::class,
     *              'cat' => Cat::class,
     *          ]);
     *
     * When mapping, the 'type' field in the source data will determine
     * which class to instantiate (Dog or Cat instead of Animal).
     *
     * @param string $field Discriminator field name (dot-notation supported)
     * @param array<string, class-string> $map Map of discriminator values to class names
     */
    public function discriminator(string $field, array $map): self
    {
        $this->discriminatorField = $field;
        $this->discriminatorMap = $map;

        return $this;
    }

    /**
     * Set filters for a specific property (dot-notation supported).
     *
     * Supports multiple call styles:
     *   - Single filter: setValueFilters('user.name', $filter1)
     *   - Multiple filters as arguments: setValueFilters('user.name', $filter1, $filter2)
     *   - Multiple filters as array: setValueFilters('user.name', [$filter1, $filter2])
     *   - Single filter as array: setValueFilters('user.name', [$filter1])
     *
     * @param string $property Property path (dot-notation)
     * @param FilterInterface|array<int, FilterInterface> ...$filters Filter instances
     */
    public function setValueFilters(string $property, FilterInterface|array ...$filters): self
    {
        // Flatten the filters array
        $flatFilters = [];
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                foreach ($filter as $f) {
                    $flatFilters[] = $f;
                }
            } else {
                $flatFilters[] = $filter;
            }
        }

        $this->propertyFilters[$property] = $flatFilters;

        return $this;
    }

    /**
     * Alias for setValueFilters().
     *
     * @param string $property Property path (dot-notation)
     * @param FilterInterface|array<int, FilterInterface> ...$filters Filter instances
     */
    public function setFilter(string $property, FilterInterface|array ...$filters): self
    {
        return $this->setValueFilters($property, ...$filters);
    }

    /**
     * Set multiple filters at once.
     *
     * @param array<string, FilterInterface|array<int, FilterInterface>> $filters Map of property paths to filters
     */
    public function setFilters(array $filters): self
    {
        foreach ($filters as $property => $filter) {
            $this->setFilter($property, $filter);
        }

        return $this;
    }

    /**
     * Reset filters for a specific property.
     *
     * @param string $property Property path (dot-notation)
     * @internal Used by DataMapperProperty
     */
    public function resetPropertyFilters(string $property): void
    {
        unset($this->propertyFilters[$property]);
    }

    /**
     * Get filters for a specific property.
     *
     * @param string $property Property path (dot-notation)
     * @return array<int, FilterInterface>
     * @internal Used by DataMapperProperty
     */
    public function getPropertyFilters(string $property): array
    {
        return $this->propertyFilters[$property] ?? [];
    }

    /**
     * Get the mapping target for a specific property.
     *
     * @param string $property Property path (dot-notation)
     * @internal Used by DataMapperProperty
     */
    public function getPropertyTarget(string $property): mixed
    {
        return $this->getValueFromPath($this->template, $property);
    }

    /**
     * Get the mapped value for a specific property.
     *
     * @param string $property Property path (dot-notation)
     * @internal Used by DataMapperProperty
     */
    public function getPropertyMappedValue(string $property): mixed
    {
        $result = $this->map();
        $target = $result->getTarget();

        if (!is_array($target)) {
            return null;
        }

        return $this->getValueFromPath($target, $property);
    }

    /**
     * Create a property accessor for fluent property operations.
     *
     * @param string $property Property path (dot-notation)
     */
    public function property(string $property): DataMapperProperty
    {
        return new DataMapperProperty($property, $this);
    }

    /**
     * Get value from nested array using dot-notation path.
     *
     * @param array<int|string, mixed> $data Data array
     * @param string $path Dot-notation path
     */
    private function getValueFromPath(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Create a query on a wildcard path.
     *
     * @param string $wildcardPath Wildcard path (e.g., 'project.positions.*')
     */
    public function query(string $wildcardPath): MapperQuery
    {
        $query = new MapperQuery($wildcardPath, $this);
        $this->queries[$wildcardPath] = $query;

        return $query;
    }

    public function skipNull(bool $skipNull = true): self
    {
        $this->skipNull = $skipNull;

        return $this;
    }

    public function reindexWildcard(bool $reindexWildcard = false): self
    {
        $this->reindexWildcard = $reindexWildcard;

        return $this;
    }

    public function trimValues(bool $trimValues = true): self
    {
        $this->trimValues = $trimValues;

        return $this;
    }

    public function caseInsensitiveReplace(bool $caseInsensitiveReplace = false): self
    {
        $this->caseInsensitiveReplace = $caseInsensitiveReplace;

        return $this;
    }

    public function deep(bool $deep = true): self
    {
        $this->deep = $deep;

        return $this;
    }

    /** @param array<string, mixed> $hooks */
    public function hooks(array $hooks = []): self
    {
        $this->hooks = $hooks;

        return $this;
    }

    public function options(MappingOptions $options): self
    {
        $this->mappingOptions = $options;

        return $this;
    }

    /**
     * Execute the mapping.
     *
     * @param bool $withQuery Whether to apply queries
     */
    public function map(bool $withQuery = true): DataMapperResult
    {
        // Apply queries to template if enabled
        $template = $this->template;
        $hasQueries = $withQuery && [] !== $this->queries;
        if ($hasQueries) {
            $template = $this->applyQueriesToTemplate($template);
        }

        // Reverse mapping if enabled
        if ($this->reverseMapping) {
            $template = MappingReverser::reverseMapping($template);
        }

        // Resolve target class using discriminator if configured
        $target = $this->resolveTargetWithDiscriminator();

        // Merge hooks with property filters
        $hooks = $this->mergeHooks($this->hooks);

        // Check if we have target aliases (e.g., ['user' => $model, 'addr' => []])
        $hasTargetAliases = is_array($target) && $this->hasTargetAliases($target);

        // Check if template has target alias references (e.g., '@user.name')
        $hasTargetAliasReferences = $this->hasTargetAliasReferences($template);

        // If we have target aliases and template references them, use mapToTargetsFromTemplate
        if ($hasTargetAliases && $hasTargetAliasReferences) {
            // Clean template: remove {{ }}, @ from values
            $cleanedTemplate = $this->cleanTemplateForTargetAliases($template);

            $result = MappingFacade::mapToTargetsFromTemplate(
                $this->source, // @phpstan-ignore-line argument.type
                $cleanedTemplate, // @phpstan-ignore-line argument.type
                $target, // @phpstan-ignore-line argument.type
                $this->getSkipNullValue(),
                $this->getReindexWildcardValue()
            );

            return new DataMapperResult($result, $this->source, $template, $this->exceptionHandler);
        }

        // Check if source is an array with aliases and template uses @alias syntax
        $hasSourceAliases = is_array($this->source) && $this->hasSourceAliases($this->source);
        $hasSourceAliasReferences = $this->hasSourceAliasReferences($template);

        // If template contains wildcard operators OR uses @alias syntax, we need to use mapFromTemplate()
        // This includes both query-generated operators and manually added operators in template
        if ($this->hasWildcardOperators($template) || ($hasSourceAliases && $hasSourceAliasReferences)) {
            // If source is already an array with aliases, use it directly
            if ($hasSourceAliases && $hasSourceAliasReferences) {
                assert(is_array($this->source), 'Source must be array when using aliases');
                $namedSources = $this->source;
            } else {
                // Extract source names from template
                $sourceNames = $this->extractSourceNamesFromTemplate($template);

                // Build named sources array
                $namedSources = [];
                foreach ($sourceNames as $sourceName) {
                    // Check if source is nested (e.g., 'products' in ['products' => [...]])
                    if (is_array($this->source) && isset($this->source[$sourceName])) {
                        $namedSources[$sourceName] = $this->source[$sourceName];
                    } else {
                        // Use entire source with a default name
                        $namedSources[$sourceName] = $this->source;
                    }
                }
            }

            // Use mapFromTemplate for wildcard operator support
            // When using queries, reindexWildcard should default to true for consistent behavior
            $mappedArray = MappingFacade::mapFromTemplate(
                $template, // @phpstan-ignore-line argument.type
                $namedSources,
                $this->getSkipNullValue(),
                $hasSourceAliases && $hasSourceAliasReferences ? $this->getReindexWildcardValue() : true
            );

            // If target is an object, map the array result to the object
            if (is_object($target)) {
                // Create a simple mapping: key => {{ key }}
                $simpleMapping = [];
                foreach (array_keys($mappedArray) as $key) {
                    $simpleMapping[$key] = '{{ ' . $key . ' }}';
                }

                $result = MappingFacade::map(
                    $mappedArray,
                    $target,
                    $simpleMapping,
                    $this->getSkipNullValue(),
                    $this->getReindexWildcardValue(),
                    $hooks,
                    $this->trimValues,
                    $this->caseInsensitiveReplace
                );
            } else {
                $result = $mappedArray;
            }
        } elseif ([] !== $this->pipelineFilters) {
            // Use pipeline with merged hooks
            $pipelineHooks = $this->buildPipelineHooks($this->pipelineFilters);
            $mergedHooks = $this->mergeHooksWithPipeline($hooks, $pipelineHooks);

            $result = MappingFacade::map(
                $this->source,
                $target,
                $template,
                $this->getSkipNullValue(),
                $this->getReindexWildcardValue(),
                $mergedHooks,
                $this->trimValues,
                $this->caseInsensitiveReplace
            );
        } else {
            // Direct mapping with merged hooks
            $result = MappingFacade::map(
                $this->source,
                $target,
                $template,
                $this->getSkipNullValue(),
                $this->getReindexWildcardValue(),
                $hooks,
                $this->trimValues,
                $this->caseInsensitiveReplace
            );
        }

        // Convert result back to JSON/XML string if target was a string
        if (is_string($this->target)) {
            $targetFormat = StringFormatDetector::detectFormat($this->target);
            if (null !== $targetFormat) {
                if ('json' === $targetFormat) {
                    $result = json_encode(
                        $result,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ) ?: '{}';
                } elseif ('xml' === $targetFormat && is_array($result)) {
                    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
                    $this->arrayToXmlRecursive($result, $xml, null);
                    $result = $xml->asXML() ?: '';
                }
            }
        }

        return new DataMapperResult($result, $this->source, $template, $this->exceptionHandler);
    }

    /**
     * Execute reverse mapping.
     *
     * @param bool $withQuery Whether to apply queries
     */
    public function reverseMap(bool $withQuery = true): DataMapperResult
    {
        // Apply queries to template if enabled
        $template = $this->template;
        if ($withQuery && [] !== $this->queries) {
            $template = $this->applyQueriesToTemplate($template);
        }

        // Resolve target class using discriminator if configured
        $target = $this->resolveTargetWithDiscriminator();

        // Merge hooks with property filters
        $hooks = $this->mergeHooks($this->hooks);

        // Reverse the template
        $reversedTemplate = MappingReverser::reverseMapping($template);

        // Execute reverse mapping
        if ([] !== $this->pipelineFilters) {
            // Use pipeline with reversed template and merged hooks
            $pipelineHooks = $this->buildPipelineHooks($this->pipelineFilters);
            $mergedHooks = $this->mergeHooksWithPipeline($hooks, $pipelineHooks);

            $result = MappingFacade::map(
                $this->source,
                $target,
                $reversedTemplate,
                $this->getSkipNullValue(),
                $this->getReindexWildcardValue(),
                $mergedHooks,
                $this->trimValues,
                $this->caseInsensitiveReplace
            );
        } else {
            // Direct mapping with reversed template and merged hooks
            $result = MappingFacade::map(
                $this->source,
                $target,
                $reversedTemplate,
                $this->getSkipNullValue(),
                $this->getReindexWildcardValue(),
                $hooks,
                $this->trimValues,
                $this->caseInsensitiveReplace
            );
        }

        return new DataMapperResult($result, $this->source, $template, $this->exceptionHandler);
    }

    /**
     * Create a deep copy of this mapper.
     * Uses ObjectHelper to ensure all nested objects are truly independent.
     */
    public function copy(): self
    {
        // Use ObjectHelper for deep copy (positional arguments for PHPStan)
        $copy = ObjectHelper::copy($this, true, 10);

        // Ensure we got the correct type back
        assert($copy instanceof self, 'Copy must be instance of FluentDataMapper');

        // Create a new exception handler for the copy (not shared!)
        $copy->exceptionHandler = new DataMapperExceptionHandler();

        return $copy;
    }

    /** Get skipNull value (handle MappingOptions). */
    private function getSkipNullValue(): bool
    {
        return $this->mappingOptions->skipNull ?? $this->skipNull;
    }

    /** Get reindexWildcard value (handle MappingOptions). */
    private function getReindexWildcardValue(): bool
    {
        return $this->mappingOptions->reindexWildcard ?? $this->reindexWildcard;
    }

    /**
     * Build hooks from property filters.
     *
     * @return array<string, mixed>
     */
    private function buildPropertyFilterHooks(): array
    {
        if ([] === $this->propertyFilters) {
            return [];
        }

        return [
            DataMapperHook::BeforeTransform->value => function($value, $context) {
                // Get target path from HookContext
                $targetPath = $context->tgtPath();

                if (null === $targetPath || !isset($this->propertyFilters[$targetPath])) {
                    return $value;
                }

                // Apply all filters for this property
                foreach ($this->propertyFilters[$targetPath] as $filter) {
                    $value = $filter->transform($value, $context);
                }

                return $value;
            },
        ];
    }

    /**
     * Merge hooks with property filter hooks.
     *
     * @param array<string, mixed> $hooks
     * @return array<string, mixed>
     */
    private function mergeHooks(array $hooks): array
    {
        $propertyHooks = $this->buildPropertyFilterHooks();

        if ([] === $propertyHooks) {
            return $hooks;
        }

        // Merge hooks - property filters should run first
        foreach ($propertyHooks as $hookName => $hookCallback) {
            if (isset($hooks[$hookName])) {
                // Wrap both callbacks
                $existingHook = $hooks[$hookName];
                assert(is_callable($hookCallback), 'Hook callback must be callable');
                assert(is_callable($existingHook), 'Existing hook must be callable');
                $hooks[$hookName] = function($value, $context) use ($hookCallback, $existingHook) {
                    // Run property filter first
                    $value = $hookCallback($value, $context);
                    // Then run existing hook
                    return $existingHook($value, $context);
                };
            } else {
                $hooks[$hookName] = $hookCallback;
            }
        }

        return $hooks;
    }

    /**
     * Merge hooks with pipeline hooks.
     *
     * Property filters run first, then pipeline filters.
     *
     * @param array<string, mixed> $hooks Hooks (may include property filter hooks)
     * @param array<string, mixed> $pipelineHooks Pipeline hooks
     * @return array<string, mixed>
     */
    private function mergeHooksWithPipeline(array $hooks, array $pipelineHooks): array
    {
        if ([] === $pipelineHooks) {
            return $hooks;
        }

        // Merge hooks - property filters should run first, then pipeline filters
        foreach ($pipelineHooks as $hookName => $pipelineCallback) {
            if (isset($hooks[$hookName])) {
                // Wrap both callbacks
                $existingHook = $hooks[$hookName];
                assert(is_callable($existingHook), 'Existing hook must be callable');
                assert(is_callable($pipelineCallback), 'Pipeline callback must be callable');
                $hooks[$hookName] = function($value, $context) use ($existingHook, $pipelineCallback) {
                    // Run existing hook first (property filters)
                    $value = $existingHook($value, $context);
                    // Then run pipeline filter
                    return $pipelineCallback($value, $context);
                };
            } else {
                $hooks[$hookName] = $pipelineCallback;
            }
        }

        return $hooks;
    }

    /**
     * Apply queries to template.
     *
     * Converts MapperQuery configurations into Wildcard Operator syntax in the template.
     *
     * @param array<int|string, mixed> $template
     * @return array<int|string, mixed>
     */
    private function applyQueriesToTemplate(array $template): array
    {
        foreach ($this->queries as $wildcardPath => $query) {
            $template = $this->injectQueryIntoTemplate($template, $wildcardPath, $query);
        }

        return $template;
    }

    /**
     * Inject a single query into the template at the appropriate wildcard location.
     *
     * @param array<int|string, mixed> $template
     * @return array<int|string, mixed>
     */
    private function injectQueryIntoTemplate(array $template, string $wildcardPath, MapperQuery $query): array
    {
        // Build operator config from query
        $operators = [];

        // WHERE conditions
        $whereConditions = $query->getWhereConditions();
        if ([] !== $whereConditions) {
            $whereConfig = [];
            foreach ($whereConditions as $condition) {
                // Convert field to template expression
                // 'status' → '{{ products.*.status }}'
                $fieldExpression = '{{ ' . $wildcardPath . '.' . $condition['field'] . ' }}';

                // Format value based on operator
                if ('=' === $condition['operator']) {
                    // Simple equality - just use the value
                    $whereConfig[$fieldExpression] = $condition['value'];
                } else {
                    // Other operators - use array format [operator, value]
                    $whereConfig[$fieldExpression] = [$condition['operator'], $condition['value']];
                }
            }
            $operators['WHERE'] = $whereConfig;
        }

        // ORDER BY conditions
        $orderByConditions = $query->getOrderByConditions();
        if ([] !== $orderByConditions) {
            $orderByConfig = [];
            foreach ($orderByConditions as $condition) {
                // Convert field to template expression
                $fieldExpression = '{{ ' . $wildcardPath . '.' . $condition['field'] . ' }}';
                $orderByConfig[$fieldExpression] = $condition['direction'];
            }
            $operators['ORDER BY'] = $orderByConfig;
        }

        // LIMIT
        if (null !== $query->getLimit()) {
            $operators['LIMIT'] = $query->getLimit();
        }

        // OFFSET
        if (null !== $query->getOffset()) {
            $operators['OFFSET'] = $query->getOffset();
        }

        // GROUP BY
        $groupByFields = $query->getGroupByFields();
        if ([] !== $groupByFields) {
            if (1 === count($groupByFields)) {
                // Single field - use 'field' key
                $operators['GROUP BY'] = [
                    'field' => '{{ ' . $wildcardPath . '.' . $groupByFields[0] . ' }}',
                ];
            } else {
                // Multiple fields - use 'fields' key
                $groupByExpressions = [];
                foreach ($groupByFields as $field) {
                    $groupByExpressions[] = '{{ ' . $wildcardPath . '.' . $field . ' }}';
                }
                $operators['GROUP BY'] = [
                    'fields' => $groupByExpressions,
                ];
            }
        }

        // If no operators, return template unchanged
        if ([] === $operators) {
            return $template;
        }

        // Inject operators into template at wildcard location
        return $this->injectOperatorsAtWildcard($template, $wildcardPath, $operators);
    }

    /**
     * Inject operators at the wildcard location in the template.
     *
     * @param array<int|string, mixed> $template
     * @param array<string, mixed> $operators
     * @return array<int|string, mixed>
     */
    private function injectOperatorsAtWildcard(array $template, string $wildcardPath, array $operators): array
    {
        // Convert wildcard path to template expression
        // 'items.*' → '{{ items.* }}'
        $templateExpression = '{{ ' . $wildcardPath . ' }}';

        // Recursively search and inject operators
        $result = $this->recursiveInjectOperators($template, $wildcardPath, $templateExpression, $operators);
        assert(is_array($result), 'Result must be array');
        return $result;
    }

    /**
     * Recursively search template and inject operators at wildcard locations.
     *
     * @param array<string, mixed> $operators
     */
    private function recursiveInjectOperators(
        mixed $template,
        string $wildcardPath,
        string $templateExpression,
        array $operators
    ): mixed
    {
        if (!is_array($template)) {
            return $template;
        }

        $result = [];

        foreach ($template as $key => $value) {
            if (is_array($value)) {
                // Check if this array has a '*' key (wildcard mapping)
                if (isset($value['*'])) {
                    // Check if the wildcard mapping contains our wildcard path
                    $wildcardMapping = $value['*'];
                    $hasWildcard = $this->containsWildcardPath($wildcardMapping, $templateExpression);

                    if ($hasWildcard) {
                        // Merge operators with existing operators in template
                        // Special handling for WHERE to combine conditions
                        $mergedValue = $value;
                        foreach ($operators as $operatorKey => $operatorValue) {
                            if ('WHERE' === $operatorKey && isset($mergedValue['WHERE'])) {
                                // Merge WHERE conditions (AND logic)
                                $existingWhere = $mergedValue['WHERE'];
                                assert(is_array($existingWhere), 'WHERE must be array');
                                assert(is_array($operatorValue), 'Operator value must be array');
                                $mergedValue['WHERE'] = array_merge($existingWhere, $operatorValue);
                            } elseif ('ORDER BY' === $operatorKey && isset($mergedValue['ORDER BY'])) {
                                // Merge ORDER BY conditions
                                $existingOrderBy = $mergedValue['ORDER BY'];
                                assert(is_array($existingOrderBy), 'ORDER BY must be array');
                                assert(is_array($operatorValue), 'Operator value must be array');
                                $mergedValue['ORDER BY'] = array_merge($existingOrderBy, $operatorValue);
                            } else {
                                // For other operators, just set/override
                                $mergedValue[$operatorKey] = $operatorValue;
                            }
                        }
                        $result[$key] = $mergedValue;
                    } else {
                        // Recurse into nested structure
                        $result[$key] = $this->recursiveInjectOperators(
                            $value,
                            $wildcardPath,
                            $templateExpression,
                            $operators
                        );
                    }
                } else {
                    // Recurse into nested arrays
                    $result[$key] = $this->recursiveInjectOperators(
                        $value,
                        $wildcardPath,
                        $templateExpression,
                        $operators
                    );
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if a value contains the wildcard path.
     *
     * Checks if the value contains the wildcard path or any sub-path of it.
     * For example, if wildcardPath is 'products.*', it matches:
     * - '{{ products.* }}'
     * - '{{ products.*.id }}'
     * - '{{ products.*.name }}'
     */
    private function containsWildcardPath(mixed $value, string $templateExpression): bool
    {
        if (is_string($value)) {
            // Extract the wildcard path from template expression
            // '{{ products.* }}' → 'products.*'
            $wildcardPath = trim($templateExpression, '{{ }}');

            // Check if value contains the wildcard path or any sub-path
            // '{{ products.*.id }}' contains 'products.*'
            return str_contains($value, $wildcardPath);
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsWildcardPath($item, $templateExpression)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if template contains wildcard operators (WHERE, ORDER BY, LIMIT, etc.).
     *
     * @param array<int|string, mixed> $template
     */
    private function hasWildcardOperators(array $template): bool
    {
        // Check if this level has a wildcard operator AND a '*' key
        // (operators only make sense with wildcards)
        $hasWildcard = isset($template['*']);
        $hasOperator = false;

        foreach ($template as $key => $value) {
            // Check if key is a registered wildcard operator
            if (is_string($key) && WildcardOperatorRegistry::has($key)) {
                $hasOperator = true;
            }

            // Recursively check nested arrays
            if (is_array($value) && $this->hasWildcardOperators($value)) {
                return true;
            }
        }
        // Only return true if we have BOTH an operator AND a wildcard
        return $hasOperator && $hasWildcard;
    }

    /**
     * Extract source names from template expressions.
     *
     * Extracts source names like 'products' from '{{ products.*.id }}'.
     *
     * @param array<int|string, mixed> $template
     * @return array<int, string>
     */
    private function extractSourceNamesFromTemplate(array $template): array
    {
        $sourceNames = [];

        foreach ($template as $key => $value) {
            // Check keys (important for WHERE clauses where template expressions are keys)
            if (is_string($key) && preg_match('/\{\{\s*@?([a-zA-Z_]\w*)\.\*/', $key, $matches)) {
                $sourceNames[] = $matches[1];
            }

            // Check values
            if (is_string($value) && preg_match('/\{\{\s*@?([a-zA-Z_]\w*)(?:\.\*|\.[\w.]+)/', $value, $matches)) {
                $sourceNames[] = $matches[1];
            } elseif (is_array($value)) {
                $sourceNames = array_merge($sourceNames, $this->extractSourceNamesFromTemplate($value));
            }
        }

        return array_unique($sourceNames);
    }

    /**
     * Get the queries.
     *
     * @return array<string, MapperQuery>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Resolve target class using discriminator if configured.
     *
     * If a discriminator is configured, this method reads the discriminator
     * field value from the source data and selects the appropriate subclass.
     *
     * @return mixed The resolved target (class name or instance)
     */
    private function resolveTargetWithDiscriminator(): mixed
    {
        // No discriminator configured - return original target
        if (null === $this->discriminatorField || [] === $this->discriminatorMap) {
            return $this->target;
        }

        // Read discriminator value from source
        $accessor = new DataAccessor($this->source);
        $discriminatorValue = $accessor->get($this->discriminatorField);

        // Determine which class to use
        $targetClass = null;

        // No discriminator value found - use original target
        if (null === $discriminatorValue) {
            $targetClass = $this->target;
        } elseif (is_scalar($discriminatorValue)) {
            // Only process scalar values (string, int, float, bool)
            // Arrays and objects cannot be used as discriminator values
            // Convert discriminator value to string for map lookup
            // Trim the value to handle cases where filters might trim it later
            $discriminatorKey = is_string($discriminatorValue) ? trim(
                $discriminatorValue
            ) : (string)$discriminatorValue;
            // Use mapped class if found, otherwise use original target
            $targetClass = $this->discriminatorMap[$discriminatorKey] ?? $this->target;
        } else {
            // Non-scalar value (array, object) - use original target
            $targetClass = $this->target;
        }

        // If it's a class name string, instantiate it
        if (is_string($targetClass) && class_exists($targetClass)) {
            return new $targetClass();
        }

        // Return as-is (could be an instance already)
        return $targetClass;
    }

    /**
     * Automatically map fields from source to target with optional snake_case → camelCase conversion.
     *
     * This method skips the template, even if one is set, and automatically maps matching field names.
     *
     * @param bool|null $deep Enable deep mode (recursively maps nested structures). If null, uses the value set via deep() method.
     */
    public function autoMap(?bool $deep = null): DataMapperResult
    {
        // Merge hooks with property filters
        $hooks = $this->mergeHooks($this->hooks);

        // Use deep value from parameter or instance property
        $deepValue = $deep ?? $this->deep;

        // Use AutoMapper directly, bypassing template
        $result = MappingFacade::autoMap(
            $this->source,
            $this->target,
            $this->getSkipNullValue(),
            $this->getReindexWildcardValue(),
            $hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace,
            $deepValue
        );

        return new DataMapperResult($result, $this->source, [], $this->exceptionHandler);
    }

    /**
     * Automatically map fields in reverse direction (target → source).
     *
     * This method skips the template, even if one is set, and automatically maps matching field names.
     *
     * @param bool|null $deep Enable deep mode (recursively maps nested structures). If null, uses the value set via deep() method.
     */
    public function reverseAutoMap(?bool $deep = null): DataMapperResult
    {
        // Merge hooks with property filters
        $hooks = $this->mergeHooks($this->hooks);

        // Use deep value from parameter or instance property
        $deepValue = $deep ?? $this->deep;

        // Use AutoMapper in reverse direction
        $result = MappingFacade::autoMap(
            $this->target,
            $this->source,
            $this->getSkipNullValue(),
            $this->getReindexWildcardValue(),
            $hooks,
            $this->trimValues,
            $this->caseInsensitiveReplace,
            $deepValue
        );

        return new DataMapperResult($result, $this->target, [], $this->exceptionHandler);
    }

    /**
     * Map multiple source-target pairs using the configured template and settings.
     *
     * This method overrides the source and target for each mapping pair.
     *
     * @param array<int, array{source: mixed, target: mixed}> $mappings Array of ['source' => ..., 'target' => ...] pairs
     * @return array<int, DataMapperResult> Array of results (indexed 0, 1, 2, ...)
     */
    public function mapMany(array $mappings): array
    {
        $results = [];

        foreach ($mappings as $index => $mapping) {
            // Create a copy of this mapper with new source and target
            $mapper = $this->copy();
            $mapper->source = $mapping['source'];
            $mapper->target = $mapping['target'];

            // Execute mapping
            $results[$index] = $mapper->map();
        }

        return $results;
    }

    /**
     * Map multiple source-target pairs in reverse direction using the configured template and settings.
     *
     * This method overrides the source and target for each mapping pair.
     *
     * @param array<int, array{source: mixed, target: mixed}> $mappings Array of ['source' => ..., 'target' => ...] pairs
     * @return array<int, DataMapperResult> Array of results (indexed 0, 1, 2, ...)
     */
    public function reverseManyMap(array $mappings): array
    {
        $results = [];

        foreach ($mappings as $index => $mapping) {
            // Create a copy of this mapper with new source and target
            $mapper = $this->copy();
            $mapper->source = $mapping['source'];
            $mapper->target = $mapping['target'];

            // Execute reverse mapping
            $results[$index] = $mapper->reverseMap();
        }

        return $results;
    }

    /** Check if target is an array with string keys (aliases). */
    private function hasTargetAliases(mixed $target): bool
    {
        if (!is_array($target)) {
            return false;
        }

        // Check if array has string keys
        foreach (array_keys($target) as $key) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /** Check if source is an array with string keys (aliases). */
    private function hasSourceAliases(mixed $source): bool
    {
        if (!is_array($source)) {
            return false;
        }

        // Check if array has string keys
        foreach (array_keys($source) as $key) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if template contains source alias references (e.g., '@user.name').
     *
     * @param array<int|string, mixed> $template
     */
    private function hasSourceAliasReferences(array $template): bool
    {
        foreach ($template as $value) {
            if (is_array($value)) {
                if ($this->hasSourceAliasReferences($value)) {
                    return true;
                }
            } elseif (is_string($value)) {
                // Check for @alias syntax
                if (preg_match('/\{\{\s*@\w+/', $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if template contains target alias references (e.g., 'user.name' or '@user.name').
     *
     * This checks if template values look like alias paths that match the target aliases.
     *
     * @param array<int|string, mixed> $template
     */
    private function hasTargetAliasReferences(array $template): bool
    {
        if (!is_array($this->target)) {
            return false;
        }

        // Get target aliases
        $targetAliases = array_keys($this->target);
        if ([] === $targetAliases) {
            return false;
        }

        foreach ($template as $value) {
            if (is_array($value)) {
                if ($this->hasTargetAliasReferences($value)) {
                    return true;
                }
            } elseif (is_string($value)) {
                // Remove {{ }} if present
                $cleanValue = trim($value);
                if (str_starts_with($cleanValue, '{{') && str_ends_with($cleanValue, '}}')) {
                    $cleanValue = trim(substr($cleanValue, 2, -2));
                }

                // Remove @ if present
                if (str_starts_with($cleanValue, '@')) {
                    $cleanValue = substr($cleanValue, 1);
                }

                // Check if value starts with any target alias
                foreach ($targetAliases as $alias) {
                    if (str_starts_with($cleanValue, $alias . '.')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Clean template for mapToTargetsFromTemplate: remove {{ }}, @ from values.
     *
     * @param array<int|string, mixed> $template
     * @return array<int|string, mixed>
     */
    private function cleanTemplateForTargetAliases(array $template): array
    {
        $cleaned = [];

        foreach ($template as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = $this->cleanTemplateForTargetAliases($value);
            } elseif (is_string($value)) {
                $cleanValue = trim($value);

                // Remove {{ }} if present
                if (str_starts_with($cleanValue, '{{') && str_ends_with($cleanValue, '}}')) {
                    $cleanValue = trim(substr($cleanValue, 2, -2));
                }

                // Remove @ if present
                if (str_starts_with($cleanValue, '@')) {
                    $cleanValue = substr($cleanValue, 1);
                }

                $cleaned[$key] = $cleanValue;
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Enable reverse mapping mode.
     *
     * In reverse mode, the mapping template is automatically reversed before being applied.
     * This allows using the same template for bidirectional mapping.
     */
    public function reverse(): self
    {
        $this->reverseMapping = true;
        return $this;
    }

    /** Check if reverse mapping is enabled. */
    public function isReverse(): bool
    {
        return $this->reverseMapping;
    }

    /**
     * Convert array to XML recursively.
     *
     * @param array<int|string, mixed> $array The array to convert
     * @param SimpleXMLElement $xml The XML element to append to
     * @param string|null $parentKey The parent key name for singularization
     */
    private function arrayToXmlRecursive(array $array, SimpleXMLElement $xml, ?string $parentKey): void
    {
        foreach ($array as $key => $value) {
            $elementName = $key;

            // Handle numeric keys - use singular of parent key
            if (is_numeric($key)) {
                if (null !== $parentKey) {
                    $elementName = $this->singularize($parentKey);
                } else {
                    $elementName = 'item';
                }
            }

            // Sanitize key to be valid XML element name
            $elementName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$elementName);

            if (is_array($value)) {
                $subnode = $xml->addChild((string)$elementName);
                if ($subnode instanceof SimpleXMLElement) {
                    $this->arrayToXmlRecursive($value, $subnode, (string)$key);
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
    private function singularize(string $word): string
    {
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
            return $irregulars[$lower];
        }

        // Common plural patterns
        if (str_ends_with($lower, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        if (str_ends_with($lower, 'ves')) {
            return substr($word, 0, -3) . 'f';
        }
        if (str_ends_with($lower, 'ses') || str_ends_with($lower, 'xes') || str_ends_with($lower, 'zes')) {
            return substr($word, 0, -2);
        }
        if (str_ends_with($lower, 's') && !str_ends_with($lower, 'ss')) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
