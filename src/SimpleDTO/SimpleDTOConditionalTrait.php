<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Contracts\ConditionalProperty;
use event4u\DataHelpers\Support\ReflectionCache;
use ReflectionAttribute;
use ReflectionProperty;

/**
 * Trait for handling conditional properties in SimpleDTOs.
 *
 * Conditional properties are marked with attributes implementing ConditionalProperty interface
 * and are only included in toArray() and JSON serialization when their condition is met.
 */
trait SimpleDTOConditionalTrait
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
        $clone = clone $this;
        $clone->conditionalContext = array_merge($this->conditionalContext ?? [], $context);

        return $clone;
    }

    /**
     * Get all conditional property attributes.
     *
     * @return array<string, array<ConditionalProperty>> Property name => array of ConditionalProperty attributes
     */
    private static function getConditionalProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $reflection = ReflectionCache::getClass($class);
        $conditionals = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(
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

            $conditionals[$property->getName()] = $propertyConditionals;
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

