<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Support;

use event4u\DataHelpers\Support\Cache\CacheInvalidator;
use event4u\DataHelpers\Support\Cache\CacheManager;
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
 * Phase 11a: Now uses persistent cache with automatic invalidation.
 * - In-memory cache for fast access within request
 * - Persistent cache (Laravel/Symfony/Filesystem) shared between workers
 * - Automatic invalidation when source files change
 *
 * This eliminates redundant reflection calls across different traits.
 */
final class ConstructorMetadata
{
    /**
     * In-memory cache for constructor metadata per class (per-request).
     *
     * Phase 6 Optimization #3: LRU Cache with size limit to prevent memory leaks
     * Phase 11a: Now backed by persistent cache with automatic invalidation
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

    /** Cache whether persistent cache is enabled (checked once) */
    private static ?bool $persistentCacheEnabled = null;

    /**
     * Get metadata for a class.
     *
     * Phase 11a: Now uses persistent cache with automatic invalidation.
     * - First checks in-memory cache (fast)
     * - Then checks persistent cache (shared between workers)
     * - Falls back to reflection if cache is invalid or missing
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
        // Check in-memory cache first (fastest)
        if (isset(self::$cache[$class])) {
            return self::$cache[$class];
        }

        // Phase 6 Optimization #3: LRU Cache cleanup when size limit reached
        if (count(self::$cache) >= self::MAX_CACHE_SIZE) {
            // Remove oldest 20% of entries (simple LRU approximation)
            $removeCount = (int) (self::MAX_CACHE_SIZE * 0.2);
            self::$cache = array_slice(self::$cache, $removeCount, null, true);
        }

        // Phase 11a: Try persistent cache with automatic invalidation
        // Only check persistent cache if it's enabled (not NONE)
        // Cache the enabled check to avoid calling it twice
        $persistentCacheEnabled = self::isPersistentCacheEnabled();

        $metadata = null;
        if ($persistentCacheEnabled) {
            $metadata = self::getFromPersistentCache($class);
        }

        if (null !== $metadata) {
            // Cache hit - store in memory and return
            self::$cache[$class] = $metadata;

            return $metadata;
        }

        // Cache miss - generate metadata via reflection
        $metadata = self::generateMetadata($class);

        // Store in both in-memory and persistent cache
        self::$cache[$class] = $metadata;

        if ($persistentCacheEnabled) {
            self::storeToPersistentCache($class, $metadata);
        }

        return $metadata;
    }

    /**
     * Generate metadata via reflection.
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
    private static function generateMetadata(string $class): array
    {
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

            return $metadata;
        } catch (Throwable) {
            // Return empty metadata on error
            return [
                'parameters' => [],
                'classAttributes' => [],
            ];
        }
    }

    /**
     * Check if persistent cache is enabled.
     *
     * Phase 11a Performance: Cache the result to avoid repeated config lookups.
     *
     * @return bool
     */
    private static function isPersistentCacheEnabled(): bool
    {
        if (null === self::$persistentCacheEnabled) {
            try {
                $config = \event4u\DataHelpers\Helpers\ConfigHelper::getInstance();
                $driver = $config->get('cache.driver', \event4u\DataHelpers\Enums\CacheDriver::AUTO);

                // Convert string to enum if needed
                if (is_string($driver)) {
                    $driver = \event4u\DataHelpers\Enums\CacheDriver::from($driver);
                }

                // Cache is disabled if driver is NONE
                self::$persistentCacheEnabled = \event4u\DataHelpers\Enums\CacheDriver::NONE !== $driver;
            } catch (\Throwable) {
                // If config fails, assume cache is enabled (safe default)
                self::$persistentCacheEnabled = true;
            }
        }

        return self::$persistentCacheEnabled;
    }

