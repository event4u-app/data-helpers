<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Laravel Config Publishing', function(): void {
    beforeEach(function(): void {
        // Clean up any previously published config
        $configPath = config_path('data-helpers.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
        }
    });

    afterEach(function(): void {
        // Clean up published config
        $configPath = config_path('data-helpers.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
        }
    });

    it('source config file exists in package', function(): void {
        $sourcePath = base_path('vendor/event4u/data-helpers/config/data-helpers.php');

        expect(File::exists($sourcePath))->toBeTrue();
    });

    it('can publish config file via artisan', function(): void {
        $configPath = config_path('data-helpers.php');

        // Config should not exist before publishing
        expect(File::exists($configPath))->toBeFalse();

        // Publish config
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        // Command should succeed
        expect($exitCode)->toBe(0);

        // Config should exist after publishing
        expect(File::exists($configPath))->toBeTrue();
    });

    it('published config is valid PHP', function(): void {
        // Publish config
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        $configPath = config_path('data-helpers.php');

        // Should not throw an error
        $config = require $configPath;

        expect($config)->toBeArray();
    });

    it('published config has correct structure', function(): void {
        // Publish config
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        $configPath = config_path('data-helpers.php');
        $config = require $configPath;

        expect($config)->toHaveKey('cache')
            ->and($config)->toHaveKey('performance_mode')
            ->and($config['cache'])->toHaveKey('driver')
            ->and($config['cache'])->toHaveKey('max_entries')
            ->and($config['cache'])->toHaveKey('default_ttl');
    });

    it('published config has correct default values', function(): void {
        // Publish config
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        $configPath = config_path('data-helpers.php');
        $config = require $configPath;

        // Check default values (using EnvHelper, so they should be the defaults)
        expect($config['cache']['driver'])->toBeString()
            ->and($config['cache']['max_entries'])->toBeInt()
            ->and($config['cache']['default_ttl'])->toBeInt()
            ->and($config['performance_mode'])->toBeString();
    });

    it('published config is identical to source', function(): void {
        // Publish config
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        $sourcePath = base_path('vendor/event4u/data-helpers/config/data-helpers.php');
        $publishedPath = config_path('data-helpers.php');

        $sourceContent = File::get($sourcePath);
        $publishedContent = File::get($publishedPath);

        expect($publishedContent)->toBe($sourceContent);
    });

    it('can republish config with force flag', function(): void {
        $configPath = config_path('data-helpers.php');

        // Publish first time
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        // Modify the file
        File::put($configPath, '<?php return ["modified" => true];');
        $modifiedContent = File::get($configPath);

        // Publish again with force
        Artisan::call('vendor:publish', [
            '--tag' => 'data-helpers-config',
            '--force' => true,
        ]);

        $newContent = File::get($configPath);

        // Should be overwritten (different from modified version)
        expect($newContent)->not->toBe($modifiedContent);

        // Should be valid config again
        $config = require $configPath;
        expect($config)->not->toHaveKey('modified')
            ->and($config)->toHaveKey('cache');
    });

    it('service provider registers publish tag', function(): void {
        $publishes = \event4u\DataHelpers\Laravel\DataHelpersServiceProvider::pathsToPublish(
            \event4u\DataHelpers\Laravel\DataHelpersServiceProvider::class,
            'data-helpers-config'
        );

        expect($publishes)->toBeArray()
            ->and($publishes)->not->toBeEmpty();
    });

    it('published config path is correct', function(): void {
        $publishes = \event4u\DataHelpers\Laravel\DataHelpersServiceProvider::pathsToPublish(
            \event4u\DataHelpers\Laravel\DataHelpersServiceProvider::class,
            'data-helpers-config'
        );

        $sourcePath = realpath(base_path('vendor/event4u/data-helpers/config/data-helpers.php'));
        $publishKeys = array_map('realpath', array_keys($publishes));

        expect($publishKeys)->toContain($sourcePath);
    });
})->group('laravel');

