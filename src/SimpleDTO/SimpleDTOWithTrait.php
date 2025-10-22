<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

/**
 * Trait for adding additional data to DTOs via with() method.
 *
 * The with() method allows adding extra properties to the DTO output
 * without modifying the DTO class itself. This is useful for:
 * - Adding computed values conditionally
 * - Including related data
 * - Adding metadata
 * - API responses with dynamic fields
 */
trait SimpleDTOWithTrait
{
    /**
     * Additional data to include in serialization.
     *
     * @var array<string, mixed>|null
     */
    private ?array $additionalData = null;

    /**
     * Add additional data to include in serialization.
     *
     * Supports two syntaxes:
     * - with($key, $value) - Add a single property
     * - with(['key' => 'value']) - Add multiple properties
     *
     * Values can be:
     * - Static values: with('key', 'value')
     * - Callbacks: with('key', fn($dto) => $dto->computeValue())
     * - Nested DTOs: with('user', $userDTO)
     *
     * @param string|array<string, mixed> $key Property name or array of properties
     * @param mixed $value Property value (only used when $key is string)
     */
    public function with(string|array $key, mixed $value = null): static
    {
        $clone = clone $this;

        if (is_array($key)) {
            // with(['key' => 'value', ...])
            $clone->additionalData = array_merge($clone->additionalData ?? [], $key);
        } else {
            // with('key', 'value')
            $clone->additionalData = array_merge($clone->additionalData ?? [], [$key => $value]);
        }

        return $clone;
    }

    /**
     * Get and evaluate additional data.
     *
     * Evaluates callbacks and converts DTOs to arrays.
     *
     * @return array<string, mixed>
     */
    private function getAdditionalData(): array
    {
        if (null === $this->additionalData) {
            return [];
        }

        $evaluated = [];

        foreach ($this->additionalData as $key => $value) {
            // Evaluate callbacks
            if (is_callable($value)) {
                $value = $value($this);
            }

            // Convert DTOs to arrays
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            $evaluated[$key] = $value;
        }

        return $evaluated;
    }

    /** Check if additional data exists. */
    private function hasAdditionalData(): bool
    {
        return null !== $this->additionalData && !empty($this->additionalData);
    }
}
