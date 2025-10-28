<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\Cache\Adapters\NullCacheAdapter;

describe('NullCacheAdapter', function(): void {
    beforeEach(function(): void {
        $this->cache = new NullCacheAdapter();
    });

    it('always returns default value on get', function(): void {
        expect($this->cache->get('key'))->toBeNull();
        expect($this->cache->get('key', 'default'))->toBe('default');
        expect($this->cache->get('key', 123))->toBe(123);
    });

    it('always returns false on set', function(): void {
        expect($this->cache->set('key', 'value'))->toBeFalse();
        expect($this->cache->set('key', 'value', 3600))->toBeFalse();
    });

    it('always returns false on has', function(): void {
        expect($this->cache->has('key'))->toBeFalse();
        
        // Even after "setting" a value
        $this->cache->set('key', 'value');
        expect($this->cache->has('key'))->toBeFalse();
    });

    it('always returns false on delete', function(): void {
        expect($this->cache->delete('key'))->toBeFalse();
    });

    it('always returns true on clear', function(): void {
        expect($this->cache->clear())->toBeTrue();
    });

    it('does not cache anything', function(): void {
        // Set a value
        $this->cache->set('test', 'value');
        
        // Try to get it back
        expect($this->cache->get('test'))->toBeNull();
        expect($this->cache->get('test', 'default'))->toBe('default');
        
        // Check if it exists
        expect($this->cache->has('test'))->toBeFalse();
    });
});
