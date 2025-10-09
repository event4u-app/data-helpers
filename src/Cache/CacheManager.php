<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Cache;

use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\Cache\Drivers\NullDriver;
use event4u\DataHelpers\Cache\Drivers\SymfonyCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;

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
        $driver = DataHelpersConfig::get('cache.driver', 'memory');
        $maxEntries = DataHelpersConfig::getCacheMaxEntries();
        $defaultTtl = DataHelpersConfig::get('cache.default_ttl');

        return match ($driver) {
            'memory' => new MemoryDriver($maxEntries),
            'null', 'none' => new NullDriver(),
            'laravel' => new LaravelCacheDriver(
                (string)DataHelpersConfig::get('cache.prefix', 'data_helpers:'),
                is_int($defaultTtl) ? $defaultTtl : null
            ),
            'symfony' => self::createSymfonyDriver($defaultTtl),
            default => throw new InvalidArgumentException(
                sprintf('Unknown cache driver: %s', (string)$driver)
            ),
        };
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
