<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\Cache\Drivers\NoneDriver;
use event4u\DataHelpers\Cache\Drivers\SymfonyCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Enums\CacheDriver;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use Throwable;
use ValueError;

/**
 * Cache manager - creates and manages cache drivers.
 */
final class CacheManager
{
    private static ?CacheInterface $instance = null;

    /** Get cache instance based on configuration. */
    public static function getInstance(): CacheInterface
    {
        if (!self::$instance instanceof CacheInterface) {
            self::$instance = self::createDriver();
        }

        return self::$instance;
    }

    /** Set custom cache instance. */
    public static function setInstance(CacheInterface $cache): void
    {
        self::$instance = $cache;
    }

    /** Reset cache instance (useful for testing). */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /** Create cache driver based on configuration. */
    private static function createDriver(): CacheInterface
    {
        $driverString = (string)DataHelpersConfig::get('cache.driver', 'none');
        $maxEntries = DataHelpersConfig::getCacheMaxEntries();
        $defaultTtl = DataHelpersConfig::get('cache.default_ttl');

        // Convert string to enum
        try {
            $driver = CacheDriver::from($driverString);
        } catch (ValueError) {
            throw new InvalidArgumentException(
                sprintf('Unknown cache driver: %s. Supported: memory, framework, none', $driverString)
            );
        }

        return match ($driver) {
            CacheDriver::MEMORY => new MemoryDriver($maxEntries),
            CacheDriver::NONE => new NoneDriver(),
            CacheDriver::FRAMEWORK => self::createFrameworkDriver($maxEntries, $defaultTtl),
        };
    }

    /** Create framework-specific driver or fallback to memory. */
    private static function createFrameworkDriver(int $maxEntries, mixed $defaultTtl): CacheInterface
    {
        // Check for Symfony first (if explicitly configured with pool)
        if (interface_exists('Psr\Cache\CacheItemPoolInterface')) {
            $pool = DataHelpersConfig::get('cache.symfony.pool');
            if ($pool instanceof CacheItemPoolInterface) {
                return self::createSymfonyDriver($defaultTtl);
            }
        }

        // Check for Laravel (class exists AND facade root is set)
        if (self::isLaravelActive()) {
            return new LaravelCacheDriver(
                (string)DataHelpersConfig::get('cache.prefix', 'data_helpers:'),
                is_int($defaultTtl) ? $defaultTtl : null
            );
        }

        // No framework detected, fallback to memory
        if (CacheDriver::fallback() === CacheDriver::MEMORY) {
            return new MemoryDriver($maxEntries);
        }

        // No framework detected, fallback to no driver
        return new NoneDriver();
    }

    /** Check if Laravel is active (not just installed). */
    private static function isLaravelActive(): bool
    {
        if (!class_exists('Illuminate\Support\Facades\Cache')) {
            return false;
        }

        try {
            // Try to get the facade root - if it throws, Laravel is not active
            $reflection = new ReflectionClass('Illuminate\Support\Facades\Cache');
            $method = $reflection->getMethod('getFacadeRoot');
            $root = $method->invoke(null);

            return null !== $root;
        } catch (Throwable) {
            return false;
        }
    }

    private static function createSymfonyDriver(mixed $defaultTtl): SymfonyCacheDriver
    {
        $pool = DataHelpersConfig::get('cache.symfony.pool');

        if (!$pool instanceof CacheItemPoolInterface) {
            throw new InvalidArgumentException(
                'Symfony cache driver requires a CacheItemPoolInterface instance in cache.symfony.pool'
            );
        }

        return new SymfonyCacheDriver(
            $pool,
            is_int($defaultTtl) ? $defaultTtl : null
        );
    }
}
