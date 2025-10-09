<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use event4u\DataHelpers\Cache\LruCache;

describe('LRU Cache', function(): void {
    it('stores and retrieves values', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        expect($cache->get('key1'))->toBe('value1');
        expect($cache->get('key2'))->toBe('value2');
    });

    it('returns null for non-existent keys', function(): void {
        $cache = new LruCache(10);

        expect($cache->get('nonexistent'))->toBeNull();
    });

    it('checks if key exists', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');

        expect($cache->has('key1'))->toBeTrue();
        expect($cache->has('nonexistent'))->toBeFalse();
    });

    it('updates existing values', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');
        $cache->set('key1', 'value2');

        expect($cache->get('key1'))->toBe('value2');
        expect($cache->size())->toBe(1);
    });

    it('removes least recently used entry when full', function(): void {
        $cache = new LruCache(3);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        // Cache is full, adding key4 should remove key1 (least recently used)
        $cache->set('key4', 'value4');

        expect($cache->has('key1'))->toBeFalse();
        expect($cache->has('key2'))->toBeTrue();
        expect($cache->has('key3'))->toBeTrue();
        expect($cache->has('key4'))->toBeTrue();
        expect($cache->size())->toBe(3);
    });

    it('updates usage on get', function(): void {
        $cache = new LruCache(3);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        // Access key1 to make it recently used
        $cache->get('key1');

        // Add key4, should remove key2 (now least recently used)
        $cache->set('key4', 'value4');

        expect($cache->has('key1'))->toBeTrue(); // Still there (recently accessed)
        expect($cache->has('key2'))->toBeFalse(); // Removed (least recently used)
        expect($cache->has('key3'))->toBeTrue();
        expect($cache->has('key4'))->toBeTrue();
    });

    it('clears all entries', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        expect($cache->size())->toBe(3);

        $cache->clear();

        expect($cache->size())->toBe(0);
        expect($cache->has('key1'))->toBeFalse();
        expect($cache->has('key2'))->toBeFalse();
        expect($cache->has('key3'))->toBeFalse();
    });

    it('reports correct size and max size', function(): void {
        $cache = new LruCache(5);

        expect($cache->size())->toBe(0);
        expect($cache->maxSize())->toBe(5);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        expect($cache->size())->toBe(2);
        expect($cache->maxSize())->toBe(5);
    });

    it('provides cache statistics', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $stats = $cache->stats();

        expect($stats)->toBe([
            'size' => 3,
            'max_size' => 10,
            'usage_percentage' => 30.0,
        ]);
    });

    it('handles cache size of 1', function(): void {
        $cache = new LruCache(1);

        $cache->set('key1', 'value1');

        expect($cache->get('key1'))->toBe('value1');

        $cache->set('key2', 'value2');
        expect($cache->has('key1'))->toBeFalse();
        expect($cache->get('key2'))->toBe('value2');
    });

    it('stores different value types', function(): void {
        $cache = new LruCache(10);

        $cache->set('string', 'value');
        $cache->set('int', 42);
        $cache->set('float', 3.14);
        $cache->set('bool', true);
        $cache->set('array', ['a', 'b', 'c']);
        $cache->set('null', null);

        expect($cache->get('string'))->toBe('value');
        expect($cache->get('int'))->toBe(42);
        expect($cache->get('float'))->toBe(3.14);
        expect($cache->get('bool'))->toBeTrue();
        expect($cache->get('array'))->toBe(['a', 'b', 'c']);
        expect($cache->get('null'))->toBeNull();
    });

    it('handles complex LRU scenario', function(): void {
        $cache = new LruCache(3);

        // Fill cache
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);

        // Access 'a' and 'b' to make them recently used
        $cache->get('a');
        $cache->get('b');

        // Add 'd', should remove 'c' (least recently used)
        $cache->set('d', 4);

        expect($cache->has('a'))->toBeTrue();
        expect($cache->has('b'))->toBeTrue();
        expect($cache->has('c'))->toBeFalse();
        expect($cache->has('d'))->toBeTrue();

        // Access 'a' again
        $cache->get('a');

        // Add 'e', should remove 'b' (now least recently used)
        $cache->set('e', 5);

        expect($cache->has('a'))->toBeTrue();
        expect($cache->has('b'))->toBeFalse();
        expect($cache->has('d'))->toBeTrue();
        expect($cache->has('e'))->toBeTrue();
    });
});