    /**
     * Get metadata from persistent cache with optional validation.
     *
     * Phase 11a Performance: Two modes based on cache.invalidation config:
     *
     * MANUAL mode (like Spatie Laravel Data):
     * - Direct cache lookup without validation overhead
     * - Cache is validated only during cache warming (bin/warm-cache.php)
     * - At runtime, we trust the cache completely
     * - Best performance, but requires manual cache clearing after code changes
     *
     * MTIME/HASH/BOTH modes:
     * - Automatic validation on every cache hit
     * - Cache is invalidated when source files change
     * - Slightly slower, but no manual cache clearing needed
     * - Good for development
     *
     * Performance Note: This method is only called on in-memory cache MISS.
     * After the first call, the result is stored in the in-memory cache,
     * so subsequent calls for the same class will hit the in-memory cache
     * and never reach this method.
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
     * }|null
     */
    private static function getFromPersistentCache(string $class): ?array
    {
        try {
            $cacheKey = 'dto_metadata_' . str_replace('\\', '_', $class);

            // Get cached data
            $serialized = CacheManager::get($cacheKey);

            if (null === $serialized) {
                // Cache miss - will trigger reflection-based generation
                return null;
            }

            // Check if validation is required based on cache.invalidation config
            if (self::shouldValidateCache()) {
                // MTIME/HASH/BOTH mode: Validate cache on every hit
                if (!self::isCacheValid($class)) {
                    // Cache is invalid - return null to trigger regeneration
                    return null;
                }
            }
            // MANUAL mode: No validation - trust the cache completely (Spatie-style)

            // Deserialize and return
            return unserialize($serialized);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Check if cache validation is required based on config.
     *
     * @return bool
     */
    private static function shouldValidateCache(): bool
    {
        static $shouldValidate = null;

        if (null === $shouldValidate) {
            try {
                $config = \event4u\DataHelpers\Helpers\ConfigHelper::getInstance();
                $invalidation = $config->get('cache.invalidation', \event4u\DataHelpers\Enums\CacheInvalidation::MANUAL);

                // Convert string to enum if needed
                if (is_string($invalidation)) {
                    $invalidation = \event4u\DataHelpers\Enums\CacheInvalidation::from($invalidation);
                }

                // Check if validation is required (MTIME/HASH/BOTH)
                $shouldValidate = $invalidation->requiresValidation();
            } catch (\Throwable) {
                // If config fails, assume MANUAL mode (no validation)
                $shouldValidate = false;
            }
        }

        return $shouldValidate;
    }

    /**
     * Check if cache is valid for a class.
     *
     * @param class-string $class
     * @return bool
     */
    private static function isCacheValid(string $class): bool
    {
        try {
            $config = \event4u\DataHelpers\Helpers\ConfigHelper::getInstance();
            $invalidation = $config->get('cache.invalidation', \event4u\DataHelpers\Enums\CacheInvalidation::MANUAL);

            // Convert string to enum if needed
            if (is_string($invalidation)) {
                $invalidation = \event4u\DataHelpers\Enums\CacheInvalidation::from($invalidation);
            }

            // Use CacheInvalidator to check validity
            return CacheInvalidator::isValid($class, $invalidation);
        } catch (\Throwable) {
            // If validation fails, assume cache is invalid
            return false;
        }
    }

    /**
     * Store metadata to persistent cache.
     *
     * @param class-string $class
     * @param array{
     *     parameters: array<string, array{
     *         name: string,
     *         type: ?string,
     *         isBuiltin: bool,
     *         allowsNull: bool,
     *         attributes: array<string, object>
     *     }>,
     *     classAttributes: array<string, object>
     * } $metadata
     */
    private static function storeToPersistentCache(string $class, array $metadata): void
    {
        try {
            $cacheKey = 'dto_metadata_' . str_replace('\\', '_', $class);

            // Serialize metadata
            $serialized = serialize($metadata);
            CacheManager::set($cacheKey, $serialized);
        } catch (Throwable) {
            // Silently fail - persistent cache is optional
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
        $mapFrom = null;

        foreach ($param->getAttributes() as $attribute) {
            try {
                $instance = $attribute->newInstance();
                $attributes[$attribute->getName()] = $instance;

                // Extract MapFrom attribute for code generation
                if ($instance instanceof \event4u\DataHelpers\SimpleDto\Attributes\MapFrom) {
                    $mapFrom = $instance->path;
                }
            } catch (Throwable) {
                // Skip attributes that can't be instantiated
            }
        }

        return [
            'name' => $param->getName(),
            'type' => $typeName,
            'isBuiltin' => $isBuiltin,
            'allowsNull' => $allowsNull,
            'hasDefault' => $param->isDefaultValueAvailable(),
            'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            'mapFrom' => $mapFrom,
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

    /**
     * Clear the cache (useful for testing).
     *
     * Phase 11a: Now also clears persistent cache.
     */
    public static function clear(): void
    {
        self::$cache = [];
        self::$persistentCacheEnabled = null; // Reset cache enabled check

        // Also clear persistent cache
        try {
            CacheManager::clear();
        } catch (\Throwable) {
            // Silently fail - persistent cache is optional
        }
    }

    /**
     * Clear cache for a specific class.
     *
     * Phase 11a: Now also clears persistent cache for the class.
     */
    public static function clearClass(string $class): void
    {
        unset(self::$cache[$class]);

        // Also clear persistent cache for this class
        try {
            $cacheKey = 'dto_metadata_' . str_replace('\\', '_', $class);
            CacheManager::delete($cacheKey);
        } catch (\Throwable) {
            // Silently fail - persistent cache is optional
        }
    }

    /**
     * Clear all in-memory cache.
     *
     * Note: This only clears the in-memory cache, not the persistent cache.
     * Use bin/clear-cache.php to clear persistent cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$persistentCacheEnabled = null;
    }
}
