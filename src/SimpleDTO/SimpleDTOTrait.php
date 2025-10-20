<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Trait providing default implementations for DTOs.
 *
 * This trait orchestrates the core DTO functionality by composing
 * specialized traits for different concerns:
 * - SimpleDTOCastsTrait: Handles attribute casting
 * - SimpleDTOValidationTrait: Handles validation
 * - SimpleDTOMappingTrait: Handles property mapping
 *
 * Responsibilities:
 * - Convert DTOs to arrays (toArray)
 * - Serialize DTOs to JSON (jsonSerialize)
 * - Create DTOs from arrays (fromArray)
 * - Coordinate between specialized traits
 *
 * Example usage:
 *   class UserDTO extends SimpleDTO {
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
 *   $user = UserDTO::validateAndCreate([
 *       'user_name' => 'John',
 *       'email_address' => 'john@example.com',
 *       'age' => 30,
 *       'created_at' => '2024-01-01 12:00:00'
 *   ]);
 *
 *   // Or create without validation
 *   $user = UserDTO::fromArray([...]);
 */
trait SimpleDTOTrait
{
    use SimpleDTOCastsTrait;
    use SimpleDTOValidationTrait;
    use SimpleDTORequestValidationTrait;
    use SimpleDTOMappingTrait;
    use SimpleDTOMapperTrait;
    use SimpleDTOVisibilityTrait;
    use SimpleDTOWrappingTrait;
    use SimpleDTOSerializerTrait;
    use SimpleDTOTransformerTrait;
    use SimpleDTONormalizerTrait;
    use SimpleDTOPipelineTrait;
    use SimpleDTOPerformanceTrait;
    use SimpleDTOLazyCastTrait;
    use SimpleDTOBenchmarkTrait;
    use SimpleDTOOptionalTrait;
    use SimpleDTOComputedTrait;
    use SimpleDTOLazyTrait;
    use SimpleDTOConditionalTrait;
    use SimpleDTOWithTrait;
    use SimpleDTOSortingTrait;

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
     * Convert the DTO to an array.
     *
     * Returns all public properties as an associative array.
     * Applies casts (set method), output mapping, visibility filters, lazy loading, and computed properties.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = get_object_vars($this);

        // Remove internal properties
        unset(
            $data['onlyProperties'],
            $data['exceptProperties'],
            $data['visibilityContext'],
            $data['computedCache'],
            $data['includedComputed'],
            $data['includedLazy'],
            $data['includeAllLazy'],
            $data['wrapKey'],
            $data['objectVarsCache'],
            $data['castedProperties'],
            $data['conditionalContext'],
            $data['additionalData'],
            $data['sortingEnabled'],
            $data['sortDirection'],
            $data['nestedSort'],
            $data['sortCallback']
        );

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

        // Apply visibility filters
        $data = $this->applyArrayVisibilityFilters($data);

        // Apply conditional filters
        $data = $this->applyConditionalFilters($data);

        // Add computed properties
        $computed = $this->getComputedValues('array');
        $data = array_merge($data, $computed);

        // Add additional data from with() method
        $additional = $this->getAdditionalData();
        $data = array_merge($data, $additional);

        // Apply wrapping
        $data = $this->applyWrapping($data);

        // Apply sorting
        $data = $this->applySorting($data);

        return $data;
    }

    /**
     * Serialize the DTO to JSON.
     *
     * This method is called automatically by json_encode().
     * Applies casts (set method), output mapping, visibility filters, lazy loading, and computed properties.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);

        // Remove internal properties
        unset(
            $data['onlyProperties'],
            $data['exceptProperties'],
            $data['visibilityContext'],
            $data['computedCache'],
            $data['includedComputed'],
            $data['includedLazy'],
            $data['includeAllLazy'],
            $data['wrapKey'],
            $data['objectVarsCache'],
            $data['castedProperties'],
            $data['conditionalContext'],
            $data['additionalData'],
            $data['sortingEnabled'],
            $data['sortDirection'],
            $data['nestedSort'],
            $data['sortCallback']
        );

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

        // Apply visibility filters
        $data = $this->applyJsonVisibilityFilters($data);

        // Apply conditional filters
        $data = $this->applyConditionalFilters($data);

        // Add computed properties
        $computed = $this->getComputedValues('json');
        $data = array_merge($data, $computed);

        // Add additional data from with() method
        $additional = $this->getAdditionalData();
        $data = array_merge($data, $additional);

        // Apply wrapping
        $data = $this->applyWrapping($data);

        // Apply sorting
        $data = $this->applySorting($data);

        return $data;
    }

    /**
     * Create a DTO instance from an array.
     *
     * This is an alias for fromSource() for backward compatibility.
     * Uses the full mapping pipeline with the following priority:
     * 1. Template (from template() method) - HIGHEST PRIORITY
     * 2. Attributes (#[MapFrom], #[MapTo])
     * 3. Automapping (fallback)
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
        return static::fromSource($data, $template, $filters, $pipeline);
    }

    /**
     * Create a type-safe collection of DTOs.
     *
     * @param array<int|string, mixed> $items
     * @return DataCollection<static> The collection of DTOs
     * @phpstan-return DataCollection<static>
     */
    public static function collection(array $items = []): DataCollection
    {
        /** @var DataCollection<static> $dataCollection */
        $dataCollection = DataCollection::forDto(static::class, $items);

        return $dataCollection;
    }
}

