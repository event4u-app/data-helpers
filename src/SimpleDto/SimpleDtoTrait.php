<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\SimpleDto\Support\FastPath;
use event4u\DataHelpers\SimpleDto\Support\UltraFastEngine;
use InvalidArgumentException;
use RuntimeException;

/**
 * Trait providing default implementations for Dtos.
 *
 * This trait orchestrates the core Dto functionality by composing
 * specialized traits for different concerns:
 * - SimpleDtoCastsTrait: Handles attribute casting
 * - SimpleDtoValidationTrait: Handles validation
 * - SimpleDtoMappingTrait: Handles property mapping
 *
 * Responsibilities:
 * - Convert Dtos to arrays (toArray)
 * - Serialize Dtos to JSON (jsonSerialize)
 * - Create Dtos from arrays (fromArray)
 * - Coordinate between specialized traits
 *
 * Example usage:
 *   class UserDto extends SimpleDto {
 *       public function __construct(
 *           #[Required]
 *           #[Email]
 *           #[MapFrom('email_address')]
 *           public readonly string $email,
 *
 *           #[Required]
 *           #[Min(3)]
 *           #[MapFrom('user_name')]
 *           public readonly string $name,
 *
 *           #[Between(18, 120)]
 *           public readonly ?int $age = null,
 *
 *           #[MapFrom('created_at')]
 *           public readonly ?DateTimeImmutable $createdAt = null,
 *       ) {}
 *
 *       protected function casts(): array {
 *           return [
 *               'createdAt' => 'datetime',
 *           ];
 *       }
 *   }
 *
 *   // Create with validation and mapping
 *   $user = UserDto::validateAndCreate([
 *       'user_name' => 'John',
 *       'email_address' => 'john@example.com',
 *       'age' => 30,
 *       'created_at' => '2024-01-01 12:00:00'
 *   ]);
 *
 *   // Or create without validation
 *   $user = UserDto::fromArray([...]);
 */
trait SimpleDtoTrait
{
    use SimpleDtoCastsTrait;
    use SimpleDtoValidationTrait;
    use SimpleDtoRequestValidationTrait;
    use SimpleDtoMappingTrait;
    use SimpleDtoMapperTrait;
    use SimpleDtoVisibilityTrait;
    use SimpleDtoWrappingTrait;
    use SimpleDtoSerializerTrait;
    use SimpleDtoImporterTrait;
    use SimpleDtoTransformerTrait;
    use SimpleDtoNormalizerTrait;
    use SimpleDtoPipelineTrait;
    use SimpleDtoPerformanceTrait;
    use SimpleDtoLazyCastTrait;
    use SimpleDtoBenchmarkTrait;
    use SimpleDtoOptionalTrait;
    use SimpleDtoComputedTrait;
    use SimpleDtoLazyTrait;
    use SimpleDtoConditionalTrait;
    use SimpleDtoWithTrait;
    use SimpleDtoSortingTrait;
    use SimpleDtoDiffTrait;

    /**
     * Include specific properties in serialization.
     *
     * This works for both lazy computed properties and lazy properties.
     *
     * @param array<string> $properties List of property names to include
     */
    public function include(array $properties): static
    {
        $clone = clone $this;

        // Include computed properties
        $clone->includedComputed = array_merge($clone->includedComputed ?? [], $properties);
        $clone->computedCache = $this->computedCache;

        // Include lazy properties
        $clone->includedLazy = array_merge($clone->includedLazy ?? [], $properties);

        return $clone;
    }

    /** Include all lazy properties in serialization. */
    public function includeAll(): static
    {
        $clone = clone $this;
        $clone->includeAllLazy = true;

        return $clone;
    }

