<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use event4u\DataHelpers\Enums\CacheInvalidation;
use event4u\DataHelpers\Helpers\ConfigHelper;

use function file_exists;
use function filemtime;
use function hash_file;

/**
 * Cache invalidation helper.
 *
 * Provides automatic cache invalidation based on file modification time
 * and/or content hash.
 */
final class CacheInvalidator
{
    /**
     * Wrap a value with invalidation metadata.
     *
     * Phase 11a Performance: Store source file path to avoid ReflectionClass on validation.
     *
     * @param mixed $value The value to cache
     * @param string $sourceFile The source file to track for changes
     *
     * @return array{data: mixed, source_file: string, mtime: int|false, hash: string|null, version: string}
     */
    public static function wrap(mixed $value, string $sourceFile): array
    {
        $strategy = self::getInvalidationStrategy();

        $mtime = file_exists($sourceFile) ? filemtime($sourceFile) : false;
        $hash = null;

        if (CacheInvalidation::HASH === $strategy || CacheInvalidation::BOTH === $strategy) {
            $hashResult = file_exists($sourceFile) ? hash_file('xxh128', $sourceFile) : false;
            $hash = is_string($hashResult) ? $hashResult : null;
        }

        return [
            'data' => $value,
            'source_file' => $sourceFile, // Store source file path for validation
            'mtime' => $mtime,
            'hash' => $hash,
            'version' => self::getPackageVersion(),
        ];
    }

    /**
     * Check if cached data is still valid.
     *
     * Phase 11a Performance: Use source file from cache if available.
     *
     * @param array{data: mixed, source_file?: string, mtime: int|false, hash: string|null, version: string} $cachedData
     * @param string|null $sourceFile The source file to check (optional if stored in cache)
     *
     * @return bool True if cache is valid, false if it should be regenerated
     */
    public static function isValid(array $cachedData, ?string $sourceFile = null): bool
    {
        // Use source file from cache if not provided
        $sourceFile = $sourceFile ?? $cachedData['source_file'] ?? null;

        if (null === $sourceFile) {
            return false;
        }

        // Check if source file exists
        if (!file_exists($sourceFile)) {
            return false;
        }

        // Check version
        if (!isset($cachedData['version']) || $cachedData['version'] !== self::getPackageVersion()) {
            return false;
        }

        $strategy = self::getInvalidationStrategy();

        // Check mtime
        if (CacheInvalidation::MTIME === $strategy || CacheInvalidation::BOTH === $strategy) {
            $currentMtime = filemtime($sourceFile);
            if (false === $currentMtime || !isset($cachedData['mtime']) || $currentMtime !== $cachedData['mtime']) {
                return false;
            }
        }

        // Check hash
        if (CacheInvalidation::HASH === $strategy || CacheInvalidation::BOTH === $strategy) {
            $currentHash = hash_file('xxh128', $sourceFile);
            if (false === $currentHash || !isset($cachedData['hash']) || $currentHash !== $cachedData['hash']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Unwrap cached data to get the original value.
     *
     * @param array{data: mixed, mtime: int|false, hash: string|null, version: string} $cachedData
     *
     * @return mixed The original cached value
     */
    public static function unwrap(array $cachedData): mixed
    {
        return $cachedData['data'] ?? null;
    }

    /**
     * Get a value from cache with automatic invalidation.
     *
     * @param string $key Cache key
     * @param string $sourceFile Source file to track for changes
     * @param callable(): mixed $generator Callback to generate value if cache is invalid
     *
     * @return mixed The cached or generated value
     */
    public static function remember(string $key, string $sourceFile, callable $generator): mixed
    {
        // Try to get from cache
        $cached = CacheManager::get($key);

        // Check if cache is valid
        if (is_array($cached) && isset($cached['data'], $cached['mtime'], $cached['hash'], $cached['version'])) {
            /** @var array{data: mixed, source_file?: string, mtime: int|false, hash: string|null, version: string} $cachedData */
            $cachedData = $cached;
            if (self::isValid($cachedData, $sourceFile)) {
                return self::unwrap($cachedData);
            }
        }

        // Generate new value
        $value = $generator();

        // Wrap and cache
        $wrapped = self::wrap($value, $sourceFile);
        CacheManager::set($key, $wrapped);

        return $value;
    }

    /**
     * Get the invalidation strategy from configuration.
     */
    private static function getInvalidationStrategy(): CacheInvalidation
    {
        $config = ConfigHelper::getInstance();
        $strategy = $config->get('cache.invalidation', CacheInvalidation::MTIME);

        if (is_string($strategy)) {
            return CacheInvalidation::from($strategy);
        }

        if ($strategy instanceof CacheInvalidation) {
            return $strategy;
        }

        return CacheInvalidation::MTIME;
    }

    /**
     * Get the package version for cache invalidation.
     */
    private static function getPackageVersion(): string
    {
        // Try to get version from composer.json
        $composerFile = __DIR__ . '/../../../composer.json';
        if (file_exists($composerFile)) {
            $contents = file_get_contents($composerFile);
            if (is_string($contents)) {
                /** @var array{version?: string}|null $composer */
                $composer = json_decode($contents, true);
                if (is_array($composer) && isset($composer['version']) && is_string($composer['version'])) {
                    return $composer['version'];
                }
            }
        }

        // Fallback: Use file modification time of this file as version
        return (string)filemtime(__FILE__);
    }
}

