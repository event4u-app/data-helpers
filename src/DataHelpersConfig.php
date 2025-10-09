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

    /** Reset configuration (for testing). */
    public static function reset(): void
    {
        self::$config = null;
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

