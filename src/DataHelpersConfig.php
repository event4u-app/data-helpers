<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\Helpers\ConfigHelper;

/**
 * Central configuration for Data Helpers package.
 *
 * This class provides a facade to the ConfigHelper singleton.
 * It automatically detects the framework (Laravel, Symfony, or plain PHP)
 * and loads the appropriate configuration.
 *
 * All operations are delegated to ConfigHelper - this is just a convenience wrapper.
 */
final class DataHelpersConfig
{
    /**
     * Initialize configuration manually (optional).
     *
     * This is useful for testing or when you want to override the auto-detected config.
     *
     * @param array<string, mixed> $config
     */
    public static function initialize(array $config): void
    {
        ConfigHelper::getInstance()->initialize($config);
    }

    /**
     * Get configuration value using dot notation.
     *
     * @param string $key Dot-notation key (e.g., 'cache.max_entries')
     * @param mixed $default Default value if key not found
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return ConfigHelper::getInstance()->get($key, $default);
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
        ConfigHelper::getInstance()->set($key, $value);
    }

    /**
     * Set multiple configuration values at once.
     *
     * @param array<string, mixed> $values Key-value pairs (keys in dot notation)
     */
    public static function setMany(array $values): void
    {
        ConfigHelper::getInstance()->setMany($values);
    }

    /** Reset configuration to original values. */
    public static function reset(): void
    {
        ConfigHelper::getInstance()->reset();
    }

    /** Get the configuration source (laravel, symfony, plain, manual, or default). */
    public static function getSource(): string
    {
        return ConfigHelper::getInstance()->getSource();
    }
}
