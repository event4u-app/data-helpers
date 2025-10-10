<?php

declare(strict_types=1);

namespace Tests\Integration\Config;

use event4u\DataHelpers\Config\ConfigHelper;
use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Symfony\DataHelpersExtension;
use Exception;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

describe('Symfony Config Integration', function(): void {
    beforeEach(function(): void {
        // Skip if Symfony is not available
        if (!class_exists('Symfony\Component\DependencyInjection\ContainerBuilder')) {
            $this->markTestSkipped('Symfony is not available');
        }

        ConfigHelper::reset();
        DataHelpersConfig::reset();
    });

    afterEach(function(): void {
        ConfigHelper::reset();
        DataHelpersConfig::reset();
    });

    it('loads config from Symfony Configuration', function(): void {
        $extension = new DataHelpersExtension();
        $processor = new Processor();

        // Process empty config (should use defaults)
        $config = $processor->processConfiguration($extension, []);

        expect($config)->toBeArray();
        expect($config)->toHaveKey('cache');
        expect($config)->toHaveKey('performance_mode');
        expect($config['cache']['max_entries'])->toBe(1000);
        expect($config['performance_mode'])->toBe('fast');
    });

    it('validates config values', function(): void {
        $extension = new DataHelpersExtension();
        $processor = new Processor();

        // Process custom config
        $config = $processor->processConfiguration($extension, [
            'data_helpers' => [
                'cache' => [
                    'max_entries' => 5000,
                ],
                'performance_mode' => 'safe',
            ],
        ]);

        expect($config['cache']['max_entries'])->toBe(5000);
        expect($config['performance_mode'])->toBe('safe');
    });

    it('rejects invalid performance mode', function(): void {
        $extension = new DataHelpersExtension();
        $processor = new Processor();

        expect(function() use ($extension, $processor): void {
            $processor->processConfiguration($extension, [
                'data_helpers' => [
                    'performance_mode' => 'invalid',
                ],
            ]);
        })->toThrow(Exception::class);
    });

    it('extension loads config correctly', function(): void {
        $container = new ContainerBuilder();
        $extension = new DataHelpersExtension();

        // Load extension with default config
        $extension->load([], $container);

        // Check parameters were set
        expect($container->hasParameter('data_helpers.cache.max_entries'))->toBeTrue();
        expect($container->hasParameter('data_helpers.performance_mode'))->toBeTrue();
        expect($container->getParameter('data_helpers.cache.max_entries'))->toBe(1000);
        expect($container->getParameter('data_helpers.performance_mode'))->toBe('fast');
    });

    it('extension loads custom config', function(): void {
        $container = new ContainerBuilder();
        $extension = new DataHelpersExtension();

        // Load extension with custom config
        // @phpstan-ignore-next-line - Array structure is correct for Symfony config
        $extension->load([
            'data_helpers' => [
                'cache' => [
                    'max_entries' => 3000,
                ],
                'performance_mode' => 'safe',
            ],
        ], $container);

        expect($container->getParameter('data_helpers.cache.max_entries'))->toBe(3000);
        expect($container->getParameter('data_helpers.performance_mode'))->toBe('safe');
    });

    it('extension has correct alias', function(): void {
        $extension = new DataHelpersExtension();

        expect($extension->getAlias())->toBe('data_helpers');
    });

    it('extension provides config path', function(): void {
        $extension = new DataHelpersExtension();
        $configPath = $extension->getConfigPath();

        expect($configPath)->toBeString();
        expect(file_exists($configPath))->toBeTrue();
    });

    it('initializes DataHelpersConfig via extension', function(): void {
        $container = new ContainerBuilder();
        $extension = new DataHelpersExtension();

        // Load extension
        // @phpstan-ignore-next-line - Array structure is correct for Symfony config
        $extension->load([
            'data_helpers' => [
                'cache' => [
                    'max_entries' => 2000,
                ],
                'performance_mode' => 'safe',
            ],
        ], $container);

        // DataHelpersConfig should be initialized
        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(2000);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');
        expect(DataHelpersConfig::isFastMode())->toBeFalse();
    });

    it('handles ENV variables in Symfony config', function(): void {
        // Set ENV variables
        $_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'] = '1500';
        $_ENV['DATA_HELPERS_PERFORMANCE_MODE'] = 'safe';

        $container = new ContainerBuilder();
        $extension = new DataHelpersExtension();

        // In real Symfony, ENV variables would be resolved by the container
        // For testing, we simulate this
        $maxEntries = (int)$_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES'];
        $performanceMode = $_ENV['DATA_HELPERS_PERFORMANCE_MODE'];

        // @phpstan-ignore-next-line - Array structure is correct for Symfony config
        $extension->load([
            'data_helpers' => [
                'cache' => [
                    'max_entries' => $maxEntries,
                ],
                'performance_mode' => $performanceMode,
            ],
        ], $container);

        expect(DataHelpersConfig::getCacheMaxEntries())->toBe(1500);
        expect(DataHelpersConfig::getPerformanceMode())->toBe('safe');

        // Cleanup
        unset($_ENV['DATA_HELPERS_CACHE_MAX_ENTRIES']);
        unset($_ENV['DATA_HELPERS_PERFORMANCE_MODE']);
    });

    it('config file exists and is valid YAML', function(): void {
        $configPath = __DIR__ . '/../../../recipe/config/packages/data_helpers.yaml';

        expect(file_exists($configPath))->toBeTrue();

        // Read and parse YAML (basic check)
        $content = file_get_contents($configPath);
        expect($content)->toContain('data_helpers:');
        expect($content)->toContain('cache:');
        expect($content)->toContain('max_entries:');
        expect($content)->toContain('performance_mode:');
    });

    it('validates integer type for max_entries', function(): void {
        $extension = new DataHelpersExtension();
        $processor = new Processor();

        // Integer value should be accepted
        $config = $processor->processConfiguration($extension, [
            'data_helpers' => [
                'cache' => [
                    'max_entries' => 2000,
                ],
            ],
        ]);

        expect($config['cache']['max_entries'])->toBe(2000);
        expect($config['cache']['max_entries'])->toBeInt();
    });

    it('provides default values when partial config is given', function(): void {
        $extension = new DataHelpersExtension();
        $processor = new Processor();

        // Only provide cache config
        $config = $processor->processConfiguration($extension, [
            'data_helpers' => [
                'cache' => [
                    'max_entries' => 500,
                ],
            ],
        ]);

        expect($config['cache']['max_entries'])->toBe(500);
        expect($config['performance_mode'])->toBe('fast'); // default
    });
})->skip(
    !class_exists('Symfony\Component\DependencyInjection\ContainerBuilder'),
    'Symfony is not available'
);

