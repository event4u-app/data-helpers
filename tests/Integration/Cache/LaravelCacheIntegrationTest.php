<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Illuminate\Support\Facades\Cache;

/**
 * @group laravel
 */
describe('Laravel Cache Integration', function(): void {
    beforeEach(function(): void {
        if (function_exists('setupLaravelCache')) {
            setupLaravelCache();
        }

        // Ensure clean state
        try {
            Cache::flush();
        } catch (Exception) {
            // Ignore flush errors in case cache is not ready
        }

        CacheManager::reset();
        DataHelpersConfig::reset();

        // Small delay to ensure database is ready
        usleep(10000); // 10ms
    });

    afterEach(function(): void {
        // Clean up in reverse order
        try {
            Cache::flush();
        } catch (Exception) {
            // Ignore flush errors
        }

        CacheManager::reset();
        DataHelpersConfig::reset();

        if (function_exists('teardownLaravelCache')) {
            teardownLaravelCache();
        }

        // Small delay to ensure cleanup is complete
        usleep(10000); // 10ms
    });

    it('uses Laravel cache driver from config', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'test:',
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(LaravelCacheDriver::class);
    });

    it('stores and retrieves values via Laravel cache', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'integration:',
        ]);

        $cache = CacheManager::getInstance();
        $cache->set('test_key', 'test_value');

        expect($cache->get('test_key'))->toBe('test_value');
        expect(Cache::has('integration:test_key'))->toBeTrue();
    });

    it('respects Laravel cache configuration', function(): void {
        // Laravel cache is configured in the test environment
        DataHelpersConfig::set('cache.driver', 'framework');

        $cache = CacheManager::getInstance();

        $cache->set('key1', 'value1');
        $cache->set('key2', ['nested' => 'array']);
        $cache->set('key3', 12345);

        expect($cache->get('key1'))->toBe('value1');
        expect($cache->get('key2'))->toBe(['nested' => 'array']);

        // Integer values might be serialized differently depending on cache driver
        $value3 = $cache->get('key3');
        expect(12345 == $value3)->toBeTrue();
    });

    it('works with Laravel cache TTL', function(): void {
        DataHelpersConfig::set('cache.driver', 'framework');

        $cache = CacheManager::getInstance();
        $cache->set('ttl_key', 'ttl_value', 3600);

        expect($cache->get('ttl_key'))->toBe('ttl_value');
    });
})->group('laravel');

