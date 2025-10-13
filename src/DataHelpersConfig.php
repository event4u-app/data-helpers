<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Config\ConfigHelper;

/**
 * Central configuration for Data Helpers package.
 *
 * This class provides a facade to the ConfigHelper singleton.
 * It automatically detects the framework (Laravel, Symfony, or plain PHP)
 * and loads the appropriate configuration.
 */
final class DataHelpersConfig
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /** @var array<string, mixed>|null Original config for reset */
    private static ?array $originalConfig = null;

    /**
     * Initialize configuration manually (optional).
     *
     * This is useful for testing or when you want to override the auto-detected config.
     *
     * @param array<string, mixed> $config
     */
    public static function initialize(array $config): void
    {
        self::$config = $config;
        self::$originalConfig = $config;
    }

    /**
     * Get configuration value using dot notation.
     *
     * @param string $key Dot-notation key (e.g., 'cache.max_entries')
     * @param mixed $default Default value if key not found
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // If manually initialized, use that config
        if (null !== self::$config) {
            return self::getFromManualConfig($key, $default);
        }

        // Otherwise, use ConfigHelper (auto-detects framework)
        return ConfigHelper::getInstance()->get($key, $default);
    }

    /** Get value from manually initialized config. */
    private static function getFromManualConfig(string $key, mixed $default): mixed
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** Get cache max entries setting. */
    public static function getCacheMaxEntries(): int
    {
        return (int)self::get('cache.max_entries', 1000);
    }

    /** Get performance mode setting. */
    public static function getPerformanceMode(): string
    {
        return (string)self::get('performance_mode', 'fast');
    }

    /** Check if fast mode is enabled. */
    public static function isFastMode(): bool
    {
        return 'fast' === self::getPerformanceMode();
    }

    /**
     * Set a single configuration value using dot notation.
     *
     * @param string $key Dot-notation key (e.g., 'logging.enabled')
     * @param mixed $value Value to set
     */
    public static function set(string $key, mixed $value): void
    {
        // Ensure config is initialized
        if (null === self::$config) {
            self::$config = [];
            self::$originalConfig = [];
        }

        // Split key into segments
        $keys = explode('.', $key);

        // Build the nested array structure
        self::$config = self::setNestedValue(self::$config, $keys, $value);
    }

    /**
     * Recursively set a nested value in an array.
     *
     * @param array<string, mixed> $array The array to modify
     * @param array<int, string> $keys The path to the value
     * @param mixed $value The value to set
     * @return array<string, mixed> The modified array
     */
    private static function setNestedValue(array $array, array $keys, mixed $value): array
    {
        $key = array_shift($keys);

        if ([] === $keys) {
            // Last key - set the value
            $array[$key] = $value;
        } else {
            // Intermediate key - recurse
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array[$key] = self::setNestedValue($array[$key], $keys, $value);
        }

        return $array;
    }

    /**
     * Set multiple configuration values at once.
     *
     * @param array<string, mixed> $values Key-value pairs (keys in dot notation)
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Reset configuration.
     *
     * By default, completely resets to null (uses framework config).
     * If $toOriginal is true, resets to the original config from initialize().
     *
     * @param bool $toOriginal If true, resets to original config. If false (default), completely resets to null.
     */
    public static function reset(bool $toOriginal = false): void
    {
        if ($toOriginal && null !== self::$originalConfig) {
            self::$config = self::$originalConfig;
        } else {
            self::$config = null;
            if (!$toOriginal) {
                self::$originalConfig = null;
            }
        }
        ConfigHelper::reset();
    }

    /** Get the configuration source (laravel, symfony, plain, or default). */
    public static function getSource(): string
    {
        if (null !== self::$config) {
            return 'manual';
        }

        return ConfigHelper::getInstance()->getSource();
    }
}

