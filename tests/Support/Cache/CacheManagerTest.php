<?php

declare(strict_types=1);

use event4u\DataHelpers\Enums\CacheDriver;
use event4u\DataHelpers\Support\Cache\CacheManager;

beforeEach(function(): void {
    // Reset cache manager before each test
    CacheManager::reset();
    CacheManager::clear();
});

afterEach(function(): void {
    // Clean up after each test
    CacheManager::clear();
    CacheManager::reset();
});

it('can detect filesystem driver as fallback', function(): void {
    $driver = CacheManager::getDriver();

    expect($driver)->toBe(CacheDriver::FILESYSTEM);
});

it('can store and retrieve values', function(): void {
    $key = 'test_key';
    $value = ['foo' => 'bar', 'nested' => ['data' => 123]];

    $result = CacheManager::set($key, $value);
    expect($result)->toBeTrue();

    $retrieved = CacheManager::get($key);
    expect($retrieved)->toBe($value);
});

it('returns default value for missing keys', function(): void {
    $default = 'default_value';
    $retrieved = CacheManager::get('non_existent_key', $default);

    expect($retrieved)->toBe($default);
});

it('can check if key exists', function(): void {
    $key = 'exists_key';

    expect(CacheManager::has($key))->toBeFalse();

    CacheManager::set($key, 'value');

    expect(CacheManager::has($key))->toBeTrue();
});

it('can delete values', function(): void {
    $key = 'delete_key';

    CacheManager::set($key, 'value');
    expect(CacheManager::has($key))->toBeTrue();

    $result = CacheManager::delete($key);
    expect($result)->toBeTrue();
    expect(CacheManager::has($key))->toBeFalse();
});

it('can clear all cache', function(): void {
    CacheManager::set('key1', 'value1');
    CacheManager::set('key2', 'value2');
    CacheManager::set('key3', 'value3');

    expect(CacheManager::has('key1'))->toBeTrue();
    expect(CacheManager::has('key2'))->toBeTrue();
    expect(CacheManager::has('key3'))->toBeTrue();

    $result = CacheManager::clear();
    expect($result)->toBeTrue();

    expect(CacheManager::has('key1'))->toBeFalse();
    expect(CacheManager::has('key2'))->toBeFalse();
    expect(CacheManager::has('key3'))->toBeFalse();
});

it('respects TTL for cache entries', function(): void {
    $key = 'ttl_key';
    $value = 'ttl_value';
    $ttl = 1; // 1 second

    CacheManager::set($key, $value, $ttl);
    expect(CacheManager::get($key))->toBe($value);

    // Wait for TTL to expire
    sleep(2);

    expect(CacheManager::get($key))->toBeNull();
});

it('can handle complex data structures', function(): void {
    $key = 'complex_key';
    $value = [
        'string' => 'test',
        'int' => 123,
        'float' => 45.67,
        'bool' => true,
        'null' => null,
        'array' => [1, 2, 3],
        'nested' => [
            'deep' => [
                'value' => 'nested_value',
            ],
        ],
    ];

    CacheManager::set($key, $value);
    $retrieved = CacheManager::get($key);

    expect($retrieved)->toBe($value);
});

it('handles cache misses gracefully', function(): void {
    $retrieved = CacheManager::get('non_existent_key');

    expect($retrieved)->toBeNull();
});

it('can store objects', function(): void {
    $key = 'object_key';
    $value = (object)['foo' => 'bar', 'baz' => 123];

    CacheManager::set($key, $value);
    $retrieved = CacheManager::get($key);

    expect($retrieved)->toEqual($value);
});
