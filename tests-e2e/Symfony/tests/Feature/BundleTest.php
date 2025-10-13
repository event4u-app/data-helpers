<?php

declare(strict_types=1);

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Symfony\DataHelpersBundle;

describe('Symfony Bundle E2E', function(): void {
    it('bundle class exists', function(): void {
        expect(class_exists(DataHelpersBundle::class))->toBeTrue();
    });

    it('can instantiate bundle', function(): void {
        $bundle = new DataHelpersBundle();

        expect($bundle)->toBeInstanceOf(DataHelpersBundle::class);
    });

    it('config file exists', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';

        expect(file_exists($configPath))->toBeTrue();
    });

    it('services file exists', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';

        expect(file_exists($servicesPath))->toBeTrue();
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

