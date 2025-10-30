<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use event4u\DataHelpers\Converters\JsonConverter;
use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\SimpleDto\Attributes\ConverterMode;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Attributes\UltraFast;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Ultra-Fast Engine for SimpleDto
 *
 * Provides OtherDto-like performance by bypassing all SimpleDto overhead.
 * Target: <1μs per operation.
 *
 * This engine:
 * - Uses direct reflection (no cache)
 * - Processes only explicitly defined attributes
 * - Skips all pipeline steps
 * - Skips validation, casts, lazy/optional wrapping
 * - Direct constructor call
 *
 * Inspired by OtherDto's minimalist approach.
 */
final class UltraFastEngine
{
    /**
     * Cache for UltraFast attribute per class.
     *
     * @var array<class-string, UltraFast|null>
     */
    private static array $ultraFastCache = [];

    /**
     * Cache for reflection classes.
     *
     * @var array<class-string, ReflectionClass<object>>
     */
    private static array $reflectionCache = [];

    /**
     * Cache for ConverterMode attribute per class.
     *
     * @var array<class-string, bool>
     */
    private static array $converterModeCache = [];

    /**
     * Check if a class has #[UltraFast] attribute.
     *
     * @param class-string $class
     */
    public static function isUltraFast(string $class): bool
    {
        if (!isset(self::$ultraFastCache[$class])) {
            $reflection = self::getReflection($class);
            $attributes = $reflection->getAttributes(UltraFast::class);
            self::$ultraFastCache[$class] = [] === $attributes ? null : $attributes[0]->newInstance();
        }

        return null !== self::$ultraFastCache[$class];
    }

    /**
     * Get UltraFast attribute for a class.
     *
     * @param class-string $class
     */
    public static function getUltraFastAttribute(string $class): ?UltraFast
    {
        if (!isset(self::$ultraFastCache[$class])) {
            self::isUltraFast($class);
        }

        return self::$ultraFastCache[$class];
    }

    /**
     * Create DTO instance using ultra-fast mode from array.
     *
     * This method only accepts arrays for maximum performance (~0.8μs).
     * For other formats, use createFrom(), createFromJson(), etc.
     *
     * @param class-string $class
     * @param array<string, mixed> $data
     */
    public static function createFromArray(string $class, array $data): object
    {
        $reflection = self::getReflection($class);
        $ultraFast = self::getUltraFastAttribute($class);

        if (!$ultraFast instanceof UltraFast) {
            throw new InvalidArgumentException(sprintf('Class %s does not have #[UltraFast] attribute', $class));
        }

        // Get constructor parameters
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $args[] = self::resolveParameter($param, $data, $ultraFast, $reflection);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Create DTO instance using ultra-fast mode from mixed data.
     *
     * Supports arrays, JSON, XML, YAML with auto-detection (~1.3-1.5μs).
     * Requires #[ConverterMode] attribute for non-array data.
     *
     * @param class-string $class
     * @param array<string, mixed>|string|object $data
     */
    public static function createFrom(string $class, mixed $data): object
    {
        // Fast path: array
        if (is_array($data)) {
            return self::createFromArray($class, $data);
        }

        // Check if ConverterMode is enabled
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                "UltraFast mode only accepts arrays. Use #[ConverterMode] attribute on {$class} to enable JSON/XML/YAML support."
            );
        }

