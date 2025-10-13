<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Laravel\DataHelpersServiceProvider;

describe('Laravel Service Provider E2E', function(): void {
    it('service provider class exists', function(): void {
        expect(class_exists(DataHelpersServiceProvider::class))->toBeTrue();
    });

    it('can instantiate service provider', function(): void {
        $container = new \Illuminate\Container\Container();
        $provider = new DataHelpersServiceProvider($container);

        expect($provider)->toBeInstanceOf(DataHelpersServiceProvider::class);
    });

    it('config file exists', function(): void {
        $configPath = __DIR__ . '/../../../../config/data-helpers.php';

        expect(file_exists($configPath))->toBeTrue();
    });

    it('config file returns array', function(): void {
        $config = require __DIR__ . '/../../../../config/data-helpers.php';

        expect($config)->toBeArray();
        expect($config)->toHaveKey('cache');
        expect($config)->toHaveKey('performance_mode');
    });

    it('uses default value (1000) when initialized without ENV', function(): void {
        // Initialize DataHelpersConfig with default values (ignoring ENV)
        DataHelpersConfig::initialize([
            'cache' => [
                'max_entries' => 1000,
            ],
            'performance_mode' => 'fast',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(1000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
    });

    it('loads configuration from .env file (250)', function(): void {
        // The .env file has DATA_HELPERS_CACHE_MAX_ENTRIES=250
        // Initialize DataHelpersConfig with ENV values
        DataHelpersConfig::initialize([
            'cache' => [
                'max_entries' => (int)($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] ?? 1000),
            ],
            'performance_mode' => $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] ?? 'fast',
        ]);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(250);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('fast');
    });
});

