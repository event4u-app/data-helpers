<?php

namespace event4u\DataHelpers\Helpers;

use event4u\DataHelpers\Traits\EnvHelperCarbonTrait;
use InvalidArgumentException;
use Stringable;

final class EnvHelper
{
    use EnvHelperCarbonTrait;

    /**
     * Get an environment variable value.
     *
     * Automatically detects the environment:
     * - Laravel: Uses env() function
     * - Symfony: Uses $_ENV (Symfony's env() is different)
     * - Plain PHP: Uses $_ENV
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Check if Laravel's env() function exists
        if (function_exists('env')) {
            return env($key, $default);
        }

        // Fallback to $_ENV for Symfony and Plain PHP
        return $_ENV[$key] ?? $default;
    }

    /**
     * Check if an environment variable exists.
     *
     * @param string $key Environment variable key
     * @return bool True if the variable exists, false otherwise
     */
    public static function has(string $key): bool
    {
        // Check if Laravel's env() function exists
        if (function_exists('env')) {
            return env($key) !== null;
        }

        // Fallback to $_ENV for Symfony and Plain PHP
        return array_key_exists($key, $_ENV);
    }

    public static function string(
        string $key,
        mixed $default = null,
        bool $forceCast = true,
    ): ?string {
        $value = self::get($key, $default);

        if (null === $value) {
            return null;
        }

        if ($forceCast) {
            if (is_numeric($value) || is_string($value)) {
                return (string)$value;
            }

            if (
                is_object($value)
                && (
                    $value instanceof Stringable
                    || method_exists($value, '__toString')
                )
            ) {
                return (string)$value;
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
        }

        self::checkTypeAndThrowException('string', $key, $value);

        return (string)$value;
    }

    public static function integer(
        string $key,
        mixed $default = null,
        bool $forceCast = true,
    ): ?int {
        $value = self::get($key, $default);

        if (null === $value) {
            return null;
        }

        if ($forceCast) {
            if (is_numeric($value) || is_string($value)) {
                return (int)$value;
            }

            if (is_bool($value)) {
                return $value ? 1 : 0;
            }
        }

        self::checkTypeAndThrowException('integer', $key, $value);

        return (int)$value;
    }

    public static function float(
        string $key,
        mixed $default = null,
        bool $forceCast = true,
    ): ?float {
        $value = self::get($key, $default);

        if (null === $value) {
            return null;
        }

        if ($forceCast) {
            if (is_numeric($value) || is_string($value)) {
                return (float)$value;
            }

            if (is_bool($value)) {
                return $value ? 1.0 : 0.0;
            }
        }

        self::checkTypeAndThrowException('float', $key, $value);

        return (float)$value;
    }

    public static function boolean(
        string $key,
        mixed $default = null,
        bool $forceCast = true,
    ): ?bool {
        $value = self::get($key, $default);

        if (null === $value) {
            return null;
        }

        if ($forceCast) {
            if (is_numeric($value)) {
                return (bool)$value;
            }

            if (is_string($value)) {
                return 'true' === strtolower($value);
            }
        }

        self::checkTypeAndThrowException('boolean', $key, $value);

        return (bool)$value;
    }

    /**
     * Get an environment variable as an array.
     *
     * Supports comma-separated values: "value1,value2,value3"
     * Trims whitespace from each value.
     *
     * @param string $key Environment variable key
     * @param null|array<int|string, mixed> $default Default value if not set
     * @param string $separator Separator for splitting (default: ',')
     * @return ?array<int|string, mixed>
     */
    public static function array(
        string $key,
        ?array $default = [],
        string $separator = ',',
    ): ?array {
        $value = self::get($key, $default);

        if (null === $value) {
            return null;
        }

        if (is_string($value) || is_numeric($value)) {
            $value = trim((string)$value);

            if ('' === $value) {
                return null;
            }

            if (
                '' === $separator ||
                !str_contains($value, $separator)
            ) {
                return [$value];
            }

            return array_map('trim', explode($separator, $value));
        }

        self::checkTypeAndThrowException('array', $key, $value);

        return $default;
    }

    private static function checkTypeAndThrowException(
        string $expectedType,
        string $key,
        mixed $value,
    ): void {
        if (is_object($value)) {
            $valueType = $value::class;
        } else {
            $valueType = gettype($value);
        }

        if ($expectedType !== $valueType) {
            throw new InvalidArgumentException(
                sprintf(
                    'Configuration value for key [%s] must be a %s, %s given.',
                    $key,
                    $expectedType,
                    $valueType
                )
            );
        }
    }
}
