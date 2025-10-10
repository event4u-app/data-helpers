<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use Illuminate\Support\Facades\Cache;

/**
 * @group laravel
 */
describe('LaravelCacheDriver', function(): void {
    beforeEach(function(): void {
        setupLaravelCache();
        Cache::flush();
        $this->driver = new LaravelCacheDriver('test_prefix:');
    });

    afterEach(function(): void {
        Cache::flush();
        teardownLaravelCache();
    });

    it('stores and retrieves values', function(): void {
        $this->driver->set('key1', 'value1');
        expect($this->driver->get('key1'))->toBe('value1');
    });

    it('returns null for non-existent keys', function(): void {
        expect($this->driver->get('non-existent'))->toBeNull();
    });

    it('checks if key exists', function(): void {
        $this->driver->set('key1', 'value1');
        expect($this->driver->has('key1'))->toBeTrue();
        expect($this->driver->has('non-existent'))->toBeFalse();
    });

    it('deletes values', function(): void {
        $this->driver->set('key1', 'value1');
        $this->driver->delete('key1');

        expect($this->driver->get('key1'))->toBeNull();
    });

    it('clears all values', function(): void {
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');
        $this->driver->clear();

        expect($this->driver->get('key1'))->toBeNull();
        expect($this->driver->get('key2'))->toBeNull();
    });

    it('stores values with TTL', function(): void {
        $this->driver->set('key1', 'value1', 60);
        expect($this->driver->get('key1'))->toBe('value1');
    });

    it('stores values forever when TTL is null', function(): void {
        $this->driver->set('key1', 'value1', null);
        expect($this->driver->get('key1'))->toBe('value1');
    });

    it('uses prefix for cache keys', function(): void {
        $this->driver->set('key1', 'value1');

        // Check that Laravel cache has the prefixed key
        expect(Cache::has('test_prefix:key1'))->toBeTrue();
        expect(Cache::get('test_prefix:key1'))->toBe('value1');
    });

    it('returns stats', function(): void {
        $this->driver->set('key1', 'value1');
        $this->driver->get('key1'); // Hit
        $this->driver->get('non-existent'); // Miss

        $stats = $this->driver->getStats();

        expect($stats)->toHaveKey('hits');
        expect($stats)->toHaveKey('misses');
        expect($stats)->toHaveKey('size');
        expect($stats)->toHaveKey('max_size');
        expect($stats['hits'])->toBe(1);
        expect($stats['misses'])->toBe(1);
    });
})->group('laravel');

