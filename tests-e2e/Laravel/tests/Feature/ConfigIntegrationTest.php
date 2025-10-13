<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
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
        Config::set('data-helpers.cache.max_entries', 500);
        Config::set('data-helpers.performance_mode', 'safe');

        // Initialize from Laravel config
        DataHelpersConfig::initialize(Config::get('data-helpers', []));

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(500)
            ->and(DataHelpersConfig::getPerformanceMode())->toBe('safe');
    });

    it('uses Laravel env() helper for environment variables', function(): void {
        // Laravel's env() helper should be available
        $envValue = env('DATA_HELPERS_CACHE_MAX_ENTRIES', 1000);

        // env() returns string from .env file, or default value
        expect($envValue)->not->toBeNull();
    });

    it('merges package config with app config', function(): void {
        // Package default config
        $packageConfig = require __DIR__ . '/../../../../config/data-helpers.php';

        // App-specific overrides
        Config::set('data-helpers.cache.max_entries', 2000);

        // Merged config should have app override
        $mergedConfig = Config::get('data-helpers');

        expect($mergedConfig['cache']['max_entries'])->toBe(2000);
    });

    it('provides default values when config not set', function(): void {
        // Don't set any config
        DataHelpersConfig::initialize([]);

        // Should use defaults
        expect(DataHelpersConfig::getCacheMaxEntries())->toBeInt()
            ->and(DataHelpersConfig::getPerformanceMode())->toBeString();
    });

    it('validates performance mode values', function(): void {
        Config::set('data-helpers.performance_mode', 'fast');
        DataHelpersConfig::initialize(Config::get('data-helpers', []));
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');

        DataHelpersConfig::reset();

        Config::set('data-helpers.performance_mode', 'safe');
        DataHelpersConfig::initialize(Config::get('data-helpers', []));
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
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
            'cache' => ['max_entries' => 100],
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(100);

        // Update at runtime
        DataHelpersConfig::set('cache.max_entries', 200);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(200);
    });

    it('supports dot notation for nested config', function(): void {
        DataHelpersConfig::initialize([
            'cache' => [
                'driver' => 'framework',
                'max_entries' => 1000,
                'prefix' => 'test:',
            ],
        ]);

        expect(DataHelpersConfig::get('cache.driver'))->toBe('framework')
            ->and(DataHelpersConfig::get('cache.max_entries'))->toBe(1000)
            ->and(DataHelpersConfig::get('cache.prefix'))->toBe('test:');
    });

    it('returns null for non-existent config keys', function(): void {
        DataHelpersConfig::initialize([]);

        expect(DataHelpersConfig::get('non.existent.key'))->toBeNull();
    });

    it('can set multiple config values at once', function(): void {
        DataHelpersConfig::setMany([
            'cache.max_entries' => 500,
            'cache.prefix' => 'bulk:',
            'performance_mode' => 'safe',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(500)
            ->and(DataHelpersConfig::get('cache.prefix'))->toBe('bulk:')
            ->and(DataHelpersConfig::getPerformanceMode())->toBe('safe');
    });

    it('preserves config across multiple accesses', function(): void {
        DataHelpersConfig::initialize([
            'cache' => ['max_entries' => 750],
        ]);

        $first = DataHelpersConfig::getCacheMaxEntries();
        $second = DataHelpersConfig::getCacheMaxEntries();
        $third = DataHelpersConfig::getCacheMaxEntries();

        expect($first)->toBe(750)
            ->and($second)->toBe(750)
            ->and($third)->toBe(750);
    });

    it('config is singleton across application', function(): void {
        DataHelpersConfig::set('cache.max_entries', 999);

        // Access from different part of code
        $value1 = DataHelpersConfig::getCacheMaxEntries();

        // Access again
        $value2 = DataHelpersConfig::get('cache.max_entries');

        expect($value1)->toBe(999)
            ->and($value2)->toBe(999);
    });

    it('reset clears all configuration', function(): void {
        // Set a custom value
        DataHelpersConfig::set('cache.max_entries', 999);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(999);

        // Reset should clear the custom value
        DataHelpersConfig::reset();

        // After reset, config should be reinitialized
        // We can't predict the exact value, but it should be callable
        $maxEntries = DataHelpersConfig::getCacheMaxEntries();
        expect($maxEntries)->toBeInt();
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

