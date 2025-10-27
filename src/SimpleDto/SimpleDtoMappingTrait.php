<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Support\NameTransformer;
use ReflectionClass;

/**
 * Trait for handling property mapping in SimpleDtos.
 *
 * This trait provides functionality to map input/output data from/to different key names
 * using the #[MapFrom] and #[MapTo] attributes.
 *
 * Features:
 * - Simple key mapping (input & output)
 * - Dot notation for nested data
 * - Multiple sources with fallback (input only)
 * - Mapping configuration caching
 * - Bidirectional mapping support
 */
trait SimpleDtoMappingTrait
{
    /**
     * Cache for ReflectionClass instances per Dto class.
     *
     * @var array<string, ReflectionClass>
     */
    private static array $reflectionCache = [];

    /**
     * Cache for input mapping configurations per Dto class.
     *
     * @var array<string, array<string, string|array<string>>>
     */
    private static array $mappingCache = [];

    /**
     * Cache for output mapping configurations per Dto class.
     *
     * @var array<string, array<string, string>>
     */
    private static array $outputMappingCache = [];

    /**
     * Cache for input name transformation format per Dto class.
     *
     * @var array<string, string|null>
     */
    private static array $inputNameTransformCache = [];

    /**
     * Cache for output name transformation format per Dto class.
     *
     * @var array<string, string|null>
     */
    private static array $outputNameTransformCache = [];

    /**
     * Get cached ReflectionClass instance for the current class.
     *
     * @return ReflectionClass
     */
    private static function getReflection(): ReflectionClass
    {
        $class = static::class;

        if (!isset(self::$reflectionCache[$class])) {
            self::$reflectionCache[$class] = new ReflectionClass($class);
        }

        return self::$reflectionCache[$class];
    }

    /**
     * Get the mapping configuration for this Dto.
     *
     * Returns an array where keys are property names and values are source keys.
     * The source can be a string or an array of strings (for fallback).
     *
     * @return array<string, string|array<string>>
     */
    public static function getMappingConfig(): array
    {
        $class = static::class;

        if (isset(self::$mappingCache[$class])) {
            return self::$mappingCache[$class];
        }

        $reflection = self::getReflection();
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            self::$mappingCache[$class] = [];

            return [];
        }

        $mapping = [];

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $mapFromAttributes = $reflectionParameter->getAttributes(MapFrom::class);

