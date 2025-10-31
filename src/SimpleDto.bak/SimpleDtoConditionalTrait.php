<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Contracts\ConditionalProperty;
use event4u\DataHelpers\Support\ReflectionCache;
use ReflectionAttribute;
use ReflectionProperty;

/**
 * Trait for handling conditional properties in SimpleDtos.
 *
 * Conditional properties are marked with attributes implementing ConditionalProperty interface
 * and are only included in toArray() and JSON serialization when their condition is met.
 */
trait SimpleDtoConditionalTrait
{
    /**
     * Context for conditional property evaluation.
     *
     * @var array<string, mixed>|null
     */
    private ?array $conditionalContext = null;

    /**
     * Set context for conditional property evaluation.
     *
     * @param array<string, mixed> $context Context data
     */
    public function withContext(array $context): static
    {
        // Phase 6 Optimization: Lazy cloning - avoid clone if no context to add
        if ([] === $context) {
            return $this; // No context to add, return self
        }

        $clone = clone $this;
        // Performance: Use + operator instead of array_merge (10-20% faster)
        // Note: $context + ($this->conditionalContext ?? []) means new context has priority
        $clone->conditionalContext = $context + ($this->conditionalContext ?? []);

        return $clone;
    }

    /**
     * Get all conditional property attributes.
     *
     * @return array<string, array<ConditionalProperty>> Property name => array of ConditionalProperty attributes
     */
    protected static function getConditionalProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        // Phase 8: Use ReflectionCache for ReflectionClass but direct getAttributes() for filtering
        // We need IS_INSTANCEOF filtering which ReflectionCache doesn't support
        $reflection = ReflectionCache::getClass($class);
        $conditionals = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            // Phase 8: Use direct getAttributes() with IS_INSTANCEOF to get all ConditionalProperty implementations
            $attributes = $reflectionProperty->getAttributes(
                ConditionalProperty::class,
                ReflectionAttribute::IS_INSTANCEOF
            );

            if (empty($attributes)) {
                continue;
            }

            $propertyConditionals = [];
            foreach ($attributes as $attribute) {
                $propertyConditionals[] = $attribute->newInstance();
            }

            $conditionals[$reflectionProperty->getName()] = $propertyConditionals;
        }

        $cache[$class] = $conditionals;

        return $conditionals;
    }

    /**
     * Check if a property should be included based on its conditional attributes.
     *
     * @param string $propertyName Property name
     * @param mixed $value Property value
     * @return bool True if property should be included
     */
    private function shouldIncludeConditionalProperty(string $propertyName, mixed $value): bool
    {
        $conditionals = static::getConditionalProperties();

        if (!isset($conditionals[$propertyName])) {
            return true; // No conditional attributes, include by default
        }

        // All conditional attributes must pass (AND logic)
        foreach ($conditionals[$propertyName] as $conditional) {
            if (!$conditional->shouldInclude($value, $this, $this->conditionalContext ?? [])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter properties based on conditional attributes.
     *
     * @param array<string, mixed> $data Property data
     * @return array<string, mixed> Filtered data
     */
    private function applyConditionalFilters(array $data): array
    {
        $conditionals = static::getConditionalProperties();

        if ([] === $conditionals) {
            return $data;
        }

        $filtered = [];

        foreach ($data as $key => $value) {
            if ($this->shouldIncludeConditionalProperty($key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
