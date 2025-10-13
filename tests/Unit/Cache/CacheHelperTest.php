<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\DataHelpersConfig;

describe('CacheHelper', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 100,
        ]);
    });

    afterEach(function(): void {
        CacheHelper::clear();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    describe('Basic Operations', function(): void {
        it('sets and gets values', function(): void {
            CacheHelper::set('key1', 'value1');
            expect(CacheHelper::get('key1'))->toBe('value1');
        });

        it('returns default value for missing keys', function(): void {
            expect(CacheHelper::get('missing', 'default'))->toBe('default');
        });

        it('checks if key exists', function(): void {
            CacheHelper::set('key1', 'value1');
            expect(CacheHelper::has('key1'))->toBeTrue();
            expect(CacheHelper::has('missing'))->toBeFalse();
        });

        it('deletes values', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::delete('key1');
            expect(CacheHelper::has('key1'))->toBeFalse();
        });

        it('clears all values', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::set('key2', 'value2');
            CacheHelper::clear();
            expect(CacheHelper::has('key1'))->toBeFalse();
            expect(CacheHelper::has('key2'))->toBeFalse();
        });
    });

    describe('Aliases', function(): void {
        it('put is alias for set', function(): void {
            CacheHelper::put('key1', 'value1');
            expect(CacheHelper::get('key1'))->toBe('value1');
        });

        it('exists is alias for has', function(): void {
            CacheHelper::set('key1', 'value1');
            expect(CacheHelper::exists('key1'))->toBeTrue();
        });

        it('forget is alias for delete', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::forget('key1');
            expect(CacheHelper::has('key1'))->toBeFalse();
        });

        it('remove is alias for delete', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::remove('key1');
            expect(CacheHelper::has('key1'))->toBeFalse();
        });

        it('flush is alias for clear', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::flush();
            expect(CacheHelper::has('key1'))->toBeFalse();
        });
    });

    describe('Advanced Operations', function(): void {
        it('remembers values', function(): void {
            $result = CacheHelper::remember('key1', 'computed-value');
            expect($result)->toBe('computed-value');
            expect(CacheHelper::get('key1'))->toBe('computed-value');
        });

        it('remembers values with callback', function(): void {
            // Ensure key doesn't exist
            CacheHelper::delete('remember-callback-key');

            $called = false;
            $result = CacheHelper::remember('remember-callback-key', function() use (&$called): string {
                $called = true;
                return 'computed-value';
            });

            expect($result)->toBe('computed-value');
            expect($called)->toBeTrue();
            expect(CacheHelper::get('remember-callback-key'))->toBe('computed-value');
        });

        it('does not call callback if value exists', function(): void {
            CacheHelper::set('key1', 'existing-value');

            $called = false;
            $result = CacheHelper::remember('key1', function() use (&$called): string {
                $called = true;
                return 'new-value';
            });

            expect($result)->toBe('existing-value');
            expect($called)->toBeFalse();
        });

        it('pulls value and deletes it', function(): void {
            CacheHelper::set('key1', 'value1');
            $value = CacheHelper::pull('key1');

            expect($value)->toBe('value1');
            expect(CacheHelper::has('key1'))->toBeFalse();
        });

        it('stores value forever', function(): void {
            CacheHelper::forever('key1', 'value1');
            expect(CacheHelper::get('key1'))->toBe('value1');
        });
    });

    describe('Multiple Operations', function(): void {
        it('gets multiple values', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::set('key2', 'value2');

            $result = CacheHelper::getMultiple(['key1', 'key2', 'key3'], 'default');

            expect($result)->toBe([
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'default',
            ]);
        });

        it('sets multiple values', function(): void {
            CacheHelper::setMultiple([
                'key1' => 'value1',
                'key2' => 'value2',
            ]);

            expect(CacheHelper::get('key1'))->toBe('value1');
            expect(CacheHelper::get('key2'))->toBe('value2');
        });

        it('deletes multiple values', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::set('key2', 'value2');
            CacheHelper::set('key3', 'value3');

            CacheHelper::deleteMultiple(['key1', 'key2']);

            expect(CacheHelper::has('key1'))->toBeFalse();
            expect(CacheHelper::has('key2'))->toBeFalse();
            expect(CacheHelper::has('key3'))->toBeTrue();
        });
    });

    describe('Numeric Operations', function(): void {
        it('increments values', function(): void {
            CacheHelper::set('counter', 5);
            $result = CacheHelper::increment('counter');

            expect($result)->toBe(6);
            expect(CacheHelper::get('counter'))->toBe(6);
        });

        it('increments by custom amount', function(): void {
            CacheHelper::set('counter', 5);
            $result = CacheHelper::increment('counter', 10);

            expect($result)->toBe(15);
            expect(CacheHelper::get('counter'))->toBe(15);
        });

        it('increments from zero if key does not exist', function(): void {
            // Ensure key doesn't exist
            CacheHelper::delete('increment-new-counter');

            $result = CacheHelper::increment('increment-new-counter');

            expect($result)->toBe(1);
            expect(CacheHelper::get('increment-new-counter'))->toBe(1);
        });

        it('decrements values', function(): void {
            CacheHelper::set('counter', 10);
            $result = CacheHelper::decrement('counter');

            expect($result)->toBe(9);
            expect(CacheHelper::get('counter'))->toBe(9);
        });

        it('decrements by custom amount', function(): void {
            CacheHelper::set('counter', 10);
            $result = CacheHelper::decrement('counter', 5);

            expect($result)->toBe(5);
            expect(CacheHelper::get('counter'))->toBe(5);
        });
    });

    describe('Statistics', function(): void {
        it('returns cache statistics', function(): void {
            CacheHelper::set('key1', 'value1');
            CacheHelper::get('key1');

            $stats = CacheHelper::getStats();

            expect($stats)->toHaveKey('hits');
            expect($stats)->toHaveKey('misses');
            expect($stats)->toHaveKey('size');
            expect($stats)->toHaveKey('max_size');
        });
    });

    describe('TTL Support', function(): void {
        it('sets value with custom TTL', function(): void {
            CacheHelper::set('key1', 'value1', 3600);
            expect(CacheHelper::get('key1'))->toBe('value1');
        });

        it('remembers value with custom TTL', function(): void {
            $result = CacheHelper::remember('key1', 'value1', 3600);
            expect($result)->toBe('value1');
        });

        it('sets multiple values with custom TTL', function(): void {
            CacheHelper::setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600);
            expect(CacheHelper::get('key1'))->toBe('value1');
            expect(CacheHelper::get('key2'))->toBe('value2');
        });
    });
});

