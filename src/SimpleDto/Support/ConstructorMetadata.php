<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/**
 * Centralized metadata cache for constructor parameters and their attributes.
 *
 * This class scans constructor parameters ONCE and caches all metadata including:
 * - Parameter types
 * - All attributes (for casts, mapping, validation, visibility, etc.)
 * - Nested DTO detection
 *
 * This eliminates redundant reflection calls across different traits.
 */
final class ConstructorMetadata
{
    /**
     * Cache for constructor metadata per class.
     *
     * Phase 6 Optimization #3: LRU Cache with size limit to prevent memory leaks
     *
     * @var array<string, array{
     *     parameters: array<string, array{
     *         name: string,
     *         type: ?string,
     *         isBuiltin: bool,
     *         allowsNull: bool,
     *         attributes: array<string, object>
     *     }>,
     *     classAttributes: array<string, object>
     * }>
     */
    private static array $cache = [];

    /** Maximum cache size before cleanup (Phase 6 Optimization #3) */
    private const MAX_CACHE_SIZE = 500;

    /**
     * Get metadata for a class.
     *
     * @param class-string $class
     * @return array{
     *     parameters: array<string, array{
     *         name: string,
     *         type: ?string,
     *         isBuiltin: bool,
     *         allowsNull: bool,
     *         attributes: array<string, object>
     *     }>,
     *     classAttributes: array<string, object>
     * }
     */
    public static function get(string $class): array
    {
        if (isset(self::$cache[$class])) {
            return self::$cache[$class];
        }

        // Phase 6 Optimization #3: LRU Cache cleanup when size limit reached
        if (count(self::$cache) >= self::MAX_CACHE_SIZE) {
            // Remove oldest 20% of entries (simple LRU approximation)
            $removeCount = (int) (self::MAX_CACHE_SIZE * 0.2);
            self::$cache = array_slice(self::$cache, $removeCount, null, true);
        }

        try {
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();

            $metadata = [
                'parameters' => [],
                'classAttributes' => self::extractClassAttributes($reflection),
            ];

            if (null !== $constructor) {
                foreach ($constructor->getParameters() as $reflectionParameter) {
                    $metadata['parameters'][$reflectionParameter->getName()] = self::extractParameterMetadata($reflectionParameter);
                }
            }

            self::$cache[$class] = $metadata;

            return $metadata;
        } catch (Throwable) {
            // Return empty metadata on error
            $emptyMetadata = [
                'parameters' => [],
                'classAttributes' => [],
            ];
            self::$cache[$class] = $emptyMetadata;

            return $emptyMetadata;
        }
    }

    /**
     * Extract class-level attributes.
     *
     * @return array<string, object>
     */
    private static function extractClassAttributes(ReflectionClass $reflection): array
    {
        $attributes = [];

        foreach ($reflection->getAttributes() as $attribute) {
            try {
                $instance = $attribute->newInstance();
                $attributes[$attribute->getName()] = $instance;
            } catch (Throwable) {
                // Skip attributes that can't be instantiated
            }
        }

        return $attributes;
    }

    /**
     * Extract parameter metadata including type and all attributes.
     *
     * @return array{
     *     name: string,
     *     type: ?string,
     *     isBuiltin: bool,
     *     allowsNull: bool,
     *     attributes: array<string, object>
     * }
     */
    private static function extractParameterMetadata(ReflectionParameter $param): array
    {
        $type = $param->getType();
        $typeName = null;
        $isBuiltin = false;
        $allowsNull = true;

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            $isBuiltin = $type->isBuiltin();
            $allowsNull = $type->allowsNull();
        }

        $attributes = [];
        foreach ($param->getAttributes() as $attribute) {
            try {
                $instance = $attribute->newInstance();
                $attributes[$attribute->getName()] = $instance;
            } catch (Throwable) {
                // Skip attributes that can't be instantiated
            }
        }

        return [
            'name' => $param->getName(),
            'type' => $typeName,
            'isBuiltin' => $isBuiltin,
            'allowsNull' => $allowsNull,
            'attributes' => $attributes,
        ];
    }

    /** Check if a class has a specific class-level attribute. */
    public static function hasClassAttribute(string $class, string $attributeClass): bool
    {
        $metadata = self::get($class);

        return isset($metadata['classAttributes'][$attributeClass]);
    }

    /** Get a specific class-level attribute instance. */
    public static function getClassAttribute(string $class, string $attributeClass): ?object
    {
        $metadata = self::get($class);

        return $metadata['classAttributes'][$attributeClass] ?? null;
    }

    /** Check if a parameter has a specific attribute. */
    public static function hasParameterAttribute(string $class, string $paramName, string $attributeClass): bool
    {
        $metadata = self::get($class);

        return isset($metadata['parameters'][$paramName]['attributes'][$attributeClass]);
    }

    /** Get a specific parameter attribute instance. */
    public static function getParameterAttribute(string $class, string $paramName, string $attributeClass): ?object
    {
        $metadata = self::get($class);

        return $metadata['parameters'][$paramName]['attributes'][$attributeClass] ?? null;
    }

    /**
     * Get all parameters with their metadata.
     *
     * @return array<string, array{
     *     name: string,
     *     type: ?string,
     *     isBuiltin: bool,
     *     allowsNull: bool,
     *     attributes: array<string, object>
     * }>
     */
    public static function getParameters(string $class): array
    {
        $metadata = self::get($class);

        return $metadata['parameters'];
    }

    /** Get parameter type. */
    public static function getParameterType(string $class, string $paramName): ?string
    {
        $metadata = self::get($class);

        return $metadata['parameters'][$paramName]['type'] ?? null;
    }

    /** Check if parameter type is builtin. */
    public static function isParameterBuiltin(string $class, string $paramName): bool
    {
        $metadata = self::get($class);

        return $metadata['parameters'][$paramName]['isBuiltin'] ?? false;
    }

    /** Check if parameter allows null. */
    public static function parameterAllowsNull(string $class, string $paramName): bool
    {
        $metadata = self::get($class);

        return $metadata['parameters'][$paramName]['allowsNull'] ?? true;
    }

    /** Clear the cache (useful for testing). */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /** Clear cache for a specific class. */
    public static function clearClass(string $class): void
    {
        unset(self::$cache[$class]);
    }
}
