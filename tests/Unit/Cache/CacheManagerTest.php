<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheInterface;
use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\LaravelCacheDriver;
use event4u\DataHelpers\Cache\Drivers\MemoryDriver;
use event4u\DataHelpers\Cache\Drivers\NoneDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Illuminate\Foundation\Application;

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
        DataHelpersConfig::setMany([
            'cache.driver' => 'memory',
            'cache.max_entries' => 100,
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(MemoryDriver::class);
    });

    it('creates none driver', function(): void {
        DataHelpersConfig::set('cache.driver', 'none');

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(NoneDriver::class);
    });

    it('creates framework driver (uses Laravel if active, otherwise memory)', function(): void {
        DataHelpersConfig::setMany([
            'cache.driver' => 'framework',
            'cache.max_entries' => 100,
            'cache.prefix' => 'test:',
        ]);

        $cache = CacheManager::getInstance();

        // Check if Laravel is active (E2E environment)
        // @phpstan-ignore-next-line - Laravel Application class only available in E2E environment
        if (class_exists(Application::class) && function_exists('app')) {
            try {
                $app = app();
                if ($app instanceof Application) {
                    expect($cache)->toBeInstanceOf(LaravelCacheDriver::class);
                    return;
                }
            } catch (Throwable) {
                // Laravel not available, fall through to memory driver check
            }
        }

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
        DataHelpersConfig::set('cache.driver', 'unknown-driver');

        expect(fn(): CacheInterface => CacheManager::getInstance())
            ->toThrow(
                InvalidArgumentException::class,
                'Unknown cache driver: unknown-driver. Supported: memory, framework, none'
            );
    });
});