    /**
     * Internal properties that should be excluded from toArray/jsonSerialize.
     *
     * @var array<string, true>
     */
    private const INTERNAL_PROPERTIES = [
        'onlyProperties' => true,
        'exceptProperties' => true,
        'visibilityContext' => true,
        'computedCache' => true,
        'includedComputed' => true,
        'includedLazy' => true,
        'includeAllLazy' => true,
        'wrapKey' => true,
        'objectVarsCache' => true,
        'castedProperties' => true,
        'conditionalContext' => true,
        'additionalData' => true,
        'sortingEnabled' => true,
        'sortDirection' => true,
        'nestedSort' => true,
        'sortCallback' => true,
    ];

    /**
     * Get object properties with internal properties removed.
     *
     * Phase 6 Optimization #2/#5: Optimized property filtering
     * - Uses foreach instead of array_diff_key (faster for small arrays)
     * - Avoids creating intermediate arrays
     *
     * @return array<string, mixed>
     */
    private function getCleanObjectVars(): array
    {
        $data = get_object_vars($this);

        // Phase 6 Optimization: Direct filtering is faster than array_diff_key for small sets
        foreach (array_keys(self::INTERNAL_PROPERTIES) as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Process data for serialization (shared logic between toArray and jsonSerialize).
     *
     * @param string $context Either 'array' or 'json'
     * @return array<string, mixed>
     */
    private function processDataForSerialization(string $context): array
    {
        $data = $this->getCleanObjectVars();

        // Unwrap optional properties
        $data = static::unwrapOptionalProperties($data);

        // Filter lazy properties (before unwrapping)
        $data = $this->filterLazyProperties($data);

        // Unwrap lazy properties
        $data = $this->unwrapLazyProperties($data);

        // Apply casts (set method) to convert values back
        $data = $this->applyOutputCasts($data);

        // Apply output mapping
        $data = $this->applyOutputMapping($data);

        // Apply visibility filters (context-specific)
        $data = 'json' === $context
            ? $this->applyJsonVisibilityFilters($data)
            : $this->applyArrayVisibilityFilters($data);

        // Apply conditional filters
        $data = $this->applyConditionalFilters($data);

        // Add computed properties (context-specific)
        $computed = $this->getComputedValues($context);
        // Performance: Use + operator instead of array_merge (10-20% faster)
        // Note: $computed + $data means computed properties override existing data
        $data = $computed + $data;

        // Add additional data from with() method
        $additional = $this->getAdditionalData();
        // Performance: Use + operator instead of array_merge (10-20% faster)
        // Note: $additional + $data means additional data overrides existing data
        $data = $additional + $data;

        // Apply wrapping
        $data = $this->applyWrapping($data);

        // Apply sorting
        $data = $this->applySorting($data);

        return $data;
    }

    /**
     * Convert the Dto to an array.
     *
     * Returns all public properties as an associative array.
     * Applies casts (set method), output mapping, visibility filters, lazy loading, and computed properties.
     *
     * Phase 7 Optimization: Uses fast path for simple DTOs (30-50% faster)
     * Ultra-Fast Mode: Uses UltraFastEngine for maximum speed (target: <1μs)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Ultra-Fast Mode: Bypass all overhead
        if (UltraFastEngine::isUltraFast(static::class)) {
            return UltraFastEngine::toArray($this);
        }

        // Phase 7: Fast path for simple DTOs without attributes or runtime modifications
        if (FastPath::canUseFastPath(static::class) && FastPath::canUseFastPathAtRuntime($this)) {
            return FastPath::fastToArray($this);
        }

        return $this->processDataForSerialization('array');
    }

    /**
     * Serialize the Dto to JSON.
     *
     * This method is called automatically by json_encode().
     * Applies casts (set method), output mapping, visibility filters, lazy loading, and computed properties.
     *
     * Phase 7 Optimization: Uses fast path for simple DTOs (30-50% faster)
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        // Phase 7: Fast path for simple DTOs without attributes or runtime modifications
        if (FastPath::canUseFastPath(static::class) && FastPath::canUseFastPathAtRuntime($this)) {
            return FastPath::fastToArray($this);
        }

        return $this->processDataForSerialization('json');
    }

    /**
     * Create a Dto instance from an array.
     *
     * This is an alias for fromSource() for backward compatibility.
     * Uses the full mapping pipeline with the following priority:
     * 1. Template (from template() method) - HIGHEST PRIORITY
     * 2. Attributes (#[MapFrom], #[MapTo])
     * 3. Automapping (fallback)
     *
     * Performance Optimization: If the class has #[UltraFast] attribute,
     * bypasses all overhead and uses direct reflection (target: <1μs).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromArray(
        array $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Ultra-Fast Mode: Bypass all overhead
        if (UltraFastEngine::isUltraFast(static::class)) {
            /** @var static */
            return UltraFastEngine::createFromArray(static::class, $data);
        }

        return static::fromSource($data, $template, $filters, $pipeline);
    }

