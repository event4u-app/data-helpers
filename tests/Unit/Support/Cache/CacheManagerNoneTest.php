<?php

declare(strict_types=1);

use event4u\DataHelpers\Enums\CacheDriver;
use event4u\DataHelpers\Helpers\ConfigHelper;
use event4u\DataHelpers\Support\Cache\Adapters\NullCacheAdapter;
use event4u\DataHelpers\Support\Cache\CacheManager;

describe('CacheManager with NONE driver', function () {
    beforeEach(function () {
        // Reset cache manager
        CacheManager::reset();

        // Set config to use NONE driver
        ConfigHelper::getInstance()->set('cache.driver', CacheDriver::NONE);
    });

    afterEach(function () {
        CacheManager::reset();
        ConfigHelper::getInstance()->reset();
    });

    it('uses NullCacheAdapter when driver is NONE', function () {
        $instance = CacheManager::getInstance();
        expect($instance)->toBeInstanceOf(NullCacheAdapter::class);
    });

    it('reports NONE as detected driver', function () {
        CacheManager::getInstance(); // Trigger detection
        expect(CacheManager::getDriver())->toBe(CacheDriver::NONE);
    });

    it('does not cache anything', function () {
        // Set a value
        $result = CacheManager::set('test_key', 'test_value');
        expect($result)->toBeFalse();

        // Try to get it back
        $value = CacheManager::get('test_key');
        expect($value)->toBeNull();

        // Check if it exists
        expect(CacheManager::has('test_key'))->toBeFalse();
    });

    it('always returns default values', function () {
        CacheManager::set('key', 'value');

        expect(CacheManager::get('key'))->toBeNull();
        expect(CacheManager::get('key', 'default'))->toBe('default');
        expect(CacheManager::get('key', 123))->toBe(123);
    });

    it('handles multiple operations', function () {
        // Set multiple
        $result = CacheManager::getInstance()->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        expect($result)->toBeFalse();

        // Get multiple
        $values = CacheManager::getInstance()->getMultiple(['key1', 'key2'], 'default');
        expect($values)->toBe([
            'key1' => 'default',
            'key2' => 'default',
        ]);

        // Delete multiple
        $result = CacheManager::getInstance()->deleteMultiple(['key1', 'key2']);
        expect($result)->toBeFalse();
    });

    it('clear always succeeds', function () {
        expect(CacheManager::clear())->toBeTrue();
    });
});

