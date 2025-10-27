<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\Computed;
use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\Support\ReflectionCache;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Trait for handling computed properties in SimpleDtos.
 *
 * Computed properties are methods marked with #[Computed] attribute that
 * are automatically included in toArray() and JSON serialization.
 */
trait SimpleDtoComputedTrait
{
    /**
     * Cache for computed property values.
     *
     * @var array<string, mixed>
     */
    private array $computedCache = [];

    /**
     * List of computed properties to include (for lazy computed properties).
     *
     * @var array<string>|null
     */
    private ?array $includedComputed = null;

    /**
     * Include specific computed properties in serialization.
     *
     * This is used for lazy computed properties that are not included by default.
     *
     * @param array<string> $properties List of computed property names to include
     */
    public function includeComputed(array $properties): static
    {
        $clone = clone $this;
        // Note: array_merge is correct here for numeric arrays (appends items)
        // + operator would not work correctly for numeric keys
        $clone->includedComputed = array_merge($clone->includedComputed ?? [], $properties);
        // Clone the cache array to avoid sharing between instances
        $clone->computedCache = $this->computedCache;

        return $clone;
    }

    /**
     * Get all computed properties for this Dto.
     *
     * @return array<string, Computed> Map of method name => Computed attribute
     */
    private function getComputedProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $computed = [];

        try {
            $methods = ReflectionCache::getMethods($this);

            foreach ($methods as $method) {
                // Skip magic methods and constructor
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $attributes = ReflectionCache::getMethodAttributes($this, $method->getName());

                if (isset($attributes[Computed::class])) {
                    $attribute = $attributes[Computed::class];
                    if ($attribute instanceof Computed) {
                        $computed[$method->getName()] = $attribute;
                    }
                }
            }
        } catch (ReflectionException) {
            // If reflection fails, return empty array
            return [];
        }

        $cache[$class] = $computed;

        return $computed;
    }

    /**
     * Get the value of a computed property.
     *
     * @param string $methodName The method name
     * @param Computed $computedAttr The Computed attribute
     */
    private function getComputedValue(string $methodName, Computed $computedAttr): mixed
    {
        // Check cache if caching is enabled
        if ($computedAttr->cache && isset($this->computedCache[$methodName])) {
            return $this->computedCache[$methodName];
        }

        // Compute the value
        try {
            $value = $this->{$methodName}();

            // Cache the value if caching is enabled
            if ($computedAttr->cache) {
                $this->computedCache[$methodName] = $value;
            }

            return $value;
        } catch (Throwable) {
            // If computation fails, return null
            return null;
        }
    }

    /**
     * Check if a computed property should be included in serialization.
     *
     * @param string $methodName The method name
     * @param Computed $computedAttr The Computed attribute
     */
    private function shouldIncludeComputed(string $methodName, Computed $computedAttr): bool
    {
        // If it's lazy, only include if explicitly requested
        if ($computedAttr->lazy) {
            return null !== $this->includedComputed && in_array($methodName, $this->includedComputed, true);
        }

        // Non-lazy computed properties are always included
        return true;
    }

    /**
     * Check if a computed property should be visible based on visibility attributes.
     *
     * @param string $methodName The method name
     * @param string $context 'array' or 'json'
     */
    private function isComputedPropertyVisible(string $methodName, string $context): bool
    {
        try {
            $reflection = new ReflectionClass($this);
            $method = $reflection->getMethod($methodName);

            // Check for Hidden attribute
            $hiddenAttrs = $method->getAttributes(Hidden::class);
            if (!empty($hiddenAttrs)) {
                return false;
            }

            // Check for HiddenFromArray attribute
            if ('array' === $context) {
                $hiddenFromArrayAttrs = $method->getAttributes(
                    HiddenFromArray::class
                );
                if (!empty($hiddenFromArrayAttrs)) {
                    return false;
                }
            }

            // Check for HiddenFromJson attribute
            if ('json' === $context) {
                $hiddenFromJsonAttrs = $method->getAttributes(
                    HiddenFromJson::class
                );
                if (!empty($hiddenFromJsonAttrs)) {
                    return false;
                }
            }

            return true;
        } catch (ReflectionException) {
            return true;
        }
    }

    /**
     * Check if a computed property should be included based on only/except filters.
     *
     * @param string $outputName The output name of the computed property
     */
    private function isComputedPropertyInFilter(string $outputName): bool
    {
        // Check only() filter
        if (null !== $this->onlyProperties && !in_array($outputName, $this->onlyProperties, true)) {
            return false;
        }

        // Check except() filter
        if (null !== $this->exceptProperties && in_array($outputName, $this->exceptProperties, true)) {
            return false;
        }

        return true;
    }

    /**
     * Get all computed property values that should be included in serialization.
     *
     * @param string $context 'array' or 'json'
     *
     * @return array<string, mixed>
     */
    private function getComputedValues(string $context = 'array'): array
    {
        $values = [];
        $computed = $this->getComputedProperties();

        foreach ($computed as $methodName => $computedAttr) {
            if (!$this->shouldIncludeComputed($methodName, $computedAttr)) {
                continue;
            }

            // Use custom name if provided, otherwise use method name
            $outputName = $computedAttr->name ?? $methodName;

            // Check visibility attributes
            if (!$this->isComputedPropertyVisible($methodName, $context)) {
                continue;
            }

            // Check only/except filters
            if (!$this->isComputedPropertyInFilter($outputName)) {
                continue;
            }

            $values[$outputName] = $this->getComputedValue($methodName, $computedAttr);
        }

        return $values;
    }

    /**
     * Clear the computed property cache.
     *
     * This is useful when you want to force recomputation of computed properties.
     *
     * @param string|null $property Specific property to clear, or null to clear all
     */
    public function clearComputedCache(?string $property = null): static
    {
        if (null === $property) {
            $this->computedCache = [];
        } else {
            unset($this->computedCache[$property]);
        }

        return $this;
    }

    /**
     * Check if a computed property value is cached.
     *
     * @param string $property The property name
     */
    public function hasComputedCache(string $property): bool
    {
        return isset($this->computedCache[$property]);
    }

    /**
     * Magic method called when cloning the Dto.
     *
     * Ensures that the computed cache is not shared between instances.
     */
    public function __clone(): void
    {
        // Create a new array to avoid sharing cache between instances
        $this->computedCache = [];
    }
}
