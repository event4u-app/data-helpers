<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Attributes\Lazy;
use event4u\DataHelpers\Support\Lazy as LazyWrapper;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

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
     */
    private bool $includeAllLazy = false;

    /**
     * Include all lazy properties in serialization.
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
     * Returns a map of property names to their Lazy attribute instances or true for union types.
     *
     * @return array<string, Lazy|true>
     */
    private static function getLazyProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $lazy = [];

        try {
            $reflection = new ReflectionClass($class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                // Check for #[Lazy] attribute
                $attributes = $property->getAttributes(Lazy::class);

                if (!empty($attributes)) {
                    $lazyAttr = $attributes[0]->newInstance();
                    $lazy[$property->getName()] = $lazyAttr;
                    continue;
                }

                // Check for Lazy union type
                $type = $property->getType();

                if ($type instanceof ReflectionUnionType) {
                    foreach ($type->getTypes() as $unionType) {
                        if ($unionType instanceof ReflectionNamedType && $unionType->getName() === LazyWrapper::class) {
                            $lazy[$property->getName()] = true; // Mark as union type
                            break;
                        }
                    }
                }
            }
        } catch (ReflectionException) {
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
     * @param Lazy|true $lazyAttr The Lazy attribute instance or true for union types
     */
    private function shouldIncludeLazy(string $propertyName, Lazy|bool $lazyAttr): bool
    {
        // If includeAll() was called, include all lazy properties
        if ($this->includeAllLazy) {
            return true;
        }

        // If property is explicitly included via include()
        if (null !== $this->includedLazy && in_array($propertyName, $this->includedLazy, true)) {
            return true;
        }

        // For union types (true), check only explicit inclusion
        if (true === $lazyAttr) {
            return false;
        }
        // Check conditional loading based on context
        // By default, lazy properties are not included
        return null !== $lazyAttr->when && $this->visibilityContext === $lazyAttr->when;
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
        $lazyProperties = static::getLazyProperties();

        if ($lazyProperties === []) {
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
        return array_keys(static::getLazyProperties());
    }

    /**
     * Check if a property is lazy.
     *
     * @param string $propertyName The property name
     */
    private function isLazyProperty(string $propertyName): bool
    {
        $lazyProperties = static::getLazyProperties();

        return isset($lazyProperties[$propertyName]);
    }

    /**
     * Wrap lazy properties in Lazy wrapper.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed>
     */
    private static function wrapLazyProperties(array $data): array
    {
        $lazyProperties = static::getLazyProperties();

        if ($lazyProperties === []) {
            return $data;
        }

        $wrapped = [];

        foreach (array_keys($lazyProperties) as $propertyName) {
            if (array_key_exists($propertyName, $data)) {
                // Wrap value in Lazy wrapper
                $value = $data[$propertyName];
                $wrapped[$propertyName] = LazyWrapper::value($value);

                // Remove from original data
                unset($data[$propertyName]);
            }
        }

        // Merge wrapped lazy properties with remaining data
        return array_merge($data, $wrapped);
    }

    /**
     * Unwrap lazy properties for serialization.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed>
     */
    private function unwrapLazyProperties(array $data): array
    {
        $unwrapped = [];

        foreach ($data as $key => $value) {
            if ($value instanceof LazyWrapper) {
                // Get the value from Lazy wrapper
                $unwrapped[$key] = $value->get();
            } else {
                $unwrapped[$key] = $value;
            }
        }

        return $unwrapped;
    }
}

