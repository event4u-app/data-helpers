<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheInterface;
use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\Cache\Drivers\NoneDriver;
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

    it('creates none driver', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'none',
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(NoneDriver::class);
    });

    it('creates framework driver (falls back to memory when no framework is active)', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'max_entries' => 100,
                'prefix' => 'test:',
            ],
        ]);

        $cache = CacheManager::getInstance();
        // In unit test environment, no framework is active, so it falls back to memory
        expect($cache)->toBeInstanceOf(MemoryDriver::class);
    });

    it('returns same instance on multiple calls', function(): void {
        $cache1 = CacheManager::getInstance();
        $cache2 = CacheManager::getInstance();

        expect($cache1)->toBe($cache2);
    });

    it('allows setting custom cache instance', function(): void {
        $customCache = new NoneDriver();
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
            ->toThrow(
                InvalidArgumentException::class,
                'Unknown cache driver: unknown-driver. Supported: memory, framework, none'
            );
    });
});

