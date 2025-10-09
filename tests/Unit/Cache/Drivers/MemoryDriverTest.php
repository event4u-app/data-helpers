<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\Drivers\MemoryDriver;

describe('MemoryDriver', function(): void {
    beforeEach(function(): void {
        $this->driver = new MemoryDriver(10);
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

    it('respects max size with LRU eviction', function(): void {
        $driver = new MemoryDriver(3);
        
        $driver->set('key1', 'value1');
        $driver->set('key2', 'value2');
        $driver->set('key3', 'value3');
        
        // Access key1 to make it more recently used
        $driver->get('key1');
        
        // Add key4, should evict key2 (least recently used)
        $driver->set('key4', 'value4');
        
        expect($driver->has('key1'))->toBeTrue();
        expect($driver->has('key2'))->toBeFalse();
        expect($driver->has('key3'))->toBeTrue();
        expect($driver->has('key4'))->toBeTrue();
    });

    it('returns stats', function(): void {
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');
        
        $stats = $this->driver->getStats();
        
        expect($stats)->toHaveKey('hits');
        expect($stats)->toHaveKey('misses');
        expect($stats)->toHaveKey('size');
        expect($stats)->toHaveKey('max_size');
        expect($stats['size'])->toBe(2);
        expect($stats['max_size'])->toBe(10);
    });

    it('ignores TTL parameter', function(): void {
        $this->driver->set('key1', 'value1', 60);
        expect($this->driver->get('key1'))->toBe('value1');
    });
});

