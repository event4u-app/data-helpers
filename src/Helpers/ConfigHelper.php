<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Helpers;

use Throwable;

/**
 * Universal configuration helper that works with Laravel, Symfony, or plain PHP.
 *
 * Automatically detects the framework and loads configuration accordingly:
 * 1. Laravel (if available)
 * 2. Symfony (if available)
 * 3. Plain PHP (fallback - loads config/laravel/data-helpers.php)
 *
 * Supports dot notation for nested values: 'cache.max_entries'
 */
final class ConfigHelper
{
    private const CONFIG_PREFIX = 'data-helpers';

    private static ?self $instance = null;

    /** @var array<string, mixed> Current configuration (can be modified) */
    private array $config = [];

    /** @var array<string, mixed> Original configuration (immutable, for reset) */
    private array $configOriginal = [];

    private string $source = 'unknown';

    private function __construct()
    {
        $this->loadConfig();
        $this->configOriginal = $this->config;
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof ConfigHelper) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** Reset the singleton instance (useful for testing). */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /** Get configuration value using dot notation. */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->getNestedValue($this->config, $key);

        return $value ?? $default;
    }

    /** Get configuration value as boolean. */
    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool)$value;
    }

    /** Get configuration value as integer. */
    public function getInteger(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return (int)$value;
    }

    /** Get configuration value as float. */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);

        return (float)$value;
    }

    /** Get configuration value as string. */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return (string)$value;
    }

    /**
     * Get configuration value as array.
     *
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    /** Check if configuration key exists. */
    public function has(string $key): bool
    {
        return null !== $this->getNestedValue($this->config, $key);
    }

    /** Get the configuration source (laravel, symfony, or plain). */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get all configuration.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Set a configuration value using dot notation.
     *
     * @param string $key Dot-notation key (e.g., 'logging.enabled')
     * @param mixed $value Value to set
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $this->config = $this->setNestedValue($this->config, $keys, $value);
    }

    /**
     * Set multiple configuration values at once.
     *
     * @param array<string, mixed> $values Key-value pairs (keys in dot notation)
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /** Reset configuration to original values. */
    public function reset(): void
    {
        $this->config = $this->configOriginal;
    }

    /**
     * Initialize configuration manually (for testing).
     *
     * @param array<string, mixed> $config
     */
    public function initialize(array $config): void
    {
        $this->config = $config;
        $this->configOriginal = $config;
        $this->source = 'manual';
    }

    /** Load configuration from the appropriate source. */
    private function loadConfig(): void
    {
        // Try Laravel first
        if ($this->isLaravelAvailable()) {
            $this->loadLaravelConfig();

            return;
        }

        // Try Symfony second
        if ($this->isSymfonyAvailable()) {
            $this->loadSymfonyConfig();

            return;
        }

        // Fallback to plain PHP
        $this->loadPlainPhpConfig();
    }

    /** Check if Laravel is available. */
    private function isLaravelAvailable(): bool
    {
        return function_exists('app') && function_exists('config');
    }

    /** Check if Symfony is available. */
    private function isSymfonyAvailable(): bool
    {
        return class_exists('Symfony\Component\DependencyInjection\ContainerInterface');
    }

    /** Load configuration from Laravel. */
    private function loadLaravelConfig(): void
    {
        try {
            /** @var array<string, mixed> $config */
            // @phpstan-ignore-next-line - config() is a Laravel helper function
            $config = config(self::CONFIG_PREFIX, []);
            $this->config = $config;
            $this->source = 'laravel';
        } catch (Throwable) {
            // Fallback to plain PHP if Laravel config fails
            $this->loadPlainPhpConfig();
        }
    }

    /** Load configuration from Symfony. */
    private function loadSymfonyConfig(): void
    {
        try {
            // Try to get the container
            if (class_exists('Symfony\Component\HttpKernel\Kernel')) {
                // In Symfony application context
                // We can't directly access the container here, so we use parameters
                // that were set by the Extension
                $this->config = [
                    'cache' => [
                        'max_entries' => $this->getSymfonyParameter('data_helpers.cache.max_entries', 1000),
                    ],
                    'performance_mode' => $this->getSymfonyParameter('data_helpers.performance_mode', 'fast'),
                ];
                $this->source = 'symfony';

                return;
            }
        } catch (Throwable) {
            // Ignore and fallback
        }

        // Fallback to plain PHP
        $this->loadPlainPhpConfig();
    }

    /** Get Symfony parameter (this is a simplified version). */
    private function getSymfonyParameter(string $name, mixed $default): mixed
    {
        // In a real Symfony app, this would be injected via DI
        // For now, we return the default
        return $default;
    }

    /** Load configuration from plain PHP file. */
    private function loadPlainPhpConfig(): void
    {
        $configPath = $this->findConfigPath();

        if (null === $configPath || !file_exists($configPath)) {
            // Use default configuration
            $this->config = $this->getDefaultConfig();
            $this->source = 'default';

            return;
        }

        /** @var array<string, mixed> $config */
        $config = require $configPath;
        $this->config = $config;

        $this->source = 'plain';
    }

    /** Find the configuration file path. */
    private function findConfigPath(): ?string
    {
        // Try multiple possible locations
        $possiblePaths = [
            // In application (after publish)
            getcwd() . '/config/data-helpers.php',
            // In package plain folder
            __DIR__ . '/../../config/plain/data-helpers.php',
            // Relative to vendor (after publish)
            dirname(__DIR__, 3) . '/config/data-helpers.php',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get default configuration.
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'cache' => [
                'max_entries' => 1000,
            ],
            'performance_mode' => 'fast',
        ];
    }

    /**
     * Get nested value from array using dot notation.
     *
     * @param array<string, mixed> $array
     */
    private function getNestedValue(array $array, string $key): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Recursively set a nested value in an array.
     *
     * @param array<string, mixed> $array The array to modify
     * @param array<int, string> $keys The path to the value
     * @param mixed $value The value to set
     * @return array<string, mixed> The modified array
     */
    private function setNestedValue(array $array, array $keys, mixed $value): array
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
            $array[$key] = $this->setNestedValue($array[$key], $keys, $value);
        }

        return $array;
    }
}
