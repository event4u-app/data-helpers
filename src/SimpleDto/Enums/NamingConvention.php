<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Enums;

use event4u\DataHelpers\SimpleDto\Support\NameTransformer;

/**
 * Naming convention enum for property name transformations.
 *
 * Provides type-safe naming conventions for transforming property names
 * between different formats (snake_case, camelCase, kebab-case, PascalCase).
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
 *
 * // Transform a name
 * $snakeName = NamingConvention::SnakeCase->transform('userName');
 * // Result: 'user_name'
 *
 * // Use in attributes
 * #[MapOutputName(NamingConvention::SnakeCase)]
 * public readonly string $userName;
 *
 * // Parse from string
 * $convention = NamingConvention::fromString('snake_case');
 * ```
 */
enum NamingConvention: string
{
    case SnakeCase = 'snake_case';
    case CamelCase = 'camelCase';
    case KebabCase = 'kebab-case';
    case PascalCase = 'PascalCase';

    /**
     * Transform a name to this naming convention.
     *
     * @param string $name The name to transform
     *
     * @return string The transformed name
     */
    public function transform(string $name): string
    {
        return match ($this) {
            self::SnakeCase => NameTransformer::toSnakeCase($name),
            self::CamelCase => NameTransformer::toCamelCase($name),
            self::KebabCase => NameTransformer::toKebabCase($name),
            self::PascalCase => NameTransformer::toPascalCase($name),
        };
    }

    /**
     * Parse a naming convention from a string.
     *
     * @param string $format The format string (e.g., 'snake_case', 'camelCase')
     *
     * @return self|null The naming convention or null if invalid
     */
    public static function fromString(string $format): ?self
    {
        return match ($format) {
            'snake_case' => self::SnakeCase,
            'camelCase' => self::CamelCase,
            'kebab-case' => self::KebabCase,
            'PascalCase' => self::PascalCase,
            default => null,
        };
    }

    /**
     * Get all available naming conventions.
     *
     * @return array<string> Array of convention names
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a string is a valid naming convention.
     *
     * @param string $format The format string to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $format): bool
    {
        return self::fromString($format) instanceof \event4u\DataHelpers\SimpleDto\Enums\NamingConvention;
    }
}
