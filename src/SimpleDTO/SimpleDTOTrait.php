<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

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
    use SimpleDTOMappingTrait;
    use SimpleDTOVisibilityTrait;
    use SimpleDTOComputedTrait {
        SimpleDTOComputedTrait::include as includeComputed;
    }
    use SimpleDTOLazyTrait {
        SimpleDTOLazyTrait::includeAll as includeLazyAll;
    }

    /**
     * Include specific properties in serialization.
     *
     * This works for both lazy computed properties and lazy properties.
     *
     * @param array<string> $properties List of property names to include
     *
     * @return static
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

    /**
     * Include all lazy properties in serialization.
     *
     * @return static
     */
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
            $data['includeAllLazy']
        );

        // Apply casts (set method) to convert values back
        $data = $this->applyOutputCasts($data);

        // Apply output mapping
        $data = $this->applyOutputMapping($data);

        // Filter lazy properties
        $data = $this->filterLazyProperties($data);

        // Apply visibility filters
        $data = $this->applyArrayVisibilityFilters($data);

        // Add computed properties
        $computed = $this->getComputedValues('array');
        $data = array_merge($data, $computed);

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
            $data['includeAllLazy']
        );

        // Apply casts (set method) to convert values back
        $data = $this->applyOutputCasts($data);

        // Apply output mapping
        $data = $this->applyOutputMapping($data);

        // Filter lazy properties
        $data = $this->filterLazyProperties($data);

        // Apply visibility filters
        $data = $this->applyJsonVisibilityFilters($data);

        // Add computed properties
        $computed = $this->getComputedValues('json');
        $data = array_merge($data, $computed);

        return $data;
    }

    /**
     * Create a DTO instance from an array.
     *
     * Uses named arguments to construct the DTO. The array keys
     * must match the constructor parameter names.
     * Applies property mapping and casts defined in the respective methods.
     *
     * Processing order:
     * 1. Apply property mapping (#[MapFrom] attributes)
     * 2. Apply casts (casts() method)
     * 3. Construct DTO instance
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        // Step 1: Apply property mapping
        $data = static::applyMapping($data);

        // Step 2: Get casts without creating an instance
        $casts = static::getCasts();

        // Step 3: Apply casts if defined
        if ([] !== $casts) {
            $data = static::applyCasts($data, $casts);
        }

        /** @phpstan-ignore new.static */
        return new static(...$data);
    }

    /**
     * Create a type-safe collection of DTOs.
     *
     * @param array<int|string, mixed> $items
     * @return DataCollection<static>
     */
    public static function collection(array $items = []): DataCollection
    {
        return DataCollection::forDto(static::class, $items);
    }
}

