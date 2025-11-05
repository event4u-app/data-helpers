<?php

declare(strict_types=1);

use event4u\DataHelpers\Config\ConfigLoader;

describe('ConfigLoader', function(): void {
    test('it loads default config when no user config provided', function(): void {
        $config = ConfigLoader::load([]);

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('performance_mode')
            ->and($config)->toHaveKey('cache')
            ->and($config)->toHaveKey('logging');
    });

    test('it merges user config with default config', function(): void {
        $userConfig = [
            'performance_mode' => 'safe',
            'cache' => [
                'ttl' => 3600,
            ],
        ];

        $config = ConfigLoader::load($userConfig);

        // User values should override defaults
        expect($config['performance_mode'])->toBe('safe')
            ->and($config['cache']['ttl'])->toBe(3600);

        // Default values should still be present
        expect($config['cache'])->toHaveKey('path')
            ->and($config['cache'])->toHaveKey('driver')
            ->and($config['cache'])->toHaveKey('code_generation');
    });

    test('it performs deep merge of nested arrays', function(): void {
        $userConfig = [
            'cache' => [
                'ttl' => 7200,
            ],
            'logging' => [
                'enabled' => true,
                'events' => [
                    'mapping_error' => false,
                ],
            ],
        ];

        $config = ConfigLoader::load($userConfig);

        // Deep nested values should be merged
        expect($config['cache']['ttl'])->toBe(7200)
            ->and($config['cache'])->toHaveKey('path') // Default value preserved
            ->and($config['logging']['enabled'])->toBe(true)
            ->and($config['logging'])->toHaveKey('driver') // Default value preserved
            ->and($config['logging']['events'])->toHaveKey('mapping_error');
    });

    test('it loads config from file path', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp files */
        $tempFile = sys_get_temp_dir() . '/test-config-' . uniqid() . '.php';

        file_put_contents($tempFile, '<?php return [
            "performance_mode" => "safe",
            "cache" => [
                "ttl" => 1800,
            ],
        ];');

        $config = ConfigLoader::load($tempFile);

        expect($config['performance_mode'])->toBe('safe')
            ->and($config['cache']['ttl'])->toBe(1800)
            ->and($config['cache'])->toHaveKey('path'); // Default preserved

        unlink($tempFile);
    });

    test('it throws exception when config file not found', function(): void {
        expect(fn(): array => ConfigLoader::load('/non/existent/config.php'))
            ->toThrow(RuntimeException::class, 'Config file not found');
    });

    test('it returns default config path', function(): void {
        $path = ConfigLoader::getDefaultConfigPath();

        expect($path)->toBeString()
            ->and(file_exists($path))->toBeTrue()
            ->and($path)->toContain('config/data-helpers.php');
    });

    test('it publishes minimal config file', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp files */
        $targetPath = sys_get_temp_dir() . '/published-config-' . uniqid() . '.php';

        $result = ConfigLoader::publish($targetPath);

        expect($result)->toBeTrue()
            ->and(file_exists($targetPath))->toBeTrue();

        $content = file_get_contents($targetPath);
        expect($content)->toContain('performance_mode')
            ->and($content)->toContain('cache')
            ->and($content)->toContain('logging')
            ->and($content)->not->toContain('slack') // Minimal config shouldn't have all options
            ->and($content)->not->toContain('grafana');

        unlink($targetPath);
    });

    test('it does not overwrite existing config file by default', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp files */
        $targetPath = sys_get_temp_dir() . '/existing-config-' . uniqid() . '.php';

        file_put_contents($targetPath, '<?php return ["test" => "value"];');

        $result = ConfigLoader::publish($targetPath);

        expect($result)->toBeFalse();

        $content = file_get_contents($targetPath);
        expect($content)->toContain('test');

        unlink($targetPath);
    });

    test('it overwrites existing config file when overwrite is true', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp files */
        $targetPath = sys_get_temp_dir() . '/overwrite-config-' . uniqid() . '.php';

        file_put_contents($targetPath, '<?php return ["test" => "value"];');

        $result = ConfigLoader::publish($targetPath, true);

        expect($result)->toBeTrue();

        $content = file_get_contents($targetPath);
        expect($content)->not->toContain('test')
            ->and($content)->toContain('performance_mode');

        unlink($targetPath);
    });

    test('it creates directory if it does not exist', function(): void {
        /** @phpstan-ignore-next-line uniqid() is fine for test temp directories */
        $tempDir = sys_get_temp_dir() . '/config-test-' . uniqid();
        $targetPath = $tempDir . '/config/data-helpers.php';

        $result = ConfigLoader::publish($targetPath);

        expect($result)->toBeTrue()
            ->and(file_exists($targetPath))->toBeTrue()
            ->and(is_dir($tempDir . '/config'))->toBeTrue();

        unlink($targetPath);
        rmdir($tempDir . '/config');
        rmdir($tempDir);
    });

    test('it preserves all default values when user config is empty', function(): void {
        $defaultConfig = ConfigLoader::load([]);
        $userConfig = ConfigLoader::load([]);

        expect($defaultConfig)->toEqual($userConfig);
    });

    test('it handles multiple levels of nesting', function(): void {
        $userConfig = [
            'logging' => [
                'events' => [
                    'mapping_error' => false,
                ],
                'sampling' => [
                    'errors' => 0.5,
                ],
            ],
        ];

        $config = ConfigLoader::load($userConfig);

        // User values should override
        expect($config['logging']['events']['mapping_error'])->toBe(false)
            ->and($config['logging']['sampling']['errors'])->toBe(0.5);

        // Other default values should be preserved
        expect($config['logging'])->toHaveKey('enabled')
            ->and($config['logging'])->toHaveKey('driver')
            ->and($config['logging']['events'])->toHaveKey('exception')
            ->and($config['logging']['sampling'])->toHaveKey('success');
    });

    test('it handles scalar value override', function(): void {
        $userConfig = [
            'performance_mode' => 'safe',
        ];

        $config = ConfigLoader::load($userConfig);

        expect($config['performance_mode'])->toBe('safe');
    });

    test('it handles null values', function(): void {
        $userConfig = [
            'cache' => [
                'ttl' => null,
            ],
        ];

        $config = ConfigLoader::load($userConfig);

        expect($config['cache']['ttl'])->toBeNull();
    });

    test('it handles boolean values', function(): void {
        $userConfig = [
            'logging' => [
                'enabled' => true,
            ],
            'cache' => [
                'code_generation' => false,
            ],
        ];

        $config = ConfigLoader::load($userConfig);

        expect($config['logging']['enabled'])->toBe(true)
            ->and($config['cache']['code_generation'])->toBe(false);
    });
});
