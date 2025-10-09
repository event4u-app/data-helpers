<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Illuminate\Support\Facades\Cache;

describe('Laravel Cache Integration', function(): void {
    beforeEach(function(): void {
        setupLaravelCache();
        Cache::flush();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        Cache::flush();
        teardownLaravelCache();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('uses Laravel cache driver from config', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'laravel',
                'prefix' => 'test:',
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(LaravelCacheDriver::class);
    });

    it('stores and retrieves values via Laravel cache', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'laravel',
                'prefix' => 'integration:',
            ],
        ]);

        $cache = CacheManager::getInstance();
        $cache->set('test_key', 'test_value');

        expect($cache->get('test_key'))->toBe('test_value');
        expect(Cache::has('integration:test_key'))->toBeTrue();
    });

    it('respects Laravel cache configuration', function(): void {
        // Laravel cache is configured in the test environment
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'laravel',
            ],
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('key1', 'value1');
        $cache->set('key2', ['nested' => 'array']);
        $cache->set('key3', 12345);

        expect($cache->get('key1'))->toBe('value1');
        expect($cache->get('key2'))->toBe(['nested' => 'array']);
        expect($cache->get('key3'))->toBe(12345);
    });

    it('works with Laravel cache TTL', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'laravel',
            ],
        ]);

        $cache = CacheManager::getInstance();
        $cache->set('ttl_key', 'ttl_value', 3600);

        expect($cache->get('ttl_key'))->toBe('ttl_value');
    });
});

