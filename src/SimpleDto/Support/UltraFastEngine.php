<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

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
     * Create DTO instance using ultra-fast mode.
     *
     * This method mimics OtherDto's approach:
     * 1. Direct reflection (no cache)
     * 2. Map constructor parameters
     * 3. Process only allowed attributes (#[MapFrom], #[MapTo], #[CastWith])
     * 4. Direct constructor call
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

        // Step 1: Check for #[MapFrom] attribute (if allowed)
        $mappedName = $name;
        if ($ultraFast->allowMapFrom) {
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

        // Apply #[MapTo] if allowed
        if ($ultraFast->allowMapTo) {
            $result = [];
            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();
                if (!array_key_exists($name, $data)) {
                    continue;
                }

                // Check for #[MapTo] attribute
                $mapToAttrs = $reflectionProperty->getAttributes(MapTo::class);
                if (!empty($mapToAttrs)) {
                    /** @var MapTo $mapTo */
                    $mapTo = $mapToAttrs[0]->newInstance();
                    $outputName = $mapTo->target;
                } else {
                    $outputName = $name;
                }

                $result[$outputName] = self::convertValue($data[$name]);
            }
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

    /** Clear all caches (for testing). */
    public static function clearCache(): void
    {
        self::$ultraFastCache = [];
        self::$reflectionCache = [];
    }
}
