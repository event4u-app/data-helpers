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

    /**
     * Convert the DTO to an array.
     *
     * Returns all public properties as an associative array.
     * Applies casts (set method), output mapping, and visibility filters.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = get_object_vars($this);

        // Remove internal visibility properties
        unset($data['onlyProperties'], $data['exceptProperties'], $data['visibilityContext']);

        // Apply casts (set method) to convert values back
        $data = $this->applyOutputCasts($data);

        // Apply output mapping
        $data = $this->applyOutputMapping($data);

        // Apply visibility filters
        return $this->applyArrayVisibilityFilters($data);
    }

    /**
     * Serialize the DTO to JSON.
     *
     * This method is called automatically by json_encode().
     * Applies casts (set method), output mapping, and visibility filters.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);

        // Remove internal visibility properties
        unset($data['onlyProperties'], $data['exceptProperties'], $data['visibilityContext']);

        // Apply casts (set method) to convert values back
        $data = $this->applyOutputCasts($data);

        // Apply output mapping
        $data = $this->applyOutputMapping($data);

        // Apply visibility filters
        return $this->applyJsonVisibilityFilters($data);
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
}

