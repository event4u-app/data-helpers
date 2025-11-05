<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Config;
use RuntimeException;

/**
 * Configuration loader for Plain PHP projects.
 *
 * Loads the package default configuration and merges it with user configuration.
 * Supports deep merging of multidimensional arrays (similar to Laravel).
 *
 * Usage:
 * ```php
 * use event4u\DataHelpers\Config\ConfigLoader;
 *
 * // Load with custom config file
 * $config = ConfigLoader::load('/path/to/your/config.php');
 *
 * // Load with custom config array
 * $config = ConfigLoader::load([
 *     'performance_mode' => 'safe',
 *     'cache' => [
 *         'ttl' => 3600,
 *     ],
 * ]);
 *
 * // Initialize DataHelpersConfig
 * DataHelpersConfig::initialize($config);
 * ```
 */
final class ConfigLoader
{
    /**
     * Load configuration with deep merging.
     *
     * Loads the package default configuration and merges it with user configuration.
     * User configuration only needs to specify values that differ from defaults.
     *
     * @param string|array<string, mixed> $userConfig Path to config file or config array
     * @return array<string, mixed> Merged configuration
     */
    public static function load(string|array $userConfig = []): array
    {
        // Load package default config
        $defaultConfig = self::loadDefaultConfig();

        // Load user config
        if (is_string($userConfig)) {
            if (!file_exists($userConfig)) {
                throw new RuntimeException('Config file not found: ' . $userConfig);
            }

            /** @var array<string, mixed> $loadedConfig */
            $loadedConfig = require $userConfig;
            $userConfig = $loadedConfig;
        }

        // Deep merge user config with default config
        return self::mergeRecursive($defaultConfig, $userConfig);
    }

    /**
     * Load the package default configuration.
     *
     * @return array<string, mixed>
     */
    private static function loadDefaultConfig(): array
    {
        $configPath = dirname(__DIR__, 2) . '/config/data-helpers.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException('Package config file not found: ' . $configPath);
        }

        /** @var array<string, mixed> $config */
        $config = require $configPath;

        return $config;
    }

    /**
     * Deep merge two arrays recursively.
     *
     * Similar to Laravel's array_merge_recursive_distinct:
     * - Numeric keys are appended (not merged)
     * - String keys are merged recursively
     * - Scalar values from $array2 override $array1
     *
     * @param array<string, mixed> $array1 Base array (defaults)
     * @param array<string, mixed> $array2 Override array (user config)
     * @return array<string, mixed> Merged array
     */
    private static function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            // If key is numeric, append the value
            if (is_int($key)) {
                $merged[] = $value;
                continue;
            }

            // If both values are arrays, merge recursively
            if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                /** @var array<string, mixed> $mergedValue */
                $mergedValue = $merged[$key];
                /** @var array<string, mixed> $valueArray */
                $valueArray = $value;
                $merged[$key] = self::mergeRecursive($mergedValue, $valueArray);
                continue;
            }

            // Otherwise, override the value
            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * Get the path to the package default config file.
     *
     * Useful for publishing the config file to user's project.
     *
     * @return string Absolute path to config file
     */
    public static function getDefaultConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config/data-helpers.php';
    }

    /**
     * Create a minimal config file with only commonly changed values.
     *
     * This creates a config file that only contains the most commonly changed
     * settings, making it easier for users to customize without being overwhelmed.
     *
     * @param string $targetPath Path where to create the config file
     * @param bool $overwrite Whether to overwrite existing file
     * @return bool True if file was created, false if file exists and overwrite is false
     */
    public static function publish(string $targetPath, bool $overwrite = false): bool
    {
        if (file_exists($targetPath) && !$overwrite) {
            return false;
        }

        $content = <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Data Helpers Configuration
 *
 * This file only contains commonly changed settings.
 * All other settings use package defaults.
 *
 * For full configuration options, see:
 * vendor/event4u/data-helpers/config/data-helpers.php
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Mode
    |--------------------------------------------------------------------------
    |
    | Fast mode uses simplified parsing without escape sequence handling.
    | Safe mode processes all escape sequences (\n, \t, \", \\, etc.).
    |
    | Options: 'fast', 'safe'
    | Default: 'fast'
    |
    */
    'performance_mode' => env('DATA_HELPERS_PERFORMANCE_MODE', 'fast'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Cache directory path
        'path' => env('DATA_HELPERS_CACHE_PATH', './.event4u/data-helpers/cache/'),

        // Cache driver: 'auto', 'filesystem', 'laravel', 'symfony', 'none'
        'driver' => env('DATA_HELPERS_CACHE_DRIVER', 'auto'),

        // Cache TTL in seconds (null = forever)
        'ttl' => env('DATA_HELPERS_CACHE_TTL', null),

        // Enable code generation for optimized performance
        'code_generation' => env('DATA_HELPERS_CODE_GENERATION', true),

        // Cache invalidation: 'manual', 'mtime', 'hash', 'both'
        'invalidation' => env('DATA_HELPERS_CACHE_INVALIDATION', 'mtime'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        // Enable logging
        'enabled' => env('DATA_HELPERS_LOG_ENABLED', false),

        // Logger driver: 'filesystem', 'framework', 'none'
        'driver' => env('DATA_HELPERS_LOG_DRIVER', 'filesystem'),

        // Log path (for filesystem driver)
        'path' => env('DATA_HELPERS_LOG_PATH', './storage/logs/'),

        // Minimum log level: 'debug', 'info', 'warning', 'error'
        'level' => env('DATA_HELPERS_LOG_LEVEL', 'info'),
    ],
];

PHP;

        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return false !== file_put_contents($targetPath, $content);
    }
}