            if (!empty($mapFromAttributes)) {
                $mapFrom = $mapFromAttributes[0]->newInstance();
                $mapping[$reflectionParameter->getName()] = $mapFrom->source;
            }
        }

        self::$mappingCache[$class] = $mapping;

        return $mapping;
    }

    /**
     * Get the input name transformation format for this Dto.
     *
     * Returns the format specified by #[MapInputName] attribute, or null if not set.
     *
     * @return string|null The transformation format or null
     */
    public static function getInputNameTransformation(): ?string
    {
        $class = static::class;

        if (isset(self::$inputNameTransformCache[$class])) {
            return self::$inputNameTransformCache[$class];
        }

        $reflection = self::getReflection();
        $attributes = $reflection->getAttributes(MapInputName::class);

        if ([] === $attributes) {
            self::$inputNameTransformCache[$class] = null;

            return null;
        }

        $mapInputName = $attributes[0]->newInstance();
        self::$inputNameTransformCache[$class] = $mapInputName->getFormat();

        return $mapInputName->getFormat();
    }

    /**
     * Apply mapping to input data.
     *
     * Maps input data keys to Dto property names based on #[MapFrom] attributes
     * and #[MapInputName] class-level transformation.
     *
     * Processing order:
     * 1. Apply #[MapFrom] property-level mappings (highest priority)
     * 2. Apply #[MapInputName] class-level transformation for unmapped properties
     * 3. Keep original keys for properties without mapping or transformation
     *
     * @param array<string, mixed> $data Input data
     *
     * @return array<string, mixed> Mapped data
     */
    protected static function applyMapping(array $data): array
    {
        $mapping = static::getMappingConfig();
        $inputTransform = static::getInputNameTransformation();

        $mappedData = [];

        // Get all constructor parameters
        $reflection = self::getReflection();
        $constructor = $reflection->getConstructor();

        if (null !== $constructor) {
            foreach ($constructor->getParameters() as $reflectionParameter) {
                $propertyName = $reflectionParameter->getName();

                // Step 1: Check if property has #[MapFrom] (highest priority)
                if (isset($mapping[$propertyName])) {
                    $sources = is_array($mapping[$propertyName]) ? $mapping[$propertyName] : [$mapping[$propertyName]];

                    // Try each source until we find a value
                    foreach ($sources as $sourceKey) {
                        $value = static::getValueFromSource($data, $sourceKey);

                        if (null !== $value) {
                            $mappedData[$propertyName] = $value;

                            break;
                        }
                    }

                    continue;
                }

                // Step 2: Check if class has #[MapInputName] transformation
                if (null !== $inputTransform) {
                    $transformedKey = NameTransformer::transform($propertyName, $inputTransform);

                    if (array_key_exists($transformedKey, $data)) {
                        $mappedData[$propertyName] = $data[$transformedKey];

                        continue;
                    }
                }

                // Step 3: Use original key if exists
                if (array_key_exists($propertyName, $data)) {
                    $mappedData[$propertyName] = $data[$propertyName];
                }
            }
        }

        return $mappedData;
    }

    /**
     * Get value from data using a source key.
     *
     * Supports dot notation for nested data access.
     *
     * @param array<string, mixed> $data       Input data
     * @param string               $sourceKey  Source key (supports dot notation)
     *
     * @return mixed|null The value or null if not found
     */
    protected static function getValueFromSource(array $data, string $sourceKey): mixed
    {
        // Check if it's a dot notation key
        if (!str_contains($sourceKey, '.')) {
            return $data[$sourceKey] ?? null;
        }

        // Handle dot notation
        $keys = explode('.', $sourceKey);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Get the output mapping configuration for this Dto.
     *
     * Returns an array where keys are property names and values are target keys.
     *
     * @return array<string, string>
     */
    public static function getOutputMappingConfig(): array
    {
        $class = static::class;

        if (isset(self::$outputMappingCache[$class])) {
            return self::$outputMappingCache[$class];
        }

        $reflection = self::getReflection();
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            self::$outputMappingCache[$class] = [];

            return [];
        }

        $mapping = [];

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $mapToAttributes = $reflectionParameter->getAttributes(MapTo::class);

            if (!empty($mapToAttributes)) {
                $mapTo = $mapToAttributes[0]->newInstance();
                $mapping[$reflectionParameter->getName()] = $mapTo->target;
            }
        }

        self::$outputMappingCache[$class] = $mapping;

        return $mapping;
    }

    /**
     * Get the output name transformation format for this Dto.
     *
     * Returns the format specified by #[MapOutputName] attribute, or null if not set.
     *
     * @return string|null The transformation format or null
     */
    public static function getOutputNameTransformation(): ?string
    {
        $class = static::class;

        if (isset(self::$outputNameTransformCache[$class])) {
            return self::$outputNameTransformCache[$class];
        }

        $reflection = self::getReflection();
        $attributes = $reflection->getAttributes(MapOutputName::class);

        if ([] === $attributes) {
            self::$outputNameTransformCache[$class] = null;

            return null;
        }

        $mapOutputName = $attributes[0]->newInstance();
        self::$outputNameTransformCache[$class] = $mapOutputName->getFormat();

        return $mapOutputName->getFormat();
    }

    /**
     * Apply output mapping to data.
     *
     * Maps Dto property names to output keys based on #[MapTo] attributes
     * and #[MapOutputName] class-level transformation.
     *
     * Processing order:
     * 1. Apply #[MapTo] property-level mappings (highest priority)
     * 2. Apply #[MapOutputName] class-level transformation for unmapped properties
     * 3. Keep original keys for properties without mapping or transformation
     *
     * @param array<string, mixed> $data Property data
     *
     * @return array<string, mixed> Mapped output data
     */
    protected function applyOutputMapping(array $data): array
    {
        $mapping = static::getOutputMappingConfig();
        $outputTransform = static::getOutputNameTransformation();

        $result = [];

        foreach ($data as $propertyName => $value) {
            // Step 1: Check if property has #[MapTo] (highest priority)
            if (isset($mapping[$propertyName])) {
                $targetKey = $mapping[$propertyName];

                // Check if it's a dot notation key
                if (str_contains($targetKey, '.')) {
                    // Build nested structure
                    static::setNestedValue($result, $targetKey, $value);
                } else {
                    // Simple key mapping
                    $result[$targetKey] = $value;
                }

                continue;
            }

            // Step 2: Check if class has #[MapOutputName] transformation
            if (null !== $outputTransform) {
                $transformedKey = NameTransformer::transform($propertyName, $outputTransform);
                $result[$transformedKey] = $value;

                continue;
            }

            // Step 3: Keep original key
            $result[$propertyName] = $value;
        }

        return $result;
    }

    /**
     * Set a value in a nested array using dot notation.
     *
     * @param array<string, mixed> $array  The array to modify (by reference)
     * @param string               $path   Dot notation path (e.g., 'user.profile.email')
     * @param mixed                $value  The value to set
     *
     * @phpstan-ignore ergebnis.noParameterPassedByReference (Necessary for nested array modification)
     */
    protected static function setNestedValue(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$array; // @phpstan-ignore ergebnis.noAssignByReference

        foreach ($keys as $i => $key) {
            if (count($keys) - 1 === $i) {
                // Last key, set the value
                $current[$key] = $value;
            } else {
                // Create nested array if it doesn't exist
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }

                $current = &$current[$key]; // @phpstan-ignore ergebnis.noAssignByReference
            }
        }
    }

    /**
     * Clear the mapping cache.
     *
     * Useful for testing or when Dto classes are modified at runtime.
     */
    public static function clearMappingCache(): void
    {
        self::$mappingCache = [];
        self::$outputMappingCache = [];
        self::$inputNameTransformCache = [];
        self::$outputNameTransformCache = [];
    }
}
