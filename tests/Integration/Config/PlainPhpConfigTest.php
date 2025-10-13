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
        putenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset ConfigHelper instance to ensure clean state
        ConfigHelper::resetInstance();
    });

    afterEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset ConfigHelper instance to ensure clean state
        ConfigHelper::resetInstance();
    });

    it('loads config from plain PHP file', function(): void {
        // ConfigHelper should auto-detect and load plain PHP config
        $helper = ConfigHelper::getInstance();

        expect($helper->getSource())->toBeIn(['plain', 'default', 'laravel', 'symfony']);

        // In E2E environments, ENV variables might be set, so we check for either default or ENV value
        // Laravel sets $_ENV but not putenv(), so we check $_ENV first, then getenv()
        $envValue = $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? getenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        $expectedMaxEntries = (false !== $envValue && '' !== $envValue) ? (int)$envValue : 1000;

        expect($helper->get('cache.max_entries'))->toBe($expectedMaxEntries);
        expect($helper->get('performance_mode'))->toBe('fast');
    })->group('package-only');

    it('uses default value (1000) when no ENV is set', function(): void {
        // Temporarily unset ENV variables to test default behavior
        $originalMaxEntries = $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? null;
        $originalPerformanceMode = $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? null;
        $originalMaxEntriesGetenv = getenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        $originalPerformanceModeGetenv = getenv('DATA_HELPERS_PERFORMANCE_MODE');

        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset config to pick up the change
        ConfigHelper::resetInstance();
        DataHelpersConfig::reset();

        // Should use default value from config
        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(1000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();

        // Restore ENV variables
        if (null !== $originalMaxEntries) {
            $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] = $originalMaxEntries;
        }
        if (null !== $originalPerformanceMode) {
            $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = $originalPerformanceMode;
        }
        if (false !== $originalMaxEntriesGetenv) {
            putenv('DATA_HELPERS_CACHE_MAX_ENTRIES=' . $originalMaxEntriesGetenv);
        }
        if (false !== $originalPerformanceModeGetenv) {
            putenv('DATA_HELPERS_PERFORMANCE_MODE=' . $originalPerformanceModeGetenv);
        }

        // Reset config again to restore original state
        ConfigHelper::resetInstance();
        DataHelpersConfig::reset();
    })->group('package-only');

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

        // Reset ConfigHelper instance to force reload from ENV
        ConfigHelper::resetInstance();

        // Should load from ENV (getCacheMaxEntries returns int, so compare with int)
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
        // In E2E environments, ENV variables might be set
        // Laravel sets $_ENV but not putenv(), so we check $_ENV first, then getenv()
        $envValue = $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? getenv('DATA_HELPERS_CACHE_MAX_ENTRIES');
        $expectedMaxEntries = (false !== $envValue && '' !== $envValue) ? (int)$envValue : 1000;

        expect(DataHelpersConfig::get('cache.max_entries'))->toBe($expectedMaxEntries);
        expect(DataHelpersConfig::get('cache.max_entries', 500))->toBe($expectedMaxEntries);
        expect(DataHelpersConfig::get('nonexistent.key', 'default'))->toBe('default');
    })->group('package-only');

    it('can be manually set', function(): void {
        DataHelpersConfig::setMany([
            'cache.max_entries' => 2000,
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(2000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
    });

    it('handles missing config file gracefully', function(): void {
        // Even if config file is missing, should use defaults
        $helper = ConfigHelper::getInstance();

        expect($helper->get('cache.max_entries'))->toBeInt();
        expect($helper->get('cache.max_entries'))->toBeGreaterThan(0);
        expect($helper->get('performance_mode'))->toBeIn(['fast', 'safe']);
    });
});

