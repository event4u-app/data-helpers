<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

/**
 * Trait for adding additional data to Dtos via with() method.
 *
 * The with() method allows adding extra properties to the Dto output
 * without modifying the Dto class itself. This is useful for:
 * - Adding computed values conditionally
 * - Including related data
 * - Adding metadata
 * - API responses with dynamic fields
 */
trait SimpleDtoWithTrait
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
     * - Nested Dtos: with('user', $userDto)
     *
     * @param string|array<string, mixed> $key Property name or array of properties
     * @param mixed $value Property value (only used when $key is string)
     */
    public function with(string|array $key, mixed $value = null): static
    {
        // Phase 6 Optimization: Lazy cloning - avoid clone if no data to add
        if ([] === $key) {
            return $this; // No data to add, return self
        }

        $clone = clone $this;

        if (is_array($key)) {
            // with(['key' => 'value', ...])
            // Performance: Use + operator instead of array_merge (10-20% faster)
            // Note: $key + ($clone->additionalData ?? []) means new data has priority
            $clone->additionalData = $key + ($clone->additionalData ?? []);
        } else {
            // with('key', 'value')
            // Performance: Use + operator instead of array_merge (10-20% faster)
            $clone->additionalData = [$key => $value] + ($clone->additionalData ?? []);
        }

        return $clone;
    }

    /**
     * Get and evaluate additional data.
     *
     * Evaluates callbacks and converts Dtos to arrays.
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

            // Convert Dtos to arrays
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            $evaluated[$key] = $value;
        }

        return $evaluated;
    }

    /**
     * Check if additional data exists.
     *
     * Phase 6 Optimization #4: Inline candidate - simple boolean check
     * Consider inlining at call sites for performance
     */
    private function hasAdditionalData(): bool
    {
        // Optimized: Single expression, no intermediate variables
        return null !== $this->additionalData && [] !== $this->additionalData;
    }
}
