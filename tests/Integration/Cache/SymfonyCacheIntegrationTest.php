<?php

declare(strict_types=1);

use event4u\DataHelpers\Cache\CacheManager;
use event4u\DataHelpers\Cache\Drivers\SymfonyCacheDriver;
use event4u\DataHelpers\DataHelpersConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @group symfony
 */
describe('Symfony Cache Integration', function(): void {
    beforeEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        CacheManager::reset();
        DataHelpersConfig::reset();
    });

    it('creates Symfony cache driver directly', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool);

        expect($cache)->toBeInstanceOf(SymfonyCacheDriver::class);
    });

    it('stores and retrieves values via Symfony cache', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool);

        $cache->set('test_key', 'test_value');

        expect($cache->get('test_key'))->toBe('test_value');
    });

    it('respects Symfony cache configuration', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool);

        $cache->set('key1', 'value1');
        $cache->set('key2', ['nested' => 'array']);
        $cache->set('key3', 12345);

        expect($cache->get('key1'))->toBe('value1');
        expect($cache->get('key2'))->toBe(['nested' => 'array']);
        expect($cache->get('key3'))->toBe(12345);
    });

    it('works with Symfony cache TTL', function(): void {
        $pool = new ArrayAdapter();
        $cache = new SymfonyCacheDriver($pool);

        $cache->set('ttl_key', 'ttl_value', 3600);

        expect($cache->get('ttl_key'))->toBe('ttl_value');
    });

    it('uses Symfony driver when pool is configured with framework driver', function(): void {
        $pool = new ArrayAdapter();

        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'symfony' => [
                    'pool' => $pool,
                ],
            ],
        ]);

        $cache = CacheManager::getInstance();
        expect($cache)->toBeInstanceOf(SymfonyCacheDriver::class);
    });
})->group('symfony');
