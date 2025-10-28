<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Support\Cache;

use event4u\DataHelpers\Enums\CacheDriver;
use event4u\DataHelpers\Helpers\ConfigHelper;
use event4u\DataHelpers\Support\Cache\Adapters\FilesystemCacheAdapter;
use event4u\DataHelpers\Support\Cache\Adapters\LaravelCacheAdapter;
use event4u\DataHelpers\Support\Cache\Adapters\NullCacheAdapter;
use event4u\DataHelpers\Support\Cache\Adapters\SymfonyCacheAdapter;

/**
 * Cache manager with automatic driver detection.
 *
 * Automatically detects and uses the best available cache backend:
 * 1. Laravel Cache (if available)
 * 2. Symfony Cache (if available)
 * 3. Filesystem Cache (always available as fallback)
 */
final class CacheManager
{
    private static ?CacheInterface $instance = null;
    private static ?CacheDriver $detectedDriver = null;

    /**
     * Get the cache instance (singleton).
     */
    public static function getInstance(): CacheInterface
    {
        if (null === self::$instance) {
            self::$instance = self::createCacheInstance();
        }

        return self::$instance;
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist
     *
     * @return mixed The cached value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->get($key, $default);
    }

    /**
     * Store a value in the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = use config default)
     *
     * @return bool True on success, false on failure
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= self::getDefaultTtl();

        return self::getInstance()->set($key, $value, $ttl);
    }

    /**
     * Check if a key exists in the cache.
     */
    public static function has(string $key): bool
    {
        return self::getInstance()->has($key);
    }

    /**
     * Delete a value from the cache.
     */
    public static function delete(string $key): bool
    {
        return self::getInstance()->delete($key);
    }

    /**
     * Clear all cache entries.
     */
    public static function clear(): bool
    {
        return self::getInstance()->clear();
    }

    /**
     * Get the detected cache driver.
     */
    public static function getDriver(): CacheDriver
    {
        if (null === self::$detectedDriver) {
            self::getInstance(); // Trigger detection
        }

        return self::$detectedDriver ?? CacheDriver::FILESYSTEM;
    }

    /**
     * Reset the cache instance (for testing).
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$detectedDriver = null;
    }

    /**
     * Create cache instance based on configuration.
     */
    private static function createCacheInstance(): CacheInterface
    {
        $config = ConfigHelper::getInstance();
        $driver = $config->get('cache.driver', CacheDriver::AUTO);

        // Convert string to enum if needed
        if (is_string($driver)) {
            $driver = CacheDriver::from($driver);
        }

        // Auto-detect if driver is AUTO
        if (CacheDriver::AUTO === $driver) {
            $driver = self::detectDriver();
        }

        self::$detectedDriver = $driver;

        return match ($driver) {
            CacheDriver::NONE => new NullCacheAdapter(),
            CacheDriver::LARAVEL => self::createLaravelAdapter(),
            CacheDriver::SYMFONY => self::createSymfonyAdapter(),
            CacheDriver::FILESYSTEM => self::createFilesystemAdapter(),
        };
    }

    /**
     * Detect the best available cache driver.
     */
    private static function detectDriver(): CacheDriver
    {
        // Try Laravel first
        if (self::isLaravelAvailable()) {
            return CacheDriver::LARAVEL;
        }

        // Try Symfony second
        if (self::isSymfonyAvailable()) {
            return CacheDriver::SYMFONY;
        }

        // Fallback to filesystem
        return CacheDriver::FILESYSTEM;
    }

    /**
     * Check if Laravel cache is available and working.
     */
    private static function isLaravelAvailable(): bool
    {
        if (!function_exists('app')) {
            return false;
        }

        try {
            $app = app();

            if (!$app->bound('cache') && !$app->bound('cache.store')) {
                return false;
            }

            // Test if cache is actually working
            $cache = $app->bound('cache.store') ? $app->make('cache.store') : $app->make('cache');
            $testKey = '__data_helpers_cache_test__';
            $testValue = 'test';

            $cache->put($testKey, $testValue, 1);
            $retrieved = $cache->get($testKey);
            $cache->forget($testKey);

            return $retrieved === $testValue;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if Symfony cache is available and working.
     */
    private static function isSymfonyAvailable(): bool
    {
        // Only use Symfony cache if we're in a Symfony application context
        // Otherwise, use filesystem cache
        if (!class_exists('Symfony\Component\HttpKernel\Kernel')) {
            return false;
        }

        if (!interface_exists('Psr\Cache\CacheItemPoolInterface')
            || !class_exists('Symfony\Component\Cache\Adapter\FilesystemAdapter')) {
            return false;
        }

        try {
            // Test if cache is actually working
            $cachePath = self::getCachePath();
            $cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
                'data_helpers_test',
                0,
                $cachePath
            );

            $testKey = '__data_helpers_cache_test__';
            $testValue = 'test';

            $item = $cache->getItem($testKey);
            $item->set($testValue);
            $cache->save($item);

            $retrieved = $cache->getItem($testKey);
            $success = $retrieved->isHit() && $retrieved->get() === $testValue;

            $cache->deleteItem($testKey);

            return $success;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Create Laravel cache adapter.
     */
    private static function createLaravelAdapter(): CacheInterface
    {
        $cache = app('cache.store') ?? app('cache');

        return new LaravelCacheAdapter($cache);
    }

    /**
     * Create Symfony cache adapter.
     */
    private static function createSymfonyAdapter(): CacheInterface
    {
        // Try to get cache from container
        if (function_exists('app') && app()->bound('cache.app')) {
            $cache = app('cache.app');

            return new SymfonyCacheAdapter($cache);
        }

        // Fallback: Create filesystem adapter using Symfony's FilesystemAdapter
        $config = ConfigHelper::getInstance();
        $cachePath = $config->getString('cache.path', './.event4u/data-helpers/cache/');

        $cacheClass = 'Symfony\Component\Cache\Adapter\FilesystemAdapter';
        $cache = new $cacheClass('data_helpers', 0, $cachePath);

        return new SymfonyCacheAdapter($cache);
    }

    /**
     * Create filesystem cache adapter.
     */
    private static function createFilesystemAdapter(): CacheInterface
    {
        $config = ConfigHelper::getInstance();
        $cachePath = $config->getString('cache.path', './.event4u/data-helpers/cache/');

        return new FilesystemCacheAdapter($cachePath);
    }

    /**
     * Get default TTL from configuration.
     */
    private static function getDefaultTtl(): ?int
    {
        $config = ConfigHelper::getInstance();
        $ttl = $config->get('cache.ttl');

        if (null === $ttl) {
            return null;
        }

        return is_int($ttl) ? $ttl : (int)$ttl;
    }
}

