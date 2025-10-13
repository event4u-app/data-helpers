<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheHelper;
use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\ClassScopedCache;
use event4u\DataHelpers\DataHelpersConfig;

describe('ClassScopedCache', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 1000,
        ]);
    });

    afterEach(function(): void {
        CacheHelper::clear();
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('sets and gets values scoped to class', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1');
        expect(ClassScopedCache::get('MyClass', 'key1'))->toBe('value1');
    });

    it('isolates cache between different classes', function(): void {
        ClassScopedCache::set('ClassA', 'key1', 'valueA');
        ClassScopedCache::set('ClassB', 'key1', 'valueB');

        expect(ClassScopedCache::get('ClassA', 'key1'))->toBe('valueA');
        expect(ClassScopedCache::get('ClassB', 'key1'))->toBe('valueB');
    });

    it('checks if key exists', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1');
        expect(ClassScopedCache::has('MyClass', 'key1'))->toBeTrue();
        expect(ClassScopedCache::has('MyClass', 'missing'))->toBeFalse();
    });

    it('deletes values', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1');
        ClassScopedCache::delete('MyClass', 'key1');
        expect(ClassScopedCache::has('MyClass', 'key1'))->toBeFalse();
    });

    it('clears all values for a class', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1');
        ClassScopedCache::set('MyClass', 'key2', 'value2');
        ClassScopedCache::set('OtherClass', 'key1', 'other');

        ClassScopedCache::clearClass('MyClass');

        expect(ClassScopedCache::has('MyClass', 'key1'))->toBeFalse();
        expect(ClassScopedCache::has('MyClass', 'key2'))->toBeFalse();
        expect(ClassScopedCache::has('OtherClass', 'key1'))->toBeTrue();
    });

    it('remembers values', function(): void {
        $result = ClassScopedCache::remember('MyClass', 'key1', 'computed-value');
        expect($result)->toBe('computed-value');
        expect(ClassScopedCache::get('MyClass', 'key1'))->toBe('computed-value');
    });

    it('remembers values with callback', function(): void {
        $called = false;
        $result = ClassScopedCache::remember('MyClass', 'key1', function() use (&$called): string {
            $called = true;
            return 'computed-value';
        });

        expect($result)->toBe('computed-value');
        expect($called)->toBeTrue();
        expect(ClassScopedCache::get('MyClass', 'key1'))->toBe('computed-value');
    });

    it('does not call callback if value exists', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'existing-value');

        $called = false;
        $result = ClassScopedCache::remember('MyClass', 'key1', function() use (&$called): string {
            $called = true;
            return 'new-value';
        });

        expect($result)->toBe('existing-value');
        expect($called)->toBeFalse();
    });

    it('evicts least recently used entry when max entries reached', function(): void {
        // Set 3 entries with max 3
        ClassScopedCache::set('MyClass', 'key1', 'value1', null, 3);
        ClassScopedCache::set('MyClass', 'key2', 'value2', null, 3);
        ClassScopedCache::set('MyClass', 'key3', 'value3', null, 3);

        // Access key1 to make it more recently used
        ClassScopedCache::get('MyClass', 'key1');

        // Add key4, should evict key2 (least recently used)
        ClassScopedCache::set('MyClass', 'key4', 'value4', null, 3);

        expect(ClassScopedCache::has('MyClass', 'key1'))->toBeTrue();
        expect(ClassScopedCache::has('MyClass', 'key2'))->toBeFalse();
        expect(ClassScopedCache::has('MyClass', 'key3'))->toBeTrue();
        expect(ClassScopedCache::has('MyClass', 'key4'))->toBeTrue();
    });

    it('updates timestamp on read', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1', null, 3);
        ClassScopedCache::set('MyClass', 'key2', 'value2', null, 3);
        ClassScopedCache::set('MyClass', 'key3', 'value3', null, 3);

        // Read key1 multiple times to keep it fresh
        ClassScopedCache::get('MyClass', 'key1');
        ClassScopedCache::get('MyClass', 'key1');

        // Add key4, should evict key2 (not key1, because key1 was accessed)
        ClassScopedCache::set('MyClass', 'key4', 'value4', null, 3);

        expect(ClassScopedCache::has('MyClass', 'key1'))->toBeTrue();
        expect(ClassScopedCache::has('MyClass', 'key2'))->toBeFalse();
    });

    it('returns class statistics', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1');
        ClassScopedCache::set('MyClass', 'key2', 'value2');

        $stats = ClassScopedCache::getClassStats('MyClass');

        expect($stats)->toHaveKey('count');
        expect($stats)->toHaveKey('keys');
        expect($stats['count'])->toBe(2);
        expect($stats['keys'])->toContain('key1');
        expect($stats['keys'])->toContain('key2');
    });

    it('handles different max entries per class', function(): void {
        // ClassA with max 2 entries
        ClassScopedCache::set('ClassA', 'key1', 'value1', null, 2);
        ClassScopedCache::set('ClassA', 'key2', 'value2', null, 2);
        ClassScopedCache::set('ClassA', 'key3', 'value3', null, 2); // Should evict key1

        // ClassB with max 3 entries
        ClassScopedCache::set('ClassB', 'key1', 'value1', null, 3);
        ClassScopedCache::set('ClassB', 'key2', 'value2', null, 3);
        ClassScopedCache::set('ClassB', 'key3', 'value3', null, 3);

        expect(ClassScopedCache::has('ClassA', 'key1'))->toBeFalse();
        expect(ClassScopedCache::has('ClassA', 'key2'))->toBeTrue();
        expect(ClassScopedCache::has('ClassA', 'key3'))->toBeTrue();

        expect(ClassScopedCache::has('ClassB', 'key1'))->toBeTrue();
        expect(ClassScopedCache::has('ClassB', 'key2'))->toBeTrue();
        expect(ClassScopedCache::has('ClassB', 'key3'))->toBeTrue();
    });

    it('supports TTL', function(): void {
        ClassScopedCache::set('MyClass', 'key1', 'value1', 3600);
        expect(ClassScopedCache::get('MyClass', 'key1'))->toBe('value1');
    });

    it('returns default value for missing keys', function(): void {
        expect(ClassScopedCache::get('MyClass', 'missing', 'default'))->toBe('default');
    });
});