    /**
     * Create a Dto instance from mixed data (array, JSON, XML, or object).
     *
     * This method accepts multiple input formats:
     * - Arrays (always supported)
     * - JSON strings (requires #[ConverterMode] with #[UltraFast])
     * - XML strings (requires #[ConverterMode] with #[UltraFast])
     * - YAML strings (requires #[ConverterMode] with #[UltraFast])
     * - Objects (requires #[ConverterMode] with #[UltraFast])
     *
     * Performance:
     * - Array only: ~0.8μs (UltraFast) or ~18.4μs (normal)
     * - With auto-detection: ~1.3-1.5μs (UltraFast) or not supported (normal)
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
        // Ultra-Fast Mode: Supports ConverterMode with auto-detection
        if (UltraFastEngine::isUltraFast(static::class)) {
            /** @var static */
            return UltraFastEngine::createFrom(static::class, $data);
        }

        // Normal mode: Only arrays supported
        if (!is_array($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    'SimpleDto::from() only accepts arrays in standard mode. Use #[ConverterMode] with #[UltraFast] on %s to enable JSON/XML/YAML support.',
                    static::class
                )
            );
        }

        return static::fromSource($data, $template, $filters, $pipeline);
    }

    /**
     * Create a Dto instance from a JSON string.
     *
     * This is an alias for fromSource() that accepts JSON strings.
     * The JSON will be decoded and processed through the full mapping pipeline.
     *
     * Performance Optimization: If the class has #[UltraFast] + #[ConverterMode],
     * bypasses all overhead and uses direct JSON parsing (target: ~1.2μs, no format detection).
     *
     * @param string $json JSON string
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromJson(
        string $json,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Ultra-Fast Mode: Direct JSON parsing (no format detection)
        if (UltraFastEngine::isUltraFast(static::class)) {
            /** @var static */
            return UltraFastEngine::createFromJson(static::class, $json);
        }

        return static::fromSource($json, $template, $filters, $pipeline);
    }

    /**
     * Create a Dto instance from an XML string.
     *
     * This is an alias for fromSource() that accepts XML strings.
     * The XML will be parsed and processed through the full mapping pipeline.
     *
     * Performance Optimization: If the class has #[UltraFast] + #[ConverterMode],
     * bypasses all overhead and uses direct XML parsing (target: ~1.2μs, no format detection).
     *
     * @param string $xml XML string
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromXml(
        string $xml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Ultra-Fast Mode: Direct XML parsing (no format detection)
        if (UltraFastEngine::isUltraFast(static::class)) {
            /** @var static */
            return UltraFastEngine::createFromXml(static::class, $xml);
        }

        return static::fromSource($xml, $template, $filters, $pipeline);
    }

    /**
     * Create a Dto instance from a YAML string.
     *
     * This is an alias for fromSource() that accepts YAML strings.
     * The YAML will be parsed and processed through the full mapping pipeline.
     *
     * Performance Optimization: If the class has #[UltraFast] + #[ConverterMode],
     * bypasses all overhead and uses direct YAML parsing (target: ~1.2μs, no format detection).
     *
     * @param string $yaml YAML string
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromYaml(
        string $yaml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Ultra-Fast Mode: Direct YAML parsing (no format detection)
        if (UltraFastEngine::isUltraFast(static::class)) {
            /** @var static */
            return UltraFastEngine::createFromYaml(static::class, $yaml);
        }

        return static::fromSource($yaml, $template, $filters, $pipeline);
    }

    /**
     * Create a Dto instance from a CSV string.
     *
     * This is an alias for fromSource() that accepts CSV strings.
     * The CSV will be parsed and processed through the full mapping pipeline.
     * Note: CSV parsing expects the first row to contain headers.
     *
     * Note: CSV parsing is not optimized in UltraFast mode yet.
     * Falls back to normal mode for now.
     *
     * @param string $csv CSV string
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromCsv(
        string $csv,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Note: CSV not yet optimized for UltraFast mode
        return static::fromSource($csv, $template, $filters, $pipeline);
    }

    /**
     * Create a type-safe collection of Dtos.
     *
     * @param array<int|string, mixed> $items
     * @return DataCollection<static> The collection of Dtos
     * @phpstan-return DataCollection<static>
     */
    public static function collection(array $items = []): DataCollection
    {
        /** @var DataCollection<static> $dataCollection */
        $dataCollection = DataCollection::forDto(static::class, $items);

        return $dataCollection;
    }

    /**
     * Get a value from the Dto using dot notation.
     *
     * Supports nested property access and wildcards for arrays.
     *
     * Examples:
     *   $dto->get('name')                    // Get simple property
     *   $dto->get('address.city')            // Get nested property
     *   $dto->get('emails.*.email')          // Get all emails from array
     *   $dto->get('user.orders.*.total')     // Nested wildcards
     *   $dto->get('missing', 'default')      // With default value
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $default Default value if path doesn't exist
     * @return mixed The value at the path, or default if not found
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->get($path, $default);
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

        // Ensure we return an array
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * Recursively convert nested Dtos and arrays of Dtos to arrays.
     *
     * @param mixed $value The value to convert
     * @return mixed The converted value
     */
    private function convertToArrayRecursive(mixed $value): mixed
    {
        // Handle arrays
        if (is_array($value)) {
            // Phase 6 Optimization #5: Use foreach instead of array_map (faster, less memory)
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->convertToArrayRecursive($item);
            }
            return $result;
        }

        // Handle Dtos
        if ($value instanceof DtoInterface) {
            return $this->convertToArrayRecursive($value->toArray());
        }

        // Handle objects with toArray method
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $this->convertToArrayRecursive($value->toArray());
        }

        return $value;
    }

    /**
     * Set a value in the Dto using dot notation and return a new instance.
     *
     * Since Dtos are immutable, this method returns a new instance with the updated value.
     * Supports nested property access and wildcards for arrays.
     *
     * Examples:
     *   $newDto = $dto->set('name', 'John')                    // Set simple property
     *   $newDto = $dto->set('address.city', 'Berlin')          // Set nested property
     *   $newDto = $dto->set('emails.*.verified', true)         // Set all emails as verified
     *   $newDto = $dto->set('user.orders.*.status', 'shipped') // Nested wildcards
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $value Value to set
     * @return static New Dto instance with the updated value
     */
    public function set(string $path, mixed $value): static
    {
        $data = $this->toArrayRecursive();
        DataMutator::make($data)->set($path, $value);

        // Ensure we have an array with string keys
        if (!is_array($data)) {
            return static::fromArray([]);
        }

        /** @var array<string, mixed> $data */
        return static::fromArray($data);
    }

    /**
     * Convert Dto to JSON string.
     *
     * @param int $flags JSON encoding flags (default: 0)
     * @return string JSON representation of the Dto
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->toArray(), $flags);
        if (false === $json) {
            throw new RuntimeException('Failed to encode Dto to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    // toXml(), toYaml(), and toCsv() methods are provided by SimpleDtoSerializerTrait
}
