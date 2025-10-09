<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheInterface;
use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\Cache\Drivers\NullDriver;
use event4u\DataHelpers\DataHelpersConfig;

describe('CacheManager', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('creates memory driver by default', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'memory',
                'max_entries' => 100,
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(MemoryDriver::class);
    });

    it('creates null driver', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'null',
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(NullDriver::class);
    });

    it('returns same instance on multiple calls', function(): void {
        $cache1 = CacheManager::getInstance();
        $cache2 = CacheManager::getInstance();

        expect($cache1)->toBe($cache2);
    });

    it('allows setting custom cache instance', function(): void {
        $customCache = new NullDriver();
        CacheManager::setInstance($customCache);

        $cache = CacheManager::getInstance();
        expect($cache)->toBe($customCache);
    });

    it('resets cache instance', function(): void {
        $cache1 = CacheManager::getInstance();
        CacheManager::reset();
        $cache2 = CacheManager::getInstance();

        expect($cache1)->not->toBe($cache2);
    });

    it('throws exception for unknown driver', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'unknown-driver',
            ],
        ]);

        expect(fn(): CacheInterface => CacheManager::getInstance())
            ->toThrow(InvalidArgumentException::class, 'Unknown cache driver: unknown-driver');
    });
});

