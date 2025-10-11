<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

describe('Symfony Recipe Files', function(): void {
    it('recipe directory exists', function(): void {
        $recipePath = __DIR__ . '/../../../../recipe';

        expect(file_exists($recipePath))->toBeTrue()
            ->and(is_dir($recipePath))->toBeTrue();
    });

    it('manifest.json exists and is valid', function(): void {
        $manifestPath = __DIR__ . '/../../../../recipe/manifest.json';

        expect(file_exists($manifestPath))->toBeTrue();

        $content = file_get_contents($manifestPath);
        $manifest = json_decode($content, true);

        expect($manifest)->toBeArray()
            ->and($manifest)->toHaveKey('bundles')
            ->and($manifest)->toHaveKey('copy-from-recipe');
    });

    it('manifest.json has correct bundle configuration', function(): void {
        $manifestPath = __DIR__ . '/../../../../recipe/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        expect($manifest['bundles'])->toHaveKey('event4u\\DataHelpers\\Symfony\\DataHelpersBundle')
            ->and($manifest['bundles']['event4u\\DataHelpers\\Symfony\\DataHelpersBundle'])->toBe(['all']);
    });

    it('manifest.json has correct copy-from-recipe configuration', function(): void {
        $manifestPath = __DIR__ . '/../../../../recipe/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        expect($manifest['copy-from-recipe'])->toHaveKey('config/packages/')
            ->and($manifest['copy-from-recipe'])->toHaveKey('config/services/')
            ->and($manifest['copy-from-recipe']['config/packages/'])->toBe('%CONFIG_DIR%/packages/')
            ->and($manifest['copy-from-recipe']['config/services/'])->toBe('%CONFIG_DIR%/services/');
    });

    it('package config file exists', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';

        expect(file_exists($configPath))->toBeTrue();
    });

    it('package config is valid YAML', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $config = Yaml::parseFile($configPath);

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('data_helpers')
            ->and($config)->toHaveKey('parameters');
    });

    it('package config has correct structure', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $config = Yaml::parseFile($configPath);

        expect($config['data_helpers'])->toHaveKey('cache')
            ->and($config['data_helpers'])->toHaveKey('performance_mode')
            ->and($config['data_helpers']['cache'])->toHaveKey('driver')
            ->and($config['data_helpers']['cache'])->toHaveKey('max_entries')
            ->and($config['data_helpers']['cache'])->toHaveKey('default_ttl')
            ->and($config['data_helpers']['cache'])->toHaveKey('symfony');
    });

    it('package config has correct default parameters', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $config = Yaml::parseFile($configPath);

        expect($config['parameters'])->toHaveKey('env(DATA_HELPERS_CACHE_DRIVER)')
            ->and($config['parameters'])->toHaveKey('env(DATA_HELPERS_CACHE_MAX_ENTRIES)')
            ->and($config['parameters'])->toHaveKey('env(DATA_HELPERS_CACHE_DEFAULT_TTL)')
            ->and($config['parameters'])->toHaveKey('env(DATA_HELPERS_PERFORMANCE_MODE)')
            ->and($config['parameters']['env(DATA_HELPERS_CACHE_DRIVER)'])->toBe('framework')
            ->and($config['parameters']['env(DATA_HELPERS_CACHE_MAX_ENTRIES)'])->toBe(1000)
            ->and($config['parameters']['env(DATA_HELPERS_CACHE_DEFAULT_TTL)'])->toBe(3600)
            ->and($config['parameters']['env(DATA_HELPERS_PERFORMANCE_MODE)'])->toBe('fast');
    });

    it('services config file exists', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';

        expect(file_exists($servicesPath))->toBeTrue();
    });

    it('services config is valid YAML', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';
        $services = Yaml::parseFile($servicesPath);

        expect($services)->toBeArray()
            ->and($services)->toHaveKey('services');
    });

    it('services config has DataMapper service', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';
        $services = Yaml::parseFile($servicesPath);

        expect($services['services'])->toHaveKey('event4u\DataHelpers\DataMapper')
            ->and($services['services']['event4u\DataHelpers\DataMapper'])->toHaveKey('public')
            ->and($services['services']['event4u\DataHelpers\DataMapper']['public'])->toBeTrue();
    });

    it('services config has MappedModelResolver service', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';
        $services = Yaml::parseFile($servicesPath);

        expect($services['services'])->toHaveKey('event4u\DataHelpers\Symfony\MappedModelResolver')
            ->and($services['services']['event4u\DataHelpers\Symfony\MappedModelResolver'])->toHaveKey('tags')
            ->and($services['services']['event4u\DataHelpers\Symfony\MappedModelResolver']['tags'])->toBeArray();
    });

    it('services config has correct service defaults', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';
        $services = Yaml::parseFile($servicesPath);

        expect($services['services'])->toHaveKey('_defaults')
            ->and($services['services']['_defaults'])->toHaveKey('autowire')
            ->and($services['services']['_defaults'])->toHaveKey('autoconfigure')
            ->and($services['services']['_defaults'])->toHaveKey('public')
            ->and($services['services']['_defaults']['autowire'])->toBeFalse()
            ->and($services['services']['_defaults']['autoconfigure'])->toBeFalse()
            ->and($services['services']['_defaults']['public'])->toBeFalse();
    });

    it('recipe config directory structure is correct', function(): void {
        $recipeConfigPath = __DIR__ . '/../../../../recipe/config';

        expect(file_exists($recipeConfigPath))->toBeTrue()
            ->and(is_dir($recipeConfigPath))->toBeTrue()
            ->and(file_exists($recipeConfigPath . '/packages'))->toBeTrue()
            ->and(is_dir($recipeConfigPath . '/packages'))->toBeTrue()
            ->and(file_exists($recipeConfigPath . '/services'))->toBeTrue()
            ->and(is_dir($recipeConfigPath . '/services'))->toBeTrue();
    });

    it('no old config/symfony directory exists', function(): void {
        $oldConfigPath = __DIR__ . '/../../../../config/symfony';

        expect(file_exists($oldConfigPath))->toBeFalse();
    });

    it('package config uses environment variables', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $content = file_get_contents($configPath);

        expect($content)->toContain('%env(DATA_HELPERS_CACHE_DRIVER)%')
            ->and($content)->toContain('%env(int:DATA_HELPERS_CACHE_MAX_ENTRIES)%')
            ->and($content)->toContain('%env(DATA_HELPERS_CACHE_DEFAULT_TTL)%')
            ->and($content)->toContain('%env(DATA_HELPERS_PERFORMANCE_MODE)%');
    });

    it('package config has Symfony cache pool configuration', function(): void {
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $config = Yaml::parseFile($configPath);

        expect($config['data_helpers']['cache']['symfony'])->toHaveKey('pool')
            ->and($config['data_helpers']['cache']['symfony']['pool'])->toBe('@cache.app');
    });

    it('services config has correct tag for MappedModelResolver', function(): void {
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';
        $services = Yaml::parseFile($servicesPath);

        $tags = $services['services']['event4u\DataHelpers\Symfony\MappedModelResolver']['tags'];

        expect($tags)->toBeArray()
            ->and($tags)->toHaveCount(1)
            ->and($tags[0])->toHaveKey('name')
            ->and($tags[0])->toHaveKey('priority')
            ->and($tags[0]['name'])->toBe('controller.argument_value_resolver')
            ->and($tags[0]['priority'])->toBe(50);
    });

    it('manifest.json is valid JSON', function(): void {
        $manifestPath = __DIR__ . '/../../../../recipe/manifest.json';
        $content = file_get_contents($manifestPath);

        json_decode($content);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('all recipe files have correct permissions', function(): void {
        $manifestPath = __DIR__ . '/../../../../recipe/manifest.json';
        $configPath = __DIR__ . '/../../../../recipe/config/packages/data_helpers.yaml';
        $servicesPath = __DIR__ . '/../../../../recipe/config/services/data_helpers.yaml';

        expect(is_readable($manifestPath))->toBeTrue()
            ->and(is_readable($configPath))->toBeTrue()
            ->and(is_readable($servicesPath))->toBeTrue();
    });
})->group('symfony');

