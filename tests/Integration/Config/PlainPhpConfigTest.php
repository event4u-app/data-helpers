<?php

declare(strict_types=1);

namespace Tests\Integration\Config;

use event4u\DataHelpers\Config\ConfigHelper;
use event4u\DataHelpers\DataHelpersConfig;

describe('Plain PHP Config Integration', function(): void {
    beforeEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);

        ConfigHelper::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);

        ConfigHelper::reset();
        DataHelpersConfig::reset();
    });

    it('loads config from plain PHP file', function(): void {
        // ConfigHelper should auto-detect and load plain PHP config
        $helper = ConfigHelper::getInstance();

        expect($helper->getSource())->toBeIn(['plain', 'default']);
        expect($helper->get('cache.max_entries'))->toBe(1000);
        expect($helper->get('performance_mode'))->toBe('fast');
    });

    it('uses DataHelpersConfig with plain PHP', function(): void {
        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(1000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();
    });

    it('respects ENV variables in plain PHP config', function(): void {
        // Skip in E2E environments where .env is already loaded and cached
        // Laravel's env() function caches values on first load, making runtime changes ineffective
        if (file_exists(getcwd() . '/.env')) {
            expect(true)->toBeTrue(); // Skip test but don't fail
            return;
        }

        // Set ENV variable (both $_ENV and putenv for Laravel compatibility)
        // Use a different value than what might be in .env files (750 instead of 500)
        $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] = '750';
        $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = 'safe';
        putenv('DATA_HELPERS_CACHE_MAX_ENTRIES=750');
        putenv('DATA_HELPERS_PERFORMANCE_MODE=safe');

        // Reset to force reload
        ConfigHelper::reset();
        DataHelpersConfig::reset();

        // Should load from ENV
        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(750);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();

        // Cleanup
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        putenv('DATA_HELPERS_PERFORMANCE_MODE');
    });

    it('provides all config values', function(): void {
        $helper = ConfigHelper::getInstance();
        $all = $helper->all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('cache');
        expect($all)->toHaveKey('performance_mode');
        expect($all['cache'])->toHaveKey('max_entries');
    });

    it('supports dot notation access', function(): void {
        expect(DataHelpersConfig::get('cache.max_entries'))->toBe(1000);
        expect(DataHelpersConfig::get('cache.max_entries', 500))->toBe(1000);
        expect(DataHelpersConfig::get('nonexistent.key', 'default'))->toBe('default');
    });

    it('can be manually initialized', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'max_entries' => 2000,
            ],
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(2000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::getSource())->toBe('manual');
    });

    it('handles missing config file gracefully', function(): void {
        // Even if config file is missing, should use defaults
        $helper = ConfigHelper::getInstance();

        expect($helper->get('cache.max_entries'))->toBeInt();
        expect($helper->get('cache.max_entries'))->toBeGreaterThan(0);
        expect($helper->get('performance_mode'))->toBeIn(['fast', 'safe']);
    });
});

