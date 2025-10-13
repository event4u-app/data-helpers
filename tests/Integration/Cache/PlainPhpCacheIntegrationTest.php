<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\DataHelpersConfig;

describe('Plain PHP Cache Integration', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('uses memory driver by default for plain PHP', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 100,
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(MemoryDriver::class);
    });

    it('stores and retrieves values in memory', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 100,
        ]);

        $cache = CacheManager::getInstance();
        $cache->set('test_key', 'test_value');

        expect($cache->get('test_key'))->toBe('test_value');
    });

    it('respects max entries configuration', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 3,
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        // Access key1 to make it more recently used
        $cache->get('key1');

        // Add key4, should evict key2 (least recently used)
        $cache->set('key4', 'value4');

        expect($cache->has('key1'))->toBeTrue();
        expect($cache->has('key2'))->toBeFalse();
        expect($cache->has('key3'))->toBeTrue();
        expect($cache->has('key4'))->toBeTrue();
    });

    it('works without persistence', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 100,
        ]);

        $cache1 = CacheManager::getInstance();
        $cache1->set('key1', 'value1');

        // Reset creates new instance
        CacheManager::reset();

        $cache2 = CacheManager::getInstance();
        expect($cache2->get('key1'))->toBeNull();
    });
});

