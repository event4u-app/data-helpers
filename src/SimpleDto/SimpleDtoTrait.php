<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use BackedEnum;
use BadMethodCallException;
use Closure;
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Exceptions\TypeMismatchException;
use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use event4u\DataHelpers\Validation\ValidationResult;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * Core trait for SimpleDto functionality.
 *
 * This trait provides the complete SimpleDto implementation including:
 * - Core methods (from, toArray, toJson, validate, etc.)
 * - Lifecycle hooks (beforeCreate, afterCreate, etc.)
 * - Additional features via traits (diff, with, sorting, etc.)
 * - Framework integrations (Doctrine, Eloquent)
 *
 * Usage:
 *   class MyDto extends SimpleDto {
 *       use SimpleDtoTrait;
 *   }
 *
 * Or use the trait directly without extending SimpleDto:
 *   class MyDto {
 *       use SimpleDtoTrait;
 *   }
 */
trait SimpleDtoTrait
{
    // Import all feature traits
    use SimpleDtoCastsTrait;
    use SimpleDtoMappingTrait;
    use SimpleDtoValidationTrait;
    use SimpleDtoRequestValidationTrait;
    use SimpleDtoMapperTrait;
    use SimpleDtoImporterTrait;
    use SimpleDtoTransformerTrait;
    use SimpleDtoNormalizerTrait;
    use SimpleDtoPipelineTrait;
    use SimpleDtoOptionalTrait;
    use SimpleDtoWrappingTrait;
    use SimpleDtoSerializerTrait;
    use SimpleDtoWithTrait;
    use SimpleDtoSortingTrait;
    use SimpleDtoDiffTrait;
    use SimpleDtoVisibilityTrait;
    use SimpleDtoLazyTrait;
    use SimpleDtoComputedTrait;
    use SimpleDtoConditionalTrait;
    use SimpleDtoLazyCastTrait;
    use SimpleDtoPerformanceTrait;
    use SimpleDtoBenchmarkTrait;

    // Framework integration traits - always included, check availability internally
    use SimpleDtoEloquentTrait;
    use SimpleDtoDoctrineTrait;
    use SimpleDtoObjectTrait;

