<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;
use ReflectionClass;
use ReflectionException;

/**
 * Hash-validated cache that automatically invalidates entries when source data changes.
 *
 * This cache stores a hash of the source data alongside the cached value.
 * When retrieving a value, it compares the current hash with the stored hash.
 * If they differ, the cache entry is invalidated and null is returned.
 *
 * Use cases:
 * - Template caching (invalidate when template changes)
 * - Class-based caching (invalidate when class file changes)
 * - Configuration caching (invalidate when config changes)
 */
final class HashValidatedCache
{
    private const HASH_SUFFIX = ':hash';

    /**
     * Get a value from cache with hash validation.
     *
     * @param string $class Class name for scoping
     * @param string $key Cache key
     * @param mixed $sourceData Data to hash for validation (e.g., template string, class file path)
     * @param mixed $default Default value if key not found or hash mismatch
     * @return mixed Value from cache or default
     */
    public static function get(
        string $class,
        string $key,
        mixed $sourceData,
        mixed $default = null
    ): mixed {
        // Get cached value and hash
        $value = ClassScopedCache::get($class, $key, $default);
        $storedHash = ClassScopedCache::get($class, $key . self::HASH_SUFFIX);

        // If no value or hash, return default
        if ($value === $default || null === $storedHash) {
            return $default;
        }

        // Calculate current hash
        $currentHash = self::calculateHash($sourceData);

        // If hash mismatch, invalidate cache
        if ($currentHash !== $storedHash) {
            self::delete($class, $key);
            return $default;
        }

        return $value;
    }

    /**
     * Store a value in cache with hash validation.
     *
     * @param string $class Class name for scoping
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param mixed $sourceData Data to hash for validation
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     * @param int $maxEntries Maximum entries per class (default: 100)
     */
    public static function set(
        string $class,
        string $key,
        mixed $value,
        mixed $sourceData,
        ?int $ttl = null,
        int $maxEntries = 100
    ): void {
        // Calculate hash
        $hash = self::calculateHash($sourceData);

        // Store value and hash
        ClassScopedCache::set($class, $key, $value, $ttl, $maxEntries);
        ClassScopedCache::set($class, $key . self::HASH_SUFFIX, $hash, $ttl, $maxEntries);
    }

    /**
     * Check if a key exists in cache with valid hash.
     *
     * @param string $class Class name for scoping
     * @param string $key Cache key
     * @param mixed $sourceData Data to hash for validation
     */
    public static function has(string $class, string $key, mixed $sourceData): bool
    {
        $value = self::get($class, $key, $sourceData, null);
        return null !== $value;
    }

    /**
     * Remove a value from cache.
     *
     * @param string $class Class name for scoping
     * @param string $key Cache key
     */
    public static function delete(string $class, string $key): void
    {
        ClassScopedCache::delete($class, $key);
        ClassScopedCache::delete($class, $key . self::HASH_SUFFIX);
    }

    /**
     * Get or store a value in cache with hash validation.
     *
     * @param string $class Class name for scoping
     * @param string $key Cache key
     * @param mixed $sourceData Data to hash for validation
     * @param callable|mixed $value Value to store if key not found (can be a callback)
     * @param int|null $ttl Time to live in seconds (null = use default from config)
     * @param int $maxEntries Maximum entries per class (default: 100)
     * @return mixed Value from cache or newly stored value
     */
    public static function remember(
        string $class,
        string $key,
        mixed $sourceData,
        mixed $value,
        ?int $ttl = null,
        int $maxEntries = 100
    ): mixed {
        $cached = self::get($class, $key, $sourceData);

        if (null !== $cached) {
            return $cached;
        }

        // If value is a callback, execute it
        $resolvedValue = is_callable($value) ? $value() : $value;

        self::set($class, $key, $resolvedValue, $sourceData, $ttl, $maxEntries);

        return $resolvedValue;
    }

    /**
     * Clear all cache entries for a specific class.
     *
     * @param string $class Class name
     */
    public static function clearClass(string $class): void
    {
        ClassScopedCache::clearClass($class);
    }

    /**
     * Calculate hash for source data.
     *
     * Supports:
     * - Strings (templates, file paths)
     * - Arrays (configuration, data structures)
     * - Objects (will be serialized)
     *
     * For file paths, reads file content and hashes it.
     * For class names, reads class file and hashes it.
     */
    private static function calculateHash(mixed $sourceData): string
    {
        // If it's a file path, read file content
        if (is_string($sourceData) && file_exists($sourceData)) {
            $content = file_get_contents($sourceData);
            return hash('xxh128', $content ?: '');
        }

        // If it's a class name, get file path and hash file content
        if (is_string($sourceData) && class_exists($sourceData)) {
            try {
                $reflection = new ReflectionClass($sourceData);
                $filePath = $reflection->getFileName();
                
                if (false !== $filePath && file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    return hash('xxh128', $content ?: '');
                }
            } catch (ReflectionException) {
                // Fall through to default hashing
            }
        }

        // For arrays and objects, serialize and hash
        if (is_array($sourceData) || is_object($sourceData)) {
            return hash('xxh128', serialize($sourceData));
        }

        // For other types, convert to string and hash
        return hash('xxh128', (string)$sourceData);
    }
}

