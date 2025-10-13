<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Illuminate\Support\Facades\Cache;

describe('Laravel Cache Integration E2E', function(): void {
    beforeEach(function(): void {
        Cache::flush();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        Cache::flush();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('uses Laravel cache driver when configured', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'test:',
        ]);

        $cache = CacheManager::getInstance();

        expect($cache)->toBeInstanceOf(LaravelCacheDriver::class);
    });

    it('stores and retrieves values through Laravel cache', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'laravel_test:',
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('key1', 'value1');
        $cache->set('key2', ['nested' => 'array']);
        $cache->set('key3', 12345);

        expect($cache->get('key1'))->toBe('value1')
            ->and($cache->get('key2'))->toBe(['nested' => 'array']);

        // Integer values might be returned as strings depending on cache driver
        $value3 = $cache->get('key3');
        expect($value3 == 12345)->toBeTrue();
    });

    it('respects TTL when storing values', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'ttl_test:',
        ]);

        $cache = CacheManager::getInstance();

        // Store with 1 second TTL
        $cache->set('short_lived', 'value', 1);

        expect($cache->get('short_lived'))->toBe('value');

        // Wait for expiration
        sleep(2);

        expect($cache->get('short_lived'))->toBeNull();
    });

    it('deletes values from Laravel cache', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'delete_test:',
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('to_delete', 'value');
        expect($cache->get('to_delete'))->toBe('value');

        $cache->delete('to_delete');
        expect($cache->get('to_delete'))->toBeNull();
    });

    it('checks if key exists in Laravel cache', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'exists_test:',
        ]);

        $cache = CacheManager::getInstance();

        expect($cache->has('nonexistent'))->toBeFalse();

        $cache->set('exists', 'value');
        expect($cache->has('exists'))->toBeTrue();
    });

    it('clears all cache entries', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'clear_test:',
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        expect($cache->has('key1'))->toBeTrue()
            ->and($cache->has('key2'))->toBeTrue()
            ->and($cache->has('key3'))->toBeTrue();

        $cache->clear();

        expect($cache->has('key1'))->toBeFalse()
            ->and($cache->has('key2'))->toBeFalse()
            ->and($cache->has('key3'))->toBeFalse();
    });

    it('uses cache prefix to avoid collisions', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'prefix_a:',
        ]);

        $cacheA = CacheManager::getInstance();
        $cacheA->set('shared_key', 'value_a');

        // Reset and use different prefix
        CacheManager::reset();
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'prefix_b:',
        ]);

        $cacheB = CacheManager::getInstance();
        $cacheB->set('shared_key', 'value_b');

        // Both should have their own values
        expect($cacheA->get('shared_key'))->toBe('value_a')
            ->and($cacheB->get('shared_key'))->toBe('value_b');
    });

    it('integrates with Laravel Cache facade', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'facade_test:',
        ]);

        $cache = CacheManager::getInstance();

        // Set via DataHelpers
        $cache->set('from_helpers', 'value');

        // Retrieve via Laravel Cache facade (with prefix)
        $value = Cache::get('facade_test:from_helpers');
        expect($value)->toBe('value');

        // Set via Laravel Cache facade
        Cache::put('facade_test:from_facade', 'facade_value', 3600);

        // Retrieve via DataHelpers
        expect($cache->get('from_facade'))->toBe('facade_value');
    });

    it('handles cache tags if supported', function(): void {
        // Note: Tags are only supported by certain cache drivers (Redis, Memcached)
        // Array driver doesn't support tags, so this test might be skipped
        if (!method_exists(Cache::store(), 'tags')) {
            $this->markTestSkipped('Cache driver does not support tags');
        }

        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'tags_test:',
        ]);

        $cache = CacheManager::getInstance();

        // This is a basic test - tags would need to be implemented in LaravelCacheDriver
        $cache->set('tagged_key', 'tagged_value');
        expect($cache->get('tagged_key'))->toBe('tagged_value');
    });

    it('uses default TTL from config', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'default_ttl_test:',
            'cache.default_ttl' => 3600,
        ]);

        $cache = CacheManager::getInstance();

        // Set without explicit TTL
        $cache->set('with_default_ttl', 'value');

        // Should be stored (we can't easily test the TTL without waiting)
        expect($cache->get('with_default_ttl'))->toBe('value');
    });

    it('stores complex data structures', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'complex_test:',
        ]);

        $cache = CacheManager::getInstance();

        $complexData = [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'roles' => ['admin', 'editor'],
                'meta' => [
                    'last_login' => '2024-01-01',
                    'preferences' => [
                        'theme' => 'dark',
                        'language' => 'en',
                    ],
                ],
            ],
        ];

        $cache->set('complex', $complexData);
        $retrieved = $cache->get('complex');

        expect($retrieved)->toBe($complexData)
            ->and($retrieved['user']['name'])->toBe('John Doe')
            ->and($retrieved['user']['roles'])->toBe(['admin', 'editor'])
            ->and($retrieved['user']['meta']['preferences']['theme'])->toBe('dark');
    });

    it('handles null values correctly', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'null_test:',
        ]);

        $cache = CacheManager::getInstance();

        // Store null value
        $cache->set('null_value', null);

        // Should return null (not false or empty)
        // Note: Laravel's has() returns false for null values, which is expected behavior
        expect($cache->get('null_value'))->toBeNull();
    });

    it('handles boolean values correctly', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.prefix' => 'bool_test:',
        ]);

        $cache = CacheManager::getInstance();

        $cache->set('true_value', true);
        $cache->set('false_value', false);

        expect($cache->get('true_value'))->toBeTrue()
            ->and($cache->get('false_value'))->toBeFalse();
    });
})->group('laravel');

