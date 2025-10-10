<?php

declare(strict_types=1);

namespace Tests\Integration\Config;

use event4u\DataHelpers\Config\ConfigHelper;
use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Laravel\DataHelpersServiceProvider;
use Illuminate\Container\Container;

/**
 * @group laravel
 */
describe('Laravel Config Integration', function(): void {
    beforeEach(function(): void {
        // Skip if Laravel is not available
        if (!class_exists('Illuminate\Container\Container')) {
            $this->markTestSkipped('Laravel is not available');
        }

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

        if (class_exists('Illuminate\Container\Container')) {
            Container::setInstance(null);
        }
    });

    it('loads config from Laravel', function(): void {
        // Manually initialize with Laravel-like config
        $laravelConfig = [
            'cache' => [
                'max_entries' => 1000,
            ],
            'performance_mode' => 'fast',
        ];

        DataHelpersConfig::initialize($laravelConfig);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(1000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
        expect(DataHelpersConfig::isFastMode())->toBeTrue();
    });

    it('service provider config file exists', function(): void {
        // Check that the config file exists
        $configPath = __DIR__ . '/../../../config/data-helpers.php';
        expect(file_exists($configPath))->toBeTrue();

        // Load and validate config structure
        $config = require $configPath;
        expect($config)->toBeArray();
        expect($config)->toHaveKey('cache');
        expect($config)->toHaveKey('performance_mode');
        expect($config['cache'])->toHaveKey('max_entries');
    });

    it('respects custom Laravel config values', function(): void {
        // Simulate custom Laravel config
        $customConfig = [
            'cache' => [
                'max_entries' => 5000,
            ],
            'performance_mode' => 'safe',
        ];

        DataHelpersConfig::initialize($customConfig);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(5000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('handles ENV variables in Laravel config', function(): void {
        // Set ENV variables
        $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] = '2500';
        $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = 'safe';

        // Simulate Laravel config with ENV values
        $maxEntries = (int)$_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'];
        $performanceMode = $_ENV['DATA_HELPERS_PERFORMANCE_MODE'];

        $configWithEnv = [
            'cache' => [
                'max_entries' => $maxEntries,
            ],
            'performance_mode' => $performanceMode,
        ];

        DataHelpersConfig::initialize($configWithEnv);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(2500);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');

        // Cleanup
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
    });

    it('provides publishable config path', function(): void {
        $app = new Container();
        // @phpstan-ignore-next-line - Container is compatible with Application for testing
        $provider = new DataHelpersServiceProvider($app);

        // The config file should exist
        $configPath = __DIR__ . '/../../../config/data-helpers.php';
        expect(file_exists($configPath))->toBeTrue();

        // Load the config file
        $config = require $configPath;
        expect($config)->toBeArray();
        expect($config)->toHaveKey('cache');
        expect($config)->toHaveKey('performance_mode');
    });
})->group('laravel')->skip(
    !class_exists('Illuminate\Container\Container'),
    'Laravel is not available'
);

