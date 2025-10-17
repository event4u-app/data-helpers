<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Trait for handling lazy-loaded properties in SimpleDTOs.
 *
 * Lazy properties are marked with #[Lazy] attribute and are not included
 * in toArray() or JSON serialization by default. They must be explicitly
 * requested using include() or includeAll() methods.
 */
trait SimpleDTOLazyTrait
{
    /**
     * List of lazy properties to include in serialization.
     *
     * @var array<string>|null
     */
    private ?array $includedLazy = null;

    /**
     * Whether to include all lazy properties.
     *
     * @var bool
     */
    private bool $includeAllLazy = false;

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
     * Get all lazy properties for this DTO class.
     *
     * Returns a map of property names to their Lazy attribute instances.
     *
     * @return array<string, Lazy>
     */
    private function getLazyProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $lazy = [];

        try {
            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $attributes = $property->getAttributes(Lazy::class);

                if (!empty($attributes)) {
                    $lazyAttr = $attributes[0]->newInstance();
                    $lazy[$property->getName()] = $lazyAttr;
                }
            }
        } catch (ReflectionException $e) {
            // If reflection fails, return empty array
            return [];
        }

        $cache[$class] = $lazy;

        return $lazy;
    }

    /**
     * Check if a property should be included based on lazy loading rules.
     *
     * @param string $propertyName The property name
     * @param Lazy $lazyAttr The Lazy attribute instance
     *
     * @return bool
     */
    private function shouldIncludeLazy(string $propertyName, Lazy $lazyAttr): bool
    {
        // If includeAll() was called, include all lazy properties
        if ($this->includeAllLazy) {
            return true;
        }

        // If property is explicitly included via include()
        if ($this->includedLazy !== null && in_array($propertyName, $this->includedLazy, true)) {
            return true;
        }

        // Check conditional loading based on context
        if ($lazyAttr->when !== null && $this->visibilityContext === $lazyAttr->when) {
            return true;
        }

        // By default, lazy properties are not included
        return false;
    }

    /**
     * Filter out lazy properties from the data array.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed>
     */
    private function filterLazyProperties(array $data): array
    {
        $lazyProperties = $this->getLazyProperties();

        if (empty($lazyProperties)) {
            return $data;
        }

        $filtered = [];

        foreach ($data as $key => $value) {
            // If property is lazy and should not be included, skip it
            if (isset($lazyProperties[$key]) && !$this->shouldIncludeLazy($key, $lazyProperties[$key])) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    /**
     * Get all lazy property names.
     *
     * @return array<string>
     */
    private function getLazyPropertyNames(): array
    {
        return array_keys($this->getLazyProperties());
    }

    /**
     * Check if a property is lazy.
     *
     * @param string $propertyName The property name
     *
     * @return bool
     */
    private function isLazyProperty(string $propertyName): bool
    {
        $lazyProperties = $this->getLazyProperties();

        return isset($lazyProperties[$propertyName]);
    }
}