    /**
     * Create DTO from data.
     *
     * Standard mode: Only accepts arrays
     * ConverterMode: Accepts JSON, XML, CSV, etc.
     *
     * Automatically applies mapperTemplate(), mapperFilters() and mapperPipeline()
     * if defined in the DTO class (via SimpleDtoMapperTrait). Parameters override DTO configuration.
     * Pipeline filters are merged (DTO pipeline + parameter pipeline).
     *
     * @param array<string, mixed>|string|object $data
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function from(
        mixed $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Check if DTO uses SimpleDtoMapperTrait (has getTemplateConfig method)
        // and load DTO configuration if available
        $usesMapperTrait = method_exists(static::class, 'getTemplateConfig'); // @phpstan-ignore-line

        if ($usesMapperTrait) {
            // Merge with parameters (parameters have priority for template/filters, merged for pipeline)
            if (null === $template) {
                $template = static::getTemplateConfig();
            }
            if (null === $filters) {
                $filters = static::getFilterConfig();
            }

            // Merge pipelines: DTO pipeline + parameter pipeline
            $dtoPipeline = static::getPipelineConfig();
            if (null !== $dtoPipeline && [] !== $dtoPipeline) {
                if (null !== $pipeline && [] !== $pipeline) {
                    // Note: array_merge is correct here for numeric arrays (appends items)
                    $pipeline = array_merge($dtoPipeline, $pipeline);
                } else {
                    $pipeline = $dtoPipeline;
                }
            }
        }

        /** @var static */
        return SimpleEngine::createFromData(static::class, $data, $template, $filters, $pipeline);
    }

    /** @var array{hash: string, data: array<string, mixed>, context: array<string, mixed>, objectId: int, includedComputed: array<int, string>}|null */
    private ?array $toArrayCache = null;

    /** @var array{hash: string, data: array<string, mixed>, context: array<string, mixed>, objectId: int, includedComputed: array<int, string>}|null */
    private ?array $toJsonCache = null;

    /**
     * Clear toArray/toJson caches.
     *
     * This method is called automatically when cloning DTOs.
     * Can also be called manually to force cache invalidation.
     */
    protected function clearSerializationCaches(): void
    {
        $this->toArrayCache = null;
        $this->toJsonCache = null;
    }

    /**
     * Get all property keys of this DTO.
     *
     * Returns the property names (not the mapped output names) of the DTO.
     * By default, all properties are included (even hidden ones).
     *
     * @param bool $includeHiddenFromArray Include properties with #[HiddenFromArray] attribute (default: true)
     * @param bool $includeHiddenFromJson Include properties with #[HiddenFromJson] attribute (default: true)
     * @return array<int, string> Array of property names
     */
    public function getKeys(bool $includeHiddenFromArray = true, bool $includeHiddenFromJson = true): array
    {
        return SimpleEngine::getKeys(static::class, $includeHiddenFromArray, $includeHiddenFromJson);
    }

    /**
     * Check if toArray() caching should be enabled.
     *
     * Caching is disabled if there are computed properties with cache: false,
     * because these properties need to be recomputed on every call.
     *
     * @return bool True if caching should be enabled
     */
    private function shouldEnableToArrayCache(): bool
    {
        static $cache = [];

        $class = static::class;

        // Check static cache first
        if (isset($cache[$class])) {
            return $cache[$class];
        }

        // Check if there are any computed properties with cache: false
        try {
            /** @var array<string, Computed> $computedMethods */
            $computedMethods = SimpleEngine::getComputedMethods($class);
            foreach ($computedMethods as $computed) {
                if (!$computed->cache) {
                    // Found a computed property with cache: false, disable toArray caching
                    $cache[$class] = false;
                    return false;
                }
            }
        } catch (Throwable) {
            // If getComputedMethods fails, assume caching is enabled
        }

        // No computed properties with cache: false, enable caching
        $cache[$class] = true;
        return true;
    }

    /**
     * Convert DTO to array.
     *
     * Respects #[MapTo], #[Hidden] and conditional attributes.
     * Results are cached - if the DTO hasn't changed, the cached array is returned.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toArray(array $context = []): array
    {
        // Merge context from withContext() method if SimpleDtoConditionalTrait is used
        if (property_exists($this, 'conditionalContext') && isset($this->conditionalContext)) { // @phpstan-ignore-line
            $context += $this->conditionalContext; // @phpstan-ignore-line
        }

        // Check if caching should be disabled (e.g., computed properties with cache: false)
        $cachingEnabled = $this->shouldEnableToArrayCache();

        if ($cachingEnabled) {
            // Fast path: if context, object ID and includedComputed are identical, use cache
            $objectId = spl_object_id($this);
            $includedComputed = SimpleEngine::getIncludedComputed($objectId);

            if (null !== $this->toArrayCache
                && $this->toArrayCache['context'] === $context
                && $this->toArrayCache['objectId'] === $objectId
                && $this->toArrayCache['includedComputed'] === $includedComputed) {
                // Fast check: context, object identity and includedComputed match
                return $this->toArrayCache['data'];
            }

            // Slow path: check if state has actually changed by comparing hash
            if (null !== $this->toArrayCache) {
                $currentHash = $this->calculateToArrayHash($context);
                if ($this->toArrayCache['hash'] === $currentHash) {
                    // State hasn't changed, return cached result
                    return $this->toArrayCache['data'];
                }
            }
        }

        $data = SimpleEngine::toArray($this, $context);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) { // @phpstan-ignore-line
            $hiddenProperties = method_exists($this, 'getHiddenFromArrayProperties') // @phpstan-ignore-line
                ? $this->getHiddenFromArrayProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Merge additional data from with() method if SimpleDtoWithTrait is used
        if (method_exists($this, 'getAdditionalData')) { // @phpstan-ignore-line
            $additionalData = $this->getAdditionalData();
            if ([] !== $additionalData) {
                // Use + operator for performance (additional data overwrites existing properties)
                $data = $additionalData + $data;
            }
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) { // @phpstan-ignore-line
            $data = $this->applyWrapping($data);
        }

        // Apply sorting if SimpleDtoSortingTrait is used
        if (method_exists($this, 'applySorting')) { // @phpstan-ignore-line
            $data = $this->applySorting($data);
        }

        // Cache the result (calculate hash after processing) - only if caching is enabled
        if ($cachingEnabled) {
            $this->toArrayCache = [
                'hash' => $this->calculateToArrayHash($context),
                'data' => $data,
                'context' => $context,
                'objectId' => $objectId,
                'includedComputed' => $includedComputed,
            ];
        }

        return $data;
    }

    /**
     * Calculate hash of current DTO state for toArray() caching.
     *
     * Uses xxHash (xxh3) for maximum performance (~10x faster than md5).
     * Prepares data for hashing by converting Enums and removing Closures.
     *
     * @param array<string, mixed> $context
     */
    private function calculateToArrayHash(array $context): string
    {
        // Get all properties (including private ones like includedLazy, includeAllLazy, etc.)
        // Using (array) cast to get all properties, not just public ones
        $properties = (array)$this;

        // Remove cache properties and internal state that doesn't affect output
        // Note: Private properties have keys like "\0ClassName\0propertyName"
        foreach (array_keys($properties) as $key) {
            if (str_contains((string)$key, 'toArrayCache') ||
                str_contains((string)$key, 'toJsonCache') ||
                str_contains((string)$key, 'computedCache') ||
                str_contains((string)$key, 'objectVarsCache') ||
                str_contains((string)$key, 'castedProperties') ||
                str_contains((string)$key, 'validationState') ||
                str_contains((string)$key, 'validationErrors') ||
                str_contains((string)$key, 'lastValidationResult')) {
                unset($properties[$key]);
            }
        }

        // Include static cache state from SimpleEngine (for included computed properties)
        $objectId = spl_object_id($this);
        $includedComputed = SimpleEngine::getIncludedComputed($objectId);

        // Old code for reference (only worked for public properties):
        /*unset(
            $properties['toArrayCache'],
            $properties['toJsonCache'],
            $properties['computedCache'],
            $properties['objectVarsCache'],
            $properties['castedProperties'],
            $properties['validationState'],
            $properties['validationErrors'],
            $properties['lastValidationResult']
        );*/

        // Include additional data if SimpleDtoWithTrait is used
        $additionalData = [];
        if (method_exists($this, 'getAdditionalData')) { // @phpstan-ignore-line
            $additionalData = $this->getAdditionalData();
        }

        // Include wrapping key if SimpleDtoWrappingTrait is used
        $wrappingKey = null;
        if (property_exists($this, 'wrappingKey')) { // @phpstan-ignore-line
            $wrappingKey = $this->wrappingKey ?? null; // @phpstan-ignore-line
        }

        // Include sorting flag if SimpleDtoSortingTrait is used
        $sortKeys = false;
        if (property_exists($this, 'sortKeys')) { // @phpstan-ignore-line
            $sortKeys = $this->sortKeys ?? false; // @phpstan-ignore-line
        }

        // Prepare data for hashing (convert Enums, remove Closures)
        $hashData = $this->prepareForHashing([
            'properties' => $properties,
            'context' => $context,
            'additionalData' => $additionalData,
            'wrappingKey' => $wrappingKey,
            'sortKeys' => $sortKeys,
            'includedComputed' => $includedComputed,
        ]);

        // Use json_encode for fast serialization
        $data = json_encode($hashData, JSON_THROW_ON_ERROR);

        // Use xxh3 if available (10x faster than md5), fallback to md5
        return function_exists('hash') && in_array('xxh3', hash_algos(), true)
            ? hash('xxh3', $data)
            : md5($data); // @phpstan-ignore disallowed.function (fallback for systems without hash())
    }

    /** Prepare data for hashing by converting Enums and removing Closures. */
    private function prepareForHashing(mixed $data): mixed
    {
        if ($data instanceof Closure) {
            return null;
        }

        if ($data instanceof BackedEnum) {
            return ['__enum__' => $data::class, 'value' => $data->value];
        }

        if ($data instanceof UnitEnum) {
            return ['__enum__' => $data::class, 'name' => $data->name];
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value instanceof Closure) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $this->prepareForHashing($value);
                }
            }
            return $data;
        }

        if (is_object($data)) {
            $properties = get_object_vars($data);
            $result = [];
            foreach ($properties as $key => $value) {
                if (!($value instanceof Closure)) {
                    $result[$key] = $this->prepareForHashing($value);
                }
            }
            return $result;
        }

        return $data;
    }

    /**
     * Calculate hash of current DTO state for toJson() caching.
     *
     * Uses xxHash (xxh3) for maximum performance (~10x faster than md5).
     * Prepares data for hashing by converting Enums and removing Closures.
     *
     * @param array<string, mixed> $context
     */
    private function calculateToJsonHash(array $context): string
    {
        // Get all properties (including private ones like includedLazy, includeAllLazy, etc.)
        // Using (array) cast to get all properties, not just public ones
        $properties = (array)$this;

        // Remove cache properties and internal state that doesn't affect output
        // Note: Private properties have keys like "\0ClassName\0propertyName"
        foreach (array_keys($properties) as $key) {
            if (str_contains((string)$key, 'toArrayCache') ||
                str_contains((string)$key, 'toJsonCache') ||
                str_contains((string)$key, 'computedCache') ||
                str_contains((string)$key, 'objectVarsCache') ||
                str_contains((string)$key, 'castedProperties') ||
                str_contains((string)$key, 'validationState') ||
                str_contains((string)$key, 'validationErrors') ||
                str_contains((string)$key, 'lastValidationResult')) {
                unset($properties[$key]);
            }
        }

        // Include static cache state from SimpleEngine (for included computed properties)
        $objectId = spl_object_id($this);
        $includedComputed = SimpleEngine::getIncludedComputed($objectId);

        // Include additional data if SimpleDtoWithTrait is used
        $additionalData = [];
        if (method_exists($this, 'getAdditionalData')) { // @phpstan-ignore-line
            $additionalData = $this->getAdditionalData();
        }

        // Include wrapping key if SimpleDtoWrappingTrait is used
        $wrappingKey = null;
        if (property_exists($this, 'wrappingKey')) { // @phpstan-ignore-line
            $wrappingKey = $this->wrappingKey ?? null; // @phpstan-ignore-line
        }

        // Include sorting flag if SimpleDtoSortingTrait is used
        $sortKeys = false;
        if (property_exists($this, 'sortKeys')) { // @phpstan-ignore-line
            $sortKeys = $this->sortKeys ?? false; // @phpstan-ignore-line
        }

        // Prepare data for hashing (convert Enums, remove Closures)
        $hashData = $this->prepareForHashing([
            'properties' => $properties,
            'context' => $context,
            'additionalData' => $additionalData,
            'wrappingKey' => $wrappingKey,
            'sortKeys' => $sortKeys,
            'includedComputed' => $includedComputed,
        ]);

        // Use json_encode for fast serialization
        $data = json_encode($hashData, JSON_THROW_ON_ERROR);

        // Use xxh3 if available (10x faster than md5), fallback to md5
        return function_exists('hash') && in_array('xxh3', hash_algos(), true)
            ? hash('xxh3', $data)
            : md5($data); // @phpstan-ignore disallowed.function (fallback for systems without hash())
    }

    /**
     * Convert DTO to JSON.
     *
     * @param int $options JSON encoding options
     */
    public function toJson(int $options = 0): string
    {
        // Merge context from withContext() method if SimpleDtoConditionalTrait is used
        $context = [];
        if (property_exists($this, 'conditionalContext') && isset($this->conditionalContext)) { // @phpstan-ignore-line
            $context = $this->conditionalContext; // @phpstan-ignore-line
        }

        // Fast path: if context, object ID and includedComputed are identical, use cache
        $objectId = spl_object_id($this);
        $includedComputed = SimpleEngine::getIncludedComputed($objectId);

        if (null !== $this->toJsonCache
            && $this->toJsonCache['context'] === $context
            && $this->toJsonCache['objectId'] === $objectId
            && $this->toJsonCache['includedComputed'] === $includedComputed) {
            // Fast check: context, object identity and includedComputed match
            return json_encode($this->toJsonCache['data'], JSON_THROW_ON_ERROR | $options);
        }

        // Slow path: check if state has actually changed by comparing hash
        if (null !== $this->toJsonCache) {
            $currentHash = $this->calculateToJsonHash($context);
            if ($this->toJsonCache['hash'] === $currentHash) {
                // State hasn't changed, return cached result
                return json_encode($this->toJsonCache['data'], JSON_THROW_ON_ERROR | $options);
            }
        }

        $data = SimpleEngine::toJsonArray($this, $context);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) { // @phpstan-ignore-line
            $hiddenProperties = method_exists($this, 'getHiddenFromJsonProperties') // @phpstan-ignore-line
                ? $this->getHiddenFromJsonProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Merge additional data from with() method if SimpleDtoWithTrait is used
        if (method_exists($this, 'getAdditionalData')) { // @phpstan-ignore-line
            $additionalData = $this->getAdditionalData();
            if ([] !== $additionalData) {
                // Use + operator for performance (additional data overwrites existing properties)
                $data = $additionalData + $data;
            }
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) { // @phpstan-ignore-line
            $data = $this->applyWrapping($data);
        }

        // Cache the result (calculate hash after processing)
        $this->toJsonCache = [
            'hash' => $this->calculateToJsonHash($context),
            'data' => $data,
            'context' => $context,
            'objectId' => $objectId,
            'includedComputed' => $includedComputed,
        ];

        return json_encode($data, JSON_THROW_ON_ERROR | $options);
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        // Merge context from withContext() method if SimpleDtoConditionalTrait is used
        $context = [];
        if (property_exists($this, 'conditionalContext') && isset($this->conditionalContext)) { // @phpstan-ignore-line
            $context = $this->conditionalContext; // @phpstan-ignore-line
        }

        // Fast path: if context, object ID and includedComputed are identical, use cache
        $objectId = spl_object_id($this);
        $includedComputed = SimpleEngine::getIncludedComputed($objectId);

        if (null !== $this->toJsonCache
            && $this->toJsonCache['context'] === $context
            && $this->toJsonCache['objectId'] === $objectId
            && $this->toJsonCache['includedComputed'] === $includedComputed) {
            // Fast check: context, object identity and includedComputed match
            return $this->toJsonCache['data'];
        }

        // Slow path: check if state has actually changed by comparing hash
        if (null !== $this->toJsonCache) {
            $currentHash = $this->calculateToJsonHash($context);
            if ($this->toJsonCache['hash'] === $currentHash) {
                // State hasn't changed, return cached result
                return $this->toJsonCache['data'];
            }
        }

        $data = SimpleEngine::toJsonArray($this, $context);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) { // @phpstan-ignore-line
            $hiddenProperties = method_exists($this, 'getHiddenFromJsonProperties') // @phpstan-ignore-line
                ? $this->getHiddenFromJsonProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Merge additional data from with() method if SimpleDtoWithTrait is used
        if (method_exists($this, 'getAdditionalData')) { // @phpstan-ignore-line
            $additionalData = $this->getAdditionalData();
            if ([] !== $additionalData) {
                // Use + operator for performance (additional data overwrites existing properties)
                $data = $additionalData + $data;
            }
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) { // @phpstan-ignore-line
            $data = $this->applyWrapping($data);
        }

        // Apply sorting if SimpleDtoSortingTrait is used
        if (method_exists($this, 'applySorting')) { // @phpstan-ignore-line
            $data = $this->applySorting($data);
        }

        // Cache the result (calculate hash after processing)
        $this->toJsonCache = [
            'hash' => $this->calculateToJsonHash($context),
            'data' => $data,
            'context' => $context,
            'objectId' => $objectId,
            'includedComputed' => $includedComputed,
        ];

        return $data;
    }

    /**
     * Get value from Dto using dot notation.
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Wildcards: 'emails.*.address', 'users.*.orders.*.total'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $default Default value if path doesn't exist
     * @return mixed The value at the path or default if not found
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->get($path, $default);
    }

    /**
     * Get an integer value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param int|null $default Default value if path not found
     * @return int|null The integer value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to int
     */
    public function getInt(string $path, ?int $default = null): ?int
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getInt($path, $default);
    }

    /**
     * Get a string value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param string|null $default Default value if path not found
     * @return string|null The string value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to string
     */
    public function getString(string $path, ?string $default = null): ?string
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getString($path, $default);
    }

    /**
     * Get a boolean value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param bool|null $default Default value if path not found
     * @return bool|null The boolean value or null
     * @throws TypeMismatchException If value is an array
     */
    public function getBool(string $path, ?bool $default = null): ?bool
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getBool($path, $default);
    }

    /**
     * Get a float value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param float|null $default Default value if path not found
     * @return float|null The float value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to float
     */
    public function getFloat(string $path, ?float $default = null): ?float
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getFloat($path, $default);
    }

    /**
     * Get an array value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param array<int|string, mixed>|null $default Default value if path not found
     * @return array<int|string, mixed>|null The array value or null
     * @throws TypeMismatchException If value is not an array
     */
    public function getArray(string $path, ?array $default = null): ?array
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getArray($path, $default);
    }

    /**
     * Get a collection of integer values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<int> Collection of integer values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to int
     */
    public function getIntCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getIntCollection($path);
    }

    /**
     * Get a collection of string values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<string> Collection of string values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to string
     */
    public function getStringCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getStringCollection($path);
    }

    /**
     * Get a collection of boolean values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<bool> Collection of boolean values
     * @throws TypeMismatchException If path doesn't contain wildcards
     */
    public function getBoolCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getBoolCollection($path);
    }

    /**
     * Get a collection of float values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<float> Collection of float values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to float
     */
    public function getFloatCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getFloatCollection($path);
    }

    /**
     * Get a collection of array values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<array<int|string, mixed>> Collection of array values
     * @throws TypeMismatchException If path doesn't contain wildcards or values are not arrays
     */
    public function getArrayCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getArrayCollection($path);
    }

    /**
     * Set value in Dto using dot notation.
     *
     * For mutable properties (not readonly): Modifies the DTO directly
     * For readonly properties: Throws BadMethodCallException
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $value Value to set
     * @throws BadMethodCallException If property is readonly
     */
    public function set(string $path, mixed $value): void
    {
        // Empty path does nothing
        if ('' === $path) {
            return;
        }

        // Check if it's a simple property (no dot notation)
        if (!str_contains($path, '.')) {
            // Check if property is mutable
            if (!SimpleEngine::isPropertyMutable(static::class, $path)) {
                throw new BadMethodCallException(
                    sprintf(
                        'Cannot modify readonly property "%s" on DTO "%s". ' .
                        'Remove the readonly keyword from the property to allow modifications.',
                        $path,
                        static::class
                    )
                );
            }

            // Property is mutable, set it directly
            $this->$path = $value; // @phpstan-ignore-line

            // Invalidate toArray cache since property changed
            $this->toArrayCache = null;

            return;
        }

        // For dot notation paths, check if root property is mutable
        $segments = explode('.', $path);
        $rootProperty = $segments[0];

        if (!SimpleEngine::isPropertyMutable(static::class, $rootProperty)) {
            throw new BadMethodCallException(
                sprintf(
                    'Cannot modify nested path "%s" because root property "%s" is readonly on DTO "%s". ' .
                    'Remove the readonly keyword from the property to allow modifications.',
                    $path,
                    $rootProperty,
                    static::class
                )
            );
        }

        // Root property is mutable, modify the nested structure directly
        $data = $this->toArrayRecursive();
        DataMutator::make($data)->set($path, $value);

        // Update the root property with the modified data
        if (is_array($data) && isset($data[$rootProperty])) { // @phpstan-ignore-line
            $newValue = $data[$rootProperty]; // @phpstan-ignore-line

            // If the property is a DTO and we have an array, reconstruct the DTO
            if (is_array($newValue)) {
                $reflection = new ReflectionClass(static::class);
                $property = $reflection->getProperty($rootProperty);
                $propertyType = $property->getType();

                if ($propertyType instanceof ReflectionNamedType && !$propertyType->isBuiltin()) {
                    $className = $propertyType->getName();
                    if (is_subclass_of($className, SimpleDto::class)) {
                        /** @var class-string<SimpleDto> $className */
                        $newValue = $className::from($newValue); // @phpstan-ignore-line
                    }
                }
            }

            $this->$rootProperty = $newValue; // @phpstan-ignore-line
        }

        // Invalidate toArray cache since property changed
        $this->toArrayCache = null;
    }

    /**
     * Unset value in Dto using dot notation (sets to null).
     *
     * For mutable properties (not readonly): Sets the value to null
     * For readonly properties: Throws BadMethodCallException
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @throws BadMethodCallException If property is readonly
     */
    public function unset(string $path): void
    {
        $this->set($path, null);
    }

    /**
     * Convert Dto to array recursively, including nested Dtos.
     *
     * @return array<string, mixed>
     */
    private function toArrayRecursive(): array
    {
        $data = $this->toArray();
        $result = $this->convertToArrayRecursive($data);

        // Ensure we return an array with string keys
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /** Recursively convert nested Dtos to arrays. */
    private function convertToArrayRecursive(mixed $data): mixed
    {
        if (is_array($data)) {
            /** @var array<string, mixed> $result */
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->convertToArrayRecursive($value);
            }
            return $result;
        }

        if ($data instanceof SimpleDto) {
            return $this->convertToArrayRecursive($data->toArray());
        }

        return $data;
    }

    // =========================================================================
    // Lifecycle Hooks
    // =========================================================================
    //
    // These methods can be overridden in your DTO classes to hook into
    // the lifecycle of DTO creation, validation and serialization.
    //
    // All hooks are optional and have no performance impact when not overridden.
    //
    // Example:
    //   class UserDto extends SimpleDto {
    //       protected function beforeCreate(array &$data): void {
    //           $data['email'] = strtolower($data['email'] ?? '');
    //       }
    //   }

    /**
     * Called before DTO creation, allows modifying input data.
     *
     * This hook is called before property mapping and casting.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeCreate(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after DTO creation.
     *
     * This hook is called after the DTO instance has been created
     * and all properties have been set.
     */
    protected function afterCreate(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before property mapping.
     *
     * This hook is called before #[MapFrom] attributes are processed.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeMapping(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after property mapping.
     *
     * This hook is called after #[MapFrom] attributes have been processed.
     */
    protected function afterMapping(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before casting a property value.
     *
     * This hook is called before type casting, nested DTOs and custom casters.
     * You can modify the value by reference.
     *
     * @param string $property Property name
     * @param mixed $value Property value (modifiable by reference)
     */
    protected function beforeCasting(string $property, mixed &$value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after casting a property value.
     *
     * This hook is called after type casting, nested DTOs and custom casters.
     *
     * @param string $property Property name
     * @param mixed $value Property value (after casting)
     */
    protected function afterCasting(string $property, mixed $value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before validation.
     *
     * This hook is called before validation rules are applied.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeValidation(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after validation.
     *
     * This hook is called after validation rules have been applied.
     * You can inspect the validation result.
     *
     * @param ValidationResult $result Validation result
     */
    protected function afterValidation(ValidationResult $result): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before serialization (toArray/toJson).
     *
     * This hook is called before the DTO is converted to an array.
     * You can modify the output data array by reference.
     *
     * @param array<string, mixed> $data Output data (modifiable by reference)
     */
    protected function beforeSerialization(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after serialization (toArray/toJson).
     *
     * This hook is called after the DTO has been converted to an array.
     * You can modify and return the output data.
     *
     * @param array<string, mixed> $data Output data
     * @return array<string, mixed> Modified output data
     */
    protected function afterSerialization(array $data): array
    {
        // Override in subclass to add custom logic
        return $data;
    }

    // =========================================================================
    // Magic Methods for Mutability Control
    // =========================================================================

    /**
     * Magic method to set property values.
     *
     * By default, SimpleDto uses readonly properties for immutability.
     * This method allows setting properties if they are NOT readonly.
     *
     * @param string $name Property name
     * @param mixed $value Property value
     * @throws RuntimeException If property is readonly
     */
    public function __set(string $name, mixed $value): void
    {
        if (SimpleEngine::isPropertyMutable(static::class, $name)) {
            $this->$name = $value;
        } else {
            throw new RuntimeException(
                sprintf(
                    'Cannot modify readonly property "%s" on DTO "%s". ' .
                    'Remove the readonly keyword from the property to allow modifications.',
                    $name,
                    static::class
                )
            );
        }
    }

    /**
     * Get mapping configuration for this DTO.
     *
     * Returns an array mapping property names to their source keys.
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getMappingConfig(): array
    {
        return SimpleEngine::getMappingConfig(static::class);
    }

    /**
     * Clear the mapping cache for this DTO.
     *
     * Useful for testing or when mapping configuration changes dynamically.
     */
    public static function clearMappingCache(): void
    {
        SimpleEngine::clearMappingCache(static::class);
    }

    /**
     * Check if a computed property has a cached value.
     *
     * @param string $name The name of the computed property
     * @return bool True if the computed property has a cached value
     */
    public function hasComputedCache(string $name): bool
    {
        return SimpleEngine::hasComputedCache($this, $name);
    }

    /**
     * Clear the computed property cache.
     *
     * This is useful when you want to force recomputation of computed properties.
     *
     * @param string|null $property Specific property to clear or null to clear all
     */
    public function clearComputedCache(?string $property = null): static
    {
        // Clear local computed cache (from SimpleDtoComputedTrait)
        if (isset($this->computedCache)) {
            if (null === $property) {
                $this->computedCache = [];
            } else {
                unset($this->computedCache[$property]);
            }
        }

        // Clear SimpleEngine's computed values cache
        SimpleEngine::clearComputedCache($this, $property);

        // Clear toArray/toJson caches to ensure fresh state
        $this->clearSerializationCaches();

        return $this;
    }

    /**
     * Include lazy computed properties in the next toArray() or toJson() call.
     *
     * @param array<int, string> $names The names of the lazy computed properties to include
     * @return static A new instance with the lazy computed properties included
     */
    public function includeComputed(array $names): static
    {
        return SimpleEngine::includeComputed($this, $names); // @phpstan-ignore-line
    }

    /**
     * Include lazy properties in the next toArray() or toJson() call.
     *
     * This is an alias for includeComputed() for backward compatibility.
     *
     * @param array<int, string> $names The names of the lazy properties to include
     * @return static A new instance with the lazy properties included
     */
    public function include(array $names): static
    {
        return SimpleEngine::includeComputed($this, $names); // @phpstan-ignore-line
    }

    /**
     * Include all lazy properties in the next toArray() or toJson() call.
     *
     * @return static A new instance with all lazy properties included
     */
    public function includeAll(): static
    {
        return SimpleEngine::includeAllLazy($this); // @phpstan-ignore-line
    }

    /**
     * Create a type-safe collection of Dtos.
     *
     * @param array<int|string, mixed> $items
     * @return DtoCollection<static> The collection of Dtos
     * @phpstan-return DtoCollection<static>
     */
    public static function collection(array $items = []): DtoCollection // @phpstan-ignore-line
    {
        /** @var DtoCollection<static> $dataCollection @phpstan-ignore-line */
        $dataCollection = DtoCollection::forDto(static::class, $items); // @phpstan-ignore-line

        return $dataCollection;
    }
}
