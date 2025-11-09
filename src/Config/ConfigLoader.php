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
     * Publish the package config file to the target path.
     *
     * Copies the complete package config file to the target location.
     * This is useful for Plain PHP projects that want to customize the configuration.
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

        // Get the package config file path
        $sourceConfigPath = self::getDefaultConfigPath();

        // Read the source config file
        $content = file_get_contents($sourceConfigPath);

        if (false === $content) {
            throw new RuntimeException('Failed to read package config file: ' . $sourceConfigPath);
        }

        // Create directory if it doesn't exist
        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the config file
        return false !== file_put_contents($targetPath, $content);
    }
}
