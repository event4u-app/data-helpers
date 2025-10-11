<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\Cache\Drivers\SymfonyCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

describe('Default TTL', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('uses default TTL from config for framework driver (Laravel)', function(): void {
        if (function_exists('setupLaravelCache')) {
            setupLaravelCache();
        }

        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'prefix' => 'test:',
                'default_ttl' => 3600,
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(LaravelCacheDriver::class);

        // Set without TTL - should use default
        $cache->set('key1', 'value1');

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');

        if (function_exists('teardownLaravelCache')) {
            teardownLaravelCache();
        }
    })->group('laravel');

    it('allows overriding default TTL for framework driver (Laravel)', function(): void {
        if (function_exists('setupLaravelCache')) {
            setupLaravelCache();
        }

        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'prefix' => 'test:',
                'default_ttl' => 3600,
            ],
        ]);

        $cache = CacheManager::getInstance();

        // Set with custom TTL - should override default
        $cache->set('key1', 'value1', 7200);

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');

        if (function_exists('teardownLaravelCache')) {
            teardownLaravelCache();
        }
    })->group('laravel');

    it('uses default TTL from config for Symfony driver', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool, 3600);

        // Set without TTL - should use default
        $cache->set('key1', 'value1');

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');
    })->group('symfony');

    it('allows overriding default TTL for Symfony driver', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool, 3600);

        // Set with custom TTL - should override default
        $cache->set('key1', 'value1', 7200);

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');
    })->group('symfony');

    it('caches forever when default TTL is null', function(): void {
        if (function_exists('setupLaravelCache')) {
            setupLaravelCache();
        }

        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'prefix' => 'test:',
                'default_ttl' => null,
            ],
        ]);

        $cache = CacheManager::getInstance();

        // Set without TTL - should cache forever
        $cache->set('key1', 'value1');

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');

        if (function_exists('teardownLaravelCache')) {
            teardownLaravelCache();
        }
    })->group('laravel');

    it('uses provided TTL over null default TTL', function(): void {
        if (function_exists('setupLaravelCache')) {
            setupLaravelCache();
        }

        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'prefix' => 'test:',
                'default_ttl' => null,
            ],
        ]);

        $cache = CacheManager::getInstance();

        // Set with custom TTL - should use provided TTL
        $cache->set('key1', 'value1', 3600);

        // Verify value is stored
        expect($cache->get('key1'))->toBe('value1');

        if (function_exists('teardownLaravelCache')) {
            teardownLaravelCache();
        }
    })->group('laravel');
});