        // Parse with auto-detection
        $data = self::parseWithConverter($data);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from JSON string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromJson(string $class, string $json): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                "JSON parsing requires #[ConverterMode] attribute on {$class}."
            );
        }

        $data = (new JsonConverter())->toArray($json);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from XML string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromXml(string $class, string $xml): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                "XML parsing requires #[ConverterMode] attribute on {$class}."
            );
        }

        $data = (new XmlConverter())->toArray($xml);
        return self::createFromArray($class, $data);
    }

    /**
     * Create DTO instance from YAML string (no format detection, ~1.2μs).
     *
     * Requires #[ConverterMode] attribute.
     *
     * @param class-string $class
     */
    public static function createFromYaml(string $class, string $yaml): object
    {
        if (!self::hasConverterMode($class)) {
            throw new InvalidArgumentException(
                "YAML parsing requires #[ConverterMode] attribute on {$class}."
            );
        }

        $data = (new YamlConverter())->toArray($yaml);
        return self::createFromArray($class, $data);
    }

    /**
     * Resolve a constructor parameter value.
     *
     * @param array<string, mixed> $data
     * @param ReflectionClass<object> $reflection
     */
    private static function resolveParameter(
        ReflectionParameter $param,
        array $data,
        UltraFast $ultraFast,
        ReflectionClass $reflection
    ): mixed {
        $name = $param->getName();

        // Step 1: Check for #[MapFrom] attribute (automatically detected)
        $mappedName = $name;
        // First check parameter attributes (for constructor promoted properties)
        $mapFromAttrs = $param->getAttributes(MapFrom::class);
        if ([] !== $mapFromAttrs) {
            /** @var MapFrom $mapFrom */
            $mapFrom = $mapFromAttrs[0]->newInstance();
            $mappedName = $mapFrom->source;
        } else {
            // Fallback to property attributes
            $property = $reflection->hasProperty($name) ? $reflection->getProperty($name) : null;
            if ($property) {
                $mapFromAttrs = $property->getAttributes(MapFrom::class);
                if (!empty($mapFromAttrs)) {
                    /** @var MapFrom $mapFrom */
                    $mapFrom = $mapFromAttrs[0]->newInstance();
                    $mappedName = $mapFrom->source;
                }
            }
        }

        // Step 2: Get value from data (with nested path support)
        $value = null;
        $found = false;

        // Handle array of sources (fallback)
        if (is_array($mappedName)) {
            foreach ($mappedName as $source) {
                if (str_contains($source, '.')) {
                    $parts = explode('.', $source);
                    $current = $data;
                    $tempFound = true;

                    foreach ($parts as $part) {
                        if (is_array($current) && array_key_exists($part, $current)) {
                            $current = $current[$part];
                        } else {
                            $tempFound = false;
                            break;
                        }
                    }

                    if ($tempFound) {
                        $value = $current;
                        $found = true;
                        break;
                    }
                } elseif (array_key_exists($source, $data)) {
                    $value = $data[$source];
                    $found = true;
                    break;
                }
            }
        } elseif (str_contains($mappedName, '.')) {
            // Check if mappedName contains dots (nested path)
            $parts = explode('.', $mappedName);
            $current = $data;
            $found = true;

            foreach ($parts as $part) {
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } else {
                    $found = false;
                    break;
                }
            }

            if ($found) {
                $value = $current;
            }
        } elseif (array_key_exists($mappedName, $data)) {
            $value = $data[$mappedName];
            $found = true;
        }

        if (!$found) {
            // Handle default values
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            // Handle nullable
            if ($param->allowsNull()) {
                return null;
            }

            throw new InvalidArgumentException('Missing required parameter: ' . $name);
        }

        // Step 3: Handle nested DTOs (auto-cast)
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            // Check if it's a DTO class (also validates it's a class-string)
            if (class_exists($typeName) && self::isDtoClass($typeName) && is_array($value)) {
                /** @var class-string $typeName */
                /** @var array<string, mixed> $value */
                // Recursively create nested DTO
                if (self::isUltraFast($typeName)) {
                    return self::createFromArray($typeName, $value);
                }
                // Fall back to normal fromArray for non-UltraFast DTOs
                return $typeName::fromArray($value);
            }
        }

        // Step 4: Handle #[CastWith] attribute (if allowed)
        // TODO: Implement if needed

        return $value;
    }

    /**
     * Convert DTO to array using ultra-fast mode.
     *
     * @return array<string, mixed>
     */
    public static function toArray(object $dto): array
    {
        $class = $dto::class;
        $reflection = self::getReflection($class);
        $ultraFast = self::getUltraFastAttribute($class);

        if (!$ultraFast instanceof UltraFast) {
            throw new InvalidArgumentException(sprintf('Class %s does not have #[UltraFast] attribute', $class));
        }

        // Get all public properties
        $data = get_object_vars($dto);

        // Check if any property has #[MapTo] attribute
        $hasMapTo = false;
        $result = [];
        foreach ($reflection->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            if (!array_key_exists($name, $data)) {
                continue;
            }

            // Check for #[MapTo] attribute (automatically detected)
            $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
            if (!empty($mapToAttrs)) {
                $hasMapTo = true;
                /** @var MapTo $mapTo */
                $mapTo = $mapToAttrs[0]->newInstance();
                $outputName = $mapTo->target;
            } else {
                $outputName = $name;
            }

            $result[$outputName] = self::convertValue($data[$name]);
        }

        if ($hasMapTo) {
            return $result;
        }

        // No mapping - direct conversion
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = self::convertValue($value);
        }

        return $result;
    }

    /** Convert a value recursively (handle nested DTOs). */
    private static function convertValue(mixed $value): mixed
    {
        // Handle arrays
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::convertValue($item);
            }
            return $result;
        }

        // Handle DTOs
        if (is_object($value) && method_exists($value, 'toArray')) {
            return self::convertValue($value->toArray());
        }

        return $value;
    }

    /**
     * Check if a class is a DTO class.
     *
     * @param class-string $class
     */
    private static function isDtoClass(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        return method_exists($class, 'fromArray');
    }

    /**
     * Get reflection class (cached).
     *
     * @param class-string $class
     * @return ReflectionClass<object>
     */
    private static function getReflection(string $class): ReflectionClass
    {
        if (!isset(self::$reflectionCache[$class])) {
            /** @var ReflectionClass<object> $reflection */
            $reflection = new ReflectionClass($class);
            self::$reflectionCache[$class] = $reflection;
        }

        return self::$reflectionCache[$class];
    }

    /**
     * Check if class has ConverterMode attribute.
     *
     * @param class-string $class
     */
    private static function hasConverterMode(string $class): bool
    {
        if (isset(self::$converterModeCache[$class])) {
            return self::$converterModeCache[$class];
        }

        $reflection = self::getReflection($class);
        $attrs = $reflection->getAttributes(ConverterMode::class);
        $hasMode = [] !== $attrs;

        self::$converterModeCache[$class] = $hasMode;
        return $hasMode;
    }

    /**
     * Parse data with converter (JSON, XML, YAML, etc.).
     *
     * @return array<string, mixed>
     */
    private static function parseWithConverter(mixed $data): array
    {
        // Handle objects
        if (is_object($data)) {
            return (array)$data;
        }

        // Handle strings (JSON, XML, YAML, CSV)
        if (is_string($data)) {
            $trimmed = trim($data);

            // Try JSON first (most common)
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                try {
                    return (new JsonConverter())->toArray($data);
                } catch (\Throwable) {
                    // Fall through to XML
                }
            }

            // Try XML
            if (str_starts_with($trimmed, '<')) {
                try {
                    return (new XmlConverter())->toArray($data);
                } catch (\Throwable) {
                    throw new InvalidArgumentException('Invalid XML format');
                }
            }

            // Try YAML (fallback for other string formats)
            try {
                return (new YamlConverter())->toArray($data);
            } catch (\Throwable) {
                throw new InvalidArgumentException('Unsupported string format. Expected JSON, XML, or YAML.');
            }
        }

        throw new InvalidArgumentException('Data must be array, string (JSON/XML/YAML), or object');
    }

    /** Clear all caches (for testing). */
    public static function clearCache(): void
    {
        self::$ultraFastCache = [];
        self::$reflectionCache = [];
        self::$converterModeCache = [];
    }
}
