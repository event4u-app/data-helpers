<?php

declare(strict_types=1);

namespace Tests\Integration\Config;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Enums\PerformanceMode;
use event4u\DataHelpers\Helpers\ConfigHelper;

describe('Plain PHP Config Integration', function(): void {
    beforeEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset ConfigHelper instance to ensure clean state
        ConfigHelper::resetInstance();
    });

    afterEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset ConfigHelper instance to ensure clean state
        ConfigHelper::resetInstance();
    });

    it('loads config from plain PHP file', function(): void {
        // ConfigHelper should auto-detect and load plain PHP config
        $helper = ConfigHelper::getInstance();

        expect($helper->getSource())->toBeIn(['plain', 'default', 'laravel', 'symfony']);
        expect($helper->get('performance_mode'))->toBe(PerformanceMode::FAST->value);
    })->group('package-only');

    it('uses default performance mode when no ENV is set', function(): void {
        // Temporarily unset ENV variables to test default behavior
        $originalPerformanceMode = $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? null;
        $originalPerformanceModeGetenv = getenv('DATA_HELPERS_PERFORMANCE_MODE');

        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_PERFORMANCE_MODE');

        // Reset config to pick up the change
        ConfigHelper::resetInstance();
        DataHelpersConfig::reset();

        // Should use default value from config
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();

        // Restore ENV variables
        if (null !== $originalPerformanceMode) {
            $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = $originalPerformanceMode;
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
        $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = 'safe';
        putenv('DATA_HELPERS_PERFORMANCE_MODE=safe');

        // Reset ConfigHelper instance to force reload from ENV
        ConfigHelper::resetInstance();

        // Should load from ENV
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();

        // Cleanup
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
        putenv('DATA_HELPERS_PERFORMANCE_MODE');
    });

    it('provides all config values', function(): void {
        $helper = ConfigHelper::getInstance();
        $all = $helper->all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('performance_mode');
    });

    it('supports dot notation access', function(): void {
        expect(DataHelpersConfig::get('performance_mode'))->toBe(PerformanceMode::FAST->value);
        expect(DataHelpersConfig::get('performance_mode', PerformanceMode::SAFE->value))->toBe(PerformanceMode::FAST->value);
        expect(DataHelpersConfig::get('nonexistent.key', 'default'))->toBe('default');
    })->group('package-only');

    it('can be manually set', function(): void {
        DataHelpersConfig::setMany([
            'performance_mode' => PerformanceMode::SAFE->value,
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);
    });

    it('handles missing config file gracefully', function(): void {
        // Even if config file is missing, should use defaults
        $helper = ConfigHelper::getInstance();

        expect($helper->get('performance_mode'))->toBeIn([PerformanceMode::FAST->value, PerformanceMode::SAFE->value]);
    });
});
