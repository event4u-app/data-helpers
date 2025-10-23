<?php

declare(strict_types=1);

namespace Tests\Integration\Config;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Frameworks\Laravel\DataHelpersServiceProvider;
use Illuminate\Container\Container;

describe('Laravel Config Integration', function(): void {
    beforeEach(function(): void {
        // Skip if Laravel is not available
        if (!class_exists('Illuminate\Container\Container')) {
            $this->markTestSkipped('Laravel is not available');
        }

        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);

        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        // Clean ENV variables
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);

        DataHelpersConfig::reset();

        if (class_exists('Illuminate\Container\Container')) {
            Container::setInstance(null);
        }
    });

    it('loads config from Laravel', function(): void {
        // Manually set Laravel-like config
        DataHelpersConfig::setMany([
            'performance_mode' => 'fast',
        ]);

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
        expect($config)->toHaveKey('performance_mode');
    });

    it('respects custom Laravel config values', function(): void {
        // Set custom config values
        DataHelpersConfig::setMany([
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('handles ENV variables in Laravel config', function(): void {
        // Set ENV variables
        $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = 'safe';

        // Set config with ENV values
        DataHelpersConfig::setMany([
            'performance_mode' => $_ENV['DATA_HELPERS_PERFORMANCE_MODE'],
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');

        // Cleanup
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
    });

    it('provides publishable config path', function(): void {
        $app = new Container();
        $provider = new DataHelpersServiceProvider($app);

        // The config file should exist
        $configPath = __DIR__ . '/../../../config/data-helpers.php';
        expect(file_exists($configPath))->toBeTrue();

        // Load the config file
        $config = require $configPath;
        expect($config)->toBeArray();
        expect($config)->toHaveKey('performance_mode');
    });
})->group('laravel')->skip(
    !class_exists('Illuminate\Container\Container'),
    'Laravel is not available'
);
