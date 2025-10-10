<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\Drivers\NoneDriver;

describe('NoneDriver', function(): void {
    beforeEach(function(): void {
        $this->driver = new NoneDriver();
    });

    it('always returns null for get', function(): void {
        $this->driver->set('key1', 'value1');
        expect($this->driver->get('key1'))->toBeNull();
    });

    it('always returns false for has', function(): void {
        $this->driver->set('key1', 'value1');
        expect($this->driver->has('key1'))->toBeFalse();
    });

    it('delete does nothing', function(): void {
        $this->driver->delete('key1');
        expect(true)->toBeTrue(); // Just verify no exception
    });

    it('clear does nothing', function(): void {
        $this->driver->clear();
        expect(true)->toBeTrue(); // Just verify no exception
    });

    it('returns empty stats', function(): void {
        $stats = $this->driver->getStats();

        expect($stats['hits'])->toBe(0);
        expect($stats['misses'])->toBe(0);
        expect($stats['size'])->toBe(0);
        expect($stats['max_size'])->toBeNull();
    });
});

