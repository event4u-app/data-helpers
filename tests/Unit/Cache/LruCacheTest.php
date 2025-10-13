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

        $stats = $cache->getStats();

        expect($stats)->toBeArray()
            ->and($stats['size'])->toBe(3)
            ->and($stats['max_size'])->toBe(10)
            ->and($stats)->toHaveKey('hits')
            ->and($stats)->toHaveKey('misses');
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

    it('supports TTL (time to live)', function(): void {
        $cache = new LruCache(10);

        // Set with very short TTL for faster tests
        $cache->set('temp', 'value', -1); // Already expired

        // Should be expired immediately
        expect($cache->has('temp'))->toBeFalse();
        expect($cache->get('temp'))->toBeNull();
    });

    it('handles entries without TTL (permanent)', function(): void {
        $cache = new LruCache(10);

        // Set without TTL
        $cache->set('permanent', 'value');

        expect($cache->has('permanent'))->toBeTrue();
        expect($cache->get('permanent'))->toBe('value');

        // Permanent entries don't expire (no sleep needed for test)
        expect($cache->has('permanent'))->toBeTrue();
        expect($cache->get('permanent'))->toBe('value');
    });

    it('mixes entries with and without TTL', function(): void {
        $cache = new LruCache(10);

        $cache->set('permanent', 'perm_value');
        $cache->set('temp', 'temp_value', -1); // Already expired

        // Permanent should exist, temp should be expired
        expect($cache->has('permanent'))->toBeTrue();
        expect($cache->has('temp'))->toBeFalse();
    });

    it('updates TTL when setting existing key', function(): void {
        $cache = new LruCache(10);

        // Set with expired TTL
        $cache->set('key', 'value1', -1);

        // Update with no TTL (permanent)
        $cache->set('key', 'value2');

        // Should exist now (no TTL)
        expect($cache->has('key'))->toBeTrue();
        expect($cache->get('key'))->toBe('value2');
    });

    it('removes TTL when updating with null TTL', function(): void {
        $cache = new LruCache(10);

        // Set with TTL
        $cache->set('key', 'value1', 10);

        // Update with null TTL (remove expiration)
        $cache->set('key', 'value2', null);

        // Should not expire (permanent now)
        expect($cache->has('key'))->toBeTrue();
        expect($cache->get('key'))->toBe('value2');
    });

    it('clears expirations when clearing cache', function(): void {
        $cache = new LruCache(10);

        $cache->set('temp1', 'value1', 10);
        $cache->set('temp2', 'value2', 10);

        expect($cache->size())->toBe(2);

        $cache->clear();

        expect($cache->size())->toBe(0);

        // Add new entry - should work fine
        $cache->set('new', 'value');
        expect($cache->has('new'))->toBeTrue();
    });

    it('deletes expiration when deleting key', function(): void {
        $cache = new LruCache(10);

        $cache->set('temp', 'value', 10);

        expect($cache->has('temp'))->toBeTrue();

        $cache->delete('temp');

        expect($cache->has('temp'))->toBeFalse();

        // Re-add with different TTL - should work fine
        $cache->set('temp', 'new_value', 5);
        expect($cache->has('temp'))->toBeTrue();
    });

    it('expired entries are removed on access', function(): void {
        $cache = new LruCache(10);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2', -1); // Already expired
        $cache->set('key3', 'value3');

        // Accessing key2 should return null and remove it
        expect($cache->get('key2'))->toBeNull();
        expect($cache->has('key2'))->toBeFalse();

        // Other keys should still exist
        expect($cache->has('key1'))->toBeTrue();
        expect($cache->has('key3'))->toBeTrue();
    });

    it('handles null values with TTL', function(): void {
        $cache = new LruCache(10);

        // Store null with expired TTL
        $cache->set('null_key', null, -1);

        // Should be expired (even though value is null)
        expect($cache->has('null_key'))->toBeFalse();
    });
});

