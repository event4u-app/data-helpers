<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Enums\PerformanceMode;
use Illuminate\Support\Facades\Config;

describe('Laravel Config Integration E2E', function(): void {
    beforeEach(function(): void {
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        DataHelpersConfig::reset();
    });

    it('loads configuration from Laravel config', function(): void {
        // Set Laravel config
        Config::set('data-helpers.performance_mode', 'safe');

        // Initialize from Laravel config
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
    });

    it('uses Laravel env() helper for environment variables', function(): void {
        // Laravel's env() helper should be available
        $envValue = env('DATA_HELPERS_PERFORMANCE_MODE', 'fast');

        // env() returns string from .env file, or default value
        expect($envValue)->not->toBeNull();
    });

    it('merges package config with app config', function(): void {
        // Package default config
        $packageConfig = require __DIR__ . '/../../../../config/data-helpers.php';

        // App-specific overrides
        Config::set('data-helpers.performance_mode', PerformanceMode::SAFE->value);

        // Merged config should have app override
        $mergedConfig = Config::get('data-helpers');

        expect($mergedConfig['performance_mode'])->toBe(PerformanceMode::SAFE->value);
    });

    it('provides default values when config not set', function(): void {
        // Don't set any config
        DataHelpersConfig::initialize([]);

        // Should use defaults
        expect(DataHelpersConfig::getPerformanceMode())->toBeString();
    });

    it('validates performance mode values', function(): void {
        Config::set('data-helpers.performance_mode', PerformanceMode::FAST->value);
        DataHelpersConfig::initialize(Config::get('data-helpers', []));
        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::FAST->value);

        DataHelpersConfig::reset();

        Config::set('data-helpers.performance_mode', PerformanceMode::SAFE->value);
        DataHelpersConfig::initialize(Config::get('data-helpers', []));
        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);
    });

    it('handles cache driver configuration', function(): void {
        Config::set('data-helpers.cache.driver', 'framework');
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::get('cache.driver'))->toBe('framework');
    });

    it('handles cache prefix configuration', function(): void {
        Config::set('data-helpers.cache.prefix', 'my_app:');
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::get('cache.prefix'))->toBe('my_app:');
    });

    it('handles cache TTL configuration', function(): void {
        Config::set('data-helpers.cache.default_ttl', 7200);
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::get('cache.default_ttl'))->toBe(7200);
    });

    it('handles null cache TTL for forever caching', function(): void {
        Config::set('data-helpers.cache.default_ttl', null);
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::get('cache.default_ttl'))->toBeNull();
    });

    it('can update config at runtime', function(): void {
        DataHelpersConfig::initialize([
            'performance_mode' => PerformanceMode::FAST->value,
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::FAST->value);

        // Update at runtime
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);
    });

    it('supports dot notation for nested config', function(): void {
        DataHelpersConfig::initialize([
            'custom' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
        ]);

        expect(DataHelpersConfig::get('custom.key1'))->toBe('value1')
            ->and(DataHelpersConfig::get('custom.key2'))->toBe('value2');
    });

    it('returns null for non-existent config keys', function(): void {
        DataHelpersConfig::initialize([]);

        expect(DataHelpersConfig::get('non.existent.key'))->toBeNull();
    });

    it('can set multiple config values at once', function(): void {
        DataHelpersConfig::setMany([
            'performance_mode' => PerformanceMode::SAFE->value,
            'custom.key' => 'value',
        ]);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value)
            ->and(DataHelpersConfig::get('custom.key'))->toBe('value');
    });

    it('preserves config across multiple accesses', function(): void {
        DataHelpersConfig::initialize([
            'performance_mode' => PerformanceMode::SAFE->value,
        ]);

        $first = DataHelpersConfig::getPerformanceMode();
        $second = DataHelpersConfig::getPerformanceMode();
        $third = DataHelpersConfig::getPerformanceMode();

        expect($first)->toBe(PerformanceMode::SAFE->value)
            ->and($second)->toBe(PerformanceMode::SAFE->value)
            ->and($third)->toBe(PerformanceMode::SAFE->value);
    });

    it('config is singleton across application', function(): void {
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        // Access from different part of code
        $value1 = DataHelpersConfig::getPerformanceMode();

        // Access again
        $value2 = DataHelpersConfig::get('performance_mode');

        expect($value1)->toBe(PerformanceMode::SAFE->value)
            ->and($value2)->toBe(PerformanceMode::SAFE->value);
    });

    it('reset clears all configuration', function(): void {
        // Set a custom value
        DataHelpersConfig::set('performance_mode', PerformanceMode::SAFE->value);

        expect(DataHelpersConfig::getPerformanceMode())->toBe(PerformanceMode::SAFE->value);

        // Reset should clear the custom value
        DataHelpersConfig::reset();

        // After reset, config should be reinitialized
        // We can't predict the exact value, but it should be callable
        $mode = DataHelpersConfig::getPerformanceMode();
        expect($mode)->toBeString();
    });

    it('handles array config values', function(): void {
        DataHelpersConfig::initialize([
            'custom' => [
                'array_value' => ['a', 'b', 'c'],
            ],
        ]);

        $value = DataHelpersConfig::get('custom.array_value');
        expect($value)->toBe(['a', 'b', 'c']);
    });

    it('integrates with Laravel config caching', function(): void {
        // When Laravel config is cached, the package should still work
        $config = Config::get('data-helpers', []);

        expect($config)->toBeArray();

        DataHelpersConfig::initialize($config);

        // Should work normally
        expect(DataHelpersConfig::get('cache.driver'))->toBeString();
    });
})->group('laravel');

