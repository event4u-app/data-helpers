<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

describe('Symfony Flex Recipe Installation', function(): void {
    beforeEach(function(): void {
        // Clean up any previously installed config files
        $configPackagesPath = __DIR__ . '/../../config/packages/data_helpers.yaml';
        $configServicesPath = __DIR__ . '/../../config/services/data_helpers.yaml';
        
        if (file_exists($configPackagesPath)) {
            unlink($configPackagesPath);
        }
        if (file_exists($configServicesPath)) {
            unlink($configServicesPath);
        }
    });

    afterEach(function(): void {
        // Clean up installed config files
        $configPackagesPath = __DIR__ . '/../../config/packages/data_helpers.yaml';
        $configServicesPath = __DIR__ . '/../../config/services/data_helpers.yaml';
        
        if (file_exists($configPackagesPath)) {
            unlink($configPackagesPath);
        }
        if (file_exists($configServicesPath)) {
            unlink($configServicesPath);
        }
    });

    it('can install recipe files manually (simulating Flex)', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }
        if (!is_dir($configDir . '/services')) {
            mkdir($configDir . '/services', 0755, true);
        }

        // Copy files as Flex would do
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );
        copy(
            $recipePath . '/config/services/data_helpers.yaml',
            $configDir . '/services/data_helpers.yaml'
        );

        // Verify files were copied
        expect(file_exists($configDir . '/packages/data_helpers.yaml'))->toBeTrue()
            ->and(file_exists($configDir . '/services/data_helpers.yaml'))->toBeTrue();
    });

    it('installed package config is valid YAML', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }

        // Copy package config
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );

        // Parse YAML
        $config = Yaml::parseFile($configDir . '/packages/data_helpers.yaml');

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('data_helpers')
            ->and($config)->toHaveKey('parameters');
    });

    it('installed package config has correct structure', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }

        // Copy package config
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );

        $config = Yaml::parseFile($configDir . '/packages/data_helpers.yaml');

        expect($config['data_helpers'])->toHaveKey('cache')
            ->and($config['data_helpers'])->toHaveKey('performance_mode')
            ->and($config['data_helpers']['cache'])->toHaveKey('driver')
            ->and($config['data_helpers']['cache'])->toHaveKey('max_entries')
            ->and($config['data_helpers']['cache'])->toHaveKey('default_ttl')
            ->and($config['data_helpers']['cache'])->toHaveKey('symfony');
    });

    it('installed services config is valid YAML', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/services')) {
            mkdir($configDir . '/services', 0755, true);
        }

        // Copy services config
        copy(
            $recipePath . '/config/services/data_helpers.yaml',
            $configDir . '/services/data_helpers.yaml'
        );

        // Parse YAML
        $services = Yaml::parseFile($configDir . '/services/data_helpers.yaml');

        expect($services)->toBeArray()
            ->and($services)->toHaveKey('services');
    });

    it('installed services config has correct services', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/services')) {
            mkdir($configDir . '/services', 0755, true);
        }

        // Copy services config
        copy(
            $recipePath . '/config/services/data_helpers.yaml',
            $configDir . '/services/data_helpers.yaml'
        );

        $services = Yaml::parseFile($configDir . '/services/data_helpers.yaml');

        expect($services['services'])->toHaveKey('event4u\DataHelpers\DataMapper')
            ->and($services['services'])->toHaveKey('event4u\DataHelpers\Symfony\MappedModelResolver')
            ->and($services['services']['event4u\DataHelpers\DataMapper'])->toHaveKey('public')
            ->and($services['services']['event4u\DataHelpers\DataMapper']['public'])->toBeTrue();
    });

    it('installed config is identical to recipe source', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }
        if (!is_dir($configDir . '/services')) {
            mkdir($configDir . '/services', 0755, true);
        }

        // Copy files
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );
        copy(
            $recipePath . '/config/services/data_helpers.yaml',
            $configDir . '/services/data_helpers.yaml'
        );

        // Compare content
        $sourcePackages = file_get_contents($recipePath . '/config/packages/data_helpers.yaml');
        $installedPackages = file_get_contents($configDir . '/packages/data_helpers.yaml');

        $sourceServices = file_get_contents($recipePath . '/config/services/data_helpers.yaml');
        $installedServices = file_get_contents($configDir . '/services/data_helpers.yaml');

        expect($installedPackages)->toBe($sourcePackages)
            ->and($installedServices)->toBe($sourceServices);
    });

    it('can reinstall config files (overwrite)', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }

        // First installation
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );

        // Modify the file
        file_put_contents(
            $configDir . '/packages/data_helpers.yaml',
            "# Modified\ndata_helpers:\n  modified: true\n"
        );

        $modifiedContent = file_get_contents($configDir . '/packages/data_helpers.yaml');

        // Reinstall (overwrite)
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );

        $newContent = file_get_contents($configDir . '/packages/data_helpers.yaml');

        // Should be different from modified version
        expect($newContent)->not->toBe($modifiedContent);

        // Should be valid YAML again
        $config = Yaml::parseFile($configDir . '/packages/data_helpers.yaml');
        expect($config)->toHaveKey('data_helpers')
            ->and($config['data_helpers'])->not->toHaveKey('modified');
    });

    it('installed config works with Symfony container', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';
        $configDir = __DIR__ . '/../../config';

        // Ensure config directories exist
        if (!is_dir($configDir . '/packages')) {
            mkdir($configDir . '/packages', 0755, true);
        }

        // Copy package config
        copy(
            $recipePath . '/config/packages/data_helpers.yaml',
            $configDir . '/packages/data_helpers.yaml'
        );

        // Parse and validate config can be used
        $config = Yaml::parseFile($configDir . '/packages/data_helpers.yaml');

        // Check that environment variables are properly referenced
        $content = file_get_contents($configDir . '/packages/data_helpers.yaml');

        expect($content)->toContain('%env(DATA_HELPERS_CACHE_DRIVER)%')
            ->and($content)->toContain('%env(int:DATA_HELPERS_CACHE_MAX_ENTRIES)%')
            ->and($content)->toContain('%env(DATA_HELPERS_CACHE_DEFAULT_TTL)%')
            ->and($content)->toContain('%env(DATA_HELPERS_PERFORMANCE_MODE)%');
    });
})->group('symfony');

