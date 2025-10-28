<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\Support\ReflectionCache;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Trait for performance optimizations in SimpleDtos.
 *
 * This trait provides caching mechanisms to improve performance:
 * - Property metadata cache
 * - Constructor parameter cache
 * - Attribute metadata cache
 * - Object vars cache
 */
trait SimpleDtoPerformanceTrait
{
    /**
     * Cache for constructor parameters per class.
     *
     * @var array<class-string, array<string, array{name: string, type: ?string, hasDefault: bool, defaultValue: mixed}>>
     */
    private static array $constructorParamsCache = [];

    /**
     * Cache for property metadata per class.
     *
     * @var array<class-string, array<string, array{type: ?string, isNullable: bool, hasDefault: bool, defaultValue: mixed}>>
     */
    private static array $propertyMetadataCache = [];

    /**
     * Cache for attribute metadata per class and property.
     *
     * @deprecated Phase 8: Use ReflectionCache::getPropertyAttributes() instead
     * @var array<class-string, array<string, array<string, object>>>
     */
    private static array $attributeMetadataCache = [];

    /**
     * Cache for object vars per instance (for toArray optimization).
     *
     * @var array<string, mixed>|null
     */
    private ?array $objectVarsCache = null;

    /**
     * Get cached constructor parameters for a class.
     *
     * @return array<string, array{name: string, type: ?string, hasDefault: bool, defaultValue: mixed}>
     */
    public static function getCachedConstructorParams(): array
    {
        $class = static::class;

        if (isset(self::$constructorParamsCache[$class])) {
            return self::$constructorParamsCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            self::$constructorParamsCache[$class] = [];

            return [];
        }

        $params = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $type = $reflectionParameter->getType();
            $typeName = null;

            if (null !== $type) {
                $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string)$type;
            }

            $params[$reflectionParameter->getName()] = [
                'name' => $reflectionParameter->getName(),
                'type' => $typeName,
                'hasDefault' => $reflectionParameter->isDefaultValueAvailable(),
                'defaultValue' => $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null,
            ];
        }

        self::$constructorParamsCache[$class] = $params;

        return $params;
    }

    /**
     * Get cached property metadata for a class.
     *
     * @return array<string, array{type: ?string, isNullable: bool, hasDefault: bool, defaultValue: mixed}>
     */
    public static function getCachedPropertyMetadata(): array
    {
        $class = static::class;

        if (isset(self::$propertyMetadataCache[$class])) {
            return self::$propertyMetadataCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $type = $reflectionProperty->getType();
            $typeName = null;
            $isNullable = false;

            if (null !== $type) {
                $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string)$type;
                $isNullable = $type->allowsNull();
            }

            $properties[$reflectionProperty->getName()] = [
                'type' => $typeName,
                'isNullable' => $isNullable,
                'hasDefault' => $reflectionProperty->hasDefaultValue(),
                'defaultValue' => $reflectionProperty->hasDefaultValue() ? $reflectionProperty->getDefaultValue() : null,
            ];
        }

        self::$propertyMetadataCache[$class] = $properties;

        return $properties;
    }

    /**
     * Get cached attributes for a property.
     *
     * Phase 8: Now uses ReflectionCache instead of duplicate cache
     *
     * @return array<string, object>
     */
    public static function getCachedPropertyAttributes(string $propertyName): array
    {
        $class = static::class;

        // Phase 8: Use ReflectionCache instead of duplicate cache
        return ReflectionCache::getPropertyAttributes($class, $propertyName);
    }

    /**
     * Get cached object vars (for toArray optimization).
     *
     * @return array<string, mixed>
     */
    private function getCachedObjectVars(): array
    {
        if (null !== $this->objectVarsCache) {
            return $this->objectVarsCache;
        }

        $this->objectVarsCache = get_object_vars($this);

        return $this->objectVarsCache;
    }

    /**
     * Invalidate object vars cache.
     *
     * Call this if the object state changes.
     */
    private function invalidateObjectVarsCache(): void
    {
        $this->objectVarsCache = null;
    }

    /**
     * Clear all performance caches.
     *
     * Phase 8: Now also clears ReflectionCache
     * Phase 11a: Now also clears ConstructorMetadata (including persistent cache)
     *
     * Useful for testing or when dealing with dynamic class loading.
     */
    public static function clearPerformanceCache(): void
    {
        self::$constructorParamsCache = [];
        self::$propertyMetadataCache = [];
        self::$attributeMetadataCache = [];

        // Phase 8: Also clear ReflectionCache
        ReflectionCache::clear();

        // Phase 11a: Also clear ConstructorMetadata (including persistent cache)
        \event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata::clear();
    }

    /**
     * Get cache statistics for debugging.
     *
     * Phase 8: Now includes ReflectionCache stats for attribute metadata
     *
     * @return array{
     *     constructorParams: int,
     *     propertyMetadata: int,
     *     attributeMetadata: int,
     *     totalMemory: int
     * }
     */
    public static function getPerformanceCacheStats(): array
    {
        $constructorParamsCount = count(self::$constructorParamsCache);
        $propertyMetadataCount = count(self::$propertyMetadataCache);

        // Phase 8: Get attribute metadata count from ReflectionCache
        $reflectionStats = ReflectionCache::getStats();
        $attributeMetadataCount = $reflectionStats['propertyAttributes'];

        // Estimate memory usage (rough approximation)
        $memory = strlen(serialize(self::$constructorParamsCache))
            + strlen(serialize(self::$propertyMetadataCache))
            + strlen(serialize(self::$attributeMetadataCache)); // Keep for backward compatibility

        return [
            'constructorParams' => $constructorParamsCount,
            'propertyMetadata' => $propertyMetadataCount,
            'attributeMetadata' => $attributeMetadataCount,
            'totalMemory' => $memory,
        ];
    }

    /**
     * Warm up the cache for a class.
     *
     * Pre-loads all metadata into cache to avoid lazy loading overhead.
     */
    public static function warmUpCache(): void
    {
        static::getCachedConstructorParams();
        static::getCachedPropertyMetadata();

        // Warm up attribute cache for all properties
        $properties = static::getCachedPropertyMetadata();
        foreach (array_keys($properties) as $propertyName) {
            static::getCachedPropertyAttributes($propertyName);
        }
    }
}
